<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use App\Http\Resources\Admin\RoleResource;
use App\Http\Resources\Admin\WorkerResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRegisterResource extends JsonResource
{
  use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'email'=>$this->email,
            'role' => new RoleResource($this->role),
            'status' => $this -> status,
            'creationDate' => $this -> creationDate,
            'creationDateHijri'=> $this -> creationDateHijri,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),

        ];
    }
}
