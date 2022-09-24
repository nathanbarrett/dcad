<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MapSearchController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PropertyChangesController;
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

Route::get('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware('guest')
    ->group(function () {

        Route::get('/login', [AuthController::class, 'showLoginForm'])
            ->name('auth.show.login');

        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('auth.login');
    });



Route::middleware('auth')
    ->group(function () {

        Route::get('/', function () {
            return redirect()->route('property.changes');
        })->name('home');

        Route::get('/property/changes', [PropertyChangesController::class, 'index'])
            ->name('property.changes');

        Route::get('/metrics', [MetricsController::class, 'index'])
            ->name('metrics.index');

        Route::get('/metrics/daily-changes-detected', [MetricsController::class, 'getDailyChangesDetected'])
            ->name('metrics.get-daily-changes-detected');

        Route::get('/search/maps', [MapSearchController::class, 'index'])
            ->name('search.maps.index');

    });
