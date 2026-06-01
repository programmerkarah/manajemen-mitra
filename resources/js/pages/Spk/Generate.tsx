import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { previewFileFromPost } from '@/utils/downloadUtils';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, FileText, Loader2 } from 'lucide-react';
import { useState } from 'react';

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string;
    jenis_petugas: 'organik' | 'non_organik';
}

interface KegiatanPeran {
    kegiatan_kode: string;
    kegiatan_nama: string;
    peran: string;
}

interface AlokasiPetugas {
    alokasi_id: number;
    alokasi_hashed_id: string;
    petugas: Petugas;
    jumlah_kegiatan: number;
    kegiatan_list: KegiatanPeran[];
    total_honor: number;
}

interface Kegiatan {
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    tahun_anggaran: number;
}

interface PeriodeAlokasi {
    id: number;
    hashed_id: string;
    bulan: number;
    bulan_label: string;
    tahun: number;
    kegiatan: Kegiatan;
}

interface GenerateProps {
    periode: PeriodeAlokasi;
    petugas_list: AlokasiPetugas[];
    has_draft_periode: boolean;
    next_nomor_urut: number;
    is_regenerate: boolean;
    default_tanggal_spk: string | null;
    existing_spk_map: Record<number, { nomor_spk: string; nomor_urut: number }>;
    last_nomor_urut_in_month: number;
    uses_suffix_for_new_petugas: boolean;
}

