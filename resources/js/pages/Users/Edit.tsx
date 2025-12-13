import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

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

    const getRoleBadgeColor = (roleName: string) => {
        const colors: Record<string, string> = {
            admin: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-300 dark:border-red-700',
            operator:
                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border-blue-300 dark:border-blue-700',
            pj: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-300 dark:border-green-700',
            approver:
                'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400 border-purple-300 dark:border-purple-700',
            guest: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400 border-gray-300 dark:border-gray-600',
        };
        return colors[roleName] || colors.guest;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Role - ${user.name}`} />

            <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Edit Role User
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Kelola role dan hak akses untuk{' '}
                                    <span className="font-medium">{user.name}</span>
                                </p>
                            </div>
                            <Link
                                href="/users"
                                className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                            >
                                Kembali
                            </Link>
                        </div>
                    </div>
                </div>

                {/* User Info */}
                <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div className="p-6">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                            Informasi User
                        </h3>
                        <dl className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Nama
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                    {user.name}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Username
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                    {user.username}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Email
                                </dt>
                                <dd className="mt-1 text-sm text-gray-900 dark:text-white">
                                    {user.email}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {/* Role Form */}
                <form onSubmit={handleSubmit}>
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                Pilih Role
                            </h3>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                User dapat memiliki lebih dari satu role. Pilih semua role yang
                                sesuai.
                            </p>

                            {errors.roles && (
                                <div className="mt-4 rounded-md bg-red-50 p-4 dark:bg-red-900/30">
                                    <p className="text-sm text-red-800 dark:text-red-400">
                                        {errors.roles}
                                    </p>
                                </div>
                            )}

                            <div className="mt-6 space-y-4">
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
                                                className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800"
                                            />
                                        </div>
                                        <div className="ml-3 flex-1">
                                            <label
                                                htmlFor={`role-${role.id}`}
                                                className="flex cursor-pointer items-center gap-2"
                                            >
                                                <span
                                                    className={`inline-flex rounded-full border px-3 py-1 text-sm font-semibold ${getRoleBadgeColor(role.name)}`}
                                                >
                                                    {role.display_name}
                                                </span>
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

                            <div className="mt-6 flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
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
                                    <Link
                                        href="/users"
                                        className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing || data.roles.length === 0}
                                        className="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
                                    >
                                        {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
