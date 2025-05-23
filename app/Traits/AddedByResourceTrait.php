<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\Worker;

trait AddedByResourceTrait
{

public function addedByAttribute()
{
    return $this->creator ? (function () {
        $creator = $this->creator;
        $isAdmin = $this->added_by_type === \App\Models\Admin::class;
        $isWorker = $this->added_by_type === \App\Models\Worker::class;

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
                ?? optional(optional($creator->workerLogin)->role)->id;

            $roleName = optional($creator->role)->name
                ?? optional(optional($creator->workerLogin)->role)->name
                ?? '';
        }

        return [
            'id' => $creator->id,
            'name' => $creator->name,
            'email' => $email,
            'role_id' => $roleId,
            'role_name' => $roleName,
            'type' => $this->added_by_type,
            'branch' => $isWorker ? [
                'id' => optional($creator->branch)->id,
                'name' => optional($creator->branch)->name,
            ] : null,
        ];
    })() : null;
}


public function updatedByAttribute()
{
    return $this->updater ? (function () {
        $updater = $this->updater;
        $isAdmin = $this->updated_by_type === \App\Models\Admin::class;
        $isWorker = $this->updated_by_type === \App\Models\Worker::class;

        $email = null;
        $roleId = null;
        $roleName = '';

        if ($isAdmin) {
            $email = $updater->email ?? null;
            $roleId = $updater->role_id ?? null;
            $roleName = optional($updater->role)->name ?? '';
        }

        if ($isWorker) {
            $email = optional($updater->workerLogin)->email ?? null;
            $roleId = $updater->role_id
                ?? optional($updater->workerLogin)->role_id
                ?? optional(optional($updater->workerLogin)->role)->id;

            $roleName = optional($updater->role)->name
                ?? optional(optional($updater->workerLogin)->role)->name
                ?? '';
        }

        return [
            'id' => $updater->id,
            'name' => $updater->name,
            'email' => $email,
            'role_id' => $roleId,
            'role_name' => $roleName,
            'type' => $this->updated_by_type,
            'branch' => $isWorker ? [
                'id' => optional($updater->branch)->id,
                'name' => optional($updater->branch)->name,
            ] : null,
        ];
    })() : null;
}


}
