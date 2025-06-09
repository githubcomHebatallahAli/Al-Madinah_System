<?php

namespace App\Http\Controllers\Admin;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\StoreResource;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\ShowAllStoreResource;

class StoreController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;



 public function showAllWithPaginate(Request $request)
{
    $this->authorize('manage_system');

    $searchTerm = $request->input('search', '');

    $query = Store::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->branch_id) {
        $query->where('branch_id', $request->branch_id);
    }

    $Stores = $query->paginate(10);

    return response()->json([
        'data' => ShowAllStoreResource::collection($Stores),
        'pagination' => [
            'total' => $Stores->total(),
            'count' => $Stores->count(),
            'per_page' => $Stores->perPage(),
            'current_page' => $Stores->currentPage(),
            'total_pages' => $Stores->lastPage(),
            'next_page_url' => $Stores->nextPageUrl(),
            'prev_page_url' => $Stores->previousPageUrl(),
        ],
        'message' => "Show All Stores."
    ]);
}


public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $searchTerm = $request->input('search', '');

    $query = Store::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->branch_id) {
        $query->where('branch_id', $request->branch_id);
    }

    $Stores = $query->get();

    return response()->json([
        'data' => ShowAllStoreResource::collection($Stores),
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
    $Store = Store::findOrFail($id);
    $oldData = $Store->toArray();

    $updateData = $request->only(['name','address','branch_id','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Store->status)
    );


    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Store->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Store);
        return $this->respondWithResource($Store, "لا يوجد تغييرات فعلية");
    }

    $Store->update($updateData);
    $changedData = $Store->getChangedData($oldData, $Store->fresh()->toArray());
    $Store->changed_data = $changedData;
    $Store->save();

    $this->loadCommonRelations($Store);
    return $this->respondWithResource($Store, "تم تحديث المخزن بنجاح");
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
