import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

interface FilterPanelProps {
    children: ReactNode;
    footer?: ReactNode;
    className?: string;
    fieldsClassName?: string;
}

export function FilterPanel({
    children,
    footer,
    className,
    fieldsClassName,
}: FilterPanelProps) {
    return (
        <div className={cn('rounded-xl border border-border bg-card p-4 sm:p-5', className)}>
            <div
                className={cn(
                    'grid items-end gap-3 md:grid-cols-2 xl:grid-cols-3',
                    fieldsClassName,
                )}
            >
                {children}
            </div>
            {footer !== undefined && (
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-3 text-sm text-muted-foreground">
                    {footer}
                </div>
            )}
        </div>
    );
}

interface FilterFieldProps {
    label: ReactNode;
    children: ReactNode;
    className?: string;
}

export function FilterField({ label, children, className }: FilterFieldProps) {
    return (
        <div className={cn('min-w-0 space-y-1.5', className)}>
            <div className="text-xs font-semibold text-foreground/80">
                {label}
            </div>
            {children}
        </div>
    );
}
