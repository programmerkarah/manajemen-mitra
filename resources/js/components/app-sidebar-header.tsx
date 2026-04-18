import { Breadcrumbs } from '@/components/breadcrumbs';
import RoleSwitcher from '@/components/role-switcher';
import { ThemeToggleButton } from '@/components/theme-toggle-button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import ViewAsUserSwitcher from '@/components/view-as-user-switcher';
import { useSidebarBreadcrumbs } from '@/hooks/useSidebarBreadcrumbs';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const augmentedBreadcrumbs = useSidebarBreadcrumbs(breadcrumbs);

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={augmentedBreadcrumbs} />
            </div>
            <div className="flex items-center gap-2">
                <ThemeToggleButton />
                <ViewAsUserSwitcher />
                <RoleSwitcher />
            </div>
        </header>
    );
}
