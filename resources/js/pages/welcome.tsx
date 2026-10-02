import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BarChart3, CheckCircle2, ClipboardList, Users } from 'lucide-react';

export default function Welcome({ canRegister = true }: { canRegister?: boolean }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="SIMANTIK" />
            <div className="relative min-h-screen overflow-hidden bg-[#f7f9fc] text-slate-950 dark:bg-slate-950 dark:text-white">
                <div className="pointer-events-none absolute inset-0 overflow-hidden">
                    <div className="absolute -top-40 right-[-10rem] size-[34rem] rounded-full bg-blue-500/10 blur-3xl" />
                    <div className="absolute bottom-[-15rem] left-[-8rem] size-[30rem] rounded-full bg-emerald-400/10 blur-3xl" />
                </div>

                <header className="relative z-10 border-b border-slate-200/70 bg-white/75 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/70">
                    <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">
                        <Link href="/" className="flex items-center">
                            <AppLogo />
                        </Link>
                        <div className="flex items-center gap-2">
                            {auth.user ? (
                                <Link href={dashboard()} className="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950">
                                    Dashboard <ArrowRight className="size-4" />
                                </Link>
                            ) : (
                                <>
                                    <Link href={login()} className="inline-flex h-10 items-center rounded-xl px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white">
                                        Masuk
                                    </Link>
                                    {canRegister && (
                                        <Link href={register()} className="hidden h-10 items-center rounded-xl bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 sm:inline-flex dark:bg-white dark:text-slate-950">
                                            Daftar
                                        </Link>
                                    )}
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main className="relative z-10">
                    <section className="mx-auto grid min-h-[calc(100vh-8.5rem)] max-w-7xl items-center gap-14 px-5 py-16 sm:px-8 lg:grid-cols-[1.05fr_.95fr] lg:py-20">
                        <div className="max-w-3xl">
                            <div className="mb-8 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold tracking-wide text-slate-600 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                <span className="size-2 rounded-full bg-emerald-500" />
                                Sistem Manajemen Tugas & Kegiatan
                            </div>
                            <h1 className="text-5xl leading-[1.03] font-bold tracking-[-0.045em] text-balance sm:text-6xl lg:text-7xl">
                                Administrasi kegiatan statistik,{' '}
                                <span className="text-blue-600 dark:text-blue-400">dalam satu ruang kerja.</span>
                            </h1>
                            <p className="mt-7 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg dark:text-slate-300">
                                SIMANTIK membantu BPS Kota Sawahlunto mengelola petugas, alokasi, dokumen, honor, dan monitoring kegiatan secara terintegrasi.
                            </p>
                            <div className="mt-9 flex flex-wrap gap-3">
                                <Link href={auth.user ? dashboard() : login()} className="inline-flex h-12 items-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-600/15 transition hover:bg-blue-700">
                                    {auth.user ? 'Buka Dashboard' : 'Masuk ke SIMANTIK'} <ArrowRight className="size-4" />
                                </Link>
                                {!auth.user && canRegister && (
                                    <Link href={register()} className="inline-flex h-12 items-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                                        Daftar akun
                                    </Link>
                                )}
                            </div>
                        </div>

                        <div className="relative mx-auto w-full max-w-xl">
                            <div className="absolute inset-10 rounded-[3rem] bg-blue-500/15 blur-3xl" />
                            <div className="relative overflow-hidden rounded-[2rem] border border-white/70 bg-white/85 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur-xl sm:p-8 dark:border-white/10 dark:bg-white/[0.06]">
                                <div className="flex items-center justify-between border-b border-slate-100 pb-6 dark:border-white/10">
                                    <div className="flex items-center gap-4">
                                        <div className="flex size-16 items-center justify-center rounded-2xl bg-slate-50 shadow-inner dark:bg-white/10">
                                            <AppLogoIcon className="size-14" />
                                        </div>
                                        <div>
                                            <p className="text-xs font-semibold tracking-[0.18em] text-slate-400">SIMANTIK</p>
                                            <p className="mt-1 font-semibold">Ruang kerja terintegrasi</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-1.5"><span className="size-2 rounded-full bg-blue-500" /><span className="size-2 rounded-full bg-emerald-500" /><span className="size-2 rounded-full bg-orange-500" /></div>
                                </div>
                                <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                    {[
                                        [Users, 'Petugas', 'Data & penugasan'],
                                        [ClipboardList, 'Kegiatan', 'Rencana & realisasi'],
                                        [CheckCircle2, 'Administrasi', 'Dokumen & approval'],
                                        [BarChart3, 'Monitoring', 'Progres & rekap'],
                                    ].map(([Icon, title, desc]) => {
                                        const FeatureIcon = Icon as typeof Users;
                                        return (
                                            <div key={String(title)} className="rounded-2xl border border-slate-100 bg-slate-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                                                <FeatureIcon className="mb-5 size-5 text-blue-600 dark:text-blue-400" />
                                                <p className="text-sm font-semibold">{String(title)}</p>
                                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{String(desc)}</p>
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="mt-6 flex items-center gap-3 rounded-2xl bg-slate-950 p-4 text-white dark:bg-white dark:text-slate-950">
                                    <span className="flex size-8 items-center justify-center rounded-full bg-emerald-500/20"><CheckCircle2 className="size-4 text-emerald-400 dark:text-emerald-600" /></span>
                                    <div><p className="text-xs font-semibold">Terintegrasi</p><p className="text-[11px] opacity-60">Satu sumber data untuk proses kerja yang konsisten.</p></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="relative z-10 border-t border-slate-200/70 px-5 py-5 text-center text-xs text-slate-500 dark:border-white/10 dark:text-slate-400">
                    © {new Date().getFullYear()} Badan Pusat Statistik Kota Sawahlunto
                </footer>
            </div>
        </>
    );
}
