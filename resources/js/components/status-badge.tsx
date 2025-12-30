import { cn } from '@/lib/utils';
import {
    AlertCircle,
    AlertTriangle,
    Ban,
    Briefcase,
    CheckCircle2,
    ClipboardCheck,
    Clock,
    Crown,
    Edit,
    Eye,
    FileCheck,
    FileText,
    Mail,
    RefreshCw,
    Send,
    ShieldCheck,
    ShieldX,
    UserCog,
    Users,
    XCircle,
} from 'lucide-react';

interface StatusBadgeProps {
    status: string;
    variant?: 'default' | 'large';
    showIcon?: boolean;
    label?: string;
}

export function StatusBadge({
    status,
    variant = 'default',
    showIcon = true,
    label,
}: StatusBadgeProps) {
    const statusConfig: Record<
        string,
        {
            label: string;
            bgColor: string;
            textColor: string;
            borderColor: string;
            icon: typeof CheckCircle2;
        }
    > = {
        // Alokasi statuses
        draft: {
            label: 'Draft',
            bgColor: 'bg-slate-100 dark:bg-slate-800',
            textColor: 'text-slate-800 dark:text-slate-200',
            borderColor: 'border-slate-300 dark:border-slate-600',
            icon: Edit,
        },
        dikirim: {
            label: 'Sudah Dikirim',
            bgColor: 'bg-blue-100 dark:bg-blue-900',
            textColor: 'text-blue-900 dark:text-blue-200',
            borderColor: 'border-blue-300 dark:border-blue-600',
            icon: Send,
        },
        disetujui: {
            label: 'Disetujui',
            bgColor: 'bg-green-100 dark:bg-green-900',
            textColor: 'text-green-900 dark:text-green-200',
            borderColor: 'border-green-300 dark:border-green-600',
            icon: CheckCircle2,
        },
        ditolak: {
            label: 'Ditolak',
            bgColor: 'bg-red-100 dark:bg-red-900',
            textColor: 'text-red-900 dark:text-red-200',
            borderColor: 'border-red-300 dark:border-red-600',
            icon: XCircle,
        },
        diajukan: {
            label: 'Menunggu Persetujuan',
            bgColor: 'bg-amber-100 dark:bg-amber-900',
            textColor: 'text-amber-900 dark:text-amber-200',
            borderColor: 'border-amber-300 dark:border-amber-600',
            icon: Clock,
        },
        perubahan: {
            label: 'Ada Perubahan',
            bgColor: 'bg-purple-100 dark:bg-purple-900',
            textColor: 'text-purple-900 dark:text-purple-200',
            borderColor: 'border-purple-300 dark:border-purple-600',
            icon: AlertTriangle,
        },
        direvisi: {
            label: 'Sudah Direvisi',
            bgColor: 'bg-indigo-100 dark:bg-indigo-900',
            textColor: 'text-indigo-900 dark:text-indigo-200',
            borderColor: 'border-indigo-300 dark:border-indigo-600',
            icon: FileCheck,
        },
        dihapus: {
            label: 'Dihapus',
            bgColor: 'bg-gray-100 dark:bg-gray-800',
            textColor: 'text-gray-800 dark:text-gray-300',
            borderColor: 'border-gray-300 dark:border-gray-600',
            icon: Ban,
        },
        // Kegiatan statuses
        aktif: {
            label: 'Aktif',
            bgColor: 'bg-green-100 dark:bg-green-900',
            textColor: 'text-green-900 dark:text-green-200',
            borderColor: 'border-green-300 dark:border-green-600',
            icon: CheckCircle2,
        },
        divalidasi: {
            label: 'Sudah Divalidasi',
            bgColor: 'bg-blue-100 dark:bg-blue-900',
            textColor: 'text-blue-900 dark:text-blue-200',
            borderColor: 'border-blue-300 dark:border-blue-600',
            icon: FileCheck,
        },
        selesai: {
            label: 'Selesai',
            bgColor: 'bg-teal-100 dark:bg-teal-900',
            textColor: 'text-teal-900 dark:text-teal-200',
            borderColor: 'border-teal-300 dark:border-teal-600',
            icon: CheckCircle2,
        },
        dibatalkan: {
            label: 'Dibatalkan',
            bgColor: 'bg-red-100 dark:bg-red-900',
            textColor: 'text-red-900 dark:text-red-200',
            borderColor: 'border-red-300 dark:border-red-600',
            icon: XCircle,
        },
        // Petugas statuses
        nonaktif: {
            label: 'Tidak Aktif',
            bgColor: 'bg-gray-100 dark:bg-gray-800',
            textColor: 'text-gray-800 dark:text-gray-300',
            borderColor: 'border-gray-300 dark:border-gray-600',
            icon: Ban,
        },
        // SK KPA & SPK statuses
        not_created: {
            label: 'Belum Dibuat',
            bgColor: 'bg-gray-100 dark:bg-gray-800',
            textColor: 'text-gray-800 dark:text-gray-300',
            borderColor: 'border-gray-300 dark:border-gray-600',
            icon: FileText,
        },
        created: {
            label: 'Sudah Dibuat',
            bgColor: 'bg-green-100 dark:bg-green-900',
            textColor: 'text-green-900 dark:text-green-200',
            borderColor: 'border-green-300 dark:border-green-600',
            icon: CheckCircle2,
        },
        revision: {
            label: 'Revisi',
            bgColor: 'bg-blue-100 dark:bg-blue-900',
            textColor: 'text-blue-900 dark:text-blue-200',
            borderColor: 'border-blue-300 dark:border-blue-600',
            icon: RefreshCw,
        },
        // Rekap Honor statuses
        normal: {
            label: 'Normal',
            bgColor: 'bg-green-100 dark:bg-green-900',
            textColor: 'text-green-900 dark:text-green-200',
            borderColor: 'border-green-300 dark:border-green-600',
            icon: CheckCircle2,
        },
        mendekati_batas: {
            label: 'Mendekati Batas',
            bgColor: 'bg-amber-100 dark:bg-amber-900',
            textColor: 'text-amber-900 dark:text-amber-200',
            borderColor: 'border-amber-300 dark:border-amber-600',
            icon: AlertTriangle,
        },
        melebihi_batas: {
            label: 'Melebihi Batas',
            bgColor: 'bg-red-100 dark:bg-red-900',
            textColor: 'text-red-900 dark:text-red-200',
            borderColor: 'border-red-300 dark:border-red-600',
            icon: AlertCircle,
        },
        // Email verification statuses
        terverifikasi: {
            label: 'Terverifikasi',
            bgColor: 'bg-green-100 dark:bg-green-900',
            textColor: 'text-green-900 dark:text-green-200',
            borderColor: 'border-green-300 dark:border-green-600',
            icon: Mail,
        },
        belum_verifikasi: {
            label: 'Belum Verifikasi',
            bgColor: 'bg-amber-100 dark:bg-amber-900',
            textColor: 'text-amber-900 dark:text-amber-200',
            borderColor: 'border-amber-300 dark:border-amber-600',
            icon: Clock,
        },
        // 2FA statuses
        '2fa_aktif': {
            label: '2FA Aktif',
            bgColor: 'bg-blue-100 dark:bg-blue-900',
            textColor: 'text-blue-900 dark:text-blue-200',
            borderColor: 'border-blue-300 dark:border-blue-600',
            icon: ShieldCheck,
        },
        '2fa_nonaktif': {
            label: 'Tidak Aktif',
            bgColor: 'bg-gray-100 dark:bg-gray-800',
            textColor: 'text-gray-800 dark:text-gray-300',
            borderColor: 'border-gray-300 dark:border-gray-600',
            icon: ShieldX,
        },
        // User Roles
        admin: {
            label: 'Admin',
            bgColor: 'bg-purple-100 dark:bg-purple-900',
            textColor: 'text-purple-900 dark:text-purple-200',
            borderColor: 'border-purple-300 dark:border-purple-600',
            icon: Crown,
        },
        pj: {
            label: 'Penanggung Jawab',
            bgColor: 'bg-blue-100 dark:bg-blue-900',
            textColor: 'text-blue-900 dark:text-blue-200',
            borderColor: 'border-blue-300 dark:border-blue-600',
            icon: Users,
        },
        operator: {
            label: 'Operator',
            bgColor: 'bg-slate-100 dark:bg-slate-800',
            textColor: 'text-slate-800 dark:text-slate-200',
            borderColor: 'border-slate-300 dark:border-slate-600',
            icon: UserCog,
        },
        guest: {
            label: 'Guest',
            bgColor: 'bg-gray-100 dark:bg-gray-800',
            textColor: 'text-gray-800 dark:text-gray-300',
            borderColor: 'border-gray-300 dark:border-gray-600',
            icon: Eye,
        },
        approver: {
            label: 'Approver',
            bgColor: 'bg-emerald-100 dark:bg-emerald-900',
            textColor: 'text-emerald-900 dark:text-emerald-200',
            borderColor: 'border-emerald-300 dark:border-emerald-600',
            icon: ClipboardCheck,
        },
        ketua_tim: {
            label: 'Ketua Tim',
            bgColor: 'bg-indigo-100 dark:bg-indigo-900',
            textColor: 'text-indigo-900 dark:text-indigo-200',
            borderColor: 'border-indigo-300 dark:border-indigo-600',
            icon: Briefcase,
        },
    };

    const config = statusConfig[status.toLowerCase()] || {
        label: status,
        bgColor: 'bg-gray-100 dark:bg-gray-800',
        textColor: 'text-gray-800 dark:text-gray-300',
        borderColor: 'border-gray-300 dark:border-gray-600',
        icon: AlertTriangle,
    };

    const Icon = config.icon;

    const sizeClasses =
        variant === 'large'
            ? 'px-4 py-2 text-base gap-2.5'
            : 'px-3 py-1.5 text-sm gap-2';

    const iconSize = variant === 'large' ? 'h-5 w-5' : 'h-4 w-4';

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full border-2 font-semibold shadow-sm',
                config.bgColor,
                config.textColor,
                config.borderColor,
                sizeClasses,
            )}
        >
            {showIcon && (
                <Icon className={cn(iconSize, 'shrink-0')} strokeWidth={2.5} />
            )}
            {label || config.label}
        </span>
    );
}
