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
    formatRupiah,
    formatRupiahCompact,
    glassTooltipClass,
    monthNames,
} from '../helpers';
import type { TrenAlokasi } from '../types';

interface AllocationTrendChartProps {
    data: TrenAlokasi[];
}

export function AllocationTrendChart({ data }: AllocationTrendChartProps) {
    const chartData = data.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
    }));

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                Tren Alokasi Bulanan
            </h3>
            <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                Total honor terbayar, jumlah kegiatan aktif, dan petugas
                teralokasi per bulan
            </p>
            <ResponsiveContainer width="100%" height={300}>
                <LineChart data={chartData}>
                    <CartesianGrid
                        strokeDasharray="3 3"
                        stroke="rgba(156,163,175,0.2)"
                    />
                    <XAxis
                        dataKey="name"
                        fontSize={12}
                        tick={{
                            fill: 'currentColor',
                            className: 'text-neutral-500',
                        }}
                    />
                    <YAxis
                        yAxisId="honor"
                        orientation="left"
                        fontSize={11}
                        tickFormatter={(value) =>
                            formatRupiahCompact(value as number)
                        }
                        tick={{ fill: '#22c55e' }}
                        width={60}
                    />
                    <YAxis
                        yAxisId="count"
                        orientation="right"
                        fontSize={11}
                        allowDecimals={false}
                        tick={{ fill: '#3b82f6' }}
                        width={36}
                    />
                    <ChartTooltip
                        content={({ active, payload, label }) => {
                            if (!active || !payload?.length) return null;

                            return (
                                <div className={glassTooltipClass}>
                                    <p className="mb-2 text-xs font-semibold text-neutral-900 dark:text-white">
                                        {label}
                                    </p>
                                    {payload.map((entry, index) => (
                                        <p
                                            key={index}
                                            className="text-xs text-neutral-600 dark:text-neutral-400"
                                        >
                                            <span
                                                style={{ color: entry.color }}
                                            >
                                                ●
                                            </span>{' '}
                                            {entry.name}:{' '}
                                            {entry.dataKey === 'total_honor'
                                                ? formatRupiah(
                                                      entry.value as number,
                                                  )
                                                : entry.value}
                                        </p>
                                    ))}
                                </div>
                            );
                        }}
                    />
                    <Legend
                        formatter={(value) => (
                            <span className="text-xs text-neutral-600 dark:text-neutral-300">
                                {value}
                            </span>
                        )}
                    />
                    <Line
                        type="monotone"
                        dataKey="total_honor"
                        stroke="#22c55e"
                        name="Total Honor"
                        strokeWidth={2}
                        dot={{ r: 3 }}
                        activeDot={{ r: 5 }}
                        yAxisId="honor"
                    />
                    <Line
                        type="monotone"
                        dataKey="total_kegiatan"
                        stroke="#3b82f6"
                        name="Jumlah Kegiatan"
                        strokeWidth={2}
                        dot={{ r: 3 }}
                        activeDot={{ r: 5 }}
                        yAxisId="count"
                    />
                    <Line
                        type="monotone"
                        dataKey="jumlah_petugas"
                        stroke="#f59e0b"
                        name="Jumlah Petugas"
                        strokeWidth={2}
                        strokeDasharray="5 3"
                        dot={{ r: 3 }}
                        activeDot={{ r: 5 }}
                        yAxisId="count"
                    />
                </LineChart>
            </ResponsiveContainer>
        </div>
    );
}
