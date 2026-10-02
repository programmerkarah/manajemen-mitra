import { cn } from '@/lib/utils';

interface ContentCardProps {
    children: React.ReactNode;
    className?: string;
    padding?: 'none' | 'sm' | 'md' | 'lg';
    density?: 'default' | 'compact';
}

export function ContentCard({
    children,
    className,
    padding = 'md',
    density = 'default',
}: ContentCardProps) {
    const paddingClasses = {
        none: '',
        sm: 'p-3 sm:p-4',
        md: 'p-4 sm:p-5 md:p-6',
        lg: 'p-4 sm:p-6 md:p-8',
    };

    return (
        <div
            className={cn(
                'rounded-xl border border-border bg-card text-card-foreground shadow-sm',
                density === 'compact' && 'rounded-lg',
                paddingClasses[padding],
                className,
            )}
        >
            {children}
        </div>
    );
}
