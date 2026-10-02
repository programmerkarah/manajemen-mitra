import DropdownMenu from 'lucide-react/dist/esm/icons/dropdown-menu';
import DropdownMenuContent from 'lucide-react/dist/esm/icons/dropdown-menu-content';
import DropdownMenuTrigger from 'lucide-react/dist/esm/icons/dropdown-menu-trigger';
import } from '@/components/ui/dropdown-menu';
import {
    SidebarMenu from 'lucide-react/dist/esm/icons/} from '@/components/ui/dropdown-menu';
import {
    sidebar-menu';
import SidebarMenuButton from 'lucide-react/dist/esm/icons/sidebar-menu-button';
import SidebarMenuItem from 'lucide-react/dist/esm/icons/sidebar-menu-item';
import useSidebar from 'lucide-react/dist/esm/icons/use-sidebar';
import } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ChevronsUpDown from 'lucide-react/dist/esm/icons/} from '@/components/ui/sidebar';
import { user-info } from '@/components/user-info';
import { user-menu-content } from '@/components/user-menu-content';
import { use-is-mobile } from '@/hooks/use-mobile';
import { type shared-data } from '@/types';
import { use-page } from '@inertiajs/react';
import { chevrons-up-down';

export function NavUser() {
    const { auth } = usePage<SharedData>().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group cursor-pointer text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent"
                            data-test="sidebar-menu-button"
                        >
                            <UserInfo user={auth.user} />
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 cursor-pointer rounded-lg"
                        align="end"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'left'
                                  : 'bottom'
                        }
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
