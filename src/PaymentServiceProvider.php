<?php

namespace Tabapay\Payment;

use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/payment.php' => config_path('payment.php'),
        ], 'config');
    }

    public function register()
    {
        $this->app->singleton(Payment::class, function () {
            return new Payment();
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/payment.php', 'payment');
    }
}