import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { PageHeader } from '@/components/page-header';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import BarChart2 from 'lucide-react/icons/bar-chart2';
import Download from 'lucide-react/icons/download';

import { useState } from 'react';
import {    Cell,
    Tooltip as ChartTooltip,    Pie,
    PieChart,
    type PieSectorShapeProps,
    ResponsiveContainer,} from 'recharts';
import {
    buildPieLegendItems,
    COLORS,
    formatRupiah,
    formatRupiahCompact,    PieLegendList,
    renderActivePieShape,
    serapanBarColor,
    serapanColor,
} from './Umum/helpers';
import { AllocationTrendChart } from './Umum/components/AllocationTrendChart';
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
    const [activePieIndex, setActivePieIndex] = useState<number | undefined>(
        undefined,
    );

    const filteredUtilisasi = utilisasiAnggaran.filter((u) => u.total_pagu > 0);
    const distribusiBebanKerjaChartData = distribusiBebanKerja.filter(
        (item) => item.count > 0,
    );
    const totalDistribusiBebanKerja = distribusiBebanKerjaChartData.reduce(
        (sum, item) => sum + item.count,
        0,
    );
    const distribusiBebanKerjaLegendItems = buildPieLegendItems(
        distribusiBebanKerjaChartData,
        (item) => item.label,
        totalDistribusiBebanKerja,
    );

    const maxTopHonor = topPetugas.length > 0 ? topPetugas[0].total_honor : 1;

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

                {/* Charts Row: Beban Kerja + Jenis Kegiatan */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Distribusi Beban Kerja */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Beban Kerja Petugas
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Sebaran petugas non-organik berdasarkan jumlah
                            kegiatan yang ditangani
                        </p>
                        <ResponsiveContainer width="100%" height={280}>
                            <PieChart
                                style={{ overflow: 'visible' }}
                                margin={{
                                    top: 10,
                                    right: 70,
                                    bottom: 10,
                                    left: 70,
                                }}
                                onClick={() => setActivePieIndex(undefined)}
                            >
                                <Pie
                                    data={distribusiBebanKerjaChartData}
                                    dataKey="count"
                                    nameKey="label"
                                    cx="50%"
                                    cy="50%"
                                    innerRadius={54}
                                    outerRadius={86}
                                    paddingAngle={2}
                                    labelLine={false}
                                    shape={(p: PieSectorShapeProps) =>
                                        renderActivePieShape(p, activePieIndex)
                                    }
                                    onClick={(_, idx, e) => {
                                        e.stopPropagation();
                                        setActivePieIndex(
                                            idx === activePieIndex
                                                ? undefined
                                                : idx,
                                        );
                                    }}
                                    cursor="pointer"
                                    stroke="none"
                                >
                                    {distribusiBebanKerjaChartData.map(
                                        (_, index) => (
                                            <Cell
                                                key={`beban-kerja-cell-${index}`}
                                                fill={
                                                    COLORS[
                                                        index % COLORS.length
                                                    ]
                                                }
                                            />
                                        ),
                                    )}
                                </Pie>
                            </PieChart>
                        </ResponsiveContainer>
                        <PieLegendList
                            items={distribusiBebanKerjaLegendItems}
                        />
                    </div>

                    {/* Ringkasan per Jenis Kegiatan */}
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            Penyerapan Anggaran per Jenis Kegiatan
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Perbandingan pagu dan penyerapan anggaran
                            berdasarkan jenis kegiatan
                        </p>
                        {ringkasanJenisKegiatan.length > 0 ? (
                            <div className="space-y-4">
                                {ringkasanJenisKegiatan.map((jenis) => (
                                    <div key={jenis.jenis}>
                                        <div className="mb-1 flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <BarChart2 className="h-3.5 w-3.5 text-neutral-400" />
                                                <span className="text-sm font-medium text-neutral-800 dark:text-neutral-200">
                                                    {jenis.label}
                                                </span>
                                                <span className="rounded-full bg-neutral-100 px-1.5 py-0.5 text-xs text-neutral-500 dark:bg-neutral-700 dark:text-neutral-400">
                                                    {jenis.jumlah_kegiatan} keg
                                                </span>
                                            </div>
                                            <span
                                                className={`text-sm font-bold ${serapanColor(jenis.serapan_persen)}`}
                                            >
                                                {jenis.serapan_persen}%
                                            </span>
                                        </div>
                                        <div className="mb-1 h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                            <div
                                                className="h-full rounded-full transition-all"
                                                style={{
                                                    width: `${Math.min(jenis.serapan_persen, 100)}%`,
                                                    backgroundColor:
                                                        serapanBarColor(
                                                            jenis.serapan_persen,
                                                        ),
                                                }}
                                            />
                                        </div>
                                        <div className="flex justify-between text-xs text-neutral-500 dark:text-neutral-400">
                                            <span>
                                                Terpakai:{' '}
                                                {formatRupiahCompact(
                                                    jenis.total_terpakai,
                                                )}
                                            </span>
                                            <span>
                                                Pagu:{' '}
                                                {formatRupiahCompact(
                                                    jenis.total_pagu,
                                                )}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="py-10 text-center text-sm text-neutral-400">
                                Belum ada data kegiatan
                            </p>
                        )}
                    </div>
                </div>

                {/* Top 10 Petugas */}
                {topPetugas.length > 0 && (
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                            10 Petugas dengan Honor Tertinggi
                        </h3>
                        <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                            Petugas non-organik dengan total honor terbesar s.d.{' '}
                            {new Date(
                                currentYear,
                                currentMonth - 1,
                            ).toLocaleDateString('id-ID', {
                                month: 'long',
                                year: 'numeric',
                            })}{' '}
                            (dengan bobot alokasi SE2026)
                        </p>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                        <th className="w-8 py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            #
                                        </th>
                                        <th className="py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Nama
                                        </th>
                                        <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Kegiatan
                                        </th>
                                        <th className="min-w-[200px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                            Total Honor
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {topPetugas.map((petugas, index) => {
                                        const barPct =
                                            maxTopHonor > 0
                                                ? (petugas.total_honor /
                                                      maxTopHonor) *
                                                  100
                                                : 0;
                                        return (
                                            <tr
                                                key={petugas.petugas_id}
                                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                                            >
                                                <td className="py-2 text-center">
                                                    <span
                                                        className={`inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${
                                                            index === 0
                                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400'
                                                                : index === 1
                                                                  ? 'bg-neutral-200 text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300'
                                                                  : index === 2
                                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                                                    : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400'
                                                        }`}
                                                    >
                                                        {index + 1}
                                                    </span>
                                                </td>
                                                <td className="py-2">
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {petugas.nama}
                                                    </div>
                                                    {petugas.jabatan && (
                                                        <div className="text-xs text-neutral-400 dark:text-neutral-500">
                                                            {petugas.jabatan}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="py-2 text-center text-neutral-600 dark:text-neutral-400">
                                                    {petugas.jumlah_kegiatan}
                                                </td>
                                                <td className="py-2">
                                                    <div className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                                                        {formatRupiah(
                                                            petugas.total_honor,
                                                        )}
                                                    </div>
                                                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                        <div
                                                            className="h-full rounded-full bg-blue-500 transition-all"
                                                            style={{
                                                                width: `${barPct}%`,
                                                            }}
                                                        />
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Utilisasi Anggaran per Kegiatan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                        Penyerapan Anggaran per Kegiatan
                    </h3>
                    <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                        Perbandingan pagu dan honor yang sudah dibayarkan per
                        kegiatan
                    </p>
                    {filteredUtilisasi.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="sticky top-0 bg-white/80 dark:bg-neutral-800/80">
                                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                            <th className="min-w-[200px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Kegiatan
                                            </th>
                                            <th className="min-w-[60px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Jenis
                                            </th>
                                            <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Pagu
                                            </th>
                                            <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Terpakai
                                            </th>
                                            <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                Sisa
                                            </th>
                                            <th className="min-w-[120px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                                % Penyerapan
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filteredUtilisasi.map((item) => (
                                            <tr
                                                key={item.kegiatan_id}
                                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                                            >
                                                <td className="py-2">
                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                        {item.nama_kegiatan}
                                                    </div>
                                                </td>
                                                <td className="py-2">
                                                    <span className="rounded-md bg-neutral-100 px-1.5 py-0.5 text-xs text-neutral-600 capitalize dark:bg-neutral-700 dark:text-neutral-300">
                                                        {item.jenis_kegiatan}
                                                    </span>
                                                </td>
                                                <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                                    {formatRupiah(
                                                        item.total_pagu,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                                    {formatRupiah(
                                                        item.total_terpakai,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                                    {formatRupiah(
                                                        item.total_pagu -
                                                            item.total_terpakai,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right">
                                                    <div
                                                        className={`text-xs font-bold ${serapanColor(item.persentase)}`}
                                                    >
                                                        {item.persentase}%
                                                    </div>
                                                    <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                                        <div
                                                            className="h-full rounded-full transition-all"
                                                            style={{
                                                                width: `${Math.min(item.persentase, 100)}%`,
                                                                backgroundColor:
                                                                    serapanBarColor(
                                                                        item.persentase,
                                                                    ),
                                                            }}
                                                        />
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    ) : (
                        <p className="py-10 text-center text-sm text-neutral-400">
                            Belum ada data kegiatan
                        </p>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
