import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

type SummaryAccent = 'neutral' | 'blue' | 'green' | 'orange' | 'violet';

interface SummaryCardProps {
    label: ReactNode;
    value: ReactNode;
    meta?: ReactNode;
    icon?: ReactNode;
    accent?: SummaryAccent;
    className?: string;
    onClick?: () => void;
}

const accentClasses: Record<SummaryAccent, string> = {
    neutral: 'bg-muted text-muted-foreground',
    blue: 'bg-[var(--pastel-blue)]/30 text-blue-600 dark:text-blue-300',
    green: 'bg-[var(--pastel-green)]/30 text-emerald-600 dark:text-emerald-300',
    orange: 'bg-[var(--pastel-orange)]/30 text-amber-600 dark:text-amber-300',
    violet: 'bg-violet-500/10 text-violet-600 dark:text-violet-300',
};

export function SummaryCard({
    label,
    value,
    meta,
    icon,
    accent = 'neutral',
    className,
    onClick,
}: SummaryCardProps) {
    const content = (
        <>
            <div className="flex min-w-0 items-start justify-between gap-3">
                <div className="min-w-0">
                    <div className="summary-card__label">{label}</div>
                    <div className="summary-card__value">{value}</div>
                    {meta !== undefined && (
                        <div className="summary-card__meta">{meta}</div>
                    )}
                </div>
                {icon && (
                    <span
                        className={cn(
                            'hidden size-9 shrink-0 items-center justify-center rounded-lg sm:flex',
                            accentClasses[accent],
                        )}
                    >
                        {icon}
                    </span>
                )}
            </div>
        </>
    );

    if (onClick) {
        return (
            <button
                type="button"
                onClick={onClick}
                className={cn(
                    'summary-card w-full text-left transition-colors hover:bg-muted/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                    className,
                )}
            >
                {content}
            </button>
        );
    }

    return <div className={cn('summary-card', className)}>{content}</div>;
}
