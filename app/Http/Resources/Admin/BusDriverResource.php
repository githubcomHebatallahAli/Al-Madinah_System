<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class BusDriverResource extends JsonResource
{
    use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'bus_id' => $this->bus?->id,
            'name' => $this -> name,
            'phoNum' => $this -> phoNum,
            'idNum' => $this -> idNum,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
