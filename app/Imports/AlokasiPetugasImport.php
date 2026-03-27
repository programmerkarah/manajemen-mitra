<?php

namespace App\Imports;

use App\Models\AlokasiPetugas;
use App\Models\Petugas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AlokasiPetugasImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use Importable;

    protected array $errors = [];

    protected int $successCount = 0;

    public function __construct(
        protected int $periodeAlokasiId,
        protected bool $isCreate = true,
    ) {}

    public function collection(Collection $rows)
    {
        $rowNumber = 1;

        // Delete existing entries if this is an edit operation
        if (! $this->isCreate) {
            AlokasiPetugas::where('periode_alokasi_id', $this->periodeAlokasiId)->delete();
        }

        foreach ($rows as $row) {
            $rowNumber++;

            // Find petugas by NIK
            $nik = trim((string) ($row['nik_petugas'] ?? ''));
            if (! $nik) {
                $this->errors[$rowNumber] = ['NIK Petugas tidak boleh kosong'];

                continue;
            }

            $petugas = Petugas::where('nik', $nik)->first();
            if (! $petugas) {
                $this->errors[$rowNumber] = ["Petugas dengan NIK {$nik} tidak ditemukan"];

                continue;
            }

            // Map display labels to database values
            $status_kepegawaian = $this->mapStatusKepegawaian($row['status_kepegawaian'] ?? '');
            $peran = $this->mapPeran($row['jenis_penugasan'] ?? '');

            $data = [
                'periode_alokasi_id' => $this->periodeAlokasiId,
                'petugas_id' => $petugas->id,
                'status_kepegawaian' => $status_kepegawaian,
                'peran' => $peran,
                'jumlah_satuan' => isset($row['jumlah_satuan_pencacahan']) ? (int) str_replace(['.', ','], ['', ''], trim((string) $row['jumlah_satuan_pencacahan'])) : 0,
                'total_honor' => isset($row['honor_pencacahan']) ? (float) str_replace(['.', ','], ['', '.'], trim((string) $row['honor_pencacahan'])) : 0,
                'is_partial_payment' => strtolower(trim($row['pembayaran_parsial'] ?? 'tidak')) === 'ya',
                'partial_jumlah_satuan' => isset($row['jumlah_satuan_parsial']) ? (int) str_replace(['.', ','], ['', ''], trim((string) $row['jumlah_satuan_parsial'])) : null,
                'estimasi_honor_partial' => isset($row['honor_parsial']) ? (float) str_replace(['.', ','], ['', '.'], trim((string) $row['honor_parsial'])) : null,
                'jumlah_satuan_listing' => isset($row['jumlah_satuan_listing']) ? (int) str_replace(['.', ','], ['', ''], trim((string) $row['jumlah_satuan_listing'])) : null,
                'total_honor_listing' => isset($row['honor_listing']) ? (float) str_replace(['.', ','], ['', '.'], trim((string) $row['honor_listing'])) : null,
                'non_response' => isset($row['non_response_pencacahan']) ? (int) str_replace(['.', ','], ['', ''], trim((string) $row['non_response_pencacahan'])) : null,
                'non_response_listing' => isset($row['non_response_listing']) ? (int) str_replace(['.', ','], ['', ''], trim((string) $row['non_response_listing'])) : null,
                'catatan' => $row['catatan'] ?? null,
            ];

            // Validate the row
            $validator = Validator::make($data, [
                'periode_alokasi_id' => ['required', 'exists:periode_alokasi,id'],
                'petugas_id' => ['required', 'exists:petugas,id'],
                'status_kepegawaian' => ['required', Rule::in(['organik', 'non_organik'])],
                'peran' => ['required', Rule::in(['pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan', 'koseka'])],
                'jumlah_satuan' => ['required', 'integer', 'min:0'],
                'total_honor' => ['required', 'numeric', 'min:0'],
                'is_partial_payment' => ['boolean'],
                'partial_jumlah_satuan' => ['nullable', 'integer', 'min:0'],
                'estimasi_honor_partial' => ['nullable', 'numeric', 'min:0'],
                'jumlah_satuan_listing' => ['nullable', 'integer', 'min:0'],
                'total_honor_listing' => ['nullable', 'numeric', 'min:0'],
                'non_response' => ['nullable', 'integer', 'min:0'],
                'non_response_listing' => ['nullable', 'integer', 'min:0'],
                'catatan' => ['nullable', 'string'],
            ], [
                'status_kepegawaian.in' => 'Status Kepegawaian tidak valid',
                'peran.in' => 'Jenis Penugasan tidak valid',
                'jumlah_satuan.required' => 'Jumlah Satuan Pencacahan harus diisi',
                'total_honor.required' => 'Honor Pencacahan harus diisi',
            ]);

            if ($validator->fails()) {
                $this->errors[$rowNumber] = $validator->errors()->all();

                continue;
            }

            // Check for duplicate petugas in same periode
            $existing = AlokasiPetugas::where('periode_alokasi_id', $this->periodeAlokasiId)
                ->where('petugas_id', $petugas->id)
                ->first();

            if ($existing && $this->isCreate) {
                $this->errors[$rowNumber] = ["Petugas {$petugas->nama} sudah dialokasikan untuk periode ini"];

                continue;
            }

            try {
                AlokasiPetugas::create($data);
                $this->successCount++;
            } catch (\Exception $e) {
                $this->errors[$rowNumber] = ["Gagal menyimpan: {$e->getMessage()}"];
            }
        }

        if (! empty($this->errors)) {
            \Log::warning('AlokasiPetugasImport errors:', ['errors' => $this->errors]);
            throw new \Maatwebsite\Excel\Validators\ValidationException([], $this->errors);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    private function mapStatusKepegawaian(string $value): string
    {
        $value = strtolower(trim($value));

        return match (true) {
            str_contains($value, 'organik') && str_contains($value, 'pns') => 'organik',
            str_contains($value, 'organik') => 'organik',
            str_contains($value, 'non') => 'non_organik',
            $value === 'organik' => 'organik',
            $value === 'non_organik' => 'non_organik',
            default => $value,
        };
    }

    private function mapPeran(string $value): string
    {
        $value = strtolower(trim($value));

        // Match against various possible input formats
        if (str_contains($value, 'pcl') || str_contains($value, 'ppl') || str_contains($value, 'pencacahan')) {
            return 'pcl_ppl';
        }
        if (str_contains($value, 'pml') || str_contains($value, 'pemeriksaan')) {
            return 'pml';
        }
        if (str_contains($value, 'pengolahan') || str_contains($value, 'data')) {
            return 'pengolahan';
        }
        if (str_contains($value, 'pengawas')) {
            return 'pengawas_pengolahan';
        }
        if (str_contains($value, 'koseka') || str_contains($value, 'koordinator')) {
            return 'koseka';
        }

        return $value;
    }
}
