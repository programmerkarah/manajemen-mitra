import { SearchableSelect } from '@/components/searchable-select';
import { Input } from '@/components/ui/input';
import { useMemo, useState } from 'react';
import { kegiatanChipStyle } from '../helpers';
import type {
    KegiatanListItem,
    PetugasKegiatanItem,
} from '../types';

interface PetugasKegiatanMappingTableProps {
    petugasKegiatan: PetugasKegiatanItem[];
    kegiatanList: KegiatanListItem[];
}

export function PetugasKegiatanMappingTable({
    petugasKegiatan,
    kegiatanList,
}: PetugasKegiatanMappingTableProps) {
    const [kegiatanFilter, setKegiatanFilter] = useState('');
    const [search, setSearch] = useState('');

    const filteredData = useMemo(() => {
        let data = petugasKegiatan;

        if (kegiatanFilter) {
            const kegiatanId = Number(kegiatanFilter);
            data = data.filter((item) =>
                item.kegiatan.some((kegiatan) => kegiatan.id === kegiatanId),
            );
        }

        if (search.trim()) {
            const query = search.toLowerCase();
            data = data.filter((item) =>
                item.petugas_nama.toLowerCase().includes(query),
            );
        }

        return data;
    }, [kegiatanFilter, petugasKegiatan, search]);

    return (
        <div className="rounded-2xl border border-white/20 bg-white/40 p-5 shadow-2xl backdrop-blur-2xl dark:border-neutral-700/30 dark:bg-neutral-800/50">
            <h3 className="mb-4 text-sm font-semibold text-neutral-900 dark:text-white">
                Pemetaan Petugas dan Kegiatan
            </h3>
            <div className="mb-4 flex flex-wrap gap-3">
                <Input
                    placeholder="Cari nama petugas..."
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    className="h-9 w-64"
                />
                <div className="w-80">
                    <SearchableSelect
                        options={[
                            { value: '', label: 'Semua Kegiatan' },
                            ...kegiatanList.map((kegiatan) => ({
                                value: String(kegiatan.id),
                                label: kegiatan.nama_kegiatan,
                            })),
                        ]}
                        value={kegiatanFilter}
                        onValueChange={setKegiatanFilter}
                        placeholder="Filter kegiatan..."
                        searchPlaceholder="Cari kegiatan..."
                    />
                </div>
                <span className="self-center text-xs text-neutral-500 dark:text-neutral-400">
                    {filteredData.length} petugas
                </span>
            </div>
            <div className="max-h-96 overflow-y-auto">
                <table className="w-full text-sm">
                    <thead className="sticky top-0 bg-white dark:bg-neutral-800">
                        <tr className="border-b border-neutral-200 dark:border-neutral-700">
                            <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Nama Petugas
                            </th>
                            <th className="py-2 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                Jml Kegiatan
                            </th>
                            <th className="py-2 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                Kegiatan
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredData.slice(0, 50).map((item) => (
                            <tr
                                key={item.petugas_id}
                                className="border-b border-neutral-100 dark:border-neutral-700/50"
                            >
                                <td className="py-1.5 text-neutral-900 dark:text-white">
                                    {item.petugas_nama}
                                </td>
                                <td className="py-1.5 text-center font-medium text-neutral-900 dark:text-white">
                                    {item.jumlah_kegiatan}
                                </td>
                                <td className="py-1.5">
                                    <div className="flex flex-wrap gap-1">
                                        {item.kegiatan.map((kegiatan) => (
                                            <span
                                                key={kegiatan.id}
                                                className={`inline-block rounded-full px-2 py-0.5 text-xs ${kegiatanChipStyle(kegiatan.id)}`}
                                            >
                                                {kegiatan.nama}
                                            </span>
                                        ))}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {filteredData.length > 50 && (
                    <p className="py-2 text-center text-xs text-neutral-400">
                        Menampilkan 50 dari {filteredData.length} petugas
                    </p>
                )}
            </div>
        </div>
    );
}
