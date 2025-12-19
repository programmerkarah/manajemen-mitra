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

interface AlokasiPetugas {
    alokasi_id: number;
    alokasi_hashed_id: string;
    petugas: Petugas;
    peran: string;
    target_listing?: number;
    target_pencacahan?: number;
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
}

const bulanLabels: Record<number, string> = {
    1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
    5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
    9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember',
};

export default function Generate({ periode, petugas_list }: GenerateProps) {
    const [formData, setFormData] = useState({
        nomor_spk_prefix: '',
        tanggal_spk: '',
    });
    const [selectedPetugas, setSelectedPetugas] = useState<string[]>([]);
    const [processing, setProcessing] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'SPK', href: '/spk' },
        { title: `Generate SPK - ${periode.kegiatan.nama_kegiatan} (${periode.bulan_label} ${periode.tahun})`, href: '#' },
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
        if (!formData.nomor_spk_prefix || !formData.tanggal_spk) {
            alert('Lengkapi form terlebih dahulu');
            return;
        }

        // Generate unique nomor for this petugas
        const nomorSpk = `${formData.nomor_spk_prefix}/SPK/${periode.bulan}/${periode.tahun}`;

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
            alert('Pilih minimal 1 petugas');
            return;
        }

        if (!formData.nomor_spk_prefix || !formData.tanggal_spk) {
            alert('Lengkapi form terlebih dahulu');
            return;
        }

        setProcessing(true);

        // Generate SPK for selected petugas
        let successCount = 0;
        const totalPetugas = selectedPetugas.length;

        selectedPetugas.forEach((petugasHashedId, index) => {
            const alokasi = petugas_list.find(a => a.petugas.hashed_id === petugasHashedId);
            if (!alokasi) return;

            const nomorSpk = `${formData.nomor_spk_prefix}/${index + 1}/SPK/${periode.bulan}/${periode.tahun}`;

            router.post(
                `/spk/periode/${periode.hashed_id}/petugas/${alokasi.petugas.hashed_id}/generate`,
                {
                    nomor_spk: nomorSpk,
                    tanggal_spk: formData.tanggal_spk,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        successCount++;
                        if (successCount === totalPetugas) {
                            setProcessing(false);
                            alert(`SPK berhasil dibuat untuk ${successCount} petugas`);
                            router.visit('/spk');
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
            'pencacah': 'Pencacah',
            'pengawas': 'Pengawas',
            'pemeriksa': 'Pemeriksa',
            'ketua_tim': 'Ketua Tim',
        };
        return labels[peran] || peran;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Generate SPK" />

            <div className="space-y-6">
                <PageHeader
                    title="Generate SPK"
                    description={`Generate Surat Perjanjian Kerja untuk ${periode.kegiatan.nama_kegiatan} - ${bulanLabels[periode.bulan]} ${periode.tahun}`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/spk">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Form SPK */}
                <ContentCard>
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                            Informasi SPK
                        </h3>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="nomor_spk_prefix">Nomor SPK (Prefix)</Label>
                                <Input
                                    id="nomor_spk_prefix"
                                    type="text"
                                    value={formData.nomor_spk_prefix}
                                    onChange={(e) => setFormData({ ...formData, nomor_spk_prefix: e.target.value })}
                                    placeholder="Contoh: 220"
                                    required
                                />
                                <p className="mt-1 text-xs text-neutral-500">
                                    Format akhir: {formData.nomor_spk_prefix || 'XXX'}/[No Urut]/SPK/{periode.bulan}/{periode.tahun}
                                </p>
                            </div>

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
                        </div>
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
                                            Total Honor
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                    {petugas_list.map((alokasi) => {
                                        const totalHonor = (alokasi.total_honor || 0) + (alokasi.total_honor_listing || 0);
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
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    {getPeranLabel(alokasi.peran)}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-neutral-900 dark:text-white">
                                                    Rp {totalHonor.toLocaleString('id-ID')}
                                                </td>
                                                <td className="px-6 py-4 text-right text-sm">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => handlePreview(alokasi)}
                                                        className="gap-1"
                                                    >
                                                        <FileText className="h-3.5 w-3.5" />
                                                        Preview
                                                    </Button>
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
                            disabled={processing || selectedPetugas.length === 0}
                        >
                            {processing ? 'Memproses...' : `Generate SPK (${selectedPetugas.length} Petugas)`}
                        </Button>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
