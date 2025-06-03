<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllWorkerResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'branch_id'   => $this->id,
            'branch_name' => $this->name,

            'titles' => $this->title->map(function ($title) {
                return [
                    'title_id'   => $title->id,
                    'title_name' => $title->name,

                    'workers' => $title->workers->map(function ($worker) {
                        return [
                            'worker_id'      => $worker->id,
                            'worker_name'    => $worker->name,
                            'status'         => $worker->status,
                            'dashboardAccess'=> $worker->dashboardAccess,
                            'creationDate'=> $worker->creationDate,
                            'creationDateHijri'=> $worker->creationDateHijri,
                        ];
                    }),
                ];
            }),
        ];
    }
}
