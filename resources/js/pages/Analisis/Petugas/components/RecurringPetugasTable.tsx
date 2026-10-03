import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useMemo, useState } from 'react';
import { kegiatanChipStyle } from '../helpers';
import type { PetugasRutinItem } from '../types';

interface RecurringPetugasTableProps {
    data: PetugasRutinItem[];
}

const PAGE_SIZE = 10;

export function RecurringPetugasTable({
    data,
}: RecurringPetugasTableProps) {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    const filteredData = useMemo(() => {
        if (!search.trim()) {
            return data;
        }

        const query = search.toLowerCase();

        return data.filter((item) =>
            item.petugas_nama.toLowerCase().includes(query),
        );
    }, [data, search]);

    if (data.length === 0) {
        return null;
    }

    const totalPages = Math.max(
        1,
        Math.ceil(filteredData.length / PAGE_SIZE),
    );
    const currentPage = Math.min(page, totalPages);
    const pageRows = filteredData.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <div className="mb-4 flex flex-wrap items-end gap-4">
                <div className="flex-1">
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                        Petugas dengan Kegiatan Rutin
                    </h3>
                    <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                        Petugas yang mengikuti kegiatan yang sama di minimal 2
                        bulan berbeda · {filteredData.length} petugas
                    </p>
                </div>
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
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Nama Petugas
                            </th>
                            <th className="py-2 pr-3 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                Jml Rutin
                            </th>
                            <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Kegiatan Rutin (jumlah bulan)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {pageRows.map((item) => (
                            <tr
                                key={item.petugas_id}
                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                            >
                                <td className="py-2 pr-3 font-medium text-neutral-900 dark:text-white">
                                    {item.petugas_nama}
                                </td>
                                <td className="py-2 pr-3 text-center font-bold text-neutral-900 dark:text-white">
                                    {item.jumlah_kegiatan_rutin}
                                </td>
                                <td className="py-2">
                                    <div className="flex flex-wrap gap-1.5">
                                        {item.kegiatan_rutin.map((kegiatan) => (
                                            <span
                                                key={kegiatan.kegiatan_id}
                                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs ${kegiatanChipStyle(kegiatan.kegiatan_id)}`}
                                            >
                                                {kegiatan.nama_kegiatan}
                                                <span className="rounded-full bg-black/10 px-1 py-px font-semibold dark:bg-white/15">
                                                    {kegiatan.jumlah_bulan}×
                                                </span>
                                            </span>
                                        ))}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {totalPages > 1 && (
                <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 text-xs dark:border-neutral-700">
                    <span className="text-neutral-500 dark:text-neutral-400">
                        Halaman {currentPage} dari {totalPages} &middot;{' '}
                        {filteredData.length} petugas
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
