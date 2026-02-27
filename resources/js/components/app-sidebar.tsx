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
    Airplay,
    ClipboardList,
    File,
    FileText,
    Gem,
    LayoutGrid,
    Package,
    Scale,
    Signature,
    Smartphone,
    Users,
    Wrench,
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
                title: 'Master',
                href: '#',
                icon: Wrench,
                items: [
                    { title: 'Kegiatan', href: '/kegiatan' },
                    { title: 'SBML', href: '/sbml' },
                    { title: 'Penandatangan', href: '/penandatangan' },
                    { title: 'DIPA', href: '/dipa' },
                    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
                ],
            },
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    { title: 'Manajemen Petugas', href: '/petugas' },
                    { title: 'Alokasi Petugas', href: '/alokasi' },
                    {
                        title: 'Pengajuan Pulsa',
                        href: '/pengajuan-pulsa',
                        icon: Smartphone,
                    },
                ],
            },

            {
                title: 'Dokumen Administrasi',
                href: '#',
                icon: FileText,
                items: [
                    { title: 'SK KPA', href: '/sk-kpa', icon: FileText },
                    {
                        title: 'Perjanjian Kerja',
                        href: '/spk',
                        icon: ClipboardList,
                    },
                    { title: 'BAST', href: '/bast', icon: FileText },
                ],
            },
            {
                title: 'Monitoring',
                href: '#',
                icon: Airplay,
                items: [
                    {
                        title: 'Rekap Honor Petugas',
                        href: '/rekap-honor',
                        icon: FileText,
                    },
                    {
                        title: 'Rekap Pengadaan Pulsa',
                        href: '/monitoring-pulsa',
                        icon: Smartphone,
                    },
                ],
            },
            { title: 'Manajemen User', href: '/users', icon: Users },
            {
                title: 'Pengaturan Sistem',
                href: '/admin/dashboard',
                icon: LayoutGrid,
            },
        );
    } else if (hasActiveRole('operator')) {
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    { title: 'Alokasi Petugas', href: '/alokasi', icon: Users },
                    {
                        title: 'Pengajuan Pulsa',
                        href: '/pengajuan-pulsa',
                        icon: Smartphone,
                    },
                ],
            },
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    { title: 'Kegiatan', href: '/kegiatan', icon: FileText },
                    { title: 'SBML', href: '/sbml', icon: Gem },
                    {
                        title: 'Penandatangan',
                        href: '/penandatangan',
                        icon: Signature,
                    },
                    { title: 'DIPA', href: '/dipa', icon: File },
                    {
                        title: 'Dasar Hukum SK',
                        href: '/dasar-hukum',
                        icon: Scale,
                    },
                ],
            },
            {
                title: 'Dokumen Administrasi',
                href: '#',
                icon: FileText,
                items: [
                    { title: 'SK KPA', href: '/sk-kpa', icon: FileText },
                    {
                        title: 'Perjanjian Kerja',
                        href: '/spk',
                        icon: ClipboardList,
                    },
                    { title: 'BAST', href: '/bast', icon: FileText },
                ],
            },
            {
                title: 'Monitoring',
                href: '#',
                icon: Airplay,
                items: [
                    {
                        title: 'Rekap Honor Petugas',
                        href: '/rekap-honor',
                        icon: FileText,
                    },
                    {
                        title: 'Rekap Pengadaan Pulsa',
                        href: '/monitoring-pulsa',
                        icon: Smartphone,
                    },
                ],
            },
        );
    } else if (hasActiveRole('ketua_tim')) {
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    { title: 'Alokasi Petugas', href: '/alokasi' },
                    {
                        title: 'Pengajuan Pulsa',
                        href: '/pengajuan-pulsa',
                        icon: Smartphone,
                    },
                ],
            },
            {
                title: 'Administrasi',
                href: '#',
                icon: FileText,
                items: [
                    { title: 'Kegiatan', href: '/kegiatan' },
                    { title: 'SK KPA', href: '/sk-kpa' },
                    { title: 'Perjanjian Kerja', href: '/spk' },
                    { title: 'BAST', href: '/bast' },
                ],
            },
            {
                title: 'Monitoring',
                href: '#',
                icon: Airplay,
                items: [
                    { title: 'Rekap Honor Petugas', href: '/rekap-honor' },
                    {
                        title: 'Rekap Pengadaan Pulsa',
                        href: '/monitoring-pulsa',
                        icon: Smartphone,
                    },
                ],
            },
        );
    } else if (hasActiveRole('approver')) {
        mainNavItems.push(
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [{ title: 'Kegiatan', href: '/kegiatan' }],
            },
            { title: 'Perjanjian Kerja', href: '/spk', icon: ClipboardList },
        );
    } else if (hasActiveRole('pj')) {
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    { title: 'Manajemen Petugas', href: '/petugas' },
                    { title: 'Alokasi Petugas', href: '/alokasi' },
                ],
            },
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    { title: 'Kegiatan', href: '/kegiatan' },
                    { title: 'SBML', href: '/sbml' },
                    { title: 'Penandatangan', href: '/penandatangan' },
                    { title: 'DIPA', href: '/dipa' },
                    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
                ],
            },
            {
                title: 'Dokumen Administrasi',
                href: '#',
                icon: FileText,
                items: [
                    { title: 'SK KPA', href: '/sk-kpa', icon: FileText },
                    {
                        title: 'Perjanjian Kerja',
                        href: '/spk',
                        icon: ClipboardList,
                    },
                    { title: 'BAST', href: '/bast', icon: FileText },
                ],
            },
            {
                title: 'Monitoring',
                href: '#',
                icon: Airplay,
                items: [
                    { title: 'Rekap Honor Petugas', href: '/rekap-honor' },
                    {
                        title: 'Pengajuan Pulsa',
                        href: '/pengajuan-pulsa',
                        icon: Smartphone,
                    },
                ],
            },
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
