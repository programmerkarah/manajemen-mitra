import { formatDecimal } from '@/lib/format-number';
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
    '#22c55e',
    '#f59e0b',
    '#ef4444',
    '#8b5cf6',
    '#ec4899',
    '#14b8a6',
    '#f97316',
] as const;

export function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

export function formatRupiahCompact(value: number): string {
    if (value >= 1_000_000_000) {
        return `${formatDecimal(value / 1_000_000_000, 1)}M`;
    }

    if (value >= 1_000_000) {
        return `${formatDecimal(value / 1_000_000, 1)}jt`;
    }

    return `${formatDecimal(value / 1_000, 0)}rb`;
}

export const glassTooltipClass =
    'rounded-xl border border-white/20 bg-white/80 p-3 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-900/80';

export interface PieLegendItem {
    label: string;
    value: number;
    percentage: number;
    color: string;
}

export function buildPieLegendItems<T extends { count: number }>(
    items: T[],
    getLabel: (item: T) => string,
    total: number,
): PieLegendItem[] {
    return items.map((item, index) => ({
        label: getLabel(item),
        value: item.count,
        percentage: total > 0 ? (item.count / total) * 100 : 0,
        color: COLORS[index % COLORS.length],
    }));
}

export function PieLegendList({ items }: { items: PieLegendItem[] }) {
    return (
        <div className="mt-4 max-h-40 space-y-2 overflow-y-auto pr-1">
            {items.map((item) => (
                <div
                    key={item.label}
                    className="flex items-center justify-between gap-3 rounded-lg border border-neutral-200/70 bg-white/70 px-3 py-2 text-xs dark:border-neutral-700/60 dark:bg-neutral-900/40"
                >
                    <div className="flex items-center gap-2 text-neutral-700 dark:text-neutral-200">
                        <span
                            className="inline-block h-2.5 w-2.5 rounded-full"
                            style={{ backgroundColor: item.color }}
                        />
                        <span className="font-medium">{item.label}</span>
                    </div>
                    <div className="text-right text-neutral-600 dark:text-neutral-300">
                        <div className="font-semibold">{item.value}</div>
                        <div>{formatDecimal(item.percentage, 1)}%</div>
                    </div>
                </div>
            ))}
        </div>
    );
}

export function serapanColor(persen: number): string {
    if (persen >= 90) return 'text-red-600 dark:text-red-400';
    if (persen >= 70) return 'text-amber-600 dark:text-amber-400';

    return 'text-green-600 dark:text-green-400';
}

export function serapanBarColor(persen: number): string {
    if (persen >= 90) return '#ef4444';
    if (persen >= 70) return '#f59e0b';

    return '#22c55e';
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
                {value} ({formatDecimal((percent ?? 0) * 100, 1)}%)
            </text>
        </g>
    );
}
