import {
    formatRupiah,
    serapanBarColor,
    serapanColor,
} from '../helpers';
import type { UtilisasiAnggaran } from '../types';

interface BudgetUtilizationTableProps {
    data: UtilisasiAnggaran[];
}

export function BudgetUtilizationTable({
    data,
}: BudgetUtilizationTableProps) {
    const filteredData = data.filter((item) => item.total_pagu > 0);

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                Penyerapan Anggaran per Kegiatan
            </h3>
            <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                Perbandingan pagu dan honor yang sudah dibayarkan per kegiatan
            </p>
            {filteredData.length > 0 ? (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="sticky top-0 bg-white/80 dark:bg-neutral-800/80">
                            <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                <th className="min-w-[200px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Kegiatan
                                </th>
                                <th className="min-w-[60px] py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Jenis
                                </th>
                                <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Pagu
                                </th>
                                <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Terpakai
                                </th>
                                <th className="min-w-[140px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Sisa
                                </th>
                                <th className="min-w-[120px] py-2 text-right text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    % Penyerapan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredData.map((item) => (
                                <tr
                                    key={item.kegiatan_id}
                                    className="border-b border-neutral-100 dark:border-neutral-700/50"
                                >
                                    <td className="py-2">
                                        <div className="font-medium text-neutral-900 dark:text-white">
                                            {item.nama_kegiatan}
                                        </div>
                                    </td>
                                    <td className="py-2">
                                        <span className="rounded-md bg-neutral-100 px-1.5 py-0.5 text-xs text-neutral-600 capitalize dark:bg-neutral-700 dark:text-neutral-300">
                                            {item.jenis_kegiatan}
                                        </span>
                                    </td>
                                    <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                        {formatRupiah(item.total_pagu)}
                                    </td>
                                    <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                        {formatRupiah(item.total_terpakai)}
                                    </td>
                                    <td className="py-2 text-right font-mono text-xs text-neutral-900 dark:text-white">
                                        {formatRupiah(
                                            item.total_pagu -
                                                item.total_terpakai,
                                        )}
                                    </td>
                                    <td className="py-2 text-right">
                                        <div
                                            className={`text-xs font-bold ${serapanColor(item.persentase)}`}
                                        >
                                            {item.persentase}%
                                        </div>
                                        <div className="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                            <div
                                                className="h-full rounded-full transition-all"
                                                style={{
                                                    width: `${Math.min(item.persentase, 100)}%`,
                                                    backgroundColor:
                                                        serapanBarColor(
                                                            item.persentase,
                                                        ),
                                                }}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <p className="py-10 text-center text-sm text-neutral-400">
                    Belum ada data kegiatan
                </p>
            )}
        </div>
    );
}
