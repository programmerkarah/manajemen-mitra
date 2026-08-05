import React from 'react';

import { ContentCard } from '@/components/content-card';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Copy,
    ExternalLink,
    Info,
    Power,
    PowerOff,
    Server,
    Settings,
    Shield,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '/admin/dashboard' },
    { title: 'Pengaturan Sistem', href: '/admin/system-settings' },
];

const API_URL = '/admin/system-settings/maintenance';
const SSO_SYNC_API_URL = '/admin/system-settings/sso-sync';
const FEATURE_TOGGLE_API_URL = '/admin/system-settings/feature-toggle';
const DEADLINE_RULE_API_URL = '/admin/system-settings/deadline-rule';
const DEADLINE_BYPASS_API_URL = '/admin/system-settings/deadline-bypass';
const DEADLINE_BYPASS_REQUEST_APPROVE_API_URL =
    '/admin/system-settings/deadline-bypass-request';

const DEADLINE_DAY_OPTIONS = Array.from({ length: 31 }, (_, index) => {
    const day = index + 1;

    return {
        label: `Tanggal ${day}`,
        value: String(day),
    };
});

interface FeatureToggleItem {
    key: string;
    label: string;
    description: string | null;
    enabled: boolean;
    sort_order: number;
}

interface DeadlineRuleItem {
    id: number;
    key: string;
    feature_key: string;
    action_key: string;
    label: string;
    description: string | null;
    deadline_at: string | null;
    cutoff_day: number | null;
    is_enforced: boolean;
    allow_manual_bypass: boolean;
    scope_type: string;
    sort_order: number;
}

interface DeadlineBypassItem {
    id: number;
    rule_key: string | null;
    rule_label: string | null;
    approved_by: string | null;
    granted_for: string | null;
    year: number | null;
    month: number | null;
    uses_count: number;
    max_uses: number;
    is_active: boolean;
    expires_at: string | null;
    created_at: string | null;
}

interface DeadlineBypassRequestItem {
    id: number;
    rule_key: string | null;
    rule_label: string | null;
    requested_by: string | null;
    reviewed_by: string | null;
    kegiatan_id: number | null;
    periode_alokasi_id: number | null;
    year: number | null;
    month: number | null;
    reason: string | null;
    status: 'pending' | 'approved' | 'rejected';
    route_name: string | null;
    http_method: string | null;
    target_url: string | null;
    max_uses: number;
    expires_at: string | null;
    review_note: string | null;
    reviewed_at: string | null;
    created_at: string | null;
}

interface SystemSettingsProps {
    maintenance: boolean;
    message: string;
    sso_sync_enabled: boolean;
    session_lifetime: number;
    feature_toggles: FeatureToggleItem[];
    deadline_rules: DeadlineRuleItem[];
    deadline_bypasses: DeadlineBypassItem[];
    deadline_bypass_requests: DeadlineBypassRequestItem[];
    deadline_storage_ready: boolean;
    [key: string]: unknown;
}

