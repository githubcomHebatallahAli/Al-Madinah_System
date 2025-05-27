<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
     use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this -> id,
            'campaign_id' => $this->campaign?->id,
            'campaign_name' => $this->campaign?->name,
            'groupNum'=> $this ->groupNum,
            'numBus'=> $this ->numBus,
            'status'=> $this ->status,
            'creationDate'=> $this ->creationDate,
            'creationDateHijri'=> $this ->creationDateHijri,
            'changed_data'=> $this ->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
