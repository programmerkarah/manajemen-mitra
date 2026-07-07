<?php

namespace App\Http\Requests;

use App\Models\SensusEkonomiPetugasReplacement;
use App\Models\Spk;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSensusEkonomiReplacementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isOperator();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'periode_alokasi_id' => ['nullable', 'exists:periode_alokasi,id'],
            'petugas_berhenti_id' => ['required', 'exists:petugas,id'],
            'petugas_pengganti_id' => [
                'nullable',
                'exists:petugas,id',
                'different:petugas_berhenti_id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $petugasId = (int) $value;

                    if ($this->isSensusEkonomiPetugas($petugasId)) {
                        $fail('Petugas pengganti tidak boleh berasal dari petugas sensus ekonomi.');

                        return;
                    }

                    if ($this->isActiveReplacementPengganti($petugasId)) {
                        $fail('Petugas pengganti sudah dipakai sebagai pengganti aktif pada replacement lain.');
                    }
                },
            ],
            'pml_cover_petugas_id' => [
                'nullable',
                'exists:petugas,id',
                'different:petugas_berhenti_id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $pmlPetugasId = (int) $value;
                    if (! $this->isSensusEkonomiPmlPetugas($pmlPetugasId)) {
                        $fail('Cover PML sementara harus petugas PML sensus ekonomi.');

                        return;
                    }

                    $spkLamaId = (int) $this->input('spk_lama_id');
                    if ($spkLamaId <= 0) {
                        return;
                    }

                    $spkLama = Spk::query()->with('alokasiPetugas.frameSampelAllocations.kegiatanFrameSampel', 'alokasiPetugas.petugas')->find($spkLamaId);
                    if (! $spkLama) {
                        return;
                    }

                    if (! $this->hasSameFrameSampelCoverage($spkLama, $pmlPetugasId)) {
                        $fail('Cover PML sementara harus memiliki wilayah tugas yang sama dengan petugas berhenti.');
                    }
                },
            ],
            'spk_lama_id' => [
                'required',
                'exists:spk,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    $petugasBerhentiId = (int) $this->input('petugas_berhenti_id');
                    if ($petugasBerhentiId <= 0) {
                        return;
                    }

                    $isValidSpk = Spk::query()
                        ->whereKey((int) $value)
                        ->where('petugas_id', $petugasBerhentiId)
                        ->where('addendum_number', 0)
                        ->whereIn('lampiran_template', ['sensus_ekonomi', 'pml_sensus_ekonomi'])
                        ->exists();

                    if (! $isValidSpk) {
                        $fail('SPK lama harus sesuai dengan petugas berhenti yang dipilih.');
                    }
                },
            ],
            'tanggal_berhenti' => ['required', 'date'],
            'tanggal_mulai_cover' => ['nullable', 'date', 'after_or_equal:tanggal_berhenti'],
            'tanggal_mulai_pkpp' => ['nullable', 'date', 'after_or_equal:tanggal_berhenti'],
            'detail_rows' => ['required', 'array', 'min:1'],
            'detail_rows.*.alokasi_petugas_frame_sampel_id' => [
                'required',
                'integer',
                'distinct',
                'exists:alokasi_petugas_frame_sampel,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $spkLamaId = (int) $this->input('spk_lama_id');
                    if ($spkLamaId <= 0 || blank($value)) {
                        return;
                    }

                    $spkLama = Spk::query()->with('alokasiPetugas.frameSampelAllocations')->find($spkLamaId);
                    if (! $spkLama) {
                        return;
                    }

                    $validFrameAllocationIds = $spkLama->alokasiPetugas?->frameSampelAllocations
                        ?->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all() ?? [];

                    if (! in_array((int) $value, $validFrameAllocationIds, true)) {
                        $fail('Detail realisasi harus berasal dari frame alokasi petugas berhenti yang dipilih.');
                    }
                },
            ],
            'detail_rows.*.realisasi_petugas_berhenti' => ['nullable', 'numeric', 'min:0'],
            'detail_rows.*.realisasi_pml_cover' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:draft,pml_cover,pengganti_ditetapkan,selesai,dibatalkan'],
            'catatan' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'periode_alokasi_id.exists' => 'Periode alokasi tidak valid.',
            'petugas_berhenti_id.required' => 'Petugas berhenti wajib dipilih.',
            'petugas_berhenti_id.exists' => 'Petugas berhenti tidak valid.',
            'petugas_pengganti_id.exists' => 'Petugas pengganti tidak valid.',
            'petugas_pengganti_id.different' => 'Petugas pengganti harus berbeda dari petugas berhenti.',
            'pml_cover_petugas_id.exists' => 'Petugas cover PML tidak valid.',
            'pml_cover_petugas_id.different' => 'Petugas cover PML harus berbeda dari petugas berhenti.',
            'spk_lama_id.required' => 'SPK lama wajib dipilih.',
            'spk_lama_id.exists' => 'SPK lama tidak valid.',
            'tanggal_berhenti.required' => 'Tanggal berhenti wajib diisi.',
            'tanggal_mulai_cover.after_or_equal' => 'Tanggal mulai cover harus sama atau setelah tanggal berhenti.',
            'tanggal_mulai_pkpp.after_or_equal' => 'Tanggal mulai PKPP harus sama atau setelah tanggal berhenti.',
            'detail_rows.required' => 'Detail realisasi per frame wajib diisi.',
            'detail_rows.array' => 'Format detail realisasi tidak valid.',
            'detail_rows.min' => 'Minimal satu detail realisasi frame harus diisi.',
            'detail_rows.*.alokasi_petugas_frame_sampel_id.required' => 'Frame alokasi wajib dipilih.',
            'detail_rows.*.alokasi_petugas_frame_sampel_id.exists' => 'Frame alokasi tidak valid.',
            'detail_rows.*.realisasi_petugas_berhenti.min' => 'Realisasi petugas berhenti tidak boleh negatif.',
            'detail_rows.*.realisasi_pml_cover.min' => 'Realisasi PML cover tidak boleh negatif.',
            'status.in' => 'Status replacement tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $detailRows = collect($this->input('detail_rows', []))
            ->map(function ($row) {
                if (! is_array($row)) {
                    return [];
                }

                $row['realisasi_petugas_berhenti'] = $row['realisasi_petugas_berhenti'] ?? 0;
                $row['realisasi_pml_cover'] = $row['realisasi_pml_cover'] ?? 0;

                return $row;
            })
            ->values()
            ->all();

        $this->merge([
            'detail_rows' => $detailRows,
            'realisasi_petugas_berhenti' => $this->input('realisasi_petugas_berhenti', 0),
            'realisasi_pml_cover' => $this->input('realisasi_pml_cover', 0),
        ]);
    }

    private function isSensusEkonomiPetugas(int $petugasId): bool
    {
        return Spk::query()
            ->where('petugas_id', $petugasId)
            ->where('addendum_number', 0)
            ->whereIn('lampiran_template', ['sensus_ekonomi', 'pml_sensus_ekonomi'])
            ->exists();
    }

    private function isActiveReplacementPengganti(int $petugasId): bool
    {
        return SensusEkonomiPetugasReplacement::query()
            ->where('petugas_pengganti_id', $petugasId)
            ->where('status', '!=', 'dibatalkan')
            ->exists();
    }

    private function isSensusEkonomiPmlPetugas(int $petugasId): bool
    {
        return Spk::query()
            ->where('petugas_id', $petugasId)
            ->where('addendum_number', 0)
            ->where('lampiran_template', 'pml_sensus_ekonomi')
            ->exists();
    }

    /**
     * @return array<int, int>
     */
    private function getSpkFrameSampelIds(Spk $spk): array
    {
        $alokasi = $spk->alokasiPetugas;
        if (! $alokasi) {
            return [];
        }

        $alokasi->loadMissing('frameSampelAllocations');

        return $alokasi->frameSampelAllocations
            ->pluck('kegiatan_frame_sampel_id')
            ->filter(fn ($id) => filled($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function hasSameFrameSampelCoverage(Spk $spkLama, int $pmlPetugasId): bool
    {
        $stoppedFrameSampelIds = $this->getSpkFrameSampelIds($spkLama);
        if (empty($stoppedFrameSampelIds)) {
            return false;
        }

        $periodeAlokasiId = (int) ($spkLama->alokasiPetugas?->periode_alokasi_id ?? 0);
        if ($periodeAlokasiId <= 0) {
            return false;
        }

        $candidatePmlSpks = Spk::query()
            ->with('alokasiPetugas.frameSampelAllocations')
            ->where('petugas_id', $pmlPetugasId)
            ->where('addendum_number', 0)
            ->where('lampiran_template', 'pml_sensus_ekonomi')
            ->whereHas('alokasiPetugas', fn ($query) => $query->where('periode_alokasi_id', $periodeAlokasiId))
            ->get();

        $pmlFrameSampelIds = $candidatePmlSpks
            ->flatMap(fn (Spk $spk) => $this->getSpkFrameSampelIds($spk))
            ->unique()
            ->values()
            ->all();

        if (empty($pmlFrameSampelIds)) {
            return false;
        }

        return count(array_intersect($stoppedFrameSampelIds, $pmlFrameSampelIds)) > 0;
    }
}
