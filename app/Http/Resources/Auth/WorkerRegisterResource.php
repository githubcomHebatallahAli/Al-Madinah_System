<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use App\Http\Resources\Admin\RoleResource;
use App\Http\Resources\Admin\WorkerResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerRegisterResource extends JsonResource
{
   use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'worker'=> new WorkerResource($this->worker),
            'email'=>$this->email,
            'role' => new RoleResource($this->role),
            'creationDate' => $this -> creationDate,
            'creationDateHijri'=> $this -> creationDateHijri,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),

        ];
    }
}
