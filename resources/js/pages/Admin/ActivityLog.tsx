import { ContentCard } from '@/components/content-card';
import { SearchableSelect } from '@/components/searchable-select';
import { DatePicker } from '@/components/ui/date-picker';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { decryptData, encryptFilters } from '@/utils/encryption';
import { Head, router, usePage } from '@inertiajs/react';
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
import { useEffect, useMemo, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '/admin/dashboard' },
    { title: 'Log Aktivitas', href: '/admin/activity-log' },
];

interface ActivityLog {
    id: number;
    user: string;
    user_id?: number;
    user_hashed_id?: string | null;
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
    logs: string | ActivityLog[];
    pagination?: PaginationData;
    filters?: FilterState;
    users?: User[];
    [key: string]: unknown;
}

function parseEncryptedLogs(logs: string | ActivityLog[]): ActivityLog[] {
    if (typeof logs === 'string') {
        try {
            const decrypted = decryptData(logs);
            return Array.isArray(decrypted) ? decrypted : [];
        } catch {
            return [];
        }
    }

    return Array.isArray(logs) ? logs : [];
}

function toUcwords(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    return value
        .trim()
        .toLowerCase()
        .split(/\s+/)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

export default function ActivityLog() {
    const pageProps = usePage<Props>().props;
    const { users = [], pagination } = pageProps;
    const activeFilters = pageProps.filters?.decrypted ?? {};

    const [decryptedLogs, setDecryptedLogs] = useState<ActivityLog[]>([]);
    const [status, setStatus] = useState(activeFilters.status || 'all');
    const [user, setUser] = useState(activeFilters.user || '');
    const [dateFrom, setDateFrom] = useState(
        activeFilters.date_from || activeFilters.date || '',
    );
    const [dateTo, setDateTo] = useState(
        activeFilters.date_to || activeFilters.date || '',
    );
    const [searchQuery, setSearchQuery] = useState(activeFilters.search || '');
    const [selectedLog, setSelectedLog] = useState<ActivityLog | null>(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [sortField, setSortField] = useState<'time' | 'user' | 'action'>(
        'time',
    );
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [isRefreshing, setIsRefreshing] = useState(false);

    useEffect(() => {
        setDecryptedLogs(parseEncryptedLogs(pageProps.logs));
    }, [pageProps.logs]);

    const buildFilterData = (page?: number): Record<string, string> => {
        const filterData: Record<string, string> = {};

        if (page) {
            filterData.page = String(page);
        }

        if (searchQuery.trim()) {
            filterData.search = searchQuery.trim();
        }

        if (status !== 'all') {
            filterData.status = status;
        }

        if (user) {
            filterData.user = user;
        }

        if (dateFrom) {
            filterData.date_from = dateFrom;
        }

        if (dateTo) {
            filterData.date_to = dateTo;
        }

        return filterData;
    };

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            '/admin/activity-log',
            {
                encrypted_filters: encryptFilters(buildFilterData()),
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.post(
            '/admin/activity-log',
            {
                encrypted_filters: encryptFilters(
                    buildFilterData(pagination?.current_page ?? 1),
                ),
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsRefreshing(false),
            },
        );
    };

    const handleExport = () => {
        const params = new URLSearchParams(buildFilterData());
        const queryString = params.toString();
        window.location.href = `/admin/activity-log/export${queryString ? `?${queryString}` : ''}`;
    };

    const handlePageChange = (page: number) => {
        router.post(
            '/admin/activity-log',
            {
                encrypted_filters: encryptFilters(buildFilterData(page)),
            },
            {
                preserveState: true,
                preserveScroll: false,
            },
        );
    };

    const handleSort = (field: 'time' | 'user' | 'action') => {
        if (sortField === field) {
            setSortDirection((current) => (current === 'asc' ? 'desc' : 'asc'));
            return;
        }

        setSortField(field);
        setSortDirection('desc');
    };

    const handleCopyLog = async () => {
        if (!selectedLog) {
            return;
        }

        const payload = {
            id: selectedLog.id,
            user: selectedLog.user,
            user_id: selectedLog.user_id,
            user_hashed_id: selectedLog.user_hashed_id,
            action: selectedLog.action,
            description: selectedLog.description,
            status: selectedLog.status,
            ip_address: selectedLog.ip_address,
            user_agent: selectedLog.user_agent,
            time: selectedLog.time,
            properties: selectedLog.properties,
        };

        await navigator.clipboard.writeText(JSON.stringify(payload, null, 2));
    };

    const sortedLogs = useMemo(() => {
        const filtered = [...decryptedLogs];

        filtered.sort((a, b) => {
            let left = '';
            let right = '';

            if (sortField === 'user') {
                left = a.user?.toLowerCase() || '';
                right = b.user?.toLowerCase() || '';
            } else if (sortField === 'action') {
                left = a.action?.toLowerCase() || '';
                right = b.action?.toLowerCase() || '';
            } else {
                left = a.time || '';
                right = b.time || '';
            }

            if (left < right) {
                return sortDirection === 'asc' ? -1 : 1;
            }

            if (left > right) {
                return sortDirection === 'asc' ? 1 : -1;
            }

            return 0;
        });

        return filtered;
    }, [decryptedLogs, sortDirection, sortField]);

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
                    <Badge className="gap-1 bg-green-600 hover:bg-green-700">
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
                    <Badge className="gap-1 bg-yellow-600 hover:bg-yellow-700">
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
        if (sortField !== field) {
            return null;
        }

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
                <div className="mb-6 flex items-center justify-between gap-4">
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
                            disabled={sortedLogs.length === 0}
                        >
                            <Download className="mr-2 h-4 w-4" />
                            Export Excel
                        </Button>
                    </div>
                </div>

                <form
                    className="mb-6 flex flex-wrap gap-3 rounded-lg bg-muted/30 p-4"
                    onSubmit={handleFilter}
                >
                    <div className="min-w-[220px] flex-1">
                        <label className="mb-1.5 block flex items-center gap-1 text-xs font-medium">
                            <Search className="h-3 w-3" />
                            Search
                        </label>
                        <Input
                            type="text"
                            placeholder="Cari user, action, IP..."
                            value={searchQuery}
                            onChange={(event) =>
                                setSearchQuery(event.target.value)
                            }
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

                    <div className="min-w-[220px] flex-1 sm:max-w-[320px]">
                        <label className="mb-1.5 block flex items-center gap-1 text-xs font-medium">
                            <UserIcon className="h-3 w-3" />
                            User
                        </label>
                        <SearchableSelect
                            options={users.map((item) => ({
                                value: String(item.id),
                                label: toUcwords(item.name),
                                searchKeywords: item.name,
                            }))}
                            value={user}
                            onValueChange={setUser}
                            placeholder="Semua user"
                            searchPlaceholder="Cari user"
                            defaultVisibleCount={8}
                            showClearAction
                            clearLabel="Clear user"
                        />
                    </div>

                    <div className="min-w-[220px] flex-1">
                        <label className="mb-1.5 block flex items-center gap-1 text-xs font-medium">
                            <Calendar className="h-3 w-3" />
                            Tanggal mulai
                        </label>
                        <DatePicker
                            value={dateFrom}
                            onChange={setDateFrom}
                            className="h-9"
                            placeholder="Tanggal mulai"
                        />
                    </div>

                    <div className="min-w-[220px] flex-1">
                        <label className="mb-1.5 block flex items-center gap-1 text-xs font-medium">
                            <Calendar className="h-3 w-3" />
                            Tanggal selesai
                        </label>
                        <DatePicker
                            value={dateTo}
                            onChange={setDateTo}
                            className="h-9"
                            placeholder="Tanggal selesai"
                        />
                    </div>

                    <div className="flex items-end">
                        <Button type="submit" size="sm" className="h-9">
                            Apply Filter
                        </Button>
                    </div>
                </form>

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
                                    {sortedLogs.length}
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
                            {sortedLogs.length === 0 ? (
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
                                sortedLogs.map((log, index) => (
                                    <tr
                                        key={log.id}
                                        className={`border-t transition-colors hover:bg-muted/30 ${index % 2 === 0 ? 'bg-background' : 'bg-muted/10'}`}
                                    >
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                                    {toUcwords(
                                                        log.user,
                                                    )?.charAt(0) || '?'}
                                                </div>
                                                <div className="font-medium">
                                                    {toUcwords(log.user) ||
                                                        'Unknown'}
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
                                    const pages: React.ReactElement[] = [];
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

                                    for (
                                        let currentPage = startPage;
                                        currentPage <= endPage;
                                        currentPage++
                                    ) {
                                        pages.push(
                                            <Button
                                                key={currentPage}
                                                variant={
                                                    currentPage ===
                                                    pagination.current_page
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                onClick={() =>
                                                    handlePageChange(
                                                        currentPage,
                                                    )
                                                }
                                                className="w-10"
                                            >
                                                {currentPage}
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

            <Dialog open={isDetailOpen} onOpenChange={setIsDetailOpen}>
                <DialogContent className="max-h-[calc(100vh-2rem)] max-w-5xl overflow-hidden">
                    <DialogHeader>
                        <div className="flex items-start justify-between gap-3">
                            <DialogTitle className="flex items-center gap-2">
                                <Activity className="h-5 w-5" />
                                Activity Detail
                            </DialogTitle>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleCopyLog}
                                disabled={!selectedLog}
                            >
                                Salin Activity Log
                            </Button>
                        </div>
                        <DialogDescription>
                            Informasi lengkap tentang aktivitas ini
                        </DialogDescription>
                    </DialogHeader>

                    {selectedLog && (
                        <div className="mt-4 space-y-4 overflow-hidden">
                            <div className="grid min-w-0 grid-cols-2 gap-4">
                                <div className="min-w-0 rounded-lg bg-muted/20 p-0">
                                    <label className="text-xs font-medium text-muted-foreground">
                                        User
                                    </label>
                                    <div className="mt-1 flex min-w-0 items-center gap-2 rounded-lg bg-muted/30 p-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-semibold text-primary">
                                            {toUcwords(
                                                selectedLog.user,
                                            )?.charAt(0) || '?'}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate font-medium">
                                                {toUcwords(selectedLog.user) ||
                                                    'Unknown'}
                                            </div>
                                            {selectedLog.user_hashed_id && (
                                                <div className="truncate text-xs text-muted-foreground">
                                                    ID:{' '}
                                                    {selectedLog.user_hashed_id}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="min-w-0">
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Status
                                    </label>
                                    <div className="mt-1">
                                        {getStatusBadge(selectedLog.status)}
                                    </div>
                                </div>
                            </div>

                            <div className="min-w-0 rounded-lg bg-muted/20 p-0">
                                <label className="text-xs font-medium text-muted-foreground">
                                    Action
                                </label>
                                <div className="mt-1 max-h-24 overflow-auto rounded-lg bg-muted/30 p-3 font-medium break-words whitespace-pre-wrap">
                                    {selectedLog.action}
                                </div>
                            </div>

                            {selectedLog.description && (
                                <div className="min-w-0 rounded-lg bg-muted/20 p-0">
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Description
                                    </label>
                                    <div className="mt-1 max-h-28 overflow-auto rounded-lg bg-muted/30 p-3 text-sm break-words whitespace-pre-wrap">
                                        {selectedLog.description}
                                    </div>
                                </div>
                            )}

                            <div className="grid min-w-0 grid-cols-2 gap-4">
                                {selectedLog.ip_address && (
                                    <div className="min-w-0">
                                        <label className="text-xs font-medium text-muted-foreground">
                                            IP Address
                                        </label>
                                        <div className="mt-1 truncate rounded bg-muted/30 p-2 font-mono text-sm">
                                            {selectedLog.ip_address}
                                        </div>
                                    </div>
                                )}

                                <div className="min-w-0">
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Timestamp
                                    </label>
                                    <div className="mt-1 truncate rounded bg-muted/30 p-2 text-sm">
                                        {selectedLog.time}
                                    </div>
                                </div>
                            </div>

                            {selectedLog.user_agent && (
                                <div className="min-w-0 rounded-lg bg-muted/20 p-0">
                                    <label className="text-xs font-medium text-muted-foreground">
                                        User Agent
                                    </label>
                                    <div className="mt-1 max-h-24 overflow-auto rounded-lg bg-muted/30 p-3 font-mono text-xs break-words whitespace-pre-wrap">
                                        {selectedLog.user_agent}
                                    </div>
                                </div>
                            )}

                            {selectedLog.properties &&
                                Object.keys(selectedLog.properties).length >
                                    0 && (
                                    <div className="min-w-0 rounded-lg bg-muted/20 p-0">
                                        <label className="text-xs font-medium text-muted-foreground">
                                            Additional Data
                                        </label>
                                        <div className="mt-1 max-h-56 overflow-auto rounded-lg bg-muted/30 p-3 font-mono text-xs">
                                            <pre className="overflow-x-auto break-words whitespace-pre-wrap">
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
