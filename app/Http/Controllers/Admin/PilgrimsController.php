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

    $searchTerm = $request->input('search', '');

    $query = Pilgrim::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

           if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

       if ($request->filled('gender') && in_array($request->gender, ['male', 'female','child'])) {
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

    $Pligrims = $query->paginate(10);

    return response()->json([
        'data' => ShowAllPilgrimsResource::collection($Pligrims),
        'pagination' => [
            'total' => $Pligrims->total(),
            'count' => $Pligrims->count(),
            'per_page' => $Pligrims->perPage(),
            'current_page' => $Pligrims->currentPage(),
            'total_pages' => $Pligrims->lastPage(),
            'next_page_url' => $Pligrims->nextPageUrl(),
            'prev_page_url' => $Pligrims->previousPageUrl(),
        ],
        'message' => "Show All Pligrims."
    ]);
}
}


public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $searchTerm = $request->input('search', '');

    $query = Pilgrim::where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc');

       if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

       if ($request->filled('gender') && in_array($request->gender, ['male', 'female','child'])) {
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

    $Pligrims = $query->get();

    return response()->json([
        'data' => ShowAllPilgrimsResource::collection($Pligrims),
        'message' => "Show All Pligrims."
    ]);
}


    public function create(PilgrimsRequest $request)
    {
        //  $this->authorize('create',Pilgrim::class);
       $data = array_merge($request->only([
            'name','phoNum','nationality',
            'description','idNum'
        ]), $this->prepareCreationMetaData());

        $Pilgrims = Pilgrim::create($data);

         return $this->respondWithResource($Pilgrims, "Pilgrims created successfully.");
        }

        public function edit(string $id)
        {
        $Pilgrims = Pilgrim::find($id);

        if (!$Pilgrims) {
            return response()->json([
                'message' => "Pilgrims not found."
            ], 404);
            }
             $this->authorize('edit',$Pilgrims);

    return $this->respondWithResource($Pilgrims, "Pilgrims retrieved for editing.");
        }

public function update(PilgrimsRequest $request, string $id)
{
    $Pilgrims = Pilgrim::findOrFail($id);
     $this->authorize('update',$Pilgrims);
    $oldData = $Pilgrims->toArray();

    $updateData = $request->only(['status','name','phoNum','nationality',
            'description','idNum'
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
        $Pilgrims = Pilgrim::findOrFail($id);
         $this->authorize('active',$Pilgrims);

        return $this->changeStatusSimple($Pilgrims, 'active');
    }

    public function notActive(string $id)
    {
        $Pilgrims = Pilgrim::findOrFail($id);
         $this->authorize('notActive',$Pilgrims);

        return $this->changeStatusSimple($Pilgrims, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return PilgrimsResource::class;
    }
}
