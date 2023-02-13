<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\ThrottleSms;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StatisticsController;

// Authentication routes (for guests)
Route::middleware(['throttle:auth_attempts'])->group(function () {
    Route::middleware([ThrottleSms::class])->post('auth/phone', [AuthController::class, 'register'])->name('login');

    Route::post('auth/phone/verify', [AuthController::class, 'verify'])->name('verify');
    Route::middleware([ThrottleSms::class])->post('auth/phone/resend', [AuthController::class, 'resend'])->name('resend');
});

// Application routes (for authorized)
Route::middleware(['auth:sanctum','check'])->group(function () {
    Route::get('user', [UserController::class, 'index']);
    Route::post('user', [UserController::class, 'update']);
    Route::delete('user', [UserController::class, 'destroy']);

    // Any user data
    Route::get('users/{user}', [UserController::class, 'getUserData'])->name('index.user');

    Route::post('user/status', [StatusController::class, 'store'])->name('status.store');

    Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('recalculatebalance', [UserController::class, 'recalculateBalance'])->name('newbalance');


    // Online statuses
    Route::get('online/{topic?}', [ActivityController::class, 'index'])->name('online.listeners');
    Route::post('online', [ActivityController::class, 'store'])->name('online.broadcast');
    Route::post('online/topic-languages', [ActivityController::class, 'getWithLanguages'])->name('online.topicLanguages');

    // Device data
    Route::post('devices', [DeviceController::class, 'store'])->name('device.store');
    Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->name('device.destroy');

    // Topics
    Route::get('topics', [TopicController::class, 'index'])->name('topic.index');
    Route::post('/user-topics', [TopicController::class, 'attachToUser'])->name('topic.attachToUser');

    // Transaction Hisotry
    Route::get('transactions', [TransactionController::class, 'getConversations'])->name('transactions');

    // Statistics
    Route::get('Statistics/Calls', [StatisticsController::class, 'getCalls'])->name('statistics.getcalls');
    Route::get('Statistics/Topics', [StatisticsController::class, 'getTopics'])->name('statistics.gettopics');
    Route::post('Statistics/Period', [StatisticsController::class, 'getPeriod'])->name('statistics.getperiod');

    //Profile
    Route::post('profile/applyforlistener', [ProfileController::class, 'applyForListener'])->name('profile.applyForListener');

    // Calls
    Route::group(['prefix' => 'call'], function () {
        Route::post('/', [ConversationController::class, 'start'])->name('call.start');
        Route::post('accept', [ConversationController::class, 'accept'])->name('call.accept');
        Route::post('cancel', [ConversationController::class, 'cancel'])->name('call.cancel');
        Route::post('finish', [ConversationController::class, 'finish'])->name('call.finish');

        Route::post('refresh', [ConversationController::class, 'refresh'])->name('channel.refresh-token');

        Route::post('list', [ConversationController::class, 'list'])->name('call.list');

        Route::post('report', [ReportController::class, 'store'])->name('report.store');

        Route::post('check', [ConversationController::class, 'checkConversationTime'])->name('call.check');

        // Finish all current user's conversations
        Route::post('finishall', [ConversationController::class, 'listFinishAll'])->name('call.finishall');
    });


    // Firebase device tokens
    Route::post('device-token/refresh', DeviceTokenController::class)->name('device-token.refresh');

    // Ratings
    Route::post('rating/{user}', [RatingController::class, 'store'])->name('rating.store');

    // Stripe payments
    Route::post('checkout-session', [PaymentController::class, 'createPaymentSession'])->name('payment.init');

    Route::get('activity/checkIdleLiteners', [ActivityController::class, 'checkIdleListeners'])->name('idleListeners');
});

Route::fallback(function () {
    return response()->json([
        'message' => 'Page Not Found.'
    ], 404);
});
