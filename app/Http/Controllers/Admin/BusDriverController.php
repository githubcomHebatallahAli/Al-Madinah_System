<?php

namespace App\Http\Controllers\Admin;

use App\Models\BusDriver;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Requests\Admin\BusDriverRequest;
use App\Http\Resources\Admin\BusDriverResource;

class BusDriverController extends Controller
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
        $BusDrivers = BusDriver::orderBy('created_at', 'desc')->get();

         $this->loadRelationsForCollection($BusDrivers);

        return response()->json([
            'data' => BusDriverResource::collection($BusDrivers),
            'message' => "All BusDrivers retrieved successfully."
        ]);
    }

     public function create(BusDriverRequest $request)
    {
        $this->authorize('manage_system');
        $data = array_merge($request->only([
            'bus_id', 'name','phoNum','idNum'

        ]), $this->prepareCreationMetaData());

        $BusDriver = BusDriver::create($data);

         return $this->respondWithResource($BusDriver, "BusDriver created successfully.");
    }


public function update(BusDriverRequest $request, string $id)
{
    $this->authorize('manage_system');
    $BusDriver = BusDriver::findOrFail($id);
    $oldData = $BusDriver->toArray();

    $updateData = $request->only([ 'bus_id', 'name','phoNum','idNum','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $BusDriver->status)
    );

    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($BusDriver->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($BusDriver);
        return $this->respondWithResource($BusDriver, "لا يوجد تغييرات فعلية");
    }

    $BusDriver->update($updateData);

    $changedData = $BusDriver->getChangedData($oldData, $BusDriver->fresh()->toArray());
    $BusDriver->changed_data = $changedData;
    $BusDriver->save();



    $this->loadCommonRelations($BusDriver);
    return $this->respondWithResource($BusDriver, "تم تحديث سائق الباص بنجاح");
}

public function edit(string $id)
{
    $this->authorize('manage_system');
    $BusDriver = BusDriver::find($id);

    if (!$BusDriver) {
        return response()->json(['message' => "BusDriver not found."], 404);
    }

    return $this->respondWithResource($BusDriver, "BusDriver retrieved for editing.");
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $BusDriver = BusDriver::findOrFail($id);

        return $this->changeStatusSimple($BusDriver, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $BusDriver = BusDriver::findOrFail($id);

        return $this->changeStatusSimple($BusDriver, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return BusDriverResource::class;
    }
}
