<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DasarHukum extends Model
{
    use SoftDeletes;

    protected $table = 'dasar_hukum';

    protected $fillable = [
        'kategori',
        'instansi',
        'nomor',
        'tentang',
        'tahun',
        'nomor_ln',
        'tahun_ln',
        'nomor_tln',
        'nomor_bn',
        'tahun_bn',
        'jenis',
        'induk_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'tahun_ln' => 'integer',
            'tahun_bn' => 'integer',
        ];
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(DasarHukum::class, 'induk_id');
    }

    public function perubahan(): HasMany
    {
        return $this->hasMany(DasarHukum::class, 'induk_id');
    }

    /** Build the kategori prefix label (e.g. "Peraturan Pemerintah"). */
    public function buildKategoriLabel(): string
    {
        return match ($this->kategori) {
            'undang_undang' => 'Undang-Undang',
            'peraturan_pemerintah' => 'Peraturan Pemerintah',
            'peraturan_presiden' => 'Peraturan Presiden',
            'peraturan_menteri_badan' => 'Peraturan '.($this->instansi && stripos($this->instansi, 'badan') === 0 ? '' : 'Menteri ').$this->instansi,
            'keputusan_menteri_kepala_badan' => 'Keputusan '.($this->instansi && stripos($this->instansi, 'badan') === 0 ? 'Kepala ' : 'Menteri ').$this->instansi,
            'peraturan_kepala_badan' => 'Peraturan Kepala Badan Pusat Statistik',
            default => $this->kategori,
        };
    }

    /** Build the Lembaran/Berita Negara parenthetical suffix for this record. */
    private function buildLembaranSuffix(): string
    {
        if (in_array($this->kategori, ['undang_undang', 'peraturan_pemerintah', 'peraturan_presiden'])) {
            if ($this->nomor_ln && $this->tahun_ln) {
                $tln = $this->nomor_tln
                    ? ', Tambahan Lembaran Negara Republik Indonesia Nomor '.$this->nomor_tln
                    : '';

                return ' (Lembaran Negara Republik Indonesia Tahun '.$this->tahun_ln.' Nomor '.$this->nomor_ln.$tln.')';
            }
        }

        if ($this->kategori === 'peraturan_menteri_badan') {
            if ($this->nomor_bn && $this->tahun_bn) {
                return ' (Berita Negara Republik Indonesia Tahun '.$this->tahun_bn.' Nomor '.$this->nomor_bn.')';
            }
        }

        return '';
    }

    /** Build the full text for this record alone (no amendment prefix). */
    public function buildSingleTeks(): string
    {
        return $this->buildKategoriLabel()
            .' Nomor '.$this->nomor
            .' Tahun '.$this->tahun
            .' tentang '.$this->tentang
            .$this->buildLembaranSuffix();
    }

    /**
     * Build the full formatted legal citation text.
     * For amendments: "Original … sebagaimana telah diubah dengan Amendment …"
     */
    public function getFormattedTeks(): string
    {
        if ($this->jenis === 'perubahan' && $this->induk !== null) {
            $amendmentCount = static::where('induk_id', $this->induk_id)
                ->where('jenis', 'perubahan')
                ->count();

            $phrase = $amendmentCount > 1
                ? 'sebagaimana telah beberapa kali diubah terakhir dengan'
                : 'sebagaimana telah diubah dengan';

            return $this->induk->buildSingleTeks()
                .' '.$phrase.' '
                .$this->buildSingleTeks();
        }

        return $this->buildSingleTeks();
    }
}
