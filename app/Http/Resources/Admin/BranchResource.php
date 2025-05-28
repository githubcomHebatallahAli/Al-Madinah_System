<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    use AddedByResourceTrait;

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'city_id' => $this->city?->id,
            'city_name' => $this->city?->name,
            'name' => $this -> name,
            'address' => $this -> address,
            'officesCount' => $this -> officesCount,
            'tripsCount' => $this-> tripsCount,
            'storesCount' => $this-> storesCount,
            'workersCount' => $this-> workersCount,
            'titlesCount' => $this-> titlesCount,
            'servicesCount' => $this-> servicesCount,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
