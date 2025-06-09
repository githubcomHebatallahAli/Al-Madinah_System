<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllCompanyResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'service_id' => $this->service?->id,
            'service_name' => $this->service?->name,
            'type' => $this -> type,
            'name' => $this -> name,
            'communication' => $this-> communication ,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
