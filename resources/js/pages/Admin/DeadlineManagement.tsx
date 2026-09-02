import React from 'react';

import { ContentCard } from '@/components/content-card';
import { MultiSelectCheckbox } from '@/components/multi-select-checkbox';
import { SearchableSelect } from '@/components/searchable-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    Clock3,
    HelpCircle,
    ShieldCheck,
    Sparkles,
    TimerReset,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administrasi', href: '/dashboard' },
    {
        title: 'Manajemen Deadline & Bypass',
        href: '/manage-deadline',
    },
];

const DEADLINE_RULE_API_URL = '/admin/system-settings/deadline-rule';
const DEADLINE_BYPASS_API_URL = '/admin/system-settings/deadline-bypass';
const DEADLINE_BYPASS_REQUEST_APPROVE_API_URL =
    '/admin/system-settings/deadline-bypass-request';
const ALLOCATION_REVISION_KEY = 'alokasi.revisi';

interface CompactDayStepperProps {
    value: number | null;
    min?: number;
    max?: number;
    disabled?: boolean;
    onChange: (value: number) => void;
}

function CompactDayStepper({
    value,
    min = 1,
    max = 31,
    disabled = false,
    onChange,
}: CompactDayStepperProps) {
    const safeValue = value ?? min;
    const normalizedValue = Number.isFinite(safeValue)
        ? Math.min(max, Math.max(min, safeValue))
        : min;

    const handleValue = (nextValue: number) => {
        const safeValue = Number.isFinite(nextValue)
            ? Math.min(max, Math.max(min, nextValue))
            : min;

        onChange(safeValue);
    };

    return (
        <div className="inline-flex items-center gap-1 rounded-xl border border-neutral-200 bg-white/80 p-1 shadow-sm ring-1 ring-transparent transition-all hover:border-amber-300 hover:ring-amber-100 dark:border-neutral-700 dark:bg-neutral-800/80 dark:hover:border-amber-600 dark:hover:ring-amber-900/70">
            <button
                type="button"
                aria-label="Kurangi tanggal cutoff"
                onClick={() => handleValue(normalizedValue - 1)}
                disabled={disabled || normalizedValue <= min}
                className="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-semibold text-neutral-700 transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-neutral-200 dark:hover:bg-neutral-700"
            >
                −
            </button>

            <input
                type="number"
                inputMode="numeric"
                min={min}
                max={max}
                value={normalizedValue}
                disabled={disabled}
                onChange={(event) => {
                    const nextValue = Number(event.target.value || min);
                    handleValue(nextValue);
                }}
                className="h-8 w-14 [appearance:textfield] border-0 bg-transparent px-1 text-center text-base font-semibold text-neutral-900 outline-none focus:outline-none dark:text-white [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
            />

            <button
                type="button"
                aria-label="Tambah tanggal cutoff"
                onClick={() => handleValue(normalizedValue + 1)}
                disabled={disabled || normalizedValue >= max}
                className="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-semibold text-neutral-700 transition hover:bg-neutral-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-neutral-200 dark:hover:bg-neutral-700"
            >
                +
            </button>
        </div>
    );
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
    granted_for: string | null;
    granted_for_user_id?: number | null;
    approved_by: string | null;
    year: number | null;
    month: number | null;
    uses_count: number;
    max_uses: number;
    is_active: boolean;
    reason: string | null;
    expires_at: string | null;
    created_at: string | null;
    metadata?: {
        source?: string;
        [key: string]: unknown;
    } | null;
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

interface DeadlineManagementProps {
    deadline_rules: DeadlineRuleItem[];
    deadline_bypasses: DeadlineBypassItem[];
    deadline_bypass_requests: DeadlineBypassRequestItem[];
    deadline_storage_ready: boolean;
    users: Array<{ id: number; name: string }>;
    [key: string]: unknown;
}

export default function DeadlineManagement() {
    const {
        deadline_rules: initialDeadlineRules,
        deadline_bypasses: initialDeadlineBypasses,
        deadline_bypass_requests: initialDeadlineBypassRequests,
        deadline_storage_ready: deadlineStorageReady,
        users: initialUsers,
    } = usePage<DeadlineManagementProps>().props;

    const [deadlineRules, setDeadlineRules] = React.useState(
        [...initialDeadlineRules].sort(
            (left, right) => left.sort_order - right.sort_order,
        ),
    );
    const [deadlineBypasses, setDeadlineBypasses] = React.useState(
        initialDeadlineBypasses,
    );
    const [deadlineBypassRequests, setDeadlineBypassRequests] = React.useState(
        initialDeadlineBypassRequests,
    );
    const [deadlineSavingKey, setDeadlineSavingKey] = React.useState<
        string | null
    >(null);
    const [requestActionLoadingId, setRequestActionLoadingId] = React.useState<
        number | null
    >(null);
    const [revokeBypassId, setRevokeBypassId] = React.useState<number | null>(
        null,
    );
    const [activeTab, setActiveTab] = React.useState<
        'request' | 'approved' | 'manual'
    >('request');
    const [manualBypassForm, setManualBypassForm] = React.useState({
        granted_for_user_id: '',
        rule_ids: [] as number[],
        expires_at: '',
        reason: '',
    });
    const [bypassSaving, setBypassSaving] = React.useState(false);
    const [flashMessage, setFlashMessage] = React.useState<{
        open: boolean;
        type: 'success' | 'error' | 'warning' | 'info';
        title: string;
        message: string;
    }>({
        open: false,
        type: 'info',
        title: '',
        message: '',
    });
    const [revokeConfirm, setRevokeConfirm] = React.useState<{
        open: boolean;
        bypassIds: number[];
        label: string;
        description: string;
    }>({
        open: false,
        bypassIds: [],
        label: '',
        description: '',
    });
    const [bypassFilters, setBypassFilters] = React.useState({
        userIds: [] as number[],
        status: 'all',
        createdFrom: '',
        createdTo: '',
    });

    const userOptions = React.useMemo(
        () =>
            initialUsers.map((user) => ({
                value: String(user.id),
                label: user.name,
                displayLabel: user.name,
            })),
        [initialUsers],
    );

    const ruleOptions = React.useMemo(
        () =>
            initialDeadlineRules.map((rule) => ({
                value: rule.id,
                label: rule.label,
                subLabel: rule.feature_key,
            })),
        [initialDeadlineRules],
    );

    const getCsrfToken = () => {
        if (typeof document !== 'undefined') {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta?.getAttribute('content') || '';
        }

        return '';
    };

    const showFlashMessage = (
        title: string,
        message: string,
        type: 'success' | 'error' | 'warning' | 'info' = 'info',
    ) => {
        setFlashMessage({ open: true, type, title, message });
    };

    const showModalAlert = (title: string, message: string) => {
        showFlashMessage(title, message, 'info');
    };

    React.useEffect(() => {
        if (!flashMessage.open) {
            return;
        }

        const timer = window.setTimeout(() => {
            setFlashMessage((current) => ({ ...current, open: false }));
        }, 4000);

        return () => window.clearTimeout(timer);
    }, [flashMessage.open]);

    const handleGrantBypass = async () => {
        if (!deadlineStorageReady) {
            showModalAlert(
                'Storage Deadline Belum Aktif',
                'Tabel deadline belum tersedia. Jalankan migrasi terlebih dahulu agar bypass dapat dibuat.',
            );

            return;
        }

        if (!manualBypassForm.granted_for_user_id) {
            showModalAlert(
                'User Belum Dipilih',
                'Pilih user yang akan diberikan bypass terlebih dahulu.',
            );

            return;
        }

        const selectedRuleKeys = manualBypassForm.rule_ids
            .map(
                (ruleId) =>
                    initialDeadlineRules.find((rule) => rule.id === ruleId)
                        ?.key,
            )
            .filter((ruleKey): ruleKey is string => Boolean(ruleKey));

        if (selectedRuleKeys.length === 0) {
            showModalAlert(
                'Jenis Akses Belum Dipilih',
                'Pilih minimal satu jenis akses yang akan diberikan bypass.',
            );

            return;
        }

        if (!manualBypassForm.expires_at) {
            showModalAlert(
                'Batas Waktu Belum Diisi',
                'Pilih tanggal batas waktu bypass sebelum menyimpan.',
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
                    granted_for_user_id: Number(
                        manualBypassForm.granted_for_user_id,
                    ),
                    rule_keys: selectedRuleKeys,
                    expires_at: manualBypassForm.expires_at || null,
                    reason: manualBypassForm.reason || null,
                }),
            });

            if (!res.ok) {
                const response = await res.json().catch(() => ({}));
                throw new Error(
                    response?.message || `HTTP error! status: ${res.status}`,
                );
            }

            setManualBypassForm({
                granted_for_user_id: '',
                rule_ids: [],
                expires_at: '',
                reason: '',
            });
            setActiveTab('manual');

            await router.reload({
                only: ['deadline_bypasses', 'deadline_bypass_requests'],
            });

            showModalAlert(
                'Bypass Dibuat',
                'Bypass manual berhasil dibuat untuk user yang dipilih.',
            );
        } catch (error) {
            console.error('Failed to grant bypass:', error);
            showModalAlert(
                'Aksi Gagal',
                error instanceof Error && error.message
                    ? error.message
                    : 'Gagal membuat bypass batas waktu. Silakan coba lagi.',
            );
        } finally {
            setBypassSaving(false);
        }
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
                `Batas waktu ${rule.label} berhasil diperbarui.`,
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

    const handleRevokeBypass = async (bypassId: number) => {
        setRevokeBypassId(bypassId);

        try {
            const res = await fetch(
                `${DEADLINE_BYPASS_API_URL}/${bypassId}/revoke`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        reason: 'Dicabut oleh admin',
                    }),
                },
            );

            if (!res.ok) {
                const response = await res.json().catch(() => ({}));
                throw new Error(
                    response?.message || `HTTP error! status: ${res.status}`,
                );
            }

            setDeadlineBypasses((current) =>
                current.map((item) =>
                    item.id === bypassId
                        ? {
                              ...item,
                              is_active: false,
                              reason: 'Dicabut oleh admin',
                          }
                        : item,
                ),
            );

            showModalAlert(
                'Aksess Dicabut',
                'Bypass aktif berhasil dicabut dan tidak akan lagi berlaku.',
            );
        } catch (error) {
            console.error('Failed to revoke bypass:', error);
            showModalAlert(
                'Aksi Gagal',
                error instanceof Error && error.message
                    ? error.message
                    : 'Gagal mencabut bypass. Silakan coba lagi.',
            );
        } finally {
            setRevokeBypassId(null);
        }
    };

    const requestRevokeBypass = (
        bypassId: number,
        label: string,
        description: string,
    ) => {
        setRevokeConfirm({
            open: true,
            bypassIds: [bypassId],
            label,
            description,
        });
    };

    const requestBulkRevoke = (
        bypassIds: number[],
        label: string,
        description: string,
    ) => {
        setRevokeConfirm({
            open: true,
            bypassIds,
            label,
            description,
        });
    };

    const confirmRevokeAction = async () => {
        if (revokeConfirm.bypassIds.length === 0) {
            return;
        }

        setRevokeConfirm((current) => ({ ...current, open: false }));

        await Promise.all(
            revokeConfirm.bypassIds.map((bypassId) =>
                handleRevokeBypass(bypassId),
            ),
        );
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

    const pendingRequests = deadlineBypassRequests.filter(
        (item) => item.status === 'pending',
    );
    const approvedRequests = deadlineBypassRequests.filter(
        (item) => item.status === 'approved',
    );
    const activeBypasses = deadlineBypasses.filter((item) => item.is_active);

    const isRequestBackedBypass = React.useCallback(
        (item: DeadlineBypassItem) => {
            const source = item.metadata?.source;

            if (source === 'manual_admin_grant') {
                return false;
            }

            if (
                source === 'deadline_bypass_request' ||
                source === 'deadline_block_prompt' ||
                item.metadata?.request_id != null
            ) {
                return true;
            }

            return approvedRequests.some(
                (request) =>
                    request.status === 'approved' &&
                    request.requested_by === item.granted_for &&
                    request.rule_key === item.rule_key &&
                    request.year === item.year &&
                    request.month === item.month,
            );
        },
        [approvedRequests],
    );

    const formatCreatedAtLabel = (value?: string | null) => {
        if (!value) {
            return '-';
        }

        const parsedDate = new Date(value);

        if (Number.isNaN(parsedDate.getTime())) {
            return value;
        }

        const formattedDate = new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(parsedDate);

        return `${formattedDate} WIB`;
    };

    const formatMonthYearLabel = (
        month?: number | null,
        year?: number | null,
    ) => {
        if (month == null || Number.isNaN(Number(month))) {
            return '-';
        }

        const normalizedMonth = Number(month);

        if (normalizedMonth < 1 || normalizedMonth > 12) {
            return `${month}`;
        }

        const monthLabel = new Intl.DateTimeFormat('id-ID', {
            month: 'long',
        }).format(
            new Date(
                Number(year ?? new Date().getFullYear()),
                normalizedMonth - 1,
                1,
            ),
        );

        return `${monthLabel} ${year ?? ''}`.trim();
    };

    const matchesBypassFilters = React.useCallback(
        (item: DeadlineBypassItem) => {
            const selectedUserIds = bypassFilters.userIds;
            if (selectedUserIds.length > 0) {
                const grantedUserId =
                    item.granted_for_user_id ??
                    (typeof item.metadata?.granted_for_user_id === 'number'
                        ? item.metadata.granted_for_user_id
                        : null);
                const matchesSelectedUser =
                    typeof grantedUserId === 'number'
                        ? selectedUserIds.includes(grantedUserId)
                        : false;

                if (!matchesSelectedUser) {
                    return false;
                }
            }

            const statusValue = item.is_active ? 'aktif' : 'tidak_aktif';
            if (
                bypassFilters.status !== 'all' &&
                statusValue !== bypassFilters.status
            ) {
                return false;
            }

            if (!item.created_at) {
                return !(bypassFilters.createdFrom || bypassFilters.createdTo);
            }

            const itemCreatedAt = new Date(item.created_at);

            if (bypassFilters.createdFrom) {
                const fromDate = new Date(
                    `${bypassFilters.createdFrom}T00:00:00`,
                );
                if (itemCreatedAt < fromDate) {
                    return false;
                }
            }

            if (bypassFilters.createdTo) {
                const toDate = new Date(`${bypassFilters.createdTo}T23:59:59`);
                if (itemCreatedAt > toDate) {
                    return false;
                }
            }

            return true;
        },
        [bypassFilters],
    );

    const groupManualBypasses = React.useMemo(() => {
        const manualOnlyBypasses = deadlineBypasses.filter(
            (item) =>
                !isRequestBackedBypass(item) && matchesBypassFilters(item),
        );

        const entries = new Map<
            string,
            {
                key: string;
                userName: string;
                reason: string | null;
                items: DeadlineBypassItem[];
                expanded: boolean;
            }
        >();

        manualOnlyBypasses.forEach((item) => {
            const userName = item.granted_for || 'User tidak diketahui';
            const createdAt = item.created_at || '';
            const key = `${userName}::${createdAt}`;

            if (!entries.has(key)) {
                entries.set(key, {
                    key,
                    userName,
                    reason: item.reason,
                    items: [],
                    expanded: false,
                });
            }

            entries.get(key)!.items.push(item);
        });

        return Array.from(entries.values()).map((entry) => ({
            ...entry,
            items: entry.items.sort((left, right) => {
                if (left.is_active !== right.is_active) {
                    return Number(right.is_active) - Number(left.is_active);
                }

                return (right.id ?? 0) - (left.id ?? 0);
            }),
        }));
    }, [deadlineBypasses, isRequestBackedBypass, matchesBypassFilters]);

    const groupApprovedBypasses = React.useMemo(() => {
        const requestBackedBypasses = deadlineBypasses.filter(
            (item) => isRequestBackedBypass(item) && matchesBypassFilters(item),
        );

        const entries = new Map<
            string,
            {
                key: string;
                userName: string;
                reason: string | null;
                items: DeadlineBypassItem[];
                expanded: boolean;
            }
        >();

        requestBackedBypasses.forEach((item) => {
            const userName = item.granted_for || 'User tidak diketahui';
            const createdAt = item.created_at || '';
            const key = `${userName}::${createdAt}`;

            if (!entries.has(key)) {
                entries.set(key, {
                    key,
                    userName,
                    reason: item.reason,
                    items: [],
                    expanded: false,
                });
            }

            entries.get(key)!.items.push(item);
        });

        return Array.from(entries.values()).map((entry) => ({
            ...entry,
            items: entry.items.sort((left, right) => {
                if (left.is_active !== right.is_active) {
                    return Number(right.is_active) - Number(left.is_active);
                }

                return (right.id ?? 0) - (left.id ?? 0);
            }),
        }));
    }, [deadlineBypasses, isRequestBackedBypass, matchesBypassFilters]);

    const getBypassStatus = (item: DeadlineBypassItem) => {
        if (!item.is_active) {
            const reasonText = (item.reason ?? '').toLowerCase();

            if (
                reasonText.includes('dicabut') ||
                reasonText.includes('cabut') ||
                reasonText.includes('revoked')
            ) {
                return 'Akses dicabut';
            }

            return 'Sudah digunakan';
        }

        return 'Aktif';
    };

    const getBypassStatusTone = (item: DeadlineBypassItem) => {
        if (!item.is_active) {
            const reasonText = (item.reason ?? '').toLowerCase();

            if (
                reasonText.includes('dicabut') ||
                reasonText.includes('cabut') ||
                reasonText.includes('revoked')
            ) {
                return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
            }

            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
        }

        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    };

    const [expandedManualGroups, setExpandedManualGroups] = React.useState<
        Record<string, boolean>
    >({});
    const [expandedApprovedGroups, setExpandedApprovedGroups] = React.useState<
        Record<string, boolean>
    >({});

    const toggleManualGroup = (key: string) => {
        setExpandedManualGroups((current) => ({
            ...current,
            [key]: !current[key],
        }));
    };

    const toggleApprovedGroup = (key: string) => {
        setExpandedApprovedGroups((current) => ({
            ...current,
            [key]: !current[key],
        }));
    };

    const manualTabLabel = `Manual Bypass (${groupManualBypasses.length})`;
    const requestTabLabel = `Request (${pendingRequests.length})`;
    const approvedTabLabel = `Approved (${approvedRequests.length})`;

    const getRuleDisplayTitle = (rule: DeadlineRuleItem) => {
        const titleMap: Record<string, string> = {
            'alokasi.manage': 'Alokasi Petugas',
            'alokasi.revisi': 'Perubahan Alokasi Petugas',
            'pengajuan_pulsa.manage': 'Pengajuan Pulsa',
            'sk.manage': 'Surat Keputusan',
            'spk.manage': 'Perjanjian Kerja',
            'spk.addendum': 'Addendum Perjanjian Kerja',
            'bast.manage': 'BAST',
        };

        return titleMap[rule.key] ?? rule.label;
    };

    const getMonthName = (year: number, month: number) =>
        new Intl.DateTimeFormat('id-ID', {
            month: 'long',
        }).format(new Date(year, month, 1));

    const getEffectiveCutoffDay = (
        rule: DeadlineRuleItem,
        year: number,
        month: number,
    ) => {
        const configuredCutoff = rule.cutoff_day ?? 1;
        const lastDayOfMonth = new Date(year, month + 1, 0).getDate();

        return Math.min(configuredCutoff, lastDayOfMonth);
    };

    const getRuleFooterText = (rule: DeadlineRuleItem) => {
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();

        const currentMonthName = getMonthName(year, month);
        const nextMonthName = getMonthName(year, month + 1);
        const effectiveCutoff = getEffectiveCutoffDay(rule, year, month);

        if (rule.key === ALLOCATION_REVISION_KEY) {
            return `${currentMonthName} → sampai ${effectiveCutoff} ${currentMonthName}`;
        }

        return `${nextMonthName} → sampai ${effectiveCutoff} ${currentMonthName}`;
    };

    const getRuleHelpText = (rule: DeadlineRuleItem) => {
        const configuredCutoff = rule.cutoff_day ?? 1;

        if (rule.key === ALLOCATION_REVISION_KEY) {
            return `Jika batas waktu ditetapkan tanggal ${configuredCutoff}, maka revisi alokasi bulan berjalan hanya dapat dilakukan sampai tanggal tersebut pada bulan yang sama. Jika tanggal tersebut tidak tersedia, batas waktu otomatis menggunakan hari terakhir bulan.`;
        }

        return `Jika batas waktu ditetapkan tanggal ${configuredCutoff}, maka ${rule.label.toLowerCase()} untuk bulan berikutnya hanya dapat dilakukan sampai tanggal tersebut pada bulan berjalan. Jika tanggal tersebut tidak tersedia, batas waktu otomatis menggunakan hari terakhir bulan.`;
    };

    const getExampleText = (rule: DeadlineRuleItem) => {
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth();

        const currentMonthName = getMonthName(year, month);
        const nextMonthName = getMonthName(year, month + 1);
        const configuredCutoff = rule.cutoff_day ?? 1;
        const effectiveCutoff = getEffectiveCutoffDay(rule, year, month);

        if (rule.key === ALLOCATION_REVISION_KEY) {
            return `Jika batas waktu ditetapkan tanggal ${configuredCutoff}, maka revisi alokasi bulan ${currentMonthName} hanya dapat dilakukan sampai tanggal ${effectiveCutoff} ${currentMonthName}.`;
        }

        return `Jika batas waktu ditetapkan tanggal ${configuredCutoff}, maka ${rule.label} untuk bulan ${nextMonthName} hanya dapat dilakukan sampai tanggal ${effectiveCutoff} ${currentMonthName}.`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manajemen Deadline & Bypass" />

            <div className="space-y-6">
                <div className="mb-2 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600 shadow-sm dark:bg-amber-900/30 dark:text-amber-400">
                            <Clock3 className="h-6 w-6" />
                        </div>
                        <div>
                            <h1 className="text-3xl font-bold text-neutral-900 dark:text-white">
                                Manajemen Deadline & Bypass
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Kelola cutoff fitur, request user, dan grant
                                bypass manual.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <ContentCard>
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Rule aktif
                                </p>
                                <p className="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                                    {deadlineRules.length}
                                </p>
                            </div>
                            <div className="rounded-xl bg-amber-100 p-2.5 text-amber-600 shadow-sm dark:bg-amber-900/30 dark:text-amber-400">
                                <TimerReset className="h-5 w-5" />
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Request pending
                                </p>
                                <p className="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                                    {pendingRequests.length}
                                </p>
                            </div>
                            <div className="rounded-xl bg-purple-100 p-2.5 text-purple-600 shadow-sm dark:bg-purple-900/30 dark:text-purple-400">
                                <Sparkles className="h-5 w-5" />
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Bypass aktif
                                </p>
                                <p className="mt-2 text-2xl font-bold text-neutral-900 dark:text-white">
                                    {activeBypasses.length}
                                </p>
                            </div>
                            <div className="rounded-xl bg-emerald-100 p-2.5 text-emerald-600 shadow-sm dark:bg-emerald-900/30 dark:text-emerald-400">
                                <ShieldCheck className="h-5 w-5" />
                            </div>
                        </div>
                    </ContentCard>
                </div>

                <ContentCard>
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 className="text-xl font-bold text-neutral-900 dark:text-white">
                                Aturan Deadline Fitur
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Setiap fitur dapat diatur batas waktu bulanan
                                agar aksi yang melewati periode memerlukan
                                persetujuan.
                            </p>
                        </div>
                    </div>

                    {!deadlineStorageReady && (
                        <div className="mb-4 rounded-xl border border-amber-400/50 bg-amber-100/70 px-4 py-3 text-amber-900 shadow-sm dark:border-amber-500/40 dark:bg-amber-900/20 dark:text-amber-100">
                            <p className="text-sm font-semibold">
                                Storage deadline belum aktif
                            </p>
                            <p className="mt-1 text-xs">
                                Aturan default ditampilkan, tetapi perubahan
                                perlu migrasi database agar bisa disimpan.
                            </p>
                        </div>
                    )}

                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        {deadlineRules.map((rule) => {
                            const isAllocationRevision =
                                rule.key === ALLOCATION_REVISION_KEY;

                            return (
                                <div
                                    key={rule.key}
                                    className="flex h-full flex-col rounded-2xl border border-neutral-200 bg-neutral-50/90 p-4 text-center shadow-sm transition-colors hover:border-amber-300 dark:border-neutral-800 dark:bg-neutral-900/60 dark:hover:border-amber-700"
                                >
                                    <div className="relative flex min-h-6 w-full items-center justify-center">
                                        <p className="px-8 text-sm font-semibold text-neutral-900 dark:text-white">
                                            {getRuleDisplayTitle(rule)}
                                        </p>

                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <button
                                                    type="button"
                                                    aria-label={`Informasi ${rule.label}`}
                                                    className="absolute right-0 inline-flex h-5 w-5 items-center justify-center rounded-full border border-neutral-300 bg-white text-neutral-600 transition-colors hover:border-amber-400 hover:text-amber-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                                                >
                                                    <HelpCircle className="h-3.5 w-3.5" />
                                                </button>
                                            </DialogTrigger>
                                            <DialogContent className="sm:max-w-md">
                                                <DialogHeader>
                                                    <DialogTitle>
                                                        {rule.label}
                                                    </DialogTitle>
                                                    <DialogDescription>
                                                        {rule.description ||
                                                            'Tidak ada penjelasan tambahan.'}
                                                    </DialogDescription>
                                                </DialogHeader>
                                                <div className="space-y-3 pt-1 text-left text-sm text-muted-foreground">
                                                    <p>
                                                        <span className="font-medium text-foreground">
                                                            Penjelasan:
                                                        </span>{' '}
                                                        {getRuleHelpText(rule)}
                                                    </p>
                                                    <p>
                                                        <span className="font-medium text-foreground">
                                                            Contoh:
                                                        </span>{' '}
                                                        {getExampleText(rule)}
                                                    </p>
                                                </div>
                                            </DialogContent>
                                        </Dialog>
                                    </div>

                                    <div className="mt-5 flex flex-1 flex-col items-center">
                                        <div className="flex w-full flex-col items-center">
                                            <p className="text-[11px] font-medium tracking-[0.12em] text-muted-foreground uppercase">
                                                {isAllocationRevision
                                                    ? 'Revisi sampai tanggal'
                                                    : 'Tutup tanggal'}
                                            </p>

                                            <div className="mt-2 flex w-full justify-center">
                                                <CompactDayStepper
                                                    value={rule.cutoff_day ?? 1}
                                                    min={1}
                                                    max={31}
                                                    disabled={
                                                        !deadlineStorageReady
                                                    }
                                                    onChange={(value) =>
                                                        setDeadlineRules(
                                                            (current) =>
                                                                current.map(
                                                                    (item) =>
                                                                        item.key ===
                                                                        rule.key
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
                                                />
                                            </div>

                                            <p className="mt-2 text-[10px] font-medium tracking-[0.12em] text-muted-foreground uppercase">
                                                {isAllocationRevision
                                                    ? 'bulan berjalan'
                                                    : 'setiap bulan'}
                                            </p>
                                        </div>

                                        <div className="mt-auto w-full pt-5">
                                            <div className="border-t border-neutral-200 pt-3 text-center text-[11px] leading-relaxed text-muted-foreground dark:border-neutral-700">
                                                {getRuleFooterText(rule)}
                                            </div>
                                        </div>
                                    </div>

                                    <Button
                                        size="sm"
                                        className="mt-4 h-8 w-full"
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
                                            : 'Simpan'}
                                    </Button>
                                </div>
                            );
                        })}
                    </div>
                </ContentCard>

                <ContentCard>
                    <div className="mb-5 flex items-center gap-2">
                        <div className="rounded-xl bg-slate-100 p-2 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <ShieldCheck className="h-5 w-5" />
                        </div>
                        <div>
                            <h2 className="text-xl font-bold text-neutral-900 dark:text-white">
                                Kelola Bypass
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Review request user dan grant akses manual.
                            </p>
                        </div>
                    </div>

                    <div className="mb-5 inline-flex rounded-xl border border-neutral-200 bg-neutral-100 p-1 dark:border-neutral-800 dark:bg-neutral-900">
                        {[
                            { key: 'request', label: requestTabLabel },
                            { key: 'approved', label: approvedTabLabel },
                            { key: 'manual', label: manualTabLabel },
                        ].map((tab) => (
                            <button
                                key={tab.key}
                                type="button"
                                onClick={() =>
                                    setActiveTab(
                                        tab.key as
                                            | 'request'
                                            | 'approved'
                                            | 'manual',
                                    )
                                }
                                className={[
                                    'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    activeTab === tab.key
                                        ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-800 dark:text-white'
                                        : 'text-muted-foreground hover:text-neutral-900 dark:hover:text-white',
                                ].join(' ')}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    {activeTab === 'request' ? (
                        <div className="space-y-3">
                            {pendingRequests.length === 0 && (
                                <div className="rounded-2xl border border-dashed border-neutral-300 p-6 text-sm text-muted-foreground dark:border-neutral-700">
                                    Belum ada request bypass.
                                </div>
                            )}

                            {pendingRequests.map((item) => (
                                <div
                                    key={item.id}
                                    className="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 shadow-sm transition-colors hover:border-blue-300 dark:border-neutral-800 dark:bg-neutral-900/60 dark:hover:border-blue-700"
                                >
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="font-semibold text-neutral-900 dark:text-white">
                                                    {item.requested_by ||
                                                        'User tidak diketahui'}
                                                </p>
                                                <Badge variant="secondary">
                                                    {item.status}
                                                </Badge>
                                            </div>

                                            <div className="grid gap-1 text-sm text-muted-foreground sm:grid-cols-2">
                                                <span>
                                                    Jenis akses:{' '}
                                                    {item.rule_label ||
                                                        item.rule_key ||
                                                        '-'}
                                                </span>
                                                <span>
                                                    Periode: Bulan{' '}
                                                    {formatMonthYearLabel(
                                                        item.month,
                                                        item.year,
                                                    )}
                                                </span>
                                                <span>
                                                    Target:{' '}
                                                    {item.route_name || '-'}
                                                </span>
                                                <span>
                                                    Pengajuan:{' '}
                                                    {item.created_at || '-'}
                                                </span>
                                            </div>

                                            <p className="text-sm text-muted-foreground">
                                                Alasan: {item.reason || '-'}
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
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
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : activeTab === 'approved' ? (
                        <div className="space-y-3">
                            <div className="grid gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-3 md:grid-cols-4 dark:border-neutral-800 dark:bg-neutral-900/60">
                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-medium tracking-[0.13em] text-muted-foreground uppercase">
                                        Nama petugas
                                    </label>
                                    <MultiSelectCheckbox
                                        options={initialUsers.map((user) => ({
                                            value: user.id,
                                            label: user.name,
                                            subLabel: 'Petugas',
                                        }))}
                                        values={bypassFilters.userIds}
                                        onValuesChange={(values) =>
                                            setBypassFilters((current) => ({
                                                ...current,
                                                userIds: values,
                                            }))
                                        }
                                        placeholder="Pilih petugas"
                                        className="min-h-10"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-medium tracking-[0.13em] text-muted-foreground uppercase">
                                        Status bypass
                                    </label>
                                    <SearchableSelect
                                        options={[
                                            {
                                                value: 'all',
                                                label: 'Semua status',
                                                displayLabel: 'Semua status',
                                            },
                                            {
                                                value: 'aktif',
                                                label: 'Aktif',
                                                displayLabel: 'Aktif',
                                            },
                                            {
                                                value: 'tidak_aktif',
                                                label: 'Tidak aktif',
                                                displayLabel: 'Tidak aktif',
                                            },
                                        ]}
                                        value={bypassFilters.status}
                                        onValueChange={(value) =>
                                            setBypassFilters((current) => ({
                                                ...current,
                                                status: value,
                                            }))
                                        }
                                        placeholder="Semua status"
                                        searchPlaceholder="Cari status..."
                                        className="h-10"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-medium tracking-[0.13em] text-muted-foreground uppercase">
                                        Dibuat dari
                                    </label>
                                    <DatePicker
                                        value={bypassFilters.createdFrom}
                                        onChange={(value) =>
                                            setBypassFilters((current) => ({
                                                ...current,
                                                createdFrom: value,
                                            }))
                                        }
                                        placeholder="Pilih tanggal"
                                        className="h-10 w-full"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-medium tracking-[0.13em] text-muted-foreground uppercase">
                                        Sampai
                                    </label>
                                    <DatePicker
                                        value={bypassFilters.createdTo}
                                        onChange={(value) =>
                                            setBypassFilters((current) => ({
                                                ...current,
                                                createdTo: value,
                                            }))
                                        }
                                        placeholder="Pilih tanggal"
                                        className="h-10 w-full"
                                    />
                                </div>
                            </div>

                            {approvedRequests.length === 0 && (
                                <div className="rounded-2xl border border-dashed border-neutral-300 p-6 text-sm text-muted-foreground dark:border-neutral-700">
                                    Belum ada request bypass yang disetujui.
                                </div>
                            )}

                            {groupApprovedBypasses.length > 0 && (
                                <div className="space-y-4 pt-2">
                                    <div className="flex items-center gap-2">
                                        <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                                            Akses yang sudah disetujui
                                        </h3>
                                        <Badge variant="outline">
                                            {groupApprovedBypasses.length} user
                                        </Badge>
                                    </div>

                                    {groupApprovedBypasses.map((group) => {
                                        const isExpanded = Boolean(
                                            expandedApprovedGroups[group.key],
                                        );
                                        const allActive = group.items.some(
                                            (item) => item.is_active,
                                        );
                                        const totalActive = group.items.filter(
                                            (item) => item.is_active,
                                        ).length;
                                        const overallRevokeDisabled =
                                            totalActive === 0;

                                        return (
                                            <div
                                                key={group.key}
                                                className="rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm dark:border-emerald-800 dark:bg-neutral-900/60"
                                            >
                                                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                                    <div className="space-y-2">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <p className="font-semibold text-neutral-900 dark:text-white">
                                                                {group.userName}
                                                            </p>
                                                            <Badge
                                                                className={
                                                                    allActive
                                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                                        : 'bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200'
                                                                }
                                                            >
                                                                {allActive
                                                                    ? 'Masih aktif'
                                                                    : 'Tidak aktif'}
                                                            </Badge>
                                                        </div>

                                                        <div className="grid gap-1 text-sm text-muted-foreground sm:grid-cols-2">
                                                            <span>
                                                                Jumlah akses:{' '}
                                                                {
                                                                    group.items
                                                                        .length
                                                                }
                                                            </span>
                                                            <span>
                                                                Periode: Bulan{' '}
                                                                {formatMonthYearLabel(
                                                                    group
                                                                        .items[0]
                                                                        ?.month,
                                                                    group
                                                                        .items[0]
                                                                        ?.year,
                                                                )}
                                                            </span>
                                                            <span className="sm:col-span-2">
                                                                Tanggal dibuat:{' '}
                                                                {formatCreatedAtLabel(
                                                                    group
                                                                        .items[0]
                                                                        ?.created_at,
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                toggleApprovedGroup(
                                                                    group.key,
                                                                )
                                                            }
                                                        >
                                                            {isExpanded
                                                                ? 'Sembunyikan'
                                                                : 'Lihat fitur'}
                                                        </Button>
                                                        {!overallRevokeDisabled && (
                                                            <Button
                                                                size="sm"
                                                                variant="destructive"
                                                                onClick={() =>
                                                                    requestBulkRevoke(
                                                                        group.items
                                                                            .filter(
                                                                                (
                                                                                    item,
                                                                                ) =>
                                                                                    item.is_active,
                                                                            )
                                                                            .map(
                                                                                (
                                                                                    item,
                                                                                ) =>
                                                                                    item.id,
                                                                            ),
                                                                        group.userName,
                                                                        `Cabut semua akses aktif untuk ${group.userName}?`,
                                                                    )
                                                                }
                                                            >
                                                                Cabut semua
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>

                                                {isExpanded && (
                                                    <div className="mt-4 space-y-3 rounded-xl border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-800 dark:bg-neutral-950/40">
                                                        {group.items.map(
                                                            (item) => (
                                                                <div
                                                                    key={
                                                                        item.id
                                                                    }
                                                                    className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-3 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800 dark:bg-neutral-950/60"
                                                                >
                                                                    <div className="space-y-1">
                                                                        <div className="flex items-center gap-2">
                                                                            <p className="font-medium text-neutral-900 dark:text-white">
                                                                                {item.rule_label ||
                                                                                    item.rule_key ||
                                                                                    'Akses'}
                                                                            </p>
                                                                            <span
                                                                                className={[
                                                                                    'rounded-full px-2 py-1 text-[11px] font-semibold',
                                                                                    getBypassStatusTone(
                                                                                        item,
                                                                                    ),
                                                                                ].join(
                                                                                    ' ',
                                                                                )}
                                                                            >
                                                                                {getBypassStatus(
                                                                                    item,
                                                                                )}
                                                                            </span>
                                                                        </div>
                                                                        <p className="text-xs text-muted-foreground">
                                                                            {item.reason ||
                                                                                'Tidak ada alasan'}
                                                                        </p>
                                                                    </div>

                                                                    <div className="flex items-center gap-2">
                                                                        {!item.is_active && (
                                                                            <span className="text-xs text-muted-foreground">
                                                                                {item.reason
                                                                                    ?.toLowerCase()
                                                                                    .includes(
                                                                                        'dicabut',
                                                                                    ) ||
                                                                                item.reason
                                                                                    ?.toLowerCase()
                                                                                    .includes(
                                                                                        'cabut',
                                                                                    )
                                                                                    ? 'Akses dicabut'
                                                                                    : 'Sudah digunakan'}
                                                                            </span>
                                                                        )}
                                                                        {item.is_active && (
                                                                            <Button
                                                                                size="sm"
                                                                                variant="destructive"
                                                                                onClick={() =>
                                                                                    requestRevokeBypass(
                                                                                        item.id,
                                                                                        item.rule_label ||
                                                                                            item.rule_key ||
                                                                                            'Akses',
                                                                                        `Cabut akses ${item.rule_label || item.rule_key || 'Akses'} untuk ${group.userName}?`,
                                                                                    )
                                                                                }
                                                                                disabled={
                                                                                    revokeBypassId ===
                                                                                    item.id
                                                                                }
                                                                            >
                                                                                {revokeBypassId ===
                                                                                item.id
                                                                                    ? 'Mencabut...'
                                                                                    : 'Cabut akses'}
                                                                            </Button>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="space-y-4">
                            <div className="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/60">
                                <div className="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <h3 className="text-base font-semibold text-neutral-900 dark:text-white">
                                            Buka Akses manual
                                        </h3>
                                        <p className="text-sm text-muted-foreground">
                                            Beri izin akses sementara untuk user
                                            tertentu.
                                        </p>
                                    </div>
                                </div>

                                <div className="grid gap-3 md:grid-cols-3">
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-medium text-muted-foreground">
                                            User
                                        </label>
                                        <SearchableSelect
                                            options={userOptions}
                                            value={
                                                manualBypassForm.granted_for_user_id
                                            }
                                            onValueChange={(value) =>
                                                setManualBypassForm(
                                                    (current) => ({
                                                        ...current,
                                                        granted_for_user_id:
                                                            value,
                                                    }),
                                                )
                                            }
                                            placeholder="Pilih user"
                                            searchPlaceholder="Cari user..."
                                            className="h-10"
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <label className="text-xs font-medium text-muted-foreground">
                                            Jenis akses
                                        </label>
                                        <MultiSelectCheckbox
                                            options={ruleOptions}
                                            values={manualBypassForm.rule_ids}
                                            onValuesChange={(values) =>
                                                setManualBypassForm(
                                                    (current) => ({
                                                        ...current,
                                                        rule_ids: values,
                                                    }),
                                                )
                                            }
                                            placeholder="Pilih fitur"
                                            className="min-h-10"
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <label className="text-xs font-medium text-muted-foreground">
                                            Batas waktu
                                        </label>
                                        <DatePicker
                                            value={manualBypassForm.expires_at}
                                            onChange={(value) =>
                                                setManualBypassForm(
                                                    (current) => ({
                                                        ...current,
                                                        expires_at: value,
                                                    }),
                                                )
                                            }
                                            placeholder="Pilih tanggal selesai"
                                            min={new Date()
                                                .toISOString()
                                                .slice(0, 10)}
                                            max={new Date(
                                                new Date().getFullYear(),
                                                new Date().getMonth() + 1,
                                                0,
                                            )
                                                .toISOString()
                                                .slice(0, 10)}
                                            className="h-10"
                                        />
                                    </div>

                                    <div className="space-y-1.5 md:col-span-3">
                                        <label className="text-xs font-medium text-muted-foreground">
                                            Alasan (opsional)
                                        </label>
                                        <Textarea
                                            value={manualBypassForm.reason}
                                            onChange={(event) =>
                                                setManualBypassForm(
                                                    (current) => ({
                                                        ...current,
                                                        reason: event.target
                                                            .value,
                                                    }),
                                                )
                                            }
                                            placeholder="Contoh: kebutuhan mendesak proses persetujuan..."
                                            className="min-h-24 resize-none"
                                        />
                                    </div>
                                </div>

                                <div className="mt-4 flex justify-end">
                                    <Button
                                        onClick={handleGrantBypass}
                                        disabled={bypassSaving}
                                    >
                                        {bypassSaving
                                            ? 'Menyimpan...'
                                            : 'Berikan akses'}
                                    </Button>
                                </div>
                            </div>

                            <div className="grid gap-3 rounded-2xl border border-neutral-200 bg-neutral-50 p-3 md:grid-cols-4 dark:border-neutral-800 dark:bg-neutral-900/60">
                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-medium tracking-[0.13em] text-muted-foreground uppercase">
                                        Nama petugas
                                    </label>
                                    <MultiSelectCheckbox
                                        options={initialUsers.map((user) => ({
                                            value: user.id,
                                            label: user.name,
                                            subLabel: 'Petugas',
                                        }))}
                                        values={bypassFilters.userIds}
                                        onValuesChange={(values) =>
                                            setBypassFilters((current) => ({
                                                ...current,
                                                userIds: values,
                                            }))
                                        }
                                        placeholder="Pilih petugas"
                                        className="min-h-10"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-medium tracking-[0.13em] text-muted-foreground uppercase">
                                        Status bypass
                                    </label>
                                    <SearchableSelect
                                        options={[
                                            {
                                                value: 'all',
                                                label: 'Semua status',
                                                displayLabel: 'Semua status',
                                            },
                                            {
                                                value: 'aktif',
                                                label: 'Aktif',
                                                displayLabel: 'Aktif',
                                            },
                                            {
                                                value: 'tidak_aktif',
                                                label: 'Tidak aktif',
                                                displayLabel: 'Tidak aktif',
                                            },
                                        ]}
                                        value={bypassFilters.status}
                                        onValueChange={(value) =>
                                            setBypassFilters((current) => ({
                                                ...current,
                                                status: value,
                                            }))
                                        }
                                        placeholder="Semua status"
                                        searchPlaceholder="Cari status..."
                                        className="h-10"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-medium tracking-[0.13em] text-muted-foreground uppercase">
                                        Dibuat dari
                                    </label>
                                    <DatePicker
                                        value={bypassFilters.createdFrom}
                                        onChange={(value) =>
                                            setBypassFilters((current) => ({
                                                ...current,
                                                createdFrom: value,
                                            }))
                                        }
                                        placeholder="Pilih tanggal"
                                        className="h-10 w-full"
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[11px] font-medium tracking-[0.13em] text-muted-foreground uppercase">
                                        Sampai
                                    </label>
                                    <DatePicker
                                        value={bypassFilters.createdTo}
                                        onChange={(value) =>
                                            setBypassFilters((current) => ({
                                                ...current,
                                                createdTo: value,
                                            }))
                                        }
                                        placeholder="Pilih tanggal"
                                        className="h-10 w-full"
                                    />
                                </div>
                            </div>

                            {groupManualBypasses.length === 0 && (
                                <div className="rounded-2xl border border-dashed border-neutral-300 p-6 text-sm text-muted-foreground dark:border-neutral-700">
                                    Belum ada data bypass manual.
                                </div>
                            )}

                            {groupManualBypasses.map((group) => {
                                const isExpanded = Boolean(
                                    expandedManualGroups[group.key],
                                );
                                const allActive = group.items.some(
                                    (item) => item.is_active,
                                );
                                const totalActive = group.items.filter(
                                    (item) => item.is_active,
                                ).length;
                                const overallRevokeDisabled = totalActive === 0;

                                return (
                                    <div
                                        key={group.key}
                                        className="rounded-2xl border border-neutral-200 bg-neutral-50 p-4 shadow-sm transition-colors hover:border-emerald-300 dark:border-neutral-800 dark:bg-neutral-900/60 dark:hover:border-emerald-700"
                                    >
                                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div className="space-y-2">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-semibold text-neutral-900 dark:text-white">
                                                        {group.userName}
                                                    </p>
                                                    <Badge
                                                        className={
                                                            allActive
                                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                                : 'bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200'
                                                        }
                                                    >
                                                        {allActive
                                                            ? 'Masih aktif'
                                                            : 'Tidak aktif'}
                                                    </Badge>
                                                </div>

                                                <div className="grid gap-1 text-sm text-muted-foreground sm:grid-cols-2">
                                                    <span>
                                                        Jumlah akses:{' '}
                                                        {group.items.length}
                                                    </span>
                                                    <span>
                                                        Periode: Bulan{' '}
                                                        {formatMonthYearLabel(
                                                            group.items[0]
                                                                ?.month,
                                                            group.items[0]
                                                                ?.year,
                                                        )}
                                                    </span>
                                                    <span className="sm:col-span-2">
                                                        Tanggal dibuat:{' '}
                                                        {formatCreatedAtLabel(
                                                            group.items[0]
                                                                ?.created_at,
                                                        )}
                                                    </span>
                                                </div>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        toggleManualGroup(
                                                            group.key,
                                                        )
                                                    }
                                                >
                                                    {isExpanded
                                                        ? 'Sembunyikan'
                                                        : 'Lihat fitur'}
                                                </Button>
                                                {!overallRevokeDisabled && (
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() =>
                                                            requestBulkRevoke(
                                                                group.items
                                                                    .filter(
                                                                        (
                                                                            item,
                                                                        ) =>
                                                                            item.is_active,
                                                                    )
                                                                    .map(
                                                                        (
                                                                            item,
                                                                        ) =>
                                                                            item.id,
                                                                    ),
                                                                group.userName,
                                                                `Cabut semua akses aktif untuk ${group.userName}?`,
                                                            )
                                                        }
                                                    >
                                                        Cabut semua
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        {isExpanded && (
                                            <div className="mt-4 space-y-3 rounded-xl border border-neutral-200 bg-white p-3 dark:border-neutral-800 dark:bg-neutral-950/40">
                                                {group.items.map((item) => (
                                                    <div
                                                        key={item.id}
                                                        className="flex flex-col gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-3 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800 dark:bg-neutral-950/60"
                                                    >
                                                        <div className="space-y-1">
                                                            <div className="flex items-center gap-2">
                                                                <p className="font-medium text-neutral-900 dark:text-white">
                                                                    {item.rule_label ||
                                                                        item.rule_key ||
                                                                        'Akses'}
                                                                </p>
                                                                <span
                                                                    className={[
                                                                        'rounded-full px-2 py-1 text-[11px] font-semibold',
                                                                        getBypassStatusTone(
                                                                            item,
                                                                        ),
                                                                    ].join(' ')}
                                                                >
                                                                    {getBypassStatus(
                                                                        item,
                                                                    )}
                                                                </span>
                                                            </div>
                                                            <p className="text-xs text-muted-foreground">
                                                                {item.reason ||
                                                                    'Tidak ada alasan'}
                                                            </p>
                                                        </div>

                                                        <div className="flex items-center gap-2">
                                                            {!item.is_active && (
                                                                <span className="text-xs text-muted-foreground">
                                                                    {item.reason
                                                                        ?.toLowerCase()
                                                                        .includes(
                                                                            'dicabut',
                                                                        ) ||
                                                                    item.reason
                                                                        ?.toLowerCase()
                                                                        .includes(
                                                                            'cabut',
                                                                        )
                                                                        ? 'Akses dicabut'
                                                                        : 'Sudah digunakan'}
                                                                </span>
                                                            )}
                                                            {item.is_active && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="destructive"
                                                                    onClick={() =>
                                                                        requestRevokeBypass(
                                                                            item.id,
                                                                            item.rule_label ||
                                                                                item.rule_key ||
                                                                                'Akses',
                                                                            `Cabut akses ${item.rule_label || item.rule_key || 'Akses'} untuk ${group.userName}?`,
                                                                        )
                                                                    }
                                                                    disabled={
                                                                        revokeBypassId ===
                                                                        item.id
                                                                    }
                                                                >
                                                                    {revokeBypassId ===
                                                                    item.id
                                                                        ? 'Mencabut...'
                                                                        : 'Cabut akses'}
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </ContentCard>
            </div>

            {revokeConfirm.open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-5 shadow-xl dark:bg-neutral-900">
                        <div className="mb-3 flex items-start gap-3">
                            <div className="mt-1 rounded-full bg-red-100 p-2 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                <AlertTriangle className="h-4 w-4" />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    {revokeConfirm.label}
                                </h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {revokeConfirm.description}
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 flex justify-end gap-2">
                            <Button
                                variant="outline"
                                onClick={() =>
                                    setRevokeConfirm((current) => ({
                                        ...current,
                                        open: false,
                                    }))
                                }
                            >
                                Batal
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={confirmRevokeAction}
                            >
                                Ya, cabut
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {flashMessage.open && (
                <div className="fixed top-4 right-4 z-[60] w-[min(92vw,26rem)] animate-in duration-300 slide-in-from-top-4">
                    <div
                        className={[
                            'rounded-2xl border p-4 shadow-2xl backdrop-blur-xl',
                            flashMessage.type === 'success' &&
                                'border-green-400/30 bg-gradient-to-br from-green-500/10 via-green-400/5 to-green-300/10 text-green-900 dark:border-green-500/20 dark:from-green-600/10 dark:via-green-500/5 dark:to-green-400/10 dark:text-green-50',
                            flashMessage.type === 'error' &&
                                'border-red-400/30 bg-gradient-to-br from-red-500/10 via-red-400/5 to-red-300/10 text-red-900 dark:border-red-500/20 dark:from-red-600/10 dark:via-red-500/5 dark:to-red-400/10 dark:text-red-50',
                            flashMessage.type === 'warning' &&
                                'border-amber-400/30 bg-gradient-to-br from-amber-500/10 via-amber-400/5 to-amber-300/10 text-amber-900 dark:border-amber-500/20 dark:from-amber-600/10 dark:via-amber-500/5 dark:to-amber-400/10 dark:text-amber-50',
                            flashMessage.type === 'info' &&
                                'border-blue-400/30 bg-gradient-to-br from-blue-500/10 via-blue-400/5 to-blue-300/10 text-blue-900 dark:border-blue-500/20 dark:from-blue-600/10 dark:via-blue-500/5 dark:to-blue-400/10 dark:text-blue-50',
                        ]
                            .filter(Boolean)
                            .join(' ')}
                    >
                        <div className="flex items-start gap-3">
                            <div className="mt-0.5 shrink-0 text-current/90">
                                {flashMessage.type === 'success' && (
                                    <ShieldCheck className="h-5 w-5" />
                                )}
                                {flashMessage.type === 'error' && (
                                    <AlertTriangle className="h-5 w-5" />
                                )}
                                {flashMessage.type === 'warning' && (
                                    <AlertTriangle className="h-5 w-5" />
                                )}
                                {flashMessage.type === 'info' && (
                                    <Clock3 className="h-5 w-5" />
                                )}
                            </div>

                            <div className="min-w-0 flex-1">
                                <div className="text-base font-bold">
                                    {flashMessage.title}
                                </div>
                                <div className="mt-1 text-sm leading-relaxed">
                                    {flashMessage.message}
                                </div>
                            </div>

                            <button
                                type="button"
                                aria-label="Tutup notifikasi"
                                onClick={() =>
                                    setFlashMessage((current) => ({
                                        ...current,
                                        open: false,
                                    }))
                                }
                                className="shrink-0 rounded-md p-1 transition-colors hover:bg-black/5 dark:hover:bg-white/10"
                            >
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1.8"
                                    className="h-4 w-4"
                                >
                                    <path d="M5 5L15 15M15 5L5 15" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
