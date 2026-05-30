import { cn } from '@/lib/utils';

interface FrameSampelTahapanSelectProps {
    value: 'listing' | 'pencacahan';
    onValueChange: (value: 'listing' | 'pencacahan') => void;
    allowListing: boolean;
    className?: string;
}

export function FrameSampelTahapanSelect({
    value,
    onValueChange,
    allowListing,
    className,
}: FrameSampelTahapanSelectProps) {
    const tabs = allowListing
        ? [
              { value: 'listing' as const, label: 'Listing' },
              { value: 'pencacahan' as const, label: 'Pencacahan' },
          ]
        : [{ value: 'pencacahan' as const, label: 'Pencacahan' }];

    return (
        <div
            className={cn(
                'inline-flex w-full rounded-xl border border-neutral-200 bg-neutral-100/80 p-1 dark:border-neutral-700 dark:bg-neutral-800/80',
                className,
            )}
        >
            {tabs.map((tab) => (
                <button
                    key={tab.value}
                    type="button"
                    onClick={() => onValueChange(tab.value)}
                    className={cn(
                        'flex-1 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        value === tab.value
                            ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-white'
                            : 'text-neutral-600 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white',
                    )}
                    aria-pressed={value === tab.value}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}