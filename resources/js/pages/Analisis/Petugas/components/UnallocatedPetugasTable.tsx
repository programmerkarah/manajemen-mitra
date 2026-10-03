import { Button } from '@/components/ui/button';
import Check from 'lucide-react/icons/check';
import Copy from 'lucide-react/icons/copy';
import { useState } from 'react';
import type { PetugasBelumDialokasikanItem } from '../types';

interface UnallocatedPetugasTableProps {
    data: PetugasBelumDialokasikanItem[];
}

const PAGE_SIZE = 10;

export function UnallocatedPetugasTable({
    data,
}: UnallocatedPetugasTableProps) {
    const [page, setPage] = useState(1);
    const [copiedId, setCopiedId] = useState<number | 'all' | null>(null);

    if (data.length === 0) {
        return null;
    }

    const totalPages = Math.max(1, Math.ceil(data.length / PAGE_SIZE));
    const currentPage = Math.min(page, totalPages);
    const pageRows = data.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
    );

    const copyRow = (item: PetugasBelumDialokasikanItem, rowNo: number) => {
        const text = item.telepon
            ? `${rowNo}. ${item.nama} (${item.telepon})`
            : `${rowNo}. ${item.nama}`;

        navigator.clipboard.writeText(text).then(() => {
            setCopiedId(item.id);
            setTimeout(() => setCopiedId(null), 1500);
        });
    };

    const copyAll = () => {
        const lines = data.map((item, index) =>
            item.telepon
                ? `${index + 1}. ${item.nama} (${item.telepon})`
                : `${index + 1}. ${item.nama}`,
        );

        navigator.clipboard.writeText(lines.join('\n')).then(() => {
            setCopiedId('all');
            setTimeout(() => setCopiedId(null), 1500);
        });
    };

    return (
        <div className="rounded-2xl border border-amber-200/60 bg-amber-50/50 p-5 shadow-2xl backdrop-blur-2xl dark:border-amber-700/30 dark:bg-amber-900/10">
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                        Petugas Belum Pernah Dialokasikan
                    </h3>
                    <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                        {data.length} petugas aktif belum pernah mendapat
                        alokasi kegiatan
                    </p>
                </div>
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    className="gap-1.5 text-xs"
                    onClick={copyAll}
                >
                    {copiedId === 'all' ? (
                        <Check className="h-3.5 w-3.5 text-green-500" />
                    ) : (
                        <Copy className="h-3.5 w-3.5" />
                    )}
                    {copiedId === 'all' ? 'Tersalin!' : 'Salin Semua'}
                </Button>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                No
                            </th>
                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Nama
                            </th>
                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Jenis Kelamin
                            </th>
                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                No HP
                            </th>
                            <th className="py-2 pr-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Kecamatan
                            </th>
                            <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400" />
                        </tr>
                    </thead>
                    <tbody>
                        {pageRows.map((item, index) => {
                            const rowNo =
                                (currentPage - 1) * PAGE_SIZE + index + 1;

                            return (
                                <tr
                                    key={item.id}
                                    className="border-b border-neutral-100 dark:border-neutral-700/50"
                                >
                                    <td className="py-1.5 pr-3 text-neutral-500 dark:text-neutral-400">
                                        {rowNo}
                                    </td>
                                    <td className="py-1.5 pr-3 font-medium text-neutral-900 dark:text-white">
                                        {item.nama}
                                    </td>
                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                        {item.jenis_kelamin ?? '—'}
                                    </td>
                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                        {item.telepon ?? '—'}
                                    </td>
                                    <td className="py-1.5 pr-3 text-neutral-600 dark:text-neutral-400">
                                        {item.kecamatan ?? '—'}
                                    </td>
                                    <td className="py-1.5 text-right">
                                        <button
                                            type="button"
                                            title="Salin baris ini"
                                            onClick={() => copyRow(item, rowNo)}
                                            className="rounded p-1 text-neutral-400 transition hover:text-neutral-700 dark:hover:text-neutral-200"
                                        >
                                            {copiedId === item.id ? (
                                                <Check className="h-3.5 w-3.5 text-green-500" />
                                            ) : (
                                                <Copy className="h-3.5 w-3.5" />
                                            )}
                                        </button>
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
                        {data.length} petugas
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
