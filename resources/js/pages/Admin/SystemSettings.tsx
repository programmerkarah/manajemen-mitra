import React from 'react';

import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';


const API_URL = '/admin/system-settings/maintenance';

export default function SystemSettings() {
    const { maintenance: initialMaintenance, message: initialMessage } = usePage().props as { maintenance: boolean, message: string };
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
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ enabled: !maintenance, message: editMessage }),
            });
            const data = await res.json();
            setMaintenance(data.maintenance);
            setMessage(data.message || '');
            setShowSaved(true);
            setTimeout(() => setShowSaved(false), 1200);
        } finally {
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

    return (
        <AppLayout>
            <Head title="System Settings" />
            <ContentCard padding="lg">
                <h2 className="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Pengaturan Sistem</h2>
                <div className="flex items-center gap-4 mb-6">
                    <span className="font-medium text-gray-700 dark:text-gray-200">Status Server:</span>
                    <span className={maintenance ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 px-3 py-1 rounded-full text-sm font-semibold' : 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 px-3 py-1 rounded-full text-sm font-semibold'}>
                        {maintenance ? 'Aktif (Maintenance)' : 'Normal'}
                    </span>
                    <Button onClick={handleToggleMaintenance} variant={maintenance ? 'destructive' : 'default'} disabled={loading}>
                        {loading ? 'Memproses...' : maintenance ? 'Nonaktifkan Maintenance' : 'Aktifkan Maintenance'}
                    </Button>
                </div>
                <div className="mb-6">
                    <label className="block font-medium mb-1 text-gray-700 dark:text-gray-200">Pesan Maintenance (opsional):</label>
                    <Textarea
                        className="w-full min-h-[80px]"
                        value={editMessage}
                        onChange={e => setEditMessage(e.target.value)}
                        maxLength={500}
                        placeholder="Pesan yang akan ditampilkan saat maintenance..."
                        disabled={loading || saving}
                    />
                    <div className="flex gap-2 mt-2 items-center">
                        <Button onClick={handleSaveMessage} disabled={saving || loading} size="sm">
                            {saving ? 'Menyimpan...' : 'Simpan Pesan'}
                        </Button>
                        <span className="text-xs text-gray-500 dark:text-gray-400">{editMessage.length}/500 karakter</span>
                        {showSaved && <span className="text-green-500 text-xs ml-2">Tersimpan!</span>}
                    </div>
                </div>
                {message && (
                    <div className="bg-blue-50 dark:bg-blue-900/30 rounded p-3 text-sm text-blue-900 dark:text-blue-200 border border-blue-200 dark:border-blue-700">
                        <strong>Pesan Aktif:</strong> {message}
                    </div>
                )}
            </ContentCard>
        </AppLayout>
    );
}
