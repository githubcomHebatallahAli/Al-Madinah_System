<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllIhramInvoiceResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [
            "id" => $this->id,
            "ihramSuppliesCount"=> $this->ihramSuppliesCount,

            'bus_invoice_id'=> $this ->busInvoice?->id,
            'payment_method_type_id' => $this->paymentMethodType?->id,
            'payment_method_type' => $this->paymentMethodType?->type,
            'payment_method_type_by' => $this->paymentMethodType?->by,

            'total' => $this->total,
            'paidAmount' => $this->paidAmount,

            'invoiceStatus' => $this->invoiceStatus,
            'paymentStatus' => $this->paymentStatus,
            'creationDateHijri' => $this->creationDateHijri,
            'creationDate' => $this->creationDate,
        ];
    }
}
