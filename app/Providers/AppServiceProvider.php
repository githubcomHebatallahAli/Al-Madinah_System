<?php

namespace App\Providers;

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

//     Gate::define('manage_system', function ($user) {
//     return
//         (Auth::guard('admin')->check() && $user->role_id == 1) ||
//         (Auth::guard('worker')->check() && $user->role_id == 2);
// });

Gate::define('manage_system', function () {
    $user = Auth::guard('worker')->user() ?? Auth::guard('admin')->user();
    return $user && in_array($user->role_id, [1, 2]);
});


    }
}
