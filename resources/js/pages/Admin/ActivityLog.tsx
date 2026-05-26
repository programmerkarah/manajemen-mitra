import { ContentCard } from '@/components/content-card';
import AppLayout from '@/layouts/app-layout';
import { decryptData, encryptFilters } from '@/utils/encryption';
import { Head } from '@inertiajs/react';
import React from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { BreadcrumbItem } from '@/types';
import { router, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertCircle,
    AlertTriangle,
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock,
    Download,
    Eye,
    Info,
    RefreshCw,
    Search,
    User as UserIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '/admin/dashboard' },
    { title: 'Log Aktivitas', href: '/admin/activity-log' },
];

interface ActivityLog {
    id: number;
    user: string;
    user_id?: number;
    action: string;
    description?: string;
    status?: 'success' | 'error' | 'warning' | 'info';
    ip_address?: string;
    user_agent?: string;
    time: string;
    created_at?: string;
    properties?: Record<string, unknown>;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Filters {
    search?: string;
    user_id?: string;
    user?: string;
    action?: string;
    status?: string;
    date?: string;
    date_from?: string;
    date_to?: string;
}

interface FilterState {
    encrypted?: string;
    decrypted?: Filters;
}

interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    logs: string | ActivityLog[]; // Can be encrypted string or array
    pagination?: PaginationData;
    filters?: FilterState;
    users?: User[];
    [key: string]: unknown;
}

