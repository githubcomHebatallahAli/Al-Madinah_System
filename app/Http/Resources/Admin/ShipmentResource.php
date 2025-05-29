<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
        'id'=> $this -> id,
        'supplier_id' => $this->supplier?->id,
        'supplier_name' => $this->supplier?->name,
        'shipmentItemsCount'=> $this ->shipmentItemsCount,
        'totalPrice'=> $this ->totalPrice,
        'description'=> $this ->description,
        'status' => $this-> status,
        'creationDate'=> $this ->creationDate,
        'creationDateHijri'=> $this ->creationDateHijri,
        'changed_data'=> $this ->changed_data,
        'added_by' => $this->addedByAttribute(),
        'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
