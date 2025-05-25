<?php

namespace App\Traits;

trait AddedByResourceTrait
{
    public function addedByAttribute()
    {

        return $this->formatUserData(
            $this->creator,
            $this->added_by_type ?? 'unknown'
        );
    }

    public function updatedByAttribute()
    {
        // إذا لم يكن هناك تغييرات حقيقية
        if (!$this->shouldShowUpdatedBy()) {
            return null;
        }

        return $this->formatUserData(
            $this->updater,
            $this->updated_by_type ?? 'unknown'
        );
    }

 protected function shouldShowUpdatedBy(): bool
    {
        // إذا كان الموديل يستخدم TracksChangesTrait
        if (method_exists($this->resource, 'hasRealChanges')) {
            return $this->resource->hasRealChanges();
        }

        // التحقق الأساسي إذا لم يكن هناك Trait
        return $this->resource->updated_by != $this->resource->added_by ||
               $this->resource->updated_by_type != $this->resource->added_by_type;
    }

    protected function formatUserData($user, string $userType): ?array
    {
        if (!$user) {
            return null;
        }

        $isAdmin = $userType === \App\Models\Admin::class;
        $isWorker = $userType === \App\Models\Worker::class;

        $email = null;
        $roleId = null;
        $roleName = '';

        if ($isAdmin) {
            $email = $user->email ?? null;
            $roleId = $user->role_id ?? null;
            $roleName = optional($user->role)->name ?? '';
        }

        if ($isWorker) {
            $email = optional($user->workerLogin)->email ?? null;
            $roleId = optional($user->workerLogin)->role_id ?? null;
            $roleName = optional(optional($user->workerLogin)->role)->name ?? '';
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $email,
            'role_id' => $roleId,
            'role_name' => $roleName,
            'type' => $userType,
            'branch' => $isWorker ? [
                'id' => optional($user->branch)->id,
                'name' => optional($user->branch)->name,
            ] : null,
        ];
    }
}
