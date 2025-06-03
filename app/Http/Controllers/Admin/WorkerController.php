<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Worker;
use App\Models\WorkerLogin;
use Illuminate\Http\Request;
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
use App\Http\Resources\Admin\ShowAllWorkerResource;
use App\Http\Resources\Auth\WorkerRegisterResource;
use App\Http\Resources\Admin\ShowAllWorkerLoginResource;

class WorkerController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;



// public function showAllWorkerLogin(Request $request)
// {
//     $this->authorize('manage_system');

//     $query = WorkerLogin::with(['worker', 'worker.title', 'worker.store', 'role'])
//         ->orderBy('created_at', 'desc');

//     if ($request->search) {
//         $query->whereHas('worker', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
//     }

//     if ($request->role_id) {
//         $query->where('role_id', $request->role_id);
//     }

//     if ($request->title_name) {
//         $query->whereHas('worker.title', function($q) use ($request) {
//             $q->where('name', 'like', '%'.$request->title_name.'%');
//         });
//     }

//     $workers = $query->paginate(10);

//     $this->loadRelationsForCollection($workers->getCollection());

//     return response()->json([
//         'data' => ShowAllWorkerLoginResource::collection($workers),
//         'pagination' => [
//             'total' => $workers->total(),
//             'count' => $workers->count(),
//             'per_page' => $workers->perPage(),
//             'current_page' => $workers->currentPage(),
//             'total_pages' => $workers->lastPage(),
//         ],
//         'message' => "Workers data retrieved successfully."
//     ]);
// }

// public function showAllWorkerLogin()
// {
//     $this->authorize('manage_system');

//     $branches = Branch::with([
//         'titles.workers.workerLogin.role',
//     ])->get();

//     return response()->json([
//         'data' => ShowAllWorkerLoginResource::collection($branches),
//         'message' => 'Workers structured data retrieved successfully.'
//     ]);
// }


