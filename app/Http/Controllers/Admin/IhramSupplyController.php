<?php

namespace App\Http\Controllers\Admin;

use App\Models\IhramSupply;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\IhramSupplyRequest;
use App\Http\Resources\Admin\IhramSupplyResource;
use App\Http\Resources\Admin\ShowAllIhramSupplyResource;

class IhramSupplyController extends Controller
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

    $query = IhramSupply::query();


    if ($request->filled('ihram_item_id')) {
        $query->where('ihram_item_id', $request->ihram_item_id);
    }

    if ($request->filled('company_id')) {
        $query->where('company_id', $request->company_id);
    }

    if ($request->filled('store_id')) {
        $query->where('store_id', $request->store_id);
    }

       if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $query->orderBy('created_at', 'desc');

    $ihramSupplies = $query->paginate(10);

    return response()->json([
        'data' => ShowAllIhramSupplyResource::collection($ihramSupplies),
        'pagination' => [
            'total' => $ihramSupplies->total(),
            'count' => $ihramSupplies->count(),
            'per_page' => $ihramSupplies->perPage(),
            'current_page' => $ihramSupplies->currentPage(),
            'total_pages' => $ihramSupplies->lastPage(),
            'next_page_url' => $ihramSupplies->nextPageUrl(),
            'prev_page_url' => $ihramSupplies->previousPageUrl(),
        ],
        'message' => "Show All Ihram Supplies."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = IhramSupply::query();

    if ($request->filled('ihram_item_id')) {
        $query->where('ihram_item_id', $request->ihram_item_id);
    }

    if ($request->filled('company_id')) {
        $query->where('company_id', $request->company_id);
    }

    if ($request->filled('store_id')) {
        $query->where('store_id', $request->store_id);
    }

     if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $query->orderBy('created_at', 'desc');

    $ihramSupplies = $query->get();

    return response()->json([
        'data' => ShowAllIhramSupplyResource::collection($ihramSupplies),
        'message' => "Show All Ihram Supplies."
    ]);
}



    public function create(IhramSupplyRequest $request)
    {
        $this->authorize('create',IhramSupply::class);
       $data = array_merge($request->only([
            'ihram_item_id','company_id','store_id',
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

    $updateData = $request->only(['status','ihram_item_id','company_id','store_id',
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
