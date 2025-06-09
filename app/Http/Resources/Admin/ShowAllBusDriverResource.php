<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllBusDriverResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'bus_id' => $this->bus?->id,
            'name' => $this -> name,
            'phoNum' => $this -> phoNum,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
