<?php

namespace App\Http\Controllers\Admin;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\CampaignRequest;
use App\Http\Resources\Admin\CampaignResource;
use App\Http\Resources\Admin\ShowAllCampaignResource;

class CampaignController extends Controller
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

    // $searchTerm = $request->input('search', '');

    // $query = Campaign::where('name', 'like', '%' . $searchTerm . '%');
    $query = Campaign::query();

        if ($request->filled('search')) {
    $query->where('name', 'like', '%' . $request->search . '%');
}
    if ($request->filled('office_id')) {
        $query->where('office_id', $request->office_id);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $Campaigns = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllCampaignResource::collection($Campaigns),
        'pagination' => [
            'total' => $Campaigns->total(),
            'count' => $Campaigns->count(),
            'per_page' => $Campaigns->perPage(),
            'current_page' => $Campaigns->currentPage(),
            'total_pages' => $Campaigns->lastPage(),
            'next_page_url' => $Campaigns->nextPageUrl(),
            'prev_page_url' => $Campaigns->previousPageUrl(),
        ],
        'message' => "Show All Campaigns."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    // $searchTerm = $request->input('search', '');

    // $query = Campaign::where('name', 'like', '%' . $searchTerm . '%');
    $query = Campaign::query();

        if ($request->filled('search')) {
    $query->where('name', 'like', '%' . $request->search . '%');
}

    if ($request->filled('office_id')) {
        $query->where('office_id', $request->office_id);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $Campaigns = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllCampaignResource::collection($Campaigns),
        'message' => "Show All Campaigns."
    ]);
}



    public function create(CampaignRequest $request)
    {
        $this->authorize('manage_system');
     $data = array_merge($request->only([
            'office_id', 'name'
        ]), $this->prepareCreationMetaData());

        $Campaign = Campaign::create($data);

         return $this->respondWithResource($Campaign, "Campaign created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Campaign = Campaign::find($id);

        if (!$Campaign) {
            return response()->json([
                'message' => "Campaign not found."
            ], 404);
            }

    return $this->respondWithResource($Campaign, "Campaign retrieved for editing.");
        }

public function update(CampaignRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Campaign = Campaign::findOrFail($id);
    $oldData = $Campaign->toArray();

    $updateData = $request->only(['name','office_id','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Campaign->status)
    );


    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Campaign->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Campaign);
        return $this->respondWithResource($Campaign, "لا يوجد تغييرات فعلية");
    }

    $Campaign->update($updateData);
    $changedData = $Campaign->getChangedData($oldData, $Campaign->fresh()->toArray());
    $Campaign->changed_data = $changedData;
    $Campaign->save();

    $this->loadCommonRelations($Campaign);
    return $this->respondWithResource($Campaign, "تم تحديث الحمله بنجاح");
}


        public function active(string $id)
    {
        $this->authorize('manage_system');
        $Campaign = Campaign::findOrFail($id);

        return $this->changeStatusSimple($Campaign, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $Campaign = Campaign::findOrFail($id);

        return $this->changeStatusSimple($Campaign, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return CampaignResource::class;
    }
}
