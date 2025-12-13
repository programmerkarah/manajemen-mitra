import { NavFooter } from '@/components/nav-footer';
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
import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    Folder,
    LayoutGrid,
    Users,
    Briefcase,
    UserCheck,
    DollarSign,
    FileText,
    ClipboardList,
    Package,
    Calculator,
    BarChart3,
} from 'lucide-react';
import AppLogo from './app-logo';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

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
                title: 'Manajemen Mitra',
                href: '/mitra',
                icon: Users,
            },
            {
                title: 'Kegiatan',
                href: '/kegiatan',
                icon: Briefcase,
            },
            {
                title: 'Alokasi Mitra',
                href: '/alokasi',
                icon: UserCheck,
            },
            {
                title: 'SBML',
                href: '/sbml',
                icon: Calculator,
            },
            {
                title: 'Laporan SBML',
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
    } else if (hasActiveRole('pj')) {
        // PJ can see Kegiatan and Alokasi
        mainNavItems.push(
            {
                title: 'Kegiatan',
                href: '/kegiatan',
                icon: Briefcase,
            },
            {
                title: 'Alokasi Mitra',
                href: '/alokasi',
                icon: UserCheck,
            }
        );
    } else if (hasActiveRole('operator') || hasActiveRole('approver')) {
        // Operator and Approver can see Alokasi, Rate Honor Management, and SBML Report
        mainNavItems.push(
            {
                title: 'Alokasi Mitra',
                href: '/alokasi',
                icon: UserCheck,
            },
            {
                title: 'Laporan SBML',
                href: '/sbml-report',
                icon: BarChart3,
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
