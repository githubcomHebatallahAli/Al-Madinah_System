<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class IhramInvoiceResource extends JsonResource
{
    use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "ihramSuppliesCount"=> $this->ihramSuppliesCount,
            'main_pilgrim' => $this->whenLoaded('mainPilgrim', function () {
    return [
        'id' => $this->mainPilgrim->id,
        'name' => $this->mainPilgrim->name,
        'phone' => $this->mainPilgrim->phone
    ];
}),

            // 'invoiceNumber' => $this->invoiceNumber,
            'bus_invoice_id'=> $this ->busInvoice?->id,
            'payment_method_type_id' => $this->paymentMethodType?->id,
            'payment_method_type' => $this->paymentMethodType?->type,
            'payment_method_type_by' => $this->paymentMethodType?->by,
            'description'=> $this-> description,

            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paidAmount' => $this->paidAmount,

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

        ];
    }
}
