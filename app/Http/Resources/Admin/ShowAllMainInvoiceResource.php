<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllMainInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            'pilgrimsCount'=> $this ->pilgrimsCount,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'totalAfterDiscount'=>$this->totalAfterDiscount,
            'tax' => $this->tax,
            'total' => $this->total,
            'paidAmount' => $this->paidAmount,
            'creationDate' => $this->creationDate,
        ];
    }
}
