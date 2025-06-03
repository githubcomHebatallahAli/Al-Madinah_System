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
        // return [
            // 'id'=>$this->id,
            // 'branch_id'   => $this->worker?->title?->branch?->id,
            // 'branch_name' => $this->worker?->title?->branch?->name,
            // 'title_id' => $this->worker?->title?->id,
            // 'title_name' => $this->worker?->title?->name,
            // 'store_id' => $this->worker?->store?->id,
            // 'store_name' => $this->worker?->store?->name,
            // 'worker_id' => $this->worker?->id,
            // 'worker_name' => $this->worker?->name,
            // 'status' => $this->worker?->status,
            // 'dashboardAccess' => $this->worker?->dashboardAccess,
            // 'email'=>$this->email,
            // 'role_id' => $this->role?->id,
            // 'role_name' => $this->role?->name,
            // 'creationDate' => $this -> creationDate,
            // 'creationDateHijri'=> $this -> creationDateHijri,

        // ];


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
