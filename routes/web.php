<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Devices
Route::resource('devices', DeviceController::class);
Route::post('devices/{device}/relay', [DeviceController::class, 'toggleRelay'])->name('devices.relay');
Route::post('devices/{device}/poll', [DeviceController::class, 'pollStatus'])->name('devices.poll');

// Unit top-ups
Route::get('devices/{device}/topup', [UnitController::class, 'create'])->name('units.create');
Route::post('devices/{device}/topup', [UnitController::class, 'store'])->name('units.store');

// API (for JS polling)
Route::prefix('api')->group(function () {
    Route::get('devices/{device}/live', [ApiController::class, 'liveData'])->name('api.live');
});

// Admin panel
Route::prefix('admin')->name('admin.')->middleware(\App\Http\Middleware\AdminAuth::class)->group(function () {
    Route::get('login', [AdminController::class, 'loginForm'])->name('login');
    Route::post('login', [AdminController::class, 'loginForm'])->name('login.post');
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('onboard', [AdminController::class, 'onboard'])->name('onboard');
    Route::post('onboard', [AdminController::class, 'onboardStore'])->name('onboard.store');
    Route::get('devices/{device}/edit', [AdminController::class, 'editDevice'])->name('devices.edit');
    Route::put('devices/{device}', [AdminController::class, 'updateDevice'])->name('devices.update');
    Route::delete('devices/{device}', [AdminController::class, 'destroyDevice'])->name('devices.destroy');
    Route::post('devices/{device}/relay', [AdminController::class, 'toggleRelay'])->name('devices.relay');
});
