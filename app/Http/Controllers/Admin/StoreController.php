<?php

namespace App\Http\Controllers\Admin;

use App\Models\Store;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\StoreResource;
use App\Traits\HandlesControllerCrudsTrait;

class StoreController extends Controller
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
        $Stores = Store::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($Stores);

        return response()->json([
            'data' =>  StoreResource::collection($Stores),
            'message' => "Show All Stores."
        ]);
    }


    public function create(StoreRequest $request)
    {
        $this->authorize('manage_users');
       $data = array_merge($request->only([
            'branch_id', 'name', 'address'
        ]), $this->prepareCreationMetaData());

        $Store = Store::create($data);

         return $this->respondWithResource($Store, "Store created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Store = Store::with(['workers'])
        ->find($id);
        if (!$Store) {
            return response()->json([
                'message' => "Store not found."
            ], 404);
            }

    return $this->respondWithResource($Store, "Store retrieved for editing.");
        }

        public function update(StoreRequest $request, string $id)
        {
         $this->authorize('manage_users');
            $Store = Store::find($id);

    if (!$Store) {
        return response()->json(['message' => "Store not found."], 404);
    }

    $oldData = $Store->toArray();
    $fieldsToCheck = ['branch_id', 'name', 'status','address'];
    $hasChanges = false;

    foreach ($fieldsToCheck as $field) {
        if ($request->has($field) && $Store->$field != $request->$field) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Store);
        return $this->respondWithResource($Store, "No actual changes detected.");
    }

    $updateData = array_merge(
        $request->only(['branch_id','name','address']),
        $this->prepareUpdateMeta($request)
    );

    $this->applyChangesAndSave($Store, $updateData, $oldData);

    return $this->respondWithResource($Store, "Store updated successfully.");
    }

    public function active(string $id)
    {
         $this->authorize('manage_users');
        $Store = Store::findOrFail($id);

        return $this->changeStatusSimple($Store, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_users');
        $Store = Store::findOrFail($id);

        return $this->changeStatusSimple($Store, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return StoreResource::class;
    }
}
