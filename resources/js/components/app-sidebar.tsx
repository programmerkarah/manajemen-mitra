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
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    ClipboardList,
    FileText,
    LayoutGrid,
    Package,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const activeRole = auth.activeRole;

    // Helper function to check if active role matches
    const hasActiveRole = (roleName: string): boolean =>
        activeRole?.name === roleName;

    // Build menu items based on active role
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (hasActiveRole('admin')) {
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    { title: 'Manajemen Petugas', href: '/petugas' },
                    { title: 'Alokasi Petugas', href: '/alokasi' },
                    {
                        title: 'Rekap Honor Petugas',
                        href: '/rekap-honor',
                    },
                ],
            },
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    { title: 'Kegiatan', href: '/kegiatan' },
                    { title: 'SBML', href: '/sbml' },
                    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
                ],
            },
            { title: 'SK KPA', href: '/sk-kpa', icon: FileText },
            { title: 'SPK', href: '/spk', icon: ClipboardList },
            { title: 'BAST', href: '/bast', icon: FileText },
            { title: 'Manajemen User', href: '/users', icon: Users },
        );
    } else if (hasActiveRole('operator')) {
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    { title: 'Alokasi Petugas', href: '/alokasi' },
                    { title: 'Rekap Honor Petugas', href: '/rekap-honor' },
                ],
            },
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    { title: 'Kegiatan', href: '/kegiatan' },
                    { title: 'SBML', href: '/sbml' },
                    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
                ],
            },
            { title: 'SK KPA', href: '/sk-kpa', icon: FileText },
            { title: 'SPK', href: '/spk', icon: ClipboardList },
            { title: 'BAST', href: '/bast', icon: FileText },
        );
    } else if (hasActiveRole('ketua_tim')) {
        mainNavItems.push(
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [{ title: 'Kegiatan', href: '/kegiatan' }],
            },
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [{ title: 'Alokasi Petugas', href: '/alokasi' }],
            },
            { title: 'SK KPA', href: '/sk-kpa', icon: FileText },
            { title: 'SPK', href: '/spk', icon: ClipboardList },
            { title: 'BAST', href: '/bast', icon: FileText },
        );
    } else if (hasActiveRole('approver')) {
        mainNavItems.push({
            title: 'Master',
            href: '#',
            icon: Package,
            items: [{ title: 'Kegiatan', href: '/kegiatan' }],
        });
    } else if (hasActiveRole('pj')) {
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    { title: 'Manajemen Petugas', href: '/petugas' },
                    { title: 'Alokasi Petugas', href: '/alokasi' },
                    { title: 'Rekap Honor Petugas', href: '/rekap-honor' },
                ],
            },
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    { title: 'Kegiatan', href: '/kegiatan' },
                    { title: 'SBML', href: '/sbml' },
                    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
                ],
            },
            { title: 'SK KPA', href: '/sk-kpa', icon: FileText },
            { title: 'SPK', href: '/spk', icon: ClipboardList },
            { title: 'BAST', href: '/bast', icon: FileText },
        );
    }

    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="border-r border-neutral-200 bg-white shadow-lg transition-all duration-300 dark:border-neutral-800 dark:bg-neutral-900"
        >
            <SidebarHeader className="flex h-20 items-center justify-center border-b border-neutral-200 dark:border-neutral-800">
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

            <SidebarFooter className="flex flex-col gap-2 border-t border-neutral-200 p-3 dark:border-neutral-800">
                <YearSwitcher />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
