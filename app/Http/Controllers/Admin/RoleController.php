<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Traits\HijriDateTrait;
use App\Traits\HandleAddedByTrait;
use App\Traits\TracksChangesTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Traits\LoadsCreatorRelationsTrait;
use App\Traits\LoadsUpdaterRelationsTrait;

class RoleController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    public function showAll()
    {
        $this->authorize('manage_users');

        $Roles = Role::get();
                  foreach ($Roles as $Role) {
        $this->loadCreatorRelations($Role);
        $this->loadUpdaterRelations($Role);
    }
        return response()->json([
            'data' => RoleResource::collection($Roles),
            'message' => "Show All Roles Successfully."
        ]);
    }


    public function create(RoleRequest $request)
    {
        $this->authorize('manage_users');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
        $addedById = $this->getAddedByIdOrFail();
        $addedByType = $this->getAddedByType();

           $Role =Role::create ([
                "name" => $request->name,
                'creationDate' => $gregorianDate,
                'creationDateHijri' => $hijriDate,
                'status' => 'active',
                'guardName'=>'worker',
                'added_by' => $addedById,
                'added_by_type' => $addedByType,
                'updated_by' => $addedById,
                'updated_by_type' => $addedByType
            ]);
           $Role->save();
        $this->loadCreatorRelations($Role);
        $this->loadUpdaterRelations($Role);
           return response()->json([
            'data' =>new RoleResource($Role),
            'message' => "Role Created Successfully."
        ]);
        }


    public function edit(string $id)
    {
        $this->authorize('manage_users');
        $Role = Role::find($id);

        if (!$Role) {
            return response()->json([
                'message' => "Role not found."
            ], 404);
        }
        $this->loadCreatorRelations($Role);
        $this->loadUpdaterRelations($Role);

        return response()->json([
            'data' =>new RoleResource($Role),
            'message' => "Edit Role By ID Successfully."
        ]);
    }



    public function update(RoleRequest $request, string $id)
    {
        $this->authorize('manage_users');
        $hijriDate = $this->getHijriDate();
        $gregorianDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
       $Role =Role::find($id);


       if (!$Role) {
        return response()->json([
            'message' => "Role not found."
        ], 404);
    }
      $oldData = $Role->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();
       $Role->update([
        "name" => $request->name,
        'creationDate' => $gregorianDate,
        'creationDateHijri' => $hijriDate,
        'status'=> $request-> status ?? 'active',
        'guardName'=>$request-> guardName ??'worker',
        'updated_by' => $updatedById,
        'updated_by_type' => $updatedByType
        ]);
         $changedData = $this->getChangedData($oldData, $Role->toArray());
        $Role->changed_data = $changedData;

       $Role->save();
        $this->loadCreatorRelations($Role);
        $this->loadUpdaterRelations($Role);
       return response()->json([
        'data' =>new RoleResource($Role),
        'message' => " Update Role By Id Successfully."
    ]);
}

         public function active(string $id)
  {
      $this->authorize('manage_users');
      $Role =Role::find($id);

      if (!$Role) {
       return response()->json([
           'message' => "Role not found."
       ]);
   }
       $oldData = $Role->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Role->status = 'active';
    $Role->creationDate = $creationDate;
    $Role->creationDateHijri = $hijriDate;
    $Role->updated_by = $updatedById;
    $Role->updated_by_type = $updatedByType;
    $Role->save();

    $changedData = $this->getChangedData($oldData, $Role->toArray());
    $Role->changed_data = $changedData;
    $Role->save();
    $this->loadCreatorRelations($Role);
    $this->loadUpdaterRelations($Role);

      return response()->json([
          'data' => new RoleResource($Role),
          'message' => 'Role has been active.'
      ]);
  }

     public function notActive(string $id)
  {
      $this->authorize('manage_users');
      $Role =Role::find($id);

      if (!$Role) {
       return response()->json([
           'message' => "Role not found."
       ]);
   }

    $oldData = $Role->toArray();
    $updatedById = $this->getUpdatedByIdOrFail();
    $updatedByType = $this->getUpdatedByType();
    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Role->status = 'notActive';
    $Role->creationDate = $creationDate;
    $Role->creationDateHijri = $hijriDate;
    $Role->updated_by = $updatedById;
    $Role->updated_by_type = $updatedByType;
    $Role->save();

    $changedData = $this->getChangedData($oldData, $Role->toArray());
    $Role->changed_data = $changedData;
    $Role->save();
    $this->loadCreatorRelations($Role);
    $this->loadUpdaterRelations($Role);
      return response()->json([
          'data' => new RoleResource($Role),
          'message' => 'Role has been notActive.'
      ]);
  }


         public function admin(string $id)
  {
      $this->authorize('manage_users');
      $Role =Role::find($id);

      if (!$Role) {
       return response()->json([
           'message' => "Role not found."
       ]);
   }
       $oldData = $Role->toArray();
        $updatedById = $this->getUpdatedByIdOrFail();
        $updatedByType = $this->getUpdatedByType();

    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Role->guardName = 'admin';
    $Role->creationDate = $creationDate;
    $Role->creationDateHijri = $hijriDate;
    $Role->updated_by = $updatedById;
    $Role->updated_by_type = $updatedByType;
    $Role->save();

    $changedData = $this->getChangedData($oldData, $Role->toArray());
    $Role->changed_data = $changedData;
    $Role->save();
    $this->loadCreatorRelations($Role);
    $this->loadUpdaterRelations($Role);

      return response()->json([
          'data' => new RoleResource($Role),
          'message' => 'Role has been admin.'
      ]);
  }

     public function worker(string $id)
  {
      $this->authorize('manage_users');
      $Role =Role::find($id);

      if (!$Role) {
       return response()->json([
           'message' => "Role not found."
       ]);
   }

    $oldData = $Role->toArray();
    $updatedById = $this->getUpdatedByIdOrFail();
    $updatedByType = $this->getUpdatedByType();
    $creationDate = now()->timezone('Asia/Riyadh')->format('Y-m-d H:i:s');
    $hijriDate = $this->getHijriDate();

    $Role->guardName = 'worker';
    $Role->creationDate = $creationDate;
    $Role->creationDateHijri = $hijriDate;
    $Role->updated_by = $updatedById;
    $Role->updated_by_type = $updatedByType;
    $Role->save();

    $changedData = $this->getChangedData($oldData, $Role->toArray());
    $Role->changed_data = $changedData;
    $Role->save();
    $this->loadCreatorRelations($Role);
    $this->loadUpdaterRelations($Role);


      return response()->json([
          'data' => new RoleResource($Role),
          'message' => 'Role has been worker.'
      ]);
  }

  }




