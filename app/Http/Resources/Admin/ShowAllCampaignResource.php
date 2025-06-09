<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllCampaignResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'office_id' => $this->office?->id,
            'office_name' => $this->office?->name,
            'name' => $this -> name,
            'groupsCount' => $this-> groupsCount,
            'workersCount' => $this-> workersCount,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
