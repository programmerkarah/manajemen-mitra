import { Head, Link } from '@inertiajs/react';

import AppLogo from '@/components/app-logo';
import TextLink from '@/components/text-link';
import { UserPlus } from 'lucide-react';

export default function Register() {
    return (
        <>
            <Head title="Daftar" />
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-blue-950">
                {/* Header */}
                <header className="border-b border-neutral-200/50 backdrop-blur-sm dark:border-neutral-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <AppLogo />
                        </Link>
                        <Link
                            href="/login"
                            className="rounded-lg px-4 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                        >
                            Masuk
                        </Link>
                    </div>
                </header>

                {/* Main Content */}
                <main className="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md">
                        <div className="rounded-2xl border border-neutral-200/70 bg-white p-8 shadow-lg dark:border-neutral-800 dark:bg-neutral-900">
                            {/* Icon & Title */}
                            <div className="mb-8 text-center">
                                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <UserPlus className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h2 className="text-2xl font-bold text-neutral-900 dark:text-white">
                                    Daftar Akun
                                </h2>
                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Pendaftaran akun dipusatkan melalui SSO.
                                </p>
                            </div>

                            <div className="space-y-4">
                                <a
                                    href="/register"
                                    className="flex h-11 w-full items-center justify-center rounded-lg bg-blue-600 text-base font-medium text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                >
                                    Lanjutkan Daftar via SSO
                                </a>

                                <div className="text-center text-sm text-neutral-600 dark:text-neutral-400">
                                    Sudah punya akun?{' '}
                                    <TextLink
                                        href="/login"
                                        className="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                    >
                                        Masuk sekarang
                                    </TextLink>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                {/* Footer */}
                <footer className="border-t border-neutral-200/50 py-4 dark:border-neutral-800">
                    <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                            © {new Date().getFullYear()} BPS Kota Sawahlunto
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
