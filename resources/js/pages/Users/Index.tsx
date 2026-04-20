import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Mail,
    MailQuestion,
    Pencil,
    RefreshCw,
    Search,
    Shield,
    User as UserIcon,
    UserRoundCog,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Manajemen User', href: '/users' },
];

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

interface Role {
    id: number;
    name: string;
    display_name: string;
    description: string;
}

interface User {
    id: number;
    name: string;
    username: string;
    email: string;
    is_active: boolean;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    is_sso_user: boolean;
    roles: Role[];
}

interface UsersIndexProps {
    users: {
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
        };
    };
}

export default function Index({ users }: UsersIndexProps) {
    const allUsers = useDecryptedData<User>(users.encrypted);

    const [search, setSearch] = useState('');
    const [sortField, setSortField] = useState<'name' | 'username' | 'email'>(
        'name',
    );
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
    const [currentPage, setCurrentPage] = useState(1);
    const [perPage] = useState(15);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const prevSearchRef = useRef(search);

    // Client-side filtering and sorting
    const filteredAndSortedUsers = useMemo(() => {
        let result: User[] = [...allUsers];

        // Filter
        if (search) {
            const query = search.toLowerCase();
            result = result.filter(
                (user: User) =>
                    user.name?.toLowerCase().includes(query) ||
                    user.username?.toLowerCase().includes(query) ||
                    user.email?.toLowerCase().includes(query) ||
                    user.roles?.some((role: Role) =>
                        role.display_name?.toLowerCase().includes(query),
                    ),
            );
        }

        // Sort
        result.sort((a: User, b: User) => {
            let aVal = '',
                bVal = '';
            switch (sortField) {
                case 'username':
                    aVal = a.username?.toLowerCase() || '';
                    bVal = b.username?.toLowerCase() || '';
                    break;
                case 'email':
                    aVal = a.email?.toLowerCase() || '';
                    bVal = b.email?.toLowerCase() || '';
                    break;
                case 'name':
                default:
                    aVal = a.name?.toLowerCase() || '';
                    bVal = b.name?.toLowerCase() || '';
                    break;
            }
            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    }, [allUsers, search, sortField, sortDirection]);

    // Client-side pagination
    const totalPages = Math.ceil(filteredAndSortedUsers.length / perPage);
    const paginatedUsers = useMemo(() => {
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        return filteredAndSortedUsers.slice(start, end);
    }, [filteredAndSortedUsers, currentPage, perPage]);

    // Reset to page 1 when search changes
    useEffect(() => {
        if (prevSearchRef.current !== search) {
            // eslint-disable-next-line react-hooks/set-state-in-effect -- Conditional reset based on search change via ref
            setCurrentPage(1);
            prevSearchRef.current = search;
        }
    }, [search]);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 500);
            },
        });
    };

    const handleSort = (field: 'name' | 'username' | 'email') => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const usersSummary = useMemo(() => {
        const total = allUsers.length;
        const aktif = allUsers.filter((item) => item.is_active).length;
        const verified = allUsers.filter(
            (item) => item.email_verified_at !== null,
        ).length;
        const twoFactor = allUsers.filter(
            (item) => item.two_factor_enabled,
        ).length;

        return {
            total,
            aktif,
            verified,
            twoFactor,
        };
    }, [allUsers]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manajemen User" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Manajemen User"
                    description="Kelola role dan hak akses pengguna sistem"
                >
                    <Button
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
                </PageHeader>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <ContentCard className="cursor-pointer border border-blue-200/60 bg-gradient-to-br from-blue-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-blue-900/40 dark:from-blue-950/30 dark:to-neutral-900">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm text-blue-700 dark:text-blue-300">
                                    Total User
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {usersSummary.total}
                                </p>
                            </div>
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                <UserIcon className="h-5 w-5" />
                            </span>
                        </div>
                    </ContentCard>

                    <ContentCard className="cursor-pointer border border-emerald-200/60 bg-gradient-to-br from-emerald-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-900/40 dark:from-emerald-950/30 dark:to-neutral-900">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm text-emerald-700 dark:text-emerald-300">
                                    User Aktif
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {usersSummary.aktif}
                                </p>
                            </div>
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                                <CheckCircle2 className="h-5 w-5" />
                            </span>
                        </div>
                    </ContentCard>

                    <ContentCard className="cursor-pointer border border-indigo-200/60 bg-gradient-to-br from-indigo-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-indigo-900/40 dark:from-indigo-950/30 dark:to-neutral-900">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm text-indigo-700 dark:text-indigo-300">
                                    Email Terverifikasi
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {usersSummary.verified}
                                </p>
                            </div>
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                <Mail className="h-5 w-5" />
                            </span>
                        </div>
                    </ContentCard>

                    <ContentCard className="cursor-pointer border border-violet-200/60 bg-gradient-to-br from-violet-50 to-white transition-all hover:-translate-y-0.5 hover:shadow-md dark:border-violet-900/40 dark:from-violet-950/30 dark:to-neutral-900">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm text-violet-700 dark:text-violet-300">
                                    2FA Aktif
                                </p>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {usersSummary.twoFactor}
                                </p>
                            </div>
                            <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300">
                                <Shield className="h-5 w-5" />
                            </span>
                        </div>
                    </ContentCard>
                </div>

                {/* Search */}
                <ContentCard>
                    <div className="space-y-3">
                        <div className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-neutral-400" />
                                <Input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari user (nama, username, email, role)..."
                                    className="h-11 pl-10 text-base"
                                />
                            </div>
                            {search && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setSearch('')}
                                    className="h-11 gap-2"
                                >
                                    <X className="h-5 w-5" />
                                    Reset
                                </Button>
                            )}
                        </div>
                    </div>
                </ContentCard>

                {/* User List */}
                <ContentCard padding="none">
                    <div className="flex items-center justify-between px-6 pt-4 pb-2">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            Menampilkan {(currentPage - 1) * perPage + 1}-
                            {Math.min(
                                currentPage * perPage,
                                filteredAndSortedUsers.length,
                            )}{' '}
                            dari {filteredAndSortedUsers.length} user
                            {search &&
                                ` (difilter dari ${allUsers.length} total)`}
                        </p>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('name')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <UserIcon className="h-4 w-4" />
                                            Nama
                                            <SortIcon
                                                field="name"
                                                sortField={sortField}
                                                sortDirection={sortDirection}
                                            />
                                        </div>
                                    </th>
                                    <th
                                        className="cursor-pointer px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap hover:bg-neutral-100 dark:hover:bg-neutral-800"
                                        onClick={() => handleSort('username')}
                                    >
                                        <div className="flex items-center gap-1.5">
                                            <UserIcon className="h-4 w-4" />
                                            Username
                                            <SortIcon
                                                field="username"
                                                sortField={sortField}
                                                sortDirection={sortDirection}
                                            />
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
                                            <UserRoundCog className="h-4 w-4" />
                                            Role
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <CheckCircle2 className="h-4 w-4" />
                                            Status
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <MailQuestion className="h-4 w-4" />
                                            Verifikasi Email
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-left text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        <div className="flex items-center gap-1.5">
                                            <Shield className="h-4 w-4" />
                                            2FA
                                        </div>
                                    </th>
                                    <th className="px-3 py-3.5 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {paginatedUsers.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-6 py-12 text-center"
                                        >
                                            <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                                <UserIcon className="h-12 w-12 opacity-20" />
                                                <p className="font-medium">
                                                    Tidak ada user yang
                                                    ditemukan
                                                </p>
                                                <p className="text-xs">
                                                    Coba ubah filter atau
                                                    kriteria pencarian
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    paginatedUsers.map((user) => (
                                        <tr
                                            key={user.id}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-3 py-3 text-sm">
                                                <div className="flex items-center gap-2">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                                        {user.name
                                                            ?.charAt(0)
                                                            .toUpperCase() ||
                                                            'U'}
                                                    </div>
                                                    <div
                                                        className="max-w-xs truncate font-medium"
                                                        title={user.name}
                                                    >
                                                        {user.name}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-sm whitespace-nowrap text-neutral-600 dark:text-neutral-400">
                                                {user.username}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                <div
                                                    className="max-w-xs truncate"
                                                    title={user.email}
                                                >
                                                    {user.email}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="flex flex-wrap gap-1">
                                                    {user.roles.map((role) => (
                                                        <StatusBadge
                                                            key={role.id}
                                                            status={role.name}
                                                        />
                                                    ))}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                <StatusBadge
                                                    status={
                                                        user.is_active
                                                            ? 'aktif'
                                                            : 'nonaktif'
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-3">
                                                {user.is_sso_user ? (
                                                    <StatusBadge status="sso_active" />
                                                ) : (
                                                    <StatusBadge
                                                        status={
                                                            user.email_verified_at
                                                                ? 'terverifikasi'
                                                                : 'belum_verifikasi'
                                                        }
                                                    />
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                {user.is_sso_user ? (
                                                    <StatusBadge status="sso_active" />
                                                ) : (
                                                    <StatusBadge
                                                        status={
                                                            user.two_factor_enabled
                                                                ? '2fa_aktif'
                                                                : '2fa_nonaktif'
                                                        }
                                                    />
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="flex justify-center gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                        className="h-9 gap-2"
                                                    >
                                                        <Link
                                                            href={`/users/${user.id}/edit`}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                            Edit Role
                                                        </Link>
                                                    </Button>
                                                    {!user.is_sso_user && (
                                                        <Dialog>
                                                            <DialogTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="border-red-500 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                                >
                                                                    Reset 2FA
                                                                </Button>
                                                            </DialogTrigger>
                                                            <DialogContent>
                                                                <DialogHeader>
                                                                    <DialogTitle>
                                                                        Reset
                                                                        Autentikasi
                                                                        Dua
                                                                        Faktor
                                                                    </DialogTitle>
                                                                    <DialogDescription>
                                                                        Reset
                                                                        2FA akan
                                                                        menghapus
                                                                        autentikasi
                                                                        dua
                                                                        faktor
                                                                        user
                                                                        ini.
                                                                        Lanjutkan?
                                                                    </DialogDescription>
                                                                </DialogHeader>
                                                                <form
                                                                    method="post"
                                                                    action={`/users/${user.id}/reset-2fa`}
                                                                >
                                                                    <input
                                                                        type="hidden"
                                                                        name="_token"
                                                                        value={
                                                                            (
                                                                                window as Window & {
                                                                                    Laravel?: {
                                                                                        csrfToken?: string;
                                                                                    };
                                                                                }
                                                                            )
                                                                                ?.Laravel
                                                                                ?.csrfToken ||
                                                                            document
                                                                                .querySelector(
                                                                                    'meta[name=csrf-token]',
                                                                                )
                                                                                ?.getAttribute(
                                                                                    'content',
                                                                                ) ||
                                                                            ''
                                                                        }
                                                                    />
                                                                    <DialogFooter>
                                                                        <DialogClose
                                                                            asChild
                                                                        >
                                                                            <Button
                                                                                type="button"
                                                                                variant="outline"
                                                                            >
                                                                                Batal
                                                                            </Button>
                                                                        </DialogClose>
                                                                        <Button
                                                                            type="submit"
                                                                            variant="destructive"
                                                                        >
                                                                            Reset
                                                                            2FA
                                                                        </Button>
                                                                    </DialogFooter>
                                                                </form>
                                                            </DialogContent>
                                                        </Dialog>
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
                        <div className="border-t border-neutral-200 px-6 py-4 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
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
                                    {/* Previous Button */}
                                    <button
                                        onClick={() =>
                                            setCurrentPage((prev) =>
                                                Math.max(1, prev - 1),
                                            )
                                        }
                                        disabled={currentPage === 1}
                                        className={`min-w-[2.5rem] rounded-lg px-3 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 ${currentPage === 1 && 'cursor-not-allowed opacity-50'}`}
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                    </button>

                                    {/* Page Numbers */}
                                    {Array.from(
                                        { length: totalPages },
                                        (_, i) => i + 1,
                                    )
                                        .filter((page) => {
                                            // Show first page, last page, current page, and pages around current
                                            return (
                                                page === 1 ||
                                                page === totalPages ||
                                                (page >= currentPage - 1 &&
                                                    page <= currentPage + 1)
                                            );
                                        })
                                        .map((page, index, array) => {
                                            // Add ellipsis
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
                                                    <button
                                                        onClick={() =>
                                                            setCurrentPage(page)
                                                        }
                                                        className={`min-w-[2.5rem] rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                                            currentPage === page
                                                                ? 'bg-blue-600 text-white shadow-sm'
                                                                : 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                                                        }`}
                                                    >
                                                        {page}
                                                    </button>
                                                </div>
                                            );
                                        })}

                                    {/* Next Button */}
                                    <button
                                        onClick={() =>
                                            setCurrentPage((prev) =>
                                                Math.min(totalPages, prev + 1),
                                            )
                                        }
                                        disabled={currentPage === totalPages}
                                        className={`min-w-[2.5rem] rounded-lg px-3 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800 ${currentPage === totalPages && 'cursor-not-allowed opacity-50'}`}
                                    >
                                        <ChevronRight className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
