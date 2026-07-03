interface PageHeaderProps {
    title: string;
    description?: string;
    children?: React.ReactNode;
}

export function PageHeader({ title, description, children }: PageHeaderProps) {
    return (
        <div className="relative overflow-hidden rounded-3xl border border-white/30 bg-gradient-to-br from-white/70 via-white/45 to-slate-50/60 p-5 shadow-[0_24px_70px_-30px_rgba(15,23,42,0.45)] backdrop-blur-2xl sm:p-6 dark:border-neutral-700/40 dark:from-neutral-900/85 dark:via-neutral-900/70 dark:to-neutral-800/60">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.12),transparent_38%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.08),transparent_34%)] dark:bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.14),transparent_38%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.08),transparent_34%)]" />
            <div className="relative flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0 flex-1">
                    <h1 className="text-2xl font-semibold tracking-tight break-words text-neutral-950 sm:text-3xl dark:text-white">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-2 max-w-3xl text-sm leading-6 break-words text-neutral-600 dark:text-neutral-300">
                            {description}
                        </p>
                    )}
                </div>
                {children && (
                    <div className="flex w-full flex-col items-stretch gap-2 lg:w-auto lg:flex-row lg:flex-wrap lg:items-center lg:justify-end">
                        {children}
                    </div>
                )}
            </div>
        </div>
    );
}