export default function Generate({
    periode,
    petugas_list,
    has_draft_periode,
    next_nomor_urut,
    is_regenerate,
    default_tanggal_spk,
    existing_spk_map,
    last_nomor_urut_in_month,
    uses_suffix_for_new_petugas,
}: GenerateProps) {
    const [formData, setFormData] = useState({
        tanggal_spk: default_tanggal_spk || '',
    });
    const [selectedPetugas, setSelectedPetugas] = useState<string[]>([]);
    const [processing, setProcessing] = useState(false);
    const [showFormModal, setShowFormModal] = useState(false);
    const [modalMessage, setModalMessage] = useState('');
    const [showSuccessModal, setShowSuccessModal] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');

    const isSensusEkonomi =
        periode.kegiatan.jenis_kegiatan === 'sensus' &&
        periode.kegiatan.nama_kegiatan.toLowerCase().trim() ===
            'sensus ekonomi';

    const formatNomorSpk = (noUrut: number, tahun: number): string => {
        if (isSensusEkonomi) {
            return `B-${String(noUrut).padStart(3, '0')}/SPK-SE2026/1373/PL.200/${tahun}`;
        }
        return `PPIS/13730/${noUrut}/K/${tahun}`;
    };

    const formatNomorSpkSuffix = (
        baseUrut: number,
        suffix: string,
        tahun: number,
    ): string => {
        if (isSensusEkonomi) {
            return `B-${String(baseUrut).padStart(3, '0')}${suffix}/SPK-SE2026/1373/PL.200/${tahun}`;
        }
        return `PPIS/13730/${baseUrut}${suffix}/K/${tahun}`;
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Perjanjian Kerja', href: '/spk' },
        {
            title: `Generate Perjanjian Kerja - ${periode.bulan_label} ${periode.tahun}`,
            href: '#',
        },
    ];

    const handleSelectAll = () => {
        if (selectedPetugas.length === petugas_list.length) {
            setSelectedPetugas([]);
        } else {
            setSelectedPetugas(petugas_list.map((a) => a.petugas.hashed_id));
        }
    };

    const handlePetugasToggle = (hashedId: string) => {
        setSelectedPetugas((prev) =>
            prev.includes(hashedId)
                ? prev.filter((id) => id !== hashedId)
                : [...prev, hashedId],
        );
    };

    const handlePreview = async (alokasi: AlokasiPetugas) => {
        if (!formData.tanggal_spk) {
            setModalMessage(
                'Lengkapi form Tanggal Perjanjian Kerja terlebih dahulu',
            );
            setShowFormModal(true);
            return;
        }

        // Get year from tanggal_spk
        const tahunSpk = new Date(formData.tanggal_spk).getFullYear();

        // Check if this petugas already has an existing SPK
        const existingSpk = existing_spk_map[alokasi.petugas.id];
        let nomorSpk: string;

        if (existingSpk) {
            // Use existing SPK number from database
            nomorSpk = existingSpk.nomor_spk;
        } else if (is_regenerate) {
            // New petugas in regenerate mode
            // Need to calculate position among new petugas only
            const sortedPetugas = [...petugas_list].sort((a, b) =>
                a.petugas.nama.localeCompare(b.petugas.nama),
            );

            // Filter only new petugas (those without existing SPK)
            const newPetugasOnly = sortedPetugas.filter(
                (p) => !existing_spk_map[p.petugas.id],
            );
            const indexAmongNew = newPetugasOnly.findIndex(
                (p) => p.petugas.hashed_id === alokasi.petugas.hashed_id,
            );

            if (uses_suffix_for_new_petugas) {
                // Use suffix mode: 3A, 3B, 3C...
                const suffix = String.fromCharCode(65 + indexAmongNew); // A, B, C...
                nomorSpk = formatNomorSpkSuffix(
                    last_nomor_urut_in_month,
                    suffix,
                    tahunSpk,
                );
            } else {
                // Use sequential mode: 4, 5, 6...
                const noUrut = last_nomor_urut_in_month + 1 + indexAmongNew;
                nomorSpk = formatNomorSpk(noUrut, tahunSpk);
            }
        } else {
            // First time generation - use sequential numbering
            const sortedPetugas = [...petugas_list].sort((a, b) =>
                a.petugas.nama.localeCompare(b.petugas.nama),
            );

            const petugasIndex = sortedPetugas.findIndex(
                (a) => a.petugas.hashed_id === alokasi.petugas.hashed_id,
            );
            const noUrut = next_nomor_urut + petugasIndex;
            nomorSpk = formatNomorSpk(noUrut, tahunSpk);
        }

        const sanitizedPetugasName = alokasi.petugas.nama.replace(
            /[^A-Za-z0-9_-]/g,
            '_',
        );

        try {
            await previewFileFromPost(
                `/spk/periode/${periode.hashed_id}/petugas/${alokasi.petugas.hashed_id}/preview`,
                {
                    nomor_spk: nomorSpk,
                    tanggal_spk: formData.tanggal_spk,
                },
                `Preview_SPK_${sanitizedPetugasName}.pdf`,
            );
        } catch {
            setModalMessage('Gagal mengunduh file preview Perjanjian Kerja.');
            setShowFormModal(true);
        }
    };

    const handlePreviewMain = async (alokasi: AlokasiPetugas) => {
        if (!formData.tanggal_spk) {
            setModalMessage(
                'Lengkapi form terlebih dahulu (Tanggal Perjanjian Kerja wajib diisi)',
            );
            setShowFormModal(true);
            return;
        }

        // Get year from tanggal_spk
        const tahunSpk = new Date(formData.tanggal_spk).getFullYear();

        // Check if this petugas already has an existing SPK
        const existingSpk = existing_spk_map[alokasi.petugas.id];
        let nomorSpk: string;

        if (existingSpk) {
            // Use existing SPK number from database
            nomorSpk = existingSpk.nomor_spk;
        } else if (is_regenerate) {
            // New petugas in regenerate mode
            const sortedPetugas = [...petugas_list].sort((a, b) =>
                a.petugas.nama.localeCompare(b.petugas.nama),
            );

            const newPetugasOnly = sortedPetugas.filter(
                (p) => !existing_spk_map[p.petugas.id],
            );
            const indexAmongNew = newPetugasOnly.findIndex(
                (p) => p.petugas.hashed_id === alokasi.petugas.hashed_id,
            );

            if (uses_suffix_for_new_petugas) {
                // Use suffix mode: 3A, 3B, 3C...
                const suffix = String.fromCharCode(65 + indexAmongNew); // A, B, C...
                nomorSpk = formatNomorSpkSuffix(
                    last_nomor_urut_in_month,
                    suffix,
                    tahunSpk,
                );
            } else {
                // Use sequential mode: 4, 5, 6...
                const noUrut = last_nomor_urut_in_month + 1 + indexAmongNew;
                nomorSpk = formatNomorSpk(noUrut, tahunSpk);
            }
        } else {
            // First time generation - use sequential numbering
            const sortedPetugas = [...petugas_list].sort((a, b) =>
                a.petugas.nama.localeCompare(b.petugas.nama),
            );

            const petugasIndex = sortedPetugas.findIndex(
                (a) => a.petugas.hashed_id === alokasi.petugas.hashed_id,
            );
            const noUrut = next_nomor_urut + petugasIndex;
            nomorSpk = formatNomorSpk(noUrut, tahunSpk);
        }

        const sanitizedPetugasName = alokasi.petugas.nama.replace(
            /[^A-Za-z0-9_-]/g,
            '_',
        );

        try {
            await previewFileFromPost(
                `/spk/periode/${periode.hashed_id}/petugas/${alokasi.petugas.hashed_id}/preview-main`,
                {
                    nomor_spk: nomorSpk,
                    tanggal_spk: formData.tanggal_spk,
                },
                `Preview_SPK_Main_${sanitizedPetugasName}.pdf`,
            );
        } catch {
            setModalMessage(
                'Gagal mengunduh file preview Perjanjian Kerja (utama).',
            );
            setShowFormModal(true);
        }
    };

    const handlePreviewLampiran = async (alokasi: AlokasiPetugas) => {
        if (!formData.tanggal_spk) {
            setModalMessage(
                'Lengkapi form terlebih dahulu (Tanggal Perjanjian Kerja wajib diisi)',
            );
            setShowFormModal(true);
            return;
        }

        // Get year from tanggal_spk
        const tahunSpk = new Date(formData.tanggal_spk).getFullYear();

        // Check if this petugas already has an existing SPK
        const existingSpk = existing_spk_map[alokasi.petugas.id];
        let nomorSpk: string;

        if (existingSpk) {
            // Use existing SPK number from database
            nomorSpk = existingSpk.nomor_spk;
        } else if (is_regenerate) {
            // New petugas in regenerate mode
            const sortedPetugas = [...petugas_list].sort((a, b) =>
                a.petugas.nama.localeCompare(b.petugas.nama),
            );

            const newPetugasOnly = sortedPetugas.filter(
                (p) => !existing_spk_map[p.petugas.id],
            );
            const indexAmongNew = newPetugasOnly.findIndex(
                (p) => p.petugas.hashed_id === alokasi.petugas.hashed_id,
            );

            if (uses_suffix_for_new_petugas) {
                // Use suffix mode: 3A, 3B, 3C...
                const suffix = String.fromCharCode(65 + indexAmongNew); // A, B, C...
                nomorSpk = formatNomorSpkSuffix(
                    last_nomor_urut_in_month,
                    suffix,
                    tahunSpk,
                );
            } else {
                // Use sequential mode: 4, 5, 6...
                const noUrut = last_nomor_urut_in_month + 1 + indexAmongNew;
                nomorSpk = formatNomorSpk(noUrut, tahunSpk);
            }
        } else {
            // First time generation - use sequential numbering
            const sortedPetugas = [...petugas_list].sort((a, b) =>
                a.petugas.nama.localeCompare(b.petugas.nama),
            );

            const petugasIndex = sortedPetugas.findIndex(
                (a) => a.petugas.hashed_id === alokasi.petugas.hashed_id,
            );
            const noUrut = next_nomor_urut + petugasIndex;
            nomorSpk = formatNomorSpk(noUrut, tahunSpk);
        }

        const sanitizedPetugasName = alokasi.petugas.nama.replace(
            /[^A-Za-z0-9_-]/g,
            '_',
        );

        try {
            await previewFileFromPost(
                `/spk/periode/${periode.hashed_id}/petugas/${alokasi.petugas.hashed_id}/preview-lampiran`,
                {
                    nomor_spk: nomorSpk,
                    tanggal_spk: formData.tanggal_spk,
                },
                `Preview_SPK_Lampiran_${sanitizedPetugasName}.pdf`,
            );
        } catch {
            setModalMessage(
                'Gagal mengunduh file preview Perjanjian Kerja (lampiran).',
            );
            setShowFormModal(true);
        }
    };

    const handleGenerateAll = () => {
        if (selectedPetugas.length === 0) {
            setModalMessage('Pilih minimal 1 petugas');
            setShowFormModal(true);
            return;
        }

        if (!formData.tanggal_spk) {
            setModalMessage(
                'Lengkapi form terlebih dahulu (Tanggal Perjanjian Kerja wajib diisi)',
            );
            setShowFormModal(true);
            return;
        }

        setProcessing(true);

        // POST to new bulk endpoint
        router.post(
            `/spk/periode/${periode.hashed_id}/generate-all`,
            {
                tanggal_spk: formData.tanggal_spk,
                petugas_ids: selectedPetugas, // Send selected petugas
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setProcessing(false);
                    // page.props will not have the response, so use event.detail if available
                    // But inertiajs router.post with JSON response will set event.detail.response
                    // So, use a fetch fallback if needed
                    setSuccessMessage(
                        'Perjanjian Kerja berhasil dibuat untuk semua petugas non-organik.',
                    );
                    setShowSuccessModal(true);
                },
                onError: () => {
                    setProcessing(false);
                    setModalMessage(
                        'Terjadi error saat generate Perjanjian Kerja.',
                    );
                    setShowFormModal(true);
                },
            },
        );
    };

    const getPeranLabel = (peran: string) => {
        const labels: Record<string, string> = {
            pcl_ppl: 'Petugas Pencacahan',
            pml: 'Pemeriksa Lapangan',
            pengolahan: 'Petugas Pengolahan',
            pengawas_pengolahan: 'Pemeriksa Pengolahan',
        };
        return labels[peran] || peran;
    };

    const handleCloseSuccessModal = () => {
        setShowSuccessModal(false);
        router.get('/spk');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Generate Perjanjian Kerja" />

            <div className="space-y-6">
                <PageHeader
                    title="Generate Perjanjian Kerja"
                    description={`Generate Surat Perjanjian Kerja untuk kegiatan bulan ${periode.bulan_label} ${periode.tahun}`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/spk">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Warning Alert jika ada periode draft */}
                {has_draft_periode && (
                    <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900 dark:bg-yellow-950">
                        <div className="flex items-start space-x-3">
                            <svg
                                className="mt-0.5 size-5 flex-shrink-0 text-yellow-600 dark:text-yellow-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            <div>
                                <h3 className="text-sm font-semibold text-yellow-900 dark:text-yellow-100">
                                    Tidak Dapat Generate Perjanjian Kerja
                                </h3>
                                <p className="mt-1 text-sm text-yellow-800 dark:text-yellow-200">
                                    Masih terdapat periode alokasi kegiatan
                                    dengan status <strong>draft</strong> di
                                    bulan {periode.bulan_label} {periode.tahun}.
                                    Pastikan semua periode alokasi kegiatan
                                    sudah dikirim atau disetujui terlebih dahulu
                                    untuk menghindari ada kegiatan yang belum
                                    ditambahkan.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Form SPK */}
                <ContentCard>
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                            Informasi Perjanjian Kerja
                        </h3>

                        {is_regenerate && (
                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-950">
                                <p className="text-sm text-blue-800 dark:text-blue-200">
                                    <strong>Mode Regenerate:</strong> Tanggal
                                    Perjanjian Kerja dan Sampai Tanggal
                                    menggunakan data yang sudah ada di database
                                    dan tidak dapat diubah.
                                </p>
                            </div>
                        )}

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="tanggal_spk">
                                    Tanggal Perjanjian Kerja
                                </Label>
                                <DatePicker
                                    id="tanggal_spk"
                                    value={formData.tanggal_spk}
                                    onChange={(v) =>
                                        setFormData({
                                            ...formData,
                                            tanggal_spk: v,
                                        })
                                    }
                                    disabled={is_regenerate}
                                />
                                <p className="mt-1 text-xs text-neutral-500">
                                    Tanggal mulai pelaksanaan Perjanjian Kerja
                                    (Pasal 3). Tanggal akhir akan otomatis
                                    dihitung dari periode kegiatan.
                                </p>
                            </div>
                        </div>

                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            {isSensusEkonomi ? (
                                <>
                                    Format Nomor Perjanjian Kerja: B-[No
                                    Urut]/SPK-SE2026/1373/PL.200/[Tahun]
                                    <br />
                                    Contoh: B-001/SPK-SE2026/1373/PL.200/
                                    {formData.tanggal_spk
                                        ? new Date(
                                              formData.tanggal_spk,
                                          ).getFullYear()
                                        : '2026'}
                                </>
                            ) : (
                                <>
                                    Format Nomor Perjanjian Kerja:
                                    PPIS/13730/[No Urut]/K/[Tahun]
                                    <br />
                                    Contoh: PPIS/13730/1/K/
                                    {formData.tanggal_spk
                                        ? new Date(
                                              formData.tanggal_spk,
                                          ).getFullYear()
                                        : '2025'}
                                </>
                            )}
                        </p>
                    </div>
                </ContentCard>

                {/* Daftar Petugas */}
                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Pilih Petugas ({selectedPetugas.length} dari{' '}
                                {petugas_list.length})
                            </h3>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleSelectAll}
                            >
                                {selectedPetugas.length === petugas_list.length
                                    ? 'Batal Pilih Semua'
                                    : 'Pilih Semua'}
                            </Button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                <thead className="bg-neutral-50 dark:bg-neutral-800">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                            <Checkbox
                                                checked={
                                                    selectedPetugas.length ===
                                                    petugas_list.length
                                                }
                                                onCheckedChange={
                                                    handleSelectAll
                                                }
                                            />
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                            Petugas
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                            Peran
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                            Jumlah Kegiatan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                            Total Honor
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                    {petugas_list.map((alokasi) => {
                                        return (
                                            <tr
                                                key={alokasi.alokasi_hashed_id}
                                                className="hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                            >
                                                <td className="px-6 py-4">
                                                    <Checkbox
                                                        checked={selectedPetugas.includes(
                                                            alokasi.petugas
                                                                .hashed_id,
                                                        )}
                                                        onCheckedChange={() =>
                                                            handlePetugasToggle(
                                                                alokasi.petugas
                                                                    .hashed_id,
                                                            )
                                                        }
                                                    />
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-2">
                                                        <div>
                                                            <div className="font-medium text-neutral-900 dark:text-white">
                                                                {
                                                                    alokasi
                                                                        .petugas
                                                                        .nama
                                                                }
                                                            </div>
                                                            <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                                {
                                                                    alokasi
                                                                        .petugas
                                                                        .nik
                                                                }
                                                            </div>
                                                        </div>
                                                        {is_regenerate &&
                                                            !existing_spk_map[
                                                                alokasi.petugas
                                                                    .id
                                                            ] && (
                                                                <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">
                                                                    Baru
                                                                </span>
                                                            )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        {alokasi.kegiatan_list.map(
                                                            (kg, idx) => (
                                                                <div
                                                                    key={idx}
                                                                    className="text-sm"
                                                                >
                                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                                        {
                                                                            kg.kegiatan_nama
                                                                        }
                                                                    </div>
                                                                    <div className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                        {getPeranLabel(
                                                                            kg.peran,
                                                                        ) +
                                                                            ' ' +
                                                                            '(' +
                                                                            kg.kegiatan_nama +
                                                                            ')'}
                                                                    </div>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    {alokasi.jumlah_kegiatan}{' '}
                                                    kegiatan
                                                </td>
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    Rp{' '}
                                                    {alokasi.total_honor.toLocaleString(
                                                        'id-ID',
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                handlePreview(
                                                                    alokasi,
                                                                )
                                                            }
                                                            className="gap-1"
                                                            title="Preview Perjanjian Kerja + Lampiran (Merged)"
                                                            disabled={
                                                                has_draft_periode
                                                            }
                                                        >
                                                            <FileText className="h-3.5 w-3.5" />
                                                            Preview
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                handlePreviewMain(
                                                                    alokasi,
                                                                )
                                                            }
                                                            className="gap-1"
                                                            title="Preview Perjanjian Kerja Main Saja"
                                                            disabled={
                                                                has_draft_periode
                                                            }
                                                        >
                                                            <FileText className="h-3.5 w-3.5" />
                                                            Perjanjian Kerja
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                handlePreviewLampiran(
                                                                    alokasi,
                                                                )
                                                            }
                                                            className="gap-1"
                                                            title="Preview Lampiran Saja"
                                                            disabled={
                                                                has_draft_periode
                                                            }
                                                        >
                                                            <FileText className="h-3.5 w-3.5" />
                                                            Lampiran
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </ContentCard>

                {/* Action Buttons */}
                <ContentCard>
                    <div className="flex justify-end gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/spk">Batal</Link>
                        </Button>
                        <Button
                            onClick={handleGenerateAll}
                            disabled={
                                processing ||
                                selectedPetugas.length === 0 ||
                                !formData.tanggal_spk ||
                                has_draft_periode
                            }
                        >
                            {processing
                                ? 'Memproses...'
                                : has_draft_periode
                                  ? 'Tidak dapat generate (ada periode draft)'
                                  : is_regenerate
                                    ? `Re-generate Perjanjian Kerja (${selectedPetugas.length} Petugas)`
                                    : `Generate Perjanjian Kerja (${selectedPetugas.length} Petugas)`}
                        </Button>
                    </div>
                </ContentCard>
            </div>

            {/* Form Validation Modal */}
            {showFormModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    onClick={() => setShowFormModal(false)}
                >
                    <div
                        className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-neutral-800"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                                <svg
                                    className="size-6 text-yellow-600 dark:text-yellow-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Perhatian
                            </h3>
                        </div>
                        <p className="mb-6 text-neutral-700 dark:text-neutral-300">
                            {modalMessage}
                        </p>
                        <div className="flex justify-end">
                            <Button onClick={() => setShowFormModal(false)}>
                                Mengerti
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Processing Loading Overlay */}
            {processing && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                    <div className="mx-4 flex flex-col items-center gap-4 rounded-2xl border border-white/20 bg-white/95 p-8 shadow-2xl dark:border-neutral-700/30 dark:bg-neutral-900/95">
                        <Loader2 className="size-10 animate-spin text-neutral-700 dark:text-neutral-300" />
                        <p className="text-sm font-semibold text-neutral-900 dark:text-white">
                            Sedang generate Perjanjian Kerja...
                        </p>
                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                            Harap tunggu sebentar
                        </p>
                    </div>
                </div>
            )}

            {/* Success Modal */}
            {showSuccessModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    onClick={handleCloseSuccessModal}
                >
                    <div
                        className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-neutral-800"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                <svg
                                    className="size-6 text-green-600 dark:text-green-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Berhasil
                            </h3>
                        </div>
                        <p className="mb-6 text-neutral-700 dark:text-neutral-300">
                            {successMessage}
                        </p>
                        <div className="flex justify-end">
                            <Button onClick={handleCloseSuccessModal}>
                                OK
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
