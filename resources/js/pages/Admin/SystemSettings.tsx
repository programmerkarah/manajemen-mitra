import { ConfirmActionDialog } from '@/components/confirm-action-dialog';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import Activity from 'lucide-react/icons/activity';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import Clock3 from 'lucide-react/icons/clock-3';
import Database from 'lucide-react/icons/database';
import ExternalLink from 'lucide-react/icons/external-link';
import RefreshCw from 'lucide-react/icons/refresh-cw';
import Settings2 from 'lucide-react/icons/settings-2';
import ShieldCheck from 'lucide-react/icons/shield-check';
import SlidersHorizontal from 'lucide-react/icons/sliders-horizontal';
import Wrench from 'lucide-react/icons/wrench';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '/admin/dashboard' },
    { title: 'Pengaturan Sistem', href: '/admin/system-settings' },
];

const API_URL = '/admin/system-settings/maintenance';
const SSO_SYNC_API_URL = '/admin/system-settings/sso-sync';
const FEATURE_TOGGLE_API_URL = '/admin/system-settings/feature-toggle';

interface FeatureToggleItem {
    key: string;
    label: string;
    description: string | null;
    enabled: boolean;
    sort_order: number;
}

interface SystemSettingsProps {
    maintenance: boolean;
    message: string | null;
    sso_sync_enabled: boolean;
    session_lifetime: number;
    feature_toggles: FeatureToggleItem[];
    deadline_storage_ready: boolean;
    [key: string]: unknown;
}

function csrfToken(): string {
    if (typeof document === 'undefined') return '';

    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function postJson<T>(
    url: string,
    body: Record<string, unknown>,
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
        },
        body: JSON.stringify(body),
    });

    const payload = await response.json();

    if (!response.ok) {
        throw new Error(
            payload?.message ??
                Object.values(payload?.errors ?? {})?.flat()?.[0] ??
                'Permintaan gagal diproses.',
        );
    }

    return payload as T;
}

