import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useMemo, useState } from 'react';
import { formatRupiah, monthNames } from '../helpers';
import type { PetugasAlokasiDetail } from '../types';

interface PetugasHonorTableProps {
    data: PetugasAlokasiDetail[];
}

const PAGE_SIZE = 15;

export function PetugasHonorTable({ data }: PetugasHonorTableProps) {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const sortedData = useMemo(() => {
        const filtered = search.trim()
            ? data.filter((item) =>
                  item.petugas_nama
                      .toLowerCase()
                      .includes(search.toLowerCase()),
              )
            : data;

        return [...filtered].sort((a, b) => b.total_honor - a.total_honor);
    }, [data, search]);

    const totalPages = Math.max(1, Math.ceil(sortedData.length / PAGE_SIZE));
    const currentPage = Math.min(page, totalPages);
    const pageRows = sortedData.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );
    const grandTotal = useMemo(
        () => data.reduce((sum, item) => sum + item.total_honor, 0),
        [data],
    );

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                Honor per Bulan per Petugas (Jan – Des)
            </h3>
            <div className="mb-4 flex flex-wrap items-end gap-4">
                <div className="w-64">
                    <Input
                        placeholder="Cari nama petugas..."
                        value={search}
                        onChange={(event) => {
                            setSearch(event.target.value);
                            setPage(1);
                        }}
                        className="h-9"
                    />
                </div>
                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {sortedData.length} petugas
                </span>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-white dark:bg-neutral-800">
                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                            <th className="py-2 pr-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Nama Petugas
                            </th>
                            {monthNames.map((month) => (
                                <th
                                    key={month}
                                    className="py-2 text-right font-medium text-neutral-600 dark:text-neutral-400"
                                >
                                    {month}
                                </th>
                            ))}
                            <th className="py-2 pl-2 text-right font-medium text-neutral-600 dark:text-neutral-400">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {pageRows.map((item) => (
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
                                            className={`py-1.5 text-right tabular-nums ${
                                                item.honor[month] > 0
                                                    ? 'font-medium text-neutral-900 dark:text-white'
                                                    : 'text-neutral-300 dark:text-neutral-600'
                                            }`}
                                        >
                                            {item.honor[month] > 0
                                                ? formatRupiah(item.honor[month])
                                                : '—'}
                                        </td>
                                    ),
                                )}
                                <td className="py-1.5 pl-2 text-right font-bold text-neutral-900 tabular-nums dark:text-white">
                                    {formatRupiah(item.total_honor)}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr className="border-t-2 border-neutral-300 dark:border-neutral-600">
                            <td
                                colSpan={13}
                                className="py-2 pr-2 font-bold text-neutral-900 dark:text-white"
                            >
                                Total
                            </td>
                            <td className="py-2 pl-2 text-right font-bold text-neutral-900 tabular-nums dark:text-white">
                                {formatRupiah(grandTotal)}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {totalPages > 1 && (
                <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                    <span className="text-neutral-500 dark:text-neutral-400">
                        Halaman {currentPage} dari {totalPages} &middot;{' '}
                        {sortedData.length} petugas
                    </span>
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={currentPage <= 1}
                            onClick={() =>
                                setPage((value) => Math.max(value - 1, 1))
                            }
                        >
                            Sebelumnya
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            disabled={currentPage >= totalPages}
                            onClick={() =>
                                setPage((value) =>
                                    Math.min(value + 1, totalPages),
                                )
                            }
                        >
                            Berikutnya
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}
