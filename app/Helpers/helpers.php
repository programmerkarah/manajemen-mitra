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
            return 'nol';
        } else if ($nilai == 10) {
            return 'sepuluh';
        } else if ($nilai == 11) {
            return 'sebelas';
        } else if ($nilai < 12) {
            $temp = ' ' . $huruf[$nilai];
        } elseif ($nilai < 20) {
            $temp = terbilang($nilai - 10) . ' belas';
        } elseif ($nilai < 100) {
            $temp = terbilang($nilai / 10) . ' puluh' . terbilang($nilai % 10);
        } elseif ($nilai < 200) {
            $temp = ' seratus' . terbilang($nilai - 100);
        } elseif ($nilai < 1000) {
            $temp = terbilang($nilai / 100) . ' ratus' . terbilang($nilai % 100);
        } elseif ($nilai < 2000) {
            $temp = ' seribu' . terbilang($nilai - 1000);
        } elseif ($nilai < 1000000) {
            $temp = terbilang($nilai / 1000) . ' ribu' . terbilang($nilai % 1000);
        } elseif ($nilai < 1000000000) {
            $temp = terbilang($nilai / 1000000) . ' juta' . terbilang($nilai % 1000000);
        } elseif ($nilai < 1000000000000) {
            $temp = terbilang($nilai / 1000000000) . ' milyar' . terbilang(fmod($nilai, 1000000000));
        } elseif ($nilai < 1000000000000000) {
            $temp = terbilang($nilai / 1000000000000) . ' trilyun' . terbilang(fmod($nilai, 1000000000000));
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
