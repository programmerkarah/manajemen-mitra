import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search, Pencil, X, ChevronLeft, ChevronRight, Save, Loader2 } from 'lucide-react';
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogClose } from '@/components/ui/dialog';
import { useState, useEffect } from 'react';
import { StatusBadge } from '@/components/status-badge';
import { encryptFilters } from '@/utils/encryption';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Manajemen User', href: '/users' },
];

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
    roles: Role[];
}

interface UsersIndexProps {
    users: {
        data: User[];
        links: any[];
        meta: any;
    };
    filters: {
        encrypted?: string
        decrypted?: {
            search?: string
        }
    };
}

export default function Index({ users, filters }: UsersIndexProps) {
    const initialFilters = filters.decrypted || {};
    const [search, setSearch] = useState(initialFilters.search || '');

    // Auto-filter with debounce
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            const filterParams = { search };
            const encryptedFilters = encryptFilters(filterParams);

            router.post(
                '/users',
                { encrypted_filters: encryptedFilters },
                { 
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manajemen User" />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Manajemen User"
                    description="Kelola role dan hak akses pengguna sistem"
                />

                {/* Search */}
                <ContentCard>
                    <div className="flex gap-4">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-neutral-400" />
                            <Input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari berdasarkan nama, username, atau email..."
                                className="h-11 text-base pl-10"
                            />
                        </div>
                        {search && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setSearch('');
                                    router.post('/users', { encrypted_filters: encryptFilters({}) }, { preserveState: true });
                                }}
                                className="gap-2 h-11"
                            >
                                <X className="h-5 w-5" />
                                Reset
                            </Button>
                        )}
                    </div>
                </ContentCard>

                {/* User List */}
                <ContentCard padding="none">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                <tr>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Nama
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Username
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Email
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Role
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Status
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Email
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        2FA
                                    </th>
                                    <th className="whitespace-nowrap px-3 py-3.5 text-center text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                {users.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={8}
                                            className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400"
                                        >
                                            <div className="flex flex-col items-center gap-2">
                                                <Search className="h-8 w-8 text-neutral-400" />
                                                <p>Tidak ada user yang ditemukan</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    users.data.map((user) => (
                                        <tr
                                            key={user.id}
                                            className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                        >
                                            <td className="px-3 py-3 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                <div className="max-w-xs truncate" title={user.name}>{user.name}</div>
                                            </td>
                                            <td className="whitespace-nowrap px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                {user.username}
                                            </td>
                                            <td className="px-3 py-3 text-sm text-neutral-600 dark:text-neutral-400">
                                                <div className="max-w-xs truncate" title={user.email}>{user.email}</div>
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
                                                <StatusBadge status={user.is_active ? 'aktif' : 'nonaktif'} />
                                            </td>
                                            <td className="px-3 py-3">
                                                <StatusBadge status={user.email_verified_at ? 'terverifikasi' : 'belum_verifikasi'} />
                                            </td>
                                            <td className="px-3 py-3">
                                                <StatusBadge status={user.two_factor_enabled ? '2fa_aktif' : '2fa_nonaktif'} />
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="flex justify-center gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                        className="gap-2 h-9"
                                                    >
                                                        <Link href={`/users/${user.id}/edit`}>
                                                            <Pencil className="h-4 w-4" />
                                                            Edit Role
                                                        </Link>
                                                    </Button>
                                                    <Dialog>
                                                        <DialogTrigger asChild>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                className="text-red-600 border-red-500 hover:bg-red-50 dark:hover:bg-red-900/30"
                                                            >
                                                                Reset 2FA
                                                            </Button>
                                                        </DialogTrigger>
                                                        <DialogContent>
                                                            <DialogHeader>
                                                                <DialogTitle>Reset Autentikasi Dua Faktor</DialogTitle>
                                                                <DialogDescription>
                                                                    Reset 2FA akan menghapus autentikasi dua faktor user ini. Lanjutkan?
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <form method="post" action={`/users/${user.id}/reset-2fa`}>
                                                                <input type="hidden" name="_token" value={window.Laravel?.csrfToken || document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''} />
                                                                <DialogFooter>
                                                                    <DialogClose asChild>
                                                                        <Button type="button" variant="outline">
                                                                            Batal
                                                                        </Button>
                                                                    </DialogClose>
                                                                    <Button type="submit" variant="destructive">
                                                                        Reset 2FA
                                                                    </Button>
                                                                </DialogFooter>
                                                            </form>
                                                        </DialogContent>
                                                    </Dialog>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {users?.meta?.last_page > 1 && (
                        <div className="border-t border-neutral-200 px-6 py-4 dark:border-neutral-800">
                            <div className="flex items-center justify-between">
                                <div className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Menampilkan <span className="font-medium">{users?.meta?.from}</span> hingga{' '}
                                    <span className="font-medium">{users?.meta?.to}</span> dari{' '}
                                    <span className="font-medium">{users?.meta?.total}</span> hasil
                                </div>
                                <div className="flex items-center gap-1">
                                    {users.links.map((link, index) => {
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
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
