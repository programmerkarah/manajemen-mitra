import { Sector, type PieSectorShapeProps } from 'recharts';

export const monthNames = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agu',
    'Sep',
    'Okt',
    'Nov',
    'Des',
] as const;

export const COLORS = [
    '#3b82f6',
    '#ef4444',
    '#22c55e',
    '#f59e0b',
    '#8b5cf6',
    '#ec4899',
    '#14b8a6',
    '#f97316',
    '#6366f1',
    '#84cc16',
] as const;

const KEGIATAN_CHIP_STYLES = [
    'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200',
    'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
    'bg-lime-100 text-lime-800 dark:bg-lime-900/40 dark:text-lime-200',
] as const;

export function kegiatanChipStyle(kegiatanId: number): string {
    const index = Math.abs(kegiatanId) % KEGIATAN_CHIP_STYLES.length;

    return KEGIATAN_CHIP_STYLES[index];
}

const glassTooltipClass =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

export function GlassTooltipContent({
    active,
    payload,
    label,
}: {
    active?: boolean;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    payload?: Array<Record<string, any>>;
    label?: string;
}) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }

    return (
        <div className={glassTooltipClass}>
            {label && (
                <p className="mb-1 text-xs font-semibold text-neutral-900 dark:text-white">
                    {label}
                </p>
            )}
            {payload.map((entry, index) => {
                const pct =
                    typeof entry.percent === 'number'
                        ? (entry.percent * 100).toFixed(1)
                        : null;

                return (
                    <p
                        key={index}
                        className="text-xs text-neutral-600 dark:text-neutral-400"
                    >
                        <span style={{ color: entry.color }}>●</span>{' '}
                        {entry.name}: {entry.value}
                        {pct !== null && ` (${pct}%)`}
                    </p>
                );
            })}
        </div>
    );
}

export function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

export function toNumericAmount(value: unknown): number {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : 0;
    }

    if (typeof value === 'string') {
        const normalized = value.replace(/\./g, '').replace(',', '.').trim();
        const parsed = Number(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    }

    return 0;
}

export function formatHonorAxis(value: number): string {
    if (value >= 1_000_000) {
        const inMillions = value / 1_000_000;

        return Number.isInteger(inMillions)
            ? `${inMillions.toFixed(0)}jt`
            : `${inMillions.toFixed(1)}jt`;
    }

    if (value >= 1_000) {
        return `${Math.round(value / 1_000)}rb`;
    }

    return `${Math.round(value)}`;
}

export interface PieLegendItem {
    label: string;
    count: number;
    color: string;
    percentage: number;
}

export function buildPieLegendItems<T extends { count: number }>(
    data: T[],
    labelResolver: (item: T) => string,
    total: number,
): PieLegendItem[] {
    return data.map((item, index) => ({
        label: labelResolver(item),
        count: item.count,
        color: COLORS[index % COLORS.length],
        percentage: total > 0 ? (item.count / total) * 100 : 0,
    }));
}

export function PieLegendList({ items }: { items: PieLegendItem[] }) {
    return (
        <div className="max-h-40 space-y-1.5 overflow-y-auto pr-1">
            {items.map((item) => (
                <div
                    key={`${item.label}-${item.color}`}
                    className="flex items-center justify-between gap-3 rounded-md border border-neutral-200/70 px-2 py-1.5 text-xs dark:border-neutral-700/70"
                >
                    <div className="flex min-w-0 items-center gap-2">
                        <span
                            className="h-2.5 w-2.5 shrink-0 rounded-full"
                            style={{ backgroundColor: item.color }}
                        />
                        <span
                            className="truncate text-neutral-700 dark:text-neutral-300"
                            title={item.label}
                        >
                            {item.label}
                        </span>
                    </div>
                    <span className="shrink-0 font-semibold text-neutral-900 dark:text-white">
                        {item.count} ({item.percentage.toFixed(1)}%)
                    </span>
                </div>
            ))}
        </div>
    );
}

const RADIAN = Math.PI / 180;

export function renderActivePieShape(
    props: PieSectorShapeProps,
    clickedIndex?: number,
) {
    const { cx, cy, innerRadius, outerRadius, startAngle, endAngle, fill } =
        props;

    if (props.index !== clickedIndex) {
        return (
            <Sector
                cx={cx}
                cy={cy}
                innerRadius={innerRadius}
                outerRadius={outerRadius}
                startAngle={startAngle}
                endAngle={endAngle}
                fill={fill}
                stroke="none"
            />
        );
    }

    const { name, value, percent } = props;
    const midAngle = (startAngle + endAngle) / 2;
    const sin = Math.sin(-RADIAN * midAngle);
    const cos = Math.cos(-RADIAN * midAngle);
    const expandedOuter = outerRadius + 8;
    const sx = cx + (expandedOuter + 2) * cos;
    const sy = cy + (expandedOuter + 2) * sin;
    const mx = cx + (expandedOuter + 20) * cos;
    const my = cy + (expandedOuter + 20) * sin;
    const ex = mx + (cos >= 0 ? 1 : -1) * 18;
    const ey = my;
    const anchor = cos >= 0 ? 'start' : 'end';

    return (
        <g>
            <Sector
                cx={cx}
                cy={cy}
                innerRadius={innerRadius}
                outerRadius={expandedOuter}
                startAngle={startAngle}
                endAngle={endAngle}
                fill={fill}
                stroke="none"
            />
            <path
                d={`M${sx},${sy}L${mx},${my}L${ex},${ey}`}
                stroke={fill}
                fill="none"
                strokeWidth={1.5}
            />
            <circle cx={ex} cy={ey} r={3} fill={fill} />
            <text
                x={ex + (cos >= 0 ? 6 : -6)}
                y={ey - 3}
                textAnchor={anchor}
                fill={fill}
                fontSize={11}
                fontWeight={600}
            >
                {name}
            </text>
            <text
                x={ex + (cos >= 0 ? 6 : -6)}
                y={ey + 11}
                textAnchor={anchor}
                fill="#9ca3af"
                fontSize={10}
            >
                {value} ({(percent * 100).toFixed(1)}%)
            </text>
        </g>
    );
}
