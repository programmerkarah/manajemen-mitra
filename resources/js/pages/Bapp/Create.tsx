import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle,
    Download,
    Eye,
    FileText,
    ImagePlus,
    Loader2,
    Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';

interface SpkItem {
    spk_id: number;
    spk_hashed_id: string;
    nomor_spk: string;
    nilai_kontrak: number;
    peran: string;
    jenis_pihak_kedua: string;
    petugas: {
        id: number | null;
        nama: string | null;
        nik: string;
    };
    target_sls: number;
    target_unit_sampel: Record<string, number>;
    has_bapp: boolean;
    bapp_hashed_id: string | null;
    realisasi_sls: number | null;
    realisasi_unit_sampel: Record<string, number>;
    file_path: string | null;
    fasih_screenshot_path: string | null;
    nomor_bapp_auto: string;
}

interface CreateProps {
    tahun: number;
    termin: number;
    termin_roman: string;
    bulan: number;
    bulan_label: string;
    persentase: number;
    can_generate: boolean;
    can_input_realisasi: boolean;
    tanggal_bapp: string | null;
    tanggal_min: string;
    tanggal_max: string;
    tanggal_fixed: boolean;
    spk_list: SpkItem[];
    unit_sampel_items: { id: number; nama: string }[];
    ketua_tim: { nama: string | null; nip: string | null };
    ppk: { nama: string | null; nip: string | null; jabatan: string | null };
    import_preview?: {
        preview_rows: {
            spk_id: number;
            spk_hashed_id: string;
            nomor_spk: string;
            petugas_nama: string | null;
            realisasi_sls: number | null;
            realisasi_unit_sampel: Record<string, number>;
            target_sls: number;
            target_unit_sampel: Record<string, number>;
        }[];
        unmatched_spks: string[];
    } | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAPP SE2026', href: '/bapp' },
    { title: 'Input Realisasi', href: '#' },
];

type RealisasiEntry = {
    spk_id: number;
    spk_hashed_id: string;
    realisasi_sls: string;
    realisasi_unit_sampel: Record<string, string>;
};

const peranLabel: Record<string, string> = {
    pcl_ppl: 'Petugas Lapangan',
    pml: 'Pemeriksa Lapangan',
    pcl: 'Petugas Lapangan',
    ppl: 'Petugas Lapangan',
};

function formatTargetUnitSampel(
    targetUnitSampel: Record<string, number>,
): string {
    const parts = Object.entries(targetUnitSampel)
        .filter(([, v]) => v > 0)
        .map(([k, v]) => `${v.toLocaleString('id-ID')} ${k}`);
    return parts.join(' dan ');
}

/** Returns list of unit sampel fields where realisasi < target (non-empty check). */
function getUnitBelowTarget(
    spk: SpkItem,
    entry: RealisasiEntry | undefined,
): { key: string; realisasi: number; target: number }[] {
    if (!entry) return [];
    const warnings: { key: string; realisasi: number; target: number }[] = [];
    Object.entries(spk.target_unit_sampel).forEach(([key, target]) => {
        if (target <= 0) return;
        const val = entry.realisasi_unit_sampel[key.toLowerCase()];
        if (val === undefined || val === '') return;
        const realisasi = parseInt(val, 10);
        if (!isNaN(realisasi) && realisasi < target) {
            warnings.push({ key, realisasi, target });
        }
    });
    return warnings;
}

