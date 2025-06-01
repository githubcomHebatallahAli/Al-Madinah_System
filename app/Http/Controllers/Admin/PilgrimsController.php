<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pilgrims;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\PilgrimsRequest;
use App\Http\Resources\Admin\PilgrimsResource;


class PilgrimsController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

        public function showAll()
    {
        $this->authorize('showAll',Pilgrims::class);

        $Pilgrims = Pilgrims::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($Pilgrims);

        return response()->json([
            'data' =>  PilgrimsResource::collection($Pilgrims),
            'message' => "Show All Pilgrims."
        ]);
    }


    public function create(PilgrimsRequest $request)
    {
         $this->authorize('create',Pilgrims::class);
       $data = array_merge($request->only([
            'name','phoNum','nationality',
            'description','idNum'
        ]), $this->prepareCreationMetaData());

        $Pilgrims = Pilgrims::create($data);

         return $this->respondWithResource($Pilgrims, "Pilgrims created successfully.");
        }

        public function edit(string $id)
        {
        $Pilgrims = Pilgrims::find($id);

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
    $Pilgrims = Pilgrims::findOrFail($id);
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
        $Pilgrims = Pilgrims::findOrFail($id);
         $this->authorize('active',$Pilgrims);

        return $this->changeStatusSimple($Pilgrims, 'active');
    }

    public function notActive(string $id)
    {
        $Pilgrims = Pilgrims::findOrFail($id);
         $this->authorize('notActive',$Pilgrims);

        return $this->changeStatusSimple($Pilgrims, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return PilgrimsResource::class;
    }
}
