<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerWebResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'branch_id'   => $this->title?->branch?->id,
            'branch_name' => $this->title?->branch?->name,
            'title_id' => $this->title?->id,
            'title_name' => $this->title?->name,
            'name' => $this -> name,
            'personPhoNum' => $this -> personPhoNum ,
            'status' => $this-> status,
            'dashboardAccess' => $this-> dashboardAccess,
            'creationDate'=> $this-> creationDate,


        ];
    }
}
