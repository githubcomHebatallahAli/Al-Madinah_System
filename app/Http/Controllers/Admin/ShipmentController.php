<?php

namespace App\Http\Controllers\Admin;

use App\Models\Shipment;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
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

        public function showAll()
    {
        $this->authorize('manage_system');
        $Shipments = Shipment::orderBy('created_at', 'desc')
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
       $data = array_merge($request->only([
            'supplier_id', 'description'
        ]), $this->prepareCreationMetaData());

        $Shipment = Shipment::create($data);

         return $this->respondWithResource($Shipment, "Shipment created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Shipment = Shipment::
        find($id);
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
    $Shipment = Shipment::findOrFail($id);
    $oldData = $Shipment->toArray();

    $updateData = $request->only(['description','supplier_id','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Shipment->status)
    );


    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Shipment->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Shipment);
        return $this->respondWithResource($Shipment, "لا يوجد تغييرات فعلية");
    }

    $Shipment->update($updateData);
    $changedData = $Shipment->getChangedData($oldData, $Shipment->fresh()->toArray());
    $Shipment->changed_data = $changedData;
    $Shipment->save();

    $this->loadCommonRelations($Shipment);
    return $this->respondWithResource($Shipment, "تم تحديث الشحنه بنجاح");
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
