interface PageHeaderProps {
    title: string;
    description?: string;
    children?: React.ReactNode;
}

export function PageHeader({ title, description, children }: PageHeaderProps) {
    return (
        <div className="rounded-xl border border-neutral-200/70 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div className="flex items-start justify-between gap-4">
                <div className="flex-1">
                    <h1 className="text-2xl font-bold text-neutral-900 dark:text-white">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-1.5 text-sm text-neutral-600 dark:text-neutral-400">
                            {description}
                        </p>
                    )}
                </div>
                {children && (
                    <div className="flex flex-wrap items-center gap-2">
                        {children}
                    </div>
                )}
            </div>
        </div>
    );
}
