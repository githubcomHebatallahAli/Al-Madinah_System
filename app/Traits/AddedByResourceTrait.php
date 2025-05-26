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



// public function updatedByAttribute()
// {

//     if (empty($this->updated_by) || $this->created_at->equalTo($this->updated_at)) {
//         return null;
//     }

//     return $this->formatUserData(
//         $this->updater,
//         $this->updated_by_type ?? 'unknown'
//     );
// }


public function updatedByAttribute()
{
    // إذا لم يكن هناك updated_by أو لم يتم التحديث بعد الإنشاء
    if (empty($this->updated_by) || $this->created_at->equalTo($this->updated_at)) {
        return null;
    }

    // إذا كان التحديث بواسطة نفس المستخدم الذي أنشأ السجل
    if ($this->updated_by == $this->added_by && $this->updated_by_type == $this->added_by_type) {
        return $this->addedByAttribute();
    }

    return $this->formatUserData(
        $this->updater,
        $this->updated_by_type ?? 'unknown'
    );
}

protected function shouldShowUpdatedBy(): bool
{

    if (method_exists($this->resource, 'hasRealChanges')) {
        return $this->resource->hasRealChanges();
    }
    return $this->resource->updated_by !== null;
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


