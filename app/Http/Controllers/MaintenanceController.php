<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    /**
     * Menampilkan halaman bypass maintenance mode
     */
    public function showBypass(Request $request)
    {
        // Jika maintenance OFF, redirect ke halaman sebelumnya
        if (! app()->isDownForMaintenance()) {
            return redirect('/dashboard');
        }

        // Jika ada query parameter key, proses langsung
        if ($request->has('key')) {
            $secretToken = config('app.maintenance_bypass_secret');

            if ($secretToken && $request->query('key') === $secretToken) {
                // Set bypass cookie menggunakan Laravel's maintenance mode bypass cookie
                $cookie = MaintenanceModeBypassCookie::create($secretToken);

                return redirect('/')
                    ->withCookie($cookie)
                    ->with('success', 'Berhasil bypass maintenance mode');
            }

            return redirect('/bypass')->withErrors(['key' => 'Kunci bypass tidak valid']);
        }

        return Inertia::render('maintenance/bypass');
    }

    /**
     * Proses bypass maintenance mode
     */
    public function processBypass(Request $request)
    {
        // Jika maintenance OFF, redirect ke halaman sebelumnya
        if (! app()->isDownForMaintenance()) {
            return redirect('/dashboard');
        }

        $request->validate([
            'key' => ['required', 'string'],
        ]);

        $secretToken = config('app.maintenance_bypass_secret');

        if ($secretToken && $request->input('key') === $secretToken) {
            // Set bypass cookie menggunakan Laravel's maintenance mode bypass cookie
            $cookie = MaintenanceModeBypassCookie::create($secretToken);

            return redirect('/')
                ->withCookie($cookie)
                ->with('success', 'Berhasil bypass maintenance mode');
        }

        return back()->withErrors([
            'key' => 'Kunci bypass tidak valid',
        ]);
    }

    /**
     * Menampilkan halaman untuk mengaktifkan kembali layanan
     */
    public function showUp(Request $request)
    {
        // Jika maintenance OFF, redirect ke halaman sebelumnya
        if (! app()->isDownForMaintenance()) {
            return redirect('/dashboard');
        }

        // Jika ada query parameter key, proses langsung
        if ($request->has('key')) {
            $secretToken = config('app.maintenance_bypass_session_key');

            if ($secretToken && $request->query('key') === $secretToken) {
                // Nonaktifkan maintenance mode
                Artisan::call('up');

                // Hapus pesan maintenance dari storage
                Storage::delete('framework/maintenance-message.txt');

                // Log aktivitas
                ActivityLog::log(
                    action: 'Sistem Diaktifkan Kembali',
                    type: 'system',
                    description: 'Maintenance mode dinonaktifkan - sistem kembali beroperasi normal',
                    status: 'success',
                    metadata: [
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );

                return redirect('/')->with('success', 'Layanan berhasil diaktifkan kembali');
            }

            return redirect('/up')->withErrors(['key' => 'Kunci aktivasi tidak valid']);
        }

        return Inertia::render('maintenance/up');
    }

    /**
     * Proses mengaktifkan kembali layanan
     */
    public function processUp(Request $request)
    {
        // Jika maintenance OFF, redirect ke halaman sebelumnya
        if (! app()->isDownForMaintenance()) {
            return redirect('/dashboard');
        }

        $request->validate([
            'key' => ['required', 'string'],
        ]);

        $secretToken = config('app.maintenance_bypass_session_key');

        if ($secretToken && $request->input('key') === $secretToken) {
            // Nonaktifkan maintenance mode
            Artisan::call('up');

            // Hapus pesan maintenance dari storage
            Storage::delete('framework/maintenance-message.txt');

            // Log aktivitas
            ActivityLog::log(
                action: 'Sistem Diaktifkan Kembali',
                type: 'system',
                description: 'Maintenance mode dinonaktifkan - sistem kembali beroperasi normal',
                status: 'success',
                metadata: [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );

            return redirect('/')->with('success', 'Layanan berhasil diaktifkan kembali');
        }

        return back()->withErrors([
            'key' => 'Kunci aktivasi tidak valid',
        ]);
    }

    /**
     * Menampilkan halaman untuk masuk maintenance mode
     */
    public function showDown(Request $request)
    {
        // Cek apakah sudah dalam maintenance mode
        if (app()->isDownForMaintenance()) {
            abort(503, 'Service Unavailable');
        }

        // Jika ada query parameter key, proses langsung
        if ($request->has('key')) {
            $secretToken = config('app.maintenance_bypass_start_key');

            if ($secretToken && $request->query('key') === $secretToken) {
                $message = $request->query('message', 'Saat ini kami sedang melakukan peningkatan layanan. Mohon bersabar.');

                // Simpan pesan maintenance ke storage
                Storage::put('framework/maintenance-message.txt', $message);

                // Get bypass secret from config
                $bypassSecret = config('app.maintenance_bypass_secret');

                // Aktifkan maintenance mode dengan secret bypass
                Artisan::call('down', [
                    '--refresh' => 15,
                    '--secret' => $bypassSecret,
                ]);

                // Remove prerendered template to allow middleware to work
                $this->removeMaintenancePrerenderTemplate();

                // Create bypass cookie for current user
                $bypassCookie = MaintenanceModeBypassCookie::create($bypassSecret);

                // Log aktivitas
                ActivityLog::log(
                    action: 'Maintenance Mode Diaktifkan',
                    type: 'system',
                    description: 'Sistem masuk maintenance mode'.($message !== 'Saat ini kami sedang melakukan peningkatan layanan. Mohon bersabar.' ? ': '.$message : ''),
                    status: 'warning',
                    metadata: [
                        'message' => $message,
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'refresh_interval' => 15,
                    ]
                );

                // Return JSON response with cookie (similar to SystemSettingsController)
                return response()->json([
                    'success' => true,
                    'message' => 'Sistem berhasil masuk maintenance mode',
                ])->withCookie($bypassCookie);
            }

            return redirect('/mt')->withErrors(['key' => 'Kunci maintenance tidak valid']);
        }

        return Inertia::render('maintenance/down');
    }

    /**
     * Proses masuk maintenance mode
     */
    public function processDown(Request $request)
    {
        // Hanya bisa diakses jika sistem TIDAK dalam maintenance mode
        if (app()->isDownForMaintenance()) {
            abort(503, 'Service Unavailable');
        }

        $request->validate([
            'key' => ['required', 'string'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $secretToken = config('app.maintenance_bypass_start_key');

        if ($secretToken && $request->input('key') === $secretToken) {
            $message = $request->input('message', 'Saat ini kami sedang melakukan peningkatan layanan. Mohon bersabar.');

            // Simpan pesan maintenance ke storage
            Storage::put('framework/maintenance-message.txt', $message);

            // Get bypass secret from config
            $bypassSecret = config('app.maintenance_bypass_secret');

            // Aktifkan maintenance mode dengan secret bypass
            Artisan::call('down', [
                '--refresh' => 15,
                '--secret' => $bypassSecret,
            ]);

            // Remove prerendered template to allow middleware to work
            $this->removeMaintenancePrerenderTemplate();

            // Create bypass cookie for current user
            $bypassCookie = MaintenanceModeBypassCookie::create($bypassSecret);

            // Log aktivitas
            ActivityLog::log(
                action: 'Maintenance Mode Diaktifkan',
                type: 'system',
                description: 'Sistem masuk maintenance mode'.($message !== 'Saat ini kami sedang melakukan peningkatan layanan. Mohon bersabar.' ? ': '.$message : ''),
                status: 'warning',
                metadata: [
                    'message' => $message,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'refresh_interval' => 15,
                ]
            );

            // Return JSON response with cookie (similar to SystemSettingsController)
            return response()->json([
                'success' => true,
                'message' => 'Sistem berhasil masuk maintenance mode',
            ])->withCookie($bypassCookie);
        }

        return back()->withErrors([
            'key' => 'Kunci maintenance tidak valid',
        ]);
    }

    /**
     * Remove prerendered template from maintenance mode file
     */
    private function removeMaintenancePrerenderTemplate(): void
    {
        $downPath = storage_path('framework/down');

        if (! file_exists($downPath)) {
            return;
        }

        $data = json_decode(file_get_contents($downPath), true);

        if (! is_array($data) || ! array_key_exists('template', $data)) {
            return;
        }

        unset($data['template']);

        file_put_contents($downPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
