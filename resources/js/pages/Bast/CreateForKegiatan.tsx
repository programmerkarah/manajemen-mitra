import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { CalendarDays, CheckSquare, FileText } from 'lucide-react';
import { useCallback, useState } from 'react';

interface PpkInfo {
    nama: string;
    nip?: string | null;
}

interface KegiatanInfo {
    id: number;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    ketua_tim_nama?: string | null;
    ketua_tim_nip?: string | null;
}

interface KegiatanAlokasi {
    kegiatan_id: number;
    kode_kegiatan: string;
    nama_kegiatan: string;
    peran: string;
    jumlah_satuan: number;
    jumlah_satuan_listing?: number | null;
    bulan: string;
    tahun: string;
}

interface PetugasItem {
    id: number;
    petugas_id: number;
    spk_id: number;
    nama_petugas: string;
    nomor_spk: string;
    peran?: string | null;
    hasil_listing?: number | null;
    satuan_listing?: string | null;
    hasil_pendataan_lapangan?: number | null;
    satuan_pendataan_lapangan?: string | null;
    hasil_pengolahan?: number | null;
    satuan_pengolahan?: string | null;
    hasil_pengolahan_listing?: number | null;
    satuan_pengolahan_listing?: string | null;
    catatan?: string | null;
    kegiatan_list?: KegiatanAlokasi[];
}

interface CreateForKegiatanProps {
    kegiatan: KegiatanInfo;
    petugas_list: PetugasItem[];
    show_listing_columns?: boolean;
    show_pengolahan_columns?: boolean;
    ppk?: PpkInfo | null;
    status_periode: 'dikirim' | 'perubahan';
    has_actual_listing_data?: boolean;
    has_actual_pendataan_data?: boolean;
    has_actual_pengolahan_listing_data?: boolean;
    has_actual_pengolahan_lapangan_data?: boolean;
}

const peranLabel = (peran?: string | null): string => {
    if (!peran) return '-';
    const key = peran.toLowerCase();
    const map: Record<string, string> = {
        pengolahan: 'Petugas Pengolahan',
        pml: 'Petugas Pemeriksa Pemutakhiran / Lapangan',
        pemeriksa_pengolahan: 'Petugas Pemeriksa Pengolahan',
        pcl_ppl: 'Petugas Pencacahan Pemutakhiran / Lapangan',
    };
    return map[key] ?? peran;
};

const breadcrumbs = (keg: KegiatanInfo): BreadcrumbItem[] => [
    { title: 'BAST', href: '/bast' },
    {
        title: keg.nama_kegiatan,
        href: `/bast/kegiatan/${keg.hashed_id}/create`,
    },
];

