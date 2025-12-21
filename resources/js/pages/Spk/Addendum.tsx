import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, FileEdit, Download, Eye } from 'lucide-react';
import { useState } from 'react';
import { Breadcrumb } from '@/components/ui/breadcrumb';
import { BreadcrumbItem } from '@/types';

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string;
    jenis_petugas: 'organik' | 'non_organik';
}

interface KegiatanInfo {
    kode_kegiatan: string;
    nama_kegiatan: string;
    peran: string;
    total_honor: number;
}

interface PetugasWithAddendum {
    petugas: Petugas;
    existing_spk_id: number;
    existing_spk_hashed_id: string;
    existing_spk_nomor: string;
    next_addendum_number: number;
    kegiatan_list: KegiatanInfo[];
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

interface AddendumProps {
    periode: PeriodeAlokasi;
    petugas_list: PetugasWithAddendum[];
}

export default function Addendum({ periode, petugas_list }: AddendumProps) {
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
    const [generatedCount, setGeneratedCount] = useState(0);

    const breadcrumbs: BreadcrumbItem[] = [
            { title: 'SPK', href: '/spk' },
            { title: `Addendum SPK - ${periode.bulan_label} ${periode.tahun}`, href: '#' },
        ];

    const handleSelectAll = () => {
        if (selectedPetugas.length === petugas_list.length) {
            setSelectedPetugas([]);
        } else {
            setSelectedPetugas(petugas_list.map((p) => p.petugas.hashed_id));
        }
    };

    const handlePetugasToggle = (hashedId: string) => {
        setSelectedPetugas((prev) =>
            prev.includes(hashedId) ? prev.filter((id) => id !== hashedId) : [...prev, hashedId]
        );
    };

    const handlePreview = (petugasData: PetugasWithAddendum) => {
        if (!formData.tanggal_spk || !formData.sampai_tanggal) {
            setModalMessage('Lengkapi form Tanggal Addendum dan Sampai Tanggal terlebih dahulu');
            setShowFormModal(true);
            return;
        }

        // Create a native form and submit to preview endpoint
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/spk/periode/${periode.hashed_id}/petugas/${petugasData.petugas.hashed_id}/preview-addendum`;
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
            tanggal_spk: formData.tanggal_spk,
            sampai_tanggal: formData.sampai_tanggal,
            parent_spk_id: petugasData.existing_spk_id.toString(),
            addendum_number: petugasData.next_addendum_number.toString(),
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

    const handleGenerate = async (petugasData: PetugasWithAddendum) => {
        if (!formData.tanggal_spk || !formData.sampai_tanggal) {
            setModalMessage('Lengkapi form Tanggal Addendum dan Sampai Tanggal terlebih dahulu');
            setShowFormModal(true);
            return;
        }

        setProcessing(true);

        try {
            const response = await fetch(
                `/spk/periode/${periode.hashed_id}/petugas/${petugasData.petugas.hashed_id}/generate-addendum`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        tanggal_spk: formData.tanggal_spk,
                        sampai_tanggal: formData.sampai_tanggal,
                        parent_spk_id: petugasData.existing_spk_id,
                        addendum_number: petugasData.next_addendum_number,
                    }),
                }
            );

            const result = await response.json();

            if (result.success) {
                setSuccessMessage(`Addendum SPK untuk ${petugasData.petugas.nama} berhasil di-generate!`);
                setShowSuccessModal(true);
            } else {
                setModalMessage(result.message || 'Gagal generate addendum SPK');
                setShowFormModal(true);
            }
        } catch (error) {
            setModalMessage('Terjadi kesalahan saat generate addendum SPK');
            setShowFormModal(true);
        } finally {
            setProcessing(false);
        }
    };

    const handleBatchGenerate = async () => {
        if (selectedPetugas.length === 0) {
            setModalMessage('Pilih minimal 1 petugas untuk generate batch addendum');
            setShowFormModal(true);
            return;
        }

        if (!formData.tanggal_spk || !formData.sampai_tanggal) {
            setModalMessage('Lengkapi form Tanggal Addendum dan Sampai Tanggal terlebih dahulu');
            setShowFormModal(true);
            return;
        }

        setProcessing(true);
        let successCount = 0;
        let failCount = 0;

        for (const hashedId of selectedPetugas) {
            const petugasData = petugas_list.find((p) => p.petugas.hashed_id === hashedId);
            if (!petugasData) continue;

            try {
                const response = await fetch(
                    `/spk/periode/${periode.hashed_id}/petugas/${petugasData.petugas.hashed_id}/generate-addendum`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify({
                            tanggal_spk: formData.tanggal_spk,
                            sampai_tanggal: formData.sampai_tanggal,
                            parent_spk_id: petugasData.existing_spk_id,
                            addendum_number: petugasData.next_addendum_number,
                        }),
                    }
                );

                const result = await response.json();

                if (result.success) {
                    successCount++;
                } else {
                    failCount++;
                }
            } catch (error) {
                failCount++;
            }
        }

        setProcessing(false);
        setGeneratedCount(successCount);
        
        if (failCount === 0) {
            setSuccessMessage(`Berhasil generate ${successCount} addendum SPK!`);
        } else {
            setSuccessMessage(`Generate selesai: ${successCount} berhasil, ${failCount} gagal.`);
        }
        setShowSuccessModal(true);
        setSelectedPetugas([]);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Addendum SPK - ${periode.bulan_label} ${periode.tahun}`} />

            <div className="space-y-6">
                {/* Info Card */}
                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex items-center gap-3">
                            <FileEdit className="h-6 w-6 text-primary" />
                            <h2 className="text-xl font-semibold">Generate Addendum SPK</h2>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="font-medium">Kegiatan:</span> {periode.kegiatan.nama_kegiatan}
                            </div>
                            <div>
                                <span className="font-medium">Kode:</span> {periode.kegiatan.kode_kegiatan}
                            </div>
                            <div>
                                <span className="font-medium">Periode:</span> {periode.bulan_label} {periode.tahun}
                            </div>
                            <div>
                                <span className="font-medium">Total Petugas:</span> {petugas_list.length} petugas
                            </div>
                        </div>
                        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md p-4">
                            <p className="text-sm text-blue-800 dark:text-blue-200">
                                <strong>Catatan:</strong> Anda akan membuat addendum SPK untuk petugas yang sudah memiliki SPK di bulan ini. 
                                Addendum akan mereferensikan SPK asli dan menyimpan perubahan sebagai dokumen baru.
                            </p>
                        </div>
                    </div>
                </ContentCard>

                {/* Form Card */}
                <ContentCard>
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold">Form Addendum SPK</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="tanggal_spk">Tanggal Addendum</Label>
                                <Input
                                    id="tanggal_spk"
                                    type="date"
                                    value={formData.tanggal_spk}
                                    onChange={(e) =>
                                        setFormData({ ...formData, tanggal_spk: e.target.value })
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="sampai_tanggal">Sampai Tanggal</Label>
                                <Input
                                    id="sampai_tanggal"
                                    type="date"
                                    value={formData.sampai_tanggal}
                                    onChange={(e) =>
                                        setFormData({ ...formData, sampai_tanggal: e.target.value })
                                    }
                                    required
                                />
                            </div>
                        </div>
                    </div>
                </ContentCard>

                {/* Petugas List Card */}
                <ContentCard>
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold">
                                Daftar Petugas dengan SPK Aktif ({petugas_list.length})
                            </h3>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleSelectAll}
                                >
                                    {selectedPetugas.length === petugas_list.length ? 'Unselect All' : 'Select All'}
                                </Button>
                                <Button
                                    type="button"
                                    onClick={handleBatchGenerate}
                                    disabled={selectedPetugas.length === 0 || processing}
                                    size="sm"
                                >
                                    Generate Batch ({selectedPetugas.length})
                                </Button>
                            </div>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted">
                                    <tr>
                                        <th className="p-3 text-left">
                                            <Checkbox
                                                checked={selectedPetugas.length === petugas_list.length && petugas_list.length > 0}
                                                onCheckedChange={handleSelectAll}
                                            />
                                        </th>
                                        <th className="p-3 text-left">No</th>
                                        <th className="p-3 text-left">Nama Petugas</th>
                                        <th className="p-3 text-left">NIK</th>
                                        <th className="p-3 text-left">SPK Asli</th>
                                        <th className="p-3 text-left">Addendum</th>
                                        <th className="p-3 text-left">Kegiatan</th>
                                        <th className="p-3 text-right">Total Honor</th>
                                        <th className="p-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {petugas_list.map((petugasData, index) => (
                                        <tr key={petugasData.petugas.hashed_id} className="hover:bg-muted/50">
                                            <td className="p-3">
                                                <Checkbox
                                                    checked={selectedPetugas.includes(petugasData.petugas.hashed_id)}
                                                    onCheckedChange={() =>
                                                        handlePetugasToggle(petugasData.petugas.hashed_id)
                                                    }
                                                />
                                            </td>
                                            <td className="p-3">{index + 1}</td>
                                            <td className="p-3 font-medium">{petugasData.petugas.nama}</td>
                                            <td className="p-3">{petugasData.petugas.nik}</td>
                                            <td className="p-3 text-xs">
                                                {petugasData.existing_spk_nomor}
                                            </td>
                                            <td className="p-3">
                                                <span className="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:text-blue-200">
                                                    Addendum ke-{petugasData.next_addendum_number}
                                                </span>
                                            </td>
                                            <td className="p-3">
                                                <div className="space-y-1">
                                                    {petugasData.kegiatan_list.map((keg, kidx) => (
                                                        <div key={kidx} className="text-xs">
                                                            {keg.nama_kegiatan} ({keg.peran})
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="p-3 text-right font-medium">
                                                Rp {petugasData.total_honor.toLocaleString('id-ID')}
                                            </td>
                                            <td className="p-3">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handlePreview(petugasData)}
                                                        disabled={processing}
                                                    >
                                                        <Eye className="h-4 w-4 mr-1" />
                                                        Preview
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        onClick={() => handleGenerate(petugasData)}
                                                        disabled={processing}
                                                    >
                                                        <Download className="h-4 w-4 mr-1" />
                                                        Generate
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </ContentCard>

                {/* Back Button */}
                <div className="flex justify-start">
                    <Link href="/spk">
                        <Button type="button" variant="outline">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Kembali
                        </Button>
                    </Link>
                </div>
            </div>

            {/* Form Modal */}
            {showFormModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-semibold mb-4">Perhatian</h3>
                        <p className="mb-6">{modalMessage}</p>
                        <div className="flex justify-end">
                            <Button onClick={() => setShowFormModal(false)}>OK</Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Success Modal */}
            {showSuccessModal && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-semibold mb-4 text-green-600 dark:text-green-400">
                            Berhasil!
                        </h3>
                        <p className="mb-6">{successMessage}</p>
                        <div className="flex justify-end gap-2">
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowSuccessModal(false);
                                    router.reload();
                                }}
                            >
                                Generate Lagi
                            </Button>
                            <Button
                                onClick={() => {
                                    setShowSuccessModal(false);
                                    router.visit('/spk');
                                }}
                            >
                                Kembali ke Daftar SPK
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
