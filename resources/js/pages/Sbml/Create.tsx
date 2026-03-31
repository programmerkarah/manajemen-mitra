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
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, FileUp, Loader2, Save, X } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SBML', href: '/sbml' },
    { title: 'Tambah SBML', href: '/sbml/create' },
];

interface SbmlEntry {
    jenis_kegiatan: 'sensus' | 'survei';
    status_kepegawaian: 'organik' | 'non_organik';
    jenis_penugasan:
        | 'pcl_ppl'
        | 'pml'
        | 'pengolahan'
        | 'pengawas_pengolahan'
        | 'koseka';
    honor_max: string;
}

interface CreateProps {
    tahun_options: number[];
}

export default function Create({ tahun_options }: CreateProps) {
    const [tahun, setTahun] = useState(
        tahun_options[0] || new Date().getFullYear(),
    );
    const [keterangan, setKeterangan] = useState('');
    const [status, setStatus] = useState<'aktif' | 'nonaktif'>('aktif');

    // Define all 18 combinations in the order specified
    const initialEntries: SbmlEntry[] = [
        // Survei - Non Organik (4 jenis - TANPA koseka)
        {
            jenis_kegiatan: 'survei',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'pcl_ppl',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'survei',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'pml',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'survei',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'pengolahan',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'survei',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'pengawas_pengolahan',
            honor_max: '',
        },
        // Survei - Organik (4 jenis - TANPA koseka)
        {
            jenis_kegiatan: 'survei',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'pcl_ppl',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'survei',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'pml',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'survei',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'pengolahan',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'survei',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'pengawas_pengolahan',
            honor_max: '',
        },
        // Sensus - Non Organik (5 jenis - DENGAN koseka)
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'pcl_ppl',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'pml',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'pengolahan',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'pengawas_pengolahan',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'non_organik',
            jenis_penugasan: 'koseka',
            honor_max: '',
        },
        // Sensus - Organik (5 jenis - DENGAN koseka)
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'pcl_ppl',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'pml',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'pengolahan',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'pengawas_pengolahan',
            honor_max: '',
        },
        {
            jenis_kegiatan: 'sensus',
            status_kepegawaian: 'organik',
            jenis_penugasan: 'koseka',
            honor_max: '',
        },
    ];

    const [entries, setEntries] = useState<SbmlEntry[]>(initialEntries);
    const [processing, setProcessing] = useState(false);
    const [importProcessing, setImportProcessing] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});

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
            koseka: 'Koseka (Koordinator Sensus Kecamatan)',
        };
        return labels[jenis] || jenis;
    };

    const formatNumber = (value: string) => {
        // Remove non-numeric characters
        const numericValue = value.replace(/\D/g, '');
        // Format with thousand separators
        return numericValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    const handleHonorChange = (index: number, value: string) => {
        const formatted = formatNumber(value);
        const newEntries = [...entries];
        newEntries[index].honor_max = formatted;
        setEntries(newEntries);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        // Convert entries to API format
        const payload = {
            tahun_anggaran: tahun,
            entries: entries.map((entry) => ({
                jenis_kegiatan: entry.jenis_kegiatan,
                status_kepegawaian: entry.status_kepegawaian,
                jenis_penugasan: entry.jenis_penugasan,
                honor_max: parseFloat(entry.honor_max.replace(/\./g, '')) || 0,
            })),
            keterangan,
            status,
        };

        // Use Inertia router to post
        router.post('/sbml', payload, {
            onSuccess: () => {
                setProcessing(false);
            },
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
        });
    };

    const handleImportSubmit: FormEventHandler = (e) => {
        e.preventDefault();

        if (!importFile) {
            setErrors((prev) => ({
                ...prev,
                file: 'Pilih file template SBML terlebih dahulu.',
            }));

            return;
        }

        setImportProcessing(true);
        setErrors((prev) => {
            const nextErrors = { ...prev };
            delete nextErrors.file;
            return nextErrors;
        });

        router.post(
            `/sbml/${tahun}/import`,
            { file: importFile },
            {
                forceFormData: true,
                onSuccess: () => {
                    setImportProcessing(false);
                    setImportFile(null);
                },
                onError: (importErrors) => {
                    setErrors((prev) => ({
                        ...prev,
                        ...importErrors,
                    }));
                    setImportProcessing(false);
                },
                onFinish: () => {
                    setImportProcessing(false);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah SBML" />

            <div className="space-y-6">
                <PageHeader
                    title="Tambah SBML"
                    description="Tentukan batas maksimal honor per bulan untuk semua kategori"
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

                <ContentCard>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label className="text-base font-semibold">
                                Template Impor SBML
                            </Label>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Download template kosong sesuai kombinasi SBML,
                                lalu isi kolom honor maksimal.
                            </p>
                            <Button variant="outline" asChild className="gap-2">
                                <a href={`/sbml/${tahun}/export/create`}>
                                    <Download className="h-4 w-4" />
                                    Download Template
                                </a>
                            </Button>
                        </div>

                        <form
                            onSubmit={handleImportSubmit}
                            className="space-y-2"
                        >
                            <Label
                                htmlFor="sbml_import_file"
                                className="text-base font-semibold"
                            >
                                Impor File SBML
                            </Label>
                            <Input
                                id="sbml_import_file"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                className="cursor-pointer"
                                onChange={(e) =>
                                    setImportFile(e.target.files?.[0] ?? null)
                                }
                            />
                            {errors.file && (
                                <p className="text-sm text-red-600">
                                    {errors.file}
                                </p>
                            )}
                            <Button
                                type="submit"
                                variant="outline"
                                className="cursor-pointer gap-2"
                                disabled={importProcessing}
                            >
                                {importProcessing ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <FileUp className="h-4 w-4" />
                                )}
                                Impor & Simpan
                            </Button>
                        </form>
                    </div>
                </ContentCard>

                <form onSubmit={submit} className="space-y-6">
                    <ContentCard>
                        <div className="space-y-6">
                            {/* Tahun Anggaran */}
                            <div>
                                <Label
                                    htmlFor="tahun_anggaran"
                                    className="text-base font-semibold"
                                >
                                    Tahun Anggaran{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={tahun.toString()}
                                    onValueChange={(value) =>
                                        setTahun(parseInt(value))
                                    }
                                >
                                    <SelectTrigger className="mt-1 h-11">
                                        <SelectValue placeholder="Pilih Tahun" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {tahun_options.map((year) => (
                                            <SelectItem
                                                key={year}
                                                value={year.toString()}
                                            >
                                                {year}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.tahun_anggaran && (
                                    <p className="mt-2 text-sm text-red-600">
                                        {errors.tahun_anggaran}
                                    </p>
                                )}
                            </div>

                            {/* Table of all combinations */}
                            <div>
                                <Label className="mb-3 block text-base font-semibold">
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
                                                {entries.map((entry, index) => (
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
                                                                onChange={(e) =>
                                                                    handleHonorChange(
                                                                        index,
                                                                        e.target
                                                                            .value,
                                                                    )
                                                                }
                                                                placeholder="0"
                                                                className="h-11 w-full text-base"
                                                            />
                                                        </td>
                                                    </tr>
                                                ))}
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
                                <Label
                                    htmlFor="keterangan"
                                    className="text-base font-semibold"
                                >
                                    Keterangan
                                </Label>
                                <Textarea
                                    id="keterangan"
                                    value={keterangan}
                                    onChange={(e) =>
                                        setKeterangan(e.target.value)
                                    }
                                    rows={3}
                                    placeholder="Catatan tambahan (opsional)"
                                    className="text-base"
                                />
                            </div>

                            {/* Status */}
                            <div>
                                <Label
                                    htmlFor="status"
                                    className="text-base font-semibold"
                                >
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
                                    Simpan
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
