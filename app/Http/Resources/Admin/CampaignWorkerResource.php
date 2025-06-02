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
            'title_id' => $this->id,
            'title_name' => $this->name,
            'name' => $this->name,
            'status' => $this->status,
            'dashboardAccess' => $this->dashboardAccess,

            'pivot' => [
                'added_by' => $this->pivot->added_by,
                'added_by_type' => $this->pivot->added_by_type,
                'updated_by' => $this->pivot->updated_by,
                'updated_by_type' => $this->pivot->updated_by_type,
                'creationDate' => $this->pivot->creationDate,
                'creationDateHijri' => $this->pivot->creationDateHijri,
            ],

            'worker_login' => $this->whenLoaded('workerLogin', function () {
                return [
                    'id' => $this->workerLogin->id,
                    'email' => $this->workerLogin->email,
                    'role_id' => $this->workerLogin->role_id,
                ];
            }),

        ];
    }
}
