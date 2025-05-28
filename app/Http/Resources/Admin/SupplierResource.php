<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    use AddedByResourceTrait;

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'supply_id' => $this->supply?->id,
            'supply_name' => $this->supply?->name,
            'name' => $this -> name,
            'communication' => $this-> communication ,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
