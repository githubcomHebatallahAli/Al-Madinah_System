<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'city_id' => $this->city?->id,
            'city_name' => $this->city?->name,
            'name' => $this -> name,
            'address' => $this -> address,
            'tripsCount' => $this-> tripsCount,
            'storesCount' => $this-> storesCount,
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
