<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllCityResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'name' => $this -> name,
            'branchesCount' => $this-> branchesCount,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