export default function SystemSettings() {
    const {
        maintenance: initialMaintenance,
        message: initialMessage,
        sso_sync_enabled: initialSsoSyncEnabled,
        session_lifetime: sessionLifetime,
        feature_toggles: initialFeatureToggles,
        deadline_storage_ready: deadlineStorageReady,
    } = usePage<SystemSettingsProps>().props;

    const [maintenance, setMaintenance] = useState(initialMaintenance);
    const [message, setMessage] = useState(initialMessage ?? '');
    const [draftMessage, setDraftMessage] = useState(initialMessage ?? '');
    const [ssoSyncEnabled, setSsoSyncEnabled] = useState(
        initialSsoSyncEnabled,
    );
    const [featureToggles, setFeatureToggles] = useState(
        [...initialFeatureToggles].sort(
            (a, b) =>
                a.sort_order - b.sort_order ||
                a.label.localeCompare(b.label, 'id'),
        ),
    );
    const [processing, setProcessing] = useState<string | null>(null);
    const [confirmMaintenance, setConfirmMaintenance] = useState(false);
    const [feedback, setFeedback] = useState<{
        type: 'success' | 'error';
        text: string;
    } | null>(null);

    const enabledFeatureCount = useMemo(
        () => featureToggles.filter((item) => item.enabled).length,
        [featureToggles],
    );

    const saveMaintenance = async (enabled = maintenance) => {
        setProcessing('maintenance');
        setFeedback(null);

        try {
            const data = await postJson<{
                maintenance: boolean;
                message?: string | null;
            }>(API_URL, {
                enabled,
                message: draftMessage,
            });

            setMaintenance(data.maintenance);
            setMessage(data.message ?? draftMessage);
            setDraftMessage(data.message ?? draftMessage);
            setConfirmMaintenance(false);
            setFeedback({
                type: 'success',
                text: enabled
                    ? 'Mode maintenance aktif dan pesan terbaru sudah disimpan.'
                    : 'Mode maintenance dinonaktifkan. Pesan maintenance tetap tersinkron.',
            });

            if (data.maintenance) {
                window.location.assign('/dashboard');
            }
        } catch (error) {
            setFeedback({
                type: 'error',
                text:
                    error instanceof Error
                        ? error.message
                        : 'Pengaturan maintenance gagal disimpan.',
            });
        } finally {
            setProcessing(null);
        }
    };

    const saveMessageOnly = async () => {
        await saveMaintenance(maintenance);
    };

    const toggleSsoSync = async (enabled: boolean) => {
        setProcessing('sso');
        setFeedback(null);

        try {
            const data = await postJson<{ enabled: boolean }>(
                SSO_SYNC_API_URL,
                { enabled },
            );
            setSsoSyncEnabled(data.enabled);
            setFeedback({
                type: 'success',
                text: `Sinkronisasi sesi SSO ${data.enabled ? 'diaktifkan' : 'dinonaktifkan'}.`,
            });
        } catch (error) {
            setFeedback({
                type: 'error',
                text:
                    error instanceof Error
                        ? error.message
                        : 'Pengaturan sinkronisasi SSO gagal diperbarui.',
            });
        } finally {
            setProcessing(null);
        }
    };

    const toggleFeature = async (item: FeatureToggleItem) => {
        const enabled = !item.enabled;
        setProcessing(`feature:${item.key}`);
        setFeedback(null);

        try {
            const data = await postJson<{
                feature_toggle: FeatureToggleItem;
            }>(FEATURE_TOGGLE_API_URL, {
                key: item.key,
                enabled,
            });

            setFeatureToggles((current) =>
                current.map((feature) =>
                    feature.key === item.key
                        ? data.feature_toggle
                        : feature,
                ),
            );
        } catch (error) {
            setFeedback({
                type: 'error',
                text:
                    error instanceof Error
                        ? error.message
                        : `Fitur ${item.label} gagal diperbarui.`,
            });
        } finally {
            setProcessing(null);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengaturan Sistem" />

            <div className="space-y-6 p-4 sm:p-6">
                <PageHeader
                    title="Pengaturan Sistem"
                    description="Kelola status layanan, integrasi, dan fitur SIMANTIK dari satu pusat konfigurasi."
                >
                    <Badge
                        variant={maintenance ? 'destructive' : 'outline'}
                        className="h-9 px-3"
                    >
                        {maintenance ? (
                            <Wrench className="mr-2 size-4" />
                        ) : (
                            <CheckCircle2 className="mr-2 size-4" />
                        )}
                        {maintenance ? 'Maintenance' : 'Sistem Aktif'}
                    </Badge>
                </PageHeader>

                {feedback && (
                    <div
                        className={
                            feedback.type === 'success'
                                ? 'rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200'
                                : 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200'
                        }
                    >
                        {feedback.text}
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    <ContentCard>
                        <div className="flex items-start gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                <Activity className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Status layanan
                                </p>
                                <p className="mt-1 text-lg font-semibold">
                                    {maintenance
                                        ? 'Pemeliharaan'
                                        : 'Beroperasi Normal'}
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-start gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                <ShieldCheck className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Sinkronisasi SSO
                                </p>
                                <p className="mt-1 text-lg font-semibold">
                                    {ssoSyncEnabled ? 'Aktif' : 'Nonaktif'}
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-start gap-3">
                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                <SlidersHorizontal className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Fitur aktif
                                </p>
                                <p className="mt-1 text-lg font-semibold">
                                    {enabledFeatureCount}/
                                    {featureToggles.length}
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <ContentCard>
                        <div className="mb-5 flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Maintenance
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Pesan ini menjadi sumber yang sama untuk
                                    System Settings, /mt, dan halaman 503.
                                </p>
                            </div>
                            <Badge
                                variant={
                                    maintenance ? 'destructive' : 'outline'
                                }
                            >
                                {maintenance ? 'Aktif' : 'Nonaktif'}
                            </Badge>
                        </div>

                        <div className="space-y-3">
                            <Textarea
                                value={draftMessage}
                                onChange={(event) =>
                                    setDraftMessage(event.target.value)
                                }
                                rows={5}
                                maxLength={500}
                                placeholder="Tulis informasi maintenance untuk pengguna..."
                            />
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-xs text-muted-foreground">
                                    {draftMessage.length}/500 karakter
                                    {message !== draftMessage
                                        ? ' · ada perubahan belum disimpan'
                                        : ''}
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        disabled={
                                            processing !== null ||
                                            message === draftMessage
                                        }
                                        onClick={() =>
                                            void saveMessageOnly()
                                        }
                                    >
                                        Simpan Pesan
                                    </Button>
                                    <Button
                                        variant={
                                            maintenance
                                                ? 'outline'
                                                : 'default'
                                        }
                                        disabled={processing !== null}
                                        onClick={() =>
                                            setConfirmMaintenance(true)
                                        }
                                    >
                                        {maintenance
                                            ? 'Nonaktifkan Maintenance'
                                            : 'Aktifkan Maintenance'}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="mb-5">
                            <h2 className="text-base font-semibold">
                                Integrasi & Sesi
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Perilaku sesi aplikasi dan validasi SSO.
                            </p>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                                <div>
                                    <p className="text-sm font-medium">
                                        Sinkronisasi sesi SSO
                                    </p>
                                    <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                        Memastikan status sesi SSO tetap
                                        konsisten dengan SIMANTIK.
                                    </p>
                                </div>
                                <Switch
                                    checked={ssoSyncEnabled}
                                    disabled={processing !== null}
                                    onCheckedChange={(enabled) =>
                                        void toggleSsoSync(enabled)
                                    }
                                />
                            </div>

                            <div className="flex items-center justify-between gap-4 rounded-xl border p-4">
                                <div className="flex items-center gap-3">
                                    <Clock3 className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm font-medium">
                                            Lifetime sesi
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Mengikuti konfigurasi server
                                        </p>
                                    </div>
                                </div>
                                <Badge variant="outline">
                                    {sessionLifetime} menit
                                </Badge>
                            </div>
                        </div>
                    </ContentCard>
                </div>

                <ContentCard>
                    <div className="mb-5">
                        <h2 className="text-base font-semibold">
                            Kontrol Fitur
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Aktifkan hanya modul yang siap digunakan. Perubahan
                            tersimpan tanpa memerlukan deploy ulang.
                        </p>
                    </div>

                    <div className="grid gap-3 lg:grid-cols-2">
                        {featureToggles.map((feature) => (
                            <div
                                key={feature.key}
                                className="flex items-start justify-between gap-4 rounded-xl border p-4"
                            >
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="text-sm font-medium">
                                            {feature.label}
                                        </p>
                                        <Badge
                                            variant={
                                                feature.enabled
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {feature.enabled
                                                ? 'Aktif'
                                                : 'Nonaktif'}
                                        </Badge>
                                    </div>
                                    {feature.description && (
                                        <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                            {feature.description}
                                        </p>
                                    )}
                                </div>
                                <Switch
                                    checked={feature.enabled}
                                    disabled={processing !== null}
                                    onCheckedChange={() =>
                                        void toggleFeature(feature)
                                    }
                                />
                            </div>
                        ))}
                    </div>
                </ContentCard>

                <ContentCard>
                    <div className="mb-4 flex items-center gap-2">
                        <Settings2 className="size-5 text-muted-foreground" />
                        <div>
                            <h2 className="font-semibold">
                                Administrasi Lanjutan
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Akses pengaturan yang memiliki halaman khusus.
                            </p>
                        </div>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <Button
                            variant="outline"
                            className="h-auto justify-between px-4 py-3"
                            asChild
                        >
                            <Link href="/admin/database-status">
                                <span className="flex items-center gap-2">
                                    <Database className="size-4" />
                                    Database
                                </span>
                                <ExternalLink className="size-4 text-muted-foreground" />
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            className="h-auto justify-between px-4 py-3"
                            asChild
                        >
                            <Link href="/admin/activity-log">
                                <span className="flex items-center gap-2">
                                    <Activity className="size-4" />
                                    Log Aktivitas
                                </span>
                                <ExternalLink className="size-4 text-muted-foreground" />
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            className="h-auto justify-between px-4 py-3"
                            asChild
                        >
                            <Link href="/manage-deadline">
                                <span className="flex items-center gap-2">
                                    <Clock3 className="size-4" />
                                    Deadline & Bypass
                                </span>
                                <Badge
                                    variant={
                                        deadlineStorageReady
                                            ? 'outline'
                                            : 'destructive'
                                    }
                                >
                                    {deadlineStorageReady
                                        ? 'Siap'
                                        : 'Migrasi'}
                                </Badge>
                            </Link>
                        </Button>
                    </div>
                </ContentCard>
            </div>

            <ConfirmActionDialog
                open={confirmMaintenance}
                onOpenChange={setConfirmMaintenance}
                title={
                    maintenance
                        ? 'Nonaktifkan maintenance?'
                        : 'Aktifkan maintenance?'
                }
                description={
                    maintenance
                        ? 'Layanan akan kembali dapat diakses oleh seluruh pengguna.'
                        : 'Pengguna biasa akan melihat halaman maintenance dengan pesan yang sudah Anda siapkan.'
                }
                confirmLabel={
                    maintenance
                        ? 'Aktifkan Layanan'
                        : 'Mulai Maintenance'
                }
                destructive={!maintenance}
                processing={processing === 'maintenance'}
                onConfirm={() => void saveMaintenance(!maintenance)}
            />
        </AppLayout>
    );
}
