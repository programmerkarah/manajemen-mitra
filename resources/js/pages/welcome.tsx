import ArrowRight from 'lucide-react/icons/arrow-right';
import BarChart3 from 'lucide-react/icons/bar-chart3';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import ClipboardCheck from 'lucide-react/icons/clipboard-check';
import FileText from 'lucide-react/icons/file-text';
import Users from 'lucide-react/icons/users';
import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { ThemeToggleButton } from '@/components/theme-toggle-button';
import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="SIMANTIK" />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b border-border bg-card/95 backdrop-blur">
                    <div className="mx-auto flex h-18 max-w-[1480px] items-center justify-between px-6 sm:px-10">
                        <Link href="/" className="flex items-center">
                            <AppLogo />
                        </Link>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex h-11 items-center gap-2 rounded-xl bg-primary px-5 text-sm font-semibold text-primary-foreground shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-px hover:bg-primary/90 hover:shadow-md"
                                >
                                    Buka Dashboard
                                    <ArrowRight className="size-4" />
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="inline-flex h-11 items-center rounded-xl px-4 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground dark:hover:text-primary-foreground"
                                    >
                                        Masuk
                                    </Link>
                                    {canRegister && (
                                        <Link
                                            href={register()}
                                            className="hidden h-11 items-center rounded-xl border border-border bg-card px-4 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-muted sm:inline-flex"
                                        >
                                            Daftar
                                        </Link>
                                    )}
                                </>
                            )}
                            <span
                                className="hidden h-5 w-px bg-border sm:block"
                                aria-hidden="true"
                            />
                            <ThemeToggleButton />
                        </nav>
                    </div>
                </header>

                <main className="mx-auto max-w-[1480px] px-6 py-10 sm:px-10 lg:py-14">
                    <section className="grid min-h-[calc(100vh-10rem)] items-center gap-12 lg:grid-cols-[1.05fr_.95fr] xl:gap-20">
                        <div className="max-w-3xl">
                            <div className="mb-7 inline-flex items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300">
                                <span className="size-2 rounded-full bg-emerald-500" />
                                BPS Kota Sawahlunto
                            </div>

                            <h1 className="max-w-3xl text-5xl leading-[1.02] font-semibold tracking-[-0.045em] sm:text-6xl xl:text-[4.9rem]">
                                Pekerjaan kegiatan statistik, lebih tertata.
                            </h1>

                            <p className="mt-7 max-w-2xl text-lg leading-8 text-muted-foreground">
                                SIMANTIK menyatukan pengelolaan kegiatan,
                                petugas, alokasi, dokumen, honor, dan monitoring
                                dalam satu ruang kerja yang konsisten.
                            </p>

                            <div className="mt-9 flex flex-wrap items-center gap-3">
                                <Link
                                    href={auth.user ? dashboard() : login()}
                                    className="inline-flex h-12 items-center gap-2 rounded-xl bg-primary px-6 text-sm font-semibold text-primary-foreground shadow-md shadow-blue-600/20 transition-all hover:-translate-y-px hover:bg-primary/90 hover:shadow-lg"
                                >
                                    {auth.user
                                        ? 'Buka Dashboard'
                                        : 'Masuk ke SIMANTIK'}
                                    <ArrowRight className="size-4" />
                                </Link>

                                {!auth.user && canRegister && (
                                    <Link
                                        href={register()}
                                        className="inline-flex h-12 items-center rounded-xl border border-border bg-card px-6 text-sm font-semibold text-foreground shadow-sm transition-colors hover:bg-muted"
                                    >
                                        Buat akun
                                    </Link>
                                )}
                            </div>

                            <div className="mt-10 flex flex-wrap gap-x-7 gap-y-3 border-t border-border pt-6 text-sm text-muted-foreground">
                                <span className="flex items-center gap-2">
                                    <CheckCircle2 className="size-4 text-emerald-600" />
                                    Data terpusat
                                </span>
                                <span className="flex items-center gap-2">
                                    <CheckCircle2 className="size-4 text-emerald-600" />
                                    Dokumen terintegrasi
                                </span>
                                <span className="flex items-center gap-2">
                                    <CheckCircle2 className="size-4 text-emerald-600" />
                                    Monitoring lebih jelas
                                </span>
                            </div>
                        </div>

                        <div className="relative w-full">
                            <div className="absolute inset-x-8 top-10 bottom-10 rounded-[2.5rem] bg-blue-500/10 blur-3xl" />

                            <div className="relative overflow-hidden rounded-[1.75rem] border border-border bg-card shadow-[0_24px_64px_rgba(15,23,42,0.12)]">
                                <div className="h-1.5 bg-gradient-to-r from-blue-600 via-emerald-500 to-orange-500" />

                                <div className="flex items-center justify-between border-b border-border px-5 py-5 sm:px-7 sm:py-6">
                                    <div className="flex items-center gap-4">
                                        <div className="flex size-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                            <AppLogoIcon className="size-11" />
                                        </div>
                                        <div>
                                            <p className="text-base font-semibold">
                                                Ringkasan SIMANTIK
                                            </p>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                Satu alur untuk pekerjaan
                                                operasional
                                            </p>
                                        </div>
                                    </div>

                                    <span className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        Terintegrasi
                                    </span>
                                </div>

                                <div className="grid gap-4 p-7 sm:grid-cols-2">
                                    {[
                                        {
                                            icon: ClipboardCheck,
                                            title: 'Kegiatan',
                                            text: 'Rencana, periode, dan progres pelaksanaan.',
                                            tone: 'blue',
                                        },
                                        {
                                            icon: Users,
                                            title: 'Petugas',
                                            text: 'Data petugas dan alokasi pekerjaan.',
                                            tone: 'emerald',
                                        },
                                        {
                                            icon: FileText,
                                            title: 'Administrasi',
                                            text: 'PK, BAST, BAPP, dan dokumen pendukung.',
                                            tone: 'orange',
                                        },
                                        {
                                            icon: BarChart3,
                                            title: 'Monitoring',
                                            text: 'Honor, realisasi, dan tindak lanjut pekerjaan.',
                                            tone: 'violet',
                                        },
                                    ].map(
                                        ({ icon: Icon, title, text, tone }) => {
                                            const toneClasses: Record<
                                                string,
                                                string
                                            > = {
                                                blue: 'border-blue-100 bg-blue-50/80 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300',
                                                emerald:
                                                    'border-emerald-100 bg-emerald-50/80 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-300',
                                                orange: 'border-orange-100 bg-orange-50/80 text-orange-700 dark:border-orange-900/50 dark:bg-orange-950/20 dark:text-orange-300',
                                                violet: 'border-violet-100 bg-violet-50/80 text-violet-700 dark:border-violet-900/50 dark:bg-violet-950/20 dark:text-violet-300',
                                            };

                                            return (
                                                <div
                                                    key={title}
                                                    className="rounded-2xl border border-border bg-muted p-4 shadow-sm sm:p-5"
                                                >
                                                    <div
                                                        className={`mb-5 flex size-11 items-center justify-center rounded-xl border ${toneClasses[tone]}`}
                                                    >
                                                        <Icon className="size-5" />
                                                    </div>
                                                    <p className="text-base font-semibold">
                                                        {title}
                                                    </p>
                                                    <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                                        {text}
                                                    </p>
                                                </div>
                                            );
                                        },
                                    )}
                                </div>

                                <div className="border-t border-border bg-muted px-5 py-4 sm:px-7 sm:py-5">
                                    <div className="grid gap-4 sm:grid-cols-3">
                                        {[
                                            ['01', 'Rencanakan'],
                                            ['02', 'Kelola'],
                                            ['03', 'Pantau'],
                                        ].map(([no, label]) => (
                                            <div
                                                key={no}
                                                className="flex items-center gap-3"
                                            >
                                                <span className="flex size-8 items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-primary-foreground dark:bg-white dark:text-slate-950">
                                                    {no}
                                                </span>
                                                <span className="text-sm font-medium text-muted-foreground">
                                                    {label}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border bg-card/80 py-5">
                    <div className="mx-auto flex max-w-[1480px] flex-col gap-1 px-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-10">
                        <span>
                            © {new Date().getFullYear()} Badan Pusat Statistik
                            Kota Sawahlunto
                        </span>
                        <span>
                            SIMANTIK · Sistem Manajemen Tugas &amp; Kegiatan
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
