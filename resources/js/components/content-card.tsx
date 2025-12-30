import { cn } from '@/lib/utils';

interface ContentCardProps {
    children: React.ReactNode;
    className?: string;
    padding?: 'none' | 'sm' | 'md' | 'lg';
}

export function ContentCard({
    children,
    className,
    padding = 'md',
}: ContentCardProps) {
    const paddingClasses = {
        none: '',
        sm: 'p-4',
        md: 'p-6',
        lg: 'p-8',
    };

    return (
        <div
            className={cn(
                'rounded-2xl border border-white/20 bg-white/40 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/40',
                paddingClasses[padding],
                className,
            )}
        >
            {children}
        </div>
    );
}
