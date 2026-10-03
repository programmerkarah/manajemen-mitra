import AlertCircle from 'lucide-react/icons/alert-circle';
import Users from 'lucide-react/icons/users';

interface PetugasKpiGridProps {
    totalPetugas: number;
    belumDialokasikan: number;
    totalAlokasiTahun: number;
}

export function PetugasKpiGrid({
    totalPetugas,
    belumDialokasikan,
    totalAlokasiTahun,
}: PetugasKpiGridProps) {
    const sudahDialokasikan = totalPetugas - belumDialokasikan;
    const sudahDialokasikanPct =
        totalPetugas > 0
            ? Math.round((sudahDialokasikan / totalPetugas) * 100)
            : 0;
    const belumDialokasikanPct =
        totalPetugas > 0
            ? Math.round((belumDialokasikan / totalPetugas) * 100)
            : 0;

    const cards = [
        {
            label: 'Total Petugas Aktif',
            value: totalPetugas,
            sub: 'Petugas non-organik aktif',
            subColor: 'text-neutral-500 dark:text-neutral-400',
            barPct: 100,
            barColor: '#3b82f6',
            icon: <Users className="h-5 w-5 text-blue-500" />,
        },
        {
            label: 'Sudah Dialokasikan',
            value: sudahDialokasikan,
            sub: `${sudahDialokasikanPct}% dari total petugas`,
            subColor: 'text-green-600 dark:text-green-400',
            barPct: sudahDialokasikanPct,
            barColor: '#22c55e',
            icon: <Users className="h-5 w-5 text-green-500" />,
        },
        {
            label: 'Belum Dialokasikan',
            value: belumDialokasikan,
            sub: 'Belum ada alokasi kegiatan',
            subColor:
                belumDialokasikan > 0
                    ? 'text-amber-600 dark:text-amber-400'
                    : 'text-neutral-500 dark:text-neutral-400',
            barPct: belumDialokasikanPct,
            barColor: '#f59e0b',
            icon: <AlertCircle className="h-5 w-5 text-amber-500" />,
        },
        {
            label: 'Total Alokasi Tahun',
            value: totalAlokasiTahun,
            sub: 'Kumulatif slot alokasi bulanan',
            subColor: 'text-neutral-500 dark:text-neutral-400',
            barPct: 100,
            barColor: '#8b5cf6',
            icon: <Users className="h-5 w-5 text-purple-500" />,
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
                    <div className="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                        <div
                            className="h-full rounded-full transition-all duration-500"
                            style={{
                                width: `${card.barPct}%`,
                                backgroundColor: card.barColor,
                            }}
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}
