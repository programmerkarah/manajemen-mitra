import Activity from 'lucide-react/icons/activity';
import Banknote from 'lucide-react/icons/banknote';
import TrendingDown from 'lucide-react/icons/trending-down';
import TrendingUp from 'lucide-react/icons/trending-up';
import Users from 'lucide-react/icons/users';
import { formatRupiah, formatRupiahCompact } from '../helpers';
import type { RingkasanKPI } from '../types';

interface GeneralKpiGridProps {
    ringkasan: RingkasanKPI;
    currentYear: number;
}

export function GeneralKpiGrid({
    ringkasan,
    currentYear,
}: GeneralKpiGridProps) {
    const cards = [
        {
            label: 'Total Pagu',
            value: formatRupiahCompact(ringkasan.total_pagu),
            sub: formatRupiah(ringkasan.total_pagu),
            icon: Banknote,
            color: 'text-blue-600 dark:text-blue-400',
            bg: 'bg-blue-50 dark:bg-blue-900/20',
        },
        {
            label: 'Total Terpakai',
            value: formatRupiahCompact(ringkasan.total_terpakai),
            sub: formatRupiah(ringkasan.total_terpakai),
            icon: TrendingUp,
            color: 'text-green-600 dark:text-green-400',
            bg: 'bg-green-50 dark:bg-green-900/20',
        },
        {
            label: 'Penyerapan Anggaran',
            value: `${ringkasan.serapan_persen}%`,
            sub:
                ringkasan.serapan_persen >= 90
                    ? 'Mendekati batas'
                    : ringkasan.serapan_persen >= 70
                      ? 'Sedang'
                      : 'Masih rendah',
            icon: ringkasan.serapan_persen >= 70 ? TrendingUp : TrendingDown,
            color:
                ringkasan.serapan_persen >= 90
                    ? 'text-red-600 dark:text-red-400'
                    : ringkasan.serapan_persen >= 70
                      ? 'text-amber-600 dark:text-amber-400'
                      : 'text-green-600 dark:text-green-400',
            bg:
                ringkasan.serapan_persen >= 90
                    ? 'bg-red-50 dark:bg-red-900/20'
                    : ringkasan.serapan_persen >= 70
                      ? 'bg-amber-50 dark:bg-amber-900/20'
                      : 'bg-green-50 dark:bg-green-900/20',
        },
        {
            label: 'Petugas Aktif',
            value: ringkasan.total_petugas_aktif.toLocaleString('id-ID'),
            sub: 'Non-organik teralokasi',
            icon: Users,
            color: 'text-purple-600 dark:text-purple-400',
            bg: 'bg-purple-50 dark:bg-purple-900/20',
        },
        {
            label: 'Kegiatan Aktif',
            value: ringkasan.total_kegiatan_aktif.toLocaleString('id-ID'),
            sub: `Tahun ${currentYear}`,
            icon: Activity,
            color: 'text-orange-600 dark:text-orange-400',
            bg: 'bg-orange-50 dark:bg-orange-900/20',
        },
    ];

    return (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            {cards.map((card) => (
                <div
                    key={card.label}
                    className="rounded-2xl border border-neutral-200/70 bg-white/80 p-4 shadow-lg dark:border-neutral-800 dark:bg-neutral-900/80"
                >
                    <div className={`mb-3 inline-flex rounded-lg p-2 ${card.bg}`}>
                        <card.icon className={`h-4 w-4 ${card.color}`} />
                    </div>
                    <div className={`text-2xl font-bold ${card.color}`}>
                        {card.value}
                    </div>
                    <div className="mt-0.5 text-xs font-medium text-neutral-700 dark:text-neutral-300">
                        {card.label}
                    </div>
                    <div className="mt-0.5 truncate text-xs text-neutral-400 dark:text-neutral-500">
                        {card.sub}
                    </div>
                </div>
            ))}
        </div>
    );
}
