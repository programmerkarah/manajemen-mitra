interface PageHeaderProps {
    title: string;
    description?: string;
    children?: React.ReactNode;
}

export function PageHeader({ title, description, children }: PageHeaderProps) {
    return (
        <div className="overflow-hidden rounded-2xl border border-white/20 bg-white/40 p-4 shadow-2xl backdrop-blur-2xl sm:p-6 dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0 flex-1">
                    <h1 className="text-2xl font-bold break-words text-neutral-900 dark:text-white">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-1.5 max-w-full text-sm break-words text-neutral-600 dark:text-neutral-400">
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
