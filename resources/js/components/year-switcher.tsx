import { router, usePage } from '@inertiajs/react';
import { Calendar1 } from 'lucide-react';
import { type SharedData } from '@/types';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenuButton } from '@/components/ui/sidebar';

export function YearSwitcher() {
    const { activeYear, availableYears } = usePage<SharedData>().props;

    const handleYearSwitch = (year: number) => {
        if (year === activeYear) return;

        router.post(
            '/switch-year',
            { year },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Reload the current page to reflect year-specific data
                    router.reload();
                },
            }
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <SidebarMenuButton
                    size="lg"
                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                >
                    <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-gray-400 dark:bg-slate-500 text-sidebar-primary-foreground">
                        <Calendar1 className="size-5 text-white dark:text-white" />
                    </div>
                    <div className="grid flex-1 text-left text-sm leading-tight">
                        <span className="truncate font-semibold">Tahun Aktif</span>
                        <span className="truncate text-xs">{activeYear}</span>
                    </div>
                </SidebarMenuButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                align="start"
                side="bottom"
                sideOffset={4}
            >
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Pilih Tahun
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {availableYears.map((year) => (
                    <DropdownMenuItem
                        key={year}
                        onClick={() => handleYearSwitch(year)}
                        className={year === activeYear ? 'bg-accent' : ''}
                    >
                        <div className="flex items-center gap-2">
                            {year === activeYear && (
                                <div className="size-2 rounded-full bg-green-500" />
                            )}
                            <span>{year}</span>
                            {year === new Date().getFullYear() && (
                                <span className="ml-auto text-xs text-muted-foreground">
                                    (Sekarang)
                                </span>
                            )}
                        </div>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
