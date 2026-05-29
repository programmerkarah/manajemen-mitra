import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { KECAMATAN_LIST, getDesaByKecamatan } from '@/lib/wilayah-data';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    CreditCard,
    Download,
    Eye,
    FileUp,
    GraduationCap,
    Mail,
    Pencil,
    PencilLine,
    Phone,
    Plus,
    RefreshCw,
    Search,
    User as UserIcon,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Petugas', href: '/petugas' }];

// SortIcon component declared outside to avoid recreation on each render
const SortIcon = ({
    field,
    sortField,
    sortDirection,
}: {
    field: string;
    sortField: string;
    sortDirection: 'asc' | 'desc';
}) => {
    if (sortField !== field) return null;
    return sortDirection === 'asc' ? (
        <ChevronUp className="h-4 w-4" />
    ) : (
        <ChevronDown className="h-4 w-4" />
    );
};

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik_masked: string;
    email: string;
    telepon: string;
    pendidikan: string;
    tahun_bergabung: number;
    status: string;
    jenis_petugas: string;
    jenis_kelamin: string | null;
    tanggal_lahir: string | null;
    kecamatan: string | null;
    desa_kelurahan: string | null;
    alamat: string | null;
}

interface BatchEditItem {
    id: string;
    nama: string;
    telepon: string;
    pendidikan: string;
    jenis_kelamin: string;
    tanggal_lahir: string;
    kecamatan: string;
    desa_kelurahan: string;
    alamat: string;
}

interface ImportPreviewRow {
    row_number: number;
    action: 'create' | 'update' | 'none';
    nama: string;
    nik: string;
    email: string;
    status: string;
    jenis_petugas: string;
    changes: string[];
    columns: Record<string, string | null>;
    changed_fields: string[];
    warnings: string[];
    valid_for_import: boolean;
}

interface ImportPreviewSummary {
    total_rows: number;
    success_count: number;
    created_count: number;
    updated_count: number;
    skipped_count: number;
}

interface PetugasIndexProps {
    petugas: {
        encrypted: string;
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        encrypted?: string;
        decrypted?: {
            search?: string;
            status?: string;
            jenis_petugas?: string;
            tahun?: string;
        };
    };
}

const PREVIEW_INLINE_ERROR_LIMIT = 6;
const NO_CHANGE_MESSAGE =
    'Tidak ada perubahan data, baris ini akan dilewati saat import.';

const PREVIEW_COLUMNS: Array<{ key: string; label: string }> = [
    { key: 'nama', label: 'Nama' },
    { key: 'nik', label: 'NIK' },
    { key: 'email', label: 'Email' },
    { key: 'telepon', label: 'Telepon' },
    { key: 'alamat', label: 'Alamat' },
    { key: 'pendidikan', label: 'Pendidikan' },
    { key: 'tahun_bergabung', label: 'Thn Gabung' },
    { key: 'status', label: 'Status' },
    { key: 'jenis_petugas', label: 'Jenis' },
    { key: 'tanggal_lahir', label: 'Tgl Lahir' },
    { key: 'kecamatan', label: 'Kecamatan' },
    { key: 'desa_kelurahan', label: 'Desa/Kel' },
];

