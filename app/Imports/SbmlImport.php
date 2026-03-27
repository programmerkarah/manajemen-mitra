<?php

namespace App\Imports;

use App\Models\Sbml;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SbmlImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use Importable;

    protected array $errors = [];

    protected int $successCount = 0;

    public function __construct(protected int $tahun) {}

    public function collection(Collection $rows)
    {
        $rowNumber = 1;
        $validRows = [];

        foreach ($rows as $row) {
            $rowNumber++;

            // Map display labels to database values
            $jenis_kegiatan = $this->mapJenisKegiatan($row['jenis_kegiatan'] ?? '');
            $status_kepegawaian = $this->mapStatusKepegawaian($row['status_kepegawaian'] ?? '');
            $jenis_penugasan = $this->mapJenisPenugasan($row['jenis_penugasan'] ?? '');

            // Silently skip note/footer rows (e.g. "Catatan: ..." rows added by the template)
            if (! in_array($jenis_kegiatan, ['sensus', 'survei'])) {
                continue;
            }

            $data = [
                'tahun_anggaran' => $this->tahun,
                'jenis_kegiatan' => $jenis_kegiatan,
                'status_kepegawaian' => $status_kepegawaian,
                'jenis_penugasan' => $jenis_penugasan,
                'honor_max' => isset($row['honor_maksimal_idr']) ? (float) str_replace(['.', ','], ['', '.'], trim((string) $row['honor_maksimal_idr'])) : 0,
                'status' => strtolower(trim($row['status'] ?? 'aktif')) === 'aktif' ? 'aktif' : 'nonaktif',
                'keterangan' => $row['keterangan'] ?? null,
            ];

            // Validate the row
            $validator = Validator::make($data, [
                'tahun_anggaran' => ['required', 'integer'],
                'jenis_kegiatan' => ['required', Rule::in(['sensus', 'survei'])],
                'status_kepegawaian' => ['required', Rule::in(['organik', 'non_organik'])],
                'jenis_penugasan' => ['required', Rule::in(['pcl_ppl', 'pml', 'pengolahan', 'pengawas_pengolahan', 'koseka'])],
                'honor_max' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
                'keterangan' => ['nullable', 'string'],
            ], [
                'jenis_kegiatan.in' => 'Jenis Kegiatan harus berupa Sensus atau Survei',
                'status_kepegawaian.required' => 'Status kepegawaian wajib diisi.',
                'status_kepegawaian.in' => 'Status Kepegawaian harus berupa Organik atau Non-Organik',
                'jenis_penugasan.required' => 'Jenis penugasan wajib diisi.',
                'jenis_penugasan.in' => 'Jenis Penugasan tidak valid',
                'honor_max.required' => 'Honor Maksimal harus diisi',
                'honor_max.numeric' => 'Honor Maksimal harus berupa angka',
                'honor_max.min' => 'Honor Maksimal harus lebih dari 0',
            ]);

            if ($validator->fails()) {
                $this->errors[$rowNumber] = $validator->errors()->all();

                continue;
            }

            $validRows[] = $data;
        }

        if (! empty($this->errors)) {
            throw new \RuntimeException('Validasi import SBML gagal: '.json_encode($this->errors));
        }

        // Only delete and insert when all rows pass validation (wrapped in transaction)
        DB::transaction(function () use ($validRows) {
            Sbml::where('tahun_anggaran', $this->tahun)->delete();

            foreach ($validRows as $data) {
                Sbml::create($data);
                $this->successCount++;
            }
        });
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    private function mapJenisKegiatan(string $value): string
    {
        return match (strtolower(trim($value))) {
            'sensus' => 'sensus',
            'survei' => 'survei',
            default => $value,
        };
    }

    private function mapStatusKepegawaian(string $value): string
    {
        $value = strtolower(trim($value));

        return match (true) {
            str_contains($value, 'non') => 'non_organik',
            str_contains($value, 'organik') || $value === 'organik' => 'organik',
            $value === 'non_organik' => 'non_organik',
            default => $value,
        };
    }

    private function mapJenisPenugasan(string $value): string
    {
        $value = strtolower(trim($value));

        // Match against various possible input formats
        if (str_contains($value, 'pcl') || str_contains($value, 'ppl') || str_contains($value, 'pencacahan')) {
            return 'pcl_ppl';
        }
        if (str_contains($value, 'pml') || str_contains($value, 'pemeriksaan')) {
            return 'pml';
        }
        if (str_contains($value, 'pengawas')) {
            return 'pengawas_pengolahan';
        }
        if (str_contains($value, 'pengolahan') || str_contains($value, 'data')) {
            return 'pengolahan';
        }
        if (str_contains($value, 'koseka') || str_contains($value, 'koordinator')) {
            return 'koseka';
        }

        return $value;
    }
}
