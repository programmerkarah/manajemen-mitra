import { ContentCard } from '@/components/content-card';
import { SearchableSelect } from '@/components/searchable-select';
import { DatePicker } from '@/components/ui/date-picker';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
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
import { useEffect, useMemo, useRef, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
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
    { title: 'Administrasi', href: '/dashboard' },
    { title: 'Log Aktivitas', href: '/activity-log' },
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

function formatDateOnly(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
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

const detailCardClassName =
    'rounded-2xl border border-white/10 bg-white/[0.04] p-4 shadow-sm shadow-black/10 dark:border-white/5 dark:bg-white/[0.03]';

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
    const [isCopied, setIsCopied] = useState(false);
    const [sortField, setSortField] = useState<'time' | 'user' | 'action'>(
        'time',
    );
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [isRefreshing, setIsRefreshing] = useState(false);
    const copyResetTimerRef = useRef<number | null>(null);

    useEffect(() => {
        setDecryptedLogs(parseEncryptedLogs(pageProps.logs));
    }, [pageProps.logs]);

    const buildFilterData = (page?: number): Record<string, string> => {
        const filterData: Record<string, string> = {};
        const today = formatDateOnly(new Date());

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

        if (dateFrom && !dateTo) {
            filterData.date_from = dateFrom;
            filterData.date_to = today;
        } else if (!dateFrom && dateTo) {
            filterData.date_from = `${dateTo.slice(0, 4)}-01-01`;
            filterData.date_to = dateTo;
        } else {
            if (dateFrom) {
                filterData.date_from = dateFrom;
            }

            if (dateTo) {
                filterData.date_to = dateTo;
            }
        }

        return filterData;
    };

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(
            '/activity-log',
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
            '/activity-log',
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
        window.location.href = `/activity-log/export${queryString ? `?${queryString}` : ''}`;
    };

    const handlePageChange = (page: number) => {
        router.post(
            '/activity-log',
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

        setIsCopied(true);

        if (copyResetTimerRef.current) {
            window.clearTimeout(copyResetTimerRef.current);
        }

        copyResetTimerRef.current = window.setTimeout(() => {
            setIsCopied(false);
            copyResetTimerRef.current = null;
        }, 2500);
    };

    useEffect(() => {
        return () => {
            if (copyResetTimerRef.current) {
                window.clearTimeout(copyResetTimerRef.current);
            }
        };
    }, []);

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

    const getStatusSummary = (status?: string) => {
        switch (status) {
            case 'success':
                return {
                    title: 'Berhasil',
                    description: 'Aktivitas selesai tanpa kendala.',
                    icon: <CheckCircle2 className="h-4 w-4" />,
                    cardClass:
                        'border-emerald-400/15 bg-emerald-500/10 text-emerald-50',
                    chipClass: 'bg-emerald-500/15 text-emerald-100',
                };
            case 'error':
                return {
                    title: 'Gagal',
                    description: 'Ada masalah saat aktivitas diproses.',
                    icon: <AlertCircle className="h-4 w-4" />,
                    cardClass: 'border-rose-400/15 bg-rose-500/10 text-rose-50',
                    chipClass: 'bg-rose-500/15 text-rose-100',
                };
            case 'warning':
                return {
                    title: 'Perhatian',
                    description:
                        'Aktivitas berhasil, tetapi ada catatan penting.',
                    icon: <AlertTriangle className="h-4 w-4" />,
                    cardClass:
                        'border-amber-400/15 bg-amber-500/10 text-amber-50',
                    chipClass: 'bg-amber-500/15 text-amber-100',
                };
            case 'info':
                return {
                    title: 'Informasi',
                    description: 'Aktivitas bersifat informatif.',
                    icon: <Info className="h-4 w-4" />,
                    cardClass: 'border-sky-400/15 bg-sky-500/10 text-sky-50',
                    chipClass: 'bg-sky-500/15 text-sky-100',
                };
            default:
                return {
                    title: 'Tidak dikenal',
                    description: 'Status aktivitas tidak tersedia.',
                    icon: <Activity className="h-4 w-4" />,
                    cardClass: 'border-zinc-400/15 bg-zinc-500/10 text-zinc-50',
                    chipClass: 'bg-zinc-500/15 text-zinc-100',
                };
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
                <DialogContent className="!flex max-h-[calc(100vh-2rem)] w-[min(92vw,72rem)] !max-w-[72rem] !flex-col overflow-hidden rounded-[1.75rem] border border-white/10 bg-zinc-950/95 p-5 shadow-[0_30px_80px_-20px_rgba(0,0,0,0.85)] sm:p-8">
                    <DialogHeader className="text-left">
                        <DialogTitle className="flex items-center gap-2 text-xl font-semibold tracking-tight text-white">
                            <span className="flex h-9 w-9 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/20">
                                <Activity className="h-5 w-5" />
                            </span>
                            Activity Detail
                        </DialogTitle>
                        <DialogDescription className="max-w-2xl text-sm text-zinc-400">
                            Informasi lengkap tentang aktivitas ini dalam
                            tampilan yang lebih nyaman dibaca.
                        </DialogDescription>
                    </DialogHeader>

                    {selectedLog && (
                        <div className="mt-5 min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                            <div className="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-2">
                                <div className={detailCardClassName}>
                                    <label className="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                                        User
                                    </label>
                                    <div className="mt-3 flex min-w-0 items-center gap-3 rounded-2xl border border-white/5 bg-white/5 p-4">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400/20 to-cyan-400/20 font-semibold text-emerald-200 ring-1 ring-white/10">
                                            {toUcwords(
                                                selectedLog.user,
                                            )?.charAt(0) || '?'}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate text-base font-semibold text-white">
                                                {toUcwords(selectedLog.user) ||
                                                    'Unknown'}
                                            </div>
                                            {selectedLog.user_hashed_id && (
                                                <div className="truncate text-xs text-zinc-400">
                                                    ID:{' '}
                                                    {selectedLog.user_hashed_id}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className={detailCardClassName}>
                                    <label className="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                                        Status
                                    </label>
                                    <div
                                        className={cn(
                                            'mt-3 flex min-h-28 flex-col justify-between rounded-2xl border p-4 shadow-sm',
                                            getStatusSummary(selectedLog.status)
                                                .cardClass,
                                        )}
                                    >
                                        <div className="flex items-center gap-3">
                                            <span
                                                className={cn(
                                                    'flex h-10 w-10 items-center justify-center rounded-full ring-1 ring-white/10',
                                                    getStatusSummary(
                                                        selectedLog.status,
                                                    ).chipClass,
                                                )}
                                            >
                                                {
                                                    getStatusSummary(
                                                        selectedLog.status,
                                                    ).icon
                                                }
                                            </span>
                                            <div className="min-w-0">
                                                <div className="text-base font-semibold text-white">
                                                    {
                                                        getStatusSummary(
                                                            selectedLog.status,
                                                        ).title
                                                    }
                                                </div>
                                                <div className="text-sm text-zinc-300">
                                                    {
                                                        getStatusSummary(
                                                            selectedLog.status,
                                                        ).description
                                                    }
                                                </div>
                                            </div>
                                        </div>
                                        <div className="mt-3 flex items-center justify-end">
                                            <Badge
                                                className={cn(
                                                    'gap-1 border-0 text-xs font-semibold',
                                                    getStatusSummary(
                                                        selectedLog.status,
                                                    ).chipClass,
                                                )}
                                            >
                                                {
                                                    getStatusSummary(
                                                        selectedLog.status,
                                                    ).icon
                                                }
                                                {
                                                    getStatusSummary(
                                                        selectedLog.status,
                                                    ).title
                                                }
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className={detailCardClassName}>
                                <label className="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                                    Action
                                </label>
                                <div className="mt-3 rounded-2xl border border-white/5 bg-white/5 p-4 text-base font-medium break-words whitespace-pre-wrap text-white">
                                    {selectedLog.action}
                                </div>
                            </div>

                            {selectedLog.description && (
                                <div className={detailCardClassName}>
                                    <label className="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                                        Description
                                    </label>
                                    <div className="mt-3 rounded-2xl border border-white/5 bg-white/5 p-4 text-sm leading-6 break-words whitespace-pre-wrap text-zinc-100">
                                        {selectedLog.description}
                                    </div>
                                </div>
                            )}

                            <div className="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2">
                                {selectedLog.ip_address && (
                                    <div className={detailCardClassName}>
                                        <label className="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                                            IP Address
                                        </label>
                                        <div className="mt-3 truncate rounded-2xl border border-white/5 bg-white/5 p-3 font-mono text-sm text-zinc-100">
                                            {selectedLog.ip_address}
                                        </div>
                                    </div>
                                )}

                                <div className={detailCardClassName}>
                                    <label className="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                                        Timestamp
                                    </label>
                                    <div className="mt-3 truncate rounded-2xl border border-white/5 bg-white/5 p-3 text-sm text-zinc-100">
                                        {selectedLog.time}
                                    </div>
                                </div>
                            </div>

                            {selectedLog.user_agent && (
                                <div className={detailCardClassName}>
                                    <label className="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                                        User Agent
                                    </label>
                                    <div className="mt-3 max-h-28 overflow-auto rounded-2xl border border-white/5 bg-white/5 p-4 font-mono text-xs leading-5 break-words whitespace-pre-wrap text-zinc-100">
                                        {selectedLog.user_agent}
                                    </div>
                                </div>
                            )}

                            {selectedLog.properties &&
                                Object.keys(selectedLog.properties).length >
                                    0 && (
                                    <div className={detailCardClassName}>
                                        <label className="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                                            Additional Data
                                        </label>
                                        <div className="mt-3 max-h-64 overflow-auto rounded-2xl border border-white/5 bg-white/5 p-4 font-mono text-xs leading-5 text-zinc-100">
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

                    <DialogFooter className="mt-2 border-t border-white/10 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleCopyLog}
                            disabled={!selectedLog}
                        >
                            {isCopied ? 'Sudah disalin' : 'Salin Activity Log'}
                        </Button>
                        <Button
                            type="button"
                            variant="default"
                            onClick={() => setIsDetailOpen(false)}
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
