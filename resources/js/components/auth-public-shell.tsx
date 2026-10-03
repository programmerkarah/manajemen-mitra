import CheckCircle2 from 'lucide-react/icons/check-circle2';
import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { ThemeToggleButton } from '@/components/theme-toggle-button';
import { Link } from '@inertiajs/react';

import { type ReactNode } from 'react';

interface AuthPublicShellProps {
    children: ReactNode;
    title: string;
    description: string;
    asideTitle?: string;
    asideDescription?: string;
    headerAction?: ReactNode;
    compact?: boolean;
}

export function AuthPublicShell({
    children,
    title,
    description,
    asideTitle = 'Kelola pekerjaan statistik dalam satu ruang kerja.',
    asideDescription = 'SIMANTIK menyatukan kegiatan, petugas, dokumen, honor, dan monitoring dengan alur yang konsisten.',
    headerAction,
    compact = false,
}: AuthPublicShellProps) {
    return (
        <div className="flex min-h-svh flex-col bg-background text-foreground">
            <header className="border-b border-border bg-card/95 backdrop-blur">
                <div className="mx-auto flex h-16 w-full max-w-[1440px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <Link href="/" prefetch className="flex min-w-0 items-center">
                        <AppLogo />
                    </Link>
                    <div className="flex shrink-0 items-center gap-2">
                        {headerAction && (
                            <div className="flex items-center">
                                {headerAction}
                            </div>
                        )}
                        {headerAction && (
                            <span
                                className="hidden h-5 w-px bg-border sm:block"
                                aria-hidden="true"
                            />
                        )}
                        <ThemeToggleButton />
                    </div>
                </div>
            </header>

            <main className="mx-auto grid w-full max-w-[1440px] flex-1 lg:grid-cols-[0.9fr_1.1fr]">
                <aside className="hidden border-r border-border bg-muted/35 px-8 py-10 lg:flex lg:items-center xl:px-12">
                    <div className="max-w-lg">
                        <div className="mb-6 flex size-14 items-center justify-center rounded-xl border border-border bg-card shadow-sm">
                            <AppLogoIcon className="size-10" />
                        </div>
                        <p className="text-xs font-semibold tracking-[0.16em] text-primary uppercase">
                            SIMANTIK
                        </p>
                        <h1 className="mt-3 text-3xl leading-tight font-semibold tracking-[-0.03em] xl:text-4xl">
                            {asideTitle}
                        </h1>
                        <p className="mt-4 text-sm leading-6 text-muted-foreground xl:text-base xl:leading-7">
                            {asideDescription}
                        </p>

                        <div className="mt-7 grid gap-2.5">
                            {[
                                'Akses sesuai peran pengguna',
                                'Data dan dokumen terpusat',
                                'Tampilan konsisten di seluruh aplikasi',
                            ].map((item) => (
                                <div
                                    key={item}
                                    className="flex items-center gap-2.5 text-sm text-muted-foreground"
                                >
                                    <span className="flex size-7 items-center justify-center rounded-lg bg-[var(--pastel-green)]/35 text-foreground">
                                        <CheckCircle2 className="size-4" />
                                    </span>
                                    <span>{item}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </aside>

                <section className="flex items-center justify-center px-4 py-7 sm:px-6 sm:py-10 lg:px-10 xl:px-14">
                    <div
                        className={
                            compact ? 'w-full max-w-md' : 'w-full max-w-xl'
                        }
                    >
                        <div className="mb-5 sm:mb-6">
                            <h2 className="text-2xl font-semibold tracking-[-0.025em] sm:text-3xl">
                                {title}
                            </h2>
                            <p className="mt-1.5 text-sm leading-6 text-muted-foreground">
                                {description}
                            </p>
                        </div>

                        <div className="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5 md:p-6">
                            {children}
                        </div>

                        <p className="mt-5 text-center text-xs text-muted-foreground">
                            © {new Date().getFullYear()} BPS Kota Sawahlunto
                        </p>
                    </div>
                </section>
            </main>
        </div>
    );
}
