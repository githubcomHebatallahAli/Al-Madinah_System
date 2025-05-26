<?php

namespace App\Http\Controllers\Admin;

use App\Models\Worker;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Admin\WorkerRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Admin\WorkerResource;

class WorkerController extends Controller
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
        $Workers = Worker::orderBy('created_at', 'desc')->get();
    $this->loadRelationsForCollection($Workers);

         return response()->json([
             'data' =>  WorkerResource::collection($Workers),
             'message' => "Show All Workers."
        ]);
    }

public function create(WorkerRequest $request)
    {
        $this->authorize('manage_system');
    $data = $request->only([
        'title_id', 'store_id', 'name', 'idNum',
        'personPhoNum', 'branchPhoNum', 'salary'
    ]);

    $data = array_merge($data, $this->prepareCreationMetaData(), [
        'dashboardAccess' => 'notOk',
    ]);

    $Worker = Worker::create($data);
    if ($request->hasFile('cv')) {
        $cvPath = $request->file('cv')->store(Worker::storageFolder);
        $Worker->cv = $cvPath;
        $Worker->save();
    }

         return $this->respondWithResource($Worker, "Worker created successfully.");
        }

public function edit(string $id)
    {
       $this->authorize('manage_system');
        $Worker = Worker::find($id);
            if (!$Worker) {
                return response()->json([
                    'message' => "Worker not found."
                ], 404);
            }

    return $this->respondWithResource($Worker, "Worker retrieved for editing.");
        }


public function update(WorkerRequest $request, string $id)
{
    $this->authorize('manage_system');

    $worker = Worker::find($id);
    if (!$worker) {
        return response()->json(['message' => "Worker not found."], 404);
    }

    $oldData = $worker->toArray();

    $request->merge(['status' => $request->status ?? $worker->status ?? 'active']);

    $fieldsToCheck = ['Worker_id', 'store_id', 'name', 'idNum', 'personPhoNum', 'branchPhoNum', 'salary', 'status', 'dashboardAccess'];
    $hasChanges = false;

    foreach ($fieldsToCheck as $field) {
        if ($request->has($field)) {  // تم إصلاح هنا - إضافة القوس الناقص
            $requestField = $request->$field ?? $worker->$field;
            if ($worker->$field != $requestField) {
                $hasChanges = true;
                break;
            }
        }
    }

    if ($request->hasFile('cv')) {
        $hasChanges = true;
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($worker);
        return $this->respondWithResource($worker, "No actual changes detected.");
    }

    $updateData = array_merge(
        $request->only($fieldsToCheck),
        $this->prepareUpdateMeta($request, $worker->status) // تمرير الحالة الحالية كقيمة افتراضية
    );

    $updateData['dashboardAccess'] = $updateData['dashboardAccess'] ?? 'notOk';


    // $this->applyChangesAndSave($worker, $updateData, $oldData);
       $worker->update($updateData);

    $changedData = $worker->getChangedData($oldData, $worker->fresh()->toArray());
    $worker->changed_data = $changedData;
    $worker->save();

    if ($request->hasFile('cv')) {
        if ($worker->cv) {
            Storage::disk('public')->delete($worker->cv);
        }
        $cvPath = $request->file('cv')->store(Worker::storageFolder, 'public');
        $worker->cv = $cvPath;
        $worker->save();
    }
     $this->loadCreatorRelations($worker);
    $this->loadUpdaterRelations($worker);
       return response()->json([
             'data' =>new  WorkerResource($worker),
             'message' => "Worker updated successfully."
        ]);
    // $this->loadCommonRelations($worker);

    // return $this->respondWithResource($worker, "Worker updated successfully.");
}

    public function active(string $id)
    {
        $this->authorize('manage_system');
        $Worker = Worker::findOrFail($id);

        return $this->changeStatusSimple($Worker, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $Worker = Worker::findOrFail($id);

        return $this->changeStatusSimple($Worker, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return WorkerResource::class;
    }

public function notOk(string $id)
{
    $this->authorize('manage_system');

    $worker = Worker::find($id);
    if (!$worker) {
        return response()->json(['message' => "Worker not found."], 404);
    }

    $oldData = $worker->toArray();

    if ($worker->dashboardAccess === 'notOk') {
        $this->loadCommonRelations($worker);
        return response()->json([
            'data' => new WorkerResource($worker),
            'message' => 'Worker dashboard access is already set to not OK',
        ]);
    }

    $updatedById = $this->getUpdatedByIdOrFail();
    $updatedByType = $this->getUpdatedByType();
    $hijriDate = $this->getHijriDate();
    $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    $worker->update([
        'dashboardAccess' => 'notOk',
        'creationDate' => $gregorianDate,
        'creationDateHijri' => $hijriDate,
        'updated_by' => $updatedById,
        'updated_by_type' => $updatedByType,
    ]);

    $changedData = $this->getChangedData($oldData, $worker->fresh()->toArray());
    $worker->changed_data = $changedData;
    $worker->save();

    $this->loadCommonRelations($worker);

    return response()->json([
        'data' => new WorkerResource($worker),
        'message' => 'Worker dashboard access set to not OK',
    ]);
}

public function ok(string $id)
{
    $this->authorize('manage_system');

    $worker = Worker::find($id);
    if (!$worker) {
        return response()->json(['message' => "Worker not found."], 404);
    }

    $oldData = $worker->toArray();

    if ($worker->dashboardAccess === 'ok') {
        $this->loadCommonRelations($worker);
        return response()->json([
            'data' => new WorkerResource($worker),
            'message' => 'Worker dashboard access is already set to OK',
        ]);
    }

    $updatedById = $this->getUpdatedByIdOrFail();
    $updatedByType = $this->getUpdatedByType();
    $hijriDate = $this->getHijriDate();
    $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

    $worker->update([
        'dashboardAccess' => 'ok',
        'creationDate' => $gregorianDate,
        'creationDateHijri' => $hijriDate,
        'updated_by' => $updatedById,
        'updated_by_type' => $updatedByType,
    ]);

    $changedData = $this->getChangedData($oldData, $worker->fresh()->toArray());
    $worker->changed_data = $changedData;
    $worker->save();

    $this->loadCommonRelations($worker);

    return response()->json([
        'data' => new WorkerResource($worker),
        'message' => 'Worker dashboard access set to OK',
    ]);
}

}
