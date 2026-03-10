<?php

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
