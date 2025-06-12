<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllBusResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'service_id' => $this->service?->id,
            'service_name' => $this->service?->name,
            'company_id' => $this->company?->id,
            'company_name' => $this->company?->name,
            'seatNum' => $this-> seatNum,
            'seatPrice' => $this-> seatPrice,
            'busNum' => $this -> busNum,
            'busModel' => $this-> busModel,
            'plateNum' => $this-> plateNum ,
            'quantity' => $this-> quantity,
            'status' => $this-> status,
            'creationDate'=> $this-> creationDate,
        ];
    }
}
