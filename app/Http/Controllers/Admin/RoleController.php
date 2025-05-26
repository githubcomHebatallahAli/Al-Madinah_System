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
use App\Traits\HandlesControllerCrudsTrait;

class RoleController extends Controller
{
    use HijriDateTrait;
    use TracksChangesTrait;
    use HandleAddedByTrait;
    use LoadsCreatorRelationsTrait;
    use LoadsUpdaterRelationsTrait;
    use HandlesControllerCrudsTrait;

    public function showAll()
    {
        $this->authorize('manage_users');

        $Roles = Role::get();
       $this->loadRelationsForCollection($Roles);
        return response()->json([
            'data' => RoleResource::collection($Roles),
            'message' => "Show All Roles Successfully."
        ]);
    }


    public function create(RoleRequest $request)
    {
        $this->authorize('manage_users');
           $data = $request->only([
         'name'
    ]);

    $data = array_merge($data, $this->prepareCreationMetaData(), [
        'guardName' => 'worker',
    ]);

    $Role = Role::create($data);
      return $this->respondWithResource($Role, "Role created successfully.");
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
       return $this->respondWithResource($Role, "Role retrieved for editing.");
    }



    public function update(RoleRequest $request, string $id)
    {
        $this->authorize('manage_users');

       $Role =Role::find($id);
       if (!$Role) {
        return response()->json([
            'message' => "Role not found."
        ], 404);
    }
      $oldData = $Role->toArray();

    $updateData = $request->only(['name','guardName','status']);

    $updateData = array_merge(
        $updateData,
        $this->prepareUpdateMeta($request, $Role->status)
    );


    $hasChanges = false;
    foreach ($updateData as $key => $value) {
        if ($Role->$key != $value) {
            $hasChanges = true;
            break;
        }
    }

    if (!$hasChanges) {
        $this->loadCommonRelations($Role);
        return $this->respondWithResource($Role, "لا يوجد تغييرات فعلية");
    }


    $updateData['guardName'] = $updateData['guardName'] ?? 'worker';

        $Role->update($updateData);

    $changedData = $Role->getChangedData($oldData, $Role->fresh()->toArray());
    $Role->changed_data = $changedData;
    $Role->save();
return $this->respondWithResource($Role, "Role updated successfully.");
}


    public function active(string $id)
    {
        $this->authorize('manage_system');
        $Role = Role::findOrFail($id);

        return $this->changeStatusSimple($Role, 'active');
    }

    public function notActive(string $id)
    {
        $this->authorize('manage_system');
        $Role = Role::findOrFail($id);

        return $this->changeStatusSimple($Role, 'notActive');
    }

    protected function getResourceClass(): string
    {
        return RoleResource::class;
    }

    public function admin(string $id)
{
    $this->authorize('manage_system');

    $Role = Role::find($id);
    if (!$Role) {
        return response()->json(['message' => "Role not found."], 404);
    }

    return $this->changeStatusSimple($Role, 'admin');
}


public function worker(string $id)
{
    $this->authorize('manage_system');

    $Role = Role::find($id);
    if (!$Role) {
        return response()->json(['message' => "Role not found."], 404);
    }

    return $this->changeStatusSimple($Role, 'worker');
}


  }




