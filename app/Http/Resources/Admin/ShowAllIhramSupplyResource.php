<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllIhramSupplyResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'company_id' => $this->company?->id,
            'company_name' => $this->company?->name,
            'ihram_item_id' => $this->ihramItem?->id,
            'ihram_item_name' => $this->ihramItem?->name,
            'store_id' => $this->store?->id,
            'store_name' => $this->store?->name,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
