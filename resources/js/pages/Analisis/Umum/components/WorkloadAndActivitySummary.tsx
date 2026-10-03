import BarChart2 from 'lucide-react/icons/bar-chart2';
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
    formatRupiahCompact,
    PieLegendList,
    renderActivePieShape,
    serapanBarColor,
    serapanColor,
} from '../helpers';
import type {
    DistribusiBebanKerja,
    RingkasanJenisKegiatan,
} from '../types';

interface WorkloadAndActivitySummaryProps {
    workloadDistribution: DistribusiBebanKerja[];
    activitySummary: RingkasanJenisKegiatan[];
}

export function WorkloadAndActivitySummary({
    workloadDistribution,
    activitySummary,
}: WorkloadAndActivitySummaryProps) {
    const [activePieIndex, setActivePieIndex] = useState<number | undefined>();

    const workloadData = useMemo(
        () => workloadDistribution.filter((item) => item.count > 0),
        [workloadDistribution],
    );
    const workloadLegendItems = useMemo(() => {
        const total = workloadData.reduce(
            (sum, item) => sum + item.count,
            0,
        );

        return buildPieLegendItems(
            workloadData,
            (item) => item.label,
            total,
        );
    }, [workloadData]);

    return (
        <div className="grid gap-6 lg:grid-cols-2">
            <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                    Distribusi Beban Kerja Petugas
                </h3>
                <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                    Sebaran petugas non-organik berdasarkan jumlah kegiatan
                    yang ditangani
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
                            data={workloadData}
                            dataKey="count"
                            nameKey="label"
                            cx="50%"
                            cy="50%"
                            innerRadius={54}
                            outerRadius={86}
                            paddingAngle={2}
                            labelLine={false}
                            shape={(props: PieSectorShapeProps) =>
                                renderActivePieShape(props, activePieIndex)
                            }
                            onClick={(_, index, event) => {
                                event.stopPropagation();
                                setActivePieIndex(
                                    index === activePieIndex
                                        ? undefined
                                        : index,
                                );
                            }}
                            cursor="pointer"
                            stroke="none"
                        >
                            {workloadData.map((_, index) => (
                                <Cell
                                    key={`beban-kerja-cell-${index}`}
                                    fill={COLORS[index % COLORS.length]}
                                />
                            ))}
                        </Pie>
                    </PieChart>
                </ResponsiveContainer>
                <PieLegendList items={workloadLegendItems} />
            </div>

            <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
                <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                    Penyerapan Anggaran per Jenis Kegiatan
                </h3>
                <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                    Perbandingan pagu dan penyerapan anggaran berdasarkan
                    jenis kegiatan
                </p>
                {activitySummary.length > 0 ? (
                    <div className="space-y-4">
                        {activitySummary.map((item) => (
                            <div key={item.jenis}>
                                <div className="mb-1 flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <BarChart2 className="h-3.5 w-3.5 text-neutral-400" />
                                        <span className="text-sm font-medium text-neutral-800 dark:text-neutral-200">
                                            {item.label}
                                        </span>
                                        <span className="rounded-full bg-neutral-100 px-1.5 py-0.5 text-xs text-neutral-500 dark:bg-neutral-700 dark:text-neutral-400">
                                            {item.jumlah_kegiatan} keg
                                        </span>
                                    </div>
                                    <span
                                        className={`text-sm font-bold ${serapanColor(item.serapan_persen)}`}
                                    >
                                        {item.serapan_persen}%
                                    </span>
                                </div>
                                <div className="mb-1 h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                    <div
                                        className="h-full rounded-full transition-all"
                                        style={{
                                            width: `${Math.min(item.serapan_persen, 100)}%`,
                                            backgroundColor: serapanBarColor(
                                                item.serapan_persen,
                                            ),
                                        }}
                                    />
                                </div>
                                <div className="flex justify-between text-xs text-neutral-500 dark:text-neutral-400">
                                    <span>
                                        Terpakai:{' '}
                                        {formatRupiahCompact(
                                            item.total_terpakai,
                                        )}
                                    </span>
                                    <span>
                                        Pagu:{' '}
                                        {formatRupiahCompact(item.total_pagu)}
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
    );
}
