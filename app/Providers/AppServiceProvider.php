<?php

namespace App\Providers;

use App\Listeners\FiscalizationListener;
use Flavytech\Etims\Events\InvoiceFailed;
use Flavytech\Etims\Events\InvoiceSubmitted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Wire SDK events to application listeners
        Event::listen(InvoiceSubmitted::class, [FiscalizationListener::class, 'handleSubmitted']);
        Event::listen(InvoiceFailed::class,    [FiscalizationListener::class, 'handleFailed']);
    }
}
