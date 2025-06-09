<?php

namespace App\Http\Controllers\Admin;

use App\Models\Flight;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlightRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\FlightResource;

class FlightController extends Controller
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
        $Flightes = Flight::orderBy('created_at', 'desc')
        ->get();
       $this->loadRelationsForCollection($Flightes);

        return response()->json([
            'data' =>  FlightResource::collection($Flightes),
            'message' => "Show All Flightes."
        ]);
    }


    public function create(FlightRequest $request)
    {
        $this->authorize('manage_system');
       $data = array_merge($request->only([
            'service_id','company_id', 'direction', 'description','class','seatNum'
            ,'quantity','sellingPrice','purchesPrice','DateTimeTrip','DateTimeTripHijri'
        ]), $this->prepareCreationMetaData());

        $Flight = Flight::create($data);

         return $this->respondWithResource($Flight, "Flight created successfully.");
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Flight = Flight::find($id);

        if (!$Flight) {
            return response()->json([
                'message' => "Flight not found."
            ], 404);
            }

    return $this->respondWithResource($Flight, "Flight retrieved for editing.");
        }

public function update(FlightRequest $request, string $id)
{
    $this->authorize('manage_system');
    $Flight = Flight::findOrFail($id);
    $oldData = $Flight->toArray();

    $updateData = $request->only(['status','service_id','company_id','direction', 'description'
            ,'quantity','sellingPrice','purchesPrice','DateTimeTrip','DateTimeTripHijri',
            'class','seatNum'
            ]);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Flight->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Flight->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Flight);
        return $this->respondWithResource($Flight, "لا يوجد تغييرات فعلية");
    }

    $Flight->update($updateData);
    $changedData = $Flight->getChangedData($oldData, $Flight->fresh()->toArray());
    $Flight->changed_data = $changedData;
    $Flight->save();

    $this->loadCommonRelations($Flight);
    return $this->respondWithResource($Flight, "تم تحديث تذكره الطيران بنجاح");
}

    public function active(string $id)
    {
         $this->authorize('manage_system');
        $Flight = Flight::findOrFail($id);

        return $this->changeStatusSimple($Flight, 'active');
    }

    public function notActive(string $id)
    {
         $this->authorize('manage_system');
        $Flight = Flight::findOrFail($id);

        return $this->changeStatusSimple($Flight, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return FlightResource::class;
    }
}
