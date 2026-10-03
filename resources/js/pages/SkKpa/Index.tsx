import { ContentCard } from '@/components/content-card';
import { FilterField, FilterPanel } from '@/components/filter-panel';
import { SummaryCard } from '@/components/summary-card';
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
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { openFastDownload } from '@/utils/downloadUtils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import CheckCircle from 'lucide-react/icons/check-circle';
import ChevronDown from 'lucide-react/icons/chevron-down';
import ChevronLeft from 'lucide-react/icons/chevron-left';
import ChevronRight from 'lucide-react/icons/chevron-right';
import ChevronUp from 'lucide-react/icons/chevron-up';
import Download from 'lucide-react/icons/download';
import Eye from 'lucide-react/icons/eye';
import FileCheck from 'lucide-react/icons/file-check';
import FileText from 'lucide-react/icons/file-text';
import Plus from 'lucide-react/icons/plus';
import RefreshCw from 'lucide-react/icons/refresh-cw';

import { useMemo, useState } from 'react';

interface LatestSk {
    id: number;
    hashed_id: string;
    nomor_sk: string;
    tanggal_sk: string;
    tahun: number;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    file_path: string | null;
    signed_file_path: string | null;
    revision_acknowledged_at: string | null;
}

interface KegiatanItem {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    tahun_anggaran: number;
    ketua_tim: string;
    sk_status: string;
    sk_status_type: 'not_created' | 'created' | 'revision';
    sk_count: number;
    has_personnel_changes: boolean;
    latest_sk: LatestSk | null;
}

interface IndexProps {
    kegiatan: {
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
    summary: {
        total_kegiatan_aktif: number;
        total_sk_belum_dibuat: number;
        total_sk_digenerate: number;
        total_sk_disahkan: number;
    };
    filters: {
        encrypted?: string;
        decrypted?: {
            search?: string;
            jenis_kegiatan?: 'sensus' | 'survei';
        };
    };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'SK KPA', href: '/sk-kpa' }];

type SummaryModalType = 'active' | 'not_created' | 'generated' | 'signed';

