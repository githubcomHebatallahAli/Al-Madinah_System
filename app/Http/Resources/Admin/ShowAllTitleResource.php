<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllTitleResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'branch_id' => $this->branch?->id,
            'branch_name' => $this->branch?->name,
            'name' => $this -> name,
            'workersCount' => $this-> workersCount,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
