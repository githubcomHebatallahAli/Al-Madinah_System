<?php

namespace App\Http\Controllers\Admin;

use App\Models\Worker;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Admin\WorkerRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Http\Resources\Admin\WorkerResource;

class WorkerController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;

public function showAll()
    {
        $this->authorize('manage_system');
        $Workers = Worker::orderBy('created_at', 'desc')
        ->get();
          foreach ($Workers as $worker) {
        $this->loadCreatorRelations($worker);
    }

         return response()->json([
             'data' =>  WorkerResource::collection($Workers),
             'message' => "Show All Workeres."
        ]);
    }

public function create(WorkerRequest $request)
    {
        $this->authorize('manage_system');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

$addedById = $this->getAddedByIdOrFail();
$addedByType = $this->getAddedByType();

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
            'added_by_type' => $addedByType,
            'status' => 'active',
            'dashboardAccess'=>'ok'
        ]);
             if ($request->hasFile('cv')) {
                $cvPath = $request->file('cv')->store(Worker::storageFolder);
                $Worker->cv = $cvPath;
            }
             $Worker->save();
             $this->loadCreatorRelations($Worker);

           return response()->json([
            'data' =>new WorkerResource($Worker),
            'message' => "Worker Created Successfully."
        ]);
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
            $this->loadCreatorRelations($Worker);

            return response()->json([
                'data' => new WorkerResource($Worker),
                'message' => "Edit Worker By ID Successfully."
            ]);
        }

public function update(WorkerRequest $request, string $id)
    {
        $this->authorize('manage_system');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $addedById = $this->getAddedByIdOrFail();
        $addedByType = $this->getAddedByType();
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
            'dashboardAccess'=> $request-> dashboardAccess ?? 'notOk',
            'added_by' => $addedById,
            'added_by_type' => $addedByType,
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
           $Worker->save();
           $this->loadCreatorRelations($Worker);
           return response()->json([
            'data' =>new WorkerResource($Worker),
            'message' => " Update Worker By Id Successfully."
        ]);
    }

public function active(string $id)
  {
      $this->authorize('manage_system');
    $Worker =Worker::findOrFail($id);

      if (!$Worker) {
       return response()->json([
           'message' => "Worker not found."
       ]);
   }
       $oldData = $Worker->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();
    $addedById = $this->getAddedByIdOrFail();
    $addedByType = $this->getAddedByType();

    $Worker->status = 'active';
    $Worker->creationDate = $creationDate;
    $Worker->creationDateHijri = $hijriDate;
    $Worker->added_by = $addedById;
    $Worker->added_by_type = $addedByType;
    $Worker->save();

    $changedData = $this->getChangedData($oldData, $Worker->toArray());
    $Worker->changed_data = $changedData;
    $Worker->save();
    $this->loadCreatorRelations($Worker);

      return response()->json([
          'data' => new WorkerResource($Worker),
          'message' => 'Worker has been active.'
      ]);
  }

     public function notActive(string $id)
  {
    $this->authorize('manage_system');
      $Worker =Worker::findOrFail($id);

      if (!$Worker) {
       return response()->json([
           'message' => "Worker not found."
       ]);
   }

          $oldData = $Worker->toArray();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();
    $addedById = $this->getAddedByIdOrFail();
    $addedByType = $this->getAddedByType();

    $Worker->status = 'notActive';
    $Worker->creationDate = $creationDate;
    $Worker->creationDateHijri = $hijriDate;
    $Worker->added_by = $addedById;
    $Worker->added_by_type = $addedByType;
    $Worker->save();

    $changedData = $this->getChangedData($oldData, $Worker->toArray());
    $Worker->changed_data = $changedData;
    $Worker->save();
    $this->loadCreatorRelations($Worker);


      return response()->json([
          'data' => new WorkerResource($Worker),
          'message' => 'Worker has been notActive.'
      ]);
  }

     public function notOk(string $id)
  {
    $this->authorize('manage_system');
      $Worker =Worker::findOrFail($id);

      if (!$Worker) {
       return response()->json([
           'message' => "Worker not found."
       ]);
   }

    $oldData = $Worker->toArray();
    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();
    $addedById = $this->getAddedByIdOrFail();
    $addedByType = $this->getAddedByType();

    $Worker->dashboardAccess = 'notOk';
    $Worker->creationDate = $creationDate;
    $Worker->creationDateHijri = $hijriDate;
    $Worker->added_by = $addedById;
    $Worker->added_by_type = $addedByType;
    $Worker->save();

    $changedData = $this->getChangedData($oldData, $Worker->toArray());
    $Worker->changed_data = $changedData;
    $Worker->save();
    $this->loadCreatorRelations($Worker);
      return response()->json([
          'data' => new WorkerResource($Worker),
          'message' => 'Worker has been notOk.'
      ]);
  }

     public function ok(string $id)
  {
    $this->authorize('manage_system');
      $Worker =Worker::findOrFail($id);

      if (!$Worker) {
       return response()->json([
           'message' => "Worker not found."
       ]);
   }

    $oldData = $Worker->toArray();
    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();
    $addedById = $this->getAddedByIdOrFail();
    $addedByType = $this->getAddedByType();

    $Worker->dashboardAccess = 'ok';
    $Worker->creationDate = $creationDate;
    $Worker->creationDateHijri = $hijriDate;
    $Worker->added_by = $addedById;
    $Worker->added_by_type = $addedByType;
    $Worker->save();

    $changedData = $this->getChangedData($oldData, $Worker->toArray());
    $Worker->changed_data = $changedData;
    $Worker->save();
    $this->loadCreatorRelations($Worker);

      return response()->json([
          'data' => new WorkerResource($Worker),
          'message' => 'Worker has been Ok.'
      ]);
  }

}
