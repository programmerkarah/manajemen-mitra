import { cn } from '@/lib/utils';

interface ContentCardProps {
    children: React.ReactNode;
    className?: string;
    padding?: 'none' | 'sm' | 'md' | 'lg';
}

export function ContentCard({ children, className, padding = 'md' }: ContentCardProps) {
    const paddingClasses = {
        none: '',
        sm: 'p-4',
        md: 'p-6',
        lg: 'p-8',
    };

    return (
        <div
            className={cn(
                'rounded-xl border border-neutral-200/70 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900',
                paddingClasses[padding],
                className
            )}
        >
            {children}
        </div>
    );
}
