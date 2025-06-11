<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class BusInvoiceResource extends JsonResource
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
            'seatPrice' => $this-> seatPrice,
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
                        'seatNumber' => $pilgrim->pivot->seatNumber,

                        'status' => $pilgrim->pivot->status,
                        'type' => $pilgrim->pivot->type,
                        'position' => $pilgrim->pivot->position,
                        'creationDateHijri' => $this->creationDateHijri,
                        'creationDate' => $this->creationDate,
                        'changed_data' => $this->changed_data,
                    ];
                });
            }),
        ];
    }

    // protected function getPilgrimName($pilgrim): string
    // {
    //     // يمكنك تعديل هذا بناءً على هيكل بيانات المعتمر لديك
    //     return $pilgrim->first_name . ' ' . $pilgrim->last_name;
    // }

    }

