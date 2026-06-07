<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix two classes of stale spk_id on the bast table:
     *
     * Case 1 - "direvisi" periode: a PeriodeAlokasi is revised, its status becomes
     *   'direvisi' and a new PeriodeAlokasi (status 'perubahan'/'dikirim') is created.
     *   BAST records remain attached to the original (direvisi) period's SPK.
     *   Fix: point bast.spk_id and bast.periode_alokasi_id to the latest SPK
     *   in the replacement periode for the same petugas+kegiatan+bulan+tahun.
     *
     * Case 2 - "addendum": an addendum SPK is created for the same alokasi, but
     *   the BAST still references the original (addendum_number=0) SPK.
     *   Fix: point bast.spk_id to the SPK with the highest addendum_number
     *   for the same alokasi_petugas_id.
     */
    public function up(): void
    {
        // Case 1: BAST attached to a 'direvisi' periode's SPK.
        // Update spk_id and periode_alokasi_id to the replacement active periode.
        DB::statement("
            UPDATE bast b
            JOIN spk s_old ON b.spk_id = s_old.id
            JOIN alokasi_petugas ap_old ON s_old.alokasi_petugas_id = ap_old.id
            JOIN periode_alokasi pa_old
                ON ap_old.periode_alokasi_id = pa_old.id
                AND pa_old.status = 'direvisi'
            JOIN alokasi_petugas ap_new ON ap_new.petugas_id = ap_old.petugas_id
            JOIN periode_alokasi pa_new
                ON ap_new.periode_alokasi_id = pa_new.id
                AND pa_new.kegiatan_id = pa_old.kegiatan_id
                AND pa_new.bulan = pa_old.bulan
                AND pa_new.tahun = pa_old.tahun
                AND pa_new.status IN ('dikirim', 'perubahan')
            JOIN spk s_new
                ON s_new.alokasi_petugas_id = ap_new.id
                AND s_new.addendum_number = (
                    SELECT MAX(s2.addendum_number)
                    FROM spk s2
                    WHERE s2.alokasi_petugas_id = ap_new.id
                )
            SET b.spk_id = s_new.id,
                b.periode_alokasi_id = pa_new.id
            WHERE b.deleted_at IS NULL
        ");

        // Case 2: BAST attached to a non-latest SPK addendum within the same alokasi.
        // Update spk_id to the SPK with the highest addendum_number for that alokasi.
        DB::statement("
            UPDATE bast b
            JOIN spk s ON b.spk_id = s.id
            JOIN spk s_latest
                ON s_latest.alokasi_petugas_id = s.alokasi_petugas_id
                AND s_latest.addendum_number = (
                    SELECT MAX(s2.addendum_number)
                    FROM spk s2
                    WHERE s2.alokasi_petugas_id = s.alokasi_petugas_id
                )
            SET b.spk_id = s_latest.id
            WHERE s.addendum_number < (
                SELECT MAX(s2.addendum_number)
                FROM spk s2
                WHERE s2.alokasi_petugas_id = s.alokasi_petugas_id
            )
            AND b.deleted_at IS NULL
        ");
    }

    /**
     * Data fixes are not reversible - the original spk_id values are not stored.
     */
    public function down(): void
    {
        //
    }
};
