import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Eye, Plus, ChevronLeft, ChevronRight, FileEdit } from 'lucide-react';
import { StatusBadge } from '@/components/status-badge';

interface KegiatanItem {
    periode_id: number;
    periode_hashed_id: string;
    kegiatan_hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    jumlah_petugas_non_organik: number;
}

interface MonthlyPeriodeItem {
    tahun: number;
    bulan: number;
    bulan_label: string;
    total_petugas_non_organik: number;
    total_spk: number;
    spk_status: string;
    spk_status_type: 'not_created' | 'created';
    has_revision: boolean;
    has_addendum: boolean;
    has_new_kegiatan_after_spk: boolean; // Kegiatan baru setelah SPK di-generate
    has_new_revision_after_addendum: boolean; // Revisi baru setelah addendum di-generate
    has_been_regenerated: boolean; // SPK sudah pernah di-regenerate
    has_incomplete_addendum: boolean; // Ada petugas dengan revisi yang belum punya addendum
    has_addendum_changes: boolean; // Ada perubahan alokasi ke petugas yang sudah punya addendum
    kegiatan_list: KegiatanItem[];
}

interface IndexProps {
    periodeList: {
        data: MonthlyPeriodeItem[];
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        bulan?: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SPK', href: '/spk' },
];

export default function Index({ periodeList, filters }: IndexProps) {
    const { auth } = usePage<SharedData>().props;

    // Check if user can create SPK (only admin and pj)
    const canCreateSpk = auth.activeRole?.name === 'admin' || auth.activeRole?.name === 'approver';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="SPK" />

            <div className="space-y-6">
                <PageHeader
                    title="Surat Perjanjian Kerja (SPK)"
                    description="Kelola Surat Perjanjian Kerja untuk petugas per bulan"
                />

                {/* Table */}
                <ContentCard>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead className="bg-neutral-50 dark:bg-neutral-800">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Periode
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Kegiatan
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Jumlah Petugas Non Organik
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Total SPK
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Status SPK
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-neutral-700 dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                {!periodeList?.data || periodeList.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">
                                            Tidak ada periode yang memerlukan SPK
                                        </td>
                                    </tr>
                                ) : (
                                    periodeList.data.map((monthData, index) => (
                                        <tr key={`${monthData.tahun}-${monthData.bulan}`} className="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                            <td className="px-4 py-4 text-sm font-medium text-neutral-900 dark:text-white whitespace-nowrap">
                                                {monthData.bulan_label} {monthData.tahun}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="space-y-1 max-w-md">
                                                    {monthData.kegiatan_list.map((kegiatan, kegIndex) => (
                                                        <div key={kegiatan.periode_id} className="text-sm">
                                                            <div className="font-medium text-neutral-900 dark:text-white break-words">
                                                                {kegiatan.nama_kegiatan}
                                                            </div>
                                                            <div className="text-xs text-neutral-600 dark:text-neutral-400 break-words">
                                                                {kegiatan.kode_kegiatan} • {kegiatan.jumlah_petugas_non_organik} petugas non-organik
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-neutral-900 dark:text-white whitespace-nowrap">
                                                {monthData.total_petugas_non_organik} petugas
                                            </td>
                                            <td className="px-4 py-4 text-sm text-neutral-900 dark:text-white whitespace-nowrap">
                                                {monthData.total_spk} SPK
                                            </td>
                                            <td className="px-4 py-4 whitespace-nowrap">
                                                <StatusBadge status={monthData.spk_status_type} />
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-col items-end gap-1.5">
                                                    {/* Generate SPK - Show if:
                                                        1. No SPK exists at all (total_spk === 0), OR
                                                        2. Some petugas don't have SPK yet (total_spk < total_petugas_non_organik)
                                                        This will show form with ONLY petugas who don't have SPK
                                                    */}
                                                    {canCreateSpk && (monthData.total_spk === 0 || monthData.total_spk < monthData.total_petugas_non_organik) && monthData.kegiatan_list.length > 0 && (
                                                        <Button
                                                            size="sm"
                                                            asChild
                                                            className="gap-1 w-full justify-start"
                                                        >
                                                            <Link href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/generate`}>
                                                                <Plus className="h-3.5 w-3.5" />
                                                                Generate SPK
                                                            </Link>
                                                        </Button>
                                                    )}

                                                    {/* Re-generate SPK - Show if:
                                                        1. All petugas have SPK (total_spk >= total_petugas_non_organik)
                                                        2. AND there are new kegiatan added after SPK was generated
                                                        3. AND has NOT been regenerated before
                                                        This will show form with ONLY petugas who have allocation changes
                                                    */}
                                                    {canCreateSpk && monthData.total_spk >= monthData.total_petugas_non_organik && monthData.has_new_kegiatan_after_spk && !monthData.has_been_regenerated && (
                                                        <Button
                                                            size="sm"
                                                            variant="default"
                                                            asChild
                                                            className="gap-1 bg-orange-600 hover:bg-orange-700 w-full justify-start"
                                                        >
                                                            <Link href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/generate`}>
                                                                <Plus className="h-3.5 w-3.5" />
                                                                Re-generate SPK
                                                            </Link>
                                                        </Button>
                                                    )}

                                                    {/* View SPK Details - Always show if SPK exists */}
                                                    {monthData.total_spk > 0 && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => router.post('/spk/month', {
                                                                bulan: monthData.bulan,
                                                                tahun: monthData.tahun
                                                            })}
                                                            className="gap-1 w-full justify-start"
                                                        >
                                                            <Eye className="h-3.5 w-3.5" />
                                                            Lihat Detail
                                                        </Button>
                                                    )}

                                                    {/* Addendum SPK - Show if:
                                                        1. Has revisions AND
                                                        2. Some petugas don't have addendum yet (has_incomplete_addendum) AND
                                                        3. No allocation changes to existing addendum petugas (!has_addendum_changes)
                                                        This will show form with ONLY petugas who don't have addendum
                                                    */}
                                                    {canCreateSpk && monthData.total_spk > 0 && monthData.has_revision && monthData.has_incomplete_addendum && !monthData.has_addendum_changes && (
                                                        <Button
                                                            size="sm"
                                                            variant="default"
                                                            asChild
                                                            className="gap-1 w-full justify-start"
                                                        >
                                                            <Link href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/addendum?bulan=${monthData.bulan}&tahun=${monthData.tahun}`}>
                                                                <FileEdit className="h-3.5 w-3.5" />
                                                                Addendum SPK
                                                            </Link>
                                                        </Button>
                                                    )}

                                                    {/* Re-generate Addendum - Show if:
                                                        1. Has revisions AND
                                                        2. There are allocation changes to petugas who already have addendum
                                                        This will show form with ONLY petugas who have allocation changes
                                                    */}
                                                    {canCreateSpk && monthData.total_spk > 0 && monthData.has_revision && monthData.has_addendum_changes && (
                                                        <Button
                                                            size="sm"
                                                            variant="default"
                                                            asChild
                                                            className="gap-1 bg-purple-600 hover:bg-purple-700 w-full justify-start"
                                                        >
                                                            <Link href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/addendum?bulan=${monthData.bulan}&tahun=${monthData.tahun}`}>
                                                                <FileEdit className="h-3.5 w-3.5" />
                                                                Re-generate Addendum
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {periodeList?.data && periodeList.data.length > 0 && (
                        <div className="mt-4 flex items-center justify-between border-t border-neutral-200 px-6 py-3 dark:border-neutral-700">
                            <div className="text-sm text-neutral-700 dark:text-neutral-300">
                                Menampilkan {periodeList.from} hingga {periodeList.to} dari {periodeList.total} bulan
                            </div>
                            <div className="flex gap-2">
                                {periodeList.links.map((link, index) => {
                                    const isFirst = link.label.includes('Previous');
                                    const isLast = link.label.includes('Next');
                                    
                                    return (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`rounded px-3 py-1 text-sm ${
                                                link.active
                                                    ? 'bg-neutral-900 text-white dark:bg-white dark:text-neutral-900'
                                                    : 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700'
                                            } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                                        >
                                            {isFirst ? (
                                                <ChevronLeft className="h-4 w-4" />
                                            ) : isLast ? (
                                                <ChevronRight className="h-4 w-4" />
                                            ) : (
                                                <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                            )}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
