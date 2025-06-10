<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllPilgrimsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'name' => $this -> name,
            'phoNum' => $this -> phoNum,
            'nationality' => $this -> nationality,
            'gender'=>$this->gender,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
