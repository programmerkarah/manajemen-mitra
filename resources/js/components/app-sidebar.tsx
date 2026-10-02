import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { YearSwitcher } from '@/components/year-switcher';
import { buildNavItems } from '@/lib/nav-items';
import { dashboard } from '@/routes';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth, isSeKetuaTim } = usePage<SharedData>().props;
    const mainNavItems = buildNavItems(auth.activeRole?.name, isSeKetuaTim);

    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="border-r border-sidebar-border bg-sidebar shadow-sm transition-all duration-300"
        >
            <SidebarHeader className="flex h-18 items-center justify-center border-b border-sidebar-border">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="pt-2">
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter className="flex flex-col gap-2 border-t border-sidebar-border p-3">
                <YearSwitcher />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
