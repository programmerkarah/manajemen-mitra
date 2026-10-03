import { Input } from '@/components/ui/input';
import { useMemo, useState } from 'react';
import { monthNames } from '../helpers';
import type { PetugasAlokasiDetail } from '../types';

interface PetugasAllocationDetailTableProps {
    data: PetugasAlokasiDetail[];
}

export function PetugasAllocationDetailTable({
    data,
}: PetugasAllocationDetailTableProps) {
    const [search, setSearch] = useState('');

    const filteredData = useMemo(() => {
        if (!search.trim()) {
            return data;
        }

        const query = search.toLowerCase();

        return data.filter((item) =>
            item.petugas_nama.toLowerCase().includes(query),
        );
    }, [data, search]);

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                Detail Alokasi per Petugas (Jan – Des)
            </h3>
            <div className="mb-4 flex flex-wrap items-end gap-4">
                <div className="w-64">
                    <Input
                        placeholder="Cari nama petugas..."
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        className="h-9"
                    />
                </div>
                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {filteredData.length} petugas
                </span>
            </div>
            <div className="max-h-96 overflow-auto">
                <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-white dark:bg-neutral-800">
                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                            <th className="py-2 pr-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Nama Petugas
                            </th>
                            {monthNames.map((month) => (
                                <th
                                    key={month}
                                    className="py-2 text-center font-medium text-neutral-600 dark:text-neutral-400"
                                >
                                    {month}
                                </th>
                            ))}
                            <th className="py-2 pl-2 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredData.slice(0, 100).map((item) => (
                            <tr
                                key={item.petugas_id}
                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                            >
                                <td className="py-1.5 pr-2 text-neutral-900 dark:text-white">
                                    {item.petugas_nama}
                                </td>
                                {Array.from({ length: 12 }, (_, index) => index + 1).map(
                                    (month) => (
                                        <td
                                            key={month}
                                            className={`py-1.5 text-center ${
                                                item.bulan[month] > 0
                                                    ? 'font-medium text-neutral-900 dark:text-white'
                                                    : 'text-neutral-300 dark:text-neutral-600'
                                            }`}
                                        >
                                            {item.bulan[month] || 0}
                                        </td>
                                    ),
                                )}
                                <td className="py-1.5 pl-2 text-center font-bold text-neutral-900 dark:text-white">
                                    {item.total}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {filteredData.length > 100 && (
                    <p className="py-2 text-center text-xs text-neutral-400">
                        Menampilkan 100 dari {filteredData.length} petugas
                    </p>
                )}
            </div>
        </div>
    );
}
