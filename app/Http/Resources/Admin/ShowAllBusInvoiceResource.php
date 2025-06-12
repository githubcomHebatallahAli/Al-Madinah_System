<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllBusInvoiceResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'invoiceNumber' => $this->invoiceNumber,
            'pilgrimsCount'=> $this ->pilgrimsCount,
            'bus_trip_id'=> $this ->busTrip?->id,
            'office_id' => $this->office?->id,
            'office_name' => $this->office?->name,
            'campaign_id' => $this->campaign?->id,
            'campaign_name' => $this->campaign?->name,
            'worker_id' => $this->worker?->id,
            'worker_name' => $this->worker?->name,
            'payment_method_type_id' => $this->paymentMethodType?->id,
            'payment_method_type_by' => $this->paymentMethodType?->by,
            'total' => $this->total,
            'paidAmount' => $this->paidAmount,
            'invoiceStatus' => $this->invoiceStatus,
            'paymentStatus' => $this->paymentStatus,
            'creationDate' => $this->creationDate,
        ];
    }
}
