<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhook', [WebhookController::class, 'handleWebhook'])->name('payment.webhook');
