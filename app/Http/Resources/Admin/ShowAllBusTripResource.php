<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllBusTripResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'bus_id' => $this->bus?->id,
            'bus_number' => $this->bus?->busNum,
            'trip_id' => $this->trip?->id,
            'trip_name' => $this->trip?->name,
            'bus_driver_id' => $this->busDriver?->id,
            'bus_driver_name' => $this->busDriver?->name,
            'travelDateHijri' => $this->travelDateHijri,
            'creationDateHijri' => $this->creationDateHijri,
            'status' => $this->status,
            'creationDate' => $this->creationDate,
        ];
    }
}
