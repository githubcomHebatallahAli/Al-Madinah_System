<?php

namespace App\Http\Controllers\Admin;

use App\Models\Supply;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupplyRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\SupplyResource;


class SupplyController extends Controller
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
        $Supplies = Supply::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($Supplies);

        return response()->json([
            'data' =>  SupplyResource::collection($Supplies),
            'message' => "Show All Supplies."
        ]);
    }


    public function create(SupplyRequest $request)
    {
        $this->authorize('manage_system');
       $data = array_merge($request->only([
            'service_id', 'name', 'address','communication','description'
        ]), $this->prepareCreationMetaData());

        $Supply = Supply::create($data);

         return $this->respondWithResource($Supply, "Supply created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Supply = Supply::find($id);

        if (!$Supply) {
            return response()->json([
                'message' => "Supply not found."
            ], 404);
            }

    return $this->respondWithResource($Supply, "Supply retrieved for editing.");
        }

public function update(SupplyRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Supply = Supply::findOrFail($id);
    $oldData = $Supply->toArray();

    $updateData = $request->only(['name','address','service_id','status','communication','description']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Supply->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Supply->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Supply);
        return $this->respondWithResource($Supply, "لا يوجد تغييرات فعلية");
    }

    $Supply->update($updateData);
    $changedData = $Supply->getChangedData($oldData, $Supply->fresh()->toArray());
    $Supply->changed_data = $changedData;
    $Supply->save();

    $this->loadCommonRelations($Supply);
    return $this->respondWithResource($Supply, "تم تحديث شركه التوريد بنجاح");
}

    public function active(string $id)
    {
         $this->authorize('manage_system');
        $Supply = Supply::findOrFail($id);

        return $this->changeStatusSimple($Supply, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_system');
        $Supply = Supply::findOrFail($id);

        return $this->changeStatusSimple($Supply, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return SupplyResource::class;
    }

}
