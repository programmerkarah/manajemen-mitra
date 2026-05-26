import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Monitor, Moon, Sun } from 'lucide-react';

const themeConfig = {
    light: { icon: Sun, label: 'Light' },
    dark: { icon: Moon, label: 'Dark' },
    system: { icon: Monitor, label: 'System' },
};

export function ThemeToggleButton() {
    const { appearance, updateAppearance } = useAppearance();
    const CurrentIcon = themeConfig[appearance].icon;

    return (
        <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-9 w-9 rounded-full px-0 sm:w-auto sm:gap-2 sm:px-3"
                    aria-label="Toggle theme"
                >
                    <CurrentIcon className="size-4" />
                    <span className="hidden text-xs font-medium sm:inline">
                        Tema
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-40">
                {(
                    Object.entries(themeConfig) as [
                        Appearance,
                        typeof themeConfig.light,
                    ][]
                ).map(([value, { icon: Icon, label }]) => (
                    <DropdownMenuItem
                        key={value}
                        onClick={() => updateAppearance(value)}
                        className={cn(
                            'cursor-pointer',
                            appearance === value &&
                                'bg-gray-100 dark:bg-zinc-800',
                        )}
                    >
                        <Icon className="mr-2 size-4" />
                        <span>{label}</span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
