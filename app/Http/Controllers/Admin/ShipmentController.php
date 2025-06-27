<?php

namespace App\Http\Controllers\Admin;


use App\Models\Flight;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HasMorphMapTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\ShipmentRequest;
use App\Http\Resources\Admin\ShipmentResource;
use App\Http\Resources\Admin\ShowAllShipmentResource;

class ShipmentController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;
    use HasMorphMapTrait;

public function showAllWithPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = Shipment::query();


    if ($request->filled('company_id')) {
        $query->where('company_id', $request->company_id);
    }

    if ($request->filled('service_id')) {
        $query->where('service_id', $request->service_id);
    }

       if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    if ($request->filled('fromDate')) {
    $query->whereDate('creationDate', '>=', $request->fromDate);
}

if ($request->filled('toDate')) {
    $query->whereDate('creationDate', '<=', $request->toDate);
}


    $query->orderBy('created_at', 'desc');

    $Shipments = $query->paginate(10);

    return response()->json([
        'data' => ShowAllShipmentResource::collection($Shipments),
        'pagination' => [
            'total' => $Shipments->total(),
            'count' => $Shipments->count(),
            'per_page' => $Shipments->perPage(),
            'current_page' => $Shipments->currentPage(),
            'total_pages' => $Shipments->lastPage(),
            'next_page_url' => $Shipments->nextPageUrl(),
            'prev_page_url' => $Shipments->previousPageUrl(),
        ],
        'message' => "Show All Shipments."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = Shipment::query();

       if ($request->filled('service_id')) {
        $query->where('service_id', $request->service_id);
    }

    if ($request->filled('company_id')) {
        $query->where('company_id', $request->company_id);
    }

     if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    if ($request->filled('fromDate')) {
    $query->whereDate('creationDate', '>=', $request->fromDate);
}

if ($request->filled('toDate')) {
    $query->whereDate('creationDate', '<=', $request->toDate);
}


    $query->orderBy('created_at', 'desc');

    $Shipment = $query->get();

    return response()->json([
        'data' => ShowAllShipmentResource::collection($Shipment),
        'message' => "Show All Ihram Supplies."
    ]);
}


public function create(ShipmentRequest $request)
{
    $this->authorize('manage_system');

    try {
        $shipment = DB::transaction(function () use ($request) {
            $data = array_merge($request->only([
                'company_id', 'supplier_id', 'service_id', 'description'
            ]), $this->prepareCreationMetaData());

            $data['status'] = $data['status'] ?? 'active';
            $data['totalPrice'] = 0;

            $shipment = Shipment::create($data);
            $total = 0;

            foreach ($request->items as $item) {
                $morphClass = $this->getMorphClass($item['item_type']);

                if (!class_exists($morphClass)) {
                    throw new \Exception("Class {$morphClass} not found");
                }

                $itemTotal = $item['quantity'] * $item['unitPrice'];
                $total += $itemTotal;

                $rentalStartHijri = $item['rentalStart'] ? $this->getHijriDate($item['rentalStart']) : null;
                $rentalEndHijri = $item['rentalEnd'] ? $this->getHijriDate($item['rentalEnd']) : null;
                $tripDateHijri = $item['DateTimeTrip'] ? $this->getHijriDate($item['DateTimeTrip']) : null;

                $shipmentItem = ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'item_id' => $item['item_id'],
                    'item_type' => $morphClass,
                    'quantity' => $item['quantity'],
                    'unitPrice' => $item['unitPrice'],
                    'totalPrice' => $itemTotal,
                    'rentalStart' => $item['rentalStart'] ?? null,
                    'rentalEnd' => $item['rentalEnd'] ?? null,
                    'rentalStartHijri' => $rentalStartHijri,
                    'rentalEndHijri' => $rentalEndHijri,
                    'DateTimeTrip' => $item['DateTimeTrip'] ?? null,
                    'DateTimeTripHijri' => $tripDateHijri,
                    'seatNum' => $item['seatNum'] ?? null,
                    'class' => $item['class'] ?? null,
                    'roomType' => $item['roomType'] ?? null,
                    'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    'creationDateHijri' => $this->getHijriDate(),
                ]);

                $shipment->updateItemsCount();

                $itemModel = $morphClass::find($item['item_id']);

                if ($itemModel) {
                    $itemModel->increment('quantity', $item['quantity']);

                    $updateData = [
                        'purchesPrice' => $item['unitPrice'],
                        'profit' => isset($itemModel->sellingPrice) ?
                            $itemModel->sellingPrice - $item['unitPrice'] : null
                    ];

                    // تحديث البيانات حسب نوع العنصر
                    if ($itemModel instanceof Flight) {
                        if (isset($item['class'])) {
                            $updateData['class'] = $item['class'];
                        }
                        if (isset($item['seatNum'])) {
                            $updateData['seatNum'] = $item['seatNum'];
                        }
                        if (isset($item['DateTimeTrip'])) {
                            $updateData['DateTimeTrip'] = $item['DateTimeTrip'];
                            $updateData['DateTimeTripHijri'] = $tripDateHijri;
                        }
                    }
                    elseif ($itemModel instanceof Bus || $itemModel instanceof Hotel) {
                        if (isset($item['rentalStart'])) {
                            $updateData['rentalStart'] = $item['rentalStart'];
                            $updateData['rentalStartHijri'] = $rentalStartHijri;
                        }
                        if (isset($item['rentalEnd'])) {
                            $updateData['rentalEnd'] = $item['rentalEnd'];
                            $updateData['rentalEndHijri'] = $rentalEndHijri;
                        }

                   

                    $itemModel->update($updateData);

                    Log::info("تم تحديث العنصر", [
                        'model' => $morphClass,
                        'id' => $item['item_id'],
                        'quantity_added' => $item['quantity'],
                        'purchesPrice_updated' => $item['unitPrice'],
                        'new_quantity' => $itemModel->quantity
                    ]);
                }
            }

            $shipment->update(['totalPrice' => $total]);
            return $shipment;
        });

        $this->loadCommonRelations($shipment);
        $shipment->load('items');

        return $this->respondWithResource($shipment, "تم إنشاء الشحنة وزيادة الكميات وتحديث البيانات بنجاح.");

    } catch (\Exception $e) {
        Log::error('فشل إنشاء الشحنة', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الشحنة: ' . $e->getMessage(),
            'error' => $e->getMessage(),
        ], 500);
    }
}

    // public function create(ShipmentRequest $request)
    // {
    //     $this->authorize('manage_system');

    //     try {
    //         $shipment = DB::transaction(function () use ($request) {
    //             $data = array_merge($request->only([
    //                 'company_id', 'supplier_id', 'service_id', 'description'
    //             ]), $this->prepareCreationMetaData());

    //             $data['status'] = $data['status'] ?? 'active';
    //             $data['totalPrice'] = 0;

    //             $shipment = Shipment::create($data);
    //             $total = 0;

    //             foreach ($request->items as $item) {
    //                 $morphClass = $this->getMorphClass($item['item_type']);

    //                 if (!class_exists($morphClass)) {
    //                     throw new \Exception("Class {$morphClass} not found");
    //                 }

    //                 $itemTotal = $item['quantity'] * $item['unitPrice'];
    //                 $total += $itemTotal;

    //                 $rentalStartHijri = $item['rentalStart'] ? $this->getHijriDate($item['rentalStart']) : null;
    //                 $rentalEndHijri = $item['rentalEnd'] ? $this->getHijriDate($item['rentalEnd']) : null;
    //                 $tripDateHijri = $item['DateTimeTrip'] ? $this->getHijriDate($item['DateTimeTrip']) : null;

    //                 $shipmentItem = ShipmentItem::create([
    //                     'shipment_id' => $shipment->id,
    //                     'item_id' => $item['item_id'],
    //                     'item_type' => $morphClass,
    //                     'quantity' => $item['quantity'],
    //                     'unitPrice' => $item['unitPrice'],
    //                     'totalPrice' => $itemTotal,
    //                     'rentalStart' => $item['rentalStart'] ?? null,
    //                     'rentalEnd' => $item['rentalEnd'] ?? null,
    //                     'rentalStartHijri' => $rentalStartHijri,
    //                     'rentalEndHijri' => $rentalEndHijri,
    //                     'DateTimeTrip' => $item['DateTimeTrip'] ?? null,
    //                     'DateTimeTripHijri' => $tripDateHijri,
    //                     'seatNum' => $item['seatNum'] ?? null,
    //                     'class' => $item['class'] ?? null,
    //                     'roomType' => $item['roomType'] ?? null,
    //                     'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
    //                     'creationDateHijri' => $this->getHijriDate(),
    //                 ]);

    //                 $shipment->updateItemsCount();

    //                 $itemModel = $morphClass::find($item['item_id']);

    //                 if ($itemModel) {

    //                     $itemModel->increment('quantity', $item['quantity']);

    //                     if ($itemModel instanceof Flight) {
    //                         $updateData = [
    //                             'purchesPrice' => $item['unitPrice'],
    //                             'profit' => isset($itemModel->sellingPrice) ?
    //                                 $itemModel->sellingPrice - $item['unitPrice'] : null
    //                         ];

    //                         if (isset($item['class'])) {
    //                             $updateData['class'] = $item['class'];
    //                         }
    //                         if (isset($item['seatNum'])) {
    //                             $updateData['seatNum'] = $item['seatNum'];
    //                         }
    //                         if (isset($item['DateTimeTrip'])) {
    //                             $updateData['DateTimeTrip'] = $item['DateTimeTrip'];
    //                             $updateData['DateTimeTripHijri'] = $tripDateHijri;
    //                         }

    //                         $itemModel->update($updateData);
    //                     }

    //                     Log::info("تم تحديث العنصر", [
    //                         'model' => $morphClass,
    //                         'id' => $item['item_id'],
    //                         'quantity_added' => $item['quantity'],
    //                         'purchesPrice_updated' => $item['unitPrice'],
    //                         'new_quantity' => $itemModel->quantity
    //                     ]);
    //                 }
    //             }

    //             $shipment->update(['totalPrice' => $total]);
    //             return $shipment;
    //         });

    //         $this->loadCommonRelations($shipment);
    //         $shipment->load('items');

    //         return $this->respondWithResource($shipment, "تم إنشاء الشحنة وزيادة الكميات وتحديث البيانات بنجاح.");

    //     } catch (\Exception $e) {
    //         Log::error('فشل إنشاء الشحنة', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //             'request' => $request->all()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'حدث خطأ أثناء إنشاء الشحنة: ' . $e->getMessage(),
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Shipment = Shipment::with('items')->find($id);

        if (!$Shipment) {
            return response()->json([
                'message' => "Shipment not found."
            ], 404);
            }

    return $this->respondWithResource($Shipment, "Shipment retrieved for editing.");
        }


        public function update(ShipmentRequest $request, string $id)
{
    $this->authorize('manage_system');

    $shipment = Shipment::with('items')->findOrFail($id);
    $oldData = $shipment->toArray();

    $updateData = $request->only([
        'company_id', 'supplier_id', 'service_id', 'description', 'status'
    ]);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $shipment->status)
    );

    $hasChanges = false;

    foreach ($updateData as $key => $value) {
        if ($shipment->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    $itemsChanged = !$this->itemsEqual($shipment, $request->items ?? []);

    if (!$hasChanges && !$itemsChanged) {
        $this->loadCommonRelations($shipment);
        $shipment->load('items');
        return $this->respondWithResource($shipment, "لا يوجد تغييرات فعلية.");
    }

    DB::transaction(function () use ($shipment, $updateData, $request) {

        $shipment->update($updateData);

        if (!$this->itemsEqual($shipment, $request->items ?? [])) {
            ShipmentItem::where('shipment_id', $shipment->id)->delete();

            $total = 0;
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unitPrice'];
                $total += $itemTotal;

                $rentalStartHijri = $item['rentalStart'] ? $this->getHijriDate($item['rentalStart']) : null;
                $rentalEndHijri   = $item['rentalEnd']   ? $this->getHijriDate($item['rentalEnd'])   : null;
                $tripDateHijri    = $item['DateTimeTrip']? $this->getHijriDate($item['DateTimeTrip']) : null;

        ShipmentItem::create([
                'shipment_id'         => $shipment->id,
                    'item_id'             => $item['item_id'],
                    'item_type'           => $this->getMorphClass($item['item_type']),
                    'quantity'            => $item['quantity'],
                    'unitPrice'           => $item['unitPrice'],
                    'totalPrice'          => $itemTotal,
                    'rentalStart'         => $item['rentalStart'] ?? null,
                    'rentalEnd'           => $item['rentalEnd'] ?? null,
                    'rentalStartHijri'    => $rentalStartHijri,
                    'rentalEndHijri'      => $rentalEndHijri,
                    'DateTimeTrip'        => $item['DateTimeTrip'] ?? null,
                    'DateTimeTripHijri'   => $tripDateHijri,
                    'seatNum'             => $item['seatNum'] ?? null,
                    'class'               => $item['class'] ?? null,
                    'roomType' => $item['roomType'] ?? null,
                    'creationDate'        => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    'creationDateHijri'   => $this->getHijriDate(),
                ]);
            }

            $shipment->update(['totalPrice' => $total]);
        }
    });

    $shipment->refresh();
    $changedData = $shipment->getChangedData($oldData, $shipment->toArray());

    $shipment->changed_data = $changedData;
    $shipment->save();

    $this->loadCommonRelations($shipment);
    $shipment->load('items');

    return $this->respondWithResource($shipment, "تم تحديث الشحنة بنجاح.");
}



