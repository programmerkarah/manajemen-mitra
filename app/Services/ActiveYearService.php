<?php

namespace App\Services;

use App\Models\Sbml;
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
        // Get years that have SBML data
        $yearsWithSbml = Sbml::where('status', 'aktif')
            ->distinct()
            ->pluck('tahun_anggaran')
            ->map(fn ($year) => (int) $year)
            ->sort()
            ->values()
            ->toArray();

        return array_reverse($yearsWithSbml);
    }

    public static function hasAvailableYears(): bool
    {
        return count(self::getAvailableYears()) > 0;
    }
}
