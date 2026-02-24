import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Copy,
    Eye,
    FileEdit,
    Plus,
} from 'lucide-react';
import { useState } from 'react';

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
        encrypted: string;
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        search?: string;
        bulan?: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Perjanjian Kerja', href: '/spk' },
];

export default function Index({ periodeList, filters }: IndexProps) {
    const { auth } = usePage<SharedData>().props;
    const decryptedPeriodeList = useDecryptedData<MonthlyPeriodeItem>(
        periodeList.encrypted,
    );
    const [copyingMonth, setCopyingMonth] = useState<string | null>(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [modalContent, setModalContent] = useState<{
        type: 'success' | 'error';
        title: string;
        message: string;
    }>({ type: 'success', title: '', message: '' });

    // Check if user can create SPK (only admin and pj)
    const canCreateSpk =
        auth.activeRole?.name === 'admin' ||
        auth.activeRole?.name === 'approver';

    const handleCopyPetugasNames = async (
        bulan: number,
        tahun: number,
        bulanLabel: string,
    ) => {
        const monthKey = `${tahun}-${bulan}`;
        setCopyingMonth(monthKey);

        try {
            const response = await fetch('/spk/petugas-names', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: JSON.stringify({ bulan, tahun }),
            });

            if (!response.ok) {
                throw new Error('Gagal mengambil data petugas');
            }

            const data = await response.json();
            const names = data.names as string[];

            if (names.length === 0) {
                setModalContent({
                    type: 'error',
                    title: 'Gagal',
                    message: 'Tidak ada petugas untuk periode ini',
                });
                setModalOpen(true);
                return;
            }

            // Format: 1. Nama1\n2. Nama2\n...\n20. NamaX
            const formattedNames = names
                .map((name, index) => `${index + 1}. ${name}`)
                .join('\n');

            // Copy to clipboard
            await navigator.clipboard.writeText(formattedNames);
            setModalContent({
                type: 'success',
                title: 'Berhasil',
                message: `${data.count} nama petugas ${bulanLabel} ${tahun} berhasil disalin ke clipboard`,
            });
            setModalOpen(true);
        } catch (error) {
            console.error('Error copying petugas names:', error);
            setModalContent({
                type: 'error',
                title: 'Gagal',
                message: 'Gagal menyalin nama petugas',
            });
            setModalOpen(true);
        } finally {
            setCopyingMonth(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Perjanjian Kerja" />

            <div className="space-y-6">
                <PageHeader
                    title="Perjanjian Kerja"
                    description="Kelola Perjanjian Kerja untuk petugas per bulan"
                />

                {/* Table */}
                <ContentCard>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead className="bg-neutral-50 dark:bg-neutral-800">
                                <tr>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Periode
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Kegiatan
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Jumlah Petugas Non Organik
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Total Perjanjian Kerja
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Status Perjanjian Kerja
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                {!decryptedPeriodeList ||
                                decryptedPeriodeList.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400"
                                        >
                                            Tidak ada periode yang memerlukan
                                            Perjanjian Kerja
                                        </td>
                                    </tr>
                                ) : (
                                    decryptedPeriodeList.map(
                                        (monthData, index) => (
                                            <tr
                                                key={`${monthData.tahun}-${monthData.bulan}`}
                                                className="hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                            >
                                                <td className="px-4 py-4 text-center text-sm font-medium whitespace-nowrap text-neutral-900 dark:text-white">
                                                    {monthData.bulan_label}{' '}
                                                    {monthData.tahun}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div className="max-w-md space-y-1">
                                                        {monthData.kegiatan_list.map(
                                                            (
                                                                kegiatan,
                                                                kegIndex,
                                                            ) => (
                                                                <div
                                                                    key={
                                                                        kegiatan.periode_id
                                                                    }
                                                                    className="text-sm"
                                                                >
                                                                    <div className="font-medium break-words text-neutral-900 dark:text-white">
                                                                        {
                                                                            kegiatan.nama_kegiatan
                                                                        }
                                                                    </div>
                                                                    <div className="text-xs break-words text-neutral-600 dark:text-neutral-400">
                                                                        {
                                                                            kegiatan.kode_kegiatan
                                                                        }{' '}
                                                                        •{' '}
                                                                        {
                                                                            kegiatan.jumlah_petugas_non_organik
                                                                        }{' '}
                                                                        petugas
                                                                        non-organik
                                                                    </div>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4 text-sm whitespace-nowrap text-neutral-900 dark:text-white">
                                                    {
                                                        monthData.total_petugas_non_organik
                                                    }{' '}
                                                    petugas
                                                </td>
                                                <td className="px-4 py-4 text-center text-sm whitespace-nowrap text-neutral-900 dark:text-white">
                                                    {monthData.total_spk} SPK
                                                </td>
                                                <td className="px-4 py-4 text-center whitespace-nowrap">
                                                    <StatusBadge
                                                        status={
                                                            monthData.spk_status_type
                                                        }
                                                    />
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div className="flex flex-col items-end gap-1.5">
                                                        {/* Copy Petugas Names - Show if there are petugas */}
                                                        {monthData.total_petugas_non_organik >
                                                            0 && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() =>
                                                                    handleCopyPetugasNames(
                                                                        monthData.bulan,
                                                                        monthData.tahun,
                                                                        monthData.bulan_label,
                                                                    )
                                                                }
                                                                disabled={
                                                                    copyingMonth ===
                                                                    `${monthData.tahun}-${monthData.bulan}`
                                                                }
                                                                className="w-full justify-start gap-1"
                                                            >
                                                                <Copy className="h-3.5 w-3.5" />
                                                                {copyingMonth ===
                                                                `${monthData.tahun}-${monthData.bulan}`
                                                                    ? 'Menyalin...'
                                                                    : 'Salin Nama Petugas'}
                                                            </Button>
                                                        )}

                                                        {/* Generate SPK - Show if:
                                                        1. No SPK exists at all (total_spk === 0), OR
                                                        2. Some petugas don't have SPK yet (total_spk < total_petugas_non_organik)
                                                        This will show form with ONLY petugas who don't have SPK
                                                    */}
                                                        {canCreateSpk &&
                                                            (monthData.total_spk ===
                                                                0 ||
                                                                monthData.total_spk <
                                                                    monthData.total_petugas_non_organik) &&
                                                            monthData
                                                                .kegiatan_list
                                                                .length > 0 && (
                                                                <Button
                                                                    size="sm"
                                                                    asChild
                                                                    className="w-full justify-start gap-1"
                                                                >
                                                                    <Link
                                                                        href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/generate`}
                                                                    >
                                                                        <Plus className="h-3.5 w-3.5" />
                                                                        Generate
                                                                        Perjanjian
                                                                        Kerja
                                                                    </Link>
                                                                </Button>
                                                            )}

                                                        {/* Re-generate SPK - Show if:
                                                        1. All petugas have SPK (total_spk >= total_petugas_non_organik)
                                                        2. AND there are new kegiatan added after SPK was generated
                                                        3. AND has NOT been regenerated before
                                                        This will show form with ONLY petugas who have allocation changes
                                                    */}
                                                        {canCreateSpk &&
                                                            monthData.total_spk >=
                                                                monthData.total_petugas_non_organik &&
                                                            monthData.has_new_kegiatan_after_spk &&
                                                            !monthData.has_been_regenerated && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="default"
                                                                    asChild
                                                                    className="w-full justify-start gap-1 bg-orange-600 hover:bg-orange-700"
                                                                >
                                                                    <Link
                                                                        href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/generate`}
                                                                    >
                                                                        <Plus className="h-3.5 w-3.5" />
                                                                        Re-generate
                                                                        Perjanjian
                                                                        Kerja
                                                                    </Link>
                                                                </Button>
                                                            )}

                                                        {/* View SPK Details - Always show if SPK exists */}
                                                        {monthData.total_spk >
                                                            0 && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() =>
                                                                    router.post(
                                                                        '/spk/month',
                                                                        {
                                                                            bulan: monthData.bulan,
                                                                            tahun: monthData.tahun,
                                                                        },
                                                                    )
                                                                }
                                                                className="w-full justify-start gap-1"
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
                                                        {canCreateSpk &&
                                                            monthData.total_spk >
                                                                0 &&
                                                            monthData.has_revision &&
                                                            monthData.has_incomplete_addendum &&
                                                            !monthData.has_addendum_changes && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="default"
                                                                    asChild
                                                                    className="w-full justify-start gap-1"
                                                                >
                                                                    <Link
                                                                        href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/addendum?bulan=${monthData.bulan}&tahun=${monthData.tahun}`}
                                                                    >
                                                                        <FileEdit className="h-3.5 w-3.5" />
                                                                        Addendum
                                                                        Perjanjian
                                                                        Kerja
                                                                    </Link>
                                                                </Button>
                                                            )}

                                                        {/* Re-generate Addendum - Show if:
                                                        1. Has revisions AND
                                                        2. There are allocation changes to petugas who already have addendum
                                                        This will show form with ONLY petugas who have allocation changes
                                                    */}
                                                        {canCreateSpk &&
                                                            monthData.total_spk >
                                                                0 &&
                                                            monthData.has_revision &&
                                                            monthData.has_addendum_changes && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="default"
                                                                    asChild
                                                                    className="w-full justify-start gap-1 bg-purple-600 hover:bg-purple-700"
                                                                >
                                                                    <Link
                                                                        href={`/spk/periode/${monthData.kegiatan_list[0].periode_hashed_id}/addendum?bulan=${monthData.bulan}&tahun=${monthData.tahun}`}
                                                                    >
                                                                        <FileEdit className="h-3.5 w-3.5" />
                                                                        Re-generate
                                                                        Addendum
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ),
                                    )
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {decryptedPeriodeList &&
                        decryptedPeriodeList.length > 0 && (
                            <div className="mt-4 flex items-center justify-between border-t border-neutral-200 px-6 py-3 dark:border-neutral-700">
                                <div className="text-sm text-neutral-700 dark:text-neutral-300">
                                    Menampilkan {periodeList.meta.from} hingga{' '}
                                    {periodeList.meta.to} dari{' '}
                                    {periodeList.meta.total} bulan
                                </div>
                                <div className="flex gap-2">
                                    {periodeList.links.map((link, index) => {
                                        const isFirst =
                                            link.label.includes('Previous');
                                        const isLast =
                                            link.label.includes('Next');

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
                                                    <span
                                                        dangerouslySetInnerHTML={{
                                                            __html: link.label,
                                                        }}
                                                    />
                                                )}
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                </ContentCard>

                {/* Modal for copy result */}
                <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle
                                className={
                                    modalContent.type === 'error'
                                        ? 'text-red-600 dark:text-red-400'
                                        : 'text-green-600 dark:text-green-400'
                                }
                            >
                                {modalContent.title}
                            </DialogTitle>
                            <DialogDescription className="text-base">
                                {modalContent.message}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button
                                onClick={() => setModalOpen(false)}
                                className="w-full sm:w-auto"
                            >
                                Tutup
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
