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
import { GlassTooltipContent, monthNames } from '../helpers';
import type { AlokasiPerBulan } from '../types';

interface MonthlyAllocationChartProps {
    data: AlokasiPerBulan[];
}

export function MonthlyAllocationChart({
    data,
}: MonthlyAllocationChartProps) {
    const chartData = data.map((item) => ({
        ...item,
        name: monthNames[item.bulan - 1],
    }));
    const totalAlokasi = data.reduce(
        (sum, item) => sum + item.jumlah_petugas,
        0,
    );

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                    Alokasi Petugas per Bulan
                </h3>
                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    Total alokasi setahun: {totalAlokasi}
                </span>
            </div>
            <div className="mb-4">
                <ResponsiveContainer width="100%" height={250}>
                    <LineChart
                        data={chartData}
                        margin={{ top: 0, right: 0, left: -20, bottom: 0 }}
                    >
                        <CartesianGrid
                            strokeDasharray="3 3"
                            stroke="rgba(128,128,128,0.15)"
                        />
                        <XAxis dataKey="name" fontSize={11} tickLine={false} />
                        <YAxis
                            fontSize={11}
                            tickLine={false}
                            allowDecimals={false}
                        />
                        <ChartTooltip content={<GlassTooltipContent />} />
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
                            {monthNames.map((month) => (
                                <th
                                    key={month}
                                    className="py-2 text-center font-medium text-neutral-600 dark:text-neutral-400"
                                >
                                    {month}
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
                                <td className={`py-1.5 font-medium ${row.color}`}>
                                    {row.label}
                                </td>
                                {data.map((item) => (
                                    <td
                                        key={item.bulan}
                                        className={`py-1.5 text-center font-medium ${row.color}`}
                                    >
                                        {item[row.key]}
                                    </td>
                                ))}
                                <td className={`py-1.5 text-center font-bold ${row.color}`}>
                                    {data.reduce(
                                        (sum, item) => sum + item[row.key],
                                        0,
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
