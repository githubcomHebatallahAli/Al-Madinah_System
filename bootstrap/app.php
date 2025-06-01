<?php

use App\Models\Admin;
use App\Models\IhramSupply;
use App\Policies\AdminPolicy;
use App\Policies\IhramSupplyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([

            'worker' => \App\Http\Middleware\WorkerMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'adminOrWorker' => \App\Http\Middleware\AdminOrWorkerMiddleware::class,
        ]);

    })
      ->withExceptions(function (Exceptions $exceptions) {
        Gate::policy(Admin::class, AdminPolicy::class);
        Gate::policy(IhramSupply::class, IhramSupplyPolicy::class);


    })->create();
