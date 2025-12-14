import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Shield, Users, Activity, CheckCircle2 } from 'lucide-react';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Selamat Datang" />
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-blue-950">
                <header className="border-b border-neutral-200/50 backdrop-blur-sm dark:border-neutral-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 dark:bg-blue-500">
                                <Shield className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-neutral-900 dark:text-white">
                                    Manajemen Mitra
                                </h1>
                                <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                    BPS Kota Sawahlunto
                                </p>
                            </div>
                        </div>
                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                >
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="rounded-lg px-4 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                                    >
                                        Masuk
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                        >
                                            Daftar
                                        </Link>
                                    )}
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                    <div className="mx-auto w-full max-w-6xl">
                        <div className="grid gap-8 lg:grid-cols-2 lg:gap-12">
                            <div className="flex flex-col justify-center">
                                <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-2 text-sm font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    <Activity className="h-4 w-4" />
                                    <span>Sistem Informasi Manajemen</span>
                                </div>
                                <h1 className="mb-4 text-4xl font-bold tracking-tight text-neutral-900 sm:text-5xl lg:text-6xl dark:text-white">
                                    Sistem Manajemen{' '}
                                    <span className="text-blue-600 dark:text-blue-400">
                                        Mitra
                                    </span>
                                </h1>
                                <p className="mb-8 text-lg text-neutral-600 dark:text-neutral-400">
                                    Platform terintegrasi untuk mengelola data petugas,
                                    kegiatan, alokasi, dan berita acara serah terima
                                    dengan mudah dan efisien.
                                </p>

                                <div className="flex flex-wrap gap-4">
                                    {auth.user ? (
                                        <Link
                                            href={dashboard()}
                                            className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-base font-medium text-white transition-all hover:bg-blue-700 hover:shadow-lg dark:bg-blue-500 dark:hover:bg-blue-600"
                                        >
                                            Buka Dashboard
                                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                        </Link>
                                    ) : (
                                        <>
                                            <Link
                                                href={login()}
                                                className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-base font-medium text-white transition-all hover:bg-blue-700 hover:shadow-lg dark:bg-blue-500 dark:hover:bg-blue-600"
                                            >
                                                Masuk
                                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                            </Link>
                                            {canRegister && (
                                                <Link
                                                    href={register()}
                                                    className="inline-flex items-center gap-2 rounded-lg border-2 border-neutral-300 bg-white px-6 py-3 text-base font-medium text-neutral-700 transition-all hover:border-neutral-400 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:border-neutral-600 dark:hover:bg-neutral-700"
                                                >
                                                    Daftar Akun
                                                </Link>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="rounded-xl border border-neutral-200 bg-white p-6 transition-all hover:shadow-lg dark:border-neutral-800 dark:bg-neutral-900">
                                    <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                        <Users className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-semibold text-neutral-900 dark:text-white">
                                        Manajemen Petugas
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Kelola data petugas dan alokasi tugas dengan sistem yang terorganisir
                                    </p>
                                </div>

                                <div className="rounded-xl border border-neutral-200 bg-white p-6 transition-all hover:shadow-lg dark:border-neutral-800 dark:bg-neutral-900">
                                    <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                                        <Activity className="h-6 w-6 text-green-600 dark:text-green-400" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-semibold text-neutral-900 dark:text-white">
                                        Tracking Kegiatan
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Monitor dan kelola kegiatan survei secara real-time dan efisien
                                    </p>
                                </div>

                                <div className="rounded-xl border border-neutral-200 bg-white p-6 transition-all hover:shadow-lg dark:border-neutral-800 dark:bg-neutral-900">
                                    <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                        <CheckCircle2 className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-semibold text-neutral-900 dark:text-white">
                                        Sistem Approval
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Workflow approval yang jelas untuk setiap proses dan dokumentasi
                                    </p>
                                </div>

                                <div className="rounded-xl border border-neutral-200 bg-white p-6 transition-all hover:shadow-lg dark:border-neutral-800 dark:bg-neutral-900">
                                    <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                                        <Shield className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-semibold text-neutral-900 dark:text-white">
                                        Keamanan Data
                                    </h3>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Autentikasi berlapis dengan 2FA dan enkripsi data untuk keamanan maksimal
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                <footer className="border-t border-neutral-200/50 py-6 dark:border-neutral-800">
                    <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            © {new Date().getFullYear()} Badan Pusat Statistik Kota Sawahlunto. All rights reserved.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
