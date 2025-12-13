<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class ActiveYearService
{
    private const SESSION_KEY = 'active_year';

    public static function get(): int
    {
        return Session::get(self::SESSION_KEY, now()->year);
    }

    public static function set(int $year): void
    {
        Session::put(self::SESSION_KEY, $year);
    }

    public static function getAvailableYears(): array
    {
        $currentYear = now()->year;
        $years = [];

        // 5 tahun ke belakang dan 2 tahun ke depan
        for ($i = -5; $i <= 2; $i++) {
            $years[] = $currentYear + $i;
        }

        return array_reverse($years);
    }
}
