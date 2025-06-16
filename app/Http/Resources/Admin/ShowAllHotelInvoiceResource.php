<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllHotelInvoiceResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
                        "id" => $this->id,
            'pilgrimsCount'=> $this ->pilgrimsCount,


            'bus_invoice_id'=> $this ->busInvoice?->id,
            'trip_id' => $this->trip?->id,
            'trip_name' => $this->trip?->name,
            'hotel_id' => $this->hotel?->id,
            'hotel_name' => $this->hotel?->name,
            'payment_method_type_id' => $this->paymentMethodType?->id,
            'payment_method_type' => $this->paymentMethodType?->type,
            'payment_method_type_by' => $this->paymentMethodType?->by,

            'bookingSource'=> $this-> bookingSource,
            'total' => $this->total,
            'paidAmount' => $this->paidAmount,
            'invoiceStatus' => $this->invoiceStatus,
            'paymentStatus' => $this->paymentStatus,
            'creationDate' => $this->creationDate,

        ];
    }
}
