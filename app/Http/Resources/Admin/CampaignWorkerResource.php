<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignWorkerResource extends JsonResource
{
    use AddedByResourceTrait;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_id' => $this->title->id,
            'title_name' => $this->title->name ?? null,
            'name' => $this->name,
            'status' => $this->status,
            'dashboardAccess' => $this->dashboardAccess,

            'pivot' => $this->whenPivotLoaded('campaign_workers', function () {
                return [
                    'added_by' => $this->addedByAttribute(),
                    'updated_by' => $this->updatedByAttribute(),
                    'creationDate' => $this->pivot->creationDate,
                    'creationDateHijri' => $this->pivot->creationDateHijri,
                    // 'changed_data' => $this->pivot->changed_data ?? null
                ];
            }),

            'worker_login' => $this->whenLoaded('workerLogin', function () {
                return [
                    'id' => $this->workerLogin->id,
                    'email' => $this->workerLogin->email,
                    'role' => [
                        'id' => $this->workerLogin->role_id,
                        'name' => $this->workerLogin->role->name ?? null
                    ]
                ];
            }),

            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name
                ];
            }),

        ];
    }
}