export default function Index({ petugas }: PetugasIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const isPJ =
        auth.activeRole?.name === 'pj' || auth.activeRole?.name === 'ketua_tim';

    const allPetugas = useDecryptedData<Petugas>(petugas.encrypted);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [jenisPetugas, setJenisPetugas] = useState('all');
    const [sortField, setSortField] = useState<'nama' | 'email'>('nama');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(15);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [isPreviewingImport, setIsPreviewingImport] = useState(false);
    const [importPreviewRows, setImportPreviewRows] = useState<
        ImportPreviewRow[]
    >([]);
    const [importPreviewErrors, setImportPreviewErrors] = useState<string[]>(
        [],
    );
    const [importPreviewSummary, setImportPreviewSummary] =
        useState<ImportPreviewSummary | null>(null);
    const [hasImportPreview, setHasImportPreview] = useState(false);
    const [showImportErrorDetail, setShowImportErrorDetail] = useState(false);
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const [showBatchEdit, setShowBatchEdit] = useState(false);
    const [batchEditItems, setBatchEditItems] = useState<BatchEditItem[]>([]);
    const [batchProcessing, setBatchProcessing] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    // Client-side filtering and sorting
    const filteredAndSortedPetugas = useMemo(() => {
        let result: Petugas[] = [...allPetugas];

        // Filter by search
        if (search) {
            const query = search.toLowerCase();
            result = result.filter(
                (item: Petugas) =>
                    item.nama?.toLowerCase().includes(query) ||
                    item.nik_masked?.toLowerCase().includes(query) ||
                    item.email?.toLowerCase().includes(query),
            );
        }

        // Filter by status
        if (status && status !== 'all') {
            result = result.filter((item: Petugas) => item.status === status);
        }

        // Filter by jenis_petugas
        if (jenisPetugas && jenisPetugas !== 'all') {
            result = result.filter((item: Petugas) => {
                // Map display values to database values
                const jenisValue =
                    jenisPetugas === 'organik' ? 'organik' : 'non-organik';
                return item.jenis_petugas === jenisValue;
            });
        }

        // Sort
        result.sort((a: Petugas, b: Petugas) => {
            let aVal = '',
                bVal = '';
            switch (sortField) {
                case 'email':
                    aVal = a.email?.toLowerCase() || '';
                    bVal = b.email?.toLowerCase() || '';
                    break;
                case 'nama':
                default:
                    aVal = a.nama?.toLowerCase() || '';
                    bVal = b.nama?.toLowerCase() || '';
                    break;
            }
            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    }, [allPetugas, search, status, jenisPetugas, sortField, sortDirection]);

    // Client-side pagination
    const totalPages = Math.ceil(filteredAndSortedPetugas.length / perPage);
    const paginatedPetugas = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        return filteredAndSortedPetugas.slice(start, end);
    }, [filteredAndSortedPetugas, currentPage, perPage]);

    const previewValidationErrors = useMemo(
        () =>
            importPreviewErrors.filter(
                (error) => !error.includes(NO_CHANGE_MESSAGE),
            ),
        [importPreviewErrors],
    );

    const previewSkippedNotices = useMemo(
        () =>
            importPreviewErrors.filter((error) =>
                error.includes(NO_CHANGE_MESSAGE),
            ),
        [importPreviewErrors],
    );

    const previewVisibleValidationErrors = useMemo(
        () => previewValidationErrors.slice(0, PREVIEW_INLINE_ERROR_LIMIT),
        [previewValidationErrors],
    );

    const previewHiddenValidationErrorCount =
        previewValidationErrors.length - previewVisibleValidationErrors.length;

    const hasImportablePreviewRows = useMemo(
        () => importPreviewRows.some((row) => row.valid_for_import),
        [importPreviewRows],
    );

    // Reset to page 1 when filters change - done via useMemo dependencies instead of setState in effect

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 500);
            },
        });
    };

    const handleSort = (field: 'nama' | 'email') => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const handleImport = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.file || !hasImportPreview || importPreviewRows.length === 0) {
            return;
        }

        post('/petugas/import', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setShowImportModal(false);
                setImportPreviewRows([]);
                setImportPreviewErrors([]);
                setImportPreviewSummary(null);
                setHasImportPreview(false);
                setShowImportErrorDetail(false);
                reset();
            },
            onError: (errors) => {
                console.error('Import errors:', errors);
            },
        });
    };

    const handlePreviewImport = (selectedFile?: File) => {
        const file = selectedFile ?? data.file;

        if (!file) {
            return;
        }

        setIsPreviewingImport(true);
        setImportPreviewRows([]);
        setImportPreviewErrors([]);
        setImportPreviewSummary(null);
        setHasImportPreview(false);

        const formData = new FormData();
        formData.append('file', file);

        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';

        fetch('/petugas/import-preview', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body: formData,
        })
            .then(async (response) => {
                const payload = await response.json();

                if (!response.ok) {
                    const message =
                        payload?.message ||
                        payload?.errors?.file?.[0] ||
                        'Gagal membaca file impor.';
                    throw new Error(message);
                }

                setImportPreviewRows(payload.rows || []);
                setImportPreviewErrors(payload.errors || []);
                setImportPreviewSummary(payload.summary || null);
                setHasImportPreview(true);
            })
            .catch((error: Error) => {
                console.error('Import preview error:', error);
                setImportPreviewErrors([error.message]);
                setHasImportPreview(false);
            })
            .finally(() => {
                setIsPreviewingImport(false);
            });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            const selectedFile = e.target.files[0];

            setData('file', selectedFile);
            setImportPreviewRows([]);
            setImportPreviewErrors([]);
            setImportPreviewSummary(null);
            setHasImportPreview(false);
            handlePreviewImport(selectedFile);
        }
    };

    const toggleSelectPetugas = (id: number) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    };

    const toggleSelectAll = () => {
        if (selectedIds.size === paginatedPetugas.length) {
            setSelectedIds(new Set());
        } else {
            setSelectedIds(new Set(paginatedPetugas.map((p) => p.id)));
        }
    };

    const openBatchEdit = () => {
        const items: BatchEditItem[] = allPetugas
            .filter((p) => selectedIds.has(p.id))
            .map((p) => ({
                id: p.hashed_id,
                nama: p.nama || '',
                telepon: p.telepon || '',
                pendidikan: p.pendidikan || '',
                jenis_kelamin: p.jenis_kelamin || '',
                tanggal_lahir: p.tanggal_lahir || '',
                kecamatan: p.kecamatan || '',
                desa_kelurahan: p.desa_kelurahan || '',
                alamat: p.alamat || '',
            }));
        setBatchEditItems(items);
        setShowBatchEdit(true);
    };

    const updateBatchItem = (
        index: number,
        field: keyof BatchEditItem,
        value: string,
    ) => {
        setBatchEditItems((prev) =>
            prev.map((item, i) =>
                i === index ? { ...item, [field]: value } : item,
            ),
        );
    };

    const handleBatchSubmit = () => {
        setBatchProcessing(true);
        router.put(
            '/petugas/batch-update',
            { petugas: batchEditItems as unknown as Record<string, string>[] },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setShowBatchEdit(false);
                    setSelectedIds(new Set());
                    setBatchEditItems([]);
                },
                onFinish: () => setBatchProcessing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Petugas" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Data Petugas"
                    description="Kelola data petugas mitra yang terlibat dalam kegiatan"
                >
                    <div className="flex w-full flex-wrap gap-2 sm:w-auto sm:justify-end">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleRefresh}
                            disabled={isRefreshing}
                            className="w-full sm:w-auto"
                        >
                            <RefreshCw
                                className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`}
                            />
                            Refresh
                        </Button>
                        {!isPJ && (
                            <>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    asChild
                                    className="gap-2"
                                >
                                    <a href="/petugas/template/download">
                                        <Download className="h-4 w-4" />
                                        Download Template
                                    </a>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    asChild
                                    className="gap-2"
                                >
                                    <a href="/petugas/existing/download">
                                        <Download className="h-4 w-4" />
                                        Download Data Existing
                                    </a>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setShowImportModal(true)}
                                    className="gap-2"
                                >
                                    <FileUp className="h-4 w-4" />
                                    Import Excel
                                </Button>
                                {selectedIds.size > 0 && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={openBatchEdit}
                                        className="gap-2"
                                    >
                                        <PencilLine className="h-4 w-4" />
                                        Batch Edit ({selectedIds.size})
                                    </Button>
                                )}
                                <Button
                                    size="sm"
                                    asChild
                                    className="w-full gap-2 sm:w-auto"
                                >
                                    <Link href="/petugas/create">
                                        <Plus className="h-4 w-4" />
                                        Tambah Petugas
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </PageHeader>

                {/* Filters */}
                <ContentCard>
                    {/* Results Counter */}
                    <div className="mb-4 text-sm text-muted-foreground">
                        Menampilkan{' '}
                        <span className="font-semibold text-foreground">
                            {(currentPage - 1) * perPage + 1}-
                            {Math.min(
                                currentPage * perPage,
                                filteredAndSortedPetugas.length,
                            )}
                        </span>{' '}
                        dari{' '}
                        <span className="font-semibold text-foreground">
                            {filteredAndSortedPetugas.length}
                        </span>{' '}
                        petugas{' '}
                        {search || status !== 'all' || jenisPetugas !== 'all'
                            ? `(difilter dari ${allPetugas.length} total petugas)`
                            : ''}
                    </div>

                    <div className="flex flex-col gap-4 sm:flex-row">
                        <div className="flex-1">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                <Input
                                    type="text"
                                    placeholder="Cari nama, NIK/NIP, atau email..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="h-10 pl-10"
                                />
                            </div>
                        </div>
                        <Select
                            value={jenisPetugas}
                            onValueChange={(value) => setJenisPetugas(value)}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Jenis Petugas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Jenis</SelectItem>
                                <SelectItem value="organik">Organik</SelectItem>
                                <SelectItem value="non-organik">
                                    Non-Organik
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            value={status}
                            onValueChange={(value) => setStatus(value)}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua Status
                                </SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                                <SelectItem value="nonaktif">
                                    Nonaktif
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </ContentCard>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="flex items-center justify-between px-6 pt-4 pb-2">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Menampilkan {(currentPage - 1) * perPage + 1}-
                            {Math.min(
                                currentPage * perPage,
                                filteredAndSortedPetugas.length,
                            )}{' '}
                            dari {filteredAndSortedPetugas.length} data
                            {(search ||
                                status !== 'all' ||
                                jenisPetugas !== 'all') &&
                                ` (difilter dari ${allPetugas.length} total)`}
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    {!isPJ && (
                                        <th className="w-10 px-3 py-3.5">
                                            <input
                                                type="checkbox"
                                                checked={
                                                    paginatedPetugas.length >
                                                        0 &&
                                                    selectedIds.size ===
                                                        paginatedPetugas.length
                                                }
                                                onChange={toggleSelectAll}
                                                className="h-4 w-4 rounded border-neutral-300"
                                            />
                                        </th>
                                    )}
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('nama')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <UserIcon className="h-4 w-4" />
                                            Nama
                                            <SortIcon
                                                field="nama"
                                                sortField={sortField}
                                                sortDirection={sortDirection}
                                            />
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <CreditCard className="h-4 w-4" />
                                            NIK/NIP
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('email')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <Mail className="h-4 w-4" />
                                            Email
                                            <SortIcon
                                                field="email"
                                                sortField={sortField}
                                                sortDirection={sortDirection}
                                            />
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <Phone className="h-4 w-4" />
                                            Telepon
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <GraduationCap className="h-4 w-4" />
                                            Pendidikan
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <CheckCircle2 className="h-4 w-4" />
                                            Status
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {paginatedPetugas.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={isPJ ? 7 : 8}
                                            className="px-6 py-12 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                                <UserIcon className="h-12 w-12 opacity-20" />
                                                <p className="font-medium">
                                                    Tidak ada data petugas
                                                </p>
                                                <p className="text-xs">
                                                    Coba ubah filter atau
                                                    kriteria pencarian
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedPetugas.map((Petugas) => (
                                        <tr
                                            key={Petugas.id}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            {!isPJ && (
                                                <td className="w-10 px-3 py-3">
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedIds.has(
                                                            Petugas.id,
                                                        )}
                                                        onChange={() =>
                                                            toggleSelectPetugas(
                                                                Petugas.id,
                                                            )
                                                        }
                                                        className="h-4 w-4 rounded border-neutral-300"
                                                    />
                                                </td>
                                            )}
                                            <td className="px-3 py-3 text-sm">
                                                <div className="flex items-center gap-2">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                                        {Petugas.nama
                                                            ?.charAt(0)
                                                            .toUpperCase() ||
                                                            'P'}
                                                    </div>
                                                    <div
                                                        className="max-w-xs truncate font-medium"
                                                        title={Petugas.nama}
                                                    >
                                                        {Petugas.nama}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {Petugas.nik_masked}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                <div
                                                    className="max-w-xs truncate"
                                                    title={Petugas.email}
                                                >
                                                    {Petugas.email}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {Petugas.telepon}
                                            </td>
                                            <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {Petugas.pendidikan}
                                            </td>
                                            <td className="px-3 py-3">
                                                <StatusBadge
                                                    status={Petugas.status}
                                                />
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                        className="h-8 w-8 p-0"
                                                    >
                                                        <Link
                                                            href={`/petugas/${Petugas.hashed_id}`}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    {!isPJ && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            asChild
                                                            className="h-8 w-8 p-0"
                                                        >
                                                            <Link
                                                                href={`/petugas/${Petugas.hashed_id}/edit`}
                                                            >
                                                                <Pencil className="h-4 w-4" />
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
                    {totalPages > 1 && (
                        <div className="flex items-center justify-between border-t border-neutral-200 px-6 py-4 dark:border-neutral-800">
                            <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                Halaman{' '}
                                <span className="font-medium">
                                    {currentPage}
                                </span>{' '}
                                dari{' '}
                                <span className="font-medium">
                                    {totalPages}
                                </span>
                            </div>
                            <div className="flex items-center gap-1">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage((prev) =>
                                            Math.max(1, prev - 1),
                                        )
                                    }
                                    disabled={currentPage === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>

                                {Array.from(
                                    { length: totalPages },
                                    (_, i) => i + 1,
                                )
                                    .filter((page) => {
                                        return (
                                            page === 1 ||
                                            page === totalPages ||
                                            (page >= currentPage - 1 &&
                                                page <= currentPage + 1)
                                        );
                                    })
                                    .map((page, index, array) => {
                                        const prevPage = array[index - 1];
                                        const showEllipsis =
                                            prevPage && page > prevPage + 1;

                                        return (
                                            <div
                                                key={page}
                                                className="flex items-center gap-1"
                                            >
                                                {showEllipsis && (
                                                    <span className="px-2 text-neutral-500">
                                                        ...
                                                    </span>
                                                )}
                                                <Button
                                                    variant={
                                                        currentPage === page
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                    size="sm"
                                                    onClick={() =>
                                                        setCurrentPage(page)
                                                    }
                                                >
                                                    {page}
                                                </Button>
                                            </div>
                                        );
                                    })}

                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setCurrentPage((prev) =>
                                            Math.min(totalPages, prev + 1),
                                        )
                                    }
                                    disabled={currentPage === totalPages}
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>

            {/* Batch Edit Modal */}
            {showBatchEdit && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
                    <ContentCard className="flex max-h-[90vh] w-full max-w-6xl flex-col">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-bold text-neutral-900 dark:text-white">
                                    Batch Edit Petugas
                                </h3>
                                <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                    Mengedit {batchEditItems.length} petugas
                                    sekaligus
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setShowBatchEdit(false)}
                                className="h-8 w-8 p-0"
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        </div>

                        <div className="flex-1 overflow-auto">
                            <table className="w-full">
                                <thead className="sticky top-0 z-10 border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                    <tr>
                                        <th className="px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            #
                                        </th>
                                        <th className="min-w-[160px] px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            Nama
                                        </th>
                                        <th className="min-w-[130px] px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            Telepon
                                        </th>
                                        <th className="min-w-[100px] px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            Pendidikan
                                        </th>
                                        <th className="min-w-[120px] px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            Jenis Kelamin
                                        </th>
                                        <th className="min-w-[140px] px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            Tanggal Lahir
                                        </th>
                                        <th className="min-w-[140px] px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            Kecamatan
                                        </th>
                                        <th className="min-w-[140px] px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            Desa/Kelurahan
                                        </th>
                                        <th className="min-w-[160px] px-2 py-2 text-left text-xs font-semibold whitespace-nowrap">
                                            Alamat
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                    {batchEditItems.map((item, index) => (
                                        <tr key={item.id}>
                                            <td className="px-2 py-2 text-xs text-neutral-500">
                                                {index + 1}
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <Input
                                                    value={item.nama}
                                                    onChange={(e) =>
                                                        updateBatchItem(
                                                            index,
                                                            'nama',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="h-8 text-sm"
                                                />
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <Input
                                                    value={item.telepon}
                                                    onChange={(e) =>
                                                        updateBatchItem(
                                                            index,
                                                            'telepon',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="h-8 text-sm"
                                                />
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <Select
                                                    value={item.pendidikan}
                                                    onValueChange={(v) =>
                                                        updateBatchItem(
                                                            index,
                                                            'pendidikan',
                                                            v,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="h-8 text-sm">
                                                        <SelectValue placeholder="Pilih" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {[
                                                            'SD',
                                                            'SMP',
                                                            'SMA',
                                                            'D1',
                                                            'D2',
                                                            'D3',
                                                            'D4',
                                                            'S1',
                                                            'S2',
                                                            'S3',
                                                        ].map((p) => (
                                                            <SelectItem
                                                                key={p}
                                                                value={p}
                                                            >
                                                                {p}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <Select
                                                    value={item.jenis_kelamin}
                                                    onValueChange={(v) =>
                                                        updateBatchItem(
                                                            index,
                                                            'jenis_kelamin',
                                                            v,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="h-8 text-sm">
                                                        <SelectValue placeholder="Pilih" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="laki-laki">
                                                            Laki-laki
                                                        </SelectItem>
                                                        <SelectItem value="perempuan">
                                                            Perempuan
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <DatePicker
                                                    value={item.tanggal_lahir}
                                                    onChange={(v) =>
                                                        updateBatchItem(
                                                            index,
                                                            'tanggal_lahir',
                                                            v,
                                                        )
                                                    }
                                                    max={
                                                        new Date()
                                                            .toISOString()
                                                            .split('T')[0]
                                                    }
                                                    className="h-8"
                                                />
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <Select
                                                    value={item.kecamatan}
                                                    onValueChange={(v) => {
                                                        updateBatchItem(
                                                            index,
                                                            'kecamatan',
                                                            v,
                                                        );
                                                        updateBatchItem(
                                                            index,
                                                            'desa_kelurahan',
                                                            '',
                                                        );
                                                    }}
                                                >
                                                    <SelectTrigger className="h-8 text-sm [&>span]:text-left">
                                                        <SelectValue placeholder="Pilih" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {KECAMATAN_LIST.map(
                                                            (kec) => (
                                                                <SelectItem
                                                                    key={
                                                                        kec.kode
                                                                    }
                                                                    value={
                                                                        kec.nama
                                                                    }
                                                                >
                                                                    {kec.nama}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <Select
                                                    value={item.desa_kelurahan}
                                                    onValueChange={(v) =>
                                                        updateBatchItem(
                                                            index,
                                                            'desa_kelurahan',
                                                            v,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="h-8 text-sm">
                                                        <SelectValue placeholder="Pilih" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {getDesaByKecamatan(
                                                            item.kecamatan,
                                                        ).map((desa) => (
                                                            <SelectItem
                                                                key={desa.kode}
                                                                value={
                                                                    desa.nama
                                                                }
                                                            >
                                                                {desa.nama}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </td>
                                            <td className="px-2 py-1.5">
                                                <textarea
                                                    value={item.alamat}
                                                    onChange={(e) =>
                                                        updateBatchItem(
                                                            index,
                                                            'alamat',
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="flex min-h-[60px] w-full min-w-[200px] rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                    rows={2}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="mt-4 flex justify-end gap-2 border-t border-neutral-200 pt-4 dark:border-neutral-800">
                            <Button
                                variant="outline"
                                onClick={() => setShowBatchEdit(false)}
                            >
                                Batal
                            </Button>
                            <Button
                                onClick={handleBatchSubmit}
                                disabled={batchProcessing}
                                className="gap-2"
                            >
                                {batchProcessing ? (
                                    <>
                                        <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                        Menyimpan...
                                    </>
                                ) : (
                                    <>
                                        <PencilLine className="h-4 w-4" />
                                        Simpan Perubahan
                                    </>
                                )}
                            </Button>
                        </div>
                    </ContentCard>
                </div>
            )}

            {/* Import Modal */}
            {showImportModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
                    <ContentCard className="max-w-9xl w-full">
                        <div className="mb-6">
                            <h3 className="text-lg font-bold text-neutral-900 dark:text-white">
                                Import Petugas dari Excel
                            </h3>
                            <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                Upload file Excel untuk menambah data baru atau
                                memperbarui data existing berdasarkan NIK/email
                            </p>
                        </div>

                        <form onSubmit={handleImport} className="space-y-4">
                            <div>
                                <Label htmlFor="file" className="mb-2">
                                    Pilih File Excel
                                </Label>
                                <Input
                                    id="file"
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={handleFileChange}
                                    className="cursor-pointer"
                                />
                                {errors.file && (
                                    <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {errors.file}
                                    </p>
                                )}
                                <p className="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                                    Format: .xlsx, .xls, .csv (Maksimal: 2MB)
                                </p>
                                <p className="mt-1.5 text-xs text-neutral-500 dark:text-neutral-400">
                                    Preview akan tampil otomatis setelah file
                                    dipilih.
                                </p>
                                {isPreviewingImport && (
                                    <p className="mt-1.5 text-xs text-blue-600 dark:text-blue-400">
                                        Membaca preview data impor...
                                    </p>
                                )}
                            </div>

                            {(importPreviewRows.length > 0 ||
                                importPreviewErrors.length > 0 ||
                                importPreviewSummary) && (
                                <div className="space-y-3 rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                    <div className="flex flex-wrap items-center gap-2 text-sm">
                                        <span className="font-semibold text-foreground">
                                            Preview Impor
                                        </span>
                                        {importPreviewSummary && (
                                            <>
                                                <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                    Baru:{' '}
                                                    {
                                                        importPreviewSummary.created_count
                                                    }
                                                </span>
                                                <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                                    Update:{' '}
                                                    {
                                                        importPreviewSummary.updated_count
                                                    }
                                                </span>
                                                <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-900/50 dark:text-slate-300">
                                                    Skipped:{' '}
                                                    {
                                                        importPreviewSummary.skipped_count
                                                    }
                                                </span>
                                            </>
                                        )}
                                    </div>

                                    {previewValidationErrors.length > 0 && (
                                        <div className="space-y-2 rounded-md bg-red-50 p-3 text-xs text-red-700 dark:bg-red-950/30 dark:text-red-300">
                                            <div className="flex items-center gap-1.5 font-semibold">
                                                <AlertTriangle className="h-4 w-4" />
                                                Validasi preview menemukan{' '}
                                                {previewValidationErrors.length}{' '}
                                                masalah
                                            </div>
                                            <div className="space-y-1">
                                                {previewVisibleValidationErrors.map(
                                                    (error) => (
                                                        <p key={error}>
                                                            {error}
                                                        </p>
                                                    ),
                                                )}
                                            </div>
                                            {previewHiddenValidationErrorCount >
                                                0 && (
                                                <div className="flex items-center justify-between gap-2 pt-1">
                                                    <p className="text-[11px] font-medium">
                                                        +
                                                        {
                                                            previewHiddenValidationErrorCount
                                                        }{' '}
                                                        error lainnya
                                                    </p>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() =>
                                                            setShowImportErrorDetail(
                                                                true,
                                                            )
                                                        }
                                                        className="h-7 text-[11px]"
                                                    >
                                                        Lihat detail
                                                    </Button>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {previewSkippedNotices.length > 0 && (
                                        <div className="space-y-1 rounded-md bg-slate-50 p-3 text-xs text-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                                            <p className="font-semibold">
                                                {previewSkippedNotices.length}{' '}
                                                baris tidak memiliki perubahan,
                                                akan di-skip saat import.
                                            </p>
                                        </div>
                                    )}

                                    {importPreviewRows.length > 0 && (
                                        <div className="max-h-72 overflow-auto rounded-md border border-neutral-200 dark:border-neutral-700">
                                            <table className="w-full text-xs">
                                                <thead className="bg-neutral-100 dark:bg-neutral-800">
                                                    <tr>
                                                        <th className="px-2 py-1 text-left">
                                                            Baris
                                                        </th>
                                                        <th className="px-2 py-1 text-left">
                                                            Aksi
                                                        </th>
                                                        <th className="px-2 py-1 text-left">
                                                            Validasi
                                                        </th>
                                                        {PREVIEW_COLUMNS.map(
                                                            (column) => (
                                                                <th
                                                                    key={
                                                                        column.key
                                                                    }
                                                                    className="px-2 py-1 text-left"
                                                                >
                                                                    {
                                                                        column.label
                                                                    }
                                                                </th>
                                                            ),
                                                        )}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {importPreviewRows.map(
                                                        (row) =>
                                                            (() => {
                                                                const isNoChangeRow =
                                                                    row.action ===
                                                                        'none' ||
                                                                    (row.action ===
                                                                        'update' &&
                                                                        row
                                                                            .changed_fields
                                                                            .length ===
                                                                            0 &&
                                                                        row.warnings.some(
                                                                            (
                                                                                warning,
                                                                            ) =>
                                                                                warning.includes(
                                                                                    NO_CHANGE_MESSAGE,
                                                                                ),
                                                                        ));

                                                                const actionLabel =
                                                                    isNoChangeRow
                                                                        ? 'None'
                                                                        : row.action ===
                                                                            'create'
                                                                          ? 'Buat'
                                                                          : 'Update';

                                                                const actionClass =
                                                                    isNoChangeRow
                                                                        ? 'bg-slate-100 text-slate-700 dark:bg-slate-900/60 dark:text-slate-300'
                                                                        : row.action ===
                                                                            'create'
                                                                          ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                                          : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';

                                                                const validationLabel =
                                                                    isNoChangeRow
                                                                        ? 'Skipped'
                                                                        : row
                                                                                .warnings
                                                                                .length >
                                                                            0
                                                                          ? `${row.warnings.length} warning`
                                                                          : 'Valid';

                                                                const validationClass =
                                                                    isNoChangeRow
                                                                        ? 'bg-slate-100 text-slate-700 dark:bg-slate-900/60 dark:text-slate-300'
                                                                        : row
                                                                                .warnings
                                                                                .length >
                                                                            0
                                                                          ? 'bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300'
                                                                          : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300';

                                                                return (
                                                                    <tr
                                                                        key={`${row.row_number}-${row.nik}-${row.email}`}
                                                                        className={`border-t border-neutral-200 dark:border-neutral-700 ${
                                                                            row.valid_for_import
                                                                                ? ''
                                                                                : isNoChangeRow
                                                                                  ? 'bg-slate-50/50 dark:bg-slate-900/20'
                                                                                  : 'bg-red-50/40 dark:bg-red-950/10'
                                                                        }`}
                                                                    >
                                                                        <td className="px-2 py-1">
                                                                            {
                                                                                row.row_number
                                                                            }
                                                                        </td>
                                                                        <td className="px-2 py-1">
                                                                            <span
                                                                                className={`rounded-full px-2 py-0.5 font-medium ${actionClass}`}
                                                                            >
                                                                                {
                                                                                    actionLabel
                                                                                }
                                                                            </span>
                                                                        </td>
                                                                        <td className="px-2 py-1">
                                                                            <span
                                                                                className={`rounded-full px-2 py-0.5 font-medium ${validationClass}`}
                                                                            >
                                                                                {
                                                                                    validationLabel
                                                                                }
                                                                            </span>
                                                                        </td>
                                                                        {PREVIEW_COLUMNS.map(
                                                                            (
                                                                                column,
                                                                            ) => {
                                                                                const value =
                                                                                    row
                                                                                        .columns?.[
                                                                                        column
                                                                                            .key
                                                                                    ] ??
                                                                                    '';
                                                                                const isChanged =
                                                                                    row.changed_fields.includes(
                                                                                        column.key,
                                                                                    );

                                                                                return (
                                                                                    <td
                                                                                        key={`${row.row_number}-${column.key}`}
                                                                                        className={`px-2 py-1 whitespace-nowrap ${
                                                                                            isChanged
                                                                                                ? 'bg-amber-50 font-medium text-amber-800 dark:bg-amber-950/30 dark:text-amber-200'
                                                                                                : ''
                                                                                        }`}
                                                                                    >
                                                                                        <div className="max-w-[170px] truncate">
                                                                                            {value ||
                                                                                                '-'}
                                                                                        </div>
                                                                                        {isChanged && (
                                                                                            <span className="text-[10px] font-semibold tracking-wide text-amber-700 uppercase dark:text-amber-300">
                                                                                                Ubah
                                                                                            </span>
                                                                                        )}
                                                                                    </td>
                                                                                );
                                                                            },
                                                                        )}
                                                                    </tr>
                                                                );
                                                            })(),
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="flex justify-end gap-2 pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setShowImportModal(false);
                                        setImportPreviewRows([]);
                                        setImportPreviewErrors([]);
                                        setImportPreviewSummary(null);
                                        setHasImportPreview(false);
                                        setShowImportErrorDetail(false);
                                        reset();
                                    }}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        !data.file ||
                                        !hasImportPreview ||
                                        !hasImportablePreviewRows
                                    }
                                    className="gap-2"
                                >
                                    {processing ? (
                                        <>
                                            <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                            Mengimport...
                                        </>
                                    ) : (
                                        <>
                                            <FileUp className="h-4 w-4" />
                                            Import
                                        </>
                                    )}
                                </Button>
                            </div>
                        </form>
                    </ContentCard>
                </div>
            )}

            <Dialog
                open={showImportErrorDetail}
                onOpenChange={setShowImportErrorDetail}
            >
                <DialogContent className="max-h-[85vh] max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Detail Error Validasi Preview</DialogTitle>
                    </DialogHeader>
                    <div className="max-h-[65vh] space-y-2 overflow-y-auto rounded-md border border-neutral-200 p-3 text-sm dark:border-neutral-700">
                        {importPreviewErrors.length > 0 ? (
                            importPreviewErrors.map((error) => (
                                <p
                                    key={error}
                                    className={`rounded px-2 py-1 ${
                                        error.includes(NO_CHANGE_MESSAGE)
                                            ? 'border border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700/60 dark:bg-slate-900/40 dark:text-slate-300'
                                            : 'border border-red-200 bg-red-50 text-red-700 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300'
                                    }`}
                                >
                                    {error}
                                </p>
                            ))
                        ) : (
                            <p className="text-neutral-500 dark:text-neutral-400">
                                Tidak ada detail error.
                            </p>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
