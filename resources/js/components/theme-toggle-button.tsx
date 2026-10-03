import Check from 'lucide-react/icons/check';
import Monitor from 'lucide-react/icons/monitor';
import Moon from 'lucide-react/icons/moon';
import Sun from 'lucide-react/icons/sun';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Appearance, useAppearance } from '@/hooks/use-appearance';

const themeConfig = {
    light: { icon: Sun, label: 'Light' },
    dark: { icon: Moon, label: 'Dark' },
    system: { icon: Monitor, label: 'System' },
} satisfies Record<Appearance, { icon: typeof Sun; label: string }>;

export function ThemeToggleButton() {
    const { appearance, updateAppearance } = useAppearance();
    const CurrentIcon = themeConfig[appearance].icon;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className="header-control flex w-9 cursor-pointer items-center justify-center gap-2 px-0 select-none sm:w-auto sm:px-3"
                    aria-label="Pilih tema"
                >
                    <CurrentIcon className="size-4" />
                    <span className="hidden text-xs font-medium sm:inline">
                        Tema
                    </span>
                </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                sideOffset={8}
                className="z-[120] w-40"
                aria-label="Tema aplikasi"
            >
                {(
                    Object.entries(themeConfig) as [
                        Appearance,
                        (typeof themeConfig)[Appearance],
                    ][]
                ).map(([value, { icon: Icon, label }]) => (
                    <DropdownMenuItem
                        key={value}
                        onSelect={() => updateAppearance(value)}
                        className="cursor-pointer"
                    >
                        <Icon className="size-4" />
                        <span className="flex-1">{label}</span>
                        {appearance === value && (
                            <Check className="size-4 text-primary" />
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
