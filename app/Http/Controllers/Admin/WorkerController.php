<?php

namespace App\Http\Controllers\Admin;

use App\Models\Worker;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Admin\WorkerRequest;
use App\Http\Resources\Admin\WorkerResource;

class WorkerController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;

public function showAll()
    {
        $this->authorize('manage_users');
        $Worker = Worker::orderBy('created_at', 'desc')
        ->get();

                  return response()->json([
                      'data' =>  WorkerResource::collection($Worker),
                      'message' => "Show All Workeres."
                  ]);
    }

public function create(WorkerRequest $request)
    {
        $this->authorize('manage_users');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

          $addedById = null;

    if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->role_id == 1) {
        $addedById = Auth::guard('admin')->id(); // Super Admin
    } elseif (Auth::guard('worker')->check() && Auth::guard('worker')->user()->role_id == 2) {
        $addedById = Auth::guard('worker')->id(); // Branch Manager
    } else {
        return response()->json([
            'message' => 'Unauthorized: Only SuperAdmin or BranchManager can add workers.'
        ], 403);
    }

        $Worker = Worker::create([
            'title_id'=> $request ->title_id,
            'store_id'=> $request ->store_id,
            "name" => $request->name,
            "idNum" => $request->idNum,
            "personPhoNum" => $request->personPhoNum,
            "branchPhoNum" => $request->branchPhoNum,
            "salary" => $request->salary,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'added_by' => $addedById,
            'status' => 'active',
        ]);
             if ($request->hasFile('cv')) {
                $cvPath = $request->file('cv')->store(Worker::storageFolder);
                $Worker->cv = $cvPath;
            }
             $Worker->save();

           return response()->json([
            'data' =>new WorkerResource($Worker),
            'message' => "Worker Created Successfully."
        ]);
        }

public function edit(string $id)
    {
        $this->authorize('manage_users');
        $Worker = Worker::find($id);
            if (!$Worker) {
                return response()->json([
                    'message' => "Worker not found."
                ], 404);
            }

            return response()->json([
                'data' => new WorkerResource($Worker),
                'message' => "Edit Worker By ID Successfully."
            ]);
        }

public function update(WorkerRequest $request, string $id)
    {
        $this->authorize('manage_users');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
           $Worker =Worker::findOrFail($id);
             $oldData = $Worker->toArray();

           if (!$Worker) {
            return response()->json([
                'message' => "Worker not found."
            ], 404);
        }
           $Worker->update([
            'title_id'=> $request ->title_id,
            'store_id'=> $request ->store_id,
            "name" => $request->name,
            "idNum" => $request->idNum,
            "personPhoNum" => $request->personPhoNum,
            "branchPhoNum" => $request->branchPhoNum,
            "salary" => $request->salary,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status'=> $request-> status ?? 'active',
            'added_by'
            ]);

                        if ($request->hasFile('cv')) {
                if ($Worker->cv) {
                    Storage::disk('public')->delete( $Worker->cv);
                }
                $cvPath = $request->file('cv')->store('Workers', 'public');
                 $Worker->cv = $cvPath;
            }
            $Worker->save();

        $changedData = $this->getChangedData($oldData, $Worker->toArray());
        $Worker->changed_data = $changedData;

    $Worker->changed_data = $changedData;

           $Worker->save();
           return response()->json([
            'data' =>new WorkerResource($Worker),
            'message' => " Update Worker By Id Successfully."
        ]);
    }

public function active(string $id)
  {
    $this->authorize('manage_users');
    $Worker =Worker::findOrFail($id);

      if (!$Worker) {
       return response()->json([
           'message' => "Worker not found."
       ]);
   }
       $oldData = $Worker->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Worker->status = 'active';
    $Worker->creationDate = $creationDate;
    $Worker->creationDateHijri = $hijriDate;
    $Worker->admin_id = auth()->id();
    $Worker->save();

    $changedData = $this->getChangedData($oldData, $Worker->toArray());
    $Worker->changed_data = $changedData;
    $Worker->save();

      return response()->json([
          'data' => new WorkerResource($Worker),
          'message' => 'Worker has been active.'
      ]);
  }

     public function notActive(string $id)
  {
      $this->authorize('manage_users');
      $Worker =Worker::findOrFail($id);

      if (!$Worker) {
       return response()->json([
           'message' => "Worker not found."
       ]);
   }

          $oldData = $Worker->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Worker->status = 'notActive';
    $Worker->creationDate = $creationDate;
    $Worker->creationDateHijri = $hijriDate;
    $Worker->admin_id = auth()->id();
    $Worker->save();

    $changedData = $this->getChangedData($oldData, $Worker->toArray());
    $Worker->changed_data = $changedData;
    $Worker->save();


      return response()->json([
          'data' => new WorkerResource($Worker),
          'message' => 'Worker has been notActive.'
      ]);
  }
}
