<?php

use App\Http\Controllers\AlokasiPetugasController;
use App\Http\Controllers\BastController;
use App\Http\Controllers\DasarHukumController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DipaController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\PenandatanganController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\RoleSwitchController;
use App\Http\Controllers\SbmlController;
use App\Http\Controllers\SbmlReportController;
use App\Http\Controllers\SkKpaController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\TwoFactorPromptController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\ViewAsUserController;
use App\Http\Controllers\YearSwitchController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Route untuk masuk ke maintenance mode (di web.php karena harus bisa diakses saat tidak maintenance)
Route::get('/mt', [MaintenanceController::class, 'showDown'])->name('maintenance.down');
Route::post('/mt', [MaintenanceController::class, 'processDown']);

// Alias /maintenance untuk /mt
Route::get('/maintenance', [MaintenanceController::class, 'showDown'])->name('maintenance.down.alt');
Route::post('/maintenance', [MaintenanceController::class, 'processDown']);

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

// Route untuk serve dan cleanup download file
Route::get('/serve-download/{filename}', function ($filename) {
    $filePath = public_path('downloads/'.$filename);

    if (! file_exists($filePath)) {
        abort(404, 'File tidak ditemukan');
    }

    return response()->download($filePath, $filename)->deleteFileAfterSend(true);
})->middleware('auth')->name('serve.download');

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

    // View As User (rhmtzikri only)
    Route::post('view-as-user/set', [ViewAsUserController::class, 'set'])->name('view-as-user.set');
    Route::post('view-as-user/clear', [ViewAsUserController::class, 'clear'])->name('view-as-user.clear');
    Route::get('view-as-user/search', [ViewAsUserController::class, 'search'])->name('view-as-user.search');

    // Admin System Settings
    Route::middleware(['active.role:admin'])->prefix('admin')->group(function () {
        Route::get('dashboard', function () {
            // Statistik sistem untuk dashboard
            $maintenance = app()->isDownForMaintenance();
            $totalUsers = \App\Models\User::count();
            $totalMitra = \App\Models\Petugas::count();
            $totalKegiatan = \App\Models\Kegiatan::whereIn('status', ['aktif', 'divalidasi'])->count();

            // Informasi backup terakhir
            $backupService = app(\App\Services\DatabaseBackupService::class);
            $backups = $backupService->listBackups();
            $lastBackup = $backups[0] ?? null;

            // Activity log terbaru
            $recentLogs = \App\Models\ActivityLog::with('user')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'user_name' => $log->user_name,
                    'action' => $log->action,
                    'description' => $log->description,
                    'status' => $log->status,
                    'type' => $log->type,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ]);

            // Database info
            $dbSize = 0;
            try {
                $dbName = DB::getDatabaseName();
                $tables = DB::select('SELECT ROUND(SUM((data_length + index_length) / 1024 / 1024), 2) AS size_mb FROM information_schema.tables WHERE table_schema = ?', [$dbName]);
                $dbSize = $tables[0]->size_mb ?? 0;
            } catch (\Exception $e) {
                // Silent fail
            }

            return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'totalUsers' => $totalUsers,
                    'totalMitra' => $totalMitra,
                    'totalKegiatan' => $totalKegiatan,
                    'dbSize' => round($dbSize, 2),
                ],
                'systemStatus' => [
                    'maintenance' => $maintenance,
                    'status' => $maintenance ? 'maintenance' : 'active',
                    'label' => $maintenance ? 'Maintenance' : 'Aktif',
                ],
                'lastBackup' => $lastBackup,
                'recentLogs' => $recentLogs,
            ]);
        })->name('admin.dashboard');
        Route::get('system-settings', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'index'])->name('admin.system-settings');
        Route::post('system-settings/maintenance', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'updateMaintenance'])->name('admin.system-settings.maintenance');
        Route::match(['get', 'post'], 'activity-log', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'activityLog'])->name('admin.activity-log');
        Route::get('activity-log/export', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'exportActivityLog'])->name('admin.activity-log.export');
        Route::get('database-status', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'databaseStatus'])->name('admin.database-status');
        Route::post('database-backup', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'backupDatabase'])->name('admin.database-backup');
        Route::post('database-restore', [\App\Http\Controllers\Admin\SystemSettingsController::class, 'restoreDatabase'])->name('admin.database-restore');
        Route::get('database-list-backups', function () {
            $backupService = app(\App\Services\DatabaseBackupService::class);
            $backups = $backupService->listBackups();

            return response()->json(['success' => true, 'backups' => $backups]);
        })->name('admin.database-list-backups');
    });
    // Petugas Management - IMPORTANT: Specific routes must come before parameter routes
    Route::middleware(['active.role:admin'])->group(function () {
        Route::get('petugas/template/download', [PetugasController::class, 'downloadTemplate'])->name('petugas.template');
        Route::post('petugas/import', [PetugasController::class, 'import'])->name('petugas.import');
        Route::get('petugas/create', [PetugasController::class, 'create'])->name('petugas.create');
        Route::post('petugas', [PetugasController::class, 'store'])->name('petugas.store');

        Route::get('petugas/{petugas}/edit', [PetugasController::class, 'edit'])->name('petugas.edit');
        Route::put('petugas/{petugas}', [PetugasController::class, 'update'])->name('petugas.update');
        Route::patch('petugas/{petugas}', [PetugasController::class, 'update']);
        Route::delete('petugas/{petugas}', [PetugasController::class, 'destroy'])->name('petugas.destroy');

        // User Role Management
        Route::match(['get', 'post'], 'users', [UserRoleController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [UserRoleController::class, 'edit'])->name('users.edit');
        Route::patch('users/{user}', [UserRoleController::class, 'update'])->name('users.update');
        Route::post('users/{user}/reset-2fa', \App\Http\Controllers\ResetUserTwoFactorController::class)->name('users.reset-2fa');
    });

    // View routes (Admin, PJ, and Administrator for read-only access)
    Route::middleware(['active.role:admin,pj,administrator'])->group(function () {
        Route::get('petugas', [PetugasController::class, 'index'])->name('petugas.index');
        Route::get('petugas/{petugas}', [PetugasController::class, 'show'])->name('petugas.show');
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

    // Kegiatan Management
    // IMPORTANT: Create route must come before {kegiatan} parameter route
    Route::get('kegiatan/create', [KegiatanController::class, 'create'])
        ->name('kegiatan.create')
        ->middleware('active.role:admin,operator,ketua_tim');

    Route::middleware(['active.role:admin,operator,ketua_tim,approver,pj,administrator'])->group(function () {
        // View routes accessible by all roles (including PJ and Administrator for read-only)
        Route::match(['get', 'post'], 'kegiatan', [KegiatanController::class, 'index'])->name('kegiatan.index');
        Route::get('kegiatan/{kegiatan}', [KegiatanController::class, 'show'])->name('kegiatan.show');
    });

    // Kegiatan modification routes (Admin, Operator, Ketua Tim only)
    Route::middleware(['active.role:admin,operator,ketua_tim'])->group(function () {
        Route::get('kegiatan/create', [KegiatanController::class, 'create'])->name('kegiatan.create');
        Route::get('kegiatan/{kegiatan}/copy', [KegiatanController::class, 'copy'])->name('kegiatan.copy');
        Route::post('kegiatan/store', [KegiatanController::class, 'store'])->name('kegiatan.store');
        Route::get('kegiatan/{kegiatan}/edit', [KegiatanController::class, 'edit'])->name('kegiatan.edit');
        Route::put('kegiatan/{kegiatan}', [KegiatanController::class, 'update'])->name('kegiatan.update');
        Route::patch('kegiatan/{kegiatan}', [KegiatanController::class, 'update']);
        Route::delete('kegiatan/{kegiatan}', [KegiatanController::class, 'destroy'])->name('kegiatan.destroy');
    });

    // Alokasi Petugas Management
    // IMPORTANT: Create route must come before {alokasi} parameter route
    Route::get('alokasi/create', [AlokasiPetugasController::class, 'create'])
        ->name('alokasi.create')
        ->middleware('active.role:admin,operator,ketua_tim');

    Route::middleware(['active.role:admin,operator,ketua_tim,approver,pj'])->group(function () {
        // View routes accessible by all roles (including PJ for read-only)
        Route::get('alokasi', [AlokasiPetugasController::class, 'index'])->name('alokasi.index');
        Route::get('alokasi/{alokasi}', [AlokasiPetugasController::class, 'show'])->name('alokasi.show');
        // Show periode detail (read-only) - accessible by PJ
        Route::get('alokasi/periode/{kegiatan}/{tahun}/{bulan}', [AlokasiPetugasController::class, 'showPeriode'])
            ->name('alokasi.periode.show');
    });

    // Alokasi modification routes (Admin, Operator, Ketua Tim only)
    Route::middleware(['active.role:admin,operator,ketua_tim'])->group(function () {
        Route::post('alokasi/store', [AlokasiPetugasController::class, 'store'])->name('alokasi.store');
        Route::post('alokasi/kegiatan/{kegiatan}/store-multiple', [AlokasiPetugasController::class, 'storeMultiple'])
            ->name('alokasi.store-multiple');
        Route::get('alokasi/{alokasi}/edit', [AlokasiPetugasController::class, 'edit'])->name('alokasi.edit');
        Route::put('alokasi/{alokasi}', [AlokasiPetugasController::class, 'update'])->name('alokasi.update');
        Route::patch('alokasi/{alokasi}', [AlokasiPetugasController::class, 'update']);
        Route::delete('alokasi/{alokasi}', [AlokasiPetugasController::class, 'destroy'])->name('alokasi.destroy');

        // Periode-based actions
        Route::post('alokasi/periode/{kegiatan}/{tahun}/{bulan}/submit', [AlokasiPetugasController::class, 'submitPeriode'])
            ->name('alokasi.periode.submit');
        // Edit periode
        Route::get('alokasi/periode/{kegiatan}/{tahun}/{bulan}/edit', [AlokasiPetugasController::class, 'editPeriode'])
            ->name('alokasi.periode.edit');
        // Update periode
        Route::put('alokasi/periode/{kegiatan}/{tahun}/{bulan}', [AlokasiPetugasController::class, 'updatePeriode'])
            ->name('alokasi.periode.update');
        Route::delete('alokasi/periode/{kegiatan}/{tahun}/{bulan}', [AlokasiPetugasController::class, 'destroyPeriode'])
            ->name('alokasi.periode.destroy');
        Route::post('alokasi/periode/{kegiatan}/{tahun}/{bulan}/revisi', [AlokasiPetugasController::class, 'revisiPeriode'])
            ->name('alokasi.periode.revisi');
    });

    // Update non response - Khusus ketua tim
    Route::post('alokasi/update-non-response', [AlokasiPetugasController::class, 'updateNonResponse'])
        ->name('alokasi.update-non-response')
        ->middleware('active.role:ketua_tim,admin,operator');

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

    // SBML Management
    Route::middleware(['active.role:admin,operator,pj'])->group(function () {
        // View routes (including PJ for read-only)
        Route::match(['get', 'post'], 'sbml', [SbmlController::class, 'index'])->name('sbml.index');
        Route::get('sbml/{tahun}', [SbmlController::class, 'show'])->name('sbml.show')->where('tahun', '[0-9]+');
    });

    // SBML modification routes (Admin, Operator only)
    Route::middleware(['active.role:admin,operator'])->group(function () {
        Route::delete('sbml/year/{tahun}', [SbmlController::class, 'destroyYear'])->name('sbml.destroyYear');
        Route::get('sbml/create', [SbmlController::class, 'create'])->name('sbml.create');
        Route::post('sbml', [SbmlController::class, 'store'])->name('sbml.store');
        Route::get('sbml/{tahun}/edit', [SbmlController::class, 'edit'])->name('sbml.edit')->where('tahun', '[0-9]+');
        Route::patch('sbml/{tahun}', [SbmlController::class, 'update'])->name('sbml.update')->where('tahun', '[0-9]+');
        Route::delete('sbml/{tahun}', [SbmlController::class, 'destroy'])->name('sbml.destroy')->where('tahun', '[0-9]+');
    });

    // Master Data Management (Admin, Operator access)
    Route::middleware(['active.role:admin,operator,pj'])->group(function () {
        // Penandatangan - View routes
        Route::match(['get', 'post'], 'penandatangan', [PenandatanganController::class, 'index'])->name('penandatangan.index');

        // DIPA - View routes
        Route::match(['get', 'post'], 'dipa', [DipaController::class, 'index'])->name('dipa.index');

        // Dasar Hukum - View routes (including PJ for read-only)
        Route::match(['get', 'post'], 'dasar-hukum', [DasarHukumController::class, 'index'])->name('dasar-hukum.index');
    });

    // Master Data modification routes (Admin, Operator only)
    Route::middleware(['active.role:admin,operator'])->group(function () {
        // Penandatangan
        Route::get('penandatangan/create', [PenandatanganController::class, 'create'])->name('penandatangan.create');
        Route::post('penandatangan/store', [PenandatanganController::class, 'store'])->name('penandatangan.store');
        Route::get('penandatangan/{penandatangan}/edit', [PenandatanganController::class, 'edit'])->name('penandatangan.edit');
        Route::put('penandatangan/{penandatangan}', [PenandatanganController::class, 'update'])->name('penandatangan.update');
        Route::patch('penandatangan/{penandatangan}', [PenandatanganController::class, 'update']);
        Route::delete('penandatangan/{penandatangan}', [PenandatanganController::class, 'destroy'])->name('penandatangan.destroy');

        // DIPA
        Route::get('dipa/create', [DipaController::class, 'create'])->name('dipa.create');
        Route::post('dipa/store', [DipaController::class, 'store'])->name('dipa.store');
        Route::get('dipa/{dipa}/edit', [DipaController::class, 'edit'])->name('dipa.edit');
        Route::put('dipa/{dipa}', [DipaController::class, 'update'])->name('dipa.update');
        Route::patch('dipa/{dipa}', [DipaController::class, 'update']);
        Route::delete('dipa/{dipa}', [DipaController::class, 'destroy'])->name('dipa.destroy');

        // Dasar Hukum
        Route::get('dasar-hukum/create', [DasarHukumController::class, 'create'])->name('dasar-hukum.create');
        Route::post('dasar-hukum/store', [DasarHukumController::class, 'store'])->name('dasar-hukum.store');
        Route::get('dasar-hukum/{dasarHukum}/edit', [DasarHukumController::class, 'edit'])->name('dasar-hukum.edit');
        Route::patch('dasar-hukum/{dasarHukum}', [DasarHukumController::class, 'update'])->name('dasar-hukum.update');
        Route::delete('dasar-hukum/{dasarHukum}', [DasarHukumController::class, 'destroy'])->name('dasar-hukum.destroy');
    });

    // SBML Report (Admin, Operator, PJ, Ketua Tim can view)
    Route::match(['get', 'post'], 'rekap-honor', [SbmlReportController::class, 'index'])
        ->name('sbml.report')
        ->middleware('active.role:admin,operator,pj,ketua_tim');

    // Document Management - View routes (Admin, Operator, PJ, Ketua Tim can view)
    Route::middleware(['active.role:admin,operator,pj,approver,ketua_tim'])->group(function () {
        Route::match(['get', 'post'], 'sk-kpa', [SkKpaController::class, 'index'])->name('sk-kpa.index');
        Route::get('sk-kpa/kegiatan/{kegiatanHashedId}', [SkKpaController::class, 'listByKegiatan'])->name('sk-kpa.list-by-kegiatan');
        Route::get('sk-kpa/{skKpa}', [SkKpaController::class, 'show'])->name('sk-kpa.show');
        Route::match(['get', 'post'], 'spk', [SpkController::class, 'index'])->name('spk.index');
        Route::post('spk/petugas-names', [SpkController::class, 'getPetugasNames'])->name('spk.petugas-names');
        Route::get('spk/list-by-month', [SpkController::class, 'listByMonth'])->name('spk.list-by-month');
        Route::get('spk/download-all', [SpkController::class, 'downloadAll'])->name('spk.download-all');
        Route::get('spk/periode/{periode}/kegiatan/{kegiatan}/download-all', [SpkController::class, 'downloadAllByKegiatan'])->name('spk.download-all-by-kegiatan');
        Route::get('spk/month', [SpkController::class, 'showByMonthGet'])->name('spk.show-by-month-get');
        Route::post('spk/month', [SpkController::class, 'showByMonth'])->name('spk.show-by-month');
        Route::post('spk/month/kegiatan/{kegiatan}/download', [SpkController::class, 'downloadByKegiatanMonth'])->name('spk.download-by-kegiatan-month');
        Route::post('spk/{spk}/upload-signed', [SpkController::class, 'uploadSigned'])->name('spk.upload-signed');
        Route::get('spk/{spk}', [SpkController::class, 'show'])->name('spk.show');

        // BAST Routes - View and Create
        Route::get('bast', [BastController::class, 'index'])->name('bast.index');
        Route::get('bast/list', [BastController::class, 'listByMonth'])->name('bast.list');
        Route::get('bast/create', [BastController::class, 'create'])->name('bast.create');
        Route::post('bast/generate-batch', [BastController::class, 'generateBatch'])->name('bast.generate-batch');
        Route::post('bast/preview-bast', [BastController::class, 'previewForSpk'])->name('bast.preview-bast');
        Route::get('bast/download-all', [BastController::class, 'downloadAll'])->name('bast.download-all');
        Route::get('bast/kegiatan/{kegiatan}/create', [BastController::class, 'createForKegiatan'])->name('bast.create-for-kegiatan');
        Route::post('bast/preview', [BastController::class, 'preview'])->name('bast.preview');
        Route::post('bast', [BastController::class, 'store'])->name('bast.store');
        Route::get('bast/{bast}/edit', [BastController::class, 'edit'])->name('bast.edit');
        Route::get('bast/{bast}/download', [BastController::class, 'downloadPdf'])->name('bast.download');
        Route::post('bast/{bast}/upload-signed', [BastController::class, 'uploadSigned'])->name('bast.upload-signed');
        Route::get('bast/{bast}', [BastController::class, 'show'])->name('bast.show');
        Route::put('bast/{bast}', [BastController::class, 'update'])->name('bast.update');
        Route::patch('bast/{bast}', [BastController::class, 'update']);
        Route::delete('bast/{bast}', [BastController::class, 'destroy'])->name('bast.destroy');
        Route::get('bast/{bast}', [BastController::class, 'show'])->name('bast.show');
    });

    // Document Management - Upload signed file (Admin, PJ, Operator can upload)
    Route::middleware(['active.role:admin,pj,operator,ketua_tim'])->group(function () {
        Route::post('sk-kpa/{skKpaHashedId}/upload-signed', [SkKpaController::class, 'uploadSigned'])->name('sk-kpa.upload-signed');
    });

    // Document Management - Generate SK (Admin, PJ, Operator can generate)
    Route::middleware(['active.role:admin,pj,operator'])->group(function () {
        Route::get('sk-kpa/kegiatan/{kegiatanHashedId}/create', [SkKpaController::class, 'create'])->name('sk-kpa.create-for-kegiatan');
        Route::post('sk-kpa/kegiatan/{kegiatanHashedId}/preview', [SkKpaController::class, 'previewSk'])->name('sk-kpa.preview');
        Route::post('sk-kpa/kegiatan/{kegiatanHashedId}/generate', [SkKpaController::class, 'generateSk'])->name('sk-kpa.generate');
        // Route::post('sk-kpa', [SkKpaController::class, 'store'])->name('sk-kpa.store'); // REMOVED: Konflik dengan route filtering di atas
        Route::get('sk-kpa/{skKpa}/edit', [SkKpaController::class, 'edit'])->name('sk-kpa.edit');
        Route::put('sk-kpa/{skKpa}', [SkKpaController::class, 'update'])->name('sk-kpa.update');
        Route::patch('sk-kpa/{skKpa}', [SkKpaController::class, 'update']);
        Route::delete('sk-kpa/{skKpa}', [SkKpaController::class, 'destroy'])->name('sk-kpa.destroy');
    });

    // SPK Routes - Admin, Approver can generate/manage SPK
    Route::middleware(['active.role:admin,approver'])->group(function () {
        Route::get('spk/periode/{periodeHashedId}/generate', [SpkController::class, 'create'])->name('spk.create');
        Route::get('spk/periode/{periodeHashedId}/addendum', [SpkController::class, 'createAddendum'])->name('spk.create-addendum');
        Route::post('spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview', [SpkController::class, 'previewSpk'])->name('spk.preview');
        Route::post('spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-main', [SpkController::class, 'previewSpkMain'])->name('spk.preview.main');
        Route::post('spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-lampiran', [SpkController::class, 'previewSpkLampiran'])->name('spk.preview.lampiran');
        Route::post('spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/preview-addendum', [SpkController::class, 'previewAddendum'])->name('spk.preview-addendum');
        Route::post('spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate', [SpkController::class, 'generateSpk'])->name('spk.generate');
        Route::post('spk/periode/{periodeHashedId}/petugas/{petugasHashedId}/generate-addendum', [SpkController::class, 'generateAddendum'])->name('spk.generate-addendum');
        Route::post('spk/periode/{periodeHashedId}/generate-all', [SpkController::class, 'generateAllSpk'])->name('spk.generate-all');
        Route::post('spk', [SpkController::class, 'store'])->name('spk.store');
        Route::get('spk/{spk}/edit', [SpkController::class, 'edit'])->name('spk.edit');
        Route::put('spk/{spk}', [SpkController::class, 'update'])->name('spk.update');
        Route::patch('spk/{spk}', [SpkController::class, 'update']);
        Route::delete('spk/{spk}', [SpkController::class, 'destroy'])->name('spk.destroy');
    });
});

require __DIR__.'/settings.php';
