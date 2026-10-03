import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
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
    monthNames,
    toNumericAmount,
} from '../helpers';
import type {
    PetugasAlokasiDetail,
    PetugasListItem,
} from '../types';

interface PetugasComparisonChartProps {
    petugasList: PetugasListItem[];
    allocationDetails: PetugasAlokasiDetail[];
}

const TOOLTIP_CLASS =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

export function PetugasComparisonChart({
    petugasList,
    allocationDetails,
}: PetugasComparisonChartProps) {
    const [selectedIds, setSelectedIds] = useState<string[]>([]);

    const chartData = useMemo(() => {
        if (selectedIds.length === 0) return null;

        const selected = allocationDetails.filter((item) =>
            selectedIds.includes(String(item.petugas_id)),
        );
        if (selected.length === 0) return null;

        return monthNames.map((name, index) => {
            const row: Record<string, number | string> = { name };
            selected.forEach((item) => {
                row[`kegiatan_${item.petugas_id}`] =
                    toNumericAmount(item.bulan[index + 1]) || 0;
                row[`honor_${item.petugas_id}`] =
                    toNumericAmount(item.honor[index + 1]) || 0;
            });
            return row;
        });
    }, [allocationDetails, selectedIds]);

    const honorAxis = useMemo(() => {
        if (!chartData || selectedIds.length === 0) {
            return {
                max: 1_000_000,
                ticks: [0, 250_000, 500_000, 750_000, 1_000_000],
            };
        }

        const maxValue = chartData.reduce((max, row) => {
            const rowMax = selectedIds.reduce(
                (innerMax, id) =>
                    Math.max(
                        innerMax,
                        toNumericAmount(row[`honor_${id}`]),
                    ),
                0,
            );
            return Math.max(max, rowMax);
        }, 0);

        const step =
            maxValue <= 2_000_000
                ? 250_000
                : maxValue <= 5_000_000
                  ? 500_000
                  : 1_000_000;
        const max = Math.max(step, Math.ceil(maxValue / step) * step);
        const ticks: number[] = [];

        for (let value = 0; value <= max; value += step) {
            ticks.push(value);
        }

        return { max, ticks };
    }, [chartData, selectedIds]);

    const addPetugas = (id: string) => {
        if (id && !selectedIds.includes(id) && selectedIds.length < 5) {
            setSelectedIds((current) => [...current, id]);
        }
    };

    const removePetugas = (id: string) => {
        setSelectedIds((current) => current.filter((item) => item !== id));
    };

    const selectedName = (id: string) =>
        petugasList.find((item) => String(item.id) === id)?.nama ??
        `Petugas ${id}`;

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                Grafik Alokasi per Petugas
            </h3>
            <div className="mb-4 flex flex-wrap items-end gap-3">
                <div className="w-72">
                    <SearchableSelect
                        options={petugasList
                            .filter(
                                (item) =>
                                    !selectedIds.includes(String(item.id)),
                            )
                            .map((item) => ({
                                value: String(item.id),
                                label: item.nama,
                            }))}
                        value=""
                        onValueChange={addPetugas}
                        placeholder={
                            selectedIds.length >= 5
                                ? 'Maks 5 petugas'
                                : 'Tambah petugas...'
                        }
                        searchPlaceholder="Cari petugas..."
                    />
                </div>
                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {selectedIds.length}/5 petugas dipilih
                </span>
            </div>
            {selectedIds.length > 0 && (
                <div className="mb-4 flex flex-wrap gap-2">
                    {selectedIds.map((id, index) => (
                        <span
                            key={id}
                            className="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium text-white"
                            style={{
                                backgroundColor: COLORS[index % COLORS.length],
                            }}
                        >
                            {selectedName(id)}
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-4 w-4 p-0 text-white hover:bg-white/20"
                                onClick={() => removePetugas(id)}
                            >
                                <X className="h-3 w-3" />
                            </Button>
                        </span>
                    ))}
                </div>
            )}
            {chartData ? (
                <div className="space-y-4">
                    <div>
                        <p className="mb-2 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            Jumlah Kegiatan per Bulan
                        </p>
                        <ResponsiveContainer width="100%" height={250}>
                            <LineChart
                                data={chartData}
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
                                {selectedIds.map((id, index) => (
                                    <Line
                                        key={id}
                                        type="monotone"
                                        dataKey={`kegiatan_${id}`}
                                        stroke={COLORS[index % COLORS.length]}
                                        name={selectedName(id)}
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                ))}
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                    <div>
                        <p className="mb-2 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            Total Honor per Bulan
                        </p>
                        <ResponsiveContainer width="100%" height={250}>
                            <LineChart
                                data={chartData}
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
                                    domain={[0, honorAxis.max]}
                                    ticks={honorAxis.ticks}
                                    allowDecimals={false}
                                    tickFormatter={formatHonorAxis}
                                />
                                <ChartTooltip
                                    content={({ active, payload, label }) => {
                                        if (!active || !payload?.length) {
                                            return null;
                                        }

                                        return (
                                            <div className={TOOLTIP_CLASS}>
                                                <p className="mb-1 text-xs font-semibold text-neutral-900 dark:text-white">
                                                    {label}
                                                </p>
                                                {payload.map((entry, index) => (
                                                    <p
                                                        key={index}
                                                        className="text-xs text-neutral-600 dark:text-neutral-400"
                                                    >
                                                        <span
                                                            style={{
                                                                color: entry.color,
                                                            }}
                                                        >
                                                            ●
                                                        </span>{' '}
                                                        {entry.name}:{' '}
                                                        {formatRupiah(
                                                            entry.value as number,
                                                        )}
                                                    </p>
                                                ))}
                                            </div>
                                        );
                                    }}
                                />
                                <Legend wrapperStyle={{ fontSize: '11px' }} />
                                {selectedIds.map((id, index) => (
                                    <Line
                                        key={id}
                                        type="monotone"
                                        dataKey={`honor_${id}`}
                                        stroke={COLORS[index % COLORS.length]}
                                        name={selectedName(id)}
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                ))}
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
    );
}
