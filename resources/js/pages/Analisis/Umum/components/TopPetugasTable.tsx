import { formatRupiah } from '../helpers';
import type { TopPetugas } from '../types';

interface TopPetugasTableProps {
    data: TopPetugas[];
    currentYear: number;
    currentMonth: number;
}

export function TopPetugasTable({
    data,
    currentYear,
    currentMonth,
}: TopPetugasTableProps) {
    if (data.length === 0) {
        return null;
    }

    const maxHonor = data[0]?.total_honor ?? 1;
    const periodLabel = new Date(
        currentYear,
        currentMonth - 1,
    ).toLocaleDateString('id-ID', {
        month: 'long',
        year: 'numeric',
    });

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                10 Petugas dengan Honor Tertinggi
            </h3>
            <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                Petugas non-organik dengan total honor terbesar s.d.{' '}
                {periodLabel} (dengan bobot alokasi SE2026)
            </p>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                            <th className="w-8 py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                #
                            </th>
                            <th className="py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                Nama
                            </th>
                            <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                Kegiatan
                            </th>
                            <th className="min-w-[200px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                Total Honor
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {data.map((petugas, index) => {
                            const barPct =
                                maxHonor > 0
                                    ? (petugas.total_honor / maxHonor) * 100
                                    : 0;

                            return (
                                <tr
                                    key={petugas.petugas_id}
                                    className="border-b border-neutral-100 dark:border-neutral-700/50"
                                >
                                    <td className="py-2 text-center">
                                        <span
                                            className={`inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ${
                                                index === 0
                                                    ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400'
                                                    : index === 1
                                                      ? 'bg-neutral-200 text-neutral-600 dark:bg-neutral-700 dark:text-neutral-300'
                                                      : index === 2
                                                        ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                                                        : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400'
                                            }`}
                                        >
                                            {index + 1}
                                        </span>
                                    </td>
                                    <td className="py-2">
                                        <div className="font-medium text-neutral-900 dark:text-white">
                                            {petugas.nama}
                                        </div>
                                        {petugas.jabatan && (
                                            <div className="text-xs text-neutral-400 dark:text-neutral-500">
                                                {petugas.jabatan}
                                            </div>
                                        )}
                                    </td>
                                    <td className="py-2 text-center text-neutral-600 dark:text-neutral-400">
                                        {petugas.jumlah_kegiatan}
                                    </td>
                                    <td className="py-2">
                                        <div className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                                            {formatRupiah(
                                                petugas.total_honor,
                                            )}
                                        </div>
                                        <div className="h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                            <div
                                                className="h-full rounded-full bg-blue-500 transition-all"
                                                style={{
                                                    width: `${barPct}%`,
                                                }}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
