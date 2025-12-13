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
    const user = auth.user;

    // Helper function to check if user has role
    const hasRole = (roleName: string): boolean => {
        return user.roles?.some((role: any) => role.name === roleName) || false;
    };

    // Build menu items based on user roles
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    // Admin can see all menus
    if (hasRole('admin')) {
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
                title: 'Rate Honor',
                href: '/rate-honor',
                icon: DollarSign,
            },
            {
                title: 'Satuan',
                href: '/satuan',
                icon: Package,
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
    } else {
        // PJ can see Kegiatan and Alokasi
        if (hasRole('pj')) {
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
        }

        // Operator and Approver can see Alokasi
        if (hasRole('operator') || hasRole('approver')) {
            mainNavItems.push({
                title: 'Alokasi Mitra',
                href: '/alokasi',
                icon: UserCheck,
            });
        }
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
