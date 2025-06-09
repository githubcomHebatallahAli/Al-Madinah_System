<?php

namespace App\Http\Controllers\Admin;

use App\Models\IhramItem;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\IhramItemRequest;
use App\Http\Resources\Admin\IhramItemResource;

class IhramItemController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

        public function showAll()
    {
        $IhramSupplies = IhramItem::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($IhramSupplies);

        return response()->json([
            'data' =>  IhramItemResource::collection($IhramSupplies),
            'message' => "Show All Ihram Items."
        ]);
    }


    public function create(IhramItemRequest $request)
    {

       $data = array_merge($request->only([
            'service_id','name','size',
            'description'
        ]), $this->prepareCreationMetaData());

        $IhramItem = IhramItem::create($data);

         return $this->respondWithResource($IhramItem, "IhramItem created successfully.");
        }

        public function edit(string $id)
        {

        $IhramItem = IhramItem::find($id);

        if (!$IhramItem) {
            return response()->json([
                'message' => "IhramItem not found."
            ], 404);
            }

    return $this->respondWithResource($IhramItem, "IhramItem retrieved for editing.");
        }

public function update(IhramItemRequest $request, string $id)
{
    $IhramItem = IhramItem::findOrFail($id);
    $oldData = $IhramItem->toArray();

    $updateData = $request->only(['status','service_id','name','size',
            'description'
            ]);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $IhramItem->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($IhramItem->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($IhramItem);
        return $this->respondWithResource($IhramItem, "لا يوجد تغييرات فعلية");
    }

    $IhramItem->update($updateData);
    $changedData = $IhramItem->getChangedData($oldData, $IhramItem->fresh()->toArray());
    $IhramItem->changed_data = $changedData;
    $IhramItem->save();

    $this->loadCommonRelations($IhramItem);
    return $this->respondWithResource($IhramItem, "تم تحديث مستلزمات الاحرام بنجاح");
}

    public function active(string $id)
    {
        $IhramItem = IhramItem::findOrFail($id);
        return $this->changeStatusSimple($IhramItem, 'active');
    }

    public function notActive(string $id)
    {
        $IhramItem = IhramItem::findOrFail($id);
        return $this->changeStatusSimple($IhramItem, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return IhramItemResource::class;
    }
}
