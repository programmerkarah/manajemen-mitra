import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { 
    Users, 
    Database, 
    Settings, 
    ActivitySquare,
    UserCheck,
    FolderKanban,
    HardDrive,
    Clock,
    CheckCircle2,
    AlertCircle,
    AlertTriangle,
    ChevronRight
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '#' },
];

interface Stats {
    totalUsers: number;
    totalMitra: number;
    totalKegiatan: number;
    dbSize: number;
}

interface SystemStatus {
    maintenance: boolean;
    status: string;
    label: string;
}

interface LastBackup {
    filename: string;
    size_formatted: string;
    created_at: string;
}

interface RecentLog {
    id: number;
    user_name: string;
    action: string;
    description: string;
    status: 'success' | 'error' | 'warning';
    type: string;
    created_at: string;
}

interface DashboardProps {
    stats: Stats;
    systemStatus: SystemStatus;
    lastBackup: LastBackup | null;
    recentLogs: RecentLog[];
    [key: string]: unknown;
}

export default function AdminDashboard() {
    const { stats, systemStatus, lastBackup, recentLogs } = usePage<DashboardProps>().props;

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'success':
                return <CheckCircle2 className="h-4 w-4 text-green-600 dark:text-green-400" />;
            case 'error':
                return <AlertCircle className="h-4 w-4 text-red-600 dark:text-red-400" />;
            case 'warning':
                return <AlertTriangle className="h-4 w-4 text-yellow-600 dark:text-yellow-400" />;
            default:
                return null;
        }
    };

    const getStatusBadge = (status: string) => {
        const variants = {
            success: 'default',
            error: 'destructive',
            warning: 'secondary',
        } as const;
        
        return variants[status as keyof typeof variants] || 'default';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            
            <div className="space-y-6">
                {/* Header */}
                <PageHeader
                    title="Dashboard Admin"
                    description="Kelola sistem dan monitor aktivitas aplikasi"
                />

                {/* Status System Alert */}
                {systemStatus.maintenance && (
                    <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
                        <div className="flex items-center gap-3">
                            <AlertTriangle className="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                            <div>
                                <h3 className="font-semibold text-yellow-900 dark:text-yellow-100">
                                    Mode Maintenance Aktif
                                </h3>
                                <p className="text-sm text-yellow-700 dark:text-yellow-300">
                                    Sistem sedang dalam mode maintenance. Hanya admin yang dapat mengakses.
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <ContentCard className="hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">Total Users</p>
                                <p className="text-3xl font-bold text-neutral-900 dark:text-white mt-1">
                                    {stats.totalUsers}
                                </p>
                            </div>
                            <div className="rounded-full bg-blue-100 p-3 dark:bg-blue-900/30">
                                <Users className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard className="hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">Total Mitra</p>
                                <p className="text-3xl font-bold text-neutral-900 dark:text-white mt-1">
                                    {stats.totalMitra}
                                </p>
                            </div>
                            <div className="rounded-full bg-green-100 p-3 dark:bg-green-900/30">
                                <UserCheck className="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard className="hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">Kegiatan Aktif</p>
                                <p className="text-3xl font-bold text-neutral-900 dark:text-white mt-1">
                                    {stats.totalKegiatan}
                                </p>
                            </div>
                            <div className="rounded-full bg-purple-100 p-3 dark:bg-purple-900/30">
                                <FolderKanban className="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard className="hover:shadow-md transition-shadow">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">Ukuran Database</p>
                                <p className="text-3xl font-bold text-neutral-900 dark:text-white mt-1">
                                    {stats.dbSize} MB
                                </p>
                            </div>
                            <div className="rounded-full bg-orange-100 p-3 dark:bg-orange-900/30">
                                <HardDrive className="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                        </div>
                    </ContentCard>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Quick Actions */}
                    <ContentCard>
                        <h3 className="text-lg font-semibold mb-4 text-neutral-900 dark:text-white">
                            Aksi Cepat
                        </h3>
                        <div className="space-y-2">
                            <Link
                                href="/admin/activity-log"
                                className="flex items-center justify-between p-3 rounded-lg border border-neutral-200 hover:border-purple-300 hover:bg-purple-50 dark:border-neutral-800 dark:hover:border-purple-700 dark:hover:bg-purple-950/30 transition-all group"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                                        <ActivitySquare className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <p className="font-medium text-neutral-900 dark:text-white">Activity Log</p>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">Monitor aktivitas sistem</p>
                                    </div>
                                </div>
                                <ChevronRight className="h-5 w-5 text-neutral-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors" />
                            </Link>

                            <Link
                                href="/admin/database-status"
                                className="flex items-center justify-between p-3 rounded-lg border border-neutral-200 hover:border-green-300 hover:bg-green-50 dark:border-neutral-800 dark:hover:border-green-700 dark:hover:bg-green-950/30 transition-all group"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="rounded-lg bg-green-100 p-2 dark:bg-green-900/30 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                                        <Database className="h-5 w-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <p className="font-medium text-neutral-900 dark:text-white">Database Management</p>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">Backup & restore database</p>
                                    </div>
                                </div>
                                <ChevronRight className="h-5 w-5 text-neutral-400 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors" />
                            </Link>

                            <Link
                                href="/admin/system-settings"
                                className="flex items-center justify-between p-3 rounded-lg border border-neutral-200 hover:border-gray-300 hover:bg-gray-50 dark:border-neutral-800 dark:hover:border-gray-700 dark:hover:bg-gray-950/30 transition-all group"
                            >
                                <div className="flex items-center gap-3">
                                    <div className="rounded-lg bg-gray-100 p-2 dark:bg-gray-900/30 group-hover:bg-gray-200 dark:group-hover:bg-gray-900/50 transition-colors">
                                        <Settings className="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                    </div>
                                    <div>
                                        <p className="font-medium text-neutral-900 dark:text-white">System Settings</p>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400">Konfigurasi sistem</p>
                                    </div>
                                </div>
                                <ChevronRight className="h-5 w-5 text-neutral-400 group-hover:text-gray-600 dark:group-hover:text-gray-400 transition-colors" />
                            </Link>
                        </div>
                    </ContentCard>

                    {/* Recent Activity & Backup Info */}
                    <div className="space-y-6">
                        {/* Backup Info */}
                        <ContentCard>
                            <h3 className="text-lg font-semibold mb-4 text-neutral-900 dark:text-white flex items-center gap-2">
                                <Database className="h-5 w-5" />
                                Backup Terakhir
                            </h3>
                            {lastBackup ? (
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-neutral-600 dark:text-neutral-400">Nama File:</span>
                                        <span className="text-sm font-medium text-neutral-900 dark:text-white truncate ml-2 max-w-[200px]" title={lastBackup.filename}>
                                            {lastBackup.filename}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-neutral-600 dark:text-neutral-400">Ukuran:</span>
                                        <span className="text-sm font-medium text-neutral-900 dark:text-white">
                                            {lastBackup.size_formatted}
                                        </span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-neutral-600 dark:text-neutral-400">Dibuat:</span>
                                        <span className="text-sm font-medium text-neutral-900 dark:text-white">
                                            {lastBackup.created_at}
                                        </span>
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center py-4">
                                    <Clock className="h-8 w-8 text-neutral-400 mx-auto mb-2" />
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Belum ada backup
                                    </p>
                                </div>
                            )}
                        </ContentCard>

                        {/* System Status */}
                        <ContentCard>
                            <h3 className="text-lg font-semibold mb-4 text-neutral-900 dark:text-white">
                                Status System
                            </h3>
                            <div className="flex items-center justify-between">
                                <span className="text-neutral-600 dark:text-neutral-400">Mode:</span>
                                <Badge 
                                    variant={systemStatus.maintenance ? 'destructive' : 'default'}
                                    className="font-semibold"
                                >
                                    {systemStatus.label}
                                </Badge>
                            </div>
                        </ContentCard>
                    </div>
                </div>

                {/* Recent Activity Logs */}
                {recentLogs && recentLogs.length > 0 && (
                    <ContentCard>
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Aktivitas Terbaru
                            </h3>
                            <Link
                                href="/admin/activity-log"
                                className="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1"
                            >
                                Lihat Semua
                                <ChevronRight className="h-4 w-4" />
                            </Link>
                        </div>
                        <div className="space-y-3">
                            {recentLogs.map((log) => (
                                <div
                                    key={log.id}
                                    className="flex items-start gap-3 p-3 rounded-lg border border-neutral-200 dark:border-neutral-800 hover:bg-neutral-50 dark:hover:bg-neutral-900/50 transition-colors"
                                >
                                    <div className="mt-0.5">
                                        {getStatusIcon(log.status)}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2 mb-1">
                                            <p className="font-medium text-sm text-neutral-900 dark:text-white">
                                                {log.action}
                                            </p>
                                            <Badge variant="outline" className="text-xs">
                                                {log.type}
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400 truncate">
                                            {log.description}
                                        </p>
                                        <div className="flex items-center gap-2 mt-1">
                                            <span className="text-xs text-neutral-500 dark:text-neutral-500">
                                                {log.user_name}
                                            </span>
                                            <span className="text-xs text-neutral-400 dark:text-neutral-600">•</span>
                                            <span className="text-xs text-neutral-500 dark:text-neutral-500">
                                                {log.created_at}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </ContentCard>
                )}
            </div>
        </AppLayout>
    );
}
