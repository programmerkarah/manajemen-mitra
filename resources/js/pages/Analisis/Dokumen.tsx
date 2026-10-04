import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ContentCard } from '@/components/content-card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import AlertTriangle from 'lucide-react/icons/alert-triangle';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import Download from 'lucide-react/icons/download';
import FileWarning from 'lucide-react/icons/file-warning';
import DocumentKpiGrid from './Dokumen/components/DocumentKpiGrid';
import DocumentTrendCharts from './Dokumen/components/DocumentTrendCharts';
import SkCompletenessTable from './Dokumen/components/SkCompletenessTable';
import StaleDraftAlert from './Dokumen/components/StaleDraftAlert';
import { monthNames } from './Dokumen/constants';
import type {
    AnalisisDokumenProps,
    TrenDokumenItem,
} from './Dokumen/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Analisis Dokumen', href: '/analisis/dokumen' },
];

function buildTrendData({
    skPerBulan,
    spkPerBulan,
}: Pick<
    AnalisisDokumenProps,
    'skPerBulan' | 'spkPerBulan'
>): TrenDokumenItem[] {
    return skPerBulan.map((sk, index) => ({
        name: monthNames[index],
        sk_diterbitkan: sk.diterbitkan + sk.ditandatangani,
        sk_draft: sk.draft,
        spk_diterbitkan: spkPerBulan[index]?.diterbitkan ?? 0,
        spk_reguler: spkPerBulan[index]?.reguler_diterbitkan ?? 0,
        spk_sensus_utama:
            spkPerBulan[index]?.sensus_utama_diterbitkan ?? 0,
        spk_draft: spkPerBulan[index]?.draft ?? 0,
    }));
}

export default function AnalisisDokumen({
    skPerBulan,
    spkPerBulan,
    skTotal,
    skDiterbitkan,
    skDraft,
    spkTotal,
    spkDiterbitkan,
    spkDraft,
    kelengkapanSKPerKegiatan,
    skDraftLama,
    currentYear,
    availableYears,
}: AnalisisDokumenProps) {
    const trendData = buildTrendData({ skPerBulan, spkPerBulan });
    const kegiatanLengkap = kelengkapanSKPerKegiatan.filter(
        (item) => item.status_dokumen === 'lengkap',
    ).length;
    const kegiatanPerluTindakLanjut = kelengkapanSKPerKegiatan.filter(
        (item) => item.status_dokumen !== 'lengkap',
    ).length;
    const completionRate =
        kelengkapanSKPerKegiatan.length > 0
            ? Math.round(
                  (kegiatanLengkap / kelengkapanSKPerKegiatan.length) * 100,
              )
            : 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Dokumen SK dan Perjanjian Kerja" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Analisis Dokumen"
                    description="Pantau kelengkapan SK dan Perjanjian Kerja, identifikasi draft tertunda, dan pastikan export menggunakan dataset yang sama dengan tampilan."
                >
                    <Select
                        value={String(currentYear)}
                        onValueChange={(year) =>
                            router.get(
                                '/analisis/dokumen',
                                { year },
                                {
                                    preserveScroll: true,
                                    preserveState: true,
                                    replace: true,
                                },
                            )
                        }
                    >
                        <SelectTrigger className="w-32">
                            <SelectValue placeholder="Tahun" />
                        </SelectTrigger>
                        <SelectContent>
                            {availableYears.map((year) => (
                                <SelectItem
                                    key={year}
                                    value={String(year)}
                                >
                                    {year}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() =>
                            window.open(
                                `/analisis/dokumen/export-pdf?year=${currentYear}`,
                                '_blank',
                                'noopener,noreferrer',
                            )
                        }
                    >
                        <Download className="mr-2 h-4 w-4" />
                        Export PDF
                    </Button>
                </PageHeader>

                <DocumentKpiGrid
                    skTotal={skTotal}
                    skDiterbitkan={skDiterbitkan}
                    skDraft={skDraft}
                    spkTotal={spkTotal}
                    spkDiterbitkan={spkDiterbitkan}
                    spkDraft={spkDraft}
                />

                <div className="grid gap-3 md:grid-cols-3">
                    <ContentCard>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Kelengkapan kegiatan
                                </p>
                                <p className="mt-1 text-2xl font-semibold">
                                    {completionRate}%
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {kegiatanLengkap} dari{' '}
                                    {kelengkapanSKPerKegiatan.length} kegiatan
                                </p>
                            </div>
                            <CheckCircle2 className="size-5 text-emerald-600" />
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Perlu tindak lanjut
                                </p>
                                <p className="mt-1 text-2xl font-semibold">
                                    {kegiatanPerluTindakLanjut}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Belum ada SK atau masih memiliki draft
                                </p>
                            </div>
                            <AlertTriangle className="size-5 text-amber-600" />
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Draft lama
                                </p>
                                <p className="mt-1 text-2xl font-semibold">
                                    {skDraftLama.length}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    SK draft lebih dari 14 hari
                                </p>
                            </div>
                            <FileWarning className="size-5 text-rose-600" />
                        </div>
                    </ContentCard>
                </div>

                <StaleDraftAlert items={skDraftLama} />

                <SkCompletenessTable
                    items={kelengkapanSKPerKegiatan}
                    currentYear={currentYear}
                />

                <DocumentTrendCharts data={trendData} />
            </div>
        </AppLayout>
    );
}
