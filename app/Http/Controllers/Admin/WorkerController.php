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
use App\Http\Resources\Admin\WorkerWebResource;
use App\Http\Resources\Admin\ShowAllWorkerResource;
use App\Http\Resources\Auth\WorkerRegisterResource;
use App\Http\Resources\Admin\ShowAllWorkerLoginResource;
use App\Http\Resources\Admin\ShowAllWorkerLoginWebResource;

class WorkerController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;



public function showAllWorkerLoginWeb(Request $request)
{
    $this->authorize('manage_system');

    $query = WorkerLogin::with(['worker', 'worker.title', 'worker.store', 'role'])
        ->orderBy('created_at', 'desc');

    if ($request->search) {
        $query->whereHas('worker', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
    }

    if ($request->role_id) {
        $query->where('role_id', $request->role_id);
    }

    if ($request->title_name) {
        $query->whereHas('worker.title', function($q) use ($request) {
            $q->where('name', 'like', '%'.$request->title_name.'%');
        });
    }

    $workers = $query->paginate(10);

    $this->loadRelationsForCollection($workers->getCollection());

    return response()->json([
        'data' => ShowAllWorkerLoginWebResource::collection($workers),
        'pagination' => [
            'total' => $workers->total(),
            'count' => $workers->count(),
            'per_page' => $workers->perPage(),
            'current_page' => $workers->currentPage(),
            'total_pages' => $workers->lastPage(),
        ],
        'message' => "Workers Login data retrieved successfully."
    ]);
}




// public function showAllWorkerLogin(Request $request)
// {
//   $this->authorize('manage_users');

//     $query = Branch::with([
//         'titles.workers.workerLogin.role'
//     ])->orderBy('created_at', 'desc');

//     if ($request->filled('branch_id')) {
//         $query->where('id', $request->branch_id);
//     }

//     if ($request->filled('title_id')) {
//         $query->whereHas('titles', function($q) use ($request) {
//             $q->where('id', $request->title_id);
//         });
//     }
//     if ($request->filled('role_id') || $request->filled('worker_name')) {
//         $query->whereHas('titles.workers.workerLogin', function($q) use ($request) {
//             if ($request->filled('role_id')) {
//                 $q->where('role_id', $request->role_id);
//             }
//             if ($request->filled('worker_name')) {
//                 $search = $request->worker_name;
//                 $q->whereHas('worker', function($q2) use ($search) {
//                     $q2->where('name', 'like', "%{$search}%");
//                 });
//             }
//         });
//     }

//     $branches = $query->paginate(10);

//     return response()->json([
//         'data' => ShowAllWorkerLoginResource::collection($branches),
//         'pagination' => [
//             'total' => $branches->total(),
//             'count' => $branches->count(),
//             'per_page' => $branches->perPage(),
//             'current_page' => $branches->currentPage(),
//             'total_pages' => $branches->lastPage(),
//         ],
//         'message' => "Workers login data retrieved successfully."
//     ]);
// }


// Ziad
public function showAllWeb()
{
    $this->authorize('manage_system');

    $query = Worker::query();

    if (request()->filled('name')) {
        $query->where('name', 'like', '%' . request('name') . '%');
    }


    if (request()->filled('title_id')) {
        $query->where('title_id', request('title_id'));
    }

     if (request()->filled('branch_id')) {
        $query->whereHas('branch', function ($q) {
            $q->where('branches.id', request('branch_id')); // تم التوضيح هنا
        });
    }

    $workers = $query->orderBy('created_at', 'desc')->get();

    $this->loadRelationsForCollection($workers);

    return response()->json([
        'data' => WorkerWebResource::collection($workers),
        'message' => "Show All Workers."
    ]);
}



//     public function showAll(Request $request)
// {
//     $this->authorize('manage_system');

//     $query = Branch::with(['titles.workers'])
//         ->orderBy('created_at', 'desc');

//     if ($request->filled('branch_id')) {
//         $query->where('id', $request->branch_id);
//     }

//     if ($request->filled('title_id')) {
//         $query->whereHas('titles', function ($q) use ($request) {
//             $q->where('id', $request->title_id);
//         });
//     }


//     if ($request->filled('worker_name')) {
//         $search = $request->worker_name;
//         $query->whereHas('titles.workers', function ($q) use ($search) {
//             $q->where('name', 'like', "%{$search}%");
//         });
//     }

//     $branches = $query->paginate(10);

//     return response()->json([
//         'data' => ShowAllWorkerResource::collection($branches),
//         'pagination' => [
//             'total' => $branches->total(),
//             'count' => $branches->count(),
//             'per_page' => $branches->perPage(),
//             'current_page' => $branches->currentPage(),
//             'total_pages' => $branches->lastPage(),
//         ],
//         'message' => "Show All Workers with filters and search."
//     ]);
// }



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
