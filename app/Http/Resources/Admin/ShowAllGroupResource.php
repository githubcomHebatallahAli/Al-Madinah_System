<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllGroupResource extends JsonResource
{

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
        ];
    }
}
