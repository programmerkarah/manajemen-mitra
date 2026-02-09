<?php

use App\Http\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

// These routes bypass PreventRequestsDuringMaintenance middleware
Route::get('/bypass', [MaintenanceController::class, 'showBypass'])->name('maintenance.bypass');
Route::post('/bypass', [MaintenanceController::class, 'processBypass']);
Route::get('/up', [MaintenanceController::class, 'showUp'])->name('maintenance.up');
Route::post('/up', [MaintenanceController::class, 'processUp']);
