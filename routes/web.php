<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MapSearchController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PropertyChangesController;
use App\Http\Controllers\ZipCodesController;
use Illuminate\Support\Facades\Route;

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

Route::inertia('/', 'Home')
    ->name('home');

Route::get('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware('guest')
    ->group(function () {

        Route::inertia('/login', 'Auth/Login')
            ->name('auth.show.login');

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('auth.login');
    });



Route::middleware('auth')
    ->group(function () {

        Route::inertia('/account', 'Account/Home')
            ->name('account.home');

        Route::inertia('/account/notification-subscriptions', 'Account/NotificationSubscriptions')
            ->name('account.notification-subscriptions');

        Route::get('/property/changes', [PropertyChangesController::class, 'index'])
            ->name('property.changes');

        Route::get('/metrics', [MetricsController::class, 'index'])
            ->name('metrics.index');

        Route::get('/metrics/daily-changes-detected', [MetricsController::class, 'getDailyChangesDetected'])
            ->name('metrics.get-daily-changes-detected');

        Route::get('/search/map', [MapSearchController::class, 'index'])
            ->name('search.map.index');

        Route::post('/search/map', [MapSearchController::class, 'mapSearch'])
            ->name('search.map.search');

        Route::get('/zip_codes/all', [ZipCodesController::class, 'all'])
            ->name('zip_codes.all');

        Route::get('/zip_codes/dallas', [ZipCodesController::class, 'dallas'])
            ->name('zip_codes.dallas');

        Route::post('/account/notification-subscription', [AccountController::class, 'createNotificationSubscription'])
            ->name('account.create.notification-subscription');

    });
