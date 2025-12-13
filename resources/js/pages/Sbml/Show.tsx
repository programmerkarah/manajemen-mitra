import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Sbml } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Pencil, Trash2 } from 'lucide-react';

export default function Show({ sbml }: { sbml: Sbml }) {
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
        if (confirm('Apakah Anda yakin ingin menghapus SBML ini?')) {
            router.delete(`/sbml/${sbml.hashed_id}`);
        }
    };

    return (
        <>
            <Head title="Detail SBML" />

            <div className="flex flex-col gap-6">
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink asChild>
                                <Link href="/dashboard">Dashboard</Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbLink asChild>
                                <Link href="/sbml">SBML</Link>
                            </BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage>Detail</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>

                <div className="rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div className="flex flex-col space-y-1.5 p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-2xl font-semibold leading-none tracking-tight">
                                    Detail SBML
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Informasi lengkap batas honor maksimal
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Button size="sm" variant="outline" asChild>
                                    <Link href={`/sbml/${sbml.hashed_id}/edit`}>
                                        <Pencil className="mr-2 h-4 w-4" />
                                        Edit
                                    </Link>
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={handleDelete}
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Hapus
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div className="p-6 pt-0">
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Tahun Anggaran
                                    </p>
                                    <p className="text-lg font-semibold">
                                        {sbml.tahun_anggaran}
                                    </p>
                                </div>

                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Status
                                    </p>
                                    <div>
                                        <span
                                            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                                                sbml.status === 'aktif'
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                            }`}
                                        >
                                            {sbml.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div className="border-t pt-4">
                                <h4 className="mb-4 text-lg font-semibold">Informasi Detail</h4>
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Jenis Kegiatan
                                        </p>
                                        <p className="text-base font-medium">
                                            {getJenisKegiatanLabel(sbml.jenis_kegiatan)}
                                        </p>
                                    </div>

                                    <div className="space-y-1">
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Status Kepegawaian
                                        </p>
                                        <p className="text-base font-medium">
                                            {getStatusKepegawaianLabel(sbml.status_kepegawaian)}
                                        </p>
                                    </div>

                                    <div className="space-y-1">
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Jenis Penugasan
                                        </p>
                                        <p className="text-base font-medium">
                                            {getJenisPenugasanLabel(sbml.jenis_penugasan)}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="border-t pt-4">
                                <h4 className="mb-4 text-lg font-semibold">Batas Honor Maksimal per Bulan</h4>
                                <div className="rounded-lg border bg-muted/50 p-6">
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Maksimal honor yang dapat diterima
                                    </p>
                                    <p className="mt-2 text-3xl font-bold text-primary">
                                        {formatCurrency(sbml.honor_max)}
                                    </p>
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        Per bulan untuk {getJenisPenugasanLabel(sbml.jenis_penugasan).toLowerCase()}
                                    </p>
                                </div>
                            </div>

                            {sbml.keterangan && (
                                <div className="border-t pt-4">
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Keterangan
                                    </p>
                                    <p className="mt-2 text-sm">
                                        {sbml.keterangan}
                                    </p>
                                </div>
                            )}

                            <div className="border-t pt-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Dibuat
                                        </p>
                                        <p className="text-sm">
                                            {new Date(sbml.created_at).toLocaleString('id-ID')}
                                        </p>
                                    </div>

                                    <div className="space-y-1">
                                        <p className="text-sm font-medium text-muted-foreground">
                                            Terakhir Diubah
                                        </p>
                                        <p className="text-sm">
                                            {new Date(sbml.updated_at).toLocaleString('id-ID')}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="flex gap-2 border-t pt-4">
                                <Button variant="outline" asChild>
                                    <Link href="/sbml">Kembali</Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Show.layout = (page: React.ReactNode) => <AppLayout children={page} />;
