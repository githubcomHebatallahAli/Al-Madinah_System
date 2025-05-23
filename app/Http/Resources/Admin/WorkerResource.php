<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
   use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'title_id' => $this->title?->id,
            'title_name' => $this->title?->name,
            'store_id' => $this->store?->id,
            'store_name' => $this->store?->name,
            'name' => $this -> name,
            'idNum' => $this -> idNum ,
            'personPhoNum' => $this -> personPhoNum ,
            'branchPhoNum' => $this -> branchPhoNum ,
            'salary' => $this -> salary ,
            'cv' => $this -> cv ,
            'workersCount' => $this-> workersCount,
            'status' => $this-> status,
            'dashboardAccess' => $this-> dashboardAccess,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
