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
                'rounded-3xl border border-white/30 bg-white/55 shadow-[0_20px_60px_-30px_rgba(15,23,42,0.4)] backdrop-blur-2xl transition-all duration-200 hover:border-white/40 hover:shadow-[0_24px_70px_-28px_rgba(15,23,42,0.5)] dark:border-neutral-700/40 dark:bg-neutral-900/45 dark:hover:border-neutral-600/60',
                paddingClasses[padding],
                className,
            )}
        >
            {children}
        </div>
    );
}
