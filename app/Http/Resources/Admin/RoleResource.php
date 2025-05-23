<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
   use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'name' => $this -> name,
            'guardName' => $this -> guardName,
            'status'=> $this -> status,
            'changed_data' => $this -> changed_data,
            'creationDate'=> $this -> creationDate,
            'creationDateHijri'=> $this -> creationDateHijri,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
