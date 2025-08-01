<?php

use App\Models\Admin;
use App\Models\Bus;
use App\Models\BusDriver;
use App\Models\BusTrip;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Flight;
use App\Models\FlightInvoice;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\IhramItem;
use App\Models\IhramSupply;
use App\Models\MainInvoice;
use App\Models\Office;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodType;
use App\Models\Pilgrim;
use App\Models\Service;
use App\Models\Shipment;
use App\Policies\AdminPolicy;
use App\Policies\BusDriverPolicy;
use App\Policies\BusPolicy;
use App\Policies\BusTripPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\FlightInvoicePolicy;
use App\Policies\FlightPolicy;
use App\Policies\GroupPolicy;
use App\Policies\HotelPolicy;
use App\Policies\IhramItemPolicy;
use App\Policies\IhramSupplyPolicy;
use App\Policies\MainInvoicePolicy;
use App\Policies\OfficePolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\PaymentMethodTypePolicy;

use App\Policies\PilgrimPolicy;
use App\Policies\ServicePolicy;
use App\Policies\ShipmentPolicy;
use function Illuminate\Foundation\Configuration\basePath;

use Illuminate\Foundation\Application;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;



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
        Gate::policy(Pilgrim::class, PilgrimPolicy::class);
        Gate::policy(BusTrip::class, BusTripPolicy::class);
        Gate::policy(MainInvoice::class, MainInvoicePolicy::class);
        Gate::policy(FlightInvoice::class, FlightInvoicePolicy::class);
        Gate::policy(Bus::class, BusPolicy::class);
        Gate::policy(BusDriver::class, BusDriverPolicy::class);
        Gate::policy(Hotel::class, HotelPolicy::class);
        Gate::policy(Flight::class, FlightPolicy::class);
        Gate::policy(IhramItem::class, IhramItemPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(PaymentMethod::class, PaymentMethodPolicy::class);
        Gate::policy(PaymentMethodType::class, PaymentMethodTypePolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);

    })->create();
