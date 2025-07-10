<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawResource extends JsonResource
{
     use AddedByResourceTrait;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this -> id,
            'withdrawnAmount'  => $this -> withdrawnAmount,
            'remainingAmount'  => $this -> remainingAmount,
            'description'  => $this -> description,
            'creationDate'=> $this ->creationDate,
            'creationDateHijri'=> $this ->creationDateHijri,
            'changed_data'=> $this ->changed_data,
            'added_by' => $this->addedByAttribute(),
            'updated_by' => $this->updatedByAttribute(),

        ];
    }
}
