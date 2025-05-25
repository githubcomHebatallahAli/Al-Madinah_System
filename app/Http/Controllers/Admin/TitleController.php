<?php

namespace App\Http\Controllers\Admin;

use App\Models\Title;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TitleRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\TitleResource;
use App\Traits\HandlesControllerCrudsTrait;

class TitleController extends Controller
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
        $titles = Title::orderBy('created_at', 'desc')->get();

         $this->loadRelationsForCollection($titles);

        return response()->json([
            'data' => TitleResource::collection($titles),
            'message' => "All titles retrieved successfully."
        ]);
    }

     public function create(TitleRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
            'branch_id', 'name'
        ]), $this->prepareCreationMetaData());

        $title = Title::create($data);

         return $this->respondWithResource($title, "Title created successfully.");
    }


    public function update(TitleRequest $request, string $id)
{
    $this->authorize('manage_system');
    $title = Title::find($id);

    if (!$title) {
        return response()->json(['message' => "Title not found."], 404);
    }

    $oldData = $title->toArray();
    $fieldsToCheck = ['branch_id', 'name', 'status'];
    $hasChanges = false;

    foreach ($fieldsToCheck as $field) {
        if ($request->has($field) && $title->$field != $request->$field) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($title);
        return $this->respondWithResource($title, "No actual changes detected.");
    }

    $updateData = array_merge(
        $request->only(['branch_id', 'name']),
        $this->prepareUpdateMeta($request)
    );

    $this->applyChangesAndSave($title, $updateData, $oldData);

    return $this->respondWithResource($title, "Title updated successfully.");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $title = Title::with(['workers'])->find($id);

    if (!$title) {
        return response()->json(['message' => "Title not found."], 404);
    }

    return $this->respondWithResource($title, "Title retrieved for editing.");
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $title = Title::findOrFail($id);

        return $this->changeStatusSimple($title, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $title = Title::findOrFail($id);

        return $this->changeStatusSimple($title, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return TitleResource::class;
    }
}
