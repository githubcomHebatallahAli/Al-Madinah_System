<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Traits\ManagesModelsTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Resources\Admin\RoleResource;

class RoleController extends Controller
{
    public function showAll()
    {
        $this->authorize('manage_users');

        $Roles = Role::get();
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

           $Role =Role::create ([
                "name" => $request->name,
                'creationDate' => $gregorianDate,
                'creationDateHijri' => $hijriDate,
                'status' => 'active',
                'guardName'=>'worker'
            ]);
           $Role->save();
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
       $Role =Role::findOrFail($id);

       if (!$Role) {
        return response()->json([
            'message' => "Role not found."
        ], 404);
    }
       $Role->update([
        "name" => $request->name,
        'creationDate' => $gregorianDate,
        'creationDateHijri' => $hijriDate,
        'status' => 'active',
        'guardName'=>'worker'
        ]);

       $Role->save();
       return response()->json([
        'data' =>new RoleResource($Role),
        'message' => " Update Role By Id Successfully."
    ]);

  }



}
