import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { decryptData } from '@/utils/encryption';

import { usePage, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Select, SelectTrigger, SelectContent, SelectItem, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogHeader, 
    DialogTitle 
} from '@/components/ui/dialog';
import { 
    Search, 
    RefreshCw, 
    Download, 
    ChevronUp, 
    ChevronDown,
    Eye,
    AlertCircle,
    CheckCircle2,
    AlertTriangle,
    Info,
    Calendar,
    User as UserIcon,
    Clock,
    Activity
} from 'lucide-react';
import { BreadcrumbItem } from '@/types';

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
    properties?: Record<string, any>;
}

interface Props {
    logs: string | ActivityLog[]; // Can be encrypted string or array
    filters?: any;
    users?: any[];
    [key: string]: unknown;
}

export default function ActivityLog() {
    const pageProps = usePage<Props>().props;
    const { filters = {}, users = [] } = pageProps;
    
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
                    console.error('❌ Failed to decrypt logs or invalid format');
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
    
    const [status, setStatus] = useState(filters.status || 'all');
    const [user, setUser] = useState(filters.user || 'all');
    const [date, setDate] = useState(filters.date || '');
    const [searchQuery, setSearchQuery] = useState('');
    const [filteredLogs, setFilteredLogs] = useState<ActivityLog[]>([]);
    const [selectedLog, setSelectedLog] = useState<ActivityLog | null>(null);
    const [isDetailOpen, setIsDetailOpen] = useState(false);
    const [sortField, setSortField] = useState<'time' | 'user' | 'action'>('time');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [isRefreshing, setIsRefreshing] = useState(false);

    // Filter logs based on search query
    useEffect(() => {
        let filtered = [...decryptedLogs];
        
        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(log => 
                log.user?.toLowerCase().includes(query) ||
                log.action?.toLowerCase().includes(query) ||
                log.description?.toLowerCase().includes(query) ||
                log.ip_address?.toLowerCase().includes(query)
            );
        }

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
    }, [decryptedLogs, searchQuery, sortField, sortDirection]);

    const handleFilter = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Prepare filter data with Laravel encrypt() helper
        const filterData: Record<string, string> = {};
        
        if (status !== 'all' && status) {
            filterData.status = status;
        }
        if (user !== 'all' && user) {
            filterData.user = user;
        }
        if (date) {
            filterData.date = date;
        }
        
        router.visit('/admin/activity-log', {
            method: 'post',
            data: filterData,
            preserveState: true,
        });
    };

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => {
                setTimeout(() => setIsRefreshing(false), 500);
            }
        });
    };

    const handleExport = () => {
        // Export to CSV
        const headers = ['User', 'Action', 'Status', 'Time', 'IP Address'];
        const csvContent = [
            headers.join(','),
            ...filteredLogs.map(log => [
                `"${log.user || ''}"`,
                `"${log.action || ''}"`,
                `"${log.status || ''}"`,
                `"${log.time || ''}"`,
                `"${log.ip_address || ''}"`,
            ].join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `activity-log-${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
    };

    const handleSort = (field: 'time' | 'user' | 'action') => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('desc');
        }
    };

    const getStatusIcon = (status?: string) => {
        switch (status) {
            case 'success':
                return <CheckCircle2 className="w-4 h-4" />;
            case 'error':
                return <AlertCircle className="w-4 h-4" />;
            case 'warning':
                return <AlertTriangle className="w-4 h-4" />;
            case 'info':
                return <Info className="w-4 h-4" />;
            default:
                return <Activity className="w-4 h-4" />;
        }
    };

    const getStatusBadge = (status?: string) => {
        switch (status) {
            case 'success':
                return <Badge variant="default" className="bg-green-600 hover:bg-green-700 gap-1">
                    {getStatusIcon(status)} Success
                </Badge>;
            case 'error':
                return <Badge variant="destructive" className="gap-1">
                    {getStatusIcon(status)} Error
                </Badge>;
            case 'warning':
                return <Badge variant="default" className="bg-yellow-600 hover:bg-yellow-700 gap-1">
                    {getStatusIcon(status)} Warning
                </Badge>;
            case 'info':
                return <Badge variant="secondary" className="gap-1">
                    {getStatusIcon(status)} Info
                </Badge>;
            default:
                return <Badge variant="outline" className="gap-1">
                    {getStatusIcon(status)} -
                </Badge>;
        }
    };

    const SortIcon = ({ field }: { field: 'time' | 'user' | 'action' }) => {
        if (sortField !== field) return null;
        return sortDirection === 'asc' ? 
            <ChevronUp className="w-4 h-4 inline ml-1" /> : 
            <ChevronDown className="w-4 h-4 inline ml-1" />;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Activity Log" />
            <ContentCard>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h2 className="text-2xl font-bold flex items-center gap-2">
                            <Activity className="w-6 h-6" />
                            Activity Log
                        </h2>
                        <p className="text-sm text-muted-foreground mt-1">
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
                            <RefreshCw className={`w-4 h-4 mr-2 ${isRefreshing ? 'animate-spin' : ''}`} />
                            Refresh
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm"
                            onClick={handleExport}
                            disabled={filteredLogs.length === 0}
                        >
                            <Download className="w-4 h-4 mr-2" />
                            Export CSV
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <form className="flex flex-wrap gap-3 mb-6 p-4 bg-muted/30 rounded-lg" onSubmit={handleFilter}>
                    <div className="flex-1 min-w-[200px]">
                        <label className="block text-xs font-medium mb-1.5 flex items-center gap-1">
                            <Search className="w-3 h-3" />
                            Search
                        </label>
                        <Input 
                            type="text" 
                            placeholder="Cari user, action, IP..." 
                            value={searchQuery}
                            onChange={e => setSearchQuery(e.target.value)}
                            className="h-9"
                        />
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1.5 flex items-center gap-1">
                            <Activity className="w-3 h-3" />
                            Status
                        </label>
                        <Select value={status} onValueChange={setStatus}>
                            <SelectTrigger className="w-36 h-9">
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
                        <label className="block text-xs font-medium mb-1.5 flex items-center gap-1">
                            <UserIcon className="w-3 h-3" />
                            User
                        </label>
                        <Select value={user} onValueChange={setUser}>
                            <SelectTrigger className="w-44 h-9">
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua User</SelectItem>
                                {users && users.map(u => (
                                    <SelectItem key={u.id} value={u.id+''}>{u.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1.5 flex items-center gap-1">
                            <Calendar className="w-3 h-3" />
                            Tanggal
                        </label>
                        <Input 
                            type="date" 
                            value={date} 
                            onChange={e => setDate(e.target.value)} 
                            className="w-40 h-9" 
                        />
                    </div>
                    <div className="flex items-end">
                        <Button type="submit" size="sm" className="h-9">
                            Apply Filter
                        </Button>
                    </div>
                </form>

                {/* Results count */}
                <div className="mb-3 text-sm text-muted-foreground">
                    Menampilkan <span className="font-semibold text-foreground">{filteredLogs.length}</span> dari {decryptedLogs.length} log
                </div>

                {/* Table */}
                <div className="overflow-x-auto border rounded-lg">
                    <table className="min-w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th 
                                    className="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-muted/70 transition-colors"
                                    onClick={() => handleSort('user')}
                                >
                                    <div className="flex items-center gap-1">
                                        <UserIcon className="w-4 h-4" />
                                        User
                                        <SortIcon field="user" />
                                    </div>
                                </th>
                                <th 
                                    className="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-muted/70 transition-colors"
                                    onClick={() => handleSort('action')}
                                >
                                    <div className="flex items-center gap-1">
                                        <Activity className="w-4 h-4" />
                                        Action
                                        <SortIcon field="action" />
                                    </div>
                                </th>
                                <th className="px-4 py-3 text-left font-semibold">
                                    <div className="flex items-center gap-1">
                                        <AlertCircle className="w-4 h-4" />
                                        Status
                                    </div>
                                </th>
                                <th className="px-4 py-3 text-left font-semibold">
                                    IP Address
                                </th>
                                <th 
                                    className="px-4 py-3 text-left font-semibold cursor-pointer hover:bg-muted/70 transition-colors"
                                    onClick={() => handleSort('time')}
                                >
                                    <div className="flex items-center gap-1">
                                        <Clock className="w-4 h-4" />
                                        Time
                                        <SortIcon field="time" />
                                    </div>
                                </th>
                                <th className="px-4 py-3 text-center font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredLogs.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-12 text-center">
                                        <div className="flex flex-col items-center gap-2 text-muted-foreground">
                                            <Activity className="w-12 h-12 opacity-20" />
                                            <p className="font-medium">Tidak ada log ditemukan</p>
                                            <p className="text-xs">Coba ubah filter atau kriteria pencarian</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                filteredLogs.map((log, index) => (
                                    <tr 
                                        key={log.id} 
                                        className={`border-t hover:bg-muted/30 transition-colors ${
                                            index % 2 === 0 ? 'bg-background' : 'bg-muted/10'
                                        }`}
                                    >
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold text-xs">
                                                    {log.user?.charAt(0).toUpperCase() || '?'}
                                                </div>
                                                <div className="font-medium">{log.user || 'Unknown'}</div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{log.action}</div>
                                            {log.description && (
                                                <div className="text-xs text-muted-foreground mt-0.5 line-clamp-1">
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
                                                <Eye className="w-4 h-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </ContentCard>

            {/* Detail Dialog */}
            <Dialog open={isDetailOpen} onOpenChange={setIsDetailOpen}>
                <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Activity className="w-5 h-5" />
                            Activity Detail
                        </DialogTitle>
                        <DialogDescription>
                            Informasi lengkap tentang aktivitas ini
                        </DialogDescription>
                    </DialogHeader>
                    
                    {selectedLog && (
                        <div className="space-y-4 mt-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">User</label>
                                    <div className="mt-1 flex items-center gap-2">
                                        <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-semibold">
                                            {selectedLog.user?.charAt(0).toUpperCase() || '?'}
                                        </div>
                                        <div>
                                            <div className="font-medium">{selectedLog.user || 'Unknown'}</div>
                                            {selectedLog.user_id && (
                                                <div className="text-xs text-muted-foreground">ID: {selectedLog.user_id}</div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">Status</label>
                                    <div className="mt-1">
                                        {getStatusBadge(selectedLog.status)}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label className="text-xs font-medium text-muted-foreground">Action</label>
                                <div className="mt-1 p-3 bg-muted/30 rounded-lg font-medium">
                                    {selectedLog.action}
                                </div>
                            </div>

                            {selectedLog.description && (
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">Description</label>
                                    <div className="mt-1 p-3 bg-muted/30 rounded-lg text-sm">
                                        {selectedLog.description}
                                    </div>
                                </div>
                            )}

                            <div className="grid grid-cols-2 gap-4">
                                {selectedLog.ip_address && (
                                    <div>
                                        <label className="text-xs font-medium text-muted-foreground">IP Address</label>
                                        <div className="mt-1 p-2 bg-muted/30 rounded font-mono text-sm">
                                            {selectedLog.ip_address}
                                        </div>
                                    </div>
                                )}
                                
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">Timestamp</label>
                                    <div className="mt-1 p-2 bg-muted/30 rounded text-sm">
                                        {selectedLog.time}
                                    </div>
                                </div>
                            </div>

                            {selectedLog.user_agent && (
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">User Agent</label>
                                    <div className="mt-1 p-3 bg-muted/30 rounded-lg text-xs font-mono break-all">
                                        {selectedLog.user_agent}
                                    </div>
                                </div>
                            )}

                            {selectedLog.properties && Object.keys(selectedLog.properties).length > 0 && (
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">Additional Data</label>
                                    <div className="mt-1 p-3 bg-muted/30 rounded-lg text-xs font-mono">
                                        <pre className="overflow-x-auto">
                                            {JSON.stringify(selectedLog.properties, null, 2)}
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
