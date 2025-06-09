<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllDelegatesCampaignResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_id' => $this->title->id,
            'title_name' => $this->title->name ?? null,
            'name' => $this->name,
               'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name
                ];
            }),

            'pivot' => $this->whenPivotLoaded('campaign_workers', function () {
                return [
                    'creationDate' => $this->pivot->creationDate,
                ];
            }),




        ];
    }
}
