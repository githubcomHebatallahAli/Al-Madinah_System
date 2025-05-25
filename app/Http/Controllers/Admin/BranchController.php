<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\Admin\BranchRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\BranchResource;

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
    $Branch = Branch::find($id);

    if (!$Branch) {
        return response()->json(['message' => "Branch not found."], 404);
    }

    $oldData = $Branch->toArray();
    $fieldsToCheck = ['city_id', 'name', 'status','address'];
    $hasChanges = false;

    foreach ($fieldsToCheck as $field) {
        if ($request->has($field) && $Branch->$field != $request->$field) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Branch);
        return $this->respondWithResource($Branch, "No actual changes detected.");
    }

    $updateData = array_merge(
        $request->only(['city_id', 'name','address']),
        $this->prepareUpdateMeta($request)
    );

    $this->applyChangesAndSave($Branch, $updateData, $oldData);
    return $this->respondWithResource($Branch, "Branch updated successfully.");
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
