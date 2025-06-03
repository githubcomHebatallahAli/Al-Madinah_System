<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentItemResource extends JsonResource
{
    // use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
        'id'=> $this -> id,
        'item_id'=> $this-> item_id,
        'item_type' => class_basename($this->item_type),
        // 'item_name' => $this->item?->name ?? $this->item?->model ?? '—',
        'quantity'=> $this-> quantity,
        'unitPrice'=> $this-> unitPrice,
        'totalPrice'=> $this ->totalPrice,
        'rentalStart'=> $this -> rentalStart,
        'rentalEnd'=> $this -> rentalEnd,
        'rentalStartHijri'=> $this ->rentalStartHijri,
        'rentalEndHijri'=> $this ->rentalEndHijri,
        'creationDate'=> $this ->creationDate,
        'creationDateHijri'=> $this ->creationDateHijri,
        // 'changed_data'=> $this ->changed_data,
        // 'added_by' => $this->addedByAttribute(),
        // 'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