export default function Create({
    tahun,
    termin,
    termin_roman,
    bulan_label,
    persentase,
    can_generate,
    can_input_realisasi,
    tanggal_bapp: initialTanggal,
    tanggal_min,
    tanggal_max,
    tanggal_fixed,
    spk_list,
    unit_sampel_items,
    ketua_tim,
    ppk,
    import_preview,
}: CreateProps) {
    const { flash } = usePage<SharedData>().props;
    const importRef = useRef<HTMLInputElement>(null);
    const screenshotRefs = useRef<Record<string, HTMLInputElement | null>>({});
    const [savingRealisasi, setSavingRealisasi] = useState(false);
    const [generatingAll, setGeneratingAll] = useState(false);
    const [generatingSingle, setGeneratingSingle] = useState<number | null>(
        null,
    );
    const [uploadingScreenshot, setUploadingScreenshot] = useState<
        string | null
    >(null);
    const [screenshotModalPath, setScreenshotModalPath] = useState<
        string | null
    >(null);

    // Shared date for ALL SPKs in this termin
    const [tanggalBapp, setTanggalBapp] = useState<string>(
        initialTanggal ?? '',
    );

    // Validation errors
    const [validationError, setValidationError] = useState<string | null>(null);

    // Local state for realisasi entries (no tanggal/nomor per-row)
    const [entries, setEntries] = useState<Record<number, RealisasiEntry>>(
        () => {
            const initial: Record<number, RealisasiEntry> = {};
            spk_list.forEach((spk) => {
                const unitSampel: Record<string, string> = {};
                unit_sampel_items.forEach((unit) => {
                    const key = unit.nama.toLowerCase();
                    unitSampel[key] =
                        spk.realisasi_unit_sampel[key]?.toString() ?? '';
                });
                initial[spk.spk_id] = {
                    spk_id: spk.spk_id,
                    spk_hashed_id: spk.spk_hashed_id,
                    realisasi_sls: spk.realisasi_sls?.toString() ?? '',
                    realisasi_unit_sampel: unitSampel,
                };
            });
            return initial;
        },
    );

    const updateEntry = (
        spkId: number,
        field: keyof RealisasiEntry,
        value: string,
        unitKey?: string,
    ) => {
        setEntries((prev) => {
            const entry = { ...prev[spkId] };
            if (field === 'realisasi_unit_sampel' && unitKey) {
                entry.realisasi_unit_sampel = {
                    ...entry.realisasi_unit_sampel,
                    [unitKey]: value,
                };
            } else {
                (entry as Record<string, unknown>)[field] = value;
            }
            return { ...prev, [spkId]: entry };
        });
    };

    const handleSaveRealisasi = () => {
        setValidationError(null);
        if (!tanggalBapp) {
            setValidationError('Tanggal BAPP wajib diisi sebelum menyimpan.');
            return;
        }
        const missingRealisasi = spk_list.filter((spk) => {
            const e = entries[spk.spk_id];
            if (!e || e.realisasi_sls === '') return true;
            return unit_sampel_items.some((u) => {
                const key = u.nama.toLowerCase();
                const val = e.realisasi_unit_sampel[key] ?? '';
                return val === '';
            });
        });
        if (missingRealisasi.length > 0) {
            setValidationError(
                `Semua realisasi (SLS, keluarga, usaha) wajib diisi untuk ${missingRealisasi.length} SPK.`,
            );
            return;
        }
        const belowTargetSpks = spk_list.flatMap((spk) => {
            const below = getUnitBelowTarget(spk, entries[spk.spk_id]);
            return below.map(
                (b) =>
                    `${spk.petugas.nama ?? spk.nomor_spk}: ${b.key} ${b.realisasi} < target ${b.target}`,
            );
        });
        if (belowTargetSpks.length > 0) {
            setValidationError(
                `Realisasi keluarga/usaha tidak boleh di bawah target:\n• ${belowTargetSpks.slice(0, 5).join('\n• ')}`,
            );
            return;
        }
        setSavingRealisasi(true);
        const payload = {
            termin,
            tanggal_bapp: tanggalBapp,
            entries: Object.values(entries).map((e) => ({
                spk_hashed_id: e.spk_hashed_id,
                realisasi_sls: e.realisasi_sls,
                realisasi_unit_sampel: e.realisasi_unit_sampel,
            })),
        };
        router.post('/bapp/realisasi', payload, {
            onFinish: () => setSavingRealisasi(false),
            preserveScroll: true,
        });
    };

    const handleGenerateAll = () => {
        setValidationError(null);
        if (!tanggalBapp) {
            setValidationError('Tanggal BAPP wajib diisi sebelum generate.');
            return;
        }
        const missingRealisasi = spk_list.filter((spk) => {
            const e = entries[spk.spk_id];
            if (!e || e.realisasi_sls === '') return true;
            return unit_sampel_items.some((u) => {
                const key = u.nama.toLowerCase();
                const val = e.realisasi_unit_sampel[key] ?? '';
                return val === '';
            });
        });
        if (missingRealisasi.length > 0) {
            setValidationError(
                `Semua realisasi (SLS, keluarga, usaha) wajib diisi untuk ${missingRealisasi.length} SPK.`,
            );
            return;
        }
        const belowTargetSpks = spk_list.flatMap((spk) => {
            const below = getUnitBelowTarget(spk, entries[spk.spk_id]);
            return below.map(
                (b) =>
                    `${spk.petugas.nama ?? spk.nomor_spk}: ${b.key} ${b.realisasi} < target ${b.target}`,
            );
        });
        if (belowTargetSpks.length > 0) {
            setValidationError(
                `Realisasi keluarga/usaha tidak boleh di bawah target:\n• ${belowTargetSpks.slice(0, 5).join('\n• ')}`,
            );
            return;
        }
        setGeneratingAll(true);
        router.post(
            '/bapp/generate-batch',
            { termin },
            {
                onFinish: () => setGeneratingAll(false),
                preserveScroll: true,
            },
        );
    };

    const handleGenerateSingle = (spkId: number) => {
        setValidationError(null);
        const entry = entries[spkId];
        if (!tanggalBapp) {
            setValidationError('Tanggal BAPP wajib diisi sebelum generate.');
            return;
        }
        if (!entry || entry.realisasi_sls === '') {
            setValidationError('Realisasi SLS wajib diisi sebelum generate.');
            return;
        }
        const missingUnit = unit_sampel_items.some((u) => {
            const val = entry.realisasi_unit_sampel[u.nama.toLowerCase()] ?? '';
            return val === '';
        });
        if (missingUnit) {
            setValidationError(
                'Semua realisasi (keluarga, usaha) wajib diisi sebelum generate.',
            );
            return;
        }
        const spk = spk_list.find((s) => s.spk_id === spkId);
        if (spk) {
            const below = getUnitBelowTarget(spk, entry);
            if (below.length > 0) {
                setValidationError(
                    `Realisasi tidak boleh di bawah target: ${below.map((b) => `${b.key} ${b.realisasi} < ${b.target}`).join(', ')}.`,
                );
                return;
            }
        }
        setGeneratingSingle(spkId);
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/bapp/generate';
        form.target = '_blank';

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        const inputs: [string, string][] = [
            ['_token', csrfToken ?? ''],
            [
                'spk_hashed_id',
                spk_list.find((s) => s.spk_id === spkId)?.spk_hashed_id ?? '',
            ],
            ['termin', termin.toString()],
        ];

        inputs.forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        setTimeout(() => setGeneratingSingle(null), 2000);
    };

    const handleImport = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('termin', termin.toString());

        router.post('/bapp/import', formData, {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => {
                if (importRef.current) {
                    importRef.current.value = '';
                }
            },
        });
    };

    const applyImportPreview = () => {
        if (!import_preview) return;
        setEntries((prev) => {
            const next = { ...prev };
            import_preview.preview_rows.forEach((row) => {
                const existing = next[row.spk_id];
                if (!existing) return;
                const unitSampel = { ...existing.realisasi_unit_sampel };
                Object.entries(row.realisasi_unit_sampel).forEach(
                    ([key, val]) => {
                        unitSampel[key] = val.toString();
                    },
                );
                next[row.spk_id] = {
                    ...existing,
                    realisasi_sls:
                        row.realisasi_sls?.toString() ?? existing.realisasi_sls,
                    realisasi_unit_sampel: unitSampel,
                };
            });
            return next;
        });
    };

    const handleUploadScreenshot = (
        bappHashedId: string,
        e: React.ChangeEvent<HTMLInputElement>,
    ) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('screenshot', file);

        setUploadingScreenshot(bappHashedId);
        router.post(`/bapp/${bappHashedId}/upload-screenshot`, formData, {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => {
                setUploadingScreenshot(null);
                const ref = screenshotRefs.current[bappHashedId];
                if (ref) ref.value = '';
            },
        });
    };

    const nilaiTermin = (nilaiKontrak: number) =>
        new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format((nilaiKontrak * persentase) / 100);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`BAPP Termin ${termin_roman} - Input Realisasi`} />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title={`BAPP Termin ${termin_roman} — SE2026`}
                    description={`Input realisasi pekerjaan termin ${termin_roman} (${bulan_label} ${tahun}) — Target ${persentase}%`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/bapp">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Flash messages */}
                {(flash as { success?: string })?.success && (
                    <div className="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800/40 dark:bg-green-900/20 dark:text-green-300">
                        <CheckCircle className="h-4 w-4 shrink-0" />
                        {(flash as { success: string }).success}
                    </div>
                )}
                {(flash as { error?: string })?.error && (
                    <div className="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800/40 dark:bg-red-900/20 dark:text-red-300">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        {(flash as { error: string }).error}
                    </div>
                )}

                {/* Import preview — shown after Excel import, before applying to inputs */}
                {import_preview && import_preview.preview_rows.length > 0 && (
                    <ContentCard>
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <h3 className="flex items-center gap-2 font-semibold">
                                <Eye className="h-4 w-4 text-blue-500" />
                                Preview Data Hasil Import
                            </h3>
                            <Button size="sm" onClick={applyImportPreview}>
                                <CheckCircle className="mr-2 h-4 w-4" />
                                Terapkan ke Input
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs text-neutral-500 dark:border-neutral-700">
                                        <th className="pr-4 pb-2 font-medium">
                                            Petugas
                                        </th>
                                        <th className="pr-4 pb-2 font-medium">
                                            SPK
                                        </th>
                                        <th className="pr-4 pb-2 text-right font-medium">
                                            Rls SLS
                                        </th>
                                        <th className="pr-4 pb-2 text-right font-medium">
                                            Target SLS
                                        </th>
                                        {unit_sampel_items.map((u) => (
                                            <th
                                                key={u.id}
                                                className="pr-4 pb-2 text-right font-medium capitalize"
                                            >
                                                Rls {u.nama}
                                            </th>
                                        ))}
                                        {unit_sampel_items.map((u) => (
                                            <th
                                                key={`t-${u.id}`}
                                                className="pr-4 pb-2 text-right font-medium capitalize"
                                            >
                                                Target {u.nama}
                                            </th>
                                        ))}
                                        <th className="pb-2 font-medium">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {import_preview.preview_rows.map((row) => {
                                        const hasBelow = unit_sampel_items.some(
                                            (u) => {
                                                const key =
                                                    u.nama.toLowerCase();
                                                const val =
                                                    row.realisasi_unit_sampel[
                                                        key
                                                    ];
                                                const target =
                                                    row.target_unit_sampel[
                                                        key
                                                    ] ?? 0;
                                                return (
                                                    val !== undefined &&
                                                    target > 0 &&
                                                    val < target
                                                );
                                            },
                                        );
                                        return (
                                            <tr
                                                key={row.spk_id}
                                                className="border-b last:border-0 dark:border-neutral-700"
                                            >
                                                <td className="py-2 pr-4">
                                                    {row.petugas_nama ?? '—'}
                                                </td>
                                                <td className="py-2 pr-4 font-mono text-xs">
                                                    {row.nomor_spk}
                                                </td>
                                                <td className="py-2 pr-4 text-right">
                                                    {row.realisasi_sls ?? (
                                                        <span className="text-neutral-400">
                                                            —
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-2 pr-4 text-right text-neutral-500">
                                                    {row.target_sls}
                                                </td>
                                                {unit_sampel_items.map((u) => {
                                                    const key =
                                                        u.nama.toLowerCase();
                                                    const val =
                                                        row
                                                            .realisasi_unit_sampel[
                                                            key
                                                        ];
                                                    const target =
                                                        row.target_unit_sampel[
                                                            key
                                                        ] ?? 0;
                                                    const isLow =
                                                        val !== undefined &&
                                                        target > 0 &&
                                                        val < target;
                                                    return (
                                                        <td
                                                            key={u.id}
                                                            className={`py-2 pr-4 text-right ${isLow ? 'font-semibold text-orange-600 dark:text-orange-400' : ''}`}
                                                        >
                                                            {val !==
                                                            undefined ? (
                                                                val
                                                            ) : (
                                                                <span className="text-neutral-400">
                                                                    —
                                                                </span>
                                                            )}
                                                        </td>
                                                    );
                                                })}
                                                {unit_sampel_items.map((u) => (
                                                    <td
                                                        key={`t-${u.id}`}
                                                        className="py-2 pr-4 text-right text-neutral-500"
                                                    >
                                                        {row.target_unit_sampel[
                                                            u.nama.toLowerCase()
                                                        ] ?? 0}
                                                    </td>
                                                ))}
                                                <td className="py-2">
                                                    {hasBelow ? (
                                                        <span className="inline-flex items-center gap-1 text-xs text-orange-600 dark:text-orange-400">
                                                            <AlertCircle className="h-3 w-3" />
                                                            Di bawah target
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                                            <CheckCircle className="h-3 w-3" />
                                                            OK
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                        {import_preview.unmatched_spks.length > 0 && (
                            <div className="mt-3 flex items-start gap-2 rounded-lg border border-orange-200 bg-orange-50 p-3 text-sm text-orange-800 dark:border-orange-800/40 dark:bg-orange-900/20 dark:text-orange-300">
                                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                <span>
                                    SPK tidak ditemukan:{' '}
                                    {import_preview.unmatched_spks
                                        .slice(0, 5)
                                        .join(', ')}
                                    {import_preview.unmatched_spks.length > 5 &&
                                        ` dan ${import_preview.unmatched_spks.length - 5} lainnya`}
                                    .
                                </span>
                            </div>
                        )}
                        <p className="mt-3 text-xs text-neutral-500">
                            Klik <strong>Terapkan ke Input</strong> untuk
                            memasukkan data ke form di bawah, lalu klik{' '}
                            <strong>Simpan Realisasi</strong>.
                        </p>
                    </ContentCard>
                )}

                {/* Validation errors */}
                {validationError && (
                    <div className="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800/40 dark:bg-red-900/20 dark:text-red-300">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        {validationError}
                    </div>
                )}

                {!can_generate && (
                    <div className="flex items-center gap-2 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-800/40 dark:bg-yellow-900/20 dark:text-yellow-300">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        <span>
                            Generate BAPP Termin {termin_roman} baru tersedia
                            mulai bulan {bulan_label}. Anda masih dapat
                            menginput dan menyimpan data realisasi.
                        </span>
                    </div>
                )}

                {!can_input_realisasi && (
                    <div className="flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800 dark:border-orange-800/40 dark:bg-orange-900/20 dark:text-orange-300">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        <span>
                            Input realisasi BAPP Termin {termin_roman} baru
                            tersedia mulai tanggal{' '}
                            {new Date(tanggal_min).toLocaleDateString('id-ID', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                            })}
                            .
                        </span>
                    </div>
                )}

                {/* Info + shared BAPP fields */}
                <ContentCard>
                    <div className="grid gap-6 md:grid-cols-2">
                        <div>
                            <h3 className="mb-2 font-semibold">
                                Informasi Termin
                            </h3>
                            <dl className="space-y-1 text-sm">
                                <div className="flex gap-2">
                                    <dt className="w-28 shrink-0 text-neutral-500">
                                        Termin
                                    </dt>
                                    <dd className="font-medium">
                                        Termin {termin_roman} ({persentase}%)
                                    </dd>
                                </div>
                                <div className="flex gap-2">
                                    <dt className="w-28 shrink-0 text-neutral-500">
                                        Bulan Generate
                                    </dt>
                                    <dd>
                                        {bulan_label} {tahun}
                                    </dd>
                                </div>
                                <div className="flex gap-2">
                                    <dt className="w-28 shrink-0 text-neutral-500">
                                        Ketua Tim
                                    </dt>
                                    <dd>{ketua_tim.nama ?? '—'}</dd>
                                </div>
                                <div className="flex gap-2">
                                    <dt className="w-28 shrink-0 text-neutral-500">
                                        PPK
                                    </dt>
                                    <dd>{ppk.nama ?? '—'}</dd>
                                </div>
                            </dl>
                        </div>

                        {/* Shared BAPP date (apply to all SPKs) */}
                        <div>
                            <h3 className="mb-3 font-semibold">Tanggal BAPP</h3>
                            <p className="mb-3 text-xs text-neutral-500">
                                Berlaku untuk semua BAPP dalam termin ini. Nomor
                                BAPP di-generate otomatis urut abjad petugas.
                            </p>
                            <div className="flex flex-col gap-1">
                                <Label htmlFor="tanggal-bapp-shared">
                                    Tanggal BAPP{' '}
                                    <span className="text-red-500">*</span>
                                    {tanggal_fixed && (
                                        <span className="ml-1 text-xs text-neutral-400">
                                            (otomatis 31 Agustus)
                                        </span>
                                    )}
                                </Label>
                                <DatePicker
                                    id="tanggal-bapp-shared"
                                    value={tanggalBapp}
                                    onChange={(v) => {
                                        setTanggalBapp(v);
                                        setValidationError(null);
                                    }}
                                    min={tanggal_min}
                                    max={tanggal_max}
                                    disabled={
                                        tanggal_fixed || !can_input_realisasi
                                    }
                                    placeholder="Pilih tanggal BAPP"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Aksi Excel */}
                    <div className="mt-4 flex flex-wrap gap-2 border-t pt-4 dark:border-neutral-700">
                        {can_input_realisasi ? (
                            <Button variant="outline" size="sm" asChild>
                                <a href="/bapp/template" download>
                                    <Download className="mr-2 h-4 w-4" />
                                    Template Excel
                                </a>
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                size="sm"
                                disabled
                                title={`Tersedia mulai ${new Date(tanggal_min).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`}
                            >
                                <Download className="mr-2 h-4 w-4" />
                                Template Excel
                            </Button>
                        )}
                        <div>
                            <input
                                ref={importRef}
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                className="hidden"
                                onChange={handleImport}
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!can_input_realisasi}
                                title={
                                    !can_input_realisasi
                                        ? `Tersedia mulai ${new Date(tanggal_min).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`
                                        : undefined
                                }
                                onClick={() => importRef.current?.click()}
                            >
                                <Upload className="mr-2 h-4 w-4" />
                                Import Excel
                            </Button>
                        </div>
                    </div>
                </ContentCard>

                {/* SPK list */}
                {spk_list.length === 0 ? (
                    <ContentCard>
                        <div className="flex flex-col items-center gap-3 py-10 text-center">
                            <FileText className="h-10 w-10 text-neutral-400" />
                            <p className="text-neutral-500">
                                Tidak ada SPK Sensus Ekonomi 2026 yang ditemukan
                                untuk tahun {tahun}.
                            </p>
                        </div>
                    </ContentCard>
                ) : (
                    <div className="flex flex-col gap-4">
                        {spk_list.map((spk) => {
                            const entry = entries[spk.spk_id];
                            const unitLabel = formatTargetUnitSampel(
                                spk.target_unit_sampel,
                            );
                            return (
                                <ContentCard key={spk.spk_id}>
                                    <div className="flex flex-col gap-4">
                                        {/* Header */}
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <div className="font-semibold">
                                                    {spk.nomor_spk}
                                                </div>
                                                <div className="text-sm text-neutral-500">
                                                    {spk.petugas.nama ?? '—'}
                                                    {spk.petugas.nik
                                                        ? ` — NIK: ${spk.petugas.nik}`
                                                        : ''}
                                                </div>
                                                <div className="mt-1 text-xs text-neutral-400">
                                                    {peranLabel[spk.peran] ??
                                                        spk.peran}
                                                    {' · '}
                                                    Nilai Termin:{' '}
                                                    {nilaiTermin(
                                                        spk.nilai_kontrak,
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {spk.has_bapp &&
                                                spk.file_path ? (
                                                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                        <CheckCircle className="mr-1 h-3 w-3" />
                                                        PDF Tersedia
                                                    </Badge>
                                                ) : spk.has_bapp ? (
                                                    <Badge variant="secondary">
                                                        Data Tersimpan
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Belum Ada
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>

                                        {/* Target info */}
                                        <div className="rounded-lg bg-blue-50 p-3 text-sm dark:bg-blue-900/20">
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <span className="font-medium text-blue-700 dark:text-blue-300">
                                                        Target Termin{' '}
                                                        {termin_roman} (
                                                        {persentase}%):
                                                    </span>{' '}
                                                    <span className="text-blue-800 dark:text-blue-200">
                                                        {spk.target_sls.toLocaleString(
                                                            'id-ID',
                                                        )}{' '}
                                                        SLS/Sub-SLS
                                                        {unitLabel ? (
                                                            <>
                                                                {' '}
                                                                dan/atau{' '}
                                                                {unitLabel}
                                                            </>
                                                        ) : null}
                                                    </span>
                                                </div>
                                                <div className="shrink-0 text-xs text-blue-600 dark:text-blue-400">
                                                    <span className="font-mono">
                                                        {spk.nomor_bapp_auto}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Realisasi inputs */}
                                        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                            <div className="flex flex-col gap-1">
                                                <Label
                                                    htmlFor={`sls-${spk.spk_id}`}
                                                >
                                                    Realisasi SLS{' '}
                                                    <span className="text-red-500">
                                                        *
                                                    </span>
                                                </Label>
                                                <Input
                                                    id={`sls-${spk.spk_id}`}
                                                    type="number"
                                                    min={0}
                                                    placeholder="Jumlah SLS"
                                                    value={
                                                        entry?.realisasi_sls ??
                                                        ''
                                                    }
                                                    disabled={
                                                        !can_input_realisasi
                                                    }
                                                    onChange={(e) =>
                                                        updateEntry(
                                                            spk.spk_id,
                                                            'realisasi_sls',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                            </div>
                                            {unit_sampel_items.map((unit) => {
                                                const unitKey =
                                                    unit.nama.toLowerCase();
                                                const rawVal =
                                                    entry
                                                        ?.realisasi_unit_sampel[
                                                        unitKey
                                                    ] ?? '';
                                                const targetVal =
                                                    spk.target_unit_sampel[
                                                        unitKey
                                                    ] ?? 0;
                                                const isBelowTarget =
                                                    rawVal !== '' &&
                                                    targetVal > 0 &&
                                                    parseInt(rawVal, 10) <
                                                        targetVal;
                                                return (
                                                    <div
                                                        key={unit.id}
                                                        className="flex flex-col gap-1"
                                                    >
                                                        <Label
                                                            htmlFor={`unit-${spk.spk_id}-${unit.id}`}
                                                        >
                                                            Realisasi{' '}
                                                            {unit.nama}{' '}
                                                            <span className="text-red-500">
                                                                *
                                                            </span>
                                                        </Label>
                                                        <Input
                                                            id={`unit-${spk.spk_id}-${unit.id}`}
                                                            type="number"
                                                            min={0}
                                                            placeholder={`Jumlah ${unit.nama}`}
                                                            value={rawVal}
                                                            disabled={
                                                                !can_input_realisasi
                                                            }
                                                            className={
                                                                isBelowTarget
                                                                    ? 'border-orange-400 focus-visible:ring-orange-400'
                                                                    : ''
                                                            }
                                                            onChange={(e) =>
                                                                updateEntry(
                                                                    spk.spk_id,
                                                                    'realisasi_unit_sampel',
                                                                    e.target
                                                                        .value,
                                                                    unitKey,
                                                                )
                                                            }
                                                        />
                                                        {isBelowTarget && (
                                                            <p className="text-xs text-orange-600 dark:text-orange-400">
                                                                Di bawah target
                                                                (
                                                                {targetVal.toLocaleString(
                                                                    'id-ID',
                                                                )}
                                                                )
                                                            </p>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        {/* Actions per SPK */}
                                        {(spk.has_bapp || can_generate) && (
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                {/* Screenshot upload */}
                                                {spk.has_bapp &&
                                                    spk.bapp_hashed_id && (
                                                        <div className="flex items-center gap-3">
                                                            <input
                                                                type="file"
                                                                accept="image/*"
                                                                className="hidden"
                                                                ref={(el) => {
                                                                    screenshotRefs.current[
                                                                        spk.bapp_hashed_id!
                                                                    ] = el;
                                                                }}
                                                                onChange={(e) =>
                                                                    handleUploadScreenshot(
                                                                        spk.bapp_hashed_id!,
                                                                        e,
                                                                    )
                                                                }
                                                            />
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    screenshotRefs.current[
                                                                        spk
                                                                            .bapp_hashed_id!
                                                                    ]?.click()
                                                                }
                                                                disabled={
                                                                    uploadingScreenshot ===
                                                                        spk.bapp_hashed_id ||
                                                                    !can_input_realisasi
                                                                }
                                                                title={
                                                                    !can_input_realisasi
                                                                        ? `Tersedia mulai ${new Date(tanggal_min).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`
                                                                        : undefined
                                                                }
                                                            >
                                                                {uploadingScreenshot ===
                                                                spk.bapp_hashed_id ? (
                                                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                                ) : (
                                                                    <ImagePlus className="mr-2 h-4 w-4" />
                                                                )}
                                                                {spk.fasih_screenshot_path
                                                                    ? 'Ganti Screenshot'
                                                                    : 'Upload Screenshot Fasih'}
                                                            </Button>
                                                            {spk.fasih_screenshot_path && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    disabled={
                                                                        !can_input_realisasi
                                                                    }
                                                                    title={
                                                                        !can_input_realisasi
                                                                            ? `Tersedia mulai ${new Date(tanggal_min).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`
                                                                            : undefined
                                                                    }
                                                                    onClick={() =>
                                                                        setScreenshotModalPath(
                                                                            `/storage/${spk.fasih_screenshot_path}`,
                                                                        )
                                                                    }
                                                                >
                                                                    <Eye className="mr-1 h-4 w-4" />
                                                                    Lihat
                                                                    Screenshot
                                                                </Button>
                                                            )}
                                                        </div>
                                                    )}
                                                {!spk.has_bapp && <div />}

                                                <div className="flex flex-wrap gap-2">
                                                    {spk.has_bapp &&
                                                        spk.bapp_hashed_id &&
                                                        spk.fasih_screenshot_path &&
                                                        spk.realisasi_sls !==
                                                            null &&
                                                        Object.keys(
                                                            spk.realisasi_unit_sampel,
                                                        ).length > 0 &&
                                                        Object.values(
                                                            spk.realisasi_unit_sampel,
                                                        ).every(
                                                            (v) => v > 0,
                                                        ) && (
                                                            <>
                                                                {can_input_realisasi ? (
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        asChild
                                                                    >
                                                                        <a
                                                                            href={`/bapp/${spk.bapp_hashed_id}/preview`}
                                                                            target="_blank"
                                                                            rel="noreferrer"
                                                                        >
                                                                            <Eye className="mr-2 h-4 w-4" />
                                                                            Preview
                                                                            BAPP
                                                                        </a>
                                                                    </Button>
                                                                ) : (
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        disabled
                                                                        title={`Tersedia mulai ${new Date(tanggal_min).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`}
                                                                    >
                                                                        <Eye className="mr-2 h-4 w-4" />
                                                                        Preview
                                                                        BAPP
                                                                    </Button>
                                                                )}
                                                                {can_input_realisasi ? (
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        asChild
                                                                    >
                                                                        <a
                                                                            href={`/bapp/${spk.bapp_hashed_id}/download`}
                                                                        >
                                                                            <Download className="mr-2 h-4 w-4" />
                                                                            Unduh
                                                                            BAPP
                                                                        </a>
                                                                    </Button>
                                                                ) : (
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        disabled
                                                                        title={`Tersedia mulai ${new Date(tanggal_min).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}`}
                                                                    >
                                                                        <Download className="mr-2 h-4 w-4" />
                                                                        Unduh
                                                                        BAPP
                                                                    </Button>
                                                                )}
                                                            </>
                                                        )}
                                                    {can_generate && (
                                                        <Button
                                                            variant={
                                                                spk.has_bapp
                                                                    ? 'secondary'
                                                                    : 'default'
                                                            }
                                                            size="sm"
                                                            onClick={() =>
                                                                handleGenerateSingle(
                                                                    spk.spk_id,
                                                                )
                                                            }
                                                            disabled={
                                                                generatingSingle ===
                                                                spk.spk_id
                                                            }
                                                        >
                                                            {generatingSingle ===
                                                            spk.spk_id ? (
                                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                            ) : (
                                                                <FileText className="mr-2 h-4 w-4" />
                                                            )}
                                                            {spk.has_bapp
                                                                ? 'Generate Ulang'
                                                                : 'Generate BAPP'}
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </ContentCard>
                            );
                        })}
                    </div>
                )}

                {/* Bottom actions */}
                {spk_list.length > 0 && (
                    <div className="flex flex-wrap justify-end gap-3">
                        <Button
                            variant="outline"
                            onClick={handleSaveRealisasi}
                            disabled={savingRealisasi || !can_input_realisasi}
                        >
                            {savingRealisasi && (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            Simpan Realisasi
                        </Button>
                        {can_generate && (
                            <Button
                                onClick={handleGenerateAll}
                                disabled={generatingAll}
                            >
                                {generatingAll ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <FileText className="mr-2 h-4 w-4" />
                                )}
                                Generate Semua BAPP
                            </Button>
                        )}
                    </div>
                )}
            </div>

            {/* Screenshot Preview Modal */}
            <Dialog
                open={screenshotModalPath !== null}
                onOpenChange={(open) => {
                    if (!open) setScreenshotModalPath(null);
                }}
            >
                <DialogContent className="max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Preview Screenshot Fasih</DialogTitle>
                    </DialogHeader>
                    {screenshotModalPath && (
                        <img
                            src={screenshotModalPath}
                            alt="Screenshot Aplikasi Fasih"
                            className="max-h-[70vh] w-full rounded object-contain"
                        />
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
