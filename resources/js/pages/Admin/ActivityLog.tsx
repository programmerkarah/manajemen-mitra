import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';

import { usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import { Select, SelectTrigger, SelectContent, SelectItem, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

export default function ActivityLog() {
    const { logs = [], filters = {}, users = [] } = usePage().props as { logs: any[], filters?: any, users?: any[] };
    const [status, setStatus] = useState(filters.status || 'all');
    const [user, setUser] = useState(filters.user || 'all');
    const [date, setDate] = useState(filters.date || '');

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.visit('/admin/activity-log', {
            method: 'get',
            data: {
                status: status === 'all' ? '' : status,
                user: user === 'all' ? '' : user,
                date,
            },
            preserveState: true,
        });
    };

    return (
        <AppLayout>
            <Head title="Activity Log" />
            <ContentCard>
                <h2 className="text-xl font-bold mb-4">Activity Log</h2>
                <form className="flex flex-wrap gap-2 mb-4 items-end" onSubmit={handleFilter}>
                    <div>
                        <label className="block text-xs font-medium mb-1">Status</label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger className="w-36">
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua</SelectItem>
                                <SelectItem value="success">Success</SelectItem>
                                <SelectItem value="error">Error</SelectItem>
                                <SelectItem value="warning">Warning</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">User</label>
                        <Select value={user} onValueChange={setUser}>
                            <SelectTrigger className="w-44">
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua</SelectItem>
                                {users && users.map(u => (
                                    <SelectItem key={u.id} value={u.id+''}>{u.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">Tanggal</label>
                        <Input type="date" value={date} onChange={e => setDate(e.target.value)} className="w-40" />
                    </div>
                    <Button type="submit" size="sm" className="h-9">Filter</Button>
                </form>
                <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                        <thead>
                            <tr>
                                <th className="px-4 py-2 text-left">User</th>
                                <th className="px-4 py-2 text-left">Action</th>
                                <th className="px-4 py-2 text-left">Status</th>
                                <th className="px-4 py-2 text-left">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.map((log) => (
                                <tr key={log.id}>
                                    <td className="px-4 py-2">{log.user}</td>
                                    <td className="px-4 py-2">{log.action}</td>
                                    <td className="px-4 py-2">
                                        {log.status === 'success' && <span className="text-green-600">Success</span>}
                                        {log.status === 'error' && <span className="text-red-600">Error</span>}
                                        {log.status === 'warning' && <span className="text-yellow-600">Warning</span>}
                                        {!log.status && <span className="text-gray-400">-</span>}
                                    </td>
                                    <td className="px-4 py-2">{log.time}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </ContentCard>
        </AppLayout>
    );
}
