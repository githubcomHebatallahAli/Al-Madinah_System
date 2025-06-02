<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllWorkerLoginResource extends JsonResource
{
     use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'title_id' => $this->worker?->title?->id,
            'title_name' => $this->worker?->title?->name,
            'store_id' => $this->worker?->store?->id,
            'store_name' => $this->worker?->store?->name,
            'worker_id' => $this->worker?->id,
            'worker_name' => $this->worker?->name,
            'status' => $this->worker?->status,
            'dashboardAccess' => $this->worker?->dashboardAccess,
            'email'=>$this->email,
            'role_id' => $this->role?->id,
            'role_name' => $this->role?->name,
            'creationDate' => $this -> creationDate,
            'creationDateHijri'=> $this -> creationDateHijri,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
