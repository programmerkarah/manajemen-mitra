import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import Download from 'lucide-react/icons/download';
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
        spk_sensus_pengganti:
            spkPerBulan[index]?.sensus_pengganti_diterbitkan ?? 0,
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
}: AnalisisDokumenProps) {
    const trendData = buildTrendData({ skPerBulan, spkPerBulan });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Dokumen SK dan Perjanjian Kerja" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Analisis Dokumen SK dan Perjanjian Kerja"
                    description={`Tahun ${currentYear}`}
                >
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() =>
                            window.open(
                                '/analisis/dokumen/export-pdf',
                                '_blank',
                                'noopener,noreferrer',
                            )
                        }
                    >
                        <Download className="h-4 w-4" />
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
