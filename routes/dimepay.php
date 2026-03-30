<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Osoobe\DimePay\Http\Controllers\CallbackController;

Route::prefix(config('dimepay.routes.prefix', 'dimepay'))
    ->middleware(config('dimepay.routes.middleware', ['api']))
    ->group(function () {

        // Webhook — handled by spatie/laravel-webhook-client
        Route::webhooks('/webhook', 'dimepay');

        // Callback — redirect landing after hosted payment page
        Route::get('/callback', [CallbackController::class, 'handle'])
            ->name('dimepay.callback');

    });
