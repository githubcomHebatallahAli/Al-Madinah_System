<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllPaymentMethodTypeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id'=> $this -> id,
            'payment_method_id' => $this->paymentMethod?->id,
            'payment_method_name' => $this->paymentMethod?->name,
            'type'=> $this -> type,
            'by'=> $this -> by,
            'status'=> $this ->status,
            'creationDate'=> $this ->creationDate,
            
        ];
    }
}
