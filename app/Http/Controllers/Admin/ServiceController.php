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

    $searchTerm = $request->input('search', '');

    $query = Service::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->branch_id) {
        $query->where('branch_id', $request->branch_id);
    }

    $Services = $query->paginate(10);

    return response()->json([
        'data' => ShowAllServiceResource::collection($Services),
        'pagination' => [
            'total' => $Services->total(),
            'count' => $Services->count(),
            'per_page' => $Services->perPage(),
            'current_page' => $Services->currentPage(),
            'total_pages' => $Services->lastPage(),
            'next_page_url' => $Services->nextPageUrl(),
            'prev_page_url' => $Services->previousPageUrl(),
        ],
        'message' => "Show All Services."
    ]);
}


public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $searchTerm = $request->input('search', '');

    $query = Service::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->branch_id) {
        $query->where('branch_id', $request->branch_id);
    }

    $Services = $query->get();

    return response()->json([
        'data' => ShowAllServiceResource::collection($Services),
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
