<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllBranchResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'city_id' => $this->city?->id,
            'city_name' => $this->city?->name,
            'name' => $this -> name,
            'address' => $this -> address,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
