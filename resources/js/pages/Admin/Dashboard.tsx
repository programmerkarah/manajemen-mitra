import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { Users, Database, Settings, ActivitySquare } from 'lucide-react';

export default function AdminDashboard() {
    const { systemStatus, lastBackup, totalUsers, lowStockCount } = usePage().props as {
        systemStatus?: { maintenance: boolean; status: string; label: string };
        lastBackup?: { filename: string; size: number; created_at: string } | null;
        totalUsers?: number;
        lowStockCount?: number;
    };

    const status = systemStatus || { maintenance: false, status: 'active', label: 'Active' };

    return (
        <AppLayout>
            <Head title="Admin Dashboard" />
            <ContentCard>
                <h2 className="text-2xl font-bold mb-6">Admin Dashboard</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div className="p-4 bg-white dark:bg-gray-900 rounded shadow flex items-center gap-4">
                        <Users className="w-8 h-8 text-blue-600" />
                        <div>
                            <div className="text-lg font-semibold">{totalUsers ?? '-'}</div>
                            <div className="text-xs text-gray-500">Total Users</div>
                        </div>
                    </div>
                    <div className="p-4 bg-white dark:bg-gray-900 rounded shadow flex items-center gap-4">
                        <Database className="w-8 h-8 text-green-600" />
                        <div>
                            <div className="text-lg font-semibold">{lastBackup?.filename ?? '-'}</div>
                            <div className="text-xs text-gray-500">Last Backup</div>
                        </div>
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Link href="/admin/activity-log" className="p-4 bg-white dark:bg-gray-900 rounded shadow flex items-center gap-4 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <ActivitySquare className="w-6 h-6 text-purple-600" />
                        <span>Activity Log</span>
                    </Link>
                    <Link href="/admin/database-status" className="p-4 bg-white dark:bg-gray-900 rounded shadow flex items-center gap-4 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <Database className="w-6 h-6 text-green-600" />
                        <span>Database Management</span>
                    </Link>
                    <Link href="/admin/system-settings" className="p-4 bg-white dark:bg-gray-900 rounded shadow flex items-center gap-4 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <Settings className="w-6 h-6 text-gray-600" />
                        <span>System Settings</span>
                    </Link>
                </div>
                <div className="mt-8">
                    <div className="flex items-center gap-2">
                        <span className="font-medium">Status Server:</span>
                        <span className={status.maintenance ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>
                            {status.maintenance ? 'Maintenance' : 'Active'}
                        </span>
                    </div>
                </div>
            </ContentCard>
        </AppLayout>
    );
}
