<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\WorkerLogin;
use App\Services\VonageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\Client\Credentials\Keypair;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    $this->app->singleton(Client::class, function ($app) {
        return new Client(new Basic(
            config('services.vonage.api_key'),
            config('services.vonage.api_secret')
        ));
    });

    $this->app->singleton(VonageService::class, function ($app) {
        return new VonageService($app->make(Client::class));
    });
    }

    public function boot(): void
    {
        Gate::define('manage_users', function($user) {
            return    Auth::guard('admin')->check()&& $user->role_id == 1;
        });

Gate::define('manage_system', function ($user) {
    return $user && (
        ($user instanceof Admin && $user->role_id == 1) ||
        ($user instanceof WorkerLogin && $user->role_id == 2)
    );
});

// Gate::define('manage_system', function ($user) {
//     if ($user instanceof Admin) {
//         return $user->role_id == 1 && $user->status === 'active';
//     }

//     if ($user instanceof WorkerLogin) {
//         $worker = $user->worker;

//         return $user->role_id == 2 &&
//                $worker &&
//                $worker->status === 'active' &&
//                $worker->dashboardAccess === 'ok';
//     }

//     return false;
// });

        // $this->app->singleton(Client::class, function ($app) {
        //     return new Client(new Basic(
        //         config('services.vonage.api_key'),
        //         config('services.vonage.api_secret')
            
        //     ));
        // });

    //         $this->app->singleton(Client::class, function ($app) {
    //     $keypair = new Keypair(
    //         file_get_contents(config('services.vonage.private_key')),
    //         config('services.vonage.application_id')
    //     );

    //     return new Client($keypair);
    // });

    }

    
    }







