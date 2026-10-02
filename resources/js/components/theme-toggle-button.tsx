import Check from 'lucide-react/icons/check';
import Monitor from 'lucide-react/icons/monitor';
import Moon from 'lucide-react/icons/moon';
import Sun from 'lucide-react/icons/sun';
import { Appearance, useAppearance } from '@/hooks/use-appearance';

import { useRef } from 'react';

const themeConfig = {
    light: { icon: Sun, label: 'Light' },
    dark: { icon: Moon, label: 'Dark' },
    system: { icon: Monitor, label: 'System' },
} satisfies Record<Appearance, { icon: typeof Sun; label: string }>;

export function ThemeToggleButton() {
    const { appearance, updateAppearance } = useAppearance();
    const CurrentIcon = themeConfig[appearance].icon;
    const detailsRef = useRef<HTMLDetailsElement>(null);

    const selectAppearance = (mode: Appearance) => {
        updateAppearance(mode);
        detailsRef.current?.removeAttribute('open');
    };

    return (
        <details ref={detailsRef} className="relative">
            <summary
                className="header-control flex w-9 cursor-pointer list-none items-center justify-center gap-2 px-0 select-none sm:w-auto sm:px-3 [&::-webkit-details-marker]:hidden"
                aria-label="Pilih tema"
            >
                <CurrentIcon className="size-4" />
                <span className="hidden text-xs font-medium sm:inline">
                    Tema
                </span>
            </summary>

            <div
                className="absolute right-0 z-50 mt-2 w-40 rounded-lg border border-border bg-popover p-1 text-popover-foreground shadow-lg"
                role="menu"
                aria-label="Tema aplikasi"
            >
                {(
                    Object.entries(themeConfig) as [
                        Appearance,
                        (typeof themeConfig)[Appearance],
                    ][]
                ).map(([value, { icon: Icon, label }]) => (
                    <button
                        key={value}
                        type="button"
                        role="menuitemradio"
                        aria-checked={appearance === value}
                        onClick={() => selectAppearance(value)}
                        className="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <Icon className="size-4 text-muted-foreground" />
                        <span className="flex-1">{label}</span>
                        {appearance === value && (
                            <Check className="size-4 text-primary" />
                        )}
                    </button>
                ))}
            </div>
        </details>
    );
}
