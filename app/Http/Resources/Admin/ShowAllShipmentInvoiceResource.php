<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllShipmentInvoiceResource extends JsonResource
{

    public function toArray(Request $request): array
    {
               return [
                'payment_method' => $this->when(
            $this->relationLoaded('paymentMethodType') && $this->paymentMethodType && $this->paymentMethodType->relationLoaded('paymentMethod'),
            function () {
                return [
                    'id' => $this->paymentMethodType->paymentMethod->id ?? null,
                    'name' => $this->paymentMethodType->paymentMethod->name ?? null,
                ];
            }
        ),

    'shipment' => $this->whenLoaded('shipment', function () {
    return [
    'shipment_id' => $this->shipment->id ?? null,

    'service_id' => $this->shipment->service->id ?? null,
    'service_name' => $this->shipment->service->name ?? null,

    // 'company_id' => $this->shipment->company->id ?? null,
    // 'company_name' => $this->shipment->company->name ?? null,

    // 'shipmentItemsCount' => $this->shipment->shipmentItemsCount ?? 0,
    // 'totalPrice' => $this->shipment->totalPrice ?? 0,
    ];
}),
    'totalPriceAfterDiscount'=> $this->totalPriceAfterDiscount,
    'paidAmount'=> $this->paidAmount,
    'remainingAmount'=> $this->remainingAmount,
    'invoice'=> $this->invoice,
    'status'=> $this ->status,
    'creationDate'=> $this ->creationDate,
        ];
    }
}
