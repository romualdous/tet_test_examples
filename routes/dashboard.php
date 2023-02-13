<?php

use App\Http\Controllers\Dashboard\LoginController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here we register routes for admin panel/dashboard that will be consumed
| by users (admins) who have admin access.
|
*/

Route::post('users/{user_id}/tracking', [AdminController::class, 'useridTracking'])->name('admin.user_tracking');
Route::post('users/tracking/all', [AdminController::class, 'tracking'])->name('admin.tracking');
Route::get('statistics/calls', [StatisticsController::class, 'getCallsToDashboard'])->name('admin.getCalls');

Route::middleware(['origin_check'])->group(function () {
    Route::post('users/{id}/recalculatebalance', [AdminController::class, 'recalculateBalanceOnUser'])->name('admin.new_balance_on_user');
});
// Guest authentication routes
Route::middleware(['guest:admin','origin_check'])->group(function () {
    //Basic Routes
    Route::post('login', [LoginController::class, 'authenticate'])->name('admin.login');
    Route::post('forgot-password', [LoginController::class, 'forgotPassword'])->name('admin.forgot-password');
    Route::post('reset-password', [LoginController::class, 'resetPassword'])->name('admin.reset-password');
});

// Routes for authorized admins
Route::middleware(['auth:admin','origin_check'])->group(function () {
    Route::get('logout', [LoginController::class, 'logout'])->name('admin.logout');
    Route::get('activity/checkIdleListeners', [ActivityController::class, 'checkIdleListeners'])->name('admin.checkIdleListeners');

    // User interacted routes
    Route::post('users/{user_id}', [AdminController::class, 'editUsersById'])->name('admin.editUsersById');
    Route::post('users/{user_id}/withdraw', [AdminController::class, 'withdrawFromUser'])->name('admin.withdrawFromUser');
    Route::post('users/{id}/recalculatebalance', [AdminController::class, 'recalculateBalanceOnUser'])->name('admin.new_balance_on_user');
    //Route::post('users/{user_id}/tracking', [AdminController::class, 'useridTracking'])->name('admin.user_tracking');
    //Route::post('users/tracking/all', [AdminController::class, 'tracking'])->name('admin.tracking');

    // Statistics routes
    //Route::get('statistics/calls', [StatisticsController::class, 'getCallsToDashboard'])->name('admin.getCalls');
    Route::post('statistics/period', [StatisticsController::class, 'getPeriod'])->name('admin.getPeriod');
    Route::get('statistics/reports', [StatisticsController::class, 'getReports'])->name('admin.getReports');

    // Current user profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('admin.profile.index');
        Route::post('password', [ProfileController::class, 'updatePassword'])->name('admin.profile.update-password');
        Route::post('information', [ProfileController::class, 'updateInfo'])->name('admin.profile.update-info');
        Route::delete('photo', [ProfileController::class, 'deletePhoto'])->name('admin.profile.delete-photo');
        Route::delete('other-browser-sessions', [ProfileController::class, 'deleteOtherSessions'])->name('admin.profile.delete-sessions');
    });

    // User index & management
    Route::resource('users', UserController::class, [
        'names' => [
            'index'   => 'admin.users.index',
            'store'   => 'admin.users.store',
            'show'    => 'admin.users.show',
            'update'  => 'admin.users.update',
            'destroy' => 'admin.users.destroy'
        ]
    ])->except(['edit', 'create']);
});
