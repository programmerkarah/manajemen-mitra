import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { YearSwitcher } from '@/components/year-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    Users,
    Package,
    FileText,
    ClipboardList,
    BarChart3,
} from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const activeRole = auth.activeRole;

    // Helper function to check if active role matches
    const hasActiveRole = (roleName: string): boolean => {
        return activeRole?.name === roleName;
    };

    // Build menu items based on active role
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    // Admin can see all menus
    if (hasActiveRole('admin')) {
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    {
                        title: 'Manajemen Petugas',
                        href: '/petugas',
                    },
                    {
                        title: 'Alokasi Petugas',
                        href: '/alokasi',
                    },
                ],
            },
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    {
                        title: 'Kegiatan',
                        href: '/kegiatan',
                    },
                    {
                        title: 'SBML',
                        href: '/sbml',
                    },
                ],
            },
            {
                title: 'Rekap Honor Petugas',
                href: '/sbml-report',
                icon: BarChart3,
            },
            {
                title: 'SK KPA',
                href: '/sk-kpa',
                icon: FileText,
            },
            {
                title: 'SPK',
                href: '/spk',
                icon: ClipboardList,
            },
            {
                title: 'BAST',
                href: '/bast',
                icon: FileText,
            },
            {
                title: 'Manajemen User',
                href: '/users',
                icon: Users,
            }
        );
    } else if (hasActiveRole('operator')) {
        // Operator: Rate Honor, Alokasi Petugas, Tambah Kegiatan, Tambah SBML
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    {
                        title: 'Alokasi Petugas',
                        href: '/alokasi',
                    },
                    {
                        title: 'Rekap Honor Petugas',
                        href: '/sbml-report',
                    },
                ],
            },
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    {
                        title: 'Kegiatan',
                        href: '/kegiatan',
                    },
                    {
                        title: 'SBML',
                        href: '/sbml',
                    },
                ],
            }
        );
    } else if (hasActiveRole('ketua_tim')) {
        // Ketua Tim: Tambah Kegiatan, Alokasi Petugas (sesuai kegiatan yang diketuai)
        mainNavItems.push(
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    {
                        title: 'Kegiatan',
                        href: '/kegiatan',
                    },
                ],
            },
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    {
                        title: 'Alokasi Petugas',
                        href: '/alokasi',
                    },
                ],
            }
        );
    } else if (hasActiveRole('approver')) {
        // Approver: Kelola Kegiatan (approve/reject)
        mainNavItems.push(
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    {
                        title: 'Kegiatan',
                        href: '/kegiatan',
                    },
                ],
            }
        );
    } else if (hasActiveRole('pj')) {
        // Penanggung Jawab: Lihat semuanya (read-only)
        mainNavItems.push(
            {
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    {
                        title: 'Manajemen Petugas',
                        href: '/petugas',
                    },
                    {
                        title: 'Alokasi Petugas',
                        href: '/alokasi',
                    },
                    {
                        title: 'Rekap Honor Petugas',
                        href: '/sbml-report',
                    },
                ],
            },
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [
                    {
                        title: 'Kegiatan',
                        href: '/kegiatan',
                    },
                    {
                        title: 'SBML',
                        href: '/sbml',
                    },
                ],
            },
            {
                title: 'SK KPA',
                href: '/sk-kpa',
                icon: FileText,
            },
            {
                title: 'SPK',
                href: '/spk',
                icon: ClipboardList,
            },
            {
                title: 'BAST',
                href: '/bast',
                icon: FileText,
            }
        );
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
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

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <YearSwitcher />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
