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
            'id'=> $this -> id,
            'payment_method_type' => new PaymentMethodTypeResource($this->whenLoaded('paymentMethodType')),
            'shipment' => new ShipmentResource($this->whenLoaded('shipment')),
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
