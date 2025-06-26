<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class BusResource extends JsonResource
{
    use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'service_id' => $this->service?->id,
            'service_name' => $this->service?->name,
            'company_id' => $this->company?->id,
            'company_name' => $this->company?->name,
            'busNum' => $this -> busNum,
            'busModel' => $this-> busModel,
            'plateNum' => $this-> plateNum ,
            'seatNum' => $this-> seatNum,
            'seatPrice' => $this-> seatPrice,
            'seatMap' => $this-> seatMap,
            'quantity' => $this-> quantity,
            'sellingPrice' => $this-> sellingPrice,
            'purchesPrice' => $this-> purchesPrice,
            'profit' => $this-> profit,
            'rentalStart' => $this->rentalStart,
            'rentalStartHijri'=> $this->rentalStartHijri,
            'rentalEnd'=> $this->rentalEnd,
            'rentalEndHijri'=> $this->rentalEndHijri,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),

        ];
    }
}
