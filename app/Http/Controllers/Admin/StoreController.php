<?php

namespace App\Http\Controllers\Admin;

use App\Models\Store;
use Illuminate\Http\Request;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRequest;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;
use App\Http\Resources\Admin\StoreResource;

class StoreController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;

        public function showAll()
    {
        $this->authorize('manage_system');
        $Stores = Store::orderBy('created_at', 'desc')
        ->get();
           foreach ($Stores as $Store) {
        $this->loadCreatorRelations($Store);
        $this->loadUpdaterRelations($Store);
    }

        return response()->json([
            'data' =>  StoreResource::collection($Stores),
            'message' => "Show All Stores."
        ]);
    }


    public function create(StoreRequest $request)
    {
       $this->authorize('manage_system');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $addedById = $this->getAddedByIdOrFail();
        $addedByType = $this->getAddedByType();

        $Store = Store::create([
            'branch_id'=> $request ->branch_id,
            "name" => $request->name,
            "address" => $request-> address,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status' => 'active',
            // 'added_by'  => auth('admin')->id(),
            // 'added_by_type' => 'App\Models\Admin',
            'added_by' => $addedById,
            'added_by_type' => $addedByType,
            'updated_by' => $addedById,
            'updated_by_type' => $addedByType
        ]);
        $this->loadCreatorRelations($Store);
        $this->loadUpdaterRelations($Store);
           return response()->json([
            'data' =>new StoreResource($Store),
            'message' => "Store Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
        $this->authorize('manage_system');
        $Store = Store::with(['workers'])
        ->find($id);
        if (!$Store) {
            return response()->json([
                'message' => "Store not found."
            ], 404);
            }
        $this->loadCreatorRelations($Store);
        $this->loadUpdaterRelations($Store);

            return response()->json([
                'data' => new StoreResource($Store),
                'message' => "Edit Store By ID Successfully."
            ]);
        }

        public function update(StoreRequest $request, string $id)
        {
        $this->authorize('manage_system');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');

        $Store =Store::find($id);
           if (!$Store) {
            return response()->json([
                'message' => "Store not found."
            ], 404);
        }
         $oldData = $Store->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();
           $Store->update([
           'branch_id'=> $request ->branch_id,
            "name" => $request->name,
            "address" => $request-> address,
            'creationDate' => $gregorianDate,
            'creationDateHijri' => $hijriDate,
            'status'=> $request-> status ?? 'active',
            // 'added_by' => auth('admin')->id(),
            // 'added_by_type' => 'App\Models\Admin',
            'updated_by' => $updatedById,
            'updated_by_type' => $updatedByType
            ]);



        $changedData = $this->getChangedData($oldData, $Store->toArray());
        $Store->changed_data = $changedData;

           $Store->save();
            $this->loadCreatorRelations($Store);
            $this->loadUpdaterRelations($Store);
           return response()->json([
            'data' =>new StoreResource($Store),
            'message' => " Update Store By Id Successfully."
        ]);
    }

     public function active(string $id)
  {
      $this->authorize('manage_users');

      $Store =Store::find($id);

      if (!$Store) {
       return response()->json([
           'message' => "Store not found."
       ]);
   }
       $oldData = $Store->toArray();
       $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Store->status = 'active';
    $Store->creationDate = $creationDate;
    $Store->creationDateHijri = $hijriDate;
    $Store->updated_by = $updatedById;
    $Store->updated_by_type = $updatedByType;
    $Store->save();

    $changedData = $this->getChangedData($oldData, $Store->toArray());
    $Store->changed_data = $changedData;
    $Store->save();
    $this->loadCreatorRelations($Store);
    $this->loadUpdaterRelations($Store);

      return response()->json([
          'data' => new StoreResource($Store),
          'message' => 'Store has been active.'
      ]);
  }

     public function notActive(string $id)
  {
      $this->authorize('manage_users');

      $Store =Store::find($id);

      if (!$Store) {
       return response()->json([
           'message' => "Store not found."
       ]);
   }

          $oldData = $Store->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Store->status = 'notActive';
    $Store->creationDate = $creationDate;
    $Store->creationDateHijri = $hijriDate;
    $Store->updated_by = $updatedById;
    $Store->updated_by_type = $updatedByType;

    $Store->save();

    $changedData = $this->getChangedData($oldData, $Store->toArray());
    $Store->changed_data = $changedData;
    $Store->save();
    $this->loadCreatorRelations($Store);
    $this->loadUpdaterRelations($Store);
      return response()->json([
          'data' => new StoreResource($Store),
          'message' => 'Store has been notActive.'
      ]);
  }
}
