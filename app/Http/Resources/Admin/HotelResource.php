<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelResource extends JsonResource
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
            'name'=> $this-> name,
            'place'=> $this-> place,
            'address'=> $this-> address,
            'communication'=> $this-> communication,
            'description' => $this->description,
            'quantity' => $this-> quantity,
            'sellingPrice' => $this-> sellingPrice,
            'purchesPrice' => $this-> purchesPrice,
            'profit' => $this-> profit,
            'roomType'=> $this-> roomType,
            'bedPrice' => $this->bedPrice ,
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
