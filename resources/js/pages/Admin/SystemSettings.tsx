import React from 'react';

import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { BreadcrumbItem } from '@/types';
import { 
    Server, 
    Power, 
    PowerOff, 
    AlertTriangle, 
    CheckCircle2,
    Copy,
    ExternalLink,
    Info,
    Shield,
    Settings,
    Clock
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '/admin/dashboard' },
    { title: 'Pengaturan Sistem', href: '/admin/system-settings' },
];


const API_URL = '/admin/system-settings/maintenance';

interface SystemSettingsProps {
    maintenance: boolean;
    message: string;
    [key: string]: unknown;
}

export default function SystemSettings() {
    const { maintenance: initialMaintenance, message: initialMessage } = usePage<SystemSettingsProps>().props;
    const [maintenance, setMaintenance] = React.useState(initialMaintenance);
    const [loading, setLoading] = React.useState(false);
    const [message, setMessage] = React.useState(initialMessage || '');
    const [editMessage, setEditMessage] = React.useState(initialMessage || '');
    const [saving, setSaving] = React.useState(false);
    const [showSaved, setShowSaved] = React.useState(false);

    function getCsrfToken() {
        if (typeof document !== 'undefined') {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta?.getAttribute('content') || '';
        }
        return '';
    }

    const handleToggleMaintenance = async () => {
        setLoading(true);
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ enabled: !maintenance, message: editMessage }),
            });

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            const data = await res.json();
            
            // If enabling maintenance, redirect to dashboard after cookie is set
            if (data.maintenance) {
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 300);
            } else {
                // If disabling maintenance, just update state
                setMaintenance(data.maintenance);
                setMessage(data.message || '');
                setShowSaved(true);
                setTimeout(() => setShowSaved(false), 1200);
                setLoading(false);
            }
        } catch (error) {
            console.error('Failed to toggle maintenance:', error);
            alert('Gagal mengubah status maintenance. Silakan coba lagi.');
            setLoading(false);
        }
    };

    const handleSaveMessage = async () => {
        setSaving(true);
        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ enabled: maintenance, message: editMessage }),
            });
            const data = await res.json();
            setMessage(data.message || '');
            setShowSaved(true);
            setTimeout(() => setShowSaved(false), 1200);
        } finally {
            setSaving(false);
        }
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        setShowSaved(true);
        setTimeout(() => setShowSaved(false), 1200);
    };

    const bypassUrl = `${window.location.origin}/bypass`;
    const upUrl = `${window.location.origin}/up`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Settings" />
            
            {/* Header Section */}
            <div className="mb-6">
                <div className="flex items-center gap-3 mb-2">
                    <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                        <Settings className="w-6 h-6 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-3xl font-bold">Pengaturan Sistem</h1>
                        <p className="text-sm text-muted-foreground mt-1">
                            Kelola status server dan maintenance mode
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid gap-6">
                {/* Status Server Card */}
                <ContentCard>
                    <div className="flex items-start justify-between mb-6">
                        <div className="flex items-center gap-3">
                            <div className={`w-14 h-14 rounded-xl flex items-center justify-center ${
                                maintenance 
                                    ? 'bg-red-100 dark:bg-red-900/30' 
                                    : 'bg-green-100 dark:bg-green-900/30'
                            }`}>
                                {maintenance ? (
                                    <PowerOff className="w-7 h-7 text-red-600 dark:text-red-400" />
                                ) : (
                                    <Power className="w-7 h-7 text-green-600 dark:text-green-400" />
                                )}
                            </div>
                            <div>
                                <h2 className="text-xl font-bold flex items-center gap-2">
                                    <Server className="w-5 h-5" />
                                    Status Server
                                </h2>
                                <p className="text-sm text-muted-foreground mt-1">
                                    Mode operasi aplikasi saat ini
                                </p>
                            </div>
                        </div>
                        {maintenance ? (
                            <Badge variant="destructive" className="gap-2 px-4 py-2 text-base">
                                <AlertTriangle className="w-4 h-4" />
                                Maintenance Mode
                            </Badge>
                        ) : (
                            <Badge variant="default" className="bg-green-600 hover:bg-green-700 gap-2 px-4 py-2 text-base">
                                <CheckCircle2 className="w-4 h-4" />
                                Server Normal
                            </Badge>
                        )}
                    </div>

                    <div className={`p-4 rounded-lg border-l-4 mb-6 ${
                        maintenance 
                            ? 'bg-red-50 dark:bg-red-900/20 border-red-500' 
                            : 'bg-green-50 dark:bg-green-900/20 border-green-500'
                    }`}>
                        <div className="flex items-start gap-3">
                            <Info className={`w-5 h-5 mt-0.5 ${
                                maintenance ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'
                            }`} />
                            <div className="flex-1">
                                <p className={`font-semibold mb-1 ${
                                    maintenance ? 'text-red-900 dark:text-red-200' : 'text-green-900 dark:text-green-200'
                                }`}>
                                    {maintenance ? 'Mode Maintenance Aktif' : 'Server Beroperasi Normal'}
                                </p>
                                <p className={`text-sm ${
                                    maintenance ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300'
                                }`}>
                                    {maintenance 
                                        ? 'Aplikasi tidak dapat diakses oleh user umum. Hanya admin dengan bypass dapat mengakses.'
                                        : 'Semua user dapat mengakses aplikasi secara normal.'
                                    }
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex gap-3">
                        <Button 
                            onClick={handleToggleMaintenance} 
                            variant={maintenance ? 'default' : 'destructive'} 
                            disabled={loading}
                            size="lg"
                            className="flex-1"
                        >
                            {loading ? (
                                <>
                                    <Clock className="w-4 h-4 mr-2 animate-spin" />
                                    Memproses...
                                </>
                            ) : maintenance ? (
                                <>
                                    <Power className="w-4 h-4 mr-2" />
                                    Nonaktifkan Maintenance
                                </>
                            ) : (
                                <>
                                    <PowerOff className="w-4 h-4 mr-2" />
                                    Aktifkan Maintenance
                                </>
                            )}
                        </Button>
                    </div>
                </ContentCard>

                {/* Maintenance Message Card */}
                <ContentCard>
                    <div className="mb-4">
                        <h2 className="text-xl font-bold flex items-center gap-2 mb-2">
                            <AlertTriangle className="w-5 h-5" />
                            Pesan Maintenance
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Pesan yang akan ditampilkan kepada user saat mode maintenance aktif
                        </p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium mb-2">
                                Isi Pesan (opsional, maks 500 karakter)
                            </label>
                            <Textarea
                                className="w-full min-h-[120px] resize-none"
                                value={editMessage}
                                onChange={e => setEditMessage(e.target.value)}
                                maxLength={500}
                                placeholder="Contoh: Aplikasi sedang dalam maintenance untuk peningkatan sistem. Akan aktif kembali pukul 14:00 WIB."
                                disabled={loading || saving}
                            />
                            <div className="flex justify-between items-center mt-2">
                                <span className="text-xs text-muted-foreground">
                                    {editMessage.length}/500 karakter
                                </span>
                                {showSaved && (
                                    <span className="text-green-600 dark:text-green-400 text-sm flex items-center gap-1 animate-in fade-in">
                                        <CheckCircle2 className="w-4 h-4" />
                                        Tersimpan!
                                    </span>
                                )}
                            </div>
                        </div>

                        <Button 
                            onClick={handleSaveMessage} 
                            disabled={saving || loading}
                            className="w-full sm:w-auto"
                        >
                            {saving ? (
                                <>
                                    <Clock className="w-4 h-4 mr-2 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <CheckCircle2 className="w-4 h-4 mr-2" />
                                    Simpan Pesan
                                </>
                            )}
                        </Button>

                        {message && (
                            <div className="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                <div className="flex items-start gap-2">
                                    <Info className="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
                                    <div className="flex-1">
                                        <p className="font-semibold text-blue-900 dark:text-blue-200 mb-1">
                                            Pesan yang Aktif:
                                        </p>
                                        <p className="text-sm text-blue-800 dark:text-blue-300">
                                            {message}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </ContentCard>

                {/* Bypass Access Card - Only show when maintenance is ON */}
                {maintenance && (
                    <ContentCard>
                        <div className="mb-4">
                            <h2 className="text-xl font-bold flex items-center gap-2 mb-2">
                                <Shield className="w-5 h-5" />
                                Akses Bypass Maintenance
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                URL khusus untuk admin bypass maintenance mode
                            </p>
                        </div>

                        <div className="space-y-4">
                            <div className="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                                <div className="flex items-start gap-2">
                                    <AlertTriangle className="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" />
                                    <div className="flex-1">
                                        <p className="font-semibold text-yellow-900 dark:text-yellow-200 mb-1">
                                            Penting!
                                        </p>
                                        <p className="text-sm text-yellow-800 dark:text-yellow-300">
                                            Jaga kerahasiaan URL bypass ini. Siapapun dengan akses ke URL ini dapat bypass maintenance mode.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-3">
                                {/* Bypass URL */}
                                <div>
                                    <label className="block text-sm font-medium mb-2">
                                        URL Bypass (masuk dengan bypass key)
                                    </label>
                                    <div className="flex gap-2">
                                        <div className="flex-1 px-3 py-2 bg-muted rounded-lg border font-mono text-sm break-all">
                                            {bypassUrl}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => copyToClipboard(bypassUrl)}
                                            title="Copy URL"
                                        >
                                            <Copy className="w-4 h-4" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.open(bypassUrl, '_blank')}
                                            title="Buka di tab baru"
                                        >
                                            <ExternalLink className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>

                                {/* Up URL */}
                                <div>
                                    <label className="block text-sm font-medium mb-2">
                                        URL Up (nonaktifkan maintenance dengan admin key)
                                    </label>
                                    <div className="flex gap-2">
                                        <div className="flex-1 px-3 py-2 bg-muted rounded-lg border font-mono text-sm break-all">
                                            {upUrl}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => copyToClipboard(upUrl)}
                                            title="Copy URL"
                                        >
                                            <Copy className="w-4 h-4" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.open(upUrl, '_blank')}
                                            title="Buka di tab baru"
                                        >
                                            <ExternalLink className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <div className="bg-muted/50 rounded-lg p-4 text-sm">
                                <p className="font-medium mb-2">Cara Penggunaan:</p>
                                <ol className="list-decimal list-inside space-y-1 text-muted-foreground">
                                    <li>Buka URL bypass di browser</li>
                                    <li>Masukkan bypass key yang telah dikonfigurasi di .env</li>
                                    <li>Setelah berhasil, Anda dapat mengakses aplikasi secara normal</li>
                                    <li>URL /up dapat digunakan untuk nonaktifkan maintenance langsung</li>
                                </ol>
                            </div>
                        </div>
                    </ContentCard>
                )}
            </div>
        </AppLayout>
    );
}
