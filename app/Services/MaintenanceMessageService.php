<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class MaintenanceMessageService
{
    private const PATH = 'framework/maintenance-message.txt';

    public function get(): ?string
    {
        if (Storage::exists(self::PATH)) {
            $message = trim((string) Storage::get(self::PATH));

            return $message !== '' ? $message : null;
        }

        $message = trim((string) Config::get('app.maintenance_message', ''));

        return $message !== '' ? $message : null;
    }

    public function put(?string $message): void
    {
        $message = trim((string) $message);

        if ($message === '') {
            Storage::delete(self::PATH);

            return;
        }

        Storage::put(self::PATH, $message);
    }

    public function forget(): void
    {
        Storage::delete(self::PATH);
    }
}
