<?php

namespace App\Traits;

use App\Models\Admin;
use App\Models\Worker;
use Illuminate\Support\Facades\Auth;


trait TracksChangesTrait
{
//     public function getChangedData(array $oldData, array $newData): array
// {
//     $ignoredKeys = ['updated_at'];
//     $changed = [];

//     foreach ($newData as $key => $newValue) {
//         if (in_array($key, $ignoredKeys)) {
//             continue;
//         }

//         if (array_key_exists($key, $oldData) && $oldData[$key] !== $newValue) {
//             $changed[$key] = [
//                 'old' => $oldData[$key],
//                 'new' => $newValue,
//             ];
//         }
//     }

//     return $changed;
// }

    public function getChangedData(array $oldData, array $newData): array
    {
        $ignoredKeys = ['updated_at', 'created_at'];
        $changed = [];

        foreach ($newData as $key => $newValue) {
            if (in_array($key, $ignoredKeys)) {
                continue;
            }

            // مقارنة القيم كـ string لتفادي الاختلافات الوهمية
            if (array_key_exists($key, $oldData) && (string)$oldData[$key] !== (string)$newValue) {
                $changed[$key] = [
                    'old' => $oldData[$key],
                    'new' => $newValue,
                ];
            }
        }

        // بيانات من أضاف السجل
        if (isset($this->added_by, $this->added_by_type)) {
            $changed['added_by'] = $this->formatUserData($this->added_by_type, $this->added_by);
        }

        // بيانات من قام بالتعديل (المستخدم الحالي)
        if (Auth::guard('admin')->check() || Auth::guard('worker')->check()) {
            $changed['updated_by'] = $this->getCurrentUserData();
        }

        return $changed;
    }

    protected function formatUserData(string $type, int $id): ?array
    {
        if ($type === Admin::class) {
            $admin = Admin::with('role')->find($id);
            if (!$admin) return null;

            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role_id' => $admin->role_id,
                'role_name' => optional($admin->role)->name,
                'type' => Admin::class,
                'branch' => null,
            ];
        }

        if ($type === Worker::class) {
            $worker = Worker::with(['branch', 'workerLogin.role'])->find($id);
            if (!$worker) return null;

            $login = $worker->workerLogin;

            return [
                'id' => $worker->id,
                'name' => $worker->name,
                'email' => optional($login)->email,
                'role_id' => optional($login)->role_id,
                'role_name' => optional(optional($login)->role)->name,
                'type' => Worker::class,
                'branch' => $worker->branch ? [
                    'id' => $worker->branch->id,
                    'name' => $worker->branch->name,
                ] : null,
            ];
        }

        return null;
    }

    protected function getCurrentUserData(): ?array
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role_id' => $admin->role_id,
                'role_name' => optional($admin->role)->name,
                'type' => Admin::class,
                'branch' => null,
            ];
        }

        if (Auth::guard('worker')->check()) {
            $worker = Auth::guard('worker')->user();
            $login = $worker->workerLogin;

            return [
                'id' => $worker->id,
                'name' => $worker->name,
                'email' => optional($login)->email,
                'role_id' => optional($login)->role_id,
                'role_name' => optional(optional($login)->role)->name,
                'type' => Worker::class,
                'branch' => $worker->branch ? [
                    'id' => $worker->branch->id,
                    'name' => $worker->branch->name,
                ] : null,
            ];
        }

        return null;
    }

}
