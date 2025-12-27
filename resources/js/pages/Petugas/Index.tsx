import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Download, FileUp, Plus, Search, Eye, Pencil, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState, useEffect } from 'react';
import { StatusBadge } from '@/components/status-badge';
import { encryptFilters } from '@/utils/encryption';
import { useDecryptedData } from '@/hooks/useDecryptedData';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Petugas', href: '/petugas' },
];

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
        encrypted?: string
        decrypted?: {
            search?: string
            status?: string
            tahun?: string
        }
    };
}

export default function Index({ petugas, filters }: PetugasIndexProps) {
    const { auth } = usePage<SharedData>().props;
    const isPJ = auth.activeRole?.name === 'pj';
    const initialFilters = filters.decrypted || {};
    
    const decryptedPetugas = useDecryptedData<Petugas>(petugas.encrypted);
    const [search, setSearch] = useState(initialFilters.search || '');
    const [status, setStatus] = useState(initialFilters.status || '');
    const [showImportModal, setShowImportModal] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        file: null as File | null,
    });

    // Auto-filter with debounce
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            const filterParams = { search, status };
            const encryptedFilters = encryptFilters(filterParams);

            router.post(
                '/petugas',
                { encrypted_filters: encryptedFilters },
                { 
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search, status]);

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
                </PageHeader>

                {/* Filters */}
                <ContentCard>
                    <div className="flex flex-col gap-4 sm:flex-row">
                        <div className="flex-1">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                <Input
                                    type="text"
                                    placeholder="Cari nama, NIK/NIP, atau email..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="h-10 pl-10"
                                />
                            </div>
                        </div>
                        <Select value={status} onValueChange={(value) => setStatus(value)}>
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder="Semua Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="aktif">Aktif</SelectItem>
                                <SelectItem value="nonaktif">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </ContentCard>

                {/* Table */}
                <ContentCard padding="none">
                    <div className="overflow-x-auto">
                        <div className="overflow-hidden rounded-2xl">
                            <table className="w-full">
                                <thead className="bg-white/60 dark:bg-neutral-800/60 backdrop-blur-md">
                                <tr>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        Nama
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        NIK/NIP
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        Email
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        Telepon
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        Pendidikan
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        Status
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-center text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-white/10 bg-white/30 dark:divide-neutral-700/20 dark:bg-neutral-800/30 backdrop-blur-sm">
                                {decryptedPetugas.map((Petugas) => (
                                    <tr
                                        key={Petugas.id}
                                        className="transition-colors hover:bg-white/50 dark:hover:bg-neutral-800/50"
                                    >
                                        <td className="px-3 py-3 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                            <div className="max-w-xs truncate" title={Petugas.nama}>{Petugas.nama}</div>
                                        </td>
                                        <td className="whitespace-nowrap px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                            {Petugas.nik_masked}
                                        </td>
                                        <td className="px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                            <div className="max-w-xs truncate" title={Petugas.email}>{Petugas.email}</div>
                                        </td>
                                        <td className="whitespace-nowrap px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                            {Petugas.telepon}
                                        </td>
                                        <td className="whitespace-nowrap px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                            {Petugas.pendidikan}
                                        </td>
                                        <td className="px-3 py-3">
                                            <StatusBadge status={Petugas.status} />
                                        </td>
                                        <td className="px-3 py-3">
                                            <div className="flex items-center justify-center gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                    className="h-8 w-8 p-0"
                                                >
                                                    <Link href={`/petugas/${Petugas.hashed_id}`}>
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
                                                        <Link href={`/petugas/${Petugas.hashed_id}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        </div>
                    </div>

                    {/* Pagination */}
                    {petugas.links && (
                        <div className="flex items-center justify-center gap-1 border-t border-neutral-200 px-6 py-4 dark:border-neutral-800">
                            {petugas.links.map((link, index) => {
                                const isFirst = link.label.includes('Previous');
                                const isLast = link.label.includes('Next');
                                
                                return (
                                    <button
                                        key={index}
                                        onClick={() => link.url && router.get(link.url)}
                                        disabled={!link.url}
                                        className={`min-w-[2.5rem] rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                            link.active
                                                ? 'bg-blue-600 text-white shadow-sm'
                                                : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                                        } ${!link.url && 'cursor-not-allowed opacity-50'}`}
                                    >
                                        {isFirst ? (
                                            <ChevronLeft className="h-4 w-4" />
                                        ) : isLast ? (
                                            <ChevronRight className="h-4 w-4" />
                                        ) : (
                                            link.label
                                        )}
                                    </button>
                                );
                            })}
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
                                Upload file Excel untuk menambahkan data petugas secara bulk
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


