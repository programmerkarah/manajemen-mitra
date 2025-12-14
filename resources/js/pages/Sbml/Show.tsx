import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { PageHeader } from '@/components/page-header';
import { ContentCard } from '@/components/content-card';
import { BreadcrumbItem, Sbml } from '@/types';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';

interface ShowProps {
    tahun: number;
    sbmlEntries: Sbml[];
    status: 'aktif' | 'nonaktif';
    keterangan: string | null;
}
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'SBML', href: '/sbml' },
    { title: 'Edit SBML', href: '/sbml/edit' },
]

export default function Show({ tahun, sbmlEntries, status, keterangan }: ShowProps) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getJenisKegiatanLabel = (jenis: string) => {
        return jenis === 'sensus' ? 'Sensus' : 'Survei'
    }

    const getStatusKepegawaianLabel = (status: string) => {
        return status === 'organik' ? 'Organik (PNS/PPPK)' : 'Non-Organik'
    }

    const getJenisPenugasanLabel = (jenis: string) => {
        const labels: Record<string, string> = {
            pcl_ppl: 'PCL/PPL (Petugas Pencacahan/Pendataan Lapangan)',
            pml: 'PML (Petugas Pemeriksaan Lapangan)',
            pengolahan: 'Petugas Pengolahan Data',
            pengawas_pengolahan: 'Pengawas Pengolahan',
        }
        return labels[jenis] || jenis
    }

    const handleDelete = () => {
        if (confirm(`Apakah Anda yakin ingin menghapus semua data SBML untuk tahun ${tahun}?`)) {
            router.delete(`/sbml/${tahun}`);
        }
    };

    // Group entries by jenis_kegiatan and status_kepegawaian
    const groupedEntries = sbmlEntries.reduce((acc, entry) => {
        const key = `${entry.jenis_kegiatan}_${entry.status_kepegawaian}`;
        if (!acc[key]) {
            acc[key] = [];
        }
        acc[key].push(entry);
        return acc;
    }, {} as Record<string, Sbml[]>);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail SBML - Tahun ${tahun}`} />

            <div className="space-y-6">
                <PageHeader
                    title={`Detail SBML Tahun ${tahun}`}
                    description="Informasi lengkap batas honor maksimal per bulan"
                >
                    <div className="flex gap-3">
                        <Button size="sm" variant="outline" asChild className="gap-2">
                            <Link href="/sbml">
                                <ArrowLeft className="h-4 w-4" />
                                Kembali
                            </Link>
                        </Button>
                        <Button size="sm" variant="outline" asChild className="gap-2">
                            <Link href={`/sbml/${tahun}/edit`}>
                                <Pencil className="h-4 w-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button
                            size="sm"
                            variant="destructive"
                            onClick={handleDelete}
                            className="gap-2"
                        >
                            <Trash2 className="h-4 w-4" />
                            Hapus
                        </Button>
                    </div>
                </PageHeader>

                {/* Summary Info */}
                <ContentCard>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div className="space-y-1">
                            <p className="text-sm font-medium text-muted-foreground">
                                Tahun Anggaran
                            </p>
                            <p className="text-lg font-semibold">
                                {tahun}
                            </p>
                        </div>

                        <div className="space-y-1">
                            <p className="text-sm font-medium text-muted-foreground">
                                Status
                            </p>
                            <div>
                                <span
                                    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                        status === 'aktif'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                    }`}
                                >
                                    {status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                </span>
                            </div>
                        </div>

                        <div className="space-y-1">
                            <p className="text-sm font-medium text-muted-foreground">
                                Total Entries
                            </p>
                            <p className="text-lg font-semibold">
                                {sbmlEntries.length} kombinasi
                            </p>
                        </div>
                    </div>

                    {keterangan && (
                        <div className="border-t border-neutral-200/70 mt-4 pt-4 dark:border-neutral-800">
                            <p className="text-sm font-medium text-muted-foreground mb-2">
                                Keterangan
                            </p>
                            <p className="text-sm text-gray-700 dark:text-gray-300">
                                {keterangan}
                            </p>
                        </div>
                    )}
                </ContentCard>

                {/* Honor Table */}
                <ContentCard padding="none">
                    <div className="border-b border-neutral-200/70 px-6 py-4 dark:border-neutral-800">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Batas Honor Maksimal per Bulan
                        </h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Daftar lengkap honor maksimal berdasarkan jenis kegiatan, status kepegawaian, dan jenis penugasan
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200/70 dark:divide-neutral-800">
                            <thead className="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        No
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Jenis Kegiatan
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Status Kepegawaian
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Jenis Penugasan
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Honor Maksimal
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200/70 bg-white dark:divide-neutral-800 dark:bg-gray-800">
                                {sbmlEntries.map((entry, index) => (
                                    <tr key={entry.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                            {index + 1}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <span className="inline-flex rounded-full px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {getJenisKegiatanLabel(entry.jenis_kegiatan)}
                                            </span>
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4">
                                            <span className="inline-flex rounded-full px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                {getStatusKepegawaianLabel(entry.status_kepegawaian)}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            {getJenisPenugasanLabel(entry.jenis_penugasan)}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                            {formatCurrency(entry.honor_max)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
