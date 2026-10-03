import { useMemo, useState } from 'react';
import {
    Cell,
    Pie,
    PieChart,
    type PieSectorShapeProps,
    ResponsiveContainer,
} from 'recharts';
import {
    buildPieLegendItems,
    COLORS,
    PieLegendList,
    renderActivePieShape,
} from '../helpers';
import type {
    DesaKelurahanItem,
    DistribusiItem,
    KecamatanItem,
    PendidikanItem,
} from '../types';

interface PetugasDemographicChartsProps {
    jenisKelamin: DistribusiItem[];
    usia: DistribusiItem[];
    kecamatan: KecamatanItem[];
    desaKelurahan: DesaKelurahanItem[];
    pendidikan: PendidikanItem[];
}

interface PieCardProps<T extends { count: number }> {
    title: string;
    data: T[];
    labelKey: keyof T;
    emptyText: string;
    suffix?: string;
}

function PieCard<T extends { count: number }>({
    title,
    data,
    labelKey,
    emptyText,
    suffix,
}: PieCardProps<T>) {
    const [activeIndex, setActiveIndex] = useState<number | undefined>();
    const total = data.reduce(
        (sum, item) => sum + Number(item.count ?? 0),
        0,
    );
    const legendItems = buildPieLegendItems(
        data,
        (item) => String(item[labelKey] ?? ''),
        total,
    );

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <div className="mb-4 flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                    {title}
                </h3>
                {suffix && (
                    <span className="text-xs text-neutral-500 dark:text-neutral-400">
                        {suffix}
                    </span>
                )}
            </div>
            {data.length > 0 ? (
                <div className="space-y-4">
                    <ResponsiveContainer width="100%" height={260}>
                        <PieChart
                            style={{ overflow: 'visible' }}
                            margin={{ top: 10, right: 70, bottom: 10, left: 70 }}
                            onClick={() => setActiveIndex(undefined)}
                        >
                            <Pie
                                data={data}
                                dataKey="count"
                                nameKey={String(labelKey)}
                                cx="50%"
                                cy="50%"
                                innerRadius={35}
                                outerRadius={78}
                                labelLine={false}
                                shape={(props: PieSectorShapeProps) =>
                                    renderActivePieShape(props, activeIndex)
                                }
                                onClick={(_, index, event) => {
                                    event.stopPropagation();
                                    setActiveIndex(
                                        index === activeIndex
                                            ? undefined
                                            : index,
                                    );
                                }}
                                cursor="pointer"
                                stroke="none"
                            >
                                {data.map((_, index) => (
                                    <Cell
                                        key={`${String(labelKey)}-${index}`}
                                        fill={COLORS[index % COLORS.length]}
                                    />
                                ))}
                            </Pie>
                        </PieChart>
                    </ResponsiveContainer>
                    <PieLegendList items={legendItems} />
                </div>
            ) : (
                <p className="py-10 text-center text-sm text-neutral-400">
                    {emptyText}
                </p>
            )}
        </div>
    );
}

export function PetugasDemographicCharts({
    jenisKelamin,
    usia,
    kecamatan,
    desaKelurahan,
    pendidikan,
}: PetugasDemographicChartsProps) {
    const jenisKelaminData = useMemo(
        () => jenisKelamin.filter((item) => item.count > 0),
        [jenisKelamin],
    );
    const usiaData = useMemo(
        () => usia.filter((item) => item.count > 0),
        [usia],
    );
    const kecamatanData = useMemo(() => {
        if (kecamatan.length <= 8) return kecamatan;
        return [
            ...kecamatan.slice(0, 7),
            {
                kecamatan: 'Lainnya',
                count: kecamatan
                    .slice(7)
                    .reduce((sum, item) => sum + item.count, 0),
            },
        ];
    }, [kecamatan]);
    const desaData = useMemo(() => {
        if (desaKelurahan.length <= 8) return desaKelurahan;
        return [
            ...desaKelurahan.slice(0, 7),
            {
                desa_kelurahan: 'Lainnya',
                count: desaKelurahan
                    .slice(7)
                    .reduce((sum, item) => sum + item.count, 0),
            },
        ];
    }, [desaKelurahan]);
    const pendidikanData = useMemo(() => {
        const normalized = pendidikan.map((item) => ({
            pendidikan: item.pendidikan || 'Belum Diisi',
            count: item.count,
        }));

        if (normalized.length <= 8) return normalized;

        return [
            ...normalized.slice(0, 7),
            {
                pendidikan: 'Lainnya',
                count: normalized
                    .slice(7)
                    .reduce((sum, item) => sum + item.count, 0),
            },
        ];
    }, [pendidikan]);

    return (
        <>
            <div className="grid gap-6 lg:grid-cols-2">
                <PieCard
                    title="Distribusi Jenis Kelamin"
                    data={jenisKelaminData}
                    labelKey="label"
                    emptyText="Data jenis kelamin belum tersedia"
                />
                <PieCard
                    title="Distribusi Usia"
                    data={usiaData}
                    labelKey="label"
                    emptyText="Data tanggal lahir belum tersedia"
                />
            </div>
            <div className="grid gap-6 lg:grid-cols-3">
                <PieCard
                    title="Distribusi Kecamatan"
                    data={kecamatanData}
                    labelKey="kecamatan"
                    emptyText="Data kecamatan belum tersedia"
                />
                <PieCard
                    title="Distribusi Desa/Kelurahan"
                    data={desaData}
                    labelKey="desa_kelurahan"
                    emptyText="Data desa/kelurahan belum tersedia"
                    suffix={`${desaKelurahan.length} wilayah`}
                />
                <PieCard
                    title="Distribusi Pendidikan"
                    data={pendidikanData}
                    labelKey="pendidikan"
                    emptyText="Data pendidikan belum tersedia"
                />
            </div>
        </>
    );
}
