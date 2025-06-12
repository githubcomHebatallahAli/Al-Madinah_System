<?php

namespace App\Http\Controllers\Admin;

use App\Models\WorkerLogin;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Traits\HandlesControllerCrudsTrait;
use App\Http\Resources\Auth\WorkerRegisterResource;
use App\Http\Resources\Admin\ShowAllWorkerLoginWebResource;

class WorkerLoginController extends Controller
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

    $query = WorkerLogin::with(['worker', 'worker.title', 'role'])
        ->orderBy('created_at', 'desc');

    if ($request->search) {
        $query->whereHas('worker', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
    }

        if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    if ($request->role_id) {
        $query->where('role_id', $request->role_id);
    }

    if ($request->title_name) {
        $query->whereHas('worker.title', function($q) use ($request) {
            $q->where('name', 'like', '%'.$request->title_name.'%');
        });
    }

    if ($request->branch_id) {
    $query->whereHas('worker.title', function($q) use ($request) {
        $q->where('branch_id', $request->branch_id);
    });
}

    $workers = $query->paginate(10);

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

public function showAllWithoutPaginate(Request $request)
{
    $this->authorize('manage_system');

    $query = WorkerLogin::with(['worker', 'worker.title', 'role'])
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

        if ($request->filled('status') && in_array($request->status, ['active', 'notActive'])) {
        $query->where('status', $request->status);
    }

    if ($request->branch_id) {
    $query->whereHas('worker.title', function($q) use ($request) {
        $q->where('branch_id', $request->branch_id);
    });
}

    $workers = $query->get();

    return response()->json([
        'data' => ShowAllWorkerLoginWebResource::collection($workers),
        'message' => "Workers Login data retrieved successfully."
    ]);
}

        public function active(string $id)
    {
        $this->authorize('manage_system');
        $Worker = WorkerLogin::findOrFail($id);

        return $this->changeStatusSimple($Worker, 'active');
    }

    public function notActive(string $id)
    {

        $this->authorize('manage_system');
        $Worker = WorkerLogin::findOrFail($id);

        return $this->changeStatusSimple($Worker, 'notActive');
    }

      protected function getResourceClass(): string
    {
        return WorkerRegisterResource::class;
    }
}
