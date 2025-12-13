<?php

use App\Http\Controllers\AlokasiPetugasController;
use App\Http\Controllers\BastController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\RoleSwitchController;
use App\Http\Controllers\SbmlController;
use App\Http\Controllers\SbmlReportController;
use App\Http\Controllers\SkKpaController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\TwoFactorPromptController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\YearSwitchController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// 2FA Setup Prompt (accessible after login and email verification, but before 2FA)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('two-factor/prompt', TwoFactorPromptController::class)
        ->name('two-factor.prompt');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Role Switching
    Route::post('switch-role', [RoleSwitchController::class, 'switch'])->name('role.switch');

    // Year Switching
    Route::post('switch-year', [YearSwitchController::class, 'switch'])->name('year.switch');

    // Petugas Management (Admin only)
    Route::middleware(['active.role:admin'])->group(function () {
        Route::get('petugas/template/download', [PetugasController::class, 'downloadTemplate'])->name('petugas.template');
        Route::post('petugas/import', [PetugasController::class, 'import'])->name('petugas.import');
        Route::resource('petugas', PetugasController::class);

        // User Role Management
        Route::get('users', [UserRoleController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [UserRoleController::class, 'edit'])->name('users.edit');
        Route::patch('users/{user}', [UserRoleController::class, 'update'])->name('users.update');
    });

    // Rate Honor Management per Kegiatan
    // IMPORTANT: Must be defined BEFORE kegiatan resource routes to avoid conflict
    Route::get('kegiatan/{kegiatan}/rate-honor/manage', [KegiatanController::class, 'manageRateHonor'])
        ->name('kegiatan.rate-honor.manage')
        ->middleware('active.role:operator,admin,ketua_tim');
    Route::post('kegiatan/{kegiatan}/rate-honor/bulk', [KegiatanController::class, 'bulkUpdateRateHonor'])
        ->name('kegiatan.rate-honor.bulk')
        ->middleware('active.role:operator,admin,ketua_tim');

    // Kegiatan Management (Admin, Operator, and Ketua Tim)
    Route::middleware(['active.role:admin,operator,ketua_tim'])->group(function () {
        Route::resource('kegiatan', KegiatanController::class);
    });

    // Alokasi Petugas Management (Admin and Ketua Tim)
    Route::middleware(['active.role:admin,ketua_tim'])->group(function () {
        Route::get('alokasi/kegiatan/{kegiatan}/manage', [AlokasiPetugasController::class, 'manage'])
            ->name('alokasi.manage');
        Route::post('alokasi/kegiatan/{kegiatan}/store-multiple', [AlokasiPetugasController::class, 'storeMultiple'])
            ->name('alokasi.store-multiple');
        Route::resource('alokasi', AlokasiPetugasController::class);
    });

    // Alokasi Approval Workflow
    Route::post('alokasi/{alokasi}/submit', [AlokasiPetugasController::class, 'submit'])
        ->name('alokasi.submit');
    Route::post('alokasi/{alokasi}/approve-pj', [AlokasiPetugasController::class, 'approvePj'])
        ->name('alokasi.approve-pj')
        ->middleware('active.role:pj');
    Route::post('alokasi/{alokasi}/approve', [AlokasiPetugasController::class, 'approve'])
        ->name('alokasi.approve')
        ->middleware('active.role:approver');
    Route::post('alokasi/{alokasi}/reject', [AlokasiPetugasController::class, 'reject'])
        ->name('alokasi.reject')
        ->middleware('active.role:approver');

    // SBML Management (Admin only)
    Route::middleware(['active.role:admin'])->group(function () {
        Route::delete('sbml/year/{tahun}', [SbmlController::class, 'destroyYear'])->name('sbml.destroyYear');
        Route::resource('sbml', SbmlController::class);
    });

    // SBML Report (Admin, Operator, Approver)
    Route::get('sbml-report', [SbmlReportController::class, 'index'])
        ->name('sbml.report')
        ->middleware('active.role:admin,operator,approver');

    // Document Management (Admin or Approver)
    Route::middleware(['active.role:admin,approver'])->group(function () {
        Route::resource('sk-kpa', SkKpaController::class);
        Route::resource('spk', SpkController::class);
        Route::resource('bast', BastController::class);
    });
});

require __DIR__.'/settings.php';
