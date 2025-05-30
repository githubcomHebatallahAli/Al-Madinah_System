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


// public function create(ShipmentRequest $request)
// {
//     $this->authorize('manage_system');

//         DB::beginTransaction();

//         try {
//             $shipment = Shipment::create([
//                 'supplier_id'        => $request->supplier_id,
//                 'service_id'         => $request->service_id,
//                 'company_id'         => $request->company_id,
//                 'status'             => $request->status ?? 'active',
//                 'creationDate'       => $request->creationDate,
//                 'creationDateHijri'  => $request->creationDateHijri,
//                 'name'               => $request->name,
//                 'description'        => $request->description,
//                 'totalPrice'         => 0,
//             ]);

//             $total = 0;

//             foreach ($request->items as $item) {
//                 $itemTotal = $item['quantity'] * $item['unitPrice'];
//                 $total += $itemTotal;

//                 ShipmentItem::create([
//                     'shipment_id' => $shipment->id,
//                     'item_id'     => $item['item_id'],
//                     'item_type'   => $this->getMorphClass($item['item_type']), // من التريت
//                     'quantity'    => $item['quantity'],
//                     'unitPrice'   => $item['unitPrice'],
//                     'totalPrice'  => $itemTotal,
//                 ]);
//             }

//             $shipment->update(['totalPrice' => $total]);

//             DB::commit();

//             return response()->json([
//                 'success' => true,
//                 'message' => 'تم إنشاء الشحنة بنجاح.',
//                 'data'    => $shipment->load('items')
//             ]);
//         } catch (\Exception $e) {
//             DB::rollBack();

//             return response()->json([
//                 'success' => false,
//                 'message' => 'حدث خطأ أثناء إنشاء الشحنة.',
//                 'error'   => $e->getMessage(),
//             ], 500);
//         }
//     }


public function create(ShipmentRequest $request)
{
    $this->authorize('manage_system');

    DB::beginTransaction();

    try {
        $data = $request->only([
            'supplier_id', 'service_id', 'company_id',
             'description'
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $data['totalPrice'] = 0;

        $this->setAddedBy($data); // من HandleAddedByTrait

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

        DB::commit();

        $this->loadCreatorRelations($shipment); // تحميل علاقات المُنشئ
        $shipment->load('items');

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الشحنة بنجاح.',
            'data'    => $shipment
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الشحنة.',
            'error'   => $e->getMessage(),
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

    DB::beginTransaction();

    try {
        $data = $request->only([
            'supplier_id', 'service_id', 'company_id', 'status','description'
        ]);

        $this->setUpdatedBy($data);

        $shipment->update($data);

        // حذف كل العناصر القديمة
        $shipment->items()->delete();

        // إعادة إضافة العناصر الجديدة
        $total = 0;

        foreach ($request->items as $item) {
            $itemTotal = $item['quantity'] * $item['unitPrice'];
            $total += $itemTotal;

            $shipment->items()->create([
                'item_id'     => $item['item_id'],
                'item_type'   => $this->getMorphClass($item['item_type']),
                'quantity'    => $item['quantity'],
                'unitPrice'   => $item['unitPrice'],
                'totalPrice'  => $itemTotal,
            ]);
        }

        // تحديث إجمالي السعر بعد تعديل العناصر
        $shipment->update(['totalPrice' => $total]);

        DB::commit();

        // تحميل العلاقات الخاصة بمن أنشأ وعدّل
        $this->loadCreatorRelations($shipment);
        $this->loadUpdaterRelations($shipment);

        // تحميل العناصر المرتبطة
        $shipment->load('items');

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الشحنة بنجاح.',
            'data'    => $shipment,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء تحديث الشحنة.',
            'error'   => $e->getMessage(),
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
