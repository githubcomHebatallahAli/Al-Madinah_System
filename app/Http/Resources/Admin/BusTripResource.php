<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class BusTripResource extends JsonResource
{
   use AddedByResourceTrait;
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
            'travelDate' => $this->travelDate,
            'travelDateHijri' => $this->travelDateHijri,
            'bookedSeats' => $this->bookedSeats,
            'availableSeats' => $this->availableSeats,
            'cancelledSeats' => $this->cancelledSeats,
            'seatMap' => $this->seatMap,
            'status' => $this->status,
            'creationDateHijri' => $this->creationDateHijri,
            'creationDate' => $this->creationDate,
            'changed_data' => $this->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
