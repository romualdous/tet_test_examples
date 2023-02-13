<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('getProduct/{language?}', [ProfileController::class, 'returnProductPage'])->name('ProductPage');
Route::post('json/save', [ProfileController::class, 'getJson'])->name('getJson');
Route::get('{id}', [ProfileController::class, 'reVisit'])->name('reVisit');
Route::get('{profile_url}/{language?}', [ProfileController::class, 'getUserDetails'])->name('ProfilePage');


