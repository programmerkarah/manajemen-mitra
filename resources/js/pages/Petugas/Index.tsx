import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
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
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    Download,
    Eye,
    FileUp,
    Pencil,
    Plus,
    Search,
    User as UserIcon,
    CreditCard,
    Mail,
    Phone,
    GraduationCap,
    CheckCircle2,
    RefreshCw,
    ChevronUp,
    ChevronDown,
} from 'lucide-react';
import { useEffect, useRef, useState, useMemo } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Petugas', href: '/petugas' }];

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
        links: any[];
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

export default function Index({ petugas, filters }: PetugasIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';

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

    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    // Client-side filtering and sorting
    const filteredAndSortedPetugas = useMemo(() => {
        let result: Petugas[] = [...allPetugas];

        // Filter by search
        if (search) {
            const query = search.toLowerCase();
            result = result.filter((item: Petugas) => 
                item.nama?.toLowerCase().includes(query) ||
                item.nik_masked?.toLowerCase().includes(query) ||
                item.email?.toLowerCase().includes(query)
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
                const jenisValue = jenisPetugas === 'organik' ? 'organik' : 'non-organik';
                return item.jenis_petugas === jenisValue;
            });
        }

        // Sort
        result.sort((a: Petugas, b: Petugas) => {
            let aVal = '', bVal = '';
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

    // Reset to page 1 when filters change
    useEffect(() => {
        setCurrentPage(1);
    }, [search, status, jenisPetugas]);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 500);
            }
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

    const SortIcon = ({ field }: { field: 'nama' | 'email' }) => {
        if (sortField !== field) return null;
        return sortDirection === 'asc' ? 
            <ChevronUp className="w-4 h-4" /> : 
            <ChevronDown className="w-4 h-4" />;
    };

    const handleImport = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.file) return;

        post('/petugas/import', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setShowImportModal(false);
                reset();
            },
            onError: (errors) => {
                console.error('Import errors:', errors);
            },
        });
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            setData('file', e.target.files[0]);
        }
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
                    <div className="flex gap-2">
                        <Button 
                            variant="outline" 
                            size="sm"
                            onClick={handleRefresh}
                            disabled={isRefreshing}
                        >
                            <RefreshCw className={`w-4 h-4 mr-2 ${isRefreshing ? 'animate-spin' : ''}`} />
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
                                    onClick={() => setShowImportModal(true)}
                                    className="gap-2"
                                >
                                    <FileUp className="h-4 w-4" />
                                    Import Excel
                                </Button>
                                <Button size="sm" asChild className="gap-2">
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
                        Menampilkan <span className="font-semibold text-foreground">{((currentPage - 1) * perPage) + 1}-{Math.min(currentPage * perPage, filteredAndSortedPetugas.length)}</span> dari <span className="font-semibold text-foreground">{filteredAndSortedPetugas.length}</span> petugas {search || status !== 'all' || jenisPetugas !== 'all' ? `(difilter dari ${allPetugas.length} total petugas)` : ''}
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
                                <SelectItem value="all">
                                    Semua Jenis
                                </SelectItem>
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
                            Menampilkan {((currentPage - 1) * perPage) + 1}-{Math.min(currentPage * perPage, filteredAndSortedPetugas.length)} dari {filteredAndSortedPetugas.length} data
                            {(search || status) && ` (difilter dari ${allPetugas.length} total)`}
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th 
                                        className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('nama')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <UserIcon className="w-4 h-4" />
                                            Nama
                                            <SortIcon field="nama" />
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <CreditCard className="w-4 h-4" />
                                            NIK/NIP
                                        </div>
                                    </th>
                                    <th 
                                        className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('email')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <Mail className="w-4 h-4" />
                                            Email
                                            <SortIcon field="email" />
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <Phone className="w-4 h-4" />
                                            Telepon
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <GraduationCap className="w-4 h-4" />
                                            Pendidikan
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <CheckCircle2 className="w-4 h-4" />
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
                                            colSpan={7}
                                            className="px-6 py-12 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                                <UserIcon className="h-12 w-12 opacity-20" />
                                                <p className="font-medium">Tidak ada data petugas</p>
                                                <p className="text-xs">Coba ubah filter atau kriteria pencarian</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedPetugas.map((Petugas, index) => (
                                    <tr
                                        key={Petugas.id}
                                        className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                    >
                                        <td className="px-3 py-3 text-sm">
                                            <div className="flex items-center gap-2">
                                                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-xs">
                                                    {Petugas.nama?.charAt(0).toUpperCase() || 'P'}
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
                        <div className="mt-6 flex items-center justify-between">
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
                                    onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                                    disabled={currentPage === 1}
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>

                                {Array.from({ length: totalPages }, (_, i) => i + 1)
                                    .filter(page => {
                                        return page === 1 || 
                                               page === totalPages || 
                                               (page >= currentPage - 1 && page <= currentPage + 1);
                                    })
                                    .map((page, index, array) => {
                                        const prevPage = array[index - 1];
                                        const showEllipsis = prevPage && page > prevPage + 1;

                                        return (
                                            <div key={page} className="flex items-center gap-1">
                                                {showEllipsis && (
                                                    <span className="px-2 text-neutral-500">...</span>
                                                )}
                                                <Button
                                                    variant={currentPage === page ? 'default' : 'outline'}
                                                    size="sm"
                                                    onClick={() => setCurrentPage(page)}
                                                >
                                                    {page}
                                                </Button>
                                            </div>
                                        );
                                    })}

                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                                    disabled={currentPage === totalPages}
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>

            {/* Import Modal */}
            {showImportModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
                    <ContentCard className="w-full max-w-md">
                        <div className="mb-6">
                            <h3 className="text-lg font-bold text-neutral-900 dark:text-white">
                                Import Petugas dari Excel
                            </h3>
                            <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                Upload file Excel untuk menambahkan data petugas
                                secara bulk
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
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        setShowImportModal(false);
                                        reset();
                                    }}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || !data.file}
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
        </AppLayout>
    );
}
