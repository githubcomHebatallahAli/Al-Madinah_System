<?php

namespace App\Http\Controllers\Admin;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Traits\HijriDateTrait;
use App\Traits\HasMorphMapTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\ShipmentRequest;
use App\Http\Resources\Admin\ShipmentResource;

class ShipmentController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;
    use HasMorphMapTrait;

        public function showAll()
    {
        $this->authorize('manage_system');
        $Shipments = Shipment::with('items')->orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($Shipments);

        return response()->json([
            'data' =>  ShipmentResource::collection($Shipments),
            'message' => "Show All Shipments."
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
                $itemTotal = $item['quantity'] * $item['unitPrice'];
                $total += $itemTotal;

                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'item_id'     => $item['item_id'],
                    'item_type'   => $this->getMorphClass($item['item_type']),
                    'quantity'    => $item['quantity'],
                    'unitPrice'   => $item['unitPrice'],
                    'totalPrice'  => $itemTotal,
                ]);
            }

            $shipment->update(['totalPrice' => $total]);

            return $shipment;
        });

        $this->loadCommonRelations($shipment);
        $shipment->load('items');

        return $this->respondWithResource($shipment, "Shipment created successfully.");

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الشحنة.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



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


public function update(ShipmentRequest $request, Shipment $shipment)
{
    $this->authorize('manage_system');

    try {
        $updatedShipment = DB::transaction(function () use ($request, $shipment) {
            // استدعاء prepareUpdateMeta مع تمرير الريكوست
            $data = array_merge(
                $request->only(['company_id', 'supplier_id', 'service_id', 'description']),
                $this->prepareUpdateMeta($request, $shipment->status)
            );

            $shipment->update($data);

            // حذف العناصر القديمة
            $shipment->items()->delete();

            $total = 0;

            // إعادة إضافة العناصر الجديدة
            foreach ($request->items as $item) {
                $itemTotal = $item['quantity'] * $item['unitPrice'];
                $total += $itemTotal;

                $shipment->items()->create([
                    'item_id'    => $item['item_id'],
                    'item_type'  => $this->getMorphClass($item['item_type']),
                    'quantity'   => $item['quantity'],
                    'unitPrice'  => $item['unitPrice'],
                    'totalPrice' => $itemTotal,
                ]);
            }

            // تحديث إجمالي السعر
            $shipment->update(['totalPrice' => $total]);

            return $shipment;
        });

        // تحميل العلاقات المشتركة (مُنشئ، مُحدّث، وعناصر)
        $this->loadCommonRelations($updatedShipment);
        $updatedShipment->load('items');

        return $this->respondWithResource($updatedShipment, "Shipment updated successfully.");

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث الشحنة.',
            'error' => $e->getMessage(),
        ], 500);
    }
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
