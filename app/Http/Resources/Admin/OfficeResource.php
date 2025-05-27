<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
{
    use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
            return [
            'id'=> $this -> id,
            'branch_id' => $this->branch?->id,
            'branch_name' => $this->branch?->name,
            'name'=> $this ->name,
            'address'=> $this ->address,
            'phoNum1'=> $this ->phoNum1,
            'phoNum2'=> $this ->phoNum2,
            'campaignsCount'=> $this ->campaignsCount,
            'status'=> $this ->status,
            'creationDate'=> $this ->creationDate,
            'creationDateHijri'=> $this ->creationDateHijri,
            'changed_data'=> $this ->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];


    }
}
