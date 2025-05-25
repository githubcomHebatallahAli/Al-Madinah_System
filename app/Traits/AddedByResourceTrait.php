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
    //     // إذا لم يكن هناك تغييرات حقيقية
    //     if (!$this->shouldShowUpdatedBy()) {
    //         return null;
    //     }

    //     return $this->formatUserData(
    //         $this->updater,
    //         $this->updated_by_type ?? 'unknown'
    //     );
    // }

    public function updatedByAttribute()
{
    // Return null if this is the initial creation (updated_by not set yet)
    if (!$this->updated_by) {
        return null;
    }

    // Original logic for updates
    return $this->formatUserData(
        $this->updater,
        $this->updated_by_type ?? 'unknown'
    );
}

//  protected function shouldShowUpdatedBy(): bool
//     {
//         // إذا كان الموديل يستخدم TracksChangesTrait
//         if (method_exists($this->resource, 'hasRealChanges')) {
//             return $this->resource->hasRealChanges();
//         }

//         // التحقق الأساسي إذا لم يكن هناك Trait
//         return $this->resource->updated_by != $this->resource->added_by ||
//                $this->resource->updated_by_type != $this->resource->added_by_type;
//     }

protected function shouldShowUpdatedBy(): bool
{
    // If the model uses TracksChangesTrait, rely on its hasRealChanges method
    if (method_exists($this->resource, 'hasRealChanges')) {
        return $this->resource->hasRealChanges();
    }

    // Basic check - always show updater if updated_by is set
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



// namespace App\Traits;

// use App\Models\Admin;
// use App\Models\Worker;

// trait AddedByResourceTrait
// {

// public function addedByAttribute()
// {
//     return $this->creator ? (function () {
//         $creator = $this->creator;
//         $isAdmin = $this->added_by_type === \App\Models\Admin::class;
//         $isWorker = $this->added_by_type === \App\Models\Worker::class;

//         $email = null;
//         $roleId = null;
//         $roleName = '';

//         if ($isAdmin) {
//             $email = $creator->email ?? null;
//             $roleId = $creator->role_id ?? null;
//             $roleName = optional($creator->role)->name ?? '';
//         }

//         if ($isWorker) {
//             $email = optional($creator->workerLogin)->email ?? null;
//             $roleId = $creator->role_id
//                 ?? optional($creator->workerLogin)->role_id
//                 ?? optional(optional($creator->workerLogin)->role)->id;

//             $roleName = optional($creator->role)->name
//                 ?? optional(optional($creator->workerLogin)->role)->name
//                 ?? '';
//         }

//         return [
//             'id' => $creator->id,
//             'name' => $creator->name,
//             'email' => $email,
//             'role_id' => $roleId,
//             'role_name' => $roleName,
//             'type' => $this->added_by_type,
//             'branch' => $isWorker ? [
//                 'id' => optional($creator->branch)->id,
//                 'name' => optional($creator->branch)->name,
//             ] : null,
//         ];
//     })() : null;
// }


// public function updatedByAttribute()
// {
//     return $this->updater ? (function () {
//         $updater = $this->updater;
//         $isAdmin = $this->updated_by_type === \App\Models\Admin::class;
//         $isWorker = $this->updated_by_type === \App\Models\Worker::class;

//         $email = null;
//         $roleId = null;
//         $roleName = '';

//         if ($isAdmin) {
//             $email = $updater->email ?? null;
//             $roleId = $updater->role_id ?? null;
//             $roleName = optional($updater->role)->name ?? '';
//         }

//         if ($isWorker) {
//             $email = optional($updater->workerLogin)->email ?? null;
//             $roleId = $updater->role_id
//                 ?? optional($updater->workerLogin)->role_id
//                 ?? optional(optional($updater->workerLogin)->role)->id;

//             $roleName = optional($updater->role)->name
//                 ?? optional(optional($updater->workerLogin)->role)->name
//                 ?? '';
//         }

//         return [
//             'id' => $updater->id,
//             'name' => $updater->name,
//             'email' => $email,
//             'role_id' => $roleId,
//             'role_name' => $roleName,
//             'type' => $this->updated_by_type,
//             'branch' => $isWorker ? [
//                 'id' => optional($updater->branch)->id,
//                 'name' => optional($updater->branch)->name,
//             ] : null,
//         ];
//     })() : null;
// }




// }

