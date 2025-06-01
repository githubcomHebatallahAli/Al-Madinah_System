<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightResource extends JsonResource
{
     use AddedByResourceTrait;

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'company_id' => $this->company?->id,
            'company_name' => $this->company?->name,
            'supplier_id' => $this->supplier?->id,
            'supplier_name' => $this->supplier?->name,
            'direction' => $this->direction ,
            'DateTimeTrip'=> $this->DateTimeTrip,
            'DateTimeTripHijri'=> $this->DateTimeTripHijri,
            'description' => $this->description,
            'quantity' => $this-> quantity,
            'sellingPrice' => $this-> sellingPrice,
            'purchesPrice' => $this-> purchesPrice,
            'profit' => $this-> profit,
            'status' => $this-> status,
            'creationDateHijri'=> $this->creationDateHijri,
            'creationDate'=> $this-> creationDate,
            'changed_data' => $this -> changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),

        ];
    }
}
