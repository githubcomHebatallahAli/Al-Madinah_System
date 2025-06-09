<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GroupRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\GroupResource;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\ShowAllGroupResource;


class GroupController extends Controller
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

    $query = Group::where('groupNum', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->campaign_id) {
        $query->where('campaign_id', $request->campaign_id);
    }

    $Groups = $query->paginate(10);

    return response()->json([
        'data' => ShowAllGroupResource::collection($Groups),
        'pagination' => [
            'total' => $Groups->total(),
            'count' => $Groups->count(),
            'per_page' => $Groups->perPage(),
            'current_page' => $Groups->currentPage(),
            'total_pages' => $Groups->lastPage(),
            'next_page_url' => $Groups->nextPageUrl(),
            'prev_page_url' => $Groups->previousPageUrl(),
        ],
        'message' => "Show All Groups."
    ]);
}


public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $searchTerm = $request->input('search', '');

    $query = Group::where('groupNum', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->campaign_id) {
        $query->where('campaign_id', $request->campaign_id);
    }

    $Groups = $query->get();

    return response()->json([
        'data' => ShowAllGroupResource::collection($Groups),
        'message' => "Show All Groups."
    ]);
}

     public function create(GroupRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
            'campaign_id', 'groupNum' ,'numBus'

        ]), $this->prepareCreationMetaData());

        $Group = Group::create($data);

         return $this->respondWithResource($Group, "Group created successfully.");
    }


public function update(GroupRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Group = Group::findOrFail($id);
    $oldData = $Group->toArray();

    $updateData = $request->only(['campaign_id', 'groupNum' ,'numBus','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Group->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Group->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Group);
        return $this->respondWithResource($Group, "لا يوجد تغييرات فعلية");
    }

    $Group->update($updateData);

    $changedData = $Group->getChangedData($oldData, $Group->fresh()->toArray());
    $Group->changed_data = $changedData;
    $Group->save();



    $this->loadCommonRelations($Group);
    return $this->respondWithResource($Group, "تم تحديث الجروب بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $Group = Group::find($id);

    if (!$Group) {
        return response()->json(['message' => "Group not found."], 404);
    }

    return $this->respondWithResource($Group, "Group retrieved for editing.");
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $Group = Group::findOrFail($id);

        return $this->changeStatusSimple($Group, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $Group = Group::findOrFail($id);

        return $this->changeStatusSimple($Group, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return GroupResource::class;
    }

}
