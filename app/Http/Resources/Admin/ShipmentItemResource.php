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
        'quantity'=> $this-> quantity,
        'unitPrice'=> $this-> unitPrice,
        'totalPrice'=> $this ->totalPrice,
        'rentalStart'=> $this -> rentalStart,
        'rentalEnd'=> $this -> rentalEnd,
        'rentalStartHijri'=> $this ->rentalStartHijri,
        'rentalEndHijri'=> $this ->rentalEndHijri,
        'busNum' => $this -> busNum,
        'busModel' => $this-> busModel,
        'plateNum' => $this-> plateNum ,
        'busSeatNum' => $this-> busSeatNum,
        'roomType'=> $this -> roomType,
        'creationDate'=> $this ->creationDate,
        'creationDateHijri'=> $this ->creationDateHijri,
        'class' => $this->class ,
        'seatNum' => $this-> seatNum,
        'DateTimeTrip'=> $this->DateTimeTrip,
        'DateTimeTripHijri'=> $this->DateTimeTripHijri,

        ];
    }
}
