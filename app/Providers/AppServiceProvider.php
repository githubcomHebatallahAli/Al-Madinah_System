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
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->role_id == 1) {
            return true;
        }

        if (Auth::guard('worker')->check() && Auth::guard('worker')->user()->role_id == 2) {
            return true;
        }

        return false;
    });
    }

    }

