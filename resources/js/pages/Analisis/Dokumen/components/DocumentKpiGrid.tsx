import FileCheck2 from 'lucide-react/icons/file-check2';
import FileText from 'lucide-react/icons/file-text';
import type { ReactNode } from 'react';

interface KpiCard {
    label: string;
    value: number;
    sub: string;
    subColor: string;
    pct: number;
    barColor: string;
    icon: ReactNode;
    draft: number | null;
}

interface Props {
    skTotal: number;
    skDiterbitkan: number;
    skDraft: number;
    spkTotal: number;
    spkDiterbitkan: number;
    spkDraft: number;
}

function ProgressBar({
    value,
    max,
    color,
}: {
    value: number;
    max: number;
    color: string;
}) {
    const pct = max > 0 ? Math.min((value / max) * 100, 100) : 0;

    return (
        <div className="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
            <div
                className="h-full rounded-full transition-all duration-500"
                style={{ width: `${pct}%`, backgroundColor: color }}
            />
        </div>
    );
}

function statusTone(pct: number) {
    if (pct >= 80) {
        return {
            text: 'text-green-600 dark:text-green-400',
            bar: '#22c55e',
        };
    }

    if (pct >= 50) {
        return {
            text: 'text-amber-600 dark:text-amber-400',
            bar: '#f59e0b',
        };
    }

    return {
        text: 'text-red-600 dark:text-red-400',
        bar: '#ef4444',
    };
}

export default function DocumentKpiGrid({
    skTotal,
    skDiterbitkan,
    skDraft,
    spkTotal,
    spkDiterbitkan,
    spkDraft,
}: Props) {
    const skPctDiterbitkan =
        skTotal > 0 ? Math.round((skDiterbitkan / skTotal) * 100) : 0;
    const spkPctDiterbitkan =
        spkTotal > 0 ? Math.round((spkDiterbitkan / spkTotal) * 100) : 0;

    const skTone = statusTone(skPctDiterbitkan);
    const spkTone = statusTone(spkPctDiterbitkan);

    const cards: KpiCard[] = [
        {
            label: 'Total SK KPA',
            value: skTotal,
            sub: `${skDiterbitkan} diterbitkan`,
            subColor: 'text-green-600 dark:text-green-400',
            pct: skPctDiterbitkan,
            barColor: '#22c55e',
            icon: <FileText className="h-5 w-5 text-blue-500" />,
            draft: skDraft,
        },
        {
            label: 'SK Diterbitkan',
            value: skDiterbitkan,
            sub: `${skPctDiterbitkan}% dari total`,
            subColor: skTone.text,
            pct: skPctDiterbitkan,
            barColor: skTone.bar,
            icon: <FileCheck2 className="h-5 w-5 text-green-500" />,
            draft: null,
        },
        {
            label: 'Total Perjanjian Kerja',
            value: spkTotal,
            sub: `${spkDiterbitkan} diterbitkan`,
            subColor: 'text-green-600 dark:text-green-400',
            pct: spkPctDiterbitkan,
            barColor: '#3b82f6',
            icon: <FileText className="h-5 w-5 text-purple-500" />,
            draft: spkDraft,
        },
        {
            label: 'SPK Diterbitkan',
            value: spkDiterbitkan,
            sub: `${spkPctDiterbitkan}% dari total`,
            subColor: spkTone.text,
            pct: spkPctDiterbitkan,
            barColor: spkTone.bar,
            icon: <FileCheck2 className="h-5 w-5 text-blue-500" />,
            draft: null,
        },
    ];

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {cards.map((card) => (
                <div
                    key={card.label}
                    className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50"
                >
                    <div className="mb-3 flex items-center justify-between">
                        <p className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                            {card.label}
                        </p>
                        {card.icon}
                    </div>
                    <p className="text-2xl font-bold text-neutral-900 dark:text-white">
                        {card.value}
                    </p>
                    <p className={`mt-0.5 text-xs ${card.subColor}`}>
                        {card.sub}
                    </p>
                    <p
                        className={`mt-0.5 text-xs ${
                            card.draft !== null && card.draft > 0
                                ? 'text-amber-600 dark:text-amber-400'
                                : 'invisible'
                        }`}
                    >
                        {card.draft !== null && card.draft > 0
                            ? `${card.draft} masih draft`
                            : '\u00A0'}
                    </p>
                    <div className="mt-3">
                        <ProgressBar
                            value={card.pct}
                            max={100}
                            color={card.barColor}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}