export default function ActivityLog() {
    const pageProps = usePage<Props>().props;
    const { users = [], pagination } = pageProps;
    const activeFilters = pageProps.filters?.decrypted ?? {};

    // Decrypt logs if encrypted
    const [decryptedLogs, setDecryptedLogs] = useState<ActivityLog[]>([]);

    useEffect(() => {
        if (typeof pageProps.logs === 'string') {
            // Logs are encrypted, decrypt them
            try {
                const decrypted = decryptData(pageProps.logs);
                if (decrypted && Array.isArray(decrypted)) {
                    setDecryptedLogs(decrypted);
                } else {
                    console.error(
                        '❌ Failed to decrypt logs or invalid format',
                    );
                    setDecryptedLogs([]);
                }
            } catch (error) {
                console.error('❌ Logs decryption error:', error);
                setDecryptedLogs([]);
            }
        } else if (Array.isArray(pageProps.logs)) {
            // Logs are not encrypted (backward compatibility)
            setDecryptedLogs(pageProps.logs);
        } else {
            setDecryptedLogs([]);
        }
    }, [pageProps.logs]);

    const [status, setStatus] = useState(activeFilters.status || 'all');
    const [user, setUser] = useState(activeFilters.user || 'all');
    const [date, setDate] = useState(activeFilters.date || '');
    const [searchQuery, setSearchQuery] = useState(activeFilters.search || '');
    const [filteredLogs, setFilteredLogs] = useState<ActivityLog[]>([]);
    const [selectedLog, setSelectedLog] = useState<ActivityLog | null>(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [sortField, setSortField] = useState<'time' | 'user' | 'action'>(
        'time',
    );
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [isRefreshing, setIsRefreshing] = useState(false);

    // Filter logs based on search query
    useEffect(() => {
        const filtered = [...decryptedLogs];

        // Apply sorting
        filtered.sort((a, b) => {
            let aVal, bVal;

            switch (sortField) {
                case 'user':
                    aVal = a.user?.toLowerCase() || '';
                    bVal = b.user?.toLowerCase() || '';
                    break;
                case 'action':
                    aVal = a.action?.toLowerCase() || '';
                    bVal = b.action?.toLowerCase() || '';
                    break;
                case 'time':
                default:
                    aVal = a.time || '';
                    bVal = b.time || '';
                    break;
            }

            if (aVal < bVal) return sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        setFilteredLogs(filtered);
    }, [decryptedLogs, sortField, sortDirection]);

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();

        const filterData: Record<string, string> = {};

        if (searchQuery.trim()) {
            filterData.search = searchQuery.trim();
        }

        if (status !== 'all' && status) {
            filterData.status = status;
        }
        if (user !== 'all' && user) {
            filterData.user = user;
        }
        if (date) {
            filterData.date = date;
        }

        router.post(
            '/admin/activity-log',
            {
                encrypted_filters: encryptFilters(filterData),
            },
            {
                preserveState: true,
            },
        );
    };

    const handleRefresh = () => {
        setIsRefreshing(true);
        const filterData: Record<string, string> = {
            page: String(pagination?.current_page ?? 1),
        };

        if (searchQuery.trim()) {
            filterData.search = searchQuery.trim();
        }

        if (status !== 'all' && status) {
            filterData.status = status;
        }

        if (user !== 'all' && user) {
            filterData.user = user;
        }

        if (date) {
            filterData.date = date;
        }

        router.post(
            '/admin/activity-log',
            {
                encrypted_filters: encryptFilters(filterData),
            },
            {
                onFinish: () => {
                    setTimeout(() => setIsRefreshing(false), 500);
                },
            },
        );
    };

    const handleExport = () => {
        const params = new URLSearchParams();

        if (searchQuery.trim()) {
            params.append('search', searchQuery.trim());
        }

        if (status && status !== 'all') {
            params.append('status', status);
        }
        if (user && user !== 'all') {
            params.append('user', user);
        }
        if (date) {
            params.append('date', date);
        }

        const url = `/admin/activity-log/export?${params.toString()}`;
        window.location.href = url;
    };

    const handleSort = (field: 'time' | 'user' | 'action') => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('desc');
        }
    };

    const handlePageChange = (page: number) => {
        const filterData: Record<string, string> = { page: page.toString() };

        if (searchQuery.trim()) {
            filterData.search = searchQuery.trim();
        }

        if (status !== 'all' && status) {
            filterData.status = status;
        }
        if (user !== 'all' && user) {
            filterData.user = user;
        }
        if (date) {
            filterData.date = date;
        }

        router.post(
            '/admin/activity-log',
            {
                encrypted_filters: encryptFilters(filterData),
            },
            {
                preserveState: true,
                preserveScroll: false,
            },
        );
    };

    const getStatusIcon = (status?: string) => {
        switch (status) {
            case 'success':
                return <CheckCircle2 className="h-4 w-4" />;
            case 'error':
                return <AlertCircle className="h-4 w-4" />;
            case 'warning':
                return <AlertTriangle className="h-4 w-4" />;
            case 'info':
                return <Info className="h-4 w-4" />;
            default:
                return <Activity className="h-4 w-4" />;
        }
    };

    const getStatusBadge = (status?: string) => {
        switch (status) {
            case 'success':
                return (
                    <Badge
                        variant="default"
                        className="gap-1 bg-green-600 hover:bg-green-700"
                    >
                        {getStatusIcon(status)} Success
                    </Badge>
                );
            case 'error':
                return (
                    <Badge variant="destructive" className="gap-1">
                        {getStatusIcon(status)} Error
                    </Badge>
                );
            case 'warning':
                return (
                    <Badge
                        variant="default"
                        className="gap-1 bg-yellow-600 hover:bg-yellow-700"
                    >
                        {getStatusIcon(status)} Warning
                    </Badge>
                );
            case 'info':
                return (
                    <Badge variant="secondary" className="gap-1">
                        {getStatusIcon(status)} Info
                    </Badge>
                );
            default:
                return (
                    <Badge variant="outline" className="gap-1">
                        {getStatusIcon(status)} -
                    </Badge>
                );
        }
    };

    const SortIcon = ({ field }: { field: 'time' | 'user' | 'action' }) => {
        if (sortField !== field) return null;
        return sortDirection === 'asc' ? (
            <ChevronUp className="ml-1 inline h-4 w-4" />
        ) : (
            <ChevronDown className="ml-1 inline h-4 w-4" />
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Activity Log" />
            <ContentCard>
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h2 className="flex items-center gap-2 text-2xl font-bold">
                            <Activity className="h-6 w-6" />
                            Activity Log
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Monitor dan tracking aktivitas user dalam sistem
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleRefresh}
                            disabled={isRefreshing}
                        >
                            <RefreshCw
                                className={`mr-2 h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`}
                            />
                            Refresh
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleExport}
                            disabled={decryptedLogs.length === 0}
                        >
                            <Download className="mr-2 h-4 w-4" />
                            Export Excel
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <form
                    className="mb-6 flex flex-wrap gap-3 rounded-lg bg-muted/30 p-4"
                    onSubmit={handleFilter}
                >
                    <div className="min-w-[200px] flex-1">
                        <label className="mb-1.5 block flex items-center gap-1 text-xs font-medium">
                            <Search className="h-3 w-3" />
                            Search
                        </label>
                        <Input
                            type="text"
                            placeholder="Cari user, action, IP..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="h-9"
                        />
                    </div>
                    <div>
                        <label className="mb-1.5 block flex items-center gap-1 text-xs font-medium">
                            <Activity className="h-3 w-3" />
                            Status
                        </label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger className="h-9 w-36">
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua</SelectItem>
                                <SelectItem value="success">Success</SelectItem>
                                <SelectItem value="error">Error</SelectItem>
                                <SelectItem value="warning">Warning</SelectItem>
                                <SelectItem value="info">Info</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="mb-1.5 block flex items-center gap-1 text-xs font-medium">
                            <UserIcon className="h-3 w-3" />
                            User
                        </label>
                        <Select value={user} onValueChange={setUser}>
                            <SelectTrigger className="h-9 w-44">
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua User</SelectItem>
                                {users &&
                                    users.map((u) => (
                                        <SelectItem
                                            key={u.id}
                                            value={u.id + ''}
                                        >
                                            {u.name}
                                        </SelectItem>
                                    ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="mb-1.5 block flex items-center gap-1 text-xs font-medium">
                            <Calendar className="h-3 w-3" />
                            Tanggal
                        </label>
                        <DatePicker
                            value={date}
                            onChange={(v) => setDate(v)}
                            className="h-9 w-40"
                        />
                    </div>
                    <div className="flex items-end">
                        <Button type="submit" size="sm" className="h-9">
                            Apply Filter
                        </Button>
                    </div>
                </form>

                {/* Results count */}
                <div className="mb-3 flex items-center justify-between text-sm text-muted-foreground">
                    <div>
                        {pagination ? (
                            <>
                                Menampilkan{' '}
                                <span className="font-semibold text-foreground">
                                    {pagination.from ?? 0}
                                </span>{' '}
                                hingga{' '}
                                <span className="font-semibold text-foreground">
                                    {pagination.to ?? 0}
                                </span>{' '}
                                dari{' '}
                                <span className="font-semibold text-foreground">
                                    {pagination.total}
                                </span>{' '}
                                log
                            </>
                        ) : (
                            <>
                                Menampilkan{' '}
                                <span className="font-semibold text-foreground">
                                    {filteredLogs.length}
                                </span>{' '}
                                dari {decryptedLogs.length} log
                            </>
                        )}
                    </div>
                    {pagination && pagination.last_page > 1 && (
                        <div className="text-xs">
                            Halaman{' '}
                            <span className="font-semibold text-foreground">
                                {pagination.current_page}
                            </span>{' '}
                            dari{' '}
                            <span className="font-semibold text-foreground">
                                {pagination.last_page}
                            </span>
                        </div>
                    )}
                </div>

                {/* Table */}
                <div className="overflow-x-auto rounded-lg border">
                    <table className="min-w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th
                                    className="cursor-pointer px-4 py-3 text-left font-semibold transition-colors hover:bg-muted/70"
                                    onClick={() => handleSort('user')}
                                >
                                    <div className="flex items-center gap-1">
                                        <UserIcon className="h-4 w-4" />
                                        User
                                        <SortIcon field="user" />
                                    </div>
                                </th>
                                <th
                                    className="cursor-pointer px-4 py-3 text-left font-semibold transition-colors hover:bg-muted/70"
                                    onClick={() => handleSort('action')}
                                >
                                    <div className="flex items-center gap-1">
                                        <Activity className="h-4 w-4" />
                                        Action
                                        <SortIcon field="action" />
                                    </div>
                                </th>
                                <th className="px-4 py-3 text-left font-semibold">
                                    <div className="flex items-center gap-1">
                                        <AlertCircle className="h-4 w-4" />
                                        Status
                                    </div>
                                </th>
                                <th className="px-4 py-3 text-left font-semibold">
                                    IP Address
                                </th>
                                <th
                                    className="cursor-pointer px-4 py-3 text-left font-semibold transition-colors hover:bg-muted/70"
                                    onClick={() => handleSort('time')}
                                >
                                    <div className="flex items-center gap-1">
                                        <Clock className="h-4 w-4" />
                                        Time
                                        <SortIcon field="time" />
                                    </div>
                                </th>
                                <th className="px-4 py-3 text-center font-semibold">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredLogs.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-12 text-center"
                                    >
                                        <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                            <Activity className="h-12 w-12 opacity-20" />
                                            <p className="font-medium">
                                                Tidak ada log ditemukan
                                            </p>
                                            <p className="text-xs">
                                                Coba ubah filter atau kriteria
                                                pencarian
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                filteredLogs.map((log, index) => (
                                    <tr
                                        key={log.id}
                                        className={`border-t transition-colors hover:bg-muted/30 ${
                                            index % 2 === 0
                                                ? 'bg-background'
                                                : 'bg-muted/10'
                                        }`}
                                    >
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                                    {log.user
                                                        ?.charAt(0)
                                                        .toUpperCase() || '?'}
                                                </div>
                                                <div className="font-medium">
                                                    {log.user || 'Unknown'}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium">
                                                {log.action}
                                            </div>
                                            {log.description && (
                                                <div className="mt-0.5 line-clamp-1 text-xs text-muted-foreground">
                                                    {log.description}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            {getStatusBadge(log.status)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-mono text-sm text-muted-foreground">
                                                {log.ip_address || '-'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {log.time}
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="h-8 w-8 p-0"
                                                onClick={() => {
                                                    setSelectedLog(log);
                                                    setIsDetailOpen(true);
                                                }}
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {pagination && pagination.last_page > 1 && (
                    <div className="mt-4 flex items-center justify-between border-t pt-4">
                        <div className="text-sm text-muted-foreground">
                            Showing {pagination.from ?? 0} to{' '}
                            {pagination.to ?? 0} of {pagination.total} entries
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handlePageChange(1)}
                                disabled={pagination.current_page === 1}
                            >
                                First
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    handlePageChange(
                                        pagination.current_page - 1,
                                    )
                                }
                                disabled={pagination.current_page === 1}
                            >
                                Previous
                            </Button>

                            <div className="flex items-center gap-1">
                                {(() => {
                                    const pages = [];
                                    const maxVisible = 5;
                                    let startPage = Math.max(
                                        1,
                                        pagination.current_page -
                                            Math.floor(maxVisible / 2),
                                    );
                                    const endPage = Math.min(
                                        pagination.last_page,
                                        startPage + maxVisible - 1,
                                    );

                                    if (endPage - startPage + 1 < maxVisible) {
                                        startPage = Math.max(
                                            1,
                                            endPage - maxVisible + 1,
                                        );
                                    }

                                    for (let i = startPage; i <= endPage; i++) {
                                        pages.push(
                                            <Button
                                                key={i}
                                                variant={
                                                    i ===
                                                    pagination.current_page
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                onClick={() =>
                                                    handlePageChange(i)
                                                }
                                                className="w-10"
                                            >
                                                {i}
                                            </Button>,
                                        );
                                    }
                                    return pages;
                                })()}
                            </div>

                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    handlePageChange(
                                        pagination.current_page + 1,
                                    )
                                }
                                disabled={
                                    pagination.current_page ===
                                    pagination.last_page
                                }
                            >
                                Next
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    handlePageChange(pagination.last_page)
                                }
                                disabled={
                                    pagination.current_page ===
                                    pagination.last_page
                                }
                            >
                                Last
                            </Button>
                        </div>
                    </div>
                )}
            </ContentCard>

            {/* Detail Dialog */}
            <Dialog open={isDetailOpen} onOpenChange={setIsDetailOpen}>
                <DialogContent className="max-h-[80vh] max-w-2xl overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Activity className="h-5 w-5" />
                            Activity Detail
                        </DialogTitle>
                        <DialogDescription>
                            Informasi lengkap tentang aktivitas ini
                        </DialogDescription>
                    </DialogHeader>

                    {selectedLog && (
                        <div className="mt-4 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        User
                                    </label>
                                    <div className="mt-1 flex items-center gap-2">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-semibold text-primary">
                                            {selectedLog.user
                                                ?.charAt(0)
                                                .toUpperCase() || '?'}
                                        </div>
                                        <div>
                                            <div className="font-medium">
                                                {selectedLog.user || 'Unknown'}
                                            </div>
                                            {selectedLog.user_id && (
                                                <div className="text-xs text-muted-foreground">
                                                    ID: {selectedLog.user_id}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Status
                                    </label>
                                    <div className="mt-1">
                                        {getStatusBadge(selectedLog.status)}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label className="text-xs font-medium text-muted-foreground">
                                    Action
                                </label>
                                <div className="mt-1 rounded-lg bg-muted/30 p-3 font-medium">
                                    {selectedLog.action}
                                </div>
                            </div>

                            {selectedLog.description && (
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Description
                                    </label>
                                    <div className="mt-1 rounded-lg bg-muted/30 p-3 text-sm">
                                        {selectedLog.description}
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                {selectedLog.ip_address && (
                                    <div>
                                        <label className="text-xs font-medium text-muted-foreground">
                                            IP Address
                                        </label>
                                        <div className="mt-1 rounded bg-muted/30 p-2 font-mono text-sm">
                                            {selectedLog.ip_address}
                                        </div>
                                    </div>
                                )}

                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Timestamp
                                    </label>
                                    <div className="mt-1 rounded bg-muted/30 p-2 text-sm">
                                        {selectedLog.time}
                                    </div>
                                </div>
                            </div>

                            {selectedLog.user_agent && (
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        User Agent
                                    </label>
                                    <div className="mt-1 rounded-lg bg-muted/30 p-3 font-mono text-xs break-all">
                                        {selectedLog.user_agent}
                                    </div>
                                </div>
                            )}

                            {selectedLog.properties &&
                                Object.keys(selectedLog.properties).length >
                                    0 && (
                                    <div>
                                        <label className="text-xs font-medium text-muted-foreground">
                                            Additional Data
                                        </label>
                                        <div className="mt-1 rounded-lg bg-muted/30 p-3 font-mono text-xs">
                                            <pre className="overflow-x-auto">
                                                {JSON.stringify(
                                                    selectedLog.properties,
                                                    null,
                                                    2,
                                                )}
                                            </pre>
                                        </div>
                                    </div>
                                )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
