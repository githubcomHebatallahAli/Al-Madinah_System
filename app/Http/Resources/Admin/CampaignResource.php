<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
   use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return[
            "id" => $this -> id,
            'office_id' => $this->office?->id,
            'office_name' => $this->office?->name,
            'name' => $this -> name,
            'groupsCount' => $this-> groupsCount,
            'workersCount' => $this-> workersCount,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
