import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip as ChartTooltip,
    XAxis,
    YAxis,
} from 'recharts';
import type { ReactNode } from 'react';
import type { TrenDokumenItem } from '../types';

function TrendTooltip({
    active,
    payload,
    label,
}: {
    active?: boolean;
    payload?: Array<{
        color?: string;
        name?: string;
        value?: string | number;
    }>;
    label?: string | number;
}) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80">
            <p className="mb-1 text-xs font-semibold text-neutral-900 dark:text-white">
                {label}
            </p>
            {payload.map((entry, index) => (
                <p
                    key={`${entry.name ?? 'item'}-${index}`}
                    className="text-xs text-neutral-600 dark:text-neutral-300"
                >
                    <span style={{ color: entry.color }}>●</span>{' '}
                    {entry.name}: {entry.value}
                </p>
            ))}
        </div>
    );
}

function ChartCard({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                {title}
            </h3>
            <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                {description}
            </p>
            {children}
        </div>
    );
}

export default function DocumentTrendCharts({
    data,
}: {
    data: TrenDokumenItem[];
}) {
    return (
        <div className="grid gap-4 lg:grid-cols-2">
            <ChartCard
                title="Tren SK per Bulan"
                description="Jumlah SK diterbitkan vs draft per bulan"
            >
                <ResponsiveContainer width="100%" height={220}>
                    <BarChart
                        data={data}
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
                        <ChartTooltip content={<TrendTooltip />} />
                        <Legend wrapperStyle={{ fontSize: '11px' }} />
                        <Bar
                            dataKey="sk_diterbitkan"
                            fill="#22c55e"
                            name="Diterbitkan"
                            stackId="sk"
                            radius={[0, 0, 0, 0]}
                        />
                        <Bar
                            dataKey="sk_draft"
                            fill="#94a3b8"
                            name="Draft"
                            stackId="sk"
                            radius={[4, 4, 0, 0]}
                        />
                    </BarChart>
                </ResponsiveContainer>
            </ChartCard>

            <ChartCard
                title="Tren Perjanjian Kerja per Bulan"
                description="PK reguler dan SE2026 utama mengikuti periode pada menu Perjanjian Kerja. PK petugas pengganti mengikuti bulan dari tanggal PK yang diinput."
            >
                <ResponsiveContainer width="100%" height={220}>
                    <BarChart
                        data={data}
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
                        <ChartTooltip content={<TrendTooltip />} />
                        <Legend wrapperStyle={{ fontSize: '11px' }} />
                        <Bar
                            dataKey="spk_reguler"
                            fill="#3b82f6"
                            name="PK Reguler"
                            stackId="spk"
                            radius={[0, 0, 0, 0]}
                        />
                        <Bar
                            dataKey="spk_sensus_utama"
                            fill="#22c55e"
                            name="SE2026 Utama"
                            stackId="spk"
                            radius={[0, 0, 0, 0]}
                        />
                        <Bar
                            dataKey="spk_draft"
                            fill="#94a3b8"
                            name="Draft / belum final"
                            stackId="spk"
                            radius={[4, 4, 0, 0]}
                        />
                    </BarChart>
                </ResponsiveContainer>
            </ChartCard>
        </div>
    );
}
