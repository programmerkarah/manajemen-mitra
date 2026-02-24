import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Sbml } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'SBML', href: '/sbml' },
    { title: 'Edit SBML', href: '/sbml/edit' },
];

interface SbmlEntry {
    id: number;
    jenis_kegiatan: 'sensus' | 'survei';
    status_kepegawaian: 'organik' | 'non_organik';
    jenis_penugasan: 'pcl_ppl' | 'pml' | 'pengolahan' | 'pengawas_pengolahan';
    honor_max: string;
}

interface Props {
    entries: Sbml[];
    tahun: number;
    status: 'aktif' | 'nonaktif';
    keterangan: string | null;
}

export default function Edit({
    entries,
    tahun,
    status: initialStatus,
    keterangan: initialKeterangan,
}: Props) {
    const [keterangan, setKeterangan] = useState(initialKeterangan || '');
    const [status, setStatus] = useState<'aktif' | 'nonaktif'>(initialStatus);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Initialize form entries from fetched data
    const [formEntries, setFormEntries] = useState<SbmlEntry[]>(
        entries.map((entry) => ({
            id: entry.id,
            jenis_kegiatan: entry.jenis_kegiatan,
            status_kepegawaian: entry.status_kepegawaian,
            jenis_penugasan: entry.jenis_penugasan,
            honor_max: entry.honor_max
                .toString()
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.'),
        })),
    );

    const getJenisKegiatanLabel = (jenis: string) => {
        return jenis === 'sensus' ? 'Sensus' : 'Survei';
    };

    const getStatusKepegawaianLabel = (status: string) => {
        return status === 'organik' ? 'Organik (PNS/PPPK)' : 'Non-Organik';
    };

    const getJenisPenugasanLabel = (jenis: string) => {
        const labels: Record<string, string> = {
            pcl_ppl: 'PCL/PPL (Petugas Pencacahan/Pendataan Lapangan)',
            pml: 'PML (Petugas Pemeriksaan Lapangan)',
            pengolahan: 'Petugas Pengolahan Data',
            pengawas_pengolahan: 'Pengawas Pengolahan',
        };
        return labels[jenis] || jenis;
    };

    const formatNumber = (value: string) => {
        const numericValue = value.replace(/\D/g, '');
        return numericValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    const handleHonorChange = (index: number, value: string) => {
        const formatted = formatNumber(value);
        const newEntries = [...formEntries];
        newEntries[index].honor_max = formatted;
        setFormEntries(newEntries);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = {
            entries: formEntries.map((entry) => ({
                id: entry.id,
                honor_max: parseFloat(entry.honor_max.replace(/\./g, '')) || 0,
            })),
            keterangan,
            status,
        };

        router.patch(`/sbml/${tahun}`, payload, {
            onSuccess: () => {
                setProcessing(false);
            },
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit SBML" />

            <div className="space-y-6">
                <PageHeader
                    title="Edit SBML"
                    description={`Edit batas maksimal honor per bulan untuk tahun ${tahun}`}
                >
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="gap-2"
                    >
                        <Link href="/sbml">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={submit} className="space-y-6">
                    <ContentCard>
                        <div className="space-y-6">
                            {/* Tahun Anggaran - Read Only */}
                            <div>
                                <Label htmlFor="tahun_anggaran">
                                    Tahun Anggaran
                                </Label>
                                <Input
                                    id="tahun_anggaran"
                                    type="text"
                                    value={tahun}
                                    disabled
                                    className="bg-neutral-100 dark:bg-neutral-800/60"
                                />
                                <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                    Tahun anggaran tidak dapat diubah
                                </p>
                            </div>

                            {/* Table of all combinations */}
                            <div>
                                <Label className="mb-3 block">
                                    Batas Honor Maksimal{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <div className="overflow-x-auto">
                                    <div className="overflow-hidden rounded-2xl">
                                        <table className="min-w-full divide-y divide-white/20 dark:divide-neutral-700/30">
                                            <thead className="bg-white/60 backdrop-blur-md dark:bg-neutral-800/60">
                                                <tr>
                                                    <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                        No
                                                    </th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                        Jenis Kegiatan
                                                    </th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                        Status Kepegawaian
                                                    </th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                        Jenis Penugasan
                                                    </th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                                        Honor Maksimal (Rp)
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-white/10 bg-white/30 backdrop-blur-sm dark:divide-neutral-700/20 dark:bg-neutral-800/30">
                                                {formEntries.map(
                                                    (entry, index) => (
                                                        <tr
                                                            key={index}
                                                            className="transition-colors hover:bg-white/50 dark:hover:bg-neutral-800/50"
                                                        >
                                                            <td className="px-4 py-3 text-sm whitespace-nowrap text-neutral-900 dark:text-white">
                                                                {index + 1}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm whitespace-nowrap text-neutral-900 dark:text-white">
                                                                {getJenisKegiatanLabel(
                                                                    entry.jenis_kegiatan,
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm whitespace-nowrap text-neutral-900 dark:text-white">
                                                                {getStatusKepegawaianLabel(
                                                                    entry.status_kepegawaian,
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm text-neutral-900 dark:text-white">
                                                                {getJenisPenugasanLabel(
                                                                    entry.jenis_penugasan,
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                <Input
                                                                    type="text"
                                                                    value={
                                                                        entry.honor_max
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        handleHonorChange(
                                                                            index,
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                    placeholder="0"
                                                                    className="w-full"
                                                                />
                                                            </td>
                                                        </tr>
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                {errors.entries && (
                                    <p className="mt-2 text-sm text-red-600">
                                        {errors.entries}
                                    </p>
                                )}
                            </div>

                            {/* Keterangan */}
                            <div>
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <Textarea
                                    id="keterangan"
                                    value={keterangan}
                                    onChange={(e) =>
                                        setKeterangan(e.target.value)
                                    }
                                    rows={3}
                                    placeholder="Catatan tambahan (opsional)"
                                />
                            </div>

                            {/* Status */}
                            <div>
                                <Label htmlFor="status">
                                    Status{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={status}
                                    onValueChange={(value) =>
                                        setStatus(value as 'aktif' | 'nonaktif')
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="aktif">
                                            Aktif
                                        </SelectItem>
                                        <SelectItem value="nonaktif">
                                            Nonaktif
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </ContentCard>

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            asChild
                            className="min-w-[180px] gap-2"
                        >
                            <Link href="/sbml">
                                <X className="h-5 w-5" />
                                Batal
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="min-w-[180px] gap-2"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <Save className="h-5 w-5" />
                                    Simpan Perubahan
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
