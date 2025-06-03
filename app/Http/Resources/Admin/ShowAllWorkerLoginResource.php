<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Traits\AddedByResourceTrait;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllWorkerLoginResource extends JsonResource
{
     use AddedByResourceTrait;
    public function toArray(Request $request): array
    {
           return [
            'branch_id' => $this->id,
            'branch_name' => $this->name,

            'titles' => $this->titles->map(function ($title) {
                return [
                    'title_id' => $title->id,
                    'title_name' => $title->name,

                    'workers' => $title->workers->map(function ($worker) {
                        return [
                            'worker_id' => $worker->id,
                            'worker_name' => $worker->name,
                            // 'store_id' => $worker->store?->id,
                            // 'store_name' => $worker->store?->name,
                            'email' => $worker->workerLogin?->email,
                            'role_id' => $worker->workerLogin?->role?->id,
                            'role_name' => $worker->workerLogin?->role?->name,
                            'status' => $worker->status,
                            'dashboardAccess' => $worker->dashboardAccess,
                            'creationDate' => $worker->creationDate,
                            'creationDateHijri' => $worker->creationDateHijri,
                        ];
                    }),
                ];
            }),
        ];
    }
}
