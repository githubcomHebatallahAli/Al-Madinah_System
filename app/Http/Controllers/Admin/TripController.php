<?php

namespace App\Http\Controllers\Admin;

use App\Models\Trip;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TripRequest;
use App\Http\Resources\Admin\TripResource;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\ShowAllTripResource;

class TripController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

public function showAllWithPaginate(Request $request)
{
    $this->authorize('manage_users');

    $query = Trip::query();

    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('branch_id')) {
        $query->where('branch_id', $request->branch_id);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $trips = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllTripResource::collection($trips),
        'pagination' => [
            'total' => $trips->total(),
            'count' => $trips->count(),
            'per_page' => $trips->perPage(),
            'current_page' => $trips->currentPage(),
            'total_pages' => $trips->lastPage(),
            'next_page_url' => $trips->nextPageUrl(),
            'prev_page_url' => $trips->previousPageUrl(),
        ],
        'message' => "Show All Trips."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_users');

    $query = Trip::query();

    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('branch_id')) {
        $query->where('branch_id', $request->branch_id);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $trips = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllTripResource::collection($trips),
        'message' => "Show All Trips."
    ]);
}



     public function create(TripRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
            'branch_id', 'name','description'

        ]), $this->prepareCreationMetaData());

        $Trip = Trip::create($data);

         return $this->respondWithResource($Trip, "Trip created successfully.");
    }


public function update(TripRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Trip = Trip::findOrFail($id);
    $oldData = $Trip->toArray();

    $updateData = $request->only(['name','branch_id','description','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Trip->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Trip->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Trip);
        return $this->respondWithResource($Trip, "لا يوجد تغييرات فعلية");
    }

    $Trip->update($updateData);

    $changedData = $Trip->getChangedData($oldData, $Trip->fresh()->toArray());
    $Trip->changed_data = $changedData;
    $Trip->save();



    $this->loadCommonRelations($Trip);
    return $this->respondWithResource($Trip, "تم تحديث الرحله بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $Trip = Trip::find($id);

    if (!$Trip) {
        return response()->json(['message' => "Trip not found."], 404);
    }

    return $this->respondWithResource($Trip, "Trip retrieved for editing.");
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $Trip = Trip::findOrFail($id);

        return $this->changeStatusSimple($Trip, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $Trip = Trip::findOrFail($id);

        return $this->changeStatusSimple($Trip, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return TripResource::class;
    }
}