export default function CreateForKegiatan({
    kegiatan,
    petugas_list,
    show_listing_columns = false,
    show_pengolahan_columns = false,
    ppk,
    status_periode,
    has_actual_listing_data = false,
    has_actual_pendataan_data = false,
    has_actual_pengolahan_listing_data = false,
    has_actual_pengolahan_lapangan_data = false,
}: CreateForKegiatanProps) {
    const anyPengolahan = petugas_list.some(
        (p) => (p.peran ?? '').toLowerCase() === 'pengolahan',
    );

    // Use data from backend instead of calculating here
    const hasActualListingData = has_actual_listing_data;
    const hasActualPendataanData = has_actual_pendataan_data;
    const hasActualPengolahanListingData = has_actual_pengolahan_listing_data;
    const hasActualPengolahanLapanganData = has_actual_pengolahan_lapangan_data;
    const [validationModalOpen, setValidationModalOpen] = useState(false);
    const [validationMessages, setValidationMessages] = useState<string[]>([]);

    const handlePreview = useCallback(
        (event: React.MouseEvent<HTMLButtonElement>, idx?: number | null) => {
            event.preventDefault();

            const form = event.currentTarget.form;
            if (!form) return;
            // validate required fields first
            const msgs = validateAll(form);
            if (msgs.length > 0) {
                setValidationMessages(msgs);
                setValidationModalOpen(true);
                return;
            }

            const formData = new FormData(form);
            if (typeof idx === 'number' && idx >= 0) {
                formData.set('petugas_index', String(idx));
            }

            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            const tempForm = document.createElement('form');
            tempForm.method = 'POST';
            tempForm.action = '/bast/preview';
            tempForm.target = '_blank';
            tempForm.style.display = 'none';

            if (csrf) {
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = csrf;
                tempForm.appendChild(tokenInput);
            }

            formData.forEach((value, key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = typeof value === 'string' ? value : '';
                tempForm.appendChild(input);
            });

            document.body.appendChild(tempForm);
            tempForm.submit();
            document.body.removeChild(tempForm);
        },
        [],
    );

    function validateAll(form: HTMLFormElement): string[] {
        const f = form as any as HTMLFormElement;
        const messages: string[] = [];

        const getVal = (name: string): string | null => {
            const el = (f.elements as any)[name];
            if (!el) return null;
            // handle NodeList
            if (el.value === undefined && el.length) {
                return (el[0].value ?? '').toString().trim();
            }
            return (el.value ?? '').toString().trim();
        };

        const tanggal = getVal('tanggal_bast');
        if (!tanggal) {
            messages.push('Tanggal BAST harus diisi.');
        }

        // global instruments: listing may be conditional based on actual data
        if (hasActualListingData) {
            const instrListing = getVal('instrumen_listing');
            if (!instrListing) {
                messages.push('Instrumen Listing harus diisi.');
            }
        }

        // Instrumen Pendataan only required if there's actual pendataan data
        if (hasActualPendataanData) {
            const instrPendataan = getVal('instrumen_pendataan_lapangan');
            if (!instrPendataan) {
                messages.push('Instrumen Pendataan / Lapangan harus diisi.');
            }
        }

        // Validate each petugas visible inputs (except catatan) - only check editable fields
        for (let i = 0; i < petugas_list.length; i++) {
            const nameBase = `petugas[${i}]`;

            // Only validate editable fields - readonly fields should not be validated
            // All petugas data fields are readonly (coming from alokasi data)
            // So we don't need to validate any petugas-specific data fields

            // Note: No validation for petugas data fields since they're all readonly
            // They come from alokasi data and cannot be edited in BAST form
        }

        return messages;
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs(kegiatan)}>
            <Head title={`Buat BAST - ${kegiatan.nama_kegiatan}`} />

            <div className="space-y-6">
                <PageHeader
                    title={`Buat BAST — ${kegiatan.nama_kegiatan}`}
                    description={`Periode ${status_periode === 'perubahan' ? 'Perubahan' : 'Dikirim'} • Ketua Tim: ${kegiatan.ketua_tim_nama ?? '-'}`}
                />

                <ContentCard>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <div className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                Kegiatan
                            </div>
                            <div className="text-neutral-900 dark:text-neutral-100">
                                {kegiatan.nama_kegiatan}
                            </div>
                            <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                {kegiatan.kode_kegiatan}
                            </div>
                        </div>
                        <div>
                            <div className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                PPK
                            </div>
                            <div className="text-neutral-900 dark:text-neutral-100">
                                {ppk?.nama ?? 'Tidak ada data PPK aktif'}
                            </div>
                            <div className="text-sm text-neutral-500 dark:text-neutral-400">
                                NIP: {ppk?.nip ?? '-'}
                            </div>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard>
                    <Form
                        action="/bast"
                        method="post"
                        onSubmit={(e: any) => {
                            const form = e.currentTarget as HTMLFormElement;
                            const msgs = validateAll(form);
                            if (msgs.length > 0) {
                                e.preventDefault();
                                setValidationMessages(msgs);
                                setValidationModalOpen(true);
                            }
                        }}
                    >
                        {({ processing, wasSuccessful, errors }) => {
                            const backendErrors = usePage().props
                                .errors as Record<string, string>;
                            const hasErrors =
                                Object.keys(backendErrors).length > 0;

                            return (
                                <div className="space-y-6">
                                    {/* Backend Validation Errors */}
                                    {hasErrors && (
                                        <div className="rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                                            <div className="flex">
                                                <div className="flex-shrink-0">
                                                    <svg
                                                        className="h-5 w-5 text-red-400"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path
                                                            fillRule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                            clipRule="evenodd"
                                                        />
                                                    </svg>
                                                </div>
                                                <div className="ml-3">
                                                    <h3 className="text-sm font-medium text-red-800 dark:text-red-200">
                                                        Terjadi kesalahan
                                                        validasi
                                                    </h3>
                                                    <div className="mt-2 text-sm text-red-700 dark:text-red-300">
                                                        <ul className="list-disc space-y-1 pl-5">
                                                            {Object.entries(
                                                                backendErrors,
                                                            ).map(
                                                                ([
                                                                    field,
                                                                    message,
                                                                ]) => (
                                                                    <li
                                                                        key={
                                                                            field
                                                                        }
                                                                    >
                                                                        {
                                                                            message
                                                                        }
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                    {/* Hidden core fields */}
                                    <input
                                        type="hidden"
                                        name="kegiatan_id"
                                        value={kegiatan.id}
                                    />
                                    {(() => {
                                        const bulan = new URLSearchParams(
                                            window.location.search,
                                        ).get('bulan');
                                        const tahun = new URLSearchParams(
                                            window.location.search,
                                        ).get('tahun');
                                        return (
                                            <>
                                                {bulan && (
                                                    <input
                                                        type="hidden"
                                                        name="bulan"
                                                        value={bulan}
                                                    />
                                                )}
                                                {tahun && (
                                                    <input
                                                        type="hidden"
                                                        name="tahun"
                                                        value={tahun}
                                                    />
                                                )}
                                            </>
                                        );
                                    })()}

                                    {/* Tanggal BAST */}
                                    <div>
                                        <label className="mb-1 block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                            Tanggal BAST
                                        </label>
                                        <div className="relative max-w-sm">
                                            <CalendarDays className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-500" />
                                            <Input
                                                type="date"
                                                name="tanggal_bast"
                                                required
                                                className="pl-9"
                                            />
                                        </div>
                                    </div>

                                    {/* FASIH */}
                                    <div>
                                        <label className="inline-flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                            <CheckSquare className="h-4 w-4" />
                                            <span>
                                                Gunakan klausul penghapusan
                                                Aplikasi FASIH
                                            </span>
                                        </label>
                                        <div className="mt-2">
                                            <input
                                                type="hidden"
                                                name="menggunakan_fasih"
                                                value="0"
                                            />
                                            <input
                                                type="checkbox"
                                                name="menggunakan_fasih"
                                                value="1"
                                                className="h-4 w-4"
                                            />
                                        </div>
                                    </div>

                                    {/* Lampiran Kegiatan */}
                                    <div>
                                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                            <FileText className="h-4 w-4" />
                                            Lampiran — Rincian Kegiatan yang Dialokasikan
                                        </div>
                                        <div className="mb-6 overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700">
                                            <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                                <thead className="bg-neutral-50 dark:bg-neutral-800">
                                                    <tr>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-neutral-700 dark:text-neutral-300">
                                                            Petugas
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-neutral-700 dark:text-neutral-300">
                                                            Kegiatan
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-neutral-700 dark:text-neutral-300">
                                                            Peran
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-neutral-700 dark:text-neutral-300">
                                                            Volume
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase text-neutral-700 dark:text-neutral-300">
                                                            Periode
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                                    {petugas_list.flatMap((petugas) =>
                                                        (petugas.kegiatan_list || []).map((kegiatan, idx) => (
                                                            <tr key={`${petugas.petugas_id}-${kegiatan.kegiatan_id}`}>
                                                                <td className="px-3 py-2 text-sm">
                                                                    {idx === 0 && (
                                                                        <div className="font-medium text-neutral-900 dark:text-white">
                                                                            {petugas.nama_petugas}
                                                                        </div>
                                                                    )}
                                                                </td>
                                                                <td className="px-3 py-2 text-sm">
                                                                    <div className="font-medium text-neutral-900 dark:text-white">
                                                                        {kegiatan.nama_kegiatan}
                                                                    </div>
                                                                    <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                        {kegiatan.kode_kegiatan}
                                                                    </div>
                                                                </td>
                                                                <td className="px-3 py-2 text-sm text-neutral-700 dark:text-neutral-300">
                                                                    {peranLabel(kegiatan.peran)}
                                                                </td>
                                                                <td className="px-3 py-2 text-sm text-neutral-700 dark:text-neutral-300">
                                                                    {kegiatan.jumlah_satuan || 0} satuan
                                                                    {kegiatan.jumlah_satuan_listing && (
                                                                        <div className="text-xs text-neutral-500">
                                                                            Listing: {kegiatan.jumlah_satuan_listing}
                                                                        </div>
                                                                    )}
                                                                </td>
                                                                <td className="px-3 py-2 text-sm text-neutral-700 dark:text-neutral-300">
                                                                    {String(kegiatan.bulan).padStart(2, '0')}/{kegiatan.tahun}
                                                                </td>
                                                            </tr>
                                                        ))
                                                    )}
                                                    {petugas_list.every((p) => !p.kegiatan_list || p.kegiatan_list.length === 0) && (
                                                        <tr>
                                                            <td colSpan={5} className="px-6 py-8 text-center text-neutral-500">
                                                                Tidak ada kegiatan yang dialokasikan.
                                                            </td>
                                                        </tr>
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {/* Lampiran Petugas */}
                                    <div>
                                        <div className="mb-3 flex items-center gap-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                            <FileText className="h-4 w-4" />
                                            Lampiran — Rincian Petugas dan Hasil
                                            Pekerjaan
                                        </div>
                                        {(() => {
                                            const showListingInput =
                                                hasActualListingData;
                                            const showPendataanInput =
                                                hasActualPendataanData; // Only show if value > 0
                                            const showPengolahanListingInput =
                                                hasActualPengolahanListingData;

                                            return (
                                                <div className="mb-4 grid grid-cols-2 gap-4">
                                                    {showListingInput && (
                                                        <div>
                                                            <div className="text-xs text-neutral-500">
                                                                Instrumen
                                                                Listing
                                                                <span className="ml-1 text-red-500">
                                                                    *
                                                                </span>
                                                            </div>
                                                            <Input
                                                                name="instrumen_listing"
                                                                placeholder="Nama instrumen untuk listing"
                                                                required
                                                            />
                                                        </div>
                                                    )}
                                                    {showPendataanInput && (
                                                        <div>
                                                            <div className="text-xs text-neutral-500">
                                                                Instrumen
                                                                Pendataan /
                                                                Lapangan
                                                                <span className="ml-1 text-red-500">
                                                                    *
                                                                </span>
                                                            </div>
                                                            <Input
                                                                name="instrumen_pendataan_lapangan"
                                                                placeholder="Nama instrumen untuk pendataan/lapangan"
                                                                required
                                                            />
                                                        </div>
                                                    )}

                                                    {/* Hidden fields for instruments not displayed */}
                                                    {!showListingInput && (
                                                        <input
                                                            type="hidden"
                                                            name="instrumen_listing"
                                                            value=""
                                                        />
                                                    )}
                                                    {!showPendataanInput && (
                                                        <input
                                                            type="hidden"
                                                            name="instrumen_pendataan_lapangan"
                                                            value=""
                                                        />
                                                    )}
                                                </div>
                                            );
                                        })()}
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                                <thead className="bg-neutral-50 dark:bg-neutral-800">
                                                    <tr>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase">
                                                            No
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase">
                                                            Nama Petugas
                                                        </th>
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase">
                                                            Nomor SPK
                                                        </th>
                                                        {hasActualListingData && (
                                                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase">
                                                                Hasil Listing
                                                            </th>
                                                        )}
                                                        {hasActualPendataanData && (
                                                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase">
                                                                Hasil Pencacahan
                                                            </th>
                                                        )}
                                                        {hasActualPengolahanListingData && (
                                                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase">
                                                                Hasil Pengolahan
                                                                Listing
                                                            </th>
                                                        )}
                                                        {hasActualPengolahanLapanganData && (
                                                            <th className="px-3 py-2 text-left text-xs font-semibold uppercase">
                                                                Hasil Pengolahan
                                                                Lapangan
                                                            </th>
                                                        )}
                                                        <th className="px-3 py-2 text-left text-xs font-semibold uppercase">
                                                            Catatan
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                                    {petugas_list.length ===
                                                    0 ? (
                                                        <tr>
                                                            <td
                                                                colSpan={
                                                                    3 + // Base columns: No, Nama Petugas, Nomor SPK
                                                                    (hasActualListingData
                                                                        ? 1
                                                                        : 0) +
                                                                    (hasActualPendataanData
                                                                        ? 1
                                                                        : 0) +
                                                                    (hasActualPengolahanListingData
                                                                        ? 1
                                                                        : 0) +
                                                                    (hasActualPengolahanLapanganData
                                                                        ? 1
                                                                        : 0) +
                                                                    1 // Catatan column
                                                                }
                                                                className="px-6 py-8 text-center text-neutral-500"
                                                            >
                                                                Tidak ada
                                                                petugas dengan
                                                                SPK untuk
                                                                periode ini.
                                                            </td>
                                                        </tr>
                                                    ) : (
                                                        petugas_list.map(
                                                            (p, idx) => (
                                                                <tr key={p.id}>
                                                                    <td className="px-3 py-2 text-sm">
                                                                        {idx +
                                                                            1}
                                                                    </td>
                                                                    <td className="px-3 py-2 text-sm">
                                                                        <div className="font-medium">
                                                                            {
                                                                                p.nama_petugas
                                                                            }
                                                                        </div>
                                                                        <div className="text-xs text-neutral-500">
                                                                            {peranLabel(
                                                                                p.peran,
                                                                            )}
                                                                        </div>
                                                                        {/* Hidden required fields */}
                                                                        <input
                                                                            type="hidden"
                                                                            name={`petugas[${idx}][petugas_id]`}
                                                                            value={
                                                                                p.petugas_id
                                                                            }
                                                                        />
                                                                        <input
                                                                            type="hidden"
                                                                            name={`petugas[${idx}][spk_id]`}
                                                                            value={
                                                                                p.spk_id
                                                                            }
                                                                        />
                                                                        <input
                                                                            type="hidden"
                                                                            name={`petugas[${idx}][nomor_spk]`}
                                                                            value={
                                                                                p.nomor_spk
                                                                            }
                                                                        />
                                                                        <input
                                                                            type="hidden"
                                                                            name={`petugas[${idx}][nama_petugas]`}
                                                                            value={
                                                                                p.nama_petugas
                                                                            }
                                                                        />

                                                                        {/* Hidden fields for columns not displayed */}
                                                                        {!hasActualListingData && (
                                                                            <>
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][hasil_listing]`}
                                                                                    value={
                                                                                        p.hasil_listing ??
                                                                                        0
                                                                                    }
                                                                                />
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][satuan_listing]`}
                                                                                    value={
                                                                                        p.satuan_listing ??
                                                                                        ''
                                                                                    }
                                                                                />
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][instrumen_listing]`}
                                                                                    value=""
                                                                                />
                                                                            </>
                                                                        )}
                                                                        {!hasActualPendataanData && (
                                                                            <>
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][hasil_pendataan_lapangan]`}
                                                                                    value={
                                                                                        p.hasil_pendataan_lapangan ??
                                                                                        0
                                                                                    }
                                                                                />
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][satuan_pendataan_lapangan]`}
                                                                                    value={
                                                                                        p.satuan_pendataan_lapangan ??
                                                                                        ''
                                                                                    }
                                                                                />
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][instrumen_pendataan_lapangan]`}
                                                                                    value=""
                                                                                />
                                                                            </>
                                                                        )}
                                                                        {!hasActualPengolahanListingData && (
                                                                            <>
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][hasil_pengolahan_listing]`}
                                                                                    value={
                                                                                        p.hasil_pengolahan_listing ??
                                                                                        0
                                                                                    }
                                                                                />
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][satuan_pengolahan_listing]`}
                                                                                    value={
                                                                                        p.satuan_pengolahan_listing ??
                                                                                        ''
                                                                                    }
                                                                                />
                                                                            </>
                                                                        )}
                                                                        {!hasActualPengolahanLapanganData && (
                                                                            <>
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][hasil_pengolahan]`}
                                                                                    value={
                                                                                        p.hasil_pengolahan ??
                                                                                        0
                                                                                    }
                                                                                />
                                                                                <input
                                                                                    type="hidden"
                                                                                    name={`petugas[${idx}][satuan_pengolahan]`}
                                                                                    value={
                                                                                        p.satuan_pengolahan ??
                                                                                        ''
                                                                                    }
                                                                                />
                                                                            </>
                                                                        )}
                                                                    </td>
                                                                    <td className="px-3 py-2 text-sm">
                                                                        {
                                                                            p.nomor_spk
                                                                        }
                                                                    </td>

                                                                    {/* Dynamic columns based on available data */}
                                                                    {hasActualListingData && (
                                                                        <td className="px-3 py-2 text-sm">
                                                                            <div className="flex gap-2">
                                                                                <Input
                                                                                    type="number"
                                                                                    min="0"
                                                                                    step="1"
                                                                                    name={`petugas[${idx}][hasil_listing]`}
                                                                                    defaultValue={
                                                                                        p.hasil_listing ??
                                                                                        ''
                                                                                    }
                                                                                    placeholder="Jumlah"
                                                                                    className="w-24"
                                                                                    readOnly
                                                                                />
                                                                                <Input
                                                                                    type="text"
                                                                                    name={`petugas[${idx}][satuan_listing]`}
                                                                                    defaultValue={
                                                                                        p.satuan_listing ??
                                                                                        ''
                                                                                    }
                                                                                    placeholder="Satuan"
                                                                                    className="w-28"
                                                                                    readOnly
                                                                                />
                                                                            </div>
                                                                        </td>
                                                                    )}

                                                                    {hasActualPendataanData && (
                                                                        <td className="px-3 py-2 text-sm">
                                                                            <div className="flex gap-2">
                                                                                <Input
                                                                                    type="number"
                                                                                    min="0"
                                                                                    step="1"
                                                                                    name={`petugas[${idx}][hasil_pendataan_lapangan]`}
                                                                                    defaultValue={
                                                                                        p.hasil_pendataan_lapangan ??
                                                                                        ''
                                                                                    }
                                                                                    placeholder="Jumlah"
                                                                                    className="w-24"
                                                                                    readOnly
                                                                                />
                                                                                <Input
                                                                                    type="text"
                                                                                    name={`petugas[${idx}][satuan_pendataan_lapangan]`}
                                                                                    defaultValue={
                                                                                        p.satuan_pendataan_lapangan ??
                                                                                        ''
                                                                                    }
                                                                                    placeholder="Satuan"
                                                                                    className="w-28"
                                                                                    readOnly
                                                                                />
                                                                            </div>
                                                                        </td>
                                                                    )}

                                                                    {hasActualPengolahanListingData && (
                                                                        <td className="px-3 py-2 text-sm">
                                                                            <div className="flex gap-2">
                                                                                <Input
                                                                                    type="number"
                                                                                    min="0"
                                                                                    step="1"
                                                                                    name={`petugas[${idx}][hasil_pengolahan_listing]`}
                                                                                    defaultValue={
                                                                                        p.hasil_pengolahan_listing ??
                                                                                        ''
                                                                                    }
                                                                                    placeholder="Jumlah"
                                                                                    className="w-24"
                                                                                    readOnly
                                                                                />
                                                                                <Input
                                                                                    type="text"
                                                                                    name={`petugas[${idx}][satuan_pengolahan_listing]`}
                                                                                    defaultValue={
                                                                                        p.satuan_pengolahan_listing ??
                                                                                        ''
                                                                                    }
                                                                                    placeholder="Satuan"
                                                                                    className="w-28"
                                                                                    readOnly
                                                                                />
                                                                            </div>
                                                                        </td>
                                                                    )}

                                                                    {hasActualPengolahanLapanganData && (
                                                                        <td className="px-3 py-2 text-sm">
                                                                            <div className="flex gap-2">
                                                                                <Input
                                                                                    type="number"
                                                                                    min="0"
                                                                                    step="1"
                                                                                    name={`petugas[${idx}][hasil_pengolahan]`}
                                                                                    defaultValue={
                                                                                        p.hasil_pengolahan ??
                                                                                        ''
                                                                                    }
                                                                                    placeholder="Jumlah"
                                                                                    className="w-24"
                                                                                    readOnly
                                                                                />
                                                                                <Input
                                                                                    type="text"
                                                                                    name={`petugas[${idx}][satuan_pengolahan]`}
                                                                                    defaultValue={
                                                                                        p.satuan_pengolahan ??
                                                                                        ''
                                                                                    }
                                                                                    placeholder="Satuan"
                                                                                    className="w-28"
                                                                                    readOnly
                                                                                />
                                                                            </div>
                                                                        </td>
                                                                    )}
                                                                    {/* Catatan */}
                                                                    <td className="px-3 py-2 text-sm">
                                                                        <Input
                                                                            type="text"
                                                                            name={`petugas[${idx}][catatan]`}
                                                                            defaultValue={
                                                                                p.catatan ??
                                                                                ''
                                                                            }
                                                                            placeholder="Catatan opsional"
                                                                        />
                                                                    </td>
                                                                    {/* Aksi */}
                                                                </tr>
                                                            ),
                                                        )
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {/* Actions */}
                                    <div className="flex items-center justify-end gap-2">
                                        <Link href="/bast">
                                            <Button variant="outline">
                                                Kembali
                                            </Button>
                                        </Link>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={(e) =>
                                                handlePreview(e as any, null)
                                            }
                                        >
                                            Preview PDF
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Menyimpan...'
                                                : 'Buat BAST'}
                                        </Button>
                                    </div>
                                </div>
                            );
                        }}
                    </Form>
                    {validationModalOpen && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center">
                            <div
                                className="absolute inset-0 bg-black/50"
                                onClick={() => setValidationModalOpen(false)}
                            />
                            <div className="relative z-10 w-full max-w-xl rounded bg-white p-6 shadow-lg dark:bg-neutral-800">
                                <h3 className="mb-3 text-lg font-semibold">
                                    Perbaiki input yang diperlukan
                                </h3>
                                <div className="mb-4 max-h-60 overflow-auto text-sm">
                                    <ul className="list-inside list-disc space-y-1 text-neutral-800 dark:text-neutral-200">
                                        {validationMessages.map((m, i) => (
                                            <li key={i}>{m}</li>
                                        ))}
                                    </ul>
                                </div>
                                <div className="flex justify-end">
                                    <Button
                                        variant="ghost"
                                        onClick={() =>
                                            setValidationModalOpen(false)
                                        }
                                    >
                                        Tutup
                                    </Button>
                                </div>
                            </div>
                        </div>
                    )}
                </ContentCard>
            </div>
        </AppLayout>
    );
}
