import { Breadcrumbs } from '@/components/breadcrumbs';
import { ThemeToggleButton } from '@/components/theme-toggle-button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useSidebarBreadcrumbs } from '@/hooks/useSidebarBreadcrumbs';
import {
    type BreadcrumbItem as BreadcrumbItemType,
    type SharedData,
} from '@/types';
import { usePage } from '@inertiajs/react';
import { lazy, Suspense } from 'react';

const RoleSwitcher = lazy(() => import('@/components/role-switcher'));
const ViewAsUserSwitcher = lazy(
    () => import('@/components/view-as-user-switcher'),
);

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const augmentedBreadcrumbs = useSidebarBreadcrumbs(breadcrumbs);
    const { auth } = usePage<SharedData>().props;
    const canSwitchRole = (auth.userRoles?.length ?? 0) > 1;
    const canViewAsUser = Boolean(auth.canViewAsUser);

    return (
        <header className="flex h-16 items-center gap-1.5 overflow-hidden border-b border-sidebar-border/50 px-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:px-4 md:px-4">
            <div className="flex min-w-0 flex-1 items-center gap-1.5 overflow-hidden">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={augmentedBreadcrumbs} />
            </div>
            <div className="flex shrink-0 items-center gap-1.5 overflow-hidden">
                <ThemeToggleButton />
                <Suspense fallback={null}>
                    {canViewAsUser && <ViewAsUserSwitcher />}
                    {canSwitchRole && <RoleSwitcher />}
                </Suspense>
            </div>
        </header>
    );
}
