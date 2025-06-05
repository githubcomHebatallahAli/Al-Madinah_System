<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentInvoiceResource extends JsonResource
{
    use AddedByResourceTrait;

    public function toArray(Request $request): array
    {
        return [
        'payment_method' => $this->when($this->relationLoaded('paymentMethodType') && $this->paymentMethodType && $this->paymentMethodType->relationLoaded('paymentMethod'), function () {
            return [
                'id' => $this->paymentMethodType->paymentMethod->id ?? null,
                'name' => $this->paymentMethodType->paymentMethod->name ?? null,
            ];
        }),

            'payment_method_type' => $this->whenLoaded('paymentMethodType', function () {
    return [
       'payment_method_type_id' => $this->paymentMethodType->id ?? null,
        'type' => $this->paymentMethodType->type ?? null,
        'by' => $this->paymentMethodType->by ?? null,

    ];
}),



            'shipment' => $this->whenLoaded('shipment', function () {
    return [
       'shipment_id' => $this->shipment->id ?? null,

            'service_id' => $this->shipment->service->id ?? null,
            'service_name' => $this->shipment->service->name ?? null,

            'supplier_id' => $this->shipment->supplier->id ?? null,
            'supplier_name' => $this->shipment->supplier->name ?? null,

            'company_id' => $this->shipment->company->id ?? null,
            'company_name' => $this->shipment->company->name ?? null,

            'shipmentItemsCount' => $this->shipment->shipmentItemsCount ?? 0,
            'totalPrice' => $this->shipment->totalPrice ?? 0,
    ];
}),


            'discount'=> $this->discount,
            'totalPriceAfterDiscount'=> $this->totalPriceAfterDiscount,
            'paidAmount'=> $this->paidAmount,
            'remainingAmount'=> $this->remainingAmount,
            'invoice'=> $this->invoice,
            'status'=> $this ->status,
            'description'=> $this ->description,
            'creationDate'=> $this ->creationDate,
            'creationDateHijri'=> $this ->creationDateHijri,
            'changed_data'=> $this ->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
