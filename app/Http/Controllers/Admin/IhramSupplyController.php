<?php

namespace App\Http\Controllers\Admin;

use App\Models\IhramSupply;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\IhramSupplyRequest;
use App\Http\Resources\Admin\IhramSupplyResource;

class IhramSupplyController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

        public function showAll()
    {
        $this->authorize('showAll',IhramSupply::class);
        $IhramSupplies = IhramSupply::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($IhramSupplies);

        return response()->json([
            'data' =>  IhramSupplyResource::collection($IhramSupplies),
            'message' => "Show All IhramSupplyies."
        ]);
    }


    public function create(IhramSupplyRequest $request)
    {
        $this->authorize('create',IhramSupply::class);
       $data = array_merge($request->only([
            'service_id', 'name','store_id','size',
            'description','quantity','sellingPrice','purchesPrice'
        ]), $this->prepareCreationMetaData());

        $IhramSupply = IhramSupply::create($data);

         return $this->respondWithResource($IhramSupply, "IhramSupply created successfully.");
        }

        public function edit(string $id)
        {

        $IhramSupply = IhramSupply::find($id);

        if (!$IhramSupply) {
            return response()->json([
                'message' => "IhramSupply not found."
            ], 404);
            }
             $this->authorize('edit',$IhramSupply);

    return $this->respondWithResource($IhramSupply, "IhramSupply retrieved for editing.");
        }

public function update(IhramSupplyRequest $request, string $id)
{
    $IhramSupply = IhramSupply::findOrFail($id);
    $this->authorize('update',$IhramSupply);
    $oldData = $IhramSupply->toArray();

    $updateData = $request->only(['status','service_id','name','store_id','size',
            'description','quantity','sellingPrice','purchesPrice'
            ]);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $IhramSupply->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($IhramSupply->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($IhramSupply);
        return $this->respondWithResource($IhramSupply, "لا يوجد تغييرات فعلية");
    }

    $IhramSupply->update($updateData);
    $changedData = $IhramSupply->getChangedData($oldData, $IhramSupply->fresh()->toArray());
    $IhramSupply->changed_data = $changedData;
    $IhramSupply->save();

    $this->loadCommonRelations($IhramSupply);
    return $this->respondWithResource($IhramSupply, "تم تحديث مستلزمات الاحرام بنجاح");
}

    public function active(string $id)
    {
        $IhramSupply = IhramSupply::findOrFail($id);
        $this->authorize('active',$IhramSupply);

        return $this->changeStatusSimple($IhramSupply, 'active');
    }

    public function notActive(string $id)
    {
        $IhramSupply = IhramSupply::findOrFail($id);
        $this->authorize('notActive',$IhramSupply);

        return $this->changeStatusSimple($IhramSupply, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return IhramSupplyResource::class;
    }
}
