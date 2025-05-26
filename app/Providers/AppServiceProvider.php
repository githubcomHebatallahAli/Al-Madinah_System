<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Worker;
use App\Models\WorkerLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage_users', function($user) {
            return    Auth::guard('admin')->check()&& $user->role_id == 1;
        });

// Gate::define('manage_system', function ($user) {
//     return $user && (
//         ($user instanceof Admin && $user->role_id == 1) ||
//         ($user instanceof WorkerLogin && $user->role_id == 2)
//     );
// });

    Gate::define('manage_system', function ($user) {
        if (!$user) return false;

        // للـ Admin
        if ($user instanceof \App\Models\Admin) {
            return $user->role_id == 1 && $user->status == 'active';
        }

        // للـ WorkerLogin
        if ($user instanceof \App\Models\WorkerLogin) {
            return $user->role_id == 2 &&
                   $user->status == 'active' &&
                   $user->dashboardAccess == 'ok';
        }

        return false;
    });

    }






}
