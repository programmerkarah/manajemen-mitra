<?php

use App\Http\Controllers\AlokasiMitraController;
use App\Http\Controllers\BastController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\RateHonorController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\SkKpaController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\TwoFactorPromptController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
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

    // Mitra Management (Admin only)
    Route::middleware(['can:admin'])->group(function () {
        Route::get('mitra/template/download', [MitraController::class, 'downloadTemplate'])->name('mitra.template');
        Route::post('mitra/import', [MitraController::class, 'import'])->name('mitra.import');
        Route::resource('mitra', MitraController::class);
        Route::resource('satuan', SatuanController::class);
        Route::resource('rate-honor', RateHonorController::class);

        // User Role Management
        Route::get('users', [UserRoleController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [UserRoleController::class, 'edit'])->name('users.edit');
        Route::patch('users/{user}', [UserRoleController::class, 'update'])->name('users.update');
    });

    // Kegiatan Management (Admin and PJ)
    Route::middleware(['can:admin-or-pj'])->group(function () {
        Route::resource('kegiatan', KegiatanController::class);
    });

    // Alokasi Management (Operator, Approver)
    Route::get('alokasi/kegiatan/{kegiatan}/manage', [AlokasiMitraController::class, 'manage'])
        ->name('alokasi.manage');
    Route::post('alokasi/kegiatan/{kegiatan}/store-multiple', [AlokasiMitraController::class, 'storeMultiple'])
        ->name('alokasi.store-multiple');
    Route::resource('alokasi', AlokasiMitraController::class);
    Route::post('alokasi/{alokasi}/submit', [AlokasiMitraController::class, 'submit'])
        ->name('alokasi.submit');
    Route::post('alokasi/{alokasi}/approve', [AlokasiMitraController::class, 'approve'])
        ->name('alokasi.approve')
        ->middleware('can:approver');
    Route::post('alokasi/{alokasi}/reject', [AlokasiMitraController::class, 'reject'])
        ->name('alokasi.reject')
        ->middleware('can:approver');

    // Document Management
    Route::middleware(['can:admin-or-approver'])->group(function () {
        Route::resource('sk-kpa', SkKpaController::class);
        Route::resource('spk', SpkController::class);
        Route::resource('bast', BastController::class);
    });
});

require __DIR__.'/settings.php';
