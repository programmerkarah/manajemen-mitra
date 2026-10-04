import { ThemeToggleButton } from '@/components/theme-toggle-button';
import AppLogo from '@/components/app-logo';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

interface MaintenanceAccessShellProps {
    title: string;
    description: string;
    eyebrow: string;
    icon: LucideIcon;
    children: ReactNode;
    note?: ReactNode;
}

export function MaintenanceAccessShell({
    title,
    description,
    eyebrow,
    icon: Icon,
    children,
    note,
}: MaintenanceAccessShellProps) {
    return (
        <main className="relative min-h-screen overflow-hidden bg-background px-4 py-8 text-foreground sm:px-6">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_12%_10%,rgba(59,130,246,0.10),transparent_32%),radial-gradient(circle_at_88%_82%,rgba(16,185,129,0.08),transparent_34%)]" />
            <div className="relative mx-auto flex min-h-[calc(100vh-4rem)] max-w-5xl flex-col">
                <header className="flex items-center justify-between gap-4">
                    <div className="flex max-w-[220px] items-center">
                        <AppLogo />
                    </div>
                    <ThemeToggleButton />
                </header>

                <section className="my-auto grid gap-8 py-10 lg:grid-cols-[1fr_440px] lg:items-center">
                    <div className="max-w-xl">
                        <div className="mb-4 inline-flex items-center gap-2 rounded-full border bg-card/80 px-3 py-1.5 text-xs font-semibold text-muted-foreground shadow-sm backdrop-blur">
                            <Icon className="size-4" />
                            {eyebrow}
                        </div>
                        <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                            {title}
                        </h1>
                        <p className="mt-4 max-w-lg text-sm leading-7 text-muted-foreground sm:text-base">
                            {description}
                        </p>
                        {note && (
                            <div className="mt-6 rounded-2xl border bg-card/70 p-4 text-sm leading-6 text-muted-foreground backdrop-blur">
                                {note}
                            </div>
                        )}
                    </div>

                    <div className="rounded-3xl border bg-card/90 p-5 shadow-[0_24px_70px_-35px_rgba(15,23,42,0.5)] backdrop-blur sm:p-6">
                        {children}
                    </div>
                </section>

                <footer className="pb-2 text-center text-xs text-muted-foreground">
                    SIMANTIK · Akses administrasi sistem
                </footer>
            </div>
        </main>
    );
}