export default function Index({ kegiatan, summary }: IndexProps) {
    const { auth } = usePage<SharedData>().props;
    const allKegiatan = useDecryptedData<KegiatanItem>(kegiatan.encrypted);

    const [search, setSearch] = useState('');
    const [jenisKegiatan, setJenisKegiatan] = useState('all');
    const [skStatusFilter, setSkStatusFilter] = useState<
        | 'all'
        | 'not_created'
        | 'draft'
        | 'signed'
        | 'cancelled'
        | 'needs_revision'
    >(() => {
        const hash = window.location.hash;
        if (hash === '#filter=not_created') return 'not_created';
        if (hash === '#filter=draft') return 'draft';
        if (hash === '#filter=signed') return 'signed';
        if (hash === '#filter=cancelled') return 'cancelled';
        if (hash === '#filter=needs_revision') return 'needs_revision';
        return 'all';
    });
    const [sortField, setSortField] = useState<
        'nama_kegiatan' | 'jenis_kegiatan' | 'tahun_anggaran' | 'sk_count'
    >('nama_kegiatan');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(15);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [summaryModalOpen, setSummaryModalOpen] = useState(false);
    const [summaryModalType, setSummaryModalType] =
        useState<SummaryModalType>('active');
    const [modalAlert, setModalAlert] = useState<{
        open: boolean;
        title: string;
        message: string;
    }>({
        open: false,
        title: '',
        message: '',
    });

    const showModalAlert = (title: string, message: string) => {
        setModalAlert({
            open: true,
            title,
            message,
        });
    };
    // Client-side filtering and sorting
    const filteredAndSortedKegiatan = useMemo(() => {
        let result: KegiatanItem[] = [...allKegiatan];

        // Filter by search
        if (search) {
            const query = search.toLowerCase();
            result = result.filter(
                (item: KegiatanItem) =>
                    item.nama_kegiatan?.toLowerCase().includes(query) ||
                    item.kode_kegiatan?.toLowerCase().includes(query),
            );
        }

        // Filter by jenis kegiatan
        if (jenisKegiatan && jenisKegiatan !== 'all') {
            result = result.filter(
                (item: KegiatanItem) => item.jenis_kegiatan === jenisKegiatan,
            );
        }

        // Sort
        result.sort((a: KegiatanItem, b: KegiatanItem) => {
            let aVal: string | number = '',
                bVal: string | number = '';
            switch (sortField) {
                case 'sk_count':
                    aVal = a.sk_count || 0;
                    bVal = b.sk_count || 0;
                    break;
                case 'jenis_kegiatan':
                    aVal = a.jenis_kegiatan?.toLowerCase() || '';
                    bVal = b.jenis_kegiatan?.toLowerCase() || '';
                    break;
                case 'tahun_anggaran':
                    aVal = a.tahun_anggaran || 0;
                    bVal = b.tahun_anggaran || 0;
                    break;
                case 'nama_kegiatan':
                default:
                    aVal = a.nama_kegiatan?.toLowerCase() || '';
                    bVal = b.nama_kegiatan?.toLowerCase() || '';
                    break;
            }
            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        // Filter by SK status (from dashboard attention item)
        if (skStatusFilter === 'not_created') {
            result = result.filter((item: KegiatanItem) => item.sk_count === 0);
        } else if (skStatusFilter === 'draft') {
            result = result.filter(
                (item: KegiatanItem) =>
                    item.sk_count > 0 &&
                    item.latest_sk !== null &&
                    item.latest_sk.status !== 'dibatalkan' &&
                    !item.latest_sk.signed_file_path,
            );
        } else if (skStatusFilter === 'signed') {
            result = result.filter((item: KegiatanItem) =>
                Boolean(item.latest_sk?.signed_file_path),
            );
        } else if (skStatusFilter === 'cancelled') {
            result = result.filter(
                (item: KegiatanItem) => item.latest_sk?.status === 'dibatalkan',
            );
        } else if (skStatusFilter === 'needs_revision') {
            result = result.filter(
                (item: KegiatanItem) =>
                    item.has_personnel_changes &&
                    item.sk_count > 0 &&
                    !item.latest_sk?.revision_acknowledged_at,
            );
        }

        return result;
    }, [
        allKegiatan,
        search,
        jenisKegiatan,
        skStatusFilter,
        sortField,
        sortDirection,
    ]);

    const totalPages = Math.ceil(filteredAndSortedKegiatan.length / perPage);
    const effectiveCurrentPage =
        totalPages > 0 ? Math.min(currentPage, totalPages) : 1;

    const paginatedKegiatan = useMemo(() => {
        const start = (effectiveCurrentPage - 1) * perPage;
        const end = start + perPage;
        return filteredAndSortedKegiatan.slice(start, end);
    }, [filteredAndSortedKegiatan, effectiveCurrentPage, perPage]);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 500);
            },
        });
    };

    const handleSort = (
        field:
            | 'nama_kegiatan'
            | 'jenis_kegiatan'
            | 'tahun_anggaran'
            | 'sk_count',
    ) => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const renderSortIcon = (
        field:
            | 'nama_kegiatan'
            | 'jenis_kegiatan'
            | 'tahun_anggaran'
            | 'sk_count',
    ) => {
        if (sortField !== field) return null;
        return sortDirection === 'asc' ? (
            <ChevronUp className="h-4 w-4" />
        ) : (
            <ChevronDown className="h-4 w-4" />
        );
    };

    // Check if user can create SK (admin, pj, operator)
    const canCreateSk =
        auth.activeRole?.name === 'admin' ||
        auth.activeRole?.name === 'pj' ||
        auth.activeRole?.name === 'operator';

    const handleReset = () => {
        setSearch('');
        setJenisKegiatan('all');
        setSkStatusFilter('all');
        setCurrentPage(1);
        // Remove hash so filter is cleared visually
        if (window.location.hash) {
            history.replaceState(
                null,
                '',
                window.location.pathname + window.location.search,
            );
        }
    };

    const openSummaryModal = (type: SummaryModalType) => {
        setSummaryModalType(type);
        setSummaryModalOpen(true);
    };

    const summaryModalItems = useMemo(() => {
        switch (summaryModalType) {
            case 'not_created':
                return allKegiatan.filter((item) => item.sk_count === 0);
            case 'generated':
                return allKegiatan.filter((item) => item.sk_count > 0);
            case 'signed':
                return allKegiatan.filter(
                    (item) => item.latest_sk?.signed_file_path,
                );
            case 'active':
            default:
                return allKegiatan;
        }
    }, [summaryModalType, allKegiatan]);

    const summaryModalTitle = useMemo(() => {
        switch (summaryModalType) {
            case 'not_created':
                return 'Daftar SK Belum Dibuat';
            case 'generated':
                return 'Daftar SK di Generate';
            case 'signed':
                return 'Daftar SK Disahkan';
            case 'active':
            default:
                return 'Daftar Kegiatan Aktif';
        }
    }, [summaryModalType]);

    const handleDownload = (keg: KegiatanItem) => {
        const latestSk = keg.latest_sk;
        if (!latestSk) {
            showModalAlert('File Tidak Tersedia', 'SK tidak tersedia.');
            return;
        }

        // Prioritaskan signed file jika ada
        const filePath = latestSk.signed_file_path || latestSk.file_path;
        if (!filePath) {
            showModalAlert('File Tidak Tersedia', 'File SK tidak tersedia.');
            return;
        }

        openFastDownload(filePath);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="SK KPA" />

            <Dialog
                open={modalAlert.open}
                onOpenChange={(open) =>
                    setModalAlert((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{modalAlert.title}</DialogTitle>
                        <DialogDescription>
                            {modalAlert.message}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() =>
                                setModalAlert((prev) => ({
                                    ...prev,
                                    open: false,
                                }))
                            }
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="space-y-5">
                <PageHeader
                    title="SK KPA"
                    description="Kelola Surat Keputusan Kuasa Pengguna Anggaran untuk setiap kegiatan"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={handleRefresh}
                        disabled={isRefreshing}
                    >
                        <RefreshCw
                            className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`}
                        />
                        Perbarui Data
                    </Button>
                </PageHeader>

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        label="Kegiatan Aktif"
                        value={summary.total_kegiatan_aktif}
                        icon={<FileText className="h-5 w-5" />}
                        accent="blue"
                        onClick={() => openSummaryModal('active')}
                    />
                    <SummaryCard
                        label="SK Belum Dibuat"
                        value={summary.total_sk_belum_dibuat}
                        icon={<Plus className="h-5 w-5" />}
                        accent="orange"
                        onClick={() => openSummaryModal('not_created')}
                    />
                    <SummaryCard
                        label="SK Digenerate"
                        value={summary.total_sk_digenerate}
                        icon={<Eye className="h-5 w-5" />}
                        accent="violet"
                        onClick={() => openSummaryModal('generated')}
                    />
                    <SummaryCard
                        label="SK Disahkan"
                        value={summary.total_sk_disahkan}
                        icon={<FileCheck className="h-5 w-5" />}
                        accent="green"
                        onClick={() => openSummaryModal('signed')}
                    />
                </div>

                {/* Filter & Search */}
                <FilterPanel
                    fieldsClassName="lg:grid-cols-[minmax(0,1.4fr)_minmax(0,0.8fr)_minmax(0,0.95fr)]"
                    footer={
                        <>
                            <p>
                                Menampilkan{' '}
                                {filteredAndSortedKegiatan.length === 0
                                    ? 0
                                    : (effectiveCurrentPage - 1) * perPage + 1}
                                -
                                {Math.min(
                                    effectiveCurrentPage * perPage,
                                    filteredAndSortedKegiatan.length,
                                )}{' '}
                                dari {filteredAndSortedKegiatan.length} data
                                {filteredAndSortedKegiatan.length !==
                                    allKegiatan.length &&
                                    ` (difilter dari ${allKegiatan.length} total)`}
                            </p>
                            {(search ||
                                jenisKegiatan !== 'all' ||
                                skStatusFilter !== 'all') && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleReset}
                                    className="h-8 px-2 text-muted-foreground"
                                >
                                    Reset filter
                                </Button>
                            )}
                        </>
                    }
                >
                    <FilterField label="Cari Kegiatan">
                        <Input
                            id="search"
                            type="text"
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setCurrentPage(1);
                            }}
                            placeholder="Nama atau kode kegiatan..."
                            className="h-10 w-full"
                        />
                    </FilterField>

                    <FilterField label="Jenis Kegiatan">
                        <Select
                            value={jenisKegiatan}
                            onValueChange={(value) => {
                                setJenisKegiatan(value);
                                setCurrentPage(1);
                            }}
                        >
                            <SelectTrigger className="h-10 w-full">
                                <SelectValue placeholder="Semua Jenis" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua</SelectItem>
                                <SelectItem value="sensus">Sensus</SelectItem>
                                <SelectItem value="survei">Survei</SelectItem>
                            </SelectContent>
                        </Select>
                    </FilterField>

                    <FilterField label="Status SK">
                        <Select
                            value={skStatusFilter}
                            onValueChange={(value) => {
                                setSkStatusFilter(
                                    value as typeof skStatusFilter,
                                );
                                setCurrentPage(1);
                            }}
                        >
                            <SelectTrigger id="status_sk" className="h-10 w-full">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="not_created">
                                    Belum Dibuat
                                </SelectItem>
                                <SelectItem value="draft">
                                    Draft / Belum Ditandatangani
                                </SelectItem>
                                <SelectItem value="signed">
                                    Ditandatangani
                                </SelectItem>
                                <SelectItem value="cancelled">
                                    Dibatalkan
                                </SelectItem>
                                <SelectItem value="needs_revision">
                                    Perlu Perubahan
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </FilterField>
                </FilterPanel>

                {/* Active SK status filter indicator */}
                {skStatusFilter !== 'all' && (
                    <div className="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm dark:border-amber-800/50 dark:bg-amber-900/20">
                        <FileText className="size-4 text-amber-600 dark:text-amber-400" />
                        <span className="text-amber-800 dark:text-amber-300">
                            {
                                {
                                    not_created:
                                        'Menampilkan kegiatan yang belum memiliki SK KPA',
                                    draft: 'Menampilkan SK yang belum ditandatangani',
                                    signed: 'Menampilkan SK yang sudah ditandatangani',
                                    cancelled: 'Menampilkan SK yang dibatalkan',
                                    needs_revision:
                                        'Menampilkan kegiatan yang perlu pembaruan SK KPA',
                                }[skStatusFilter]
                            }
                        </span>
                        <button
                            type="button"
                            onClick={handleReset}
                            className="ml-auto text-amber-600 underline hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-200"
                        >
                            Hapus filter
                        </button>
                    </div>
                )}

                {/* Table */}
                <ContentCard padding="none">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th
                                        className="cursor-pointer px-3 py-3 text-left text-sm font-semibold text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() =>
                                            handleSort('nama_kegiatan')
                                        }
                                    >
                                        <div className="flex items-center gap-1">
                                            Kegiatan
                                            {renderSortIcon('nama_kegiatan')}
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() =>
                                            handleSort('jenis_kegiatan')
                                        }
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Jenis
                                            {renderSortIcon('jenis_kegiatan')}
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() =>
                                            handleSort('tahun_anggaran')
                                        }
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Tahun
                                            {renderSortIcon('tahun_anggaran')}
                                        </div>
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Ketua Tim
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Status SK
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        File
                                    </th>
                                    <th className="px-3 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {paginatedKegiatan.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            {filteredAndSortedKegiatan.length ===
                                                0 && allKegiatan.length > 0
                                                ? 'Tidak ada data yang sesuai dengan filter'
                                                : 'Tidak ada kegiatan yang memerlukan SK KPA'}
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedKegiatan.map((keg) => (
                                        <tr
                                            key={keg.id}
                                            className="transition-colors hover:bg-neutral-50/80 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-3 py-2.5">
                                                <div>
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {keg.nama_kegiatan}
                                                    </div>
                                                    <div className="mt-0.5 text-sm text-neutral-600 dark:text-neutral-400">
                                                        {keg.latest_sk
                                                            ? `SK Nomor ${keg.latest_sk.nomor_sk} Tahun ${keg.latest_sk.tahun}`
                                                            : 'Belum dibuat SK KPA'}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-3 py-2.5 text-center text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                <span className="capitalize">
                                                    {keg.jenis_kegiatan}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-white">
                                                {keg.tahun_anggaran}
                                            </td>
                                            <td className="px-3 py-2.5 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {keg.ketua_tim}
                                            </td>
                                            <td className="px-3 py-2.5 text-center">
                                                <StatusBadge
                                                    status={keg.sk_status_type}
                                                />
                                            </td>
                                            <td className="px-3 py-2.5 text-center">
                                                {keg.latest_sk ? (
                                                    keg.latest_sk
                                                        .signed_file_path ? (
                                                        <StatusBadge
                                                            status="signed"
                                                            label="Ditandatangani"
                                                        />
                                                    ) : (
                                                        <StatusBadge
                                                            status="unsigned"
                                                            label="Draft"
                                                        />
                                                    )
                                                ) : (
                                                    <span className="text-neutral-400 dark:text-neutral-500">
                                                        -
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2.5">
                                                <div className="flex items-center justify-center gap-1.5">
                                                    {/* Buat SK / Buat SK Perubahan - Admin, PJ, and Operator */}
                                                    {canCreateSk && (
                                                        <>
                                                            {keg.sk_count ===
                                                                0 && (
                                                                <Button
                                                                    size="sm"
                                                                    asChild
                                                                >
                                                                    <Link
                                                                        href={`/sk-kpa/kegiatan/${keg.hashed_id}/create`}
                                                                    >
                                                                        <Plus className="h-3.5 w-3.5" />
                                                                    </Link>
                                                                </Button>
                                                            )}

                                                            {keg.sk_count > 0 &&
                                                                keg.has_personnel_changes &&
                                                                !keg.latest_sk
                                                                    ?.revision_acknowledged_at && (
                                                                    <>
                                                                        <Button
                                                                            size="sm"
                                                                            variant="outline"
                                                                            asChild
                                                                            title="Buat SK Perubahan"
                                                                        >
                                                                            <Link
                                                                                href={`/sk-kpa/kegiatan/${keg.hashed_id}/create`}
                                                                            >
                                                                                <Plus className="h-3.5 w-3.5" />
                                                                            </Link>
                                                                        </Button>
                                                                        <Button
                                                                            size="sm"
                                                                            variant="ghost"
                                                                            title="Tandai tidak perlu revisi SK"
                                                                            onClick={() =>
                                                                                keg.latest_sk &&
                                                                                router.post(
                                                                                    `/sk-kpa/${keg.latest_sk.hashed_id}/acknowledge-revision`,
                                                                                    {},
                                                                                    {
                                                                                        preserveScroll: true,
                                                                                    },
                                                                                )
                                                                            }
                                                                        >
                                                                            <CheckCircle className="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
                                                                        </Button>
                                                                    </>
                                                                )}
                                                        </>
                                                    )}
                                                    {keg.latest_sk && (
                                                        <Button
                                                            size="sm"
                                                            variant="secondary"
                                                            asChild
                                                            title="Lihat Detail"
                                                        >
                                                            <Link
                                                                href={`/sk-kpa/${keg.latest_sk.hashed_id}`}
                                                            >
                                                                <Eye className="h-3.5 w-3.5" />
                                                            </Link>
                                                        </Button>
                                                    )}

                                                    {keg.latest_sk
                                                        ?.file_path && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                handleDownload(
                                                                    keg,
                                                                )
                                                            }
                                                            title="Download SK"
                                                        >
                                                            <Download className="h-3.5 w-3.5" />
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

                    {totalPages > 1 && (
                        <div className="mt-4 flex items-center justify-between border-t border-neutral-200 px-6 py-3 dark:border-neutral-700">
                            <div className="text-sm text-neutral-700 dark:text-neutral-300">
                                Halaman {effectiveCurrentPage} dari {totalPages}
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage(effectiveCurrentPage - 1)
                                    }
                                    disabled={effectiveCurrentPage === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>
                                {Array.from(
                                    { length: totalPages },
                                    (_, i) => i + 1,
                                ).map((page) => (
                                    <Button
                                        key={page}
                                        type="button"
                                        variant={
                                            effectiveCurrentPage === page
                                                ? 'default'
                                                : 'outline'
                                        }
                                        size="sm"
                                        onClick={() => setCurrentPage(page)}
                                    >
                                        {page}
                                    </Button>
                                ))}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage(effectiveCurrentPage + 1)
                                    }
                                    disabled={
                                        effectiveCurrentPage === totalPages
                                    }
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </ContentCard>

                <Dialog
                    open={summaryModalOpen}
                    onOpenChange={setSummaryModalOpen}
                >
                    <DialogContent className="sm:max-w-7xl">
                        <DialogHeader>
                            <DialogTitle>{summaryModalTitle}</DialogTitle>
                            <DialogDescription>
                                Klik aksi pada kegiatan untuk proses lanjutan.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="max-h-[60vh] space-y-2 overflow-y-auto pr-1">
                            {summaryModalItems.length === 0 ? (
                                <div className="rounded-md border border-dashed border-neutral-300 px-4 py-8 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                                    Tidak ada data untuk kategori ini.
                                </div>
                            ) : (
                                summaryModalItems.map((item) => (
                                    <div
                                        key={`summary-${item.id}`}
                                        className="flex items-center justify-between rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-800"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {item.nama_kegiatan}
                                            </p>
                                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                {item.sk_count} SK
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {canCreateSk &&
                                                item.sk_count === 0 && (
                                                    <Button size="sm" asChild>
                                                        <Link
                                                            href={`/sk-kpa/kegiatan/${item.hashed_id}/create`}
                                                        >
                                                            <Plus className="h-3.5 w-3.5" />
                                                        </Link>
                                                    </Button>
                                                )}
                                            {item.latest_sk && (
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/sk-kpa/${item.latest_sk.hashed_id}`}
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
