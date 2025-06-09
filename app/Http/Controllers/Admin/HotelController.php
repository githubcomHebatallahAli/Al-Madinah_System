<?php

namespace App\Http\Controllers\Admin;

use App\Models\Hotel;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HotelRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\HotelResource;
use App\Traits\HandlesControllerCrudsTrait;


class HotelController extends Controller
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
        $Hoteles = Hotel::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($Hoteles);

        return response()->json([
            'data' =>  HotelResource::collection($Hoteles),
            'message' => "Show All Hoteles."
        ]);
    }


    public function create(HotelRequest $request)
    {
        $this->authorize('manage_system');
       $data = array_merge($request->only([
            'service_id','company_id', 'name','place','address',
            'description','communication','quantity','sellingPrice','purchesPrice'
        ]), $this->prepareCreationMetaData());

        $Hotel = Hotel::create($data);

         return $this->respondWithResource($Hotel, "Hotel created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Hotel = Hotel::find($id);

        if (!$Hotel) {
            return response()->json([
                'message' => "Hotel not found."
            ], 404);
            }

    return $this->respondWithResource($Hotel, "Hotel retrieved for editing.");
        }

public function update(HotelRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Hotel = Hotel::findOrFail($id);
    $oldData = $Hotel->toArray();

    $updateData = $request->only(['status', 'service_id','company_id','name','place','address',
            'description','communication','quantity','sellingPrice','purchesPrice'
            ]);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Hotel->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Hotel->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Hotel);
        return $this->respondWithResource($Hotel, "لا يوجد تغييرات فعلية");
    }

    $Hotel->update($updateData);
    $changedData = $Hotel->getChangedData($oldData, $Hotel->fresh()->toArray());
    $Hotel->changed_data = $changedData;
    $Hotel->save();

    $this->loadCommonRelations($Hotel);
    return $this->respondWithResource($Hotel, "تم تحديث الفندق بنجاح");
}

    public function active(string $id)
    {
         $this->authorize('manage_system');
        $Hotel = Hotel::findOrFail($id);

        return $this->changeStatusSimple($Hotel, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_system');
        $Hotel = Hotel::findOrFail($id);

        return $this->changeStatusSimple($Hotel, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return HotelResource::class;
    }
}