public function showAllWorkerLogin(Request $request)
{
    $this->authorize('manage_system');

    $query = WorkerLogin::with(['worker', 'worker.title', 'worker.store', 'role'])
        ->orderBy('created_at', 'desc');

    // فلتر بالـ branch_id عن طريق علاقة worker -> title -> branch
    if ($request->filled('branch_id')) {
        $branchId = $request->branch_id;
        $query->whereHas('worker.title.branch', function ($q) use ($branchId) {
            $q->where('id', $branchId);
        });
    }

    // فلتر بالـ title_id (worker -> title)
    if ($request->filled('title_id')) {
        $query->whereHas('worker.title', function ($q) use ($request) {
            $q->where('id', $request->title_id);
        });
    }

    // فلتر بالـ role_id مباشرة
    if ($request->filled('role_id')) {
        $query->where('role_id', $request->role_id);
    }

    // سيرش بالاسم في جدول العمال (worker.name)
    if ($request->filled('worker_name')) {
        $search = $request->worker_name;
        $query->whereHas('worker', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    $workers = $query->paginate(10);

    // لو عندك دالة لتحميل علاقات إضافية ممكن تستخدمها
    // $this->loadRelationsForCollection($workers->getCollection());

    return response()->json([
        'data' => ShowAllWorkerLoginResource::collection($workers),
        'pagination' => [
            'total' => $workers->total(),
            'count' => $workers->count(),
            'per_page' => $workers->perPage(),
            'current_page' => $workers->currentPage(),
            'total_pages' => $workers->lastPage(),
        ],
        'message' => "Workers data retrieved successfully."
    ]);
}



public function showAll()
    {
        $this->authorize('manage_system');
        $branches = Branch::with('titles.workers')
        ->orderBy('created_at', 'desc')->get();
    $this->loadRelationsForCollection($branches);

         return response()->json([
             'data' =>  ShowAllWorkerResource::collection($branches),
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


// public function update(WorkerRequest $request, string $id)
// {
//     $this->authorize('manage_system');

//     $worker = Worker::find($id);
//     if (!$worker) {
//         return response()->json(['message' => "Worker not found."], 404);
//     }

//     $oldData = $worker->toArray();

//     $request->merge(['status' => $request->status ?? $worker->status ?? 'active']);

//     $fieldsToCheck = ['title_id', 'store_id', 'name', 'idNum', 'personPhoNum', 'branchPhoNum', 'salary', 'status', 'dashboardAccess'];
//     $hasChanges = false;

//     foreach ($fieldsToCheck as $field) {
//         if ($request->has($field)) {  // تم إصلاح هنا - إضافة القوس الناقص
//             $requestField = $request->$field ?? $worker->$field;
//             if ($worker->$field != $requestField) {
//                 $hasChanges = true;
//                 break;
//             }
//         }
//     }

//     if ($request->hasFile('cv')) {
//         $hasChanges = true;
//     }

//     if (!$hasChanges) {
//         $this->loadCommonRelations($worker);
//         return $this->respondWithResource($worker, "No actual changes detected.");
//     }

//     $updateData = array_merge(
//         $request->only($fieldsToCheck),
//         $this->prepareUpdateMeta($request, $worker->status)
//     );

//     $updateData['dashboardAccess'] = $updateData['dashboardAccess'] ?? 'notOk';


//     // $this->applyChangesAndSave($worker, $updateData, $oldData);
//        $worker->update($updateData);

//     $changedData = $worker->getChangedData($oldData, $worker->fresh()->toArray());
//     $worker->changed_data = $changedData;
//     $worker->save();

//     if ($request->hasFile('cv')) {
//         if ($worker->cv) {
//             Storage::disk('public')->delete($worker->cv);
//         }
//         $cvPath = $request->file('cv')->store(Worker::storageFolder, 'public');
//         $worker->cv = $cvPath;
//         $worker->save();
//     }

//     $this->loadCommonRelations($worker);

//     return $this->respondWithResource($worker, "Worker updated successfully.");
// }

public function update(WorkerRequest $request, string $id)
{
    $this->authorize('manage_system');

    $worker = Worker::find($id);
    if (!$worker) {
        return response()->json(['message' => "Worker not found."], 404);
    }

    $oldData = $worker->toArray();
    $oldCv = $worker->cv;

    $request->merge(['status' => $request->status ?? $worker->status ?? 'active']);

    $fieldsToCheck = ['title_id', 'store_id', 'name', 'idNum', 'personPhoNum', 'branchPhoNum', 'salary', 'status', 'dashboardAccess'];
    $hasChanges = false;

    foreach ($fieldsToCheck as $field) {
        if ($request->has($field)) {
            $requestField = $request->$field ?? $worker->$field;
            if ($worker->$field != $requestField) {
                $hasChanges = true;
                break;
            }
        }
    }


    $cvChanged = $request->hasFile('cv');
    if ($cvChanged) {
        $hasChanges = true;
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($worker);
        return $this->respondWithResource($worker, "No actual changes detected.");
    }

    $updateData = array_merge(
        $request->only($fieldsToCheck),
        $this->prepareUpdateMeta($request, $worker->status)
    );

    $updateData['dashboardAccess'] = $updateData['dashboardAccess'] ?? 'notOk';

    $worker->update($updateData);

    if ($cvChanged) {
        if ($worker->cv) {
            Storage::disk('public')->delete($worker->cv);
        }
        $cvPath = $request->file('cv')->store(Worker::storageFolder, 'public');
        $worker->cv = $cvPath;
        $worker->save();
    }

    $newData = $worker->fresh()->toArray();

    if ($cvChanged) {
        $newData['cv'] = $worker->cv;
        $oldData['cv'] = $oldCv;
    }

    $changedData = $worker->getChangedData($oldData, $newData);
    $worker->changed_data = $changedData;
    $worker->save();

    $this->loadCommonRelations($worker);

    return $this->respondWithResource($worker, "Worker updated successfully.");
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
        return $this->respondWithResource($worker, 'Worker dashboard access is already set to not OK');
    }

    $worker->dashboardAccess = 'notOk';
    $worker->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $worker->creationDateHijri = $this->getHijriDate();
    $worker->updated_by = $this->getUpdatedByIdOrFail();
    $worker->updated_by_type = $this->getUpdatedByType();
    $worker->save();

    $metaForDiffOnly = [
        'creationDate' => $worker->creationDate,
        'creationDateHijri' => $worker->creationDateHijri,
    ];

    $changedData = $worker->getChangedData($oldData, array_merge($worker->fresh()->toArray(), $metaForDiffOnly));
    $worker->changed_data = $changedData;
    $worker->save();

    $this->loadCommonRelations($worker);
    return $this->respondWithResource($worker, 'Worker dashboard access set to not OK');
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
        return $this->respondWithResource($worker, 'Worker dashboard access is already set to OK');
    }

    $worker->dashboardAccess = 'ok';
    $worker->creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $worker->creationDateHijri = $this->getHijriDate();
    $worker->updated_by = $this->getUpdatedByIdOrFail();
    $worker->updated_by_type = $this->getUpdatedByType();
    $worker->save();

    $metaForDiffOnly = [
        'creationDate' => $worker->creationDate,
        'creationDateHijri' => $worker->creationDateHijri,
    ];

    $changedData = $worker->getChangedData($oldData, array_merge($worker->fresh()->toArray(), $metaForDiffOnly));
    $worker->changed_data = $changedData;
    $worker->save();

    $this->loadCommonRelations($worker);
    return $this->respondWithResource($worker, 'Worker dashboard access set to OK');
}


}
