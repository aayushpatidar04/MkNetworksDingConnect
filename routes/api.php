<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DingConnectController;
use App\Http\Controllers\Api\OperatorController;
use App\Http\Controllers\Api\PaymentWebhookController;

Route::post('/ding/callback', [DingConnectController::class, 'callback']);
Route::post('/payment/webhook', [PaymentWebhookController::class, 'webhook']);
Route::get('/operators', [OperatorController::class, 'index']);
Route::get('/countries', [OperatorController::class, 'countries']);
