import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, FileText, Download } from 'lucide-react';
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
}

const bulanLabels: Record<number, string> = {
    1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
    5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
    9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember',
};

export default function Generate({ periode, petugas_list, has_draft_periode, next_nomor_urut }: GenerateProps) {
    const [formData, setFormData] = useState({
        tanggal_spk: '',
        sampai_tanggal: '',
    });
    const [selectedPetugas, setSelectedPetugas] = useState<string[]>([]);
    const [processing, setProcessing] = useState(false);
    const [showFormModal, setShowFormModal] = useState(false);
    const [modalMessage, setModalMessage] = useState('');
    const [showSuccessModal, setShowSuccessModal] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');


    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'SPK', href: '/spk' },
        { title: `Generate SPK - ${periode.bulan_label} ${periode.tahun}`, href: '#' },
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
            prev.includes(hashedId) ? prev.filter((id) => id !== hashedId) : [...prev, hashedId]
        );
    };

    const handlePreview = (alokasi: AlokasiPetugas) => {
        if (!formData.tanggal_spk || !formData.sampai_tanggal) {
            setModalMessage('Lengkapi form Tanggal SPK dan Sampai Tanggal terlebih dahulu');
            setShowFormModal(true);
            return;
        }

        // Get year from tanggal_spk
        const tahunSpk = new Date(formData.tanggal_spk).getFullYear();

        // Sort all petugas by name to get correct urutan
        const sortedPetugas = [...petugas_list].sort((a, b) => 
            a.petugas.nama.localeCompare(b.petugas.nama)
        );
        
        // Find the index (0-based) of this petugas in sorted list
        const petugasIndex = sortedPetugas.findIndex(a => a.petugas.hashed_id === alokasi.petugas.hashed_id);
        
        // Calculate nomor urut: next_nomor_urut + index
        const noUrut = next_nomor_urut + petugasIndex;

        // Generate nomor SPK: PPIS/13730/{No Urut}/K/{tahun}
        const nomorSpk = `PPIS/13730/${noUrut}/K/${tahunSpk}`;

        // Create a native form and submit to preview endpoint
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/spk/periode/${periode.hashed_id}/petugas/${alokasi.petugas.hashed_id}/preview`;
        form.target = '_blank';
        form.style.display = 'none';

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        // Add form data
        const formDataToSubmit = {
            nomor_spk: nomorSpk,
            tanggal_spk: formData.tanggal_spk,
            sampai_tanggal: formData.sampai_tanggal,
        };

        Object.entries(formDataToSubmit).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    const handlePreviewMain = (alokasi: any) => {
        if (!formData.tanggal_spk || !formData.sampai_tanggal) {
            setModalMessage('Lengkapi form terlebih dahulu (Tanggal SPK dan Sampai Tanggal wajib diisi)');
            setShowFormModal(true);
            return;
        }

        // Get year from tanggal_spk
        const tahunSpk = new Date(formData.tanggal_spk).getFullYear();
        
        // Sort all petugas by name to get correct urutan
        const sortedPetugas = [...petugas_list].sort((a, b) => 
            a.petugas.nama.localeCompare(b.petugas.nama)
        );
        
        // Find the index (0-based) of this petugas in sorted list
        const petugasIndex = sortedPetugas.findIndex(a => a.petugas.hashed_id === alokasi.petugas.hashed_id);
        
        // Calculate nomor urut: next_nomor_urut + index
        const noUrut = next_nomor_urut + petugasIndex;

        // Generate nomor SPK: PPIS/13730/{No Urut}/K/{tahun}
        const nomorSpk = `PPIS/13730/${noUrut}/K/${tahunSpk}`;

        // Create a native form and submit to preview-main endpoint
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/spk/periode/${periode.hashed_id}/petugas/${alokasi.petugas.hashed_id}/preview-main`;
        form.target = '_blank';
        form.style.display = 'none';

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        // Add form data
        const formDataToSubmit = {
            nomor_spk: nomorSpk,
            tanggal_spk: formData.tanggal_spk,
            sampai_tanggal: formData.sampai_tanggal,
        };

        Object.entries(formDataToSubmit).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    const handlePreviewLampiran = (alokasi: any) => {
        if (!formData.tanggal_spk || !formData.sampai_tanggal) {
            setModalMessage('Lengkapi form terlebih dahulu (Tanggal SPK dan Sampai Tanggal wajib diisi)');
            setShowFormModal(true);
            return;
        }

        // Get year from tanggal_spk
        const tahunSpk = new Date(formData.tanggal_spk).getFullYear();
        
        // Sort all petugas by name to get correct urutan
        const sortedPetugas = [...petugas_list].sort((a, b) => 
            a.petugas.nama.localeCompare(b.petugas.nama)
        );
        
        // Find the index (0-based) of this petugas in sorted list
        const petugasIndex = sortedPetugas.findIndex(a => a.petugas.hashed_id === alokasi.petugas.hashed_id);
        
        // Calculate nomor urut: next_nomor_urut + index
        const noUrut = next_nomor_urut + petugasIndex;

        // Generate nomor SPK: PPIS/13730/{No Urut}/K/{tahun}
        const nomorSpk = `PPIS/13730/${noUrut}/K/${tahunSpk}`;

        // Create a native form and submit to preview-lampiran endpoint
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/spk/periode/${periode.hashed_id}/petugas/${alokasi.petugas.hashed_id}/preview-lampiran`;
        form.target = '_blank';
        form.style.display = 'none';

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        // Add form data
        const formDataToSubmit = {
            nomor_spk: nomorSpk,
            tanggal_spk: formData.tanggal_spk,
            sampai_tanggal: formData.sampai_tanggal,
        };

        Object.entries(formDataToSubmit).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    const handleGenerateAll = () => {
        if (selectedPetugas.length === 0) {
            setModalMessage('Pilih minimal 1 petugas');
            setShowFormModal(true);
            return;
        }

        if (!formData.tanggal_spk || !formData.sampai_tanggal) {
            setModalMessage('Lengkapi form terlebih dahulu (Tanggal SPK dan Sampai Tanggal wajib diisi)');
            setShowFormModal(true);
            return;
        }

        setProcessing(true);

        // Get year from tanggal_spk
        const tahunSpk = new Date(formData.tanggal_spk).getFullYear();

        // Sort petugas by name (alphabetically) to ensure consistent numbering
        const sortedPetugas = [...petugas_list]
            .filter(a => selectedPetugas.includes(a.petugas.hashed_id))
            .sort((a, b) => a.petugas.nama.localeCompare(b.petugas.nama));

        // Generate SPK for selected petugas
        let successCount = 0;
        const totalPetugas = sortedPetugas.length;

        sortedPetugas.forEach((alokasi, index) => {
            // Calculate nomor urut: next_nomor_urut + index
            const noUrut = next_nomor_urut + index;
            
            // Format: PPIS/13730/{No Urut}/K/{tahun}
            const nomorSpk = `PPIS/13730/${noUrut}/K/${tahunSpk}`;

            router.post(
                `/spk/periode/${periode.hashed_id}/petugas/${alokasi.petugas.hashed_id}/generate`,
                {
                    nomor_spk: nomorSpk,
                    tanggal_spk: formData.tanggal_spk,
                    sampai_tanggal: formData.sampai_tanggal,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        successCount++;
                        if (successCount === totalPetugas) {
                            setProcessing(false);
                            setSuccessMessage(`SPK berhasil dibuat untuk ${successCount} petugas`);
                            setShowSuccessModal(true);
                        }
                    },
                    onError: (errors) => {
                        console.error('Error generating SPK:', errors);
                        setProcessing(false);
                    },
                }
            );
        });
    };

    const getPeranLabel = (peran: string) => {
        const labels: Record<string, string> = {
            'pcl_ppl': 'Petugas Pencacahan',
            'pml': 'Pemeriksa Lapangan',
            'pengolahan': 'Petugas Pengolahan',
            'pengawas_pengolahan': 'Pemeriksa Pengolahan',
        };
        return labels[peran] || peran;
    };

    const handleCloseSuccessModal = () => {
        setShowSuccessModal(false);
        router.get('/spk');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Generate SPK" />

            <div className="space-y-6">
                <PageHeader
                    title="Generate SPK"
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
                                    Tidak Dapat Generate SPK
                                </h3>
                                <p className="mt-1 text-sm text-yellow-800 dark:text-yellow-200">
                                    Masih terdapat periode alokasi kegiatan dengan status <strong>draft</strong> di bulan {periode.bulan_label} {periode.tahun}. 
                                    Pastikan semua periode alokasi kegiatan sudah dikirim atau disetujui terlebih dahulu untuk menghindari ada kegiatan yang belum ditambahkan.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Form SPK */}
                <ContentCard>
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                            Informasi SPK
                        </h3>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="tanggal_spk">Tanggal SPK</Label>
                                <Input
                                    id="tanggal_spk"
                                    type="date"
                                    value={formData.tanggal_spk}
                                    onChange={(e) => setFormData({ ...formData, tanggal_spk: e.target.value })}
                                    required
                                />
                            </div>

                            <div>
                                <Label htmlFor="sampai_tanggal">Sampai Tanggal</Label>
                                <Input
                                    id="sampai_tanggal"
                                    type="date"
                                    value={formData.sampai_tanggal}
                                    onChange={(e) => setFormData({ ...formData, sampai_tanggal: e.target.value })}
                                    required
                                />
                                <p className="mt-1 text-xs text-neutral-500">
                                    Tanggal akhir pelaksanaan pekerjaan (Pasal 3)
                                </p>
                            </div>
                        </div>

                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Format Nomor SPK: PPIS/13730/[No Urut]/K/[Tahun]<br />
                            Contoh: PPIS/13730/1/K/{formData.tanggal_spk ? new Date(formData.tanggal_spk).getFullYear() : '2025'}
                        </p>
                    </div>
                </ContentCard>

                {/* Daftar Petugas */}
                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Pilih Petugas ({selectedPetugas.length} dari {petugas_list.length})
                            </h3>
                            <Button variant="outline" size="sm" onClick={handleSelectAll}>
                                {selectedPetugas.length === petugas_list.length ? 'Batal Pilih Semua' : 'Pilih Semua'}
                            </Button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                <thead className="bg-neutral-50 dark:bg-neutral-800">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                            <Checkbox
                                                checked={selectedPetugas.length === petugas_list.length}
                                                onCheckedChange={handleSelectAll}
                                            />
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                            Petugas
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                            Peran
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                            Jumlah Kegiatan
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                            Total Honor
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                    {petugas_list.map((alokasi) => {
                                        return (
                                            <tr key={alokasi.alokasi_hashed_id} className="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                                <td className="px-6 py-4">
                                                    <Checkbox
                                                        checked={selectedPetugas.includes(alokasi.petugas.hashed_id)}
                                                        onCheckedChange={() => handlePetugasToggle(alokasi.petugas.hashed_id)}
                                                    />
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div>
                                                        <div className="font-medium text-neutral-900 dark:text-white">
                                                            {alokasi.petugas.nama}
                                                        </div>
                                                        <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                                            {alokasi.petugas.nik}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        {alokasi.kegiatan_list.map((kg, idx) => (
                                                            <div key={idx} className="text-sm">
                                                                <div className="font-medium text-neutral-900 dark:text-white">
                                                                    {kg.kegiatan_kode}
                                                                </div>
                                                                <div className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                    {getPeranLabel(kg.peran)}
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    {alokasi.jumlah_kegiatan} kegiatan
                                                </td>
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    Rp {alokasi.total_honor.toLocaleString('id-ID')}
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => handlePreview(alokasi)}
                                                            className="gap-1"
                                                            title="Preview SPK + Lampiran (Merged)"
                                                            disabled={has_draft_periode}
                                                        >
                                                            <FileText className="h-3.5 w-3.5" />
                                                            Preview
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => handlePreviewMain(alokasi)}
                                                            className="gap-1"
                                                            title="Preview SPK Main Saja"
                                                            disabled={has_draft_periode}
                                                        >
                                                            <FileText className="h-3.5 w-3.5" />
                                                            SPK
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => handlePreviewLampiran(alokasi)}
                                                            className="gap-1"
                                                            title="Preview Lampiran Saja"
                                                            disabled={has_draft_periode}
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
                            disabled={processing || selectedPetugas.length === 0 || !formData.tanggal_spk || !formData.sampai_tanggal || has_draft_periode}
                        >
                            {processing ? 'Memproses...' : has_draft_periode ? 'Tidak dapat generate (ada periode draft)' : `Generate SPK (${selectedPetugas.length} Petugas)`}
                        </Button>
                    </div>
                </ContentCard>
            </div>

            {/* Form Validation Modal */}
            {showFormModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onClick={() => setShowFormModal(false)}>
                    <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-neutral-800" onClick={(e) => e.stopPropagation()}>
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                                <svg className="size-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">Perhatian</h3>
                        </div>
                        <p className="mb-6 text-neutral-700 dark:text-neutral-300">{modalMessage}</p>
                        <div className="flex justify-end">
                            <Button onClick={() => setShowFormModal(false)}>Mengerti</Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Success Modal */}
            {showSuccessModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50" onClick={handleCloseSuccessModal}>
                    <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-neutral-800" onClick={(e) => e.stopPropagation()}>
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                <svg className="size-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">Berhasil</h3>
                        </div>
                        <p className="mb-6 text-neutral-700 dark:text-neutral-300">{successMessage}</p>
                        <div className="flex justify-end">
                            <Button onClick={handleCloseSuccessModal}>OK</Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
