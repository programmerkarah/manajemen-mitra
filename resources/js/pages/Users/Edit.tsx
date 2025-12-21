import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Save, X, Loader2, ArrowLeft } from 'lucide-react';
import { StatusBadge } from '@/components/status-badge';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

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
    roles: Role[];
}

interface UsersEditProps {
    user: User;
    allRoles: Role[];
}

export default function Edit({ user, allRoles }: UsersEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Manajemen User', href: '/users' },
        { title: `Edit Role - ${user.name}`, href: `/users/${user.id}/edit` },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        roles: user.roles.map((role) => role.id),
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/users/${user.id}`, {
            preserveScroll: true,
        });
    };

    const handleRoleToggle = (roleId: number) => {
        if (data.roles.includes(roleId)) {
            setData('roles', data.roles.filter((id) => id !== roleId));
        } else {
            setData('roles', [...data.roles, roleId]);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Role - ${user.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Edit Role User"
                    description={`Kelola role dan hak akses untuk ${user.name}`}
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/users">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* User Info */}
                <ContentCard>
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Informasi User
                    </h3>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <Label className="text-base font-semibold">
                                Nama
                            </Label>
                            <p className="mt-1 text-gray-900 dark:text-white">
                                {user.name}
                            </p>
                        </div>
                        <div>
                            <Label className="text-base font-semibold">
                                Username
                            </Label>
                            <p className="mt-1 text-gray-900 dark:text-white">
                                {user.username}
                            </p>
                        </div>
                        <div>
                            <Label className="text-base font-semibold">
                                Email
                            </Label>
                            <p className="mt-1 text-gray-900 dark:text-white">
                                {user.email}
                            </p>
                        </div>
                    </div>
                </ContentCard>

                {/* Role Form */}
                <form onSubmit={handleSubmit}>
                    <ContentCard>
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Pilih Role
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            User dapat memiliki lebih dari satu role. Pilih semua role yang sesuai.
                        </p>

                        {errors.roles && (
                            <div className="rounded-md bg-red-50 p-4 dark:bg-red-900/30">
                                <p className="text-sm text-red-800 dark:text-red-400">
                                    {errors.roles}
                                </p>
                            </div>
                        )}

                        <div className="space-y-4">
                            {allRoles.map((role) => (
                                <div
                                    key={role.id}
                                    className="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <div className="flex h-5 items-center">
                                        <input
                                            type="checkbox"
                                            id={`role-${role.id}`}
                                            checked={data.roles.includes(role.id)}
                                            onChange={() => handleRoleToggle(role.id)}
                                            className="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800"
                                        />
                                    </div>
                                    <div className="ml-3 flex-1">
                                        <label
                                            htmlFor={`role-${role.id}`}
                                            className="flex cursor-pointer items-center gap-2"
                                        >
                                            <StatusBadge status={role.name} />
                                        </label>
                                        {role.description && (
                                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {role.description}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                {data.roles.length === 0 ? (
                                    <span className="font-medium text-red-600 dark:text-red-400">
                                        Pilih minimal satu role
                                    </span>
                                ) : (
                                    <>
                                        <span className="font-medium">
                                            {data.roles.length}
                                        </span>{' '}
                                        role dipilih
                                    </>
                                )}
                            </p>
                            <div className="flex gap-3">
                                <Button variant="outline" asChild className="gap-2 min-w-[180px]">
                                    <Link href="/users">
                                        <X className="h-5 w-5" />
                                        Batal
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing || data.roles.length === 0}
                                    className="gap-2 min-w-[200px]"
                                >
                                    {processing ? (
                                        <>
                                            <Loader2 className="h-5 w-5 animate-spin" />
                                            Menyimpan...
                                        </>
                                    ) : (
                                        <>
                                            <Save className="h-5 w-5" />
                                            Simpan Perubahan
                                        </>
                                    )}
                                </Button>
                            </div>
                        </div>
                    </ContentCard>
                </form>
            </div>
        </AppLayout>
    );
}
