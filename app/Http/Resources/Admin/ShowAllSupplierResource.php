<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllSupplierResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return[
            "id" => $this -> id,
            'company_id' => $this->company?->id,
            'company_name' => $this->company?->name,
            'name' => $this -> name,
            'communication' => $this-> communication ,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
