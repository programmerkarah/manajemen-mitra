import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenuButton } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { AlertCircle, Calendar1 } from 'lucide-react';

export function YearSwitcher() {
    const { activeYear, availableYears, hasAvailableYears } =
        usePage<SharedData>().props;

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
            },
        );
    };

    // If no available years (no SBML), show a warning instead of dropdown
    if (!hasAvailableYears) {
        return (
            <Link href="/sbml">
                <SidebarMenuButton
                    size="lg"
                    className="hover:bg-orange-100 data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground dark:hover:bg-orange-900/20"
                >
                    <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-orange-500 text-sidebar-primary-foreground dark:bg-orange-600">
                        <AlertCircle className="size-5 text-white dark:text-white" />
                    </div>
                    <div className="grid flex-1 text-left text-sm leading-tight">
                        <span className="truncate font-semibold text-orange-700 dark:text-orange-400">
                            Belum ada SBML
                        </span>
                        <span className="truncate text-xs text-orange-600 dark:text-orange-500">
                            Klik untuk membuat
                        </span>
                    </div>
                </SidebarMenuButton>
            </Link>
        );
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <SidebarMenuButton
                    size="lg"
                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                >
                    <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-gray-400 text-sidebar-primary-foreground dark:bg-slate-500">
                        <Calendar1 className="size-5 text-white dark:text-white" />
                    </div>
                    <div className="grid flex-1 cursor-pointer text-left text-sm leading-tight">
                        <span className="truncate font-semibold">
                            Tahun Aktif
                        </span>
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
                        className={`cursor-pointer ${year === activeYear ? 'bg-accent' : ''}`}
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