protected function itemsEqual(Shipment $shipment, array $newItems): bool
{
    $oldItems = $shipment->items->map(function ($item) {
        return [
            'item_id'   => (int) $item->item_id,
            'item_type' => $item->item_type,
            'quantity'  => (float) $item->quantity,
            'unitPrice' => (float) $item->unitPrice,
            'rentalStart'          => $item->rentalStart,
            'rentalEnd'            => $item->rentalEnd,
            'rentalStartHijri'     => $item->rentalStartHijri,
            'rentalEndHijri'       => $item->rentalEndHijri,
            'DateTimeTripHijri'    => $item->DateTimeTripHijri,
            'DateTimeTrip'         => $item->DateTimeTrip,
            'seatNum'              => $item->seatNum,
            'class'                => $item->class,
            'roomType'                => $item->roomType,
        ];
    })->sortBy(fn($item) => $item['item_id'] . $item['item_type'])->values()->toArray();
    $newItemsNormalized = collect($newItems)->map(function ($item) {
        return [
        'item_id'   => (int) $item['item_id'],
        'item_type' => $this->getMorphClass($item['item_type']),
        'quantity'  => (float) $item['quantity'],
        'unitPrice' => (float) $item['unitPrice'],
        'rentalStart'          => $item['rentalStart'] ?? null,
        'rentalEnd'            => $item['rentalEnd'] ?? null,
        'rentalStartHijri'     => $item['rentalStartHijri'] ?? null,
        'rentalEndHijri'       => $item['rentalEndHijri'] ?? null,
        'DateTimeTripHijri'    => $item['DateTimeTripHijri'] ?? null,
        'DateTimeTrip'         => $item['DateTimeTrip'] ?? null,
        'seatNum'              => $item['seatNum'] ?? null,
        'class'                => $item['class'] ?? null,
        'roomType'                => $item['roomType'] ?? null,
        ];
    })->sortBy(fn($item) => $item['item_id'] . $item['item_type'])->values()->toArray();

    return $oldItems === $newItemsNormalized;
}

    public function active(string $id)
    {
         $this->authorize('manage_system');
        $Shipment = Shipment::findOrFail($id);

        return $this->changeStatusSimple($Shipment, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_system');
        $Shipment = Shipment::findOrFail($id);

        return $this->changeStatusSimple($Shipment, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return ShipmentResource::class;
    }
}
