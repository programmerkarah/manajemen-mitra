import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import Download from 'lucide-react/icons/download';
import { PetugasAllocationDetailTable } from './Petugas/components/PetugasAllocationDetailTable';
import { PetugasDemographicCharts } from './Petugas/components/PetugasDemographicCharts';
import { PetugasKpiGrid } from './Petugas/components/PetugasKpiGrid';
import { RegionalDistributionTable } from './Petugas/components/RegionalDistributionTable';
import { RecurringPetugasTable } from './Petugas/components/RecurringPetugasTable';
import { UnallocatedPetugasTable } from './Petugas/components/UnallocatedPetugasTable';
import { MonthlyAllocationChart } from './Petugas/components/MonthlyAllocationChart';
import { PetugasComparisonChart } from './Petugas/components/PetugasComparisonChart';
import { PetugasHonorTable } from './Petugas/components/PetugasHonorTable';
import { PetugasKegiatanMappingTable } from './Petugas/components/PetugasKegiatanMappingTable';
import type { AnalisisPetugasProps } from './Petugas/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Analisis Petugas', href: '/analisis/petugas' },
];

export default function AnalisisPetugas({
    distribusiJenisKelamin,
    distribusiKecamatan,
    distribusiDesaKelurahan,
    distribusiTugasDesaKelurahan,
    distribusiUsia,
    distribusiPendidikan,
    alokasiPerBulan,
    petugasKegiatan,
    kegiatanList,
    petugasAlokasiDetail,
    petugasList,
    petugasBelumDialokasikan,
    petugasRutin,
    totalPetugas,
    currentYear,
}: AnalisisPetugasProps) {
    const totalAlokasiTahun = alokasiPerBulan.reduce(
        (sum, item) => sum + item.jumlah_petugas,
        0,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analisis Petugas Non-Organik" />
            <div className="flex flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <PageHeader
                    title="Analisis Petugas Non-Organik"
                    description={`Tahun ${currentYear} · Total petugas non-organik aktif: ${totalPetugas}`}
                >
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() =>
                            window.open(
                                '/analisis/petugas/export-pdf',
                                '_blank',
                                'noopener,noreferrer',
                            )
                        }
                    >
                        <Download className="h-4 w-4" />
                        Export PDF
                    </Button>
                </PageHeader>

                <PetugasKpiGrid
                    totalPetugas={totalPetugas}
                    belumDialokasikan={petugasBelumDialokasikan.length}
                    totalAlokasiTahun={totalAlokasiTahun}
                />

                <PetugasDemographicCharts
                    jenisKelamin={distribusiJenisKelamin}
                    usia={distribusiUsia}
                    kecamatan={distribusiKecamatan}
                    desaKelurahan={distribusiDesaKelurahan}
                    pendidikan={distribusiPendidikan}
                />

                <RegionalDistributionTable
                    data={distribusiTugasDesaKelurahan}
                />

                <MonthlyAllocationChart data={alokasiPerBulan} />

                <UnallocatedPetugasTable
                    data={petugasBelumDialokasikan}
                />

                <RecurringPetugasTable data={petugasRutin} />

                <PetugasComparisonChart
                    petugasList={petugasList}
                    allocationDetails={petugasAlokasiDetail}
                />

                <PetugasAllocationDetailTable data={petugasAlokasiDetail} />

                <PetugasHonorTable data={petugasAlokasiDetail} />

                <PetugasKegiatanMappingTable
                    petugasKegiatan={petugasKegiatan}
                    kegiatanList={kegiatanList}
                />
            </div>
        </AppLayout>
    );
}
