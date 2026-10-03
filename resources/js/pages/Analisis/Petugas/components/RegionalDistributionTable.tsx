import { Button } from '@/components/ui/button';
import { useState } from 'react';
import type { DistribusiTugasDesaKelurahanItem } from '../types';

interface RegionalDistributionTableProps {
    data: DistribusiTugasDesaKelurahanItem[];
}

const PAGE_SIZE = 10;

export function RegionalDistributionTable({
    data,
}: RegionalDistributionTableProps) {
    const [page, setPage] = useState(1);
    const totalPages = Math.max(1, Math.ceil(data.length / PAGE_SIZE));
    const currentPage = Math.min(page, totalPages);
    const pageRows = data.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <div className="mb-4 flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                    Distribusi Petugas per Desa/Kelurahan
                </h3>
                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                    {data.length} wilayah
                </span>
            </div>
            {data.length > 0 ? (
                <>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                    <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        No
                                    </th>
                                    <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Kecamatan
                                    </th>
                                    <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Desa/Kelurahan
                                    </th>
                                    <th className="py-2 pr-3 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                        Petugas
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {pageRows.map((item, index) => {
                                    const rowNo =
                                        (currentPage - 1) * PAGE_SIZE +
                                        index +
                                        1;

                                    return (
                                        <tr
                                            key={`${item.desa_kelurahan}-${index}`}
                                            className="border-b border-neutral-100 dark:border-neutral-700/50"
                                        >
                                            <td className="py-1.5 pr-3 text-neutral-500 dark:text-neutral-400">
                                                {rowNo}
                                            </td>
                                            <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                                {item.kecamatan}
                                            </td>
                                            <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                                {item.desa_kelurahan}
                                            </td>
                                            <td className="py-1.5 pr-3 text-center font-semibold text-sky-600 dark:text-sky-400">
                                                {item.jumlah_petugas}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    {totalPages > 1 && (
                        <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                            <span className="text-neutral-500 dark:text-neutral-400">
                                Halaman {currentPage} dari {totalPages} &middot;{' '}
                                {data.length} wilayah
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    disabled={currentPage <= 1}
                                    onClick={() =>
                                        setPage((value) =>
                                            Math.max(value - 1, 1),
                                        )
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
                </>
            ) : (
                <p className="py-10 text-center text-sm text-neutral-400">
                    Data distribusi petugas desa/kelurahan belum tersedia
                </p>
            )}
        </div>
    );
}
