import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileCheck, FileText, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';

interface PeriodeData {
    bulan: number;
    bulan_label: string;
    tahun: number;
    total_spk: number;
    spk_with_bast: number;
    spk_without_bast: number;
    has_spk: boolean;
    all_completed: boolean;
    first_bast_hashed_id: string | null;
}

interface IndexProps {
    data: {
        encrypted: string;
    };
    filters: {
        search?: string;
    };
    active_year: number;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'BAST', href: '/bast' }];

type SummaryModalType =
    | 'all_spk_periods'
    | 'need_bast'
    | 'completed'
    | 'without_spk';

export default function Index({ data, active_year }: IndexProps) {
    const decryptedData = useDecryptedData<PeriodeData>(data.encrypted);
    const [summaryModalOpen, setSummaryModalOpen] = useState(false);
    const [summaryModalType, setSummaryModalType] =
        useState<SummaryModalType>('all_spk_periods');

    const summaryGroups = useMemo(() => {
        const periods = decryptedData ?? [];

        return {
            allSpkPeriods: periods.filter((item) => item.has_spk),
            needBast: periods.filter((item) => item.has_spk && !item.all_completed),
            completed: periods.filter((item) => item.has_spk && item.all_completed),
            withoutSpk: periods.filter((item) => !item.has_spk),
        };
    }, [decryptedData]);

    const summaryModalItems = useMemo(() => {
        switch (summaryModalType) {
            case 'need_bast':
                return summaryGroups.needBast;
            case 'completed':
                return summaryGroups.completed;
            case 'without_spk':
                return summaryGroups.withoutSpk;
            case 'all_spk_periods':
            default:
                return summaryGroups.allSpkPeriods;
        }
    }, [summaryGroups, summaryModalType]);

    const summaryModalTitle = useMemo(() => {
        switch (summaryModalType) {
            case 'need_bast':
                return 'Periode Perlu Generate BAST';
            case 'completed':
                return 'Periode BAST Selesai';
            case 'without_spk':
                return 'Periode Tanpa Perjanjian Kerja';
            case 'all_spk_periods':
            default:
                return 'Semua Periode Dengan Perjanjian Kerja';
        }
    }, [summaryModalType]);

    const openSummaryModal = (type: SummaryModalType) => {
        setSummaryModalType(type);
        setSummaryModalOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="BAST" />

            <div className="space-y-6">
                <PageHeader
                    title="Berita Acara Serah Terima (BAST)"
                    description={`Kelola BAST hasil pekerjaan petugas tahun ${active_year}`}
                />

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <button
                        type="button"
                        onClick={() => openSummaryModal('all_spk_periods')}
                        className="text-left"
                    >
                        <ContentCard className="transition-all hover:-translate-y-0.5 hover:shadow-md">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Periode Dengan Perjanjian Kerja
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        {summaryGroups.allSpkPeriods.length}
                                    </p>
                                </div>
                                <FileText className="h-5 w-5 text-neutral-400" />
                            </div>
                        </ContentCard>
                    </button>
                    <button
                        type="button"
                        onClick={() => openSummaryModal('need_bast')}
                        className="text-left"
                    >
                        <ContentCard className="transition-all hover:-translate-y-0.5 hover:shadow-md">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Perlu BAST
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        {summaryGroups.needBast.length}
                                    </p>
                                </div>
                                <Plus className="h-5 w-5 text-neutral-400" />
                            </div>
                        </ContentCard>
                    </button>
                    <button
                        type="button"
                        onClick={() => openSummaryModal('completed')}
                        className="text-left"
                    >
                        <ContentCard className="transition-all hover:-translate-y-0.5 hover:shadow-md">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        BAST Selesai
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        {summaryGroups.completed.length}
                                    </p>
                                </div>
                                <FileCheck className="h-5 w-5 text-neutral-400" />
                            </div>
                        </ContentCard>
                    </button>
                    <button
                        type="button"
                        onClick={() => openSummaryModal('without_spk')}
                        className="text-left"
                    >
                        <ContentCard className="transition-all hover:-translate-y-0.5 hover:shadow-md">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Tanpa Perjanjian Kerja
                                    </p>
                                    <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                        {summaryGroups.withoutSpk.length}
                                    </p>
                                </div>
                                <FileText className="h-5 w-5 text-neutral-400" />
                            </div>
                        </ContentCard>
                    </button>
                </div>

                {/* Table */}
                <ContentCard>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead className="bg-neutral-50 dark:bg-neutral-800">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Periode
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Total Perjanjian Kerja
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        BAST Dibuat
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Belum BAST
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                {decryptedData.map((item) => (
                                    <tr
                                        key={`${item.tahun}-${item.bulan}`}
                                        className="hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                    >
                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                            <div className="font-medium">
                                                {item.bulan_label} {item.tahun}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                            {item.total_spk}
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap">
                                            <span className="font-medium text-green-600 dark:text-green-400">
                                                {item.spk_with_bast}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap">
                                            <span className="font-medium text-orange-600 dark:text-orange-400">
                                                {item.spk_without_bast}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap">
                                            {!item.has_spk ? (
                                                <Badge variant="secondary">
                                                    Tidak ada Perjanjian Kerja
                                                </Badge>
                                            ) : item.all_completed ? (
                                                <Badge variant="default">
                                                    <FileCheck className="mr-1 h-3 w-3" />
                                                    Selesai
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    <FileText className="mr-1 h-3 w-3" />
                                                    Perlu BAST
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap">
                                            <div className="flex items-center justify-center gap-2">
                                                {item.has_spk &&
                                                    !item.all_completed && (
                                                        <Link
                                                            href={`/bast/create?bulan=${item.bulan}&tahun=${item.tahun}`}
                                                        >
                                                            <Button size="sm">
                                                                <Plus className="mr-1 h-4 w-4" />
                                                                Generate BAST
                                                            </Button>
                                                        </Link>
                                                    )}
                                                {item.spk_with_bast > 0 &&
                                                    item.first_bast_hashed_id && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/bast/${item.first_bast_hashed_id}`}
                                                            >
                                                                <FileText className="mr-1 h-4 w-4" />
                                                                Detail BAST
                                                            </Link>
                                                        </Button>
                                                    )}
                                                {item.has_spk &&
                                                    item.all_completed &&
                                                    item.spk_with_bast ===
                                                        0 && (
                                                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                            Tidak ada BAST
                                                        </span>
                                                    )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>

                <Dialog open={summaryModalOpen} onOpenChange={setSummaryModalOpen}>
                    <DialogContent className="max-w-4xl">
                        <DialogHeader>
                            <DialogTitle>{summaryModalTitle}</DialogTitle>
                            <DialogDescription>
                                Klik aksi pada periode untuk proses generate atau melihat detail BAST.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="max-h-[60vh] space-y-2 overflow-y-auto pr-1">
                            {summaryModalItems.length === 0 ? (
                                <div className="rounded-md border border-dashed border-neutral-300 px-4 py-8 text-center text-sm text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                                    Tidak ada data pada kategori ini.
                                </div>
                            ) : (
                                summaryModalItems.map((item) => (
                                    <div
                                        key={`summary-${item.tahun}-${item.bulan}`}
                                        className="flex items-center justify-between gap-3 rounded-md border border-neutral-200 px-3 py-2 dark:border-neutral-800"
                                    >
                                        <div>
                                            <p className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {item.bulan_label} {item.tahun}
                                            </p>
                                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                {item.total_spk} SPK ·{' '}
                                                {item.spk_with_bast} BAST dibuat ·{' '}
                                                {item.spk_without_bast} belum BAST
                                            </p>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            {item.has_spk && !item.all_completed && (
                                                <Button size="sm" asChild>
                                                    <Link
                                                        href={`/bast/create?bulan=${item.bulan}&tahun=${item.tahun}`}
                                                    >
                                                        <Plus className="h-3.5 w-3.5" />
                                                    </Link>
                                                </Button>
                                            )}

                                            {item.spk_with_bast > 0 &&
                                                item.first_bast_hashed_id && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/bast/${item.first_bast_hashed_id}`}
                                                        >
                                                            <FileText className="h-3.5 w-3.5" />
                                                        </Link>
                                                    </Button>
                                                )}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
