<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pilgrim;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\PilgrimsRequest;
use App\Http\Resources\Admin\PilgrimsResource;
use App\Http\Resources\Admin\ShowAllPilgrimsResource;


class PilgrimsController extends Controller
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

    $query = Pilgrim::query();

    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    if ($request->filled('gender') && in_array($request->gender, ['male', 'female', 'child'])) {
        $query->where('gender', $request->gender);
    }

    if ($request->filled('nationality')) {
        $query->where('nationality', $request->nationality);
    }

    if ($request->filled('fromDate')) {
        $query->whereDate('creationDate', '>=', $request->fromDate);
    }

    if ($request->filled('toDate')) {
        $query->whereDate('creationDate', '<=', $request->toDate);
    }

    $pilgrims = $query->orderBy('created_at', 'desc')->paginate(10);

    return response()->json([
        'data' => ShowAllPilgrimsResource::collection($pilgrims),
        'pagination' => [
            'total' => $pilgrims->total(),
            'count' => $pilgrims->count(),
            'per_page' => $pilgrims->perPage(),
            'current_page' => $pilgrims->currentPage(),
            'total_pages' => $pilgrims->lastPage(),
            'next_page_url' => $pilgrims->nextPageUrl(),
            'prev_page_url' => $pilgrims->previousPageUrl(),
        ],
        'message' => "Show All Pilgrims."
    ]);
}

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = Pilgrim::query();

    if ($request->filled('search')) {
        $query->where('name', 'like', '%' . $request->search . '%');
    }

    if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    if ($request->filled('gender') && in_array($request->gender, ['male', 'female', 'child'])) {
        $query->where('gender', $request->gender);
    }

    if ($request->filled('nationality')) {
        $query->where('nationality', $request->nationality);
    }

    if ($request->filled('fromDate')) {
        $query->whereDate('creationDate', '>=', $request->fromDate);
    }

    if ($request->filled('toDate')) {
        $query->whereDate('creationDate', '<=', $request->toDate);
    }

    $pilgrims = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'data' => ShowAllPilgrimsResource::collection($pilgrims),
        'message' => "Show All Pilgrims."
    ]);
}



    public function create(PilgrimsRequest $request)
    {
          $this->authorize('manage_system');
        //  $this->authorize('create',Pilgrim::class);
       $data = array_merge($request->only([
            'name','phoNum','nationality',
            'description','idNum','gender'
        ]), $this->prepareCreationMetaData());

        $Pilgrims = Pilgrim::create($data);

         return $this->respondWithResource($Pilgrims, "Pilgrims created successfully.");
        }

        public function edit(string $id)
        {
              $this->authorize('manage_system');
        $Pilgrims = Pilgrim::find($id);

        if (!$Pilgrims) {
            return response()->json([
                'message' => "Pilgrims not found."
            ], 404);
            }
            //  $this->authorize('edit',$Pilgrims);

    return $this->respondWithResource($Pilgrims, "Pilgrims retrieved for editing.");
        }

public function update(PilgrimsRequest $request, string $id)
{
      $this->authorize('manage_system');
    $Pilgrims = Pilgrim::findOrFail($id);
    //  $this->authorize('update',$Pilgrims);
    $oldData = $Pilgrims->toArray();

    $updateData = $request->only(['status','name','phoNum','nationality',
            'description','idNum','gender'
            ]);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Pilgrims->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Pilgrims->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Pilgrims);
        return $this->respondWithResource($Pilgrims, "لا يوجد تغييرات فعلية");
    }

    $Pilgrims->update($updateData);
    $changedData = $Pilgrims->getChangedData($oldData, $Pilgrims->fresh()->toArray());
    $Pilgrims->changed_data = $changedData;
    $Pilgrims->save();

    $this->loadCommonRelations($Pilgrims);
    return $this->respondWithResource($Pilgrims, "تم تحديث المعتمر بنجاح");
}

    public function active(string $id)
    {
          $this->authorize('manage_system');
        $Pilgrims = Pilgrim::findOrFail($id);
        //  $this->authorize('active',$Pilgrims);

        return $this->changeStatusSimple($Pilgrims, 'active');
    }

    public function notActive(string $id)
    {  $this->authorize('manage_system');
        $Pilgrims = Pilgrim::findOrFail($id);
        //  $this->authorize('notActive',$Pilgrims);

        return $this->changeStatusSimple($Pilgrims, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return PilgrimsResource::class;
    }
}
