<?php

namespace App\Http\Controllers\Admin;

use App\Models\Office;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OfficeRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\OfficeResource;


class OfficeController extends Controller
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
        $Offices = Office::orderBy('created_at', 'desc')->get();

         $this->loadRelationsForCollection($Offices);

        return response()->json([
            'data' => OfficeResource::collection($Offices),
            'message' => "All Offices retrieved successfully."
        ]);
    }

     public function create(OfficeRequest $request)
    {
        $this->authorize('manage_users');
        $data = array_merge($request->only([
            'branch_id','name','address','phoNum1','phoNum2'

        ]), $this->prepareCreationMetaData());

        $Office = Office::create($data);

         return $this->respondWithResource($Office, "Office created successfully.");
    }


public function update(OfficeRequest $request, string $id)
{
    $this->authorize('manage_users');
    $Office = Office::findOrFail($id);
    $oldData = $Office->toArray();

    $updateData = $request->only(['name','address','branch_id','phoNum1','phoNum2','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Office->status)
    );


    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Office->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Office);
        return $this->respondWithResource($Office, "لا يوجد تغييرات فعلية");
    }

    $Office->update($updateData);
    $changedData = $Office->getChangedData($oldData, $Office->fresh()->toArray());
    $Office->changed_data = $changedData;
    $Office->save();

    $this->loadCommonRelations($Office);
    return $this->respondWithResource($Office, "تم تحديث المكتب بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $Office = Office::find($id);

    if (!$Office) {
        return response()->json(['message' => "Office not found."], 404);
    }

    return $this->respondWithResource($Office, "Office retrieved for editing.");
}

        public function active(string $id)
    {
         $this->authorize('manage_users');
        $Office = Office::findOrFail($id);

        return $this->changeStatusSimple($Office, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_users');
        $Office = Office::findOrFail($id);

        return $this->changeStatusSimple($Office, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return OfficeResource::class;
    }
}
