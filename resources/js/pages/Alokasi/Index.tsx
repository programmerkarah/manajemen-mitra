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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Kegiatan, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    ChevronLeft,
    ChevronRight,
    Copy,
    Edit2,
    Eye,
    MoreVertical,
    Plus,
    RefreshCw,
    RotateCcw,
    Search,
    Send,
    Users,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

interface AlokasiPeriod {
    kegiatan_id: number;
    periode_id: number;
    bulan: string;
    tahun: number;
    jenis_kegiatan: 'sensus' | 'survei';
    status: 'draft' | 'dikirim' | 'direvisi' | 'dihapus' | 'perubahan';
    jumlah_petugas: number;
    total_honor: number;
    estimasi_honor: number;
    sisa_pagu: number;
    pagu_pencacahan: number;
    pagu_listing: number;
    pagu_terpakai: number;
    latest_created_at: string;
    is_latest_periode: boolean;
    has_completed_revision_cycle: boolean;
    has_spk_generated: boolean;
    has_non_organik_spk_in_kegiatan: boolean;
    kegiatan: Kegiatan;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Alokasi', href: '#' }];

interface Props {
    alokasi: {
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
        encrypted?: string;
        decrypted?: {
            search?: string;
            status?: string;
            bulan?: string;
        };
    };
    hasKegiatans: boolean;
}

type SummaryCardType = 'all' | 'draft' | 'dikirim' | 'revisi';

export default function Index({ alokasi, hasKegiatans }: Props) {
    const { auth } = usePage<SharedData>().props;

    // Decrypt data once with memoization for filtering/sorting
    const decryptedAlokasi = useDecryptedData<AlokasiPeriod>(alokasi.encrypted);
    const isPJ = auth.activeRole?.name === 'pj';
    const isAdmin = auth.activeRole?.name === 'admin';
    const isOperator = auth.activeRole?.name === 'operator';
    const isAdminOrOperator = isAdmin || isOperator;

    // State for client-side filtering
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [bulan, setBulan] = useState('all');
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 15;
    const prevFiltersRef = useRef({ search, status, bulan });

    // Client-side filtering
    const filteredAlokasi = useMemo(() => {
        let result = [...decryptedAlokasi];

        // Filter by search (kegiatan name or description)
        if (search) {
            const query = search.toLowerCase();
            result = result.filter(
                (periode) =>
                    periode.kegiatan.nama_kegiatan
                        .toLowerCase()
                        .includes(query) ||
                    (periode.kegiatan.deskripsi || '')
                        .toLowerCase()
                        .includes(query),
            );
        }

        // Filter by status
        if (status && status !== 'all') {
            result = result.filter((periode) => periode.status === status);
        }

        // Filter by bulan
        if (bulan && bulan !== 'all') {
            result = result.filter((periode) => periode.bulan === bulan);
        }

        return result;
    }, [decryptedAlokasi, search, status, bulan]);

    // Client-side pagination
    const paginatedAlokasi = useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;
        const endIndex = startIndex + perPage;
        return filteredAlokasi.slice(startIndex, endIndex);
    }, [filteredAlokasi, currentPage, perPage]);

    const totalPages = Math.ceil(filteredAlokasi.length / perPage);

    const alokasiSummary = useMemo(() => {
        const totalPeriode = decryptedAlokasi.length;
        const totalDraft = decryptedAlokasi.filter(
            (item) => item.status === 'draft',
        ).length;
        const totalDikirim = decryptedAlokasi.filter(
            (item) => item.status === 'dikirim',
        ).length;
        const totalPerubahan = decryptedAlokasi.filter(
            (item) => item.status === 'perubahan' || item.status === 'direvisi',
        ).length;

        return {
            totalPeriode,
            totalDraft,
            totalDikirim,
            totalPerubahan,
        };
    }, [decryptedAlokasi]);

    // Reset to page 1 when filters change
    useEffect(() => {
        const prevFilters = prevFiltersRef.current;
        if (
            prevFilters.search !== search ||
            prevFilters.status !== status ||
            prevFilters.bulan !== bulan
        ) {
            // eslint-disable-next-line react-hooks/set-state-in-effect -- Conditional reset based on filter change via ref
            setCurrentPage(1);
            prevFiltersRef.current = { search, status, bulan };
        }
    }, [search, status, bulan]);

    // Modal states
    const [showKirimModal, setShowKirimModal] = useState(false);
    const [showBatalkanModal, setShowBatalkanModal] = useState(false);
    const [showBatalkanRevisiModal, setShowBatalkanRevisiModal] =
        useState(false);
    const [showRevisiModal, setShowRevisiModal] = useState(false);
    const [showKembalikanDraftModal, setShowKembalikanDraftModal] =
        useState(false);
    const [showSummaryModal, setShowSummaryModal] = useState(false);
    const [summaryCardType, setSummaryCardType] =
        useState<SummaryCardType>('all');
    const [selectedPeriode, setSelectedPeriode] = useState<{
        kegiatanId: number;
        kegiatanHashedId?: string;
        bulan: string;
        tahun: number;
        namaKegiatan?: string;
    } | null>(null);

    const bulanOptions = [
        { value: '01', label: 'Januari' },
        { value: '02', label: 'Februari' },
        { value: '03', label: 'Maret' },
        { value: '04', label: 'April' },
        { value: '05', label: 'Mei' },
        { value: '06', label: 'Juni' },
        { value: '07', label: 'Juli' },
        { value: '08', label: 'Agustus' },
        { value: '09', label: 'September' },
        { value: '10', label: 'Oktober' },
        { value: '11', label: 'November' },
        { value: '12', label: 'Desember' },
    ];

    // Function to check if periode is current month or previous month
    const canRevisiPeriode = (bulan: string, tahun: number): boolean => {
        const now = new Date();
        const currentMonth = now.getMonth() + 1; // getMonth() returns 0-11
        const currentYear = now.getFullYear();

        const periodeMonth = parseInt(bulan);
        const periodeYear = tahun;

        // Check if it's current month
        if (periodeYear === currentYear && periodeMonth === currentMonth) {
            return true;
        }

        // Check if it's previous month
        const previousMonth = currentMonth === 1 ? 12 : currentMonth - 1;
        const previousMonthYear =
            currentMonth === 1 ? currentYear - 1 : currentYear;

        if (
            periodeYear === previousMonthYear &&
            periodeMonth === previousMonth
        ) {
            return true;
        }

        return false;
    };

    const handleOpenSummaryModal = (type: SummaryCardType) => {
        setSummaryCardType(type);
        setShowSummaryModal(true);
    };

    const summaryModalItems = useMemo(() => {
        switch (summaryCardType) {
            case 'draft':
                return decryptedAlokasi.filter(
                    (item) => item.status === 'draft',
                );
            case 'dikirim':
                return decryptedAlokasi.filter(
                    (item) => item.status === 'dikirim',
                );
            case 'revisi':
                return decryptedAlokasi.filter(
                    (item) =>
                        item.status === 'perubahan' ||
                        item.status === 'direvisi',
                );
            case 'all':
            default:
                return decryptedAlokasi;
        }
    }, [summaryCardType, decryptedAlokasi]);

    const summaryModalTitle = useMemo(() => {
        switch (summaryCardType) {
            case 'draft':
                return 'Daftar Alokasi Draft';
            case 'dikirim':
                return 'Daftar Alokasi Dikirim';
            case 'revisi':
                return 'Daftar Alokasi Revisi';
            case 'all':
            default:
                return 'Daftar Seluruh Alokasi';
        }
    }, [summaryCardType]);

    const handleReset = () => {
        setSearch('');
        setStatus('all');
        setBulan('all');
        setCurrentPage(1);
    };

    const handleKirim = (
        kegiatanHashedId: string,
        bulan: string,
        tahun: number,
        namaKegiatan: string,
    ) => {
        setSelectedPeriode({
            kegiatanId: 0,
            bulan,
            tahun,
            namaKegiatan,
            kegiatanHashedId,
        });
        setShowKirimModal(true);
    };

    const confirmKirim = () => {
        if (selectedPeriode && selectedPeriode.kegiatanHashedId) {
            router.post(
                `/alokasi/periode/${selectedPeriode.kegiatanHashedId}/${selectedPeriode.tahun}/${selectedPeriode.bulan}/submit`,
                {},
                {
                    onSuccess: () => {
                        setShowKirimModal(false);
                        setSelectedPeriode(null);
                    },
                },
            );
        }
    };

    const handleBatalkan = (
        kegiatanHashedId: string,
        bulan: string,
        tahun: number,
        namaKegiatan: string,
    ) => {
        setSelectedPeriode({
            kegiatanId: 0,
            bulan,
            tahun,
            namaKegiatan,
            kegiatanHashedId,
        });
        setShowBatalkanModal(true);
    };

    const confirmBatalkan = () => {
        if (selectedPeriode && selectedPeriode.kegiatanHashedId) {
            router.delete(
                `/alokasi/periode/${selectedPeriode.kegiatanHashedId}/${selectedPeriode.tahun}/${selectedPeriode.bulan}`,
                {
                    onSuccess: () => {
                        setShowBatalkanModal(false);
                        setSelectedPeriode(null);
                    },
                },
            );
        }
    };

    const handleKembalikanDraft = (
        kegiatanHashedId: string,
        bulan: string,
        tahun: number,
        namaKegiatan: string,
    ) => {
        setSelectedPeriode({
            kegiatanId: 0,
            bulan,
            tahun,
            namaKegiatan,
            kegiatanHashedId,
        });
        setShowKembalikanDraftModal(true);
    };

    const confirmKembalikanDraft = () => {
        if (selectedPeriode && selectedPeriode.kegiatanHashedId) {
            router.post(
                `/alokasi/periode/${selectedPeriode.kegiatanHashedId}/${selectedPeriode.tahun}/${selectedPeriode.bulan}/kembalikan-draft`,
                {},
                {
                    onSuccess: () => {
                        setShowKembalikanDraftModal(false);
                        setSelectedPeriode(null);
                    },
                },
            );
        }
    };

    const handleBatalkanRevisi = (
        kegiatanHashedId: string,
        bulan: string,
        tahun: number,
        namaKegiatan: string,
    ) => {
        setSelectedPeriode({
            kegiatanId: 0,
            bulan,
            tahun,
            namaKegiatan,
            kegiatanHashedId,
        });
        setShowBatalkanRevisiModal(true);
    };

    const confirmBatalkanRevisi = () => {
        if (selectedPeriode && selectedPeriode.kegiatanHashedId) {
            router.post(
                `/alokasi/periode/${selectedPeriode.kegiatanHashedId}/${selectedPeriode.tahun}/${selectedPeriode.bulan}/revisi/batal`,
                {},
                {
                    onSuccess: () => {
                        setShowBatalkanRevisiModal(false);
                        setSelectedPeriode(null);
                    },
                },
            );
        }
    };

    const handleRevisi = (
        kegiatanHashedId: string,
        bulan: string,
        tahun: number,
        namaKegiatan: string,
    ) => {
        setSelectedPeriode({
            kegiatanId: 0,
            bulan,
            tahun,
            namaKegiatan,
            kegiatanHashedId,
        });
        setShowRevisiModal(true);
    };

    const confirmRevisi = () => {
        if (selectedPeriode && selectedPeriode.kegiatanHashedId) {
            router.post(
                `/alokasi/periode/${selectedPeriode.kegiatanHashedId}/${selectedPeriode.tahun}/${selectedPeriode.bulan}/revisi`,
                {},
                {
                    onSuccess: () => {
                        setShowRevisiModal(false);
                        setSelectedPeriode(null);
                    },
                },
            );
        }
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getBulanLabel = (bulan: string | number) => {
        // Convert to string and ensure bulan has leading zero
        const bulanStr = String(bulan).padStart(2, '0');
        const bulanObj = bulanOptions.find((b) => b.value === bulanStr);
        return bulanObj?.label || bulanStr;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alokasi petugas" />

            <PageHeader
                title="Alokasi Petugas"
                description="Kelola alokasi petugas untuk setiap kegiatan"
            >
                {!isPJ && hasKegiatans && (
                    <Button size="sm" asChild className="gap-2">
                        <Link href="/alokasi/create">
                            <Plus className="h-4 w-4" />
                            Tambah Periode Kegiatan
                        </Link>
                    </Button>
                )}
            </PageHeader>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <button
                    type="button"
                    className="cursor-pointer text-left"
                    onClick={() => handleOpenSummaryModal('all')}
                >
                    <ContentCard className="border border-blue-200/60 bg-gradient-to-br from-blue-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-blue-900/40 dark:from-blue-950/30 dark:to-neutral-900">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm text-blue-700 dark:text-blue-300">
                                    Total Alokasi Kegiatan
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {alokasiSummary.totalPeriode}
                                </p>
                            </div>
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                <Users className="h-5 w-5" />
                            </span>
                        </div>
                    </ContentCard>
                </button>

                <button
                    type="button"
                    className="cursor-pointer text-left"
                    onClick={() => handleOpenSummaryModal('draft')}
                >
                    <ContentCard className="border border-amber-200/60 bg-gradient-to-br from-amber-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-amber-900/40 dark:from-amber-950/30 dark:to-neutral-900">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm text-amber-700 dark:text-amber-300">
                                    Draft Kegiatan
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {alokasiSummary.totalDraft}
                                </p>
                            </div>
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                <Edit2 className="h-5 w-5" />
                            </span>
                        </div>
                    </ContentCard>
                </button>

                <button
                    type="button"
                    className="cursor-pointer text-left"
                    onClick={() => handleOpenSummaryModal('dikirim')}
                >
                    <ContentCard className="border border-emerald-200/60 bg-gradient-to-br from-emerald-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-neutral-900">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm text-emerald-700 dark:text-emerald-300">
                                    Kegiatan Dikirim
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {alokasiSummary.totalDikirim}
                                </p>
                            </div>
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                <Send className="h-5 w-5" />
                            </span>
                        </div>
                    </ContentCard>
                </button>

                <button
                    type="button"
                    className="cursor-pointer text-left"
                    onClick={() => handleOpenSummaryModal('revisi')}
                >
                    <ContentCard className="border border-violet-200/60 bg-gradient-to-br from-violet-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-violet-900/40 dark:from-violet-950/30 dark:to-neutral-900">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm text-violet-700 dark:text-violet-300">
                                    Kegiatan Direvisi
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {alokasiSummary.totalPerubahan}
                                </p>
                            </div>
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300">
                                <RotateCcw className="h-5 w-5" />
                            </span>
                        </div>
                    </ContentCard>
                </button>
            </div>

            {/* Filters */}
            <ContentCard>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div className="space-y-2">
                        <Label
                            htmlFor="search"
                            className="text-base font-semibold"
                        >
                            Cari Kegiatan
                        </Label>
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-neutral-500" />
                            <Input
                                id="search"
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Nama atau deskripsi kegiatan..."
                                className="pl-10"
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label
                            htmlFor="bulan"
                            className="text-base font-semibold"
                        >
                            Bulan
                        </Label>
                        <Select
                            value={bulan}
                            onValueChange={(value) => setBulan(value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Semua Bulan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Bulan</SelectItem>
                                {bulanOptions.map((b) => (
                                    <SelectItem key={b.value} value={b.value}>
                                        {b.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label
                            htmlFor="status"
                            className="text-base font-semibold"
                        >
                            Status
                        </Label>
                        <Select
                            value={status}
                            onValueChange={(value) => setStatus(value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua Status
                                </SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="dikirim">
                                    Terkirim
                                </SelectItem>
                                <SelectItem value="perubahan">
                                    Perubahan
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-end">
                        <Button
                            onClick={handleReset}
                            variant="outline"
                            className="w-full gap-2"
                        >
                            <RotateCcw className="h-5 w-5" />
                            Reset Filter
                        </Button>
                    </div>
                </div>
            </ContentCard>

            {/* Table */}
            <ContentCard padding="none" className="overflow-x-auto">
                <div className="rounded-2xl">
                    <table className="w-full min-w-max">
                        <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                            <tr>
                                <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                    Kegiatan
                                </th>
                                <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                    Bulan
                                </th>
                                <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                    Estimasi Honor
                                </th>
                                <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                    Sisa Pagu
                                </th>
                                <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                    Jumlah Petugas
                                </th>
                                <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                    Status
                                </th>
                                <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                            {paginatedAlokasi.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-6 py-12 text-center text-neutral-500 dark:text-neutral-400"
                                    >
                                        {filteredAlokasi.length === 0 &&
                                        decryptedAlokasi.length > 0
                                            ? 'Tidak ada data yang sesuai dengan filter'
                                            : 'Tidak ada data alokasi'}
                                    </td>
                                </tr>
                            ) : (
                                paginatedAlokasi.map((periode, index) => (
                                    <tr
                                        key={`${periode.kegiatan_id}-${periode.bulan}-${periode.tahun}-${index}`}
                                        className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                    >
                                        <td className="px-3 py-3">
                                            <div className="max-w-xs">
                                                <div className="font-medium break-words text-neutral-900 dark:text-white">
                                                    {
                                                        periode.kegiatan
                                                            .nama_kegiatan
                                                    }
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-900 dark:text-white">
                                            {getBulanLabel(periode.bulan)}{' '}
                                            {periode.tahun}
                                        </td>
                                        <td className="px-3 py-3 text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-white">
                                            {formatCurrency(
                                                periode.estimasi_honor,
                                            )}
                                        </td>
                                        <td className="px-3 py-3 text-sm whitespace-nowrap">
                                            <span
                                                className={`font-semibold ${
                                                    periode.sisa_pagu >= 0
                                                        ? 'text-green-600 dark:text-green-400'
                                                        : 'text-red-600 dark:text-red-400'
                                                }`}
                                            >
                                                {formatCurrency(
                                                    periode.sisa_pagu,
                                                )}
                                            </span>
                                        </td>
                                        <td className="px-3 py-3 whitespace-nowrap">
                                            <span className="inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-gradient-to-br from-blue-500/20 via-blue-400/10 to-blue-300/10 px-4 py-2 text-base font-semibold text-blue-900 shadow-lg backdrop-blur-md dark:text-blue-200">
                                                <Users
                                                    className="h-5 w-5 shrink-0"
                                                    strokeWidth={2.5}
                                                />
                                                {periode.jumlah_petugas} petugas
                                            </span>
                                        </td>
                                        <td className="px-3 py-3 whitespace-nowrap">
                                            <StatusBadge
                                                status={periode.status}
                                            />
                                        </td>
                                        <td className="px-3 py-3 whitespace-nowrap">
                                            <div className="flex items-center justify-center gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                    className="shrink-0 gap-1.5"
                                                    title="Lihat Detail"
                                                >
                                                    <Link
                                                        href={`/alokasi/periode/${periode.kegiatan.hashed_id}/${periode.tahun}/${periode.bulan}`}
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                        Detail
                                                    </Link>
                                                </Button>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        asChild
                                                    >
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            className="h-8 w-8 cursor-pointer p-0"
                                                        >
                                                            <MoreVertical className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent
                                                        align="end"
                                                        className="w-48"
                                                    >
                                                        {!isPJ &&
                                                            periode.status ===
                                                                'draft' && (
                                                                <>
                                                                    <DropdownMenuItem
                                                                        onClick={() =>
                                                                            handleKirim(
                                                                                periode
                                                                                    .kegiatan
                                                                                    .hashed_id,
                                                                                periode.bulan,
                                                                                periode.tahun,
                                                                                periode
                                                                                    .kegiatan
                                                                                    .nama_kegiatan,
                                                                            )
                                                                        }
                                                                        className="cursor-pointer gap-2"
                                                                    >
                                                                        <Send className="h-4 w-4" />
                                                                        Kirim
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem className="cursor-pointer gap-2">
                                                                        <Link
                                                                            href={`/alokasi/periode/${periode.kegiatan.hashed_id}/${periode.tahun}/${periode.bulan}/edit`}
                                                                            className="flex w-full items-center gap-2"
                                                                        >
                                                                            <Edit2 className="h-4 w-4" />
                                                                            Edit
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem className="cursor-pointer gap-2">
                                                                        <Link
                                                                            href={`/alokasi/create?kegiatan_id=${periode.kegiatan.hashed_id}&copy_from_bulan=${periode.bulan}&copy_from_tahun=${periode.tahun}`}
                                                                            className="flex w-full items-center gap-2"
                                                                        >
                                                                            <Copy className="h-4 w-4" />
                                                                            Salin
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    {isAdminOrOperator &&
                                                                        !periode.has_spk_generated && (
                                                                            <DropdownMenuItem
                                                                                onClick={() =>
                                                                                    handleBatalkan(
                                                                                        periode
                                                                                            .kegiatan
                                                                                            .hashed_id,
                                                                                        periode.bulan,
                                                                                        periode.tahun,
                                                                                        periode
                                                                                            .kegiatan
                                                                                            .nama_kegiatan,
                                                                                    )
                                                                                }
                                                                                className="cursor-pointer gap-2 text-red-600 dark:text-red-400"
                                                                            >
                                                                                <X className="h-4 w-4" />
                                                                                Batalkan
                                                                                Alokasi
                                                                            </DropdownMenuItem>
                                                                        )}
                                                                </>
                                                            )}
                                                        {!isPJ &&
                                                            (periode.status ===
                                                                'dikirim' ||
                                                                periode.status ===
                                                                    'perubahan') && (
                                                                <>
                                                                    <DropdownMenuItem className="cursor-pointer gap-2">
                                                                        <Link
                                                                            href={`/alokasi/create?kegiatan_id=${periode.kegiatan.hashed_id}&copy_from_bulan=${periode.bulan}&copy_from_tahun=${periode.tahun}`}
                                                                            className="flex w-full items-center gap-2"
                                                                        >
                                                                            <Copy className="h-4 w-4" />
                                                                            Salin
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    {canRevisiPeriode(
                                                                        periode.bulan,
                                                                        periode.tahun,
                                                                    ) &&
                                                                        !periode.has_completed_revision_cycle && (
                                                                            <DropdownMenuItem
                                                                                onClick={() =>
                                                                                    handleRevisi(
                                                                                        periode
                                                                                            .kegiatan
                                                                                            .hashed_id,
                                                                                        periode.bulan,
                                                                                        periode.tahun,
                                                                                        periode
                                                                                            .kegiatan
                                                                                            .nama_kegiatan,
                                                                                    )
                                                                                }
                                                                                className="cursor-pointer gap-2 text-purple-600 dark:text-purple-400"
                                                                            >
                                                                                <RefreshCw className="h-4 w-4" />
                                                                                Revisi
                                                                            </DropdownMenuItem>
                                                                        )}
                                                                    {isAdminOrOperator &&
                                                                        periode.status ===
                                                                            'dikirim' &&
                                                                        !periode.has_non_organik_spk_in_kegiatan && (
                                                                            <DropdownMenuItem
                                                                                onClick={() =>
                                                                                    handleKembalikanDraft(
                                                                                        periode
                                                                                            .kegiatan
                                                                                            .hashed_id,
                                                                                        periode.bulan,
                                                                                        periode.tahun,
                                                                                        periode
                                                                                            .kegiatan
                                                                                            .nama_kegiatan,
                                                                                    )
                                                                                }
                                                                                className="cursor-pointer gap-2 text-amber-600 dark:text-amber-400"
                                                                            >
                                                                                <RotateCcw className="h-4 w-4" />
                                                                                Kembalikan
                                                                                ke
                                                                                Draft
                                                                            </DropdownMenuItem>
                                                                        )}
                                                                </>
                                                            )}
                                                        {isAdmin &&
                                                            periode.status ===
                                                                'perubahan' && (
                                                                <DropdownMenuItem
                                                                    onClick={() =>
                                                                        handleBatalkanRevisi(
                                                                            periode
                                                                                .kegiatan
                                                                                .hashed_id,
                                                                            periode.bulan,
                                                                            periode.tahun,
                                                                            periode
                                                                                .kegiatan
                                                                                .nama_kegiatan,
                                                                        )
                                                                    }
                                                                    className="cursor-pointer gap-2 text-red-600 dark:text-red-400"
                                                                >
                                                                    <X className="h-4 w-4" />
                                                                    Batalkan
                                                                    Revisi
                                                                </DropdownMenuItem>
                                                            )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {totalPages > 1 && (
                    <div className="flex items-center justify-between border-t border-neutral-200 px-6 py-4 dark:border-neutral-800">
                        <div className="text-sm text-neutral-600 dark:text-neutral-400">
                            Menampilkan {(currentPage - 1) * perPage + 1}-
                            {Math.min(
                                currentPage * perPage,
                                filteredAlokasi.length,
                            )}{' '}
                            dari {filteredAlokasi.length} data
                            {(search || status !== 'all' || bulan !== 'all') &&
                                ` (difilter dari ${decryptedAlokasi.length} total)`}
                        </div>
                        <div className="flex gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setCurrentPage((prev) =>
                                        Math.max(1, prev - 1),
                                    )
                                }
                                disabled={currentPage === 1}
                                className="h-9 gap-1"
                            >
                                <ChevronLeft className="h-4 w-4" />
                                Previous
                            </Button>

                            {Array.from(
                                { length: totalPages },
                                (_, i) => i + 1,
                            ).map((page) => (
                                <Button
                                    key={page}
                                    variant={
                                        currentPage === page
                                            ? 'default'
                                            : 'outline'
                                    }
                                    size="sm"
                                    onClick={() => setCurrentPage(page)}
                                    className="h-9 w-9"
                                >
                                    {page}
                                </Button>
                            ))}

                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    setCurrentPage((prev) =>
                                        Math.min(totalPages, prev + 1),
                                    )
                                }
                                disabled={currentPage === totalPages}
                                className="h-9 gap-1"
                            >
                                Next
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                )}
            </ContentCard>

            {/* Modal Kirim */}
            <Dialog open={showKirimModal} onOpenChange={setShowKirimModal}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-xl">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-neutral-700/50">
                                <Send className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <span>Kirim Alokasi Periode</span>
                        </DialogTitle>
                        <DialogDescription className="pt-2 text-base">
                            Alokasi yang dikirim akan digunakan sebagai dasar
                            pembuatan <strong>SK KPA</strong> dan{' '}
                            <strong>Perjanjian Kerja</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedPeriode && (
                        <div className="space-y-4">
                            <div className="space-y-3 border-y border-white/20 py-4 dark:border-neutral-700/30">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-white/50 backdrop-blur-sm dark:bg-neutral-800/60">
                                        <span className="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                            📋
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Kegiatan
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {selectedPeriode.namaKegiatan}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-white/50 backdrop-blur-sm dark:bg-neutral-800/60">
                                        <span className="text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                            📅
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Periode
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {getBulanLabel(
                                                selectedPeriode.bulan,
                                            )}{' '}
                                            {selectedPeriode.tahun}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-blue-400/30 bg-gradient-to-br from-blue-500/20 via-blue-400/10 to-blue-300/10 p-3 shadow-lg backdrop-blur-xl">
                                <div className="flex gap-2">
                                    <AlertCircle className="h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                                    <div className="space-y-1 text-sm text-blue-800 dark:text-blue-200">
                                        <p className="font-medium">
                                            Pastikan data sudah benar:
                                        </p>
                                        <ul className="ml-4 list-disc space-y-1">
                                            <li>
                                                Data akan digunakan untuk SK KPA
                                            </li>
                                            <li>
                                                Data akan digunakan untuk
                                                Perjanjian Kerja
                                            </li>
                                            <li>
                                                Dapat direvisi jika diperlukan
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowKirimModal(false)}
                            className="w-full sm:w-auto"
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={confirmKirim}
                            className="w-full sm:w-auto"
                        >
                            <Send className="mr-2 h-4 w-4" />
                            Ya, Kirim Sekarang
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Batalkan */}
            <Dialog
                open={showBatalkanModal}
                onOpenChange={setShowBatalkanModal}
            >
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-xl">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <AlertCircle className="h-5 w-5 text-red-600 dark:text-red-400" />
                            </div>
                            <span className="text-red-600 dark:text-red-400">
                                Batalkan Alokasi Periode
                            </span>
                        </DialogTitle>
                        <DialogDescription className="pt-2 text-base">
                            <strong className="text-red-600 dark:text-red-400">
                                Perhatian:
                            </strong>{' '}
                            Data alokasi akan dihapus secara permanen dan tidak
                            dapat dikembalikan.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedPeriode && (
                        <div className="space-y-4 border-y border-white/20 py-4 dark:border-neutral-700/30">
                            <div className="space-y-3">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-red-500/30 via-red-400/20 to-red-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-red-600 dark:text-red-400">
                                            📋
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Kegiatan
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {selectedPeriode.namaKegiatan}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-red-500/30 via-red-400/20 to-red-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-red-600 dark:text-red-400">
                                            📅
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Periode
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {getBulanLabel(
                                                selectedPeriode.bulan,
                                            )}{' '}
                                            {selectedPeriode.tahun}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-red-400/30 bg-gradient-to-br from-red-500/20 via-red-400/10 to-red-300/10 p-3 shadow-lg backdrop-blur-xl">
                                <div className="flex gap-2">
                                    <AlertCircle className="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                                    <p className="text-sm text-red-800 dark:text-red-200">
                                        Semua data petugas pada periode ini akan
                                        dihapus. Tindakan ini tidak dapat
                                        dibatalkan.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowBatalkanModal(false)}
                            className="w-full sm:w-auto"
                        >
                            Tidak, Kembali
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmBatalkan}
                            className="w-full sm:w-auto"
                        >
                            <X className="mr-2 h-4 w-4" />
                            Ya, Batalkan Alokasi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Kembalikan ke Draft */}
            <Dialog
                open={showKembalikanDraftModal}
                onOpenChange={setShowKembalikanDraftModal}
            >
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-xl">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                                <RotateCcw className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <span className="text-amber-600 dark:text-amber-400">
                                Kembalikan ke Draft
                            </span>
                        </DialogTitle>
                        <DialogDescription className="pt-2 text-base">
                            Status alokasi akan dikembalikan ke{' '}
                            <strong>draft</strong>. Data petugas tidak akan
                            dihapus.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedPeriode && (
                        <div className="space-y-4 border-y border-white/20 py-4 dark:border-neutral-700/30">
                            <div className="space-y-3">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-amber-500/30 via-amber-400/20 to-amber-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-amber-600 dark:text-amber-400">
                                            📋
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Kegiatan
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {selectedPeriode.namaKegiatan}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-amber-500/30 via-amber-400/20 to-amber-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-amber-600 dark:text-amber-400">
                                            📅
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Periode
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {getBulanLabel(
                                                selectedPeriode.bulan,
                                            )}{' '}
                                            {selectedPeriode.tahun}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-amber-400/30 bg-gradient-to-br from-amber-500/20 via-amber-400/10 to-amber-300/10 p-3 shadow-lg backdrop-blur-xl">
                                <div className="flex gap-2">
                                    <AlertCircle className="h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                                    <p className="text-sm text-amber-800 dark:text-amber-200">
                                        Periode akan kembali ke status draft dan
                                        dapat diedit kembali. Tindakan ini hanya
                                        diizinkan jika setidaknya satu
                                        Perjanjian Kerja belum dicetak.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowKembalikanDraftModal(false)}
                            className="w-full sm:w-auto"
                        >
                            Tidak, Kembali
                        </Button>
                        <Button
                            onClick={confirmKembalikanDraft}
                            className="w-full gap-2 bg-amber-600 text-white hover:bg-amber-700 sm:w-auto dark:bg-amber-600 dark:hover:bg-amber-700"
                        >
                            <RotateCcw className="h-4 w-4" />
                            Ya, Kembalikan ke Draft
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Revisi */}
            <Dialog open={showRevisiModal} onOpenChange={setShowRevisiModal}>
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-xl">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                                <RefreshCw className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <span>Revisi Alokasi Periode</span>
                        </DialogTitle>
                        <DialogDescription className="pt-2 text-base">
                            Revisi akan membuat draft baru yang dapat diedit.
                            Revisi ini akan menghasilkan{' '}
                            <strong>SK Perubahan</strong> dan{' '}
                            <strong>Addendum Perjanjian Kerja</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    {selectedPeriode && (
                        <div className="space-y-4">
                            <div className="space-y-3 border-y border-white/20 py-4 dark:border-neutral-700/30">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-orange-500/30 via-orange-400/20 to-orange-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-orange-600 dark:text-orange-400">
                                            📋
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Kegiatan
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {selectedPeriode.namaKegiatan}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-orange-500/30 via-orange-400/20 to-orange-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-orange-600 dark:text-orange-400">
                                            📅
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Periode
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {getBulanLabel(
                                                selectedPeriode.bulan,
                                            )}{' '}
                                            {selectedPeriode.tahun}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-orange-400/30 bg-gradient-to-br from-orange-500/20 via-orange-400/10 to-orange-300/10 p-3 shadow-lg backdrop-blur-xl">
                                <div className="flex gap-2">
                                    <AlertCircle className="h-5 w-5 flex-shrink-0 text-orange-600 dark:text-orange-400" />
                                    <div className="space-y-1 text-sm text-orange-800 dark:text-orange-200">
                                        <p className="font-medium">
                                            Proses revisi:
                                        </p>
                                        <ul className="ml-4 list-disc space-y-1">
                                            <li>
                                                Data yang terkirim akan
                                                diarsipkan
                                            </li>
                                            <li>
                                                Salinan data akan dibuat sebagai
                                                draft baru
                                            </li>
                                            <li>
                                                Setelah dikirim ulang akan
                                                dibuat SK Perubahan
                                            </li>
                                            <li>
                                                Perjanjian Kerja akan
                                                ditambahkan Addendum
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowRevisiModal(false)}
                            className="w-full sm:w-auto"
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={confirmRevisi}
                            className="w-full sm:w-auto"
                        >
                            <RefreshCw className="mr-2 h-4 w-4" />
                            Ya, Lanjutkan Revisi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Modal Batalkan Revisi (Admin) */}
            <Dialog
                open={showBatalkanRevisiModal}
                onOpenChange={setShowBatalkanRevisiModal}
            >
                <DialogContent className="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-xl">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <AlertCircle className="h-5 w-5 text-red-600 dark:text-red-400" />
                            </div>
                            <span className="text-red-600 dark:text-red-400">
                                Batalkan Revisi Terkirim
                            </span>
                        </DialogTitle>
                        <DialogDescription className="pt-2 text-base">
                            Tindakan ini akan menghapus data alokasi status
                            <strong> perubahan</strong> untuk periode ini, lalu
                            mengembalikan periode sebelumnya menjadi
                            <strong> dikirim</strong>.
                        </DialogDescription>
                    </DialogHeader>

                    {selectedPeriode && (
                        <div className="space-y-4 border-y border-white/20 py-4 dark:border-neutral-700/30">
                            <div className="space-y-3">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-red-500/30 via-red-400/20 to-red-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-red-600 dark:text-red-400">
                                            📋
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Kegiatan
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {selectedPeriode.namaKegiatan}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded bg-gradient-to-br from-red-500/30 via-red-400/20 to-red-300/10 backdrop-blur-sm">
                                        <span className="text-sm font-semibold text-red-600 dark:text-red-400">
                                            📅
                                        </span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-xs font-medium tracking-wide text-neutral-600 uppercase dark:text-neutral-400">
                                            Periode
                                        </p>
                                        <p className="mt-1 font-medium text-neutral-900 dark:text-white">
                                            {getBulanLabel(
                                                selectedPeriode.bulan,
                                            )}{' '}
                                            {selectedPeriode.tahun}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-red-400/30 bg-gradient-to-br from-red-500/20 via-red-400/10 to-red-300/10 p-3 shadow-lg backdrop-blur-xl">
                                <div className="flex gap-2">
                                    <AlertCircle className="h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                                    <p className="text-sm text-red-800 dark:text-red-200">
                                        Jika addendum Perjanjian Kerja sudah
                                        dibuat, pembatalan revisi akan ditolak
                                        otomatis.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => setShowBatalkanRevisiModal(false)}
                            className="w-full sm:w-auto"
                        >
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={confirmBatalkanRevisi}
                            className="w-full sm:w-auto"
                        >
                            <X className="mr-2 h-4 w-4" />
                            Ya, Batalkan Revisi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={showSummaryModal} onOpenChange={setShowSummaryModal}>
                <DialogContent className="sm:max-w-7xl">
                    <DialogHeader>
                        <DialogTitle>{summaryModalTitle}</DialogTitle>
                        <DialogDescription>
                            Pilih periode kegiatan untuk melihat detail alokasi.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="max-h-[60vh] space-y-2 overflow-y-auto pr-1">
                        {summaryModalItems.length === 0 ? (
                            <div className="rounded-md border border-dashed border-neutral-300 px-4 py-8 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                                Tidak ada data pada kategori ini.
                            </div>
                        ) : (
                            summaryModalItems.map((item, index) => (
                                <div
                                    key={`${item.kegiatan_id}-${item.bulan}-${item.tahun}-${index}`}
                                    className="flex items-center justify-between gap-3 rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-800"
                                >
                                    <div>
                                        <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            {item.kegiatan.nama_kegiatan}
                                        </p>
                                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                            {item.kegiatan.kode_kegiatan} ·{' '}
                                            {getBulanLabel(item.bulan)}{' '}
                                            {item.tahun}
                                        </p>
                                    </div>

                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={`/alokasi/periode/${item.kegiatan.hashed_id}/${item.tahun}/${item.bulan}`}
                                        >
                                            <Eye className="h-3.5 w-3.5" />
                                            Detail
                                        </Link>
                                    </Button>
                                </div>
                            ))
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
