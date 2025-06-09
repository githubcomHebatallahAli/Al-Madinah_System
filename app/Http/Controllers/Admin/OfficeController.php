<?php

namespace App\Http\Controllers\Admin;

use App\Models\Office;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OfficeRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\OfficeResource;
use App\Http\Resources\Admin\ShowAllOfficeResource;


class OfficeController extends Controller
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

    $query = Office::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->branch_id) {
        $query->where('branch_id', $request->branch_id);
    }

    $Offices = $query->paginate(10);

    return response()->json([
        'data' => ShowAllOfficeResource::collection($Offices),
        'pagination' => [
            'total' => $Offices->total(),
            'count' => $Offices->count(),
            'per_page' => $Offices->perPage(),
            'current_page' => $Offices->currentPage(),
            'total_pages' => $Offices->lastPage(),
            'next_page_url' => $Offices->nextPageUrl(),
            'prev_page_url' => $Offices->previousPageUrl(),
        ],
        'message' => "Show All Offices."
    ]);
}


public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $searchTerm = $request->input('search', '');

    $query = Office::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

    if ($request->branch_id) {
        $query->where('branch_id', $request->branch_id);
    }

    $Offices = $query->get();

    return response()->json([
        'data' => ShowAllOfficeResource::collection($Offices),
        'message' => "Show All Offices."
    ]);
}

     public function create(OfficeRequest $request)
    {
        $this->authorize('manage_users');
        $data = array_merge($request->only([
            'branch_id','name','address','phoNum1','phoNum2'

        ]), $this->prepareCreationMetaData());

        $Office = Office::create($data);

         return $this->respondWithResource($Office, "Office created successfully.");
    }


public function update(OfficeRequest $request, string $id)
{
    $this->authorize('manage_users');
    $Office = Office::findOrFail($id);
    $oldData = $Office->toArray();

    $updateData = $request->only(['name','address','branch_id','phoNum1','phoNum2','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Office->status)
    );




    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Office->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Office);
        return $this->respondWithResource($Office, "لا يوجد تغييرات فعلية");
    }

    $Office->update($updateData);
    $changedData = $Office->getChangedData($oldData, $Office->fresh()->toArray());
    $Office->changed_data = $changedData;
    $Office->save();

    $this->loadCommonRelations($Office);
    return $this->respondWithResource($Office, "تم تحديث المكتب بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $Office = Office::find($id);

    if (!$Office) {
        return response()->json(['message' => "Office not found."], 404);
    }

    return $this->respondWithResource($Office, "Office retrieved for editing.");
}

        public function active(string $id)
    {
         $this->authorize('manage_users');
        $Office = Office::findOrFail($id);

        return $this->changeStatusSimple($Office, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_users');
        $Office = Office::findOrFail($id);

        return $this->changeStatusSimple($Office, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return OfficeResource::class;
    }
}
