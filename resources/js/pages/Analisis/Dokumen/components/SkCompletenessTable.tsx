import CheckCircle2 from 'lucide-react/icons/check-circle2';
import Clock from 'lucide-react/icons/clock';
import XCircle from 'lucide-react/icons/x-circle';
import { useState } from 'react';
import { DOCUMENT_PAGE_SIZE, jenisLabel } from '../constants';
import type { KelengkapanSKPerKegiatan } from '../types';

const statusConfig = {
    belum: {
        label: 'Belum Ada SK',
        icon: <XCircle className="h-4 w-4 text-red-500" />,
        badge: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
    },
    sebagian: {
        label: 'Ada Draft',
        icon: <Clock className="h-4 w-4 text-amber-500" />,
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
    },
    lengkap: {
        label: 'Diterbitkan',
        icon: <CheckCircle2 className="h-4 w-4 text-green-500" />,
        badge: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
    },
};

interface Props {
    items: KelengkapanSKPerKegiatan[];
    currentYear: number;
}

export default function SkCompletenessTable({ items, currentYear }: Props) {
    const [currentPage, setCurrentPage] = useState(1);
    const totalPages = Math.ceil(items.length / DOCUMENT_PAGE_SIZE);
    const pagedItems = items.slice(
        (currentPage - 1) * DOCUMENT_PAGE_SIZE,
        currentPage * DOCUMENT_PAGE_SIZE,
    );

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-1 text-sm font-semibold text-neutral-900 dark:text-white">
                Kelengkapan SK per Kegiatan
            </h3>
            <p className="mb-4 text-xs text-neutral-500 dark:text-neutral-400">
                Status penerbitan SK KPA untuk setiap kegiatan aktif tahun{' '}
                {currentYear}
            </p>

            {items.length === 0 ? (
                <p className="text-sm text-neutral-400 dark:text-neutral-500">
                    Tidak ada data kegiatan aktif.
                </p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-neutral-200 dark:border-neutral-700">
                                <th className="py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Kegiatan
                                </th>
                                <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Jenis
                                </th>
                                <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Total SK
                                </th>
                                <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Diterbitkan
                                </th>
                                <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Ditandatangani
                                </th>
                                <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Draft
                                </th>
                                <th className="py-2 text-center text-xs font-medium text-neutral-500 dark:text-neutral-400">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {pagedItems.map((item) => {
                                const config =
                                    statusConfig[item.status_dokumen];

                                return (
                                    <tr
                                        key={item.kegiatan_id}
                                        className="border-b border-neutral-100 dark:border-neutral-700/50"
                                    >
                                        <td className="py-2">
                                            <p className="text-xs font-medium text-neutral-900 dark:text-white">
                                                {item.nama_kegiatan}
                                            </p>
                                        </td>
                                        <td className="py-2 text-center text-xs text-neutral-600 dark:text-neutral-400">
                                            {jenisLabel[item.jenis_kegiatan] ??
                                                item.jenis_kegiatan}
                                        </td>
                                        <td className="py-2 text-center text-xs font-semibold text-neutral-900 dark:text-white">
                                            {item.total_sk || '-'}
                                        </td>
                                        <td className="py-2 text-center text-xs font-medium text-green-600 dark:text-green-400">
                                            {item.sk_diterbitkan || '-'}
                                        </td>
                                        <td className="py-2 text-center text-xs font-medium text-blue-600 dark:text-blue-400">
                                            {item.sk_ditandatangani || '-'}
                                        </td>
                                        <td className="py-2 text-center text-xs font-medium text-amber-600 dark:text-amber-400">
                                            {item.sk_draft || '-'}
                                        </td>
                                        <td className="py-2 text-center">
                                            <span
                                                className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${config.badge}`}
                                            >
                                                {config.icon}
                                                {config.label}
                                            </span>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            {totalPages > 1 && (
                <div className="mt-4 flex items-center justify-between border-t border-neutral-200 pt-3 dark:border-neutral-700">
                    <p className="text-xs text-neutral-500 dark:text-neutral-400">
                        Menampilkan{' '}
                        {(currentPage - 1) * DOCUMENT_PAGE_SIZE + 1}–
                        {Math.min(
                            currentPage * DOCUMENT_PAGE_SIZE,
                            items.length,
                        )}{' '}
                        dari {items.length} kegiatan
                    </p>
                    <div className="flex items-center gap-1">
                        <button
                            type="button"
                            onClick={() =>
                                setCurrentPage((page) =>
                                    Math.max(1, page - 1),
                                )
                            }
                            disabled={currentPage === 1}
                            className="rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-medium text-neutral-700 shadow-sm transition hover:bg-neutral-50 disabled:opacity-40 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
                        >
                            ← Prev
                        </button>
                        {Array.from(
                            { length: totalPages },
                            (_, index) => index + 1,
                        ).map((page) => (
                            <button
                                key={page}
                                type="button"
                                onClick={() => setCurrentPage(page)}
                                className={`rounded-lg border px-2.5 py-1 text-xs font-medium shadow-sm transition ${
                                    page === currentPage
                                        ? 'border-blue-500 bg-blue-500 text-white'
                                        : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700'
                                }`}
                            >
                                {page}
                            </button>
                        ))}
                        <button
                            type="button"
                            onClick={() =>
                                setCurrentPage((page) =>
                                    Math.min(totalPages, page + 1),
                                )
                            }
                            disabled={currentPage === totalPages}
                            className="rounded-lg border border-neutral-200 bg-white px-2.5 py-1 text-xs font-medium text-neutral-700 shadow-sm transition hover:bg-neutral-50 disabled:opacity-40 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-200 dark:hover:bg-neutral-700"
                        >
                            Next →
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
