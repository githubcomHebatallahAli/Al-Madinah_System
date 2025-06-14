<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class HotelInvoiceResource extends JsonResource
{
    use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            'pilgrimsCount'=> $this ->pilgrimsCount,
            'main_pilgrim' => $this->whenLoaded('mainPilgrim', function () {
    return [
        'id' => $this->mainPilgrim->id,
        'name' => $this->mainPilgrim->name,
        'phone' => $this->mainPilgrim->phone
    ];
}),

            'invoiceNumber' => $this->invoiceNumber,
            'pilgrimsCount'=> $this ->pilgrimsCount,
            'bus_trip_id'=> $this ->busTrip?->id,
            'bedPrice' => $this->busTrip?->hotel?->bedPrice ?? 0,
            'roomPrice' => $this->busTrip?->hotel?->roomPrice ?? 0,
            'trip_id' => $this->trip?->id,
            'trip_name' => $this->trip?->name,
            'hotel_id' => $this->hotel?->id,
            'hotel_name' => $this->hotel?->name,
            'payment_method_type_id' => $this->paymentMethodType?->id,
            'payment_method_type' => $this->paymentMethodType?->type,
            'payment_method_type_by' => $this->paymentMethodType?->by,

        'residenceDate' => $this-> residenceDate ,
        'residenceDateHijri'=> $this-> residenceDateHijri,
        'bookingSource'=> $this-> bookingSource,
        'roomNum'=> $this-> roomNum,
        'need'=> $this-> need,
        'sleep'=> $this-> sleep,
        'numDay'=> $this-> numDay,
        'description'=> $this-> description,

            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paidAmount' => $this->paidAmount,
            'bookedSeats' => $this->busTrip->bookedSeats ?? 0,
            'availableSeats' => $this->busTrip->availableSeats ?? 0,
            'cancelledSeats' => $this->busTrip->cancelledSeats ?? 0,
            'invoiceStatus' => $this->invoiceStatus,
            'reason' => $this->reason,
            'paymentStatus' => $this->paymentStatus,
            'incomplete_pilgrims' => $this->incomplete_pilgrims,
            'creationDateHijri' => $this->creationDateHijri,
            'creationDate' => $this->creationDate,
            'changed_data' => $this->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
            'pilgrims' => $this->whenLoaded('pilgrims', function () {
                return $this->pilgrims->map(function ($pilgrim) {
                    return [
                        'id' => $pilgrim->id,
                        'name' => $pilgrim->name ?? '-',
                        'status' => $pilgrim->pivot->status,
                        'creationDateHijri' => $this->getHijriDate($pilgrim->pivot->creationDateHijri),
                        'creationDate' => $pilgrim->pivot->creationDate,

                    ];
                });
            }),

        ];
    }
}
