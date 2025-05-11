<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
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
            'name' => $this -> name,
            'branchesCount' => $this-> branchesCount,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'admin' => $this->admin?->name,
            'changed_data' => $this -> changed_data

        ] ;
    }
}
