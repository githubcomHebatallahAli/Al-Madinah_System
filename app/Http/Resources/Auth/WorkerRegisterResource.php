<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use App\Http\Resources\Admin\RoleResource;
use App\Http\Resources\Admin\WorkerResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerRegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'worker'=> new WorkerResource($this->worker),
            'email'=>$this->email,
            'role' => new RoleResource($this->role),
        ];
    }
}
