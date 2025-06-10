<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Requests\Admin\ServiceRequest;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\ServiceResource;
use App\Http\Resources\Admin\ShowAllServiceResource;


class ServiceController extends Controller
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

    $query = Service::query();

    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('branch_id')) {
        $query->where('branch_id', $request->branch_id);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $services = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllServiceResource::collection($services),
        'pagination' => [
            'total' => $services->total(),
            'count' => $services->count(),
            'per_page' => $services->perPage(),
            'current_page' => $services->currentPage(),
            'total_pages' => $services->lastPage(),
            'next_page_url' => $services->nextPageUrl(),
            'prev_page_url' => $services->previousPageUrl(),
        ],
        'message' => "Show All Services."
    ]);
}


public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = Service::query();

    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('branch_id')) {
        $query->where('branch_id', $request->branch_id);
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    $services = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllServiceResource::collection($services),
        'message' => "Show All Services."
    ]);
}


     public function create(ServiceRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
            'branch_id', 'name','description'

        ]), $this->prepareCreationMetaData());

        $Service = Service::create($data);

         return $this->respondWithResource($Service, "Service created successfully.");
    }


public function update(ServiceRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Service = Service::findOrFail($id);
    $oldData = $Service->toArray();

    $updateData = $request->only(['name','branch_id','description','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Service->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Service->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Service);
        return $this->respondWithResource($Service, "لا يوجد تغييرات فعلية");
    }

    $Service->update($updateData);

    $changedData = $Service->getChangedData($oldData, $Service->fresh()->toArray());
    $Service->changed_data = $changedData;
    $Service->save();



    $this->loadCommonRelations($Service);
    return $this->respondWithResource($Service, "تم تحديث الخدمه بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $Service = Service::find($id);

    if (!$Service) {
        return response()->json(['message' => "Service not found."], 404);
    }

    return $this->respondWithResource($Service, "Service retrieved for editing.");
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $Service = Service::findOrFail($id);

        return $this->changeStatusSimple($Service, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $Service = Service::findOrFail($id);

        return $this->changeStatusSimple($Service, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return ServiceResource::class;
    }
}
