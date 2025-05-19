<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class TitleResource extends JsonResource
{
  use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
             "id" => $this -> id,
            'branch_id' => $this->branch?->id,
            'branch_name' => $this->branch?->name,
            'name' => $this -> name,
            'workersCount' => $this-> workersCount,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'admin_id' => $this->admin?->id,
            'admin_name' => $this->admin?->name,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
        ];
    }
}
