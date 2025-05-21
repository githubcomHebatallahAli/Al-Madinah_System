<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\Worker;

trait AddedByResourceTrait
{
    // public function addedByAttribute()
    // {
    //     return $this->whenLoaded('creator', function () {
    //         return [
    //             'id' => $this->creator->id,
    //             'name' => $this->creator->name,
    //             'email' => $this->creator->email ?? null,
    //             'role_id' => $this->creator->role_id,
    //             'role_name' => optional($this->creator->role)->name ?? '',
    //             'type' => $this->added_by_type,
    //             'branch' => $this->when(
    //                 method_exists($this->creator, 'branch') && $this->creator->relationLoaded('branch'),
    //                 function () {
    //                     return [
    //                         'id' => optional($this->creator->branch)->id,
    //                         'name' => optional($this->creator->branch)->name,
    //                     ];
    //                 }
    //             ),
    //         ];
    //     });
    // }

    public function addedByAttribute()
    {
        return $this->whenLoaded('creator', function () {
            $creator = $this->creator;
            $isAdmin = $this->added_by_type === Admin::class;
            $isWorker = $this->added_by_type === Worker::class;

            $email = null;
            $roleId = null;
            $roleName = '';

            if ($isAdmin) {
                $email = $creator->email ?? null;
                $roleId = $creator->role_id ?? null;
                $roleName = optional($creator->role)->name ?? '';
            }

            if ($isWorker) {
                $email = optional($creator->workerLogin)->email ?? null;
                $roleId = $creator->role_id
                    ?? optional($creator->workerLogin)->role_id
                    ?? optional($creator->workerLogin->role)->id;

                $roleName = optional($creator->role)->name
                    ?? optional($creator->workerLogin->role)->name
                    ?? '';
            }

            return [
                'id' => $creator->id,
                'name' => $creator->name,
                'email' => $email,
                'role_id' => $roleId,
                'role_name' => $roleName,
                'type' => $this->added_by_type,
                'branch' => $this->when(
                    method_exists($creator, 'branch') && $creator->relationLoaded('branch'),
                    function () use ($creator) {
                        return [
                            'id' => optional($creator->branch)->id,
                            'name' => optional($creator->branch)->name,
                        ];
                    }
                ),
            ];
        });
    }
}
