<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightInvoiceResource extends JsonResource
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

            // 'invoiceNumber' => $this->invoiceNumber,
            'flight_id'=> $this ->flight_id,
            'flight_direction' => $this->flight?->direction,
            'DateTimeTrip'=>$this->flight?->DateTimeTrip,
            'trip_id' => $this->trip?->id,
            'trip_name' => $this->trip?->name,
            'hotel_id' => $this->hotel?->id,
            'hotel_name' => $this->hotel?->name,
            'payment_method_type_id' => $this->paymentMethodType?->id,
            'payment_method_type' => $this->paymentMethodType?->type,
            'payment_method_type_by' => $this->paymentMethodType?->by,

            'description'=> $this-> description,
            'seatsCount' => $this->seatsCount,
            'ticketPrice' => $this->flight?->sellingPrice ?? 0,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'totalAfterDiscount'=>$this->totalAfterDiscount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paidAmount' => $this->paidAmount,

            'invoiceStatus' => $this->invoiceStatus,
            'reason' => $this->reason,
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
                         'idNum' => $pilgrim->idNum ?? '-',
                        'phoNum' => $pilgrim->phoNum ?? '-',
                        'nationality' => $pilgrim->nationality ?? '-',
                        'gender' => $pilgrim-> gender?? '-',
                        'seatNumber' => $pilgrim->pivot->seatNumber,
                        'creationDateHijri' => $this->getHijriDate($pilgrim->pivot->creationDateHijri),
                        'creationDate' => $pilgrim->pivot->creationDate,
                    ];
                });
            }),


        ];
    }
}
