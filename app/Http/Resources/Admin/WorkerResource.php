<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'title_id' => $this->title?->id,
            'title_name' => $this->title?->name,
            'store_id' => $this->store?->id,
            'store_name' => $this->store?->name,
            'name' => $this -> name,
            'address' => $this -> address,
            'idNum' => $this -> idNum ,
            'personPhoNum' => $this -> personPhoNum ,
            'branchPhoNum' => $this -> branchPhoNum ,
            'salary' => $this -> salary ,
            'cv' => $this -> cv ,
            'workersCount' => $this-> workersCount,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'admin_id' => $this->admin?->id,
            'admin_name' => $this->admin?->name,
            'changed_data' => $this -> changed_data
        ];
    }
}
