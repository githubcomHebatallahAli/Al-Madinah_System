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

 Gate::define('manage_system', function () {
            $adminUser = Auth::guard('admin')->user();
            if ($adminUser && $adminUser->role_id == 1) {
                return true;
            }

            $workerUser = Auth::guard('worker')->user();
            if ($workerUser && $workerUser->role_id == 2) {
                return true;
            }

            return false;
        });
    }

    }

