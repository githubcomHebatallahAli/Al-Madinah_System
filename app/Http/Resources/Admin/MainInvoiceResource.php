<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class MainInvoiceResource extends JsonResource
{
    use AddedByResourceTrait;

    public function toArray(Request $request): array
    {
        return [
                        "id" => $this->id,
            'pilgrimsCount'=> $this ->pilgrimsCount,

                        'main_pilgrim' => $this->whenLoaded('mainPilgrim', function() {
                return $this->mainPilgrim ? [
                    'id' => $this->mainPilgrim->id,
                    'name' => $this->mainPilgrim->name,
                    'phone' => $this->mainPilgrim->phoNum ?? null,

                ] : null;
            }),

            'invoiceNumber' => $this->invoiceNumber,
            'pilgrimsCount'=> $this ->pilgrimsCount,
            'bus_trip_id'=> $this ->busTrip?->id,
            'seatPrice' => $this->busTrip?->bus?->seatPrice ?? 0,
            'campaign_id' => $this->campaign?->id,
            'campaign_name' => $this->campaign?->name,
            'office_id' => $this->office?->id,
            'office_name' => $this->office?->name,
            'group_id' => $this->group?->id,
            'group_num' => $this->group?->groupNum,
            'worker_id' => $this->worker?->id,
            'worker_name' => $this->worker?->name,
            'payment_method_type_id' => $this->paymentMethodType?->id,
            'payment_method_type' => $this->paymentMethodType?->type,
            'payment_method_type_by' => $this->paymentMethodType?->by,

            'bedPrice' => $this->hotel?->bedPrice ?? 0,
            'roomPrice' => $this->hotel?->sellingPrice ?? 0,
            'trip_id' => $this->trip?->id,
            'trip_name' => $this->trip?->name,
            'hotel_id' => $this->hotel?->id,
            'hotel_name' => $this->hotel?->name,
            'checkInDate' => $this-> checkInDate ,
        'checkInDateHijri'=> $this-> checkInDateHijri,
        'checkOutDate'=> $this-> checkOutDate,
        'checkOutDateHijri'=> $this-> checkOutDateHijri,
        'bookingSource'=> $this-> bookingSource,
        'roomNum'=> $this-> roomNum,
        'need'=> $this-> need,
        'sleep'=> $this-> sleep,
        'numDay'=> $this-> numDay,

        "ihramSuppliesCount"=> $this->ihramSuppliesCount,
        
        'description'=> $this-> description,

            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'totalAfterDiscount'=>$this->totalAfterDiscount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paidAmount' => $this->paidAmount,
            'bookedSeats' => $this->busTrip->bookedSeats ?? 0,
            'availableSeats' => $this->busTrip->availableSeats ?? 0,
            'cancelledSeats' => $this->busTrip->cancelledSeats ?? 0,
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
                        'status' => $pilgrim->pivot->status,
                        'type' => $pilgrim->pivot->type,
                        'position' => $pilgrim->pivot->position,
                        'creationDateHijri' => $this->getHijriDate($pilgrim->pivot->creationDateHijri),
                        'creationDate' => $pilgrim->pivot->creationDate,

                    ];
                });
            }),
        ];

    }
}
