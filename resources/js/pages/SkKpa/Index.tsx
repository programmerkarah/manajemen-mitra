import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
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
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronUp,
    Download,
    Eye,
    Plus,
    RefreshCw,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

interface LatestSk {
    id: number;
    hashed_id: string;
    nomor_sk: string;
    tanggal_sk: string;
    tahun: number;
    status: 'draft' | 'diterbitkan' | 'dibatalkan';
    file_path: string | null;
    signed_file_path: string | null;
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

export default function Index({ kegiatan, summary }: IndexProps) {
    const { auth } = usePage<SharedData>().props;
    const allKegiatan = useDecryptedData<KegiatanItem>(kegiatan.encrypted);

    const [search, setSearch] = useState('');
    const [jenisKegiatan, setJenisKegiatan] = useState('all');
    const [sortField, setSortField] = useState<
        'nama_kegiatan' | 'jenis_kegiatan' | 'tahun_anggaran' | 'sk_count'
    >('nama_kegiatan');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
    const [isRefreshing, setIsRefreshing] = useState(false);
    const prevFiltersRef = useRef({ search, jenisKegiatan });

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

        return result;
    }, [allKegiatan, search, jenisKegiatan, sortField, sortDirection]);

    // Reset filter reference when filters change
    useEffect(() => {
        const prevFilters = prevFiltersRef.current;
        if (
            prevFilters.search !== search ||
            prevFilters.jenisKegiatan !== jenisKegiatan
        ) {
            prevFiltersRef.current = { search, jenisKegiatan };
        }
    }, [search, jenisKegiatan]);

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
    };

    const handleDownload = (keg: KegiatanItem) => {
        const latestSk = keg.latest_sk;
        if (!latestSk) {
            alert('SK tidak tersedia');
            return;
        }

        // Prioritaskan signed file jika ada
        const filePath = latestSk.signed_file_path || latestSk.file_path;
        if (!filePath) {
            alert('File SK tidak tersedia');
            return;
        }

        window.open(`/${filePath}`, '_blank');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="SK KPA" />

            <div className="space-y-6">
                <PageHeader
                    title="SK KPA"
                    description="Kelola Surat Keputusan Kuasa Pengguna Anggaran untuk setiap kegiatan"
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <ContentCard>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Kegiatan Aktif
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {summary.total_kegiatan_aktif}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            SK Belum Dibuat
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {summary.total_sk_belum_dibuat}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            SK di Generate
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {summary.total_sk_digenerate}
                        </p>
                    </ContentCard>
                    <ContentCard>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            SK Disahkan
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {summary.total_sk_disahkan}
                        </p>
                    </ContentCard>
                </div>

                {/* Filter & Search */}
                <ContentCard>
                    <div className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <label
                                    htmlFor="search"
                                    className="mb-2 block text-sm font-medium text-neutral-700 dark:text-neutral-300"
                                >
                                    Cari Kegiatan
                                </label>
                                <Input
                                    id="search"
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Nama atau kode kegiatan..."
                                    className="w-full"
                                />
                            </div>

                            <div>
                                <label
                                    htmlFor="jenis_kegiatan"
                                    className="mb-2 block text-sm font-medium text-neutral-700 dark:text-neutral-300"
                                >
                                    Jenis Kegiatan
                                </label>
                                <Select
                                    value={jenisKegiatan}
                                    onValueChange={(value) =>
                                        setJenisKegiatan(value)
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Semua Jenis" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua
                                        </SelectItem>
                                        <SelectItem value="sensus">
                                            Sensus
                                        </SelectItem>
                                        <SelectItem value="survei">
                                            Survei
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-end">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleReset}
                                    className="w-full"
                                >
                                    Reset Filter
                                </Button>
                            </div>
                        </div>
                    </div>
                </ContentCard>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="flex items-center justify-between px-6 pt-4 pb-2">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Menampilkan{' '}
                            {filteredAndSortedKegiatan.length > 0 ? 1 : 0}-
                            {filteredAndSortedKegiatan.length} dari{' '}
                            {filteredAndSortedKegiatan.length} data
                            {filteredAndSortedKegiatan.length !==
                                allKegiatan.length &&
                                ` (difilter dari ${allKegiatan.length} total)`}
                        </p>
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
                            Refresh
                        </Button>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
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
                                        className="cursor-pointer px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
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
                                        className="cursor-pointer px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 hover:bg-neutral-100 dark:text-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() =>
                                            handleSort('tahun_anggaran')
                                        }
                                    >
                                        <div className="flex items-center justify-center gap-1">
                                            Tahun
                                            {renderSortIcon('tahun_anggaran')}
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Ketua Tim
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Status SK
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        File
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {filteredAndSortedKegiatan.length === 0 ? (
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
                                    filteredAndSortedKegiatan.map((keg) => (
                                        <tr
                                            key={keg.id}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-3 py-3">
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
                                            <td className="px-3 py-3 text-center text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                <span className="capitalize">
                                                    {keg.jenis_kegiatan}
                                                </span>
                                            </td>
                                            <td className="px-3 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-white">
                                                {keg.tahun_anggaran}
                                            </td>
                                            <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {keg.ketua_tim}
                                            </td>
                                            <td className="px-3 py-3 text-center">
                                                <StatusBadge
                                                    status={keg.sk_status_type}
                                                />
                                            </td>
                                            <td className="px-3 py-3 text-center">
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
                                            <td className="px-3 py-3">
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
                                                                keg.has_personnel_changes && (
                                                                    <Button
                                                                        size="sm"
                                                                        variant="outline"
                                                                        asChild
                                                                        title="SK Perubahan"
                                                                    >
                                                                        <Link
                                                                            href={`/sk-kpa/kegiatan/${keg.hashed_id}/create`}
                                                                        >
                                                                            <Plus className="h-3.5 w-3.5" />
                                                                        </Link>
                                                                    </Button>
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
                </ContentCard>
            </div>
        </AppLayout>
    );
}
