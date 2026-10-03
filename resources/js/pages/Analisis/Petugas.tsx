import { SearchableSelect } from '@/components/searchable-select';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import Check from 'lucide-react/icons/check';
import Copy from 'lucide-react/icons/copy';
import Download from 'lucide-react/icons/download';
import X from 'lucide-react/icons/x';
import { useMemo, useState } from 'react';
import {
    CartesianGrid,
    Tooltip as ChartTooltip,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    XAxis,
    YAxis,
} from 'recharts';
import {
    COLORS,
    formatHonorAxis,
    formatRupiah,
    GlassTooltipContent,
    kegiatanChipStyle,
    monthNames,
    toNumericAmount,
} from './Petugas/helpers';
import { PetugasAllocationDetailTable } from './Petugas/components/PetugasAllocationDetailTable';
import { PetugasDemographicCharts } from './Petugas/components/PetugasDemographicCharts';
import { PetugasKpiGrid } from './Petugas/components/PetugasKpiGrid';
import { PetugasHonorTable } from './Petugas/components/PetugasHonorTable';
import { PetugasKegiatanMappingTable } from './Petugas/components/PetugasKegiatanMappingTable';
import type {
    AnalisisPetugasProps,
    PetugasBelumDialokasikanItem,
} from './Petugas/types';

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
    const [belumDialokasikanPage, setBelumDialokasikanPage] = useState(1);
    const belumDialokasikanPageSize = 10;
    const [distribusiWilayahPage, setDistribusiWilayahPage] = useState(1);
    const distribusiWilayahPageSize = 10;
    const [copiedBelumDialokasikanId, setCopiedBelumDialokasikanId] = useState<
        number | 'all' | null
    >(null);

    const copyBelumDialokasikanRow = (
        item: PetugasBelumDialokasikanItem,
        no: number,
    ) => {
        const text = item.telepon
            ? `${no}. ${item.nama} (${item.telepon})`
            : `${no}. ${item.nama}`;
        navigator.clipboard.writeText(text).then(() => {
            setCopiedBelumDialokasikanId(item.id);
            setTimeout(() => setCopiedBelumDialokasikanId(null), 1500);
        });
    };

    const copyAllBelumDialokasikan = () => {
        const lines = petugasBelumDialokasikan.map((item, idx) =>
            item.telepon
                ? `${idx + 1}. ${item.nama} (${item.telepon})`
                : `${idx + 1}. ${item.nama}`,
        );
        navigator.clipboard.writeText(lines.join('\n')).then(() => {
            setCopiedBelumDialokasikanId('all');
            setTimeout(() => setCopiedBelumDialokasikanId(null), 1500);
        });
    };
    const [searchPetugasRutin, setSearchPetugasRutin] = useState('');
    const [petugasRutinPage, setPetugasRutinPage] = useState(1);
    const petugasRutinPageSize = 10;

    const filteredPetugasRutin = useMemo(() => {
        if (!searchPetugasRutin.trim()) {
            return petugasRutin;
        }
        const q = searchPetugasRutin.toLowerCase();
        return petugasRutin.filter((p) =>
            p.petugas_nama.toLowerCase().includes(q),
        );
    }, [petugasRutin, searchPetugasRutin]);

    const petugasRutinTotalPages = Math.max(
        1,
        Math.ceil(filteredPetugasRutin.length / petugasRutinPageSize),
    );
    const petugasRutinCurrentPage = Math.min(
        petugasRutinPage,
        petugasRutinTotalPages,
    );
    const petugasRutinPageRows = filteredPetugasRutin.slice(
        (petugasRutinCurrentPage - 1) * petugasRutinPageSize,
        petugasRutinCurrentPage * petugasRutinPageSize,
    );

    const belumDialokasikanTotalPages = Math.max(
        1,
        Math.ceil(petugasBelumDialokasikan.length / belumDialokasikanPageSize),
    );
    const belumDialokasikanCurrentPage = Math.min(
        belumDialokasikanPage,
        belumDialokasikanTotalPages,
    );
    const belumDialokasikanPageRows = petugasBelumDialokasikan.slice(
        (belumDialokasikanCurrentPage - 1) * belumDialokasikanPageSize,
        belumDialokasikanCurrentPage * belumDialokasikanPageSize,
    );

    const multiPetugasChartData = useMemo(() => {
        if (selectedPetugasIds.length === 0) {
            return null;
        }
        const selected = petugasAlokasiDetail.filter((p) =>
            selectedPetugasIds.includes(String(p.petugas_id)),
        );
        if (selected.length === 0) {
            return null;
        }
        return monthNames.map((name, i) => {
            const row: Record<string, number | string> = { name };
            selected.forEach((p) => {
                row[`kegiatan_${p.petugas_id}`] =
                    toNumericAmount(p.bulan[i + 1]) || 0;
                row[`honor_${p.petugas_id}`] =
                    toNumericAmount(p.honor[i + 1]) || 0;
            });
            return row;
        });
    }, [petugasAlokasiDetail, selectedPetugasIds]);

    const honorAxisConfig = useMemo(() => {
        if (!multiPetugasChartData || selectedPetugasIds.length === 0) {
            return {
                max: 1_000_000,
                ticks: [0, 250_000, 500_000, 750_000, 1_000_000],
            };
        }

        const maxValue = multiPetugasChartData.reduce((max, row) => {
            const rowMax = selectedPetugasIds.reduce((innerMax, id) => {
                const value = toNumericAmount(row[`honor_${id}`]);

                return Math.max(innerMax, value);
            }, 0);

            return Math.max(max, rowMax);
        }, 0);

        const step =
            maxValue <= 2_000_000
                ? 250_000
                : maxValue <= 5_000_000
                  ? 500_000
                  : 1_000_000;

        const axisMax = Math.max(step, Math.ceil(maxValue / step) * step);
        const ticks: number[] = [];

        for (let value = 0; value <= axisMax; value += step) {
            ticks.push(value);
        }

        return {
            max: axisMax,
            ticks,
        };
    }, [multiPetugasChartData, selectedPetugasIds]);

    const addPetugas = (id: string) => {
        if (
            id &&
            !selectedPetugasIds.includes(id) &&
            selectedPetugasIds.length < 5
        ) {
            setSelectedPetugasIds((prev) => [...prev, id]);
        }
    };

    const removePetugas = (id: string) => {
        setSelectedPetugasIds((prev) => prev.filter((p) => p !== id));
    };

    const alokasiChartData = alokasiPerBulan.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
    }));

    const distribusiWilayahTotalPages = Math.max(
        1,
        Math.ceil(
            distribusiTugasDesaKelurahan.length / distribusiWilayahPageSize,
        ),
    );
    const distribusiWilayahCurrentPage = Math.min(
        distribusiWilayahPage,
        distribusiWilayahTotalPages,
    );
    const distribusiWilayahPageRows = distribusiTugasDesaKelurahan.slice(
        (distribusiWilayahCurrentPage - 1) * distribusiWilayahPageSize,
        distribusiWilayahCurrentPage * distribusiWilayahPageSize,
    );

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

                {/* Distribusi Petugas per Desa/Kelurahan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                            Distribusi Petugas per Desa/Kelurahan
                        </h3>
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                            {distribusiTugasDesaKelurahan.length} wilayah
                        </span>
                    </div>
                    {distribusiTugasDesaKelurahan.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                No
                                            </th>
                                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Kecamatan
                                            </th>
                                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                                Desa/Kelurahan
                                            </th>
                                            <th className="py-2 pr-3 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                                Petugas
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {distribusiWilayahPageRows.map(
                                            (item, index) => {
                                                const rowNo =
                                                    (distribusiWilayahCurrentPage -
                                                        1) *
                                                        distribusiWilayahPageSize +
                                                    index +
                                                    1;

                                                return (
                                                    <tr
                                                        key={`${item.desa_kelurahan}-${index}`}
                                                        className="border-b border-neutral-100 dark:border-neutral-700/50"
                                                    >
                                                        <td className="py-1.5 pr-3 text-neutral-500 dark:text-neutral-400">
                                                            {rowNo}
                                                        </td>
                                                        <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                                            {item.kecamatan}
                                                        </td>
                                                        <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                                            {
                                                                item.desa_kelurahan
                                                            }
                                                        </td>
                                                        <td className="py-1.5 pr-3 text-center font-semibold text-sky-600 dark:text-sky-400">
                                                            {
                                                                item.jumlah_petugas
                                                            }
                                                        </td>
                                                    </tr>
                                                );
                                            },
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            {distribusiWilayahTotalPages > 1 && (
                                <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                                    <span className="text-neutral-500 dark:text-neutral-400">
                                        Halaman {distribusiWilayahCurrentPage}{' '}
                                        dari {distribusiWilayahTotalPages}{' '}
                                        &middot;{' '}
                                        {distribusiTugasDesaKelurahan.length}{' '}
                                        wilayah
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={
                                                distribusiWilayahCurrentPage <=
                                                1
                                            }
                                            onClick={() =>
                                                setDistribusiWilayahPage((p) =>
                                                    Math.max(p - 1, 1),
                                                )
                                            }
                                        >
                                            Sebelumnya
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={
                                                distribusiWilayahCurrentPage >=
                                                distribusiWilayahTotalPages
                                            }
                                            onClick={() =>
                                                setDistribusiWilayahPage((p) =>
                                                    Math.min(
                                                        p + 1,
                                                        distribusiWilayahTotalPages,
                                                    ),
                                                )
                                            }
                                        >
                                            Berikutnya
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        <p className="py-10 text-center text-sm text-neutral-400">
                            Data distribusi petugas desa/kelurahan belum
                            tersedia
                        </p>
                    )}
                </div>

                {/* Alokasi Petugas per Bulan */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                            Alokasi Petugas per Bulan
                        </h3>
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                            Total alokasi setahun: {totalAlokasiTahun}
                        </span>
                    </div>
                    <div className="mb-4">
                        <ResponsiveContainer width="100%" height={250}>
                            <LineChart
                                data={alokasiChartData}
                                margin={{
                                    top: 0,
                                    right: 0,
                                    left: -20,
                                    bottom: 0,
                                }}
                            >
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    stroke="rgba(128,128,128,0.15)"
                                />
                                <XAxis
                                    dataKey="name"
                                    fontSize={11}
                                    tickLine={false}
                                />
                                <YAxis
                                    fontSize={11}
                                    tickLine={false}
                                    allowDecimals={false}
                                />
                                <ChartTooltip
                                    content={<GlassTooltipContent />}
                                />
                                <Legend wrapperStyle={{ fontSize: '11px' }} />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_petugas"
                                    stroke="#3b82f6"
                                    name="Jumlah Petugas"
                                    strokeWidth={2}
                                    dot={false}
                                />
                                <Line
                                    type="monotone"
                                    dataKey="jumlah_kegiatan"
                                    stroke="#22c55e"
                                    name="Jumlah Kegiatan"
                                    strokeWidth={2}
                                    dot={false}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="min-w-[120px] py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Metrik
                                    </th>
                                    {monthNames.map((m) => (
                                        <th
                                            key={m}
                                            className="py-2 text-center font-medium text-neutral-600 dark:text-neutral-400"
                                        >
                                            {m}
                                        </th>
                                    ))}
                                    <th className="py-2 text-center font-semibold text-neutral-600 dark:text-neutral-400">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {(
                                    [
                                        {
                                            label: 'Jumlah Petugas',
                                            key: 'jumlah_petugas' as const,
                                            color: 'text-blue-600 dark:text-blue-400',
                                        },
                                        {
                                            label: 'Jumlah Kegiatan',
                                            key: 'jumlah_kegiatan' as const,
                                            color: 'text-green-600 dark:text-green-400',
                                        },
                                    ] as const
                                ).map((row) => (
                                    <tr
                                        key={row.key}
                                        className="border-b border-neutral-100 dark:border-neutral-700/50"
                                    >
                                        <td
                                            className={`py-1.5 font-medium ${row.color}`}
                                        >
                                            {row.label}
                                        </td>
                                        {alokasiPerBulan.map((item) => (
                                            <td
                                                key={item.bulan}
                                                className={`py-1.5 text-center font-medium ${row.color}`}
                                            >
                                                {item[row.key]}
                                            </td>
                                        ))}
                                        <td
                                            className={`py-1.5 text-center font-bold ${row.color}`}
                                        >
                                            {alokasiPerBulan.reduce(
                                                (s, i) => s + i[row.key],
                                                0,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Petugas Belum Pernah Dialokasikan */}
                {petugasBelumDialokasikan.length > 0 && (
                    <div className="rounded-2xl border border-amber-200/60 bg-amber-50/50 p-5 shadow-2xl backdrop-blur-2xl dark:border-amber-700/30 dark:bg-amber-900/10">
                        <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                    Petugas Belum Pernah Dialokasikan
                                </h3>
                                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                    {petugasBelumDialokasikan.length} petugas
                                    aktif belum pernah mendapat alokasi kegiatan
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                className="gap-1.5 text-xs"
                                onClick={copyAllBelumDialokasikan}
                            >
                                {copiedBelumDialokasikanId === 'all' ? (
                                    <Check className="h-3.5 w-3.5 text-green-500" />
                                ) : (
                                    <Copy className="h-3.5 w-3.5" />
                                )}
                                {copiedBelumDialokasikanId === 'all'
                                    ? 'Tersalin!'
                                    : 'Salin Semua'}
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            No
                                        </th>
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Nama
                                        </th>
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Jenis Kelamin
                                        </th>
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            No HP
                                        </th>
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Kecamatan
                                        </th>
                                        <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {belumDialokasikanPageRows.map(
                                        (item, idx) => {
                                            const rowNo =
                                                (belumDialokasikanCurrentPage -
                                                    1) *
                                                    belumDialokasikanPageSize +
                                                idx +
                                                1;
                                            return (
                                                <tr
                                                    key={item.id}
                                                    className="border-b border-neutral-100 dark:border-neutral-700/50"
                                                >
                                                    <td className="py-1.5 pr-3 text-neutral-500 dark:text-neutral-400">
                                                        {rowNo}
                                                    </td>
                                                    <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                                        {item.nama}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                                        {item.jenis_kelamin ??
                                                            '—'}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                                        {item.telepon ?? '—'}
                                                    </td>
                                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                                        {item.kecamatan ?? '—'}
                                                    </td>
                                                    <td className="py-1.5 text-right">
                                                        <button
                                                            type="button"
                                                            title="Salin baris ini"
                                                            onClick={() =>
                                                                copyBelumDialokasikanRow(
                                                                    item,
                                                                    rowNo,
                                                                )
                                                            }
                                                            className="rounded p-1 text-neutral-400 transition hover:text-neutral-700 dark:hover:text-neutral-200"
                                                        >
                                                            {copiedBelumDialokasikanId ===
                                                            item.id ? (
                                                                <Check className="h-3.5 w-3.5 text-green-500" />
                                                            ) : (
                                                                <Copy className="h-3.5 w-3.5" />
                                                            )}
                                                        </button>
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {belumDialokasikanTotalPages > 1 && (
                            <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                                <span className="text-neutral-500 dark:text-neutral-400">
                                    Halaman {belumDialokasikanCurrentPage} dari{' '}
                                    {belumDialokasikanTotalPages} &middot;{' '}
                                    {petugasBelumDialokasikan.length} petugas
                                </span>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={
                                            belumDialokasikanCurrentPage <= 1
                                        }
                                        onClick={() =>
                                            setBelumDialokasikanPage((p) =>
                                                Math.max(p - 1, 1),
                                            )
                                        }
                                    >
                                        Sebelumnya
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={
                                            belumDialokasikanCurrentPage >=
                                            belumDialokasikanTotalPages
                                        }
                                        onClick={() =>
                                            setBelumDialokasikanPage((p) =>
                                                Math.min(
                                                    p + 1,
                                                    belumDialokasikanTotalPages,
                                                ),
                                            )
                                        }
                                    >
                                        Berikutnya
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Petugas dengan Kegiatan Rutin */}
                {petugasRutin.length > 0 && (
                    <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                        <div className="mb-4 flex flex-wrap items-end gap-4">
                            <div className="flex-1">
                                <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                    Petugas dengan Kegiatan Rutin
                                </h3>
                                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                    Petugas yang mengikuti kegiatan yang sama di
                                    minimal 2 bulan berbeda ·{' '}
                                    {filteredPetugasRutin.length} petugas
                                </p>
                            </div>
                            <div className="w-64">
                                <Input
                                    placeholder="Cari nama petugas..."
                                    value={searchPetugasRutin}
                                    onChange={(e) => {
                                        setSearchPetugasRutin(e.target.value);
                                        setPetugasRutinPage(1);
                                    }}
                                    className="h-9"
                                />
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                        <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Nama Petugas
                                        </th>
                                        <th className="py-2 pr-3 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                            Jml Rutin
                                        </th>
                                        <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                            Kegiatan Rutin (jumlah bulan)
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {petugasRutinPageRows.map((item) => (
                                        <tr
                                            key={item.petugas_id}
                                            className="border-b border-neutral-100 dark:border-neutral-700/50"
                                        >
                                            <td className="py-2 pr-3 font-medium text-neutral-900 dark:text-white">
                                                {item.petugas_nama}
                                            </td>
                                            <td className="py-2 pr-3 text-center font-bold text-neutral-900 dark:text-white">
                                                {item.jumlah_kegiatan_rutin}
                                            </td>
                                            <td className="py-2">
                                                <div className="flex flex-wrap gap-1.5">
                                                    {item.kegiatan_rutin.map(
                                                        (k) => (
                                                            <span
                                                                key={
                                                                    k.kegiatan_id
                                                                }
                                                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ${kegiatanChipStyle(k.kegiatan_id)}`}
                                                            >
                                                                {
                                                                    k.nama_kegiatan
                                                                }
                                                                <span className="rounded-full bg-black/10 px-1 py-px font-semibold dark:bg-white/15">
                                                                    {
                                                                        k.jumlah_bulan
                                                                    }
                                                                    ×
                                                                </span>
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {petugasRutinTotalPages > 1 && (
                            <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                                <span className="text-neutral-500 dark:text-neutral-400">
                                    Halaman {petugasRutinCurrentPage} dari{' '}
                                    {petugasRutinTotalPages} &middot;{' '}
                                    {filteredPetugasRutin.length} petugas
                                </span>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={petugasRutinCurrentPage <= 1}
                                        onClick={() =>
                                            setPetugasRutinPage((p) =>
                                                Math.max(p - 1, 1),
                                            )
                                        }
                                    >
                                        Sebelumnya
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={
                                            petugasRutinCurrentPage >=
                                            petugasRutinTotalPages
                                        }
                                        onClick={() =>
                                            setPetugasRutinPage((p) =>
                                                Math.min(
                                                    p + 1,
                                                    petugasRutinTotalPages,
                                                ),
                                            )
                                        }
                                    >
                                        Berikutnya
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Per-Petugas Chart - Dynamic Multi-select */}
                <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                    <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                        Grafik Alokasi per Petugas
                    </h3>
                    <div className="mb-4 flex flex-wrap items-end gap-3">
                        <div className="w-72">
                            <SearchableSelect
                                options={petugasList
                                    .filter(
                                        (p) =>
                                            !selectedPetugasIds.includes(
                                                String(p.id),
                                            ),
                                    )
                                    .map((p) => ({
                                        value: String(p.id),
                                        label: p.nama,
                                    }))}
                                value=""
                                onValueChange={addPetugas}
                                placeholder={
                                    selectedPetugasIds.length >= 5
                                        ? 'Maks 5 petugas'
                                        : 'Tambah petugas...'
                                }
                                searchPlaceholder="Cari petugas..."
                            />
                        </div>
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                            {selectedPetugasIds.length}/5 petugas dipilih
                        </span>
                    </div>
                    {selectedPetugasIds.length > 0 && (
                        <div className="mb-4 flex flex-wrap gap-2">
                            {selectedPetugasIds.map((id, idx) => {
                                const p = petugasList.find(
                                    (pt) => String(pt.id) === id,
                                );
                                return (
                                    <span
                                        key={id}
                                        className="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium text-white"
                                        style={{
                                            backgroundColor:
                                                COLORS[idx % COLORS.length],
                                        }}
                                    >
                                        {p?.nama ?? id}
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="h-4 w-4 p-0 text-white hover:bg-white/20"
                                            onClick={() => removePetugas(id)}
                                        >
                                            <X className="h-3 w-3" />
                                        </Button>
                                    </span>
                                );
                            })}
                        </div>
                    )}
                    {multiPetugasChartData ? (
                        <div className="space-y-4">
                            <div>
                                <p className="mb-2 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    Jumlah Kegiatan per Bulan
                                </p>
                                <ResponsiveContainer width="100%" height={250}>
                                    <LineChart
                                        data={multiPetugasChartData}
                                        margin={{
                                            top: 0,
                                            right: 0,
                                            left: -20,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            stroke="rgba(128,128,128,0.15)"
                                        />
                                        <XAxis
                                            dataKey="name"
                                            fontSize={11}
                                            tickLine={false}
                                        />
                                        <YAxis
                                            fontSize={11}
                                            tickLine={false}
                                            allowDecimals={false}
                                        />
                                        <ChartTooltip
                                            content={<GlassTooltipContent />}
                                        />
                                        <Legend
                                            wrapperStyle={{ fontSize: '11px' }}
                                        />
                                        {selectedPetugasIds.map((id, idx) => {
                                            const p = petugasList.find(
                                                (pt) => String(pt.id) === id,
                                            );
                                            return (
                                                <Line
                                                    key={id}
                                                    type="monotone"
                                                    dataKey={`kegiatan_${id}`}
                                                    stroke={
                                                        COLORS[
                                                            idx % COLORS.length
                                                        ]
                                                    }
                                                    name={
                                                        p?.nama ??
                                                        `Petugas ${id}`
                                                    }
                                                    strokeWidth={2}
                                                    dot={false}
                                                />
                                            );
                                        })}
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                            <div>
                                <p className="mb-2 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                    Total Honor per Bulan
                                </p>
                                <ResponsiveContainer width="100%" height={250}>
                                    <LineChart
                                        data={multiPetugasChartData}
                                        margin={{
                                            top: 0,
                                            right: 0,
                                            left: 10,
                                            bottom: 0,
                                        }}
                                    >
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            stroke="rgba(128,128,128,0.15)"
                                        />
                                        <XAxis
                                            dataKey="name"
                                            fontSize={11}
                                            tickLine={false}
                                        />
                                        <YAxis
                                            fontSize={11}
                                            tickLine={false}
                                            domain={[0, honorAxisConfig.max]}
                                            ticks={honorAxisConfig.ticks}
                                            allowDecimals={false}
                                            tickFormatter={formatHonorAxis}
                                        />
                                        <ChartTooltip
                                            content={({
                                                active,
                                                payload,
                                                label,
                                            }) => {
                                                if (
                                                    !active ||
                                                    !payload ||
                                                    payload.length === 0
                                                ) {
                                                    return null;
                                                }
                                                return (
                                                    <div
                                                        className={
                                                            glassTooltipClass
                                                        }
                                                    >
                                                        <p className="mb-1 text-xs font-semibold text-neutral-900 dark:text-white">
                                                            {label}
                                                        </p>
                                                        {payload.map(
                                                            (entry, i) => (
                                                                <p
                                                                    key={i}
                                                                    className="text-xs text-neutral-600 dark:text-neutral-400"
                                                                >
                                                                    <span
                                                                        style={{
                                                                            color: entry.color,
                                                                        }}
                                                                    >
                                                                        ●
                                                                    </span>{' '}
                                                                    {entry.name}
                                                                    :{' '}
                                                                    {formatRupiah(
                                                                        entry.value as number,
                                                                    )}
                                                                </p>
                                                            ),
                                                        )}
                                                    </div>
                                                );
                                            }}
                                        />
                                        <Legend
                                            wrapperStyle={{ fontSize: '11px' }}
                                        />
                                        {selectedPetugasIds.map((id, idx) => {
                                            const p = petugasList.find(
                                                (pt) => String(pt.id) === id,
                                            );
                                            return (
                                                <Line
                                                    key={id}
                                                    type="monotone"
                                                    dataKey={`honor_${id}`}
                                                    stroke={
                                                        COLORS[
                                                            idx % COLORS.length
                                                        ]
                                                    }
                                                    name={
                                                        p?.nama ??
                                                        `Petugas ${id}`
                                                    }
                                                    strokeWidth={2}
                                                    dot={false}
                                                />
                                            );
                                        })}
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    ) : (
                        <p className="py-10 text-center text-sm text-neutral-400">
                            Pilih petugas untuk melihat grafik alokasi bulanan
                        </p>
                    )}
                </div>

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
