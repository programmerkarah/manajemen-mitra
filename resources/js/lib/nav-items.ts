import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import {
    Airplay,
    BarChart3,
    ClipboardList,
    Database,
    File,
    FileText,
    Gem,
    Layers,
    LayoutGrid,
    LineChart,
    Package,
    Scale,
    Signature,
    Smartphone,
    Users,
    Wrench,
} from 'lucide-react';

/**
 * Returns the sidebar navigation items for the given active role name.
 * Shared between AppSidebar and useSidebarBreadcrumbs so the nav structure
 * is always in sync.
 */
export function buildNavItems(
    activeRoleName: string | undefined,
    isSeKetuaTim = false,
): NavItem[] {
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (activeRoleName === 'admin') {
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
                    {
                        title: 'Master Sampel',
                        href: '/master-sampel',
                        icon: Layers,
                    },
                    {
                        title: 'Frame Sampel',
                        href: '/frame-sampel',
                        icon: Database,
                        activeWhen: ['/frame-sampel', '/kegiatan/'],
                    },
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
                        items: [
                            {
                                title: 'PK Reguler',
                                href: '/spk?mode=regular',
                            },
                            {
                                title: 'PK Sensus Ekonomi',
                                href: '/spk?mode=sensus-ekonomi',
                            },
                            {
                                title: 'PK Petugas Pengganti',
                                href: '/spk/petugas-pengganti',
                            },
                        ],
                    },
                    {
                        title: 'Berita Acara',
                        href: '/berita-acara',
                        icon: FileText,
                    },
                    { title: 'BAPP SE2026', href: '/bapp', icon: FileText },
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
                    {
                        title: 'Penggunaan Aplikasi',
                        href: '/monlap-pa',
                        icon: BarChart3,
                    },
                    {
                        title: 'Penilaian Mitra Statistik',
                        href: '/monitoring-penilaian-mitra',
                        icon: LineChart,
                    },
                ],
            },
            {
                title: 'Analisis',
                href: '#',
                icon: BarChart3,
                items: [
                    { title: 'Analisis Petugas', href: '/analisis/petugas' },
                    {
                        title: 'Analisis Pegawai',
                        href: '/analisis/petugas-organik',
                    },
                    { title: 'Analisis Pulsa', href: '/analisis/pulsa' },
                    { title: 'Analisis Dokumen', href: '/analisis/dokumen' },
                    { title: 'Analisis Umum', href: '/analisis/umum' },
                ],
            },
            { title: 'Manajemen User', href: '/users', icon: Users },
            {
                title: 'Pengaturan Sistem',
                href: '/admin/dashboard',
                icon: LayoutGrid,
                /** Stay active for all /admin/* sub-pages */
                activeWhen: ['/admin/'],
            },
        );
    } else if (activeRoleName === 'operator') {
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
                    {
                        title: 'Master Sampel',
                        href: '/master-sampel',
                        icon: Layers,
                    },
                    {
                        title: 'Frame Sampel',
                        href: '/frame-sampel',
                        icon: Database,
                        activeWhen: ['/frame-sampel', '/kegiatan/'],
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
                        items: [
                            {
                                title: 'PK Reguler',
                                href: '/spk?mode=regular',
                            },
                            {
                                title: 'PK Sensus Ekonomi',
                                href: '/spk?mode=sensus-ekonomi',
                            },
                            {
                                title: 'PK Petugas Pengganti',
                                href: '/spk/petugas-pengganti',
                            },
                        ],
                    },
                    {
                        title: 'Berita Acara',
                        href: '/berita-acara',
                        icon: FileText,
                    },
                    { title: 'BAPP SE2026', href: '/bapp', icon: FileText },
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
                    {
                        title: 'Penggunaan Aplikasi',
                        href: '/monlap-pa',
                        icon: BarChart3,
                    },
                    {
                        title: 'Penilaian Mitra Statistik',
                        href: '/monitoring-penilaian-mitra',
                        icon: LineChart,
                    },
                ],
            },
            {
                title: 'Analisis',
                href: '#',
                icon: BarChart3,
                items: [
                    { title: 'Analisis Petugas', href: '/analisis/petugas' },
                    {
                        title: 'Analisis Petugas Organik',
                        href: '/analisis/petugas-organik',
                    },
                    { title: 'Analisis Pulsa', href: '/analisis/pulsa' },
                    { title: 'Analisis Dokumen', href: '/analisis/dokumen' },
                    { title: 'Analisis Umum', href: '/analisis/umum' },
                ],
            },
        );
    } else if (activeRoleName === 'ketua_tim') {
        mainNavItems.push(
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
                title: 'Administrasi',
                href: '#',
                icon: FileText,
                items: [
                    { title: 'Kegiatan', href: '/kegiatan' },
                    { title: 'SK KPA', href: '/sk-kpa' },
                    { title: 'Perjanjian Kerja', href: '/spk' },
                    { title: 'Berita Acara', href: '/berita-acara' },
                    ...(isSeKetuaTim
                        ? [{ title: 'BAPP SE2026', href: '/bapp' }]
                        : []),
                    {
                        title: 'Master Sampel',
                        href: '/master-sampel',
                        icon: Layers,
                    },
                    {
                        title: 'Frame Sampel',
                        href: '/frame-sampel',
                        icon: Database,
                        activeWhen: ['/frame-sampel', '/kegiatan/'],
                    },
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
                    {
                        title: 'Penilaian Mitra Statistik',
                        href: '/monitoring-penilaian-mitra',
                        icon: LineChart,
                    },
                ],
            },
        );
    } else if (activeRoleName === 'approver') {
        mainNavItems.push(
            {
                title: 'Master',
                href: '#',
                icon: Package,
                items: [{ title: 'Kegiatan', href: '/kegiatan' }],
            },
            { title: 'Perjanjian Kerja', href: '/spk', icon: ClipboardList },
        );
    } else if (activeRoleName === 'pj') {
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
                    {
                        title: 'Master Sampel',
                        href: '/master-sampel',
                        icon: Layers,
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
                        items: [
                            {
                                title: 'PK Reguler',
                                href: '/spk?mode=regular',
                            },
                            {
                                title: 'PK Sensus Ekonomi',
                                href: '/spk?mode=sensus-ekonomi',
                            },
                            {
                                title: 'PK Petugas Pengganti',
                                href: '/spk/petugas-pengganti',
                            },
                        ],
                    },
                    {
                        title: 'Berita Acara',
                        href: '/berita-acara',
                        icon: FileText,
                    },
                ],
            },
            {
                title: 'Monitoring',
                href: '#',
                icon: Airplay,
                items: [
                    { title: 'Rekap Honor Petugas', href: '/rekap-honor' },
                    {
                        title: 'Penilaian Mitra Statistik',
                        href: '/monitoring-penilaian-mitra',
                        icon: LineChart,
                    },
                ],
            },
            {
                title: 'Analisis',
                href: '#',
                icon: BarChart3,
                items: [
                    { title: 'Analisis Petugas', href: '/analisis/petugas' },
                    {
                        title: 'Analisis Petugas Organik',
                        href: '/analisis/petugas-organik',
                    },
                    { title: 'Analisis Pulsa', href: '/analisis/pulsa' },
                    { title: 'Analisis Dokumen', href: '/analisis/dokumen' },
                    { title: 'Analisis Umum', href: '/analisis/umum' },
                ],
            },
        );
    }

    if (activeRoleName && activeRoleName !== 'guest') {
        const petugasMenu = mainNavItems.find(
            (item) => item.title === 'Petugas',
        );

        if (petugasMenu) {
            petugasMenu.items = petugasMenu.items ?? [];
            const alreadyExists = petugasMenu.items.some(
                (item) => item.href === '/petugas/review',
            );

            if (!alreadyExists) {
                petugasMenu.items.push({
                    title: 'Penilaian Mitra Statistik',
                    href: '/petugas/review',
                });
            }
        } else {
            mainNavItems.push({
                title: 'Petugas',
                href: '#',
                icon: Users,
                items: [
                    {
                        title: 'Penilaian Mitra Statistik',
                        href: '/petugas/review',
                    },
                ],
            });
        }
    }

    return mainNavItems;
}
