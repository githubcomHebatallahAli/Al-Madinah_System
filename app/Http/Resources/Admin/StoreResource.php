<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
   use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
