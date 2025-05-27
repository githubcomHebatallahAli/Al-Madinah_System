<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this -> id,
            'branch_id' => $this->branch?->id,
            'branch_name' => $this->branch?->name,
            'name'=> $this ->name,
            'pilgrimsCount'=> $this ->pilgrimsCount,
            'status'=> $this ->status,
            'description'=> $this ->description,
            'creationDate'=> $this ->creationDate,
            'creationDateHijri'=> $this ->creationDateHijri,
            'changed_data'=> $this ->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
