<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CloseResource extends JsonResource
{
   
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_active' => $this->is_active,
        ];
    }
}
