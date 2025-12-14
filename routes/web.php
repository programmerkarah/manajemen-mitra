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

// Debug route - remove after deployment works
Route::get('/debug', function () {
    return response()->json([
        'status' => 'OK',
        'laravel_version' => app()->version(),
        'environment' => app()->environment(),
        'debug_mode' => config('app.debug'),
        'url' => config('app.url'),
        'session_driver' => config('session.driver'),
        'cache_driver' => config('cache.default'),
        'can_register' => Features::enabled(Features::registration()),
    ]);
});

// Simple HTML test without Inertia
Route::get('/simple', function () {
    return view('simple-test');
});

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

    // Kegiatan Approval Workflow
    Route::post('kegiatan/{kegiatan}/submit', [KegiatanController::class, 'submit'])
        ->name('kegiatan.submit')
        ->middleware('active.role:admin,operator,ketua_tim');
    Route::post('kegiatan/{kegiatan}/approve', [KegiatanController::class, 'approve'])
        ->name('kegiatan.approve')
        ->middleware('active.role:admin,approver');
    Route::post('kegiatan/{kegiatan}/reject', [KegiatanController::class, 'reject'])
        ->name('kegiatan.reject')
        ->middleware('active.role:admin,approver');

    // Kegiatan Management (Admin, Operator, Ketua Tim, and Approver can view)
    Route::middleware(['active.role:admin,operator,ketua_tim,approver'])->group(function () {
        Route::resource('kegiatan', KegiatanController::class);
    });

    // Alokasi Petugas Management (Admin, Operator, Ketua Tim, Approver can view)
    Route::middleware(['active.role:admin,operator,ketua_tim,approver'])->group(function () {
        Route::get('alokasi/kegiatan/{kegiatan}/manage', [AlokasiPetugasController::class, 'manage'])
            ->name('alokasi.manage');
        Route::post('alokasi/kegiatan/{kegiatan}/store-multiple', [AlokasiPetugasController::class, 'storeMultiple'])
            ->name('alokasi.store-multiple');
        Route::resource('alokasi', AlokasiPetugasController::class);
    });

    // Alokasi Approval Workflow
    Route::post('alokasi/{alokasi}/submit', [AlokasiPetugasController::class, 'submit'])
        ->name('alokasi.submit')
        ->middleware('active.role:admin,operator,ketua_tim');
    Route::post('alokasi/{alokasi}/approve', [AlokasiPetugasController::class, 'approve'])
        ->name('alokasi.approve')
        ->middleware('active.role:admin,approver');
    Route::post('alokasi/{alokasi}/reject', [AlokasiPetugasController::class, 'reject'])
        ->name('alokasi.reject')
        ->middleware('active.role:admin,approver');

    // SBML Management (Admin, Operator)
    Route::middleware(['active.role:admin,operator'])->group(function () {
        Route::delete('sbml/year/{tahun}', [SbmlController::class, 'destroyYear'])->name('sbml.destroyYear');
        Route::get('sbml', [SbmlController::class, 'index'])->name('sbml.index');
        Route::get('sbml/create', [SbmlController::class, 'create'])->name('sbml.create');
        Route::post('sbml', [SbmlController::class, 'store'])->name('sbml.store');
        Route::get('sbml/{tahun}', [SbmlController::class, 'show'])->name('sbml.show')->where('tahun', '[0-9]+');
        Route::get('sbml/{tahun}/edit', [SbmlController::class, 'edit'])->name('sbml.edit')->where('tahun', '[0-9]+');
        Route::patch('sbml/{tahun}', [SbmlController::class, 'update'])->name('sbml.update')->where('tahun', '[0-9]+');
        Route::delete('sbml/{tahun}', [SbmlController::class, 'destroy'])->name('sbml.destroy')->where('tahun', '[0-9]+');
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
