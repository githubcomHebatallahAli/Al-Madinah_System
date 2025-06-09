<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllHotelResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'service_id' => $this->service?->id,
            'service_name' => $this->service?->name,
            'company_id' => $this->company?->id,
            'company_name' => $this->company?->name,
            'name'=> $this-> name,
            'place'=> $this-> place,
            'communication'=> $this-> communication,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
