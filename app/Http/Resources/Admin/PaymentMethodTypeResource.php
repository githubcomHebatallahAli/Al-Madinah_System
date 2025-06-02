<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodTypeResource extends JsonResource
{
        use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this -> id,
            'payment_method_id' => $this->paymentMethod?->id,
            'payment_method_name' => $this->paymentMethod?->name,
            'type'=> $this -> type,
            'status'=> $this ->status,
            'creationDate'=> $this ->creationDate,
            'creationDateHijri'=> $this ->creationDateHijri,
            'changed_data'=> $this ->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),
        ];
    }
}