export default function SystemSettings() {
    const {
        maintenance: initialMaintenance,
        message: initialMessage,
        sso_sync_enabled: initialSsoSyncEnabled,
        session_lifetime: sessionLifetime,
        feature_toggles: initialFeatureToggles,
        deadline_rules: initialDeadlineRules,
        deadline_bypasses: initialDeadlineBypasses,
        deadline_bypass_requests: initialDeadlineBypassRequests,
        deadline_storage_ready: deadlineStorageReady,
    } = usePage<SystemSettingsProps>().props;
    const [maintenance, setMaintenance] = React.useState(initialMaintenance);
    const [loading, setLoading] = React.useState(false);
    const [message, setMessage] = React.useState(initialMessage || '');
    const [editMessage, setEditMessage] = React.useState(initialMessage || '');
    const [saving, setSaving] = React.useState(false);
    const [ssoSyncEnabled, setSsoSyncEnabled] = React.useState(
        initialSsoSyncEnabled,
    );
    const [ssoSyncSaving, setSsoSyncSaving] = React.useState(false);
    const [featureToggles, setFeatureToggles] = React.useState(
        [...initialFeatureToggles].sort((left, right) => {
            if (left.sort_order !== right.sort_order) {
                return left.sort_order - right.sort_order;
            }

            return left.label.localeCompare(right.label);
        }),
    );
    const [featureToggleSavingKey, setFeatureToggleSavingKey] = React.useState<
        string | null
    >(null);
    const [deadlineRules, setDeadlineRules] = React.useState(
        [...initialDeadlineRules].sort(
            (left, right) => left.sort_order - right.sort_order,
        ),
    );
    const [deadlineBypasses] = React.useState(initialDeadlineBypasses);
    const [deadlineBypassRequests, setDeadlineBypassRequests] = React.useState(
        initialDeadlineBypassRequests,
    );
    const [deadlineSavingKey, setDeadlineSavingKey] = React.useState<
        string | null
    >(null);
    const [bypassForm, setBypassForm] = React.useState({
        rule_key: initialDeadlineRules[0]?.key ?? 'alokasi.manage',
        year: String(new Date().getFullYear()),
        month: String(new Date().getMonth() + 1),
        max_uses: '1',
        expires_at: '',
        reason: '',
    });
    const [bypassSaving, setBypassSaving] = React.useState(false);
    const [requestActionLoadingId, setRequestActionLoadingId] = React.useState<
        number | null
    >(null);
    const [showSaved, setShowSaved] = React.useState(false);
    const [modalAlert, setModalAlert] = React.useState<{
        open: boolean;
        title: string;
        message: string;
    }>({
        open: false,
        title: '',
        message: '',
    });

    const showModalAlert = (title: string, message: string) => {
        setModalAlert({
            open: true,
            title,
            message,
        });
    };

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
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    enabled: !maintenance,
                    message: editMessage,
                }),
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
            showModalAlert(
                'Aksi Gagal',
                'Gagal mengubah status maintenance. Silakan coba lagi.',
            );
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
                body: JSON.stringify({
                    enabled: maintenance,
                    message: editMessage,
                }),
            });
            const data = await res.json();
            setMessage(data.message || '');
            setShowSaved(true);
            setTimeout(() => setShowSaved(false), 1200);
        } finally {
            setSaving(false);
        }
    };

    const handleToggleSsoSync = async () => {
        setSsoSyncSaving(true);
        try {
            const res = await fetch(SSO_SYNC_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    enabled: !ssoSyncEnabled,
                }),
            });

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            const data = await res.json();
            setSsoSyncEnabled(Boolean(data.enabled));
            setShowSaved(true);
            setTimeout(() => setShowSaved(false), 1200);
        } catch (error) {
            console.error('Failed to toggle SSO sync:', error);
            showModalAlert(
                'Aksi Gagal',
                'Gagal memperbarui pengaturan SSO Sync. Silakan coba lagi.',
            );
        } finally {
            setSsoSyncSaving(false);
        }
    };

    const handleToggleFeature = async (feature: FeatureToggleItem) => {
        setFeatureToggleSavingKey(feature.key);

        try {
            const res = await fetch(FEATURE_TOGGLE_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    key: feature.key,
                    enabled: !feature.enabled,
                }),
            });

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            const data = await res.json();
            const updatedFeature = data.feature_toggle as FeatureToggleItem;

            setFeatureToggles((current) =>
                current
                    .map((item) =>
                        item.key === updatedFeature.key ? updatedFeature : item,
                    )
                    .sort((left, right) => {
                        if (left.sort_order !== right.sort_order) {
                            return left.sort_order - right.sort_order;
                        }

                        return left.label.localeCompare(right.label);
                    }),
            );

            showModalAlert(
                'Pengaturan Disimpan',
                `Fitur ${updatedFeature.label} berhasil ${updatedFeature.enabled ? 'diaktifkan' : 'dinonaktifkan'}.`,
            );
        } catch (error) {
            console.error('Failed to toggle feature:', error);
            showModalAlert(
                'Aksi Gagal',
                'Gagal mengubah status fitur. Silakan coba lagi.',
            );
        } finally {
            setFeatureToggleSavingKey(null);
        }
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        setShowSaved(true);
        setTimeout(() => setShowSaved(false), 1200);
    };

    const handleSaveDeadlineRule = async (rule: DeadlineRuleItem) => {
        if (!deadlineStorageReady) {
            showModalAlert(
                'Storage Deadline Belum Aktif',
                'Tabel deadline belum tersedia. Jalankan migrasi terlebih dahulu agar perubahan dapat disimpan.',
            );

            return;
        }

        if (!rule.cutoff_day || rule.cutoff_day < 1 || rule.cutoff_day > 31) {
            showModalAlert(
                'Tanggal Belum Valid',
                `Tanggal deadline untuk ${rule.label} harus diisi antara 1 sampai 31.`,
            );

            return;
        }

        setDeadlineSavingKey(rule.key);

        try {
            const res = await fetch(DEADLINE_RULE_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    key: rule.key,
                    cutoff_day: rule.cutoff_day,
                }),
            });

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            showModalAlert(
                'Pengaturan Disimpan',
                `Batas waktu ${rule.label} diperbarui.`,
            );
        } catch (error) {
            console.error('Failed to save deadline rule:', error);
            showModalAlert(
                'Aksi Gagal',
                'Gagal menyimpan batas waktu. Silakan coba lagi.',
            );
        } finally {
            setDeadlineSavingKey(null);
        }
    };

    const handleGrantBypass = async () => {
        if (!deadlineStorageReady) {
            showModalAlert(
                'Storage Deadline Belum Aktif',
                'Tabel deadline belum tersedia. Jalankan migrasi terlebih dahulu agar bypass dapat dibuat.',
            );

            return;
        }

        setBypassSaving(true);

        try {
            const res = await fetch(DEADLINE_BYPASS_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    rule_key: bypassForm.rule_key,
                    year: bypassForm.year ? Number(bypassForm.year) : null,
                    month: bypassForm.month ? Number(bypassForm.month) : null,
                    max_uses: Number(bypassForm.max_uses || '1'),
                    expires_at: bypassForm.expires_at || null,
                    reason: bypassForm.reason || null,
                }),
            });

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            window.location.reload();
        } catch (error) {
            console.error('Failed to grant bypass:', error);
            showModalAlert(
                'Aksi Gagal',
                'Gagal membuat bypass batas waktu. Silakan coba lagi.',
            );
        } finally {
            setBypassSaving(false);
        }
    };

    const handleApproveBypassRequest = async (requestId: number) => {
        setRequestActionLoadingId(requestId);

        try {
            const res = await fetch(
                `${DEADLINE_BYPASS_REQUEST_APPROVE_API_URL}/${requestId}/approve`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({}),
                },
            );

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            setDeadlineBypassRequests((current) =>
                current.map((item) =>
                    item.id === requestId
                        ? {
                              ...item,
                              status: 'approved',
                              reviewed_by: 'Admin',
                              reviewed_at: new Date().toISOString(),
                          }
                        : item,
                ),
            );

            showModalAlert(
                'Request Disetujui',
                'Request bypass berhasil disetujui dan bypass aktif sudah dibuat.',
            );
        } catch (error) {
            console.error('Failed to approve bypass request:', error);
            showModalAlert(
                'Aksi Gagal',
                'Gagal menyetujui request bypass. Silakan coba lagi.',
            );
        } finally {
            setRequestActionLoadingId(null);
        }
    };

    const handleRejectBypassRequest = async (requestId: number) => {
        setRequestActionLoadingId(requestId);

        try {
            const res = await fetch(
                `${DEADLINE_BYPASS_REQUEST_APPROVE_API_URL}/${requestId}/reject`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        review_note: 'Permintaan bypass ditolak oleh admin.',
                    }),
                },
            );

            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }

            setDeadlineBypassRequests((current) =>
                current.map((item) =>
                    item.id === requestId
                        ? {
                              ...item,
                              status: 'rejected',
                              reviewed_by: 'Admin',
                              reviewed_at: new Date().toISOString(),
                              review_note:
                                  'Permintaan bypass ditolak oleh admin.',
                          }
                        : item,
                ),
            );

            showModalAlert(
                'Request Ditolak',
                'Request bypass berhasil ditolak.',
            );
        } catch (error) {
            console.error('Failed to reject bypass request:', error);
            showModalAlert(
                'Aksi Gagal',
                'Gagal menolak request bypass. Silakan coba lagi.',
            );
        } finally {
            setRequestActionLoadingId(null);
        }
    };

    const bypassUrl = `${window.location.origin}/bypass`;
    const upUrl = `${window.location.origin}/up`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="System Settings" />

            <Dialog
                open={modalAlert.open}
                onOpenChange={(open) =>
                    setModalAlert((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{modalAlert.title}</DialogTitle>
                        <DialogDescription>
                            {modalAlert.message}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() =>
                                setModalAlert((prev) => ({
                                    ...prev,
                                    open: false,
                                }))
                            }
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Header Section */}
            <div className="mb-6">
                <div className="mb-2 flex items-center gap-3">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                        <Settings className="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 className="text-3xl font-bold">
                            Pengaturan Sistem
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola status server dan maintenance mode
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid gap-6">
                {/* Status Server Card */}
                <ContentCard>
                    <div className="mb-6 flex items-start justify-between">
                        <div className="flex items-center gap-3">
                            <div
                                className={`flex h-14 w-14 items-center justify-center rounded-xl ${
                                    maintenance
                                        ? 'bg-red-100 dark:bg-red-900/30'
                                        : 'bg-green-100 dark:bg-green-900/30'
                                }`}
                            >
                                {maintenance ? (
                                    <PowerOff className="h-7 w-7 text-red-600 dark:text-red-400" />
                                ) : (
                                    <Power className="h-7 w-7 text-green-600 dark:text-green-400" />
                                )}
                            </div>
                            <div>
                                <h2 className="flex items-center gap-2 text-xl font-bold">
                                    <Server className="h-5 w-5" />
                                    Status Server
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Mode operasi aplikasi saat ini
                                </p>
                            </div>
                        </div>
                        {maintenance ? (
                            <Badge
                                variant="destructive"
                                className="gap-2 px-4 py-2 text-base"
                            >
                                <AlertTriangle className="h-4 w-4" />
                                Maintenance Mode
                            </Badge>
                        ) : (
                            <Badge
                                variant="default"
                                className="gap-2 bg-green-600 px-4 py-2 text-base hover:bg-green-700"
                            >
                                <CheckCircle2 className="h-4 w-4" />
                                Server Normal
                            </Badge>
                        )}
                    </div>

                    <div
                        className={`mb-6 rounded-lg border-l-4 p-4 ${
                            maintenance
                                ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                                : 'border-green-500 bg-green-50 dark:bg-green-900/20'
                        }`}
                    >
                        <div className="flex items-start gap-3">
                            <Info
                                className={`mt-0.5 h-5 w-5 ${
                                    maintenance
                                        ? 'text-red-600 dark:text-red-400'
                                        : 'text-green-600 dark:text-green-400'
                                }`}
                            />
                            <div className="flex-1">
                                <p
                                    className={`mb-1 font-semibold ${
                                        maintenance
                                            ? 'text-red-900 dark:text-red-200'
                                            : 'text-green-900 dark:text-green-200'
                                    }`}
                                >
                                    {maintenance
                                        ? 'Mode Maintenance Aktif'
                                        : 'Server Beroperasi Normal'}
                                </p>
                                <p
                                    className={`text-sm ${
                                        maintenance
                                            ? 'text-red-700 dark:text-red-300'
                                            : 'text-green-700 dark:text-green-300'
                                    }`}
                                >
                                    {maintenance
                                        ? 'Aplikasi tidak dapat diakses oleh user umum. Hanya admin dengan bypass dapat mengakses.'
                                        : 'Semua user dapat mengakses aplikasi secara normal.'}
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
                                    <Clock className="mr-2 h-4 w-4 animate-spin" />
                                    Memproses...
                                </>
                            ) : maintenance ? (
                                <>
                                    <Power className="mr-2 h-4 w-4" />
                                    Nonaktifkan Maintenance
                                </>
                            ) : (
                                <>
                                    <PowerOff className="mr-2 h-4 w-4" />
                                    Aktifkan Maintenance
                                </>
                            )}
                        </Button>
                    </div>
                </ContentCard>

                <ContentCard>
                    <div className="mb-6 flex items-start justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                                <Settings className="h-7 w-7 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <h2 className="flex items-center gap-2 text-xl font-bold">
                                    Toggle Fitur
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Aktifkan atau nonaktifkan fitur tanpa
                                    mematikan seluruh aplikasi.
                                </p>
                            </div>
                        </div>
                        <Badge
                            variant="secondary"
                            className="px-4 py-2 text-base"
                        >
                            {
                                featureToggles.filter((item) => item.enabled)
                                    .length
                            }
                            /{featureToggles.length} aktif
                        </Badge>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {featureToggles.map((feature) => (
                            <div
                                key={feature.key}
                                className={`rounded-xl border p-4 transition-colors ${
                                    feature.enabled
                                        ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900/40 dark:bg-emerald-950/20'
                                        : 'border-neutral-200 bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900/40'
                                }`}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-base font-semibold">
                                            {feature.label}
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {feature.description ||
                                                'Tidak ada deskripsi.'}
                                        </p>
                                    </div>
                                    <Badge
                                        variant={
                                            feature.enabled
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {feature.enabled ? 'Aktif' : 'Nonaktif'}
                                    </Badge>
                                </div>

                                <div className="mt-4 flex items-center justify-between gap-3">
                                    <span className="text-xs text-muted-foreground">
                                        Key: {feature.key}
                                    </span>
                                    <Button
                                        size="sm"
                                        variant={
                                            feature.enabled
                                                ? 'outline'
                                                : 'default'
                                        }
                                        onClick={() =>
                                            handleToggleFeature(feature)
                                        }
                                        disabled={
                                            featureToggleSavingKey ===
                                            feature.key
                                        }
                                    >
                                        {featureToggleSavingKey ===
                                        feature.key ? (
                                            <>
                                                <Clock className="mr-2 h-4 w-4 animate-spin" />
                                                Menyimpan...
                                            </>
                                        ) : feature.enabled ? (
                                            'Nonaktifkan'
                                        ) : (
                                            'Aktifkan'
                                        )}
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                </ContentCard>

                <ContentCard>
                    <div className="mb-6 flex items-start justify-between gap-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-14 w-14 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                                <Clock className="h-7 w-7 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <h2 className="text-xl font-bold">
                                    Manajemen Batas Waktu
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Daftar fitur di bawah wajib memiliki tanggal
                                    cutoff bulanan per periode target.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mb-4 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 dark:border-neutral-800 dark:bg-neutral-900/50">
                        <p className="text-sm font-medium text-neutral-800 dark:text-neutral-100">
                            Daftar Fitur Yang Perlu Diatur Deadline
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Contoh: cutoff 25 berarti periode Juli ditutup
                            setelah 25 Juni. Setelah itu user harus request
                            bypass ke admin.
                        </p>
                    </div>

                    {!deadlineStorageReady && (
                        <div className="mb-4 rounded-xl border border-amber-400/50 bg-amber-100/70 px-4 py-3 text-amber-900 dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-100">
                            <p className="text-sm font-semibold">
                                Storage deadline belum aktif
                            </p>
                            <p className="mt-1 text-xs">
                                Daftar fitur sudah ditampilkan dari konfigurasi
                                default, tetapi penyimpanan perubahan deadline
                                membutuhkan migrasi database.
                            </p>
                        </div>
                    )}

                    <div className="space-y-4">
                        {deadlineRules.map((rule) => (
                            <div
                                key={rule.key}
                                className="rounded-xl border border-neutral-200 p-4 dark:border-neutral-800"
                            >
                                <div className="mb-3 flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-base font-semibold">
                                            {rule.label}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {rule.description ||
                                                'Tidak ada deskripsi.'}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Fitur: {rule.feature_key}
                                        </p>
                                    </div>
                                    <Badge variant="default">
                                        Cutoff Periode Bulanan
                                    </Badge>
                                </div>

                                <div className="grid gap-3 md:grid-cols-1">
                                    <div className="space-y-1">
                                        <p className="text-xs font-medium text-muted-foreground">
                                            Tanggal deadline bulanan
                                        </p>
                                        <Select
                                            value={
                                                rule.cutoff_day
                                                    ? String(rule.cutoff_day)
                                                    : ''
                                            }
                                            onValueChange={(value) =>
                                                setDeadlineRules((current) =>
                                                    current.map((item) =>
                                                        item.key === rule.key
                                                            ? {
                                                                  ...item,
                                                                  cutoff_day:
                                                                      Number(
                                                                          value,
                                                                      ),
                                                              }
                                                            : item,
                                                    ),
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full bg-white dark:bg-neutral-900">
                                                <SelectValue placeholder="Pilih tanggal deadline" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {DEADLINE_DAY_OPTIONS.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">
                                            {rule.key === 'alokasi.revisi'
                                                ? 'Khusus revisi: bulan berjalan selalu bisa diproses. Bulan sebelumnya hanya bisa sampai tanggal cutoff di bulan berjalan.'
                                                : 'Periode target akan ditutup pada tanggal ini di bulan sebelumnya. Setelah lewat cutoff, aksi butuh persetujuan admin.'}
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-3 flex justify-end">
                                    <Button
                                        size="sm"
                                        onClick={() =>
                                            handleSaveDeadlineRule(rule)
                                        }
                                        disabled={
                                            !deadlineStorageReady ||
                                            deadlineSavingKey === rule.key
                                        }
                                    >
                                        {deadlineSavingKey === rule.key
                                            ? 'Menyimpan...'
                                            : 'Simpan Rule'}
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mt-6 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                        <p className="mb-3 text-base font-semibold">
                            Request Bypass Dari User
                        </p>
                        <div className="space-y-2">
                            {deadlineBypassRequests.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    Belum ada request bypass.
                                </p>
                            )}
                            {deadlineBypassRequests.map((item) => (
                                <div
                                    key={item.id}
                                    className="rounded-lg border border-neutral-200 px-3 py-3 text-sm dark:border-neutral-800"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-2">
                                        <div className="space-y-1">
                                            <p className="font-semibold">
                                                {item.rule_label ||
                                                    item.rule_key}{' '}
                                                • {item.month || '-'} /{' '}
                                                {item.year || '-'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Pemohon:{' '}
                                                {item.requested_by || '-'}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Alasan: {item.reason || '-'}
                                            </p>
                                        </div>
                                        <Badge
                                            variant={
                                                item.status === 'pending'
                                                    ? 'secondary'
                                                    : item.status === 'approved'
                                                      ? 'default'
                                                      : 'destructive'
                                            }
                                        >
                                            {item.status}
                                        </Badge>
                                    </div>

                                    {item.status === 'pending' && (
                                        <div className="mt-3 flex flex-wrap justify-end gap-2">
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() =>
                                                    handleRejectBypassRequest(
                                                        item.id,
                                                    )
                                                }
                                                disabled={
                                                    requestActionLoadingId ===
                                                    item.id
                                                }
                                            >
                                                Tolak
                                            </Button>
                                            <Button
                                                size="sm"
                                                onClick={() =>
                                                    handleApproveBypassRequest(
                                                        item.id,
                                                    )
                                                }
                                                disabled={
                                                    requestActionLoadingId ===
                                                    item.id
                                                }
                                            >
                                                Setujui
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="mt-6 rounded-xl border border-neutral-200 p-4 dark:border-neutral-800">
                        <p className="mb-3 text-base font-semibold">
                            Grant Bypass Manual
                        </p>
                        <div className="grid gap-3 md:grid-cols-3">
                            <Input
                                value={bypassForm.rule_key}
                                onChange={(event) =>
                                    setBypassForm((current) => ({
                                        ...current,
                                        rule_key: event.target.value,
                                    }))
                                }
                                placeholder="Rule key"
                            />
                            <Input
                                value={bypassForm.year}
                                onChange={(event) =>
                                    setBypassForm((current) => ({
                                        ...current,
                                        year: event.target.value,
                                    }))
                                }
                                placeholder="Tahun"
                            />
                            <Input
                                value={bypassForm.month}
                                onChange={(event) =>
                                    setBypassForm((current) => ({
                                        ...current,
                                        month: event.target.value,
                                    }))
                                }
                                placeholder="Bulan"
                            />
                            <Input
                                value={bypassForm.max_uses}
                                onChange={(event) =>
                                    setBypassForm((current) => ({
                                        ...current,
                                        max_uses: event.target.value,
                                    }))
                                }
                                placeholder="Maksimal pakai"
                            />
                            <Input
                                type="datetime-local"
                                value={bypassForm.expires_at}
                                onChange={(event) =>
                                    setBypassForm((current) => ({
                                        ...current,
                                        expires_at: event.target.value,
                                    }))
                                }
                            />
                        </div>
                        <Textarea
                            className="mt-3"
                            value={bypassForm.reason}
                            onChange={(event) =>
                                setBypassForm((current) => ({
                                    ...current,
                                    reason: event.target.value,
                                }))
                            }
                            placeholder="Alasan bypass"
                        />
                        <div className="mt-3 flex justify-end">
                            <Button
                                onClick={handleGrantBypass}
                                disabled={!deadlineStorageReady || bypassSaving}
                            >
                                {bypassSaving ? 'Menyimpan...' : 'Buat Bypass'}
                            </Button>
                        </div>
                    </div>

                    <div className="mt-6">
                        <p className="mb-3 text-base font-semibold">
                            Riwayat Bypass Terbaru
                        </p>
                        <div className="space-y-2">
                            {deadlineBypasses.map((item) => (
                                <div
                                    key={item.id}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800"
                                >
                                    <span>
                                        {item.rule_label || item.rule_key} •{' '}
                                        {item.month || '-'} / {item.year || '-'}
                                    </span>
                                    <Badge
                                        variant={
                                            item.is_active
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {item.uses_count}/{item.max_uses}
                                    </Badge>
                                </div>
                            ))}
                        </div>
                    </div>
                </ContentCard>

                {/* SSO Sync Settings Card */}
                <ContentCard>
                    <div className="mb-6 flex items-start justify-between">
                        <div className="flex items-center gap-3">
                            <div
                                className={`flex h-14 w-14 items-center justify-center rounded-xl ${
                                    ssoSyncEnabled
                                        ? 'bg-blue-100 dark:bg-blue-900/30'
                                        : 'bg-neutral-100 dark:bg-neutral-800/40'
                                }`}
                            >
                                <Shield
                                    className={`h-7 w-7 ${
                                        ssoSyncEnabled
                                            ? 'text-blue-600 dark:text-blue-400'
                                            : 'text-neutral-500 dark:text-neutral-400'
                                    }`}
                                />
                            </div>
                            <div>
                                <h2 className="flex items-center gap-2 text-xl font-bold">
                                    Sinkronisasi SSO
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Sinkronisasi latar belakang ke server SSO
                                </p>
                            </div>
                        </div>
                        <Badge
                            variant={ssoSyncEnabled ? 'default' : 'secondary'}
                            className={`gap-2 px-4 py-2 text-base ${
                                ssoSyncEnabled
                                    ? 'bg-blue-600 hover:bg-blue-700'
                                    : ''
                            }`}
                        >
                            {ssoSyncEnabled ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                    </div>

                    <div className="mb-6 rounded-lg border-l-4 border-blue-500 bg-blue-50 p-4 dark:bg-blue-900/20">
                        <div className="flex items-start gap-3">
                            <Info className="mt-0.5 h-5 w-5 text-blue-600 dark:text-blue-400" />
                            <div className="space-y-1">
                                <p className="font-semibold text-blue-900 dark:text-blue-200">
                                    Lifetime sesi tetap absolut
                                </p>
                                <p className="text-sm text-blue-800 dark:text-blue-300">
                                    Walaupun SSO sync aktif, sesi lokal tidak
                                    akan diperpanjang. Setelah melewati{' '}
                                    <span className="font-semibold">
                                        {sessionLifetime} menit
                                    </span>
                                    , sinkronisasi berikutnya akan memaksa
                                    logout ke halaman login.
                                </p>
                            </div>
                        </div>
                    </div>

                    <Button
                        onClick={handleToggleSsoSync}
                        disabled={ssoSyncSaving}
                        variant={ssoSyncEnabled ? 'outline' : 'default'}
                        size="lg"
                        className="w-full"
                    >
                        {ssoSyncSaving ? (
                            <>
                                <Clock className="mr-2 h-4 w-4 animate-spin" />
                                Memproses...
                            </>
                        ) : ssoSyncEnabled ? (
                            <>Nonaktifkan SSO Sync</>
                        ) : (
                            <>Aktifkan SSO Sync</>
                        )}
                    </Button>
                </ContentCard>

                {/* Maintenance Message Card */}
                <ContentCard>
                    <div className="mb-4">
                        <h2 className="mb-2 flex items-center gap-2 text-xl font-bold">
                            <AlertTriangle className="h-5 w-5" />
                            Pesan Maintenance
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Pesan yang akan ditampilkan kepada user saat mode
                            maintenance aktif
                        </p>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="mb-2 block text-sm font-medium">
                                Isi Pesan (opsional, maks 500 karakter)
                            </label>
                            <Textarea
                                className="min-h-[120px] w-full resize-none"
                                value={editMessage}
                                onChange={(e) => setEditMessage(e.target.value)}
                                maxLength={500}
                                placeholder="Contoh: Aplikasi sedang dalam maintenance untuk peningkatan sistem. Akan aktif kembali pukul 14:00 WIB."
                                disabled={loading || saving}
                            />
                            <div className="mt-2 flex items-center justify-between">
                                <span className="text-xs text-muted-foreground">
                                    {editMessage.length}/500 karakter
                                </span>
                                {showSaved && (
                                    <span className="flex animate-in items-center gap-1 text-sm text-green-600 fade-in dark:text-green-400">
                                        <CheckCircle2 className="h-4 w-4" />
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
                                    <Clock className="mr-2 h-4 w-4 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <CheckCircle2 className="mr-2 h-4 w-4" />
                                    Simpan Pesan
                                </>
                            )}
                        </Button>

                        {message && (
                            <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/30">
                                <div className="flex items-start gap-2">
                                    <Info className="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                                    <div className="flex-1">
                                        <p className="mb-1 font-semibold text-blue-900 dark:text-blue-200">
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
                            <h2 className="mb-2 flex items-center gap-2 text-xl font-bold">
                                <Shield className="h-5 w-5" />
                                Akses Bypass Maintenance
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                URL khusus untuk admin bypass maintenance mode
                            </p>
                        </div>

                        <div className="space-y-4">
                            <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
                                <div className="flex items-start gap-2">
                                    <AlertTriangle className="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-600 dark:text-yellow-400" />
                                    <div className="flex-1">
                                        <p className="mb-1 font-semibold text-yellow-900 dark:text-yellow-200">
                                            Penting!
                                        </p>
                                        <p className="text-sm text-yellow-800 dark:text-yellow-300">
                                            Jaga kerahasiaan URL bypass ini.
                                            Siapapun dengan akses ke URL ini
                                            dapat bypass maintenance mode.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-3">
                                {/* Bypass URL */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        URL Bypass (masuk dengan bypass key)
                                    </label>
                                    <div className="flex gap-2">
                                        <div className="flex-1 rounded-lg border bg-muted px-3 py-2 font-mono text-sm break-all">
                                            {bypassUrl}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                copyToClipboard(bypassUrl)
                                            }
                                            title="Copy URL"
                                        >
                                            <Copy className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                window.open(bypassUrl, '_blank')
                                            }
                                            title="Buka di tab baru"
                                        >
                                            <ExternalLink className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                {/* Up URL */}
                                <div>
                                    <label className="mb-2 block text-sm font-medium">
                                        URL Up (nonaktifkan maintenance dengan
                                        admin key)
                                    </label>
                                    <div className="flex gap-2">
                                        <div className="flex-1 rounded-lg border bg-muted px-3 py-2 font-mono text-sm break-all">
                                            {upUrl}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                copyToClipboard(upUrl)
                                            }
                                            title="Copy URL"
                                        >
                                            <Copy className="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                window.open(upUrl, '_blank')
                                            }
                                            title="Buka di tab baru"
                                        >
                                            <ExternalLink className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-lg bg-muted/50 p-4 text-sm">
                                <p className="mb-2 font-medium">
                                    Cara Penggunaan:
                                </p>
                                <ol className="list-inside list-decimal space-y-1 text-muted-foreground">
                                    <li>Buka URL bypass di browser</li>
                                    <li>
                                        Masukkan bypass key yang telah
                                        dikonfigurasi di .env
                                    </li>
                                    <li>
                                        Setelah berhasil, Anda dapat mengakses
                                        aplikasi secara normal
                                    </li>
                                    <li>
                                        URL /up dapat digunakan untuk
                                        nonaktifkan maintenance langsung
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </ContentCard>
                )}
            </div>
        </AppLayout>
    );
}
