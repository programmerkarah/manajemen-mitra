<?php

if (! function_exists('terbilang')) {
    /**
     * Convert number to Indonesian text
     */
    function terbilang($nilai)
    {
        $nilai = abs($nilai);
        $huruf = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        $temp = '';

        if ($nilai == 0) {
            return '';
        } elseif ($nilai < 10) {
            return $huruf[$nilai].' ';
        } elseif ($nilai == 10) {
            return 'sepuluh ';
        } elseif ($nilai == 11) {
            return 'sebelas ';
        } elseif ($nilai < 20) {
            $temp = terbilang($nilai - 10).'belas ';
        } elseif ($nilai < 100) {
            $temp = terbilang($nilai / 10).'puluh '.terbilang($nilai % 10);
        } elseif ($nilai < 200) {
            $temp = 'seratus '.terbilang($nilai - 100);
        } elseif ($nilai < 1000) {
            $temp = terbilang($nilai / 100).'ratus '.terbilang($nilai % 100);
        } elseif ($nilai < 2000) {
            $temp = 'seribu '.terbilang($nilai - 1000);
        } elseif ($nilai < 1000000) {
            $temp = terbilang($nilai / 1000).'ribu '.terbilang($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            $temp = terbilang($nilai / 1000000).'juta '.terbilang($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            $temp = terbilang($nilai / 1000000000).'milyar '.terbilang(fmod($nilai, 1000000000));
        } elseif ($nilai < 1000000000000000) {
            $temp = terbilang($nilai / 1000000000000).'trilyun '.terbilang(fmod($nilai, 1000000000000));
        }

        return $temp;
    }
}

if (! function_exists('tanggalTerbilang')) {
    /**
     * Convert date number to Indonesian text (1-31)
     */
    function tanggalTerbilang($tanggal)
    {
        return trim(terbilang($tanggal));
    }
}

if (! function_exists('tahunTerbilang')) {
    /**
     * Convert year to Indonesian text
     */
    function tahunTerbilang($tahun)
    {
        return trim(terbilang($tahun));
    }
}

if (! function_exists('effectiveUser')) {
    /**
     * Get the effective user for authorization checks.
     * Returns the viewed user if viewing as another user, otherwise returns the authenticated user.
     */
    function effectiveUser(?Illuminate\Http\Request $request = null): ?\App\Models\User
    {
        $request = $request ?? request();

        // If session has view_as_user_id, always use that user
        if (session()->has('view_as_user_id')) {
            return \App\Models\User::find(session('view_as_user_id'));
        }

        // Fallback to request attribute (for legacy/edge cases)
        $viewAsUser = $request->attributes->get('view_as_user');
        if ($viewAsUser) {
            return $viewAsUser;
        }

        // Return the authenticated user
        return $request->user();
    }
}
