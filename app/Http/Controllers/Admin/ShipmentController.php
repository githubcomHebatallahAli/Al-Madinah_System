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
                    'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    'creationDateHijri' => $this->getHijriDate(),
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

    // مقارنة العناصر
    $itemsChanged = !$this->itemsEqual($shipment, $request->items ?? []);

    if (!$hasChanges && !$itemsChanged) {
        $this->loadCommonRelations($shipment);
        $shipment->load('items');
        return $this->respondWithResource($shipment, "لا يوجد تغييرات فعلية.");
    }

    DB::transaction(function () use ($shipment, $updateData, $request) {

        // تحديث الشحنة
        $shipment->update($updateData);

        // حذف العناصر القديمة لو اتغيرت
        if (!$this->itemsEqual($shipment, $request->items ?? [])) {
            ShipmentItem::where('shipment_id', $shipment->id)->delete();

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
                    'creationDate' => now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
                    'creationDateHijri' => $this->getHijriDate(),
                ]);
            }

            $shipment->update(['totalPrice' => $total]);
        }
    });

    $shipment->refresh(); // تحديث الريليشن بعد الـ transaction

    // حساب changed_data بعد التحديث
    $changedData = $shipment->getChangedData($oldData, $shipment->toArray());

    $shipment->changed_data = $changedData;
    $shipment->save();

    $this->loadCommonRelations($shipment);
    $shipment->load('items');

    return $this->respondWithResource($shipment, "تم تحديث الشحنة بنجاح.");
}



protected function itemsEqual(Shipment $shipment, array $newItems): bool
{
    // العناصر القديمة بعد ترتيبها
    $oldItems = $shipment->items->map(function ($item) {
        return [
            'item_id'   => (int) $item->item_id,
            'item_type' => $item->item_type,
            'quantity'  => (float) $item->quantity,
            'unitPrice' => (float) $item->unitPrice,
        ];
    })->sortBy(fn($item) => $item['item_id'] . $item['item_type'])->values()->toArray();

    // العناصر الجديدة بعد تحويل نوع العنصر المورف وتصفيتها
    $newItemsNormalized = collect($newItems)->map(function ($item) {
        return [
            'item_id'   => (int) $item['item_id'],
            'item_type' => $this->getMorphClass($item['item_type']),
            'quantity'  => (float) $item['quantity'],
            'unitPrice' => (float) $item['unitPrice'],
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
