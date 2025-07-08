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
            'busSubtotal' => $this->busSubtotal,
            // 'campaign_id' => $this->campaign?->id,
            // 'campaign_name' => $this->campaign?->name,
            'office_id' => $this->office?->id,
            'office_name' => $this->office?->name,
            // 'group_id' => $this->group?->id,
            // 'group_num' => $this->group?->groupNum,
            // 'worker_id' => $this->worker?->id,
            // 'worker_name' => $this->worker?->name,
            // 'payment_method_type_id' => $this->paymentMethodType?->id,
            // 'payment_method_type' => $this->paymentMethodType?->type,
            // 'payment_method_type_by' => $this->paymentMethodType?->by,

        'description'=> $this-> description,

            'invoiceStatus' => $this->invoiceStatus,
            'reason' => $this->reason,


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

                'ihramSupplies' => $this->ihramSupplies->map(function ($ihramSupply) {
                    return [
                        'id' => $ihramSupply->id,
                        'name' => $ihramSupply->ihramItem->name,
                        'sellingPrice' => $ihramSupply->sellingPrice,
                        'quantity' => $ihramSupply->pivot->quantity,
                        'total' => $ihramSupply->pivot->total,
                    ];
                }),

        "ihramSuppliesCount"=> $this->ihramSuppliesCount,
        'ihramSubtotal' => $this->ihramSubtotal,

'hotels' => $this->hotels->map(function ($hotel) {
    return [
        'id' => $hotel->id,
        'name' => $hotel->name,
        'bedPrice' => $hotel->bedPrice ?? 0,
        'roomPrice' => $hotel->sellingPrice ?? 0,
        'checkInDate' => $hotel->pivot->checkInDate,
        'checkInDateHijri' => $hotel->pivot->checkInDateHijri,
        'checkOutDate' => $hotel->pivot->checkOutDate,
        'checkOutDateHijri' => $hotel->pivot->checkOutDateHijri,
        'bookingSource' => $hotel->pivot->bookingSource,
        // 'roomNum' => $hotel->pivot->roomNum,
        'numRoom' => $hotel->pivot->numRoom,
        'numBed' => $hotel->pivot->numBed,
        'need' => $hotel->pivot->need,
        'sleep' => $hotel->pivot->sleep,
        'numDay' => $hotel->pivot->numDay,
        'hotelSubtotal' => $hotel->pivot->hotelSubtotal,
    ];
}),

 'creationDateHijri' => $this->creationDateHijri,
            'creationDate' => $this->creationDate,
            'changed_data' => $this->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),

    'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'totalAfterDiscount'=>$this->totalAfterDiscount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paidAmount' => $this->paidAmount,

        ];

    }
}
