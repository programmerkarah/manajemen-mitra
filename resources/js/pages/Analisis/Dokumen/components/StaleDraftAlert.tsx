import AlertTriangle from 'lucide-react/icons/alert-triangle';
import { monthNames } from '../constants';
import type { SkDraftLama } from '../types';

export default function StaleDraftAlert({
    items,
}: {
    items: SkDraftLama[];
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div className="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 shadow-lg backdrop-blur-xl dark:border-amber-800/40 dark:bg-amber-900/20">
            <div className="mb-3 flex items-center gap-2">
                <AlertTriangle className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                <h3 className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                    {items.length} SK masih draft lebih dari 14 hari
                </h3>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-amber-200 dark:border-amber-700/50">
                            <th className="py-1.5 text-left text-xs font-medium text-amber-700 dark:text-amber-400">
                                Kegiatan
                            </th>
                            <th className="py-1.5 text-center text-xs font-medium text-amber-700 dark:text-amber-400">
                                Bulan
                            </th>
                            <th className="py-1.5 text-center text-xs font-medium text-amber-700 dark:text-amber-400">
                                Umur
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {items.map((item) => (
                            <tr
                                key={item.id}
                                className="border-b border-amber-100 dark:border-amber-800/30"
                            >
                                <td className="py-1.5 text-xs text-amber-900 dark:text-amber-200">
                                    <span className="font-medium">
                                        {item.kegiatan_nama}
                                    </span>
                                </td>
                                <td className="py-1.5 text-center text-xs text-amber-800 dark:text-amber-300">
                                    {monthNames[item.bulan - 1]} {item.tahun}
                                </td>
                                <td className="py-1.5 text-center text-xs font-semibold text-amber-700 dark:text-amber-400">
                                    {item.umur_hari} hari
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
