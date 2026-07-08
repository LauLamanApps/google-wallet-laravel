<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use LauLamanApps\GoogleWalletLaravel\Http\Controllers\CallbackController;

Route::post('callback', [CallbackController::class, 'callback'])
    ->name('google-wallet.callback');
