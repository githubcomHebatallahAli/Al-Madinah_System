<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\BranchResource;
use App\Http\Resources\Admin\ShowAllBranchResource;

class BranchController extends Controller
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
        $Branches = Branch::orderBy('created_at', 'desc')
        ->get();
        $this->loadRelationsForCollection($Branches);

        return response()->json([
            'data' =>  BranchResource::collection($Branches),
            'message' => "Show All branches."
        ]);
    }
    // ==================
      public function showAllWithPaginate(Request $request)
    {
         $this->authorize('manage_system');

        $searchTerm = $request->input('search', '');
       $Branches = Branch::where('name', 'like', '%' . $searchTerm . '%')
       ->orderBy('created_at', 'desc')
        ->paginate(10);
        $this->loadRelationsForCollection($Branches);

        return response()->json([
            'data' =>  ShowAllBranchResource::collection($Branches),
              'pagination' => [
                        'total' => $Branches->total(),
                        'count' => $Branches->count(),
                        'per_page' => $Branches->perPage(),
                        'current_page' => $Branches->currentPage(),
                        'total_pages' => $Branches->lastPage(),
                        'next_page_url' => $Branches->nextPageUrl(),
                        'prev_page_url' => $Branches->previousPageUrl(),
                    ],
            'message' => "Show All Branches."
        ]);
    }
        public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_system');

        $searchTerm = $request->input('search', '');
       $Branches = Branch::where('name', 'like', '%' . $searchTerm . '%')
       ->orderBy('created_at', 'desc')
        ->get();
        $this->loadRelationsForCollection($Branches);

        return response()->json([
            'data' =>  ShowAllBranchResource::collection($Branches),
            'message' => "Show All Branches."
        ]);
    }

    public function create(BranchRequest $request)
    {
        $this->authorize('manage_users');
     $data = array_merge($request->only([
            'city_id', 'name','address'
        ]), $this->prepareCreationMetaData());

        $Branch = Branch::create($data);

         return $this->respondWithResource($Branch, "Branch created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Branch = Branch::with(['titles','offices','trips','stores'])
        ->find($id);
        if (!$Branch) {
            return response()->json([
                'message' => "Branch not found."
            ], 404);
            }

    return $this->respondWithResource($Branch, "Branch retrieved for editing.");
        }

public function update(BranchRequest $request, string $id)
{
    $this->authorize('manage_users');
    $Branch = Branch::findOrFail($id);
    $oldData = $Branch->toArray();

    $updateData = $request->only(['name','address','city_id','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Branch->status)
    );


    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Branch->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Branch);
        return $this->respondWithResource($Branch, "لا يوجد تغييرات فعلية");
    }

    $Branch->update($updateData);
    $changedData = $Branch->getChangedData($oldData, $Branch->fresh()->toArray());
    $Branch->changed_data = $changedData;
    $Branch->save();

    $this->loadCommonRelations($Branch);
    return $this->respondWithResource($Branch, "تم تحديث الفرع بنجاح");
}


        public function active(string $id)
    {
         $this->authorize('manage_users');
        $Branch = Branch::findOrFail($id);

        return $this->changeStatusSimple($Branch, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_users');
        $Branch = Branch::findOrFail($id);

        return $this->changeStatusSimple($Branch, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return BranchResource::class;
    }
}
