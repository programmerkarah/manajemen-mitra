import AppLogo from '@/components/app-logo';
import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, CheckCircle2 } from 'lucide-react';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="SIMANTIK" />
            <div className="min-h-screen bg-white text-slate-950 dark:bg-slate-950 dark:text-slate-50">
                <header className="border-b border-slate-200 dark:border-slate-800">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8">
                        <Link href="/" className="flex items-center">
                            <AppLogo />
                        </Link>
                        <nav className="flex items-center gap-1">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex h-9 items-center gap-2 rounded-lg bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950"
                                >
                                    Buka dashboard{' '}
                                    <ArrowRight className="size-4" />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="inline-flex h-9 items-center px-3 text-sm font-medium text-slate-600 hover:text-slate-950 dark:text-slate-300 dark:hover:text-white"
                                    >
                                        Masuk
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="inline-flex h-9 items-center rounded-lg bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950"
                                        >
                                            Daftar
                                        </Link>
                                    )}
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="mx-auto max-w-6xl px-5 sm:px-8">
                    <section className="grid min-h-[calc(100vh-8rem)] items-center gap-12 py-16 lg:grid-cols-[1.2fr_.8fr]">
                        <div className="max-w-2xl">
                            <p className="mb-5 text-sm font-semibold text-blue-600 dark:text-blue-400">
                                BPS Kota Sawahlunto
                            </p>
                            <h1 className="text-4xl leading-[1.08] font-semibold tracking-[-0.035em] sm:text-5xl lg:text-[3.5rem]">
                                Pekerjaan kegiatan statistik, lebih tertata.
                            </h1>
                            <p className="mt-6 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">
                                SIMANTIK menyatukan pengelolaan kegiatan,
                                petugas, alokasi, dokumen, honor, dan monitoring
                                dalam satu alur kerja.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    className="inline-flex h-11 items-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    {auth.user
                                        ? 'Buka dashboard'
                                        : 'Masuk ke SIMANTIK'}{' '}
                                    <ArrowRight className="size-4" />
                                </Link>
                                {!auth.user && canRegister && (
                                    <Link
                                        href={register()}
                                        className="inline-flex h-11 items-center rounded-lg border border-slate-300 px-5 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-900"
                                    >
                                        Buat akun
                                    </Link>
                                )}
                            </div>
                        </div>

                        <div className="border-l border-slate-200 pl-0 lg:pl-10 dark:border-slate-800">
                            <p className="mb-5 text-xs font-semibold tracking-[0.16em] text-slate-400 uppercase">
                                Satu alur kerja
                            </p>
                            <div className="divide-y divide-slate-200 border-y border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                                {[
                                    [
                                        '01',
                                        'Rencanakan kegiatan',
                                        'Siapkan kegiatan dan kebutuhan petugas.',
                                    ],
                                    [
                                        '02',
                                        'Kelola pelaksanaan',
                                        'Atur alokasi, administrasi, dan dokumen.',
                                    ],
                                    [
                                        '03',
                                        'Pantau penyelesaian',
                                        'Lihat progres dan pekerjaan yang perlu ditindaklanjuti.',
                                    ],
                                ].map(([no, title, desc]) => (
                                    <div
                                        key={no}
                                        className="grid grid-cols-[2.5rem_1fr] gap-3 py-5"
                                    >
                                        <span className="text-xs font-semibold text-slate-400">
                                            {no}
                                        </span>
                                        <div>
                                            <p className="text-sm font-semibold">
                                                {title}
                                            </p>
                                            <p className="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                {desc}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-5 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <CheckCircle2 className="size-4 text-emerald-600" />{' '}
                                Data kerja terpusat dan konsisten
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-slate-200 py-5 dark:border-slate-800">
                    <div className="mx-auto max-w-6xl px-5 text-xs text-slate-500 sm:px-8">
                        © {new Date().getFullYear()} Badan Pusat Statistik Kota
                        Sawahlunto
                    </div>
                </footer>
            </div>
        </>
    );
}
