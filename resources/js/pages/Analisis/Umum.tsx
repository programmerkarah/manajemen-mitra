import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import Download from 'lucide-react/icons/download';
import { AllocationTrendChart } from './Umum/components/AllocationTrendChart';
import { BudgetUtilizationTable } from './Umum/components/BudgetUtilizationTable';
import { TopPetugasTable } from './Umum/components/TopPetugasTable';
import { WorkloadAndActivitySummary } from './Umum/components/WorkloadAndActivitySummary';
import { GeneralKpiGrid } from './Umum/components/GeneralKpiGrid';
import type { AnalisisUmumProps } from './Umum/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Analisis Umum', href: '/analisis/umum' },
];

export default function AnalisisUmum({
    utilisasiAnggaran,
    distribusiBebanKerja,
    trenAlokasi,
    ringkasanKPI,
    ringkasanJenisKegiatan,
    topPetugas,
    currentYear,
    currentMonth,
}: AnalisisUmumProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Umum" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <PageHeader
                    title="Analisis Umum"
                    description={`Ringkasan anggaran, beban kerja, dan tren alokasi · Tahun ${currentYear}`}
                >
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() =>
                            window.open(
                                '/analisis/umum/export-pdf',
                                '_blank',
                                'noopener,noreferrer',
                            )
                        }
                    >
                        <Download className="h-4 w-4" />
                        Export PDF
                    </Button>
                </PageHeader>

                <GeneralKpiGrid
                    ringkasan={ringkasanKPI}
                    currentYear={currentYear}
                />

                <AllocationTrendChart data={trenAlokasi} />

                <WorkloadAndActivitySummary
                    workloadDistribution={distribusiBebanKerja}
                    activitySummary={ringkasanJenisKegiatan}
                />

                <TopPetugasTable
                    data={topPetugas}
                    currentYear={currentYear}
                    currentMonth={currentMonth}
                />

                <BudgetUtilizationTable data={utilisasiAnggaran} />
            </div>
        </AppLayout>
    );
}
