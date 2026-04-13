import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Info, Send } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengajuan Pulsa', href: '/pengajuan-pulsa' },
    { title: 'Ajukan', href: '/pengajuan-pulsa/create' },
];

interface KegiatanItem {
    id: number;
    kode_kegiatan: string;
    nama_kegiatan: string;
    metode_pendataan_pencacahan: 'PAPI' | 'CAPI' | null;
    metode_pendataan_listing: 'PAPI' | 'CAPI' | null;
    metode_pelatihan:
        | 'daring'
        | 'luring'
        | 'hybrid'
        | 'tidak_ada_pelatihan'
        | null;
    bulan_pelatihan: number | null;
    has_listing_updating: boolean;
}

interface PetugasItem {
    id: number;
    nama: string;
    peran: string;
}

interface Props {
    eligibleKegiatan: KegiatanItem[];
    /** Petugas eligible for pendataan pulsa: sourced from current bulan allocations */
    petugasPerKegiatan: Record<number, PetugasItem[]>;
    /** Petugas eligible for pelatihan pulsa: sourced from bulan_pelatihan+1 allocations (or bulan_pelatihan if kegiatan starts that month) */
    petugasPerKegiatanPelatihan: Record<number, PetugasItem[]>;
    /** Total existing submissions from kegiatan managed by this ketua tim */
    existingTotals: Record<number, number>;
    /** Total existing submissions across ALL kegiatan (global) */
    allExistingTotals: Record<number, number>;
    /** key = "${kegiatan_id}_${petugas_id}_${jenis_pulsa}" → nominal already submitted */
    existingPerKegiatan: Record<string, number>;
    filters: { bulan: string; tahun: string };
}

/** Ordered array to avoid JS integer-key reordering in Object.entries */
const BULAN_LIST: Array<[string, string]> = [
    ['01', 'Januari'],
    ['02', 'Februari'],
    ['03', 'Maret'],
    ['04', 'April'],
    ['05', 'Mei'],
    ['06', 'Juni'],
    ['07', 'Juli'],
    ['08', 'Agustus'],
    ['09', 'September'],
    ['10', 'Oktober'],
    ['11', 'November'],
    ['12', 'Desember'],
];
const BULAN_LABELS: Record<string, string> = Object.fromEntries(BULAN_LIST);

const MAX_PULSA_PER_PETUGAS = 100000;

/** Format raw number to Indonesian thousand-separated string, e.g. 35000 → "35.000" */
const formatThousands = (value: number): string =>
    value > 0 ? value.toLocaleString('id-ID') : '';

/** Parse Indonesian thousand-separated string back to raw number */
const parseRaw = (str: string): number => {
    const num = parseInt(str.replace(/\./g, '').replace(/[^\d]/g, ''), 10);
    return isNaN(num) ? 0 : num;
};

const PERAN_LABELS: Record<string, string> = {
    pml: 'Petugas Pemeriksaan',
    pcl_ppl: 'Petugas Pendataan',
    pcl: 'Petugas Pendataan',
    ppl: 'Petugas Pendataan',
    lapangan: 'Petugas Pendataan',
};

const peranLabel = (peran: string): string =>
    PERAN_LABELS[peran] ?? 'Petugas Pendataan';

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);

/** State for nominal inputs: key = `${kegiatanId}__${petugasId}__${jenisPulsa}` */
type NominalMap = Record<string, number>;

export default function PengajuanPulsaCreate({
    eligibleKegiatan,
    petugasPerKegiatan,
    petugasPerKegiatanPelatihan,
    existingTotals,
    allExistingTotals,
    existingPerKegiatan,
    filters,
}: Props) {
    const [bulan, setBulan] = useState(filters.bulan);
    const tahun = filters.tahun;
    const [catatan, setCatatan] = useState('');
    const [nominals, setNominals] = useState<NominalMap>({});
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const getNominal = useCallback(
        (
            kegiatanId: number,
            petugasId: number,
            jenisPulsa: 'pelatihan' | 'pendataan',
        ) => nominals[`${kegiatanId}__${petugasId}__${jenisPulsa}`] ?? 0,
        [nominals],
    );

    const setNominal = useCallback(
        (
            kegiatanId: number,
            petugasId: number,
            jenisPulsa: 'pelatihan' | 'pendataan',
            value: number,
        ) => {
            setNominals((prev) => ({
                ...prev,
                [`${kegiatanId}__${petugasId}__${jenisPulsa}`]: value,
            }));
        },
        [],
    );

    /** Compute new total per petugas from the current form state */
    const newTotalPerPetugas = useMemo<Record<number, number>>(() => {
        const totals: Record<number, number> = {};
        for (const key of Object.keys(nominals)) {
            const [, petugasIdStr] = key.split('__');
            const petugasId = Number(petugasIdStr);
            totals[petugasId] = (totals[petugasId] ?? 0) + (nominals[key] ?? 0);
        }
        return totals;
    }, [nominals]);

    const totalForPetugas = useCallback(
        (petugasId: number) => {
            const existing = existingTotals[petugasId] ?? 0;
            const newTotal = newTotalPerPetugas[petugasId] ?? 0;
            return existing + newTotal;
        },
        [existingTotals, newTotalPerPetugas],
    );

    const isOverLimit = useCallback(
        (petugasId: number) =>
            totalForPetugas(petugasId) > MAX_PULSA_PER_PETUGAS,
        [totalForPetugas],
    );

    /**
     * Invert petugasPerKegiatan + petugasPerKegiatanPelatihan → list of unique petugas
     * each with their kegiatan list, sorted by petugas name.
     * A petugas card appears if they are eligible for either pendataan or pelatihan pulsa.
     */
    const petugasWithKegiatan = useMemo(() => {
        const map = new Map<
            number,
            { petugas: PetugasItem; kegiatan: KegiatanItem[] }
        >();

        const addPetugas = (p: PetugasItem, kegiatan: KegiatanItem) => {
            if (!map.has(p.id)) {
                map.set(p.id, { petugas: p, kegiatan: [] });
            }
            if (!map.get(p.id)!.kegiatan.some((k) => k.id === kegiatan.id)) {
                map.get(p.id)!.kegiatan.push(kegiatan);
            }
        };

        for (const kegiatan of eligibleKegiatan) {
            for (const p of petugasPerKegiatan[kegiatan.id] ?? []) {
                addPetugas(p, kegiatan);
            }
            for (const p of petugasPerKegiatanPelatihan[kegiatan.id] ?? []) {
                addPetugas(p, kegiatan);
            }
        }
        return Array.from(map.values()).sort((a, b) =>
            a.petugas.nama.localeCompare(b.petugas.nama, 'id'),
        );
    }, [eligibleKegiatan, petugasPerKegiatan, petugasPerKegiatanPelatihan]);

    const handleFilterChange = (newBulan: string) => {
        router.get(
            '/pengajuan-pulsa/create',
            { bulan: newBulan },
            { preserveState: false },
        );
    };

    const handleSubmit = () => {
        const overLimitPetugas = Object.keys(newTotalPerPetugas).filter(
            (id) => totalForPetugas(Number(id)) > MAX_PULSA_PER_PETUGAS,
        );

        // Validate multiples of 1000
        const invalidNominals = Object.values(nominals).filter(
            (v) => v > 0 && v % 1000 !== 0,
        );
        if (invalidNominals.length > 0) {
            setErrors({
                general:
                    'Semua nominal pulsa harus merupakan kelipatan Rp1.000.',
            });
            return;
        }

        if (overLimitPetugas.length > 0) {
            setErrors({
                general: `Beberapa petugas melebihi batas pulsa Rp100.000 per bulan. Sesuaikan nominal sebelum mengirim.`,
            });
            return;
        }

        const items: Array<{
            kegiatan_id: number;
            petugas_id: number;
            jenis_pulsa: string;
            nominal: number;
        }> = [];

        for (const key of Object.keys(nominals)) {
            const nominal = nominals[key] ?? 0;
            if (nominal <= 0) continue;
            const [kegiatanIdStr, petugasIdStr, jenisPulsa] = key.split('__');
            items.push({
                kegiatan_id: Number(kegiatanIdStr),
                petugas_id: Number(petugasIdStr),
                jenis_pulsa: jenisPulsa,
                nominal,
            });
        }

        if (items.length === 0) {
            setErrors({
                general: 'Isi minimal satu nominal pulsa sebelum mengirim.',
            });
            return;
        }

        setErrors({});
        setIsSubmitting(true);

        router.post(
            '/pengajuan-pulsa',
            { bulan, tahun, catatan, items },
            {
                onError: (errs) => {
                    setErrors(errs as Record<string, string>);
                    setIsSubmitting(false);
                },
                onFinish: () => setIsSubmitting(false),
            },
        );
    };

    const hasEligibleKegiatan = petugasWithKegiatan.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ajukan Pulsa" />
            <div className="space-y-4">
                <PageHeader
                    title="Ajukan Pulsa"
                    description="Pengajuan pulsa petugas untuk pelatihan dan/atau pendataan. Pastikan nominal yang diajukan sudah benar sebelum mengirim."
                >
                    <Button variant="outline" asChild className="gap-2">
                        <Link href="/pengajuan-pulsa">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {/* Periode Filter */}
                <ContentCard>
                    <h3 className="mb-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                        Periode
                    </h3>
                    <div className="flex flex-wrap gap-4">
                        <div className="space-y-1.5">
                            <Label>Bulan</Label>
                            <Select
                                value={bulan}
                                onValueChange={(v) => {
                                    setBulan(v);
                                    handleFilterChange(v);
                                }}
                            >
                                <SelectTrigger className="w-40">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {BULAN_LIST.map(([val, label]) => (
                                        <SelectItem key={val} value={val}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </ContentCard>

                {!hasEligibleKegiatan ? (
                    <ContentCard>
                        <div className="flex flex-col items-center gap-2 py-8 text-center">
                            <AlertTriangle className="h-10 w-10 text-amber-500" />
                            <p className="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                Tidak ada kegiatan dengan alokasi petugas untuk
                                periode ini
                            </p>
                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                Belum ada petugas non-organik yang dialokasikan
                                pada {BULAN_LABELS[bulan]} {tahun}.
                            </p>
                        </div>
                    </ContentCard>
                ) : (
                    <>
                        {errors.general && (
                            <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                                {errors.general}
                            </div>
                        )}

                        {/* Per-petugas cards */}
                        {petugasWithKegiatan.map(
                            ({ petugas, kegiatan: kegiatanList }) => {
                                const total = totalForPetugas(petugas.id);
                                const over = isOverLimit(petugas.id);

                                return (
                                    <ContentCard
                                        key={petugas.id}
                                        padding="none"
                                    >
                                        {/* Card header */}
                                        <div
                                            className={`flex items-center justify-between px-6 py-4 ${over ? 'bg-red-50 dark:bg-red-900/10' : ''}`}
                                        >
                                            <div>
                                                <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                                    {petugas.nama}
                                                </h3>
                                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                    {peranLabel(petugas.peran)}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <div
                                                    className={`text-sm font-semibold ${over ? 'text-red-600 dark:text-red-400' : 'text-neutral-900 dark:text-neutral-100'}`}
                                                >
                                                    Total:{' '}
                                                    {formatCurrency(total)}
                                                </div>
                                                {over && (
                                                    <div className="flex items-center justify-end gap-1 text-xs text-red-600 dark:text-red-400">
                                                        <AlertTriangle className="h-3 w-3" />
                                                        Melebihi batas Rp100.000
                                                    </div>
                                                )}
                                                {!over &&
                                                    existingTotals[petugas.id] >
                                                        0 && (
                                                        <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                            Sudah diajukan (kegiatan ini):{' '}
                                                            {formatCurrency(
                                                                existingTotals[
                                                                    petugas.id
                                                                ],
                                                            )}
                                                        </div>
                                                    )}
                                                {(() => {
                                                    const allTotal =
                                                        allExistingTotals[
                                                            petugas.id
                                                        ] ?? 0;
                                                    const ownTotal =
                                                        existingTotals[
                                                            petugas.id
                                                        ] ?? 0;
                                                    const externalTotal =
                                                        allTotal - ownTotal;
                                                    if (externalTotal <= 0) {
                                                        return null;
                                                    }
                                                    return (
                                                        <div className="mt-0.5 flex items-center justify-end gap-1 text-xs text-amber-600 dark:text-amber-400">
                                                            <Info className="h-3 w-3 shrink-0" />
                                                            +{formatCurrency(externalTotal)}{' '}
                                                            dari kegiatan lain
                                                            (total:{' '}
                                                            {formatCurrency(
                                                                allTotal,
                                                            )}
                                                            )
                                                        </div>
                                                    );
                                                })()}
                                            </div>
                                        </div>

                                        {/* Kegiatan table */}
                                        <div className="overflow-x-auto border-t border-neutral-200 dark:border-neutral-800">
                                            <table className="w-full table-fixed">
                                                <colgroup>
                                                    <col className="w-[44%]" />
                                                    <col className="w-[28%]" />
                                                    <col className="w-[28%]" />
                                                </colgroup>
                                                <thead className="border-b border-neutral-200 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-900/50">
                                                    <tr>
                                                        <th className="px-4 py-3 text-left text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                                            Kegiatan
                                                        </th>
                                                        <th className="px-4 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                            Pulsa Pelatihan (Rp)
                                                        </th>
                                                        <th className="px-4 py-3 text-center text-sm font-semibold whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                                            Pulsa Pendataan (Rp)
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                                    {kegiatanList.map(
                                                        (kegiatan) => {
                                                            const canPendataan =
                                                                kegiatan.metode_pendataan_pencacahan ===
                                                                    'CAPI' &&
                                                                (
                                                                    petugasPerKegiatan[
                                                                        kegiatan
                                                                            .id
                                                                    ] ?? []
                                                                ).some(
                                                                    (p) =>
                                                                        p.id ===
                                                                        petugas.id,
                                                                );
                                                            const canPelatihan =
                                                                (
                                                                    petugasPerKegiatanPelatihan[
                                                                        kegiatan
                                                                            .id
                                                                    ] ?? []
                                                                ).some(
                                                                    (p) =>
                                                                        p.id ===
                                                                        petugas.id,
                                                                );
                                                            const submittedPelatihan =
                                                                (existingPerKegiatan[
                                                                    `${kegiatan.id}_${petugas.id}_pelatihan`
                                                                ] ?? 0) > 0;
                                                            const submittedPendataan =
                                                                (existingPerKegiatan[
                                                                    `${kegiatan.id}_${petugas.id}_pendataan`
                                                                ] ?? 0) > 0;
                                                            const existingPelatihan =
                                                                existingPerKegiatan[
                                                                    `${kegiatan.id}_${petugas.id}_pelatihan`
                                                                ] ?? 0;
                                                            const existingPendataanVal =
                                                                existingPerKegiatan[
                                                                    `${kegiatan.id}_${petugas.id}_pendataan`
                                                                ] ?? 0;
                                                            const isPapi =
                                                                kegiatan.metode_pendataan_pencacahan ===
                                                                'PAPI';

                                                            return (
                                                                <tr
                                                                    key={
                                                                        kegiatan.id
                                                                    }
                                                                    className="transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                                                >
                                                                    <td className="px-4 py-3 text-sm text-neutral-900 dark:text-neutral-100">
                                                                        <div className="flex items-center gap-2">
                                                                            <span
                                                                                className={`inline-flex shrink-0 items-center rounded px-1.5 py-0.5 text-xs font-medium ${
                                                                                    isPapi
                                                                                        ? 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
                                                                                        : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                                                }`}
                                                                            >
                                                                                {isPapi
                                                                                    ? 'PAPI'
                                                                                    : 'CAPI'}
                                                                            </span>

                                                                            <span className="font-medium">
                                                                                {
                                                                                    kegiatan.nama_kegiatan
                                                                                }
                                                                            </span>
                                                                        </div>
                                                                    </td>

                                                                    {/* Pulsa Pelatihan — only on configured bulan_pelatihan */}
                                                                    <td className="px-4 py-3">
                                                                        {submittedPelatihan ? (
                                                                            <div className="flex h-9 w-full items-center justify-end rounded-md border border-neutral-200 bg-neutral-100 px-3 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400">
                                                                                {formatCurrency(
                                                                                    existingPelatihan,
                                                                                )}
                                                                            </div>
                                                                        ) : !canPelatihan ? (
                                                                            <div className="flex h-9 w-full items-center justify-center rounded-md border border-neutral-200/50 bg-neutral-50 text-xs text-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-600">
                                                                                —
                                                                            </div>
                                                                        ) : (
                                                                            (() => {
                                                                                const raw =
                                                                                    getNominal(
                                                                                        kegiatan.id,
                                                                                        petugas.id,
                                                                                        'pelatihan',
                                                                                    );
                                                                                const invalid =
                                                                                    raw >
                                                                                        0 &&
                                                                                    (raw %
                                                                                        1000 !==
                                                                                        0 ||
                                                                                        raw >
                                                                                            MAX_PULSA_PER_PETUGAS);
                                                                                return (
                                                                                    <div className="space-y-1">
                                                                                        <Input
                                                                                            type="text"
                                                                                            inputMode="numeric"
                                                                                            value={formatThousands(
                                                                                                raw,
                                                                                            )}
                                                                                            onChange={(
                                                                                                e,
                                                                                            ) =>
                                                                                                setNominal(
                                                                                                    kegiatan.id,
                                                                                                    petugas.id,
                                                                                                    'pelatihan',
                                                                                                    parseRaw(
                                                                                                        e
                                                                                                            .target
                                                                                                            .value,
                                                                                                    ),
                                                                                                )
                                                                                            }
                                                                                            className={`w-full text-right ${invalid ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                                                                                            placeholder="0"
                                                                                        />
                                                                                        {invalid && (
                                                                                            <p className="text-xs text-red-500">
                                                                                                Kelipatan
                                                                                                1.000,
                                                                                                maks
                                                                                                100.000
                                                                                            </p>
                                                                                        )}
                                                                                    </div>
                                                                                );
                                                                            })()
                                                                        )}
                                                                    </td>

                                                                    {/* Pulsa Pendataan — locked for PAPI */}
                                                                    <td className="px-4 py-3">
                                                                        {submittedPendataan ? (
                                                                            <div className="flex h-9 w-full items-center justify-end rounded-md border border-neutral-200 bg-neutral-100 px-3 text-sm text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-400">
                                                                                {formatCurrency(
                                                                                    existingPendataanVal,
                                                                                )}
                                                                            </div>
                                                                        ) : !canPendataan ? (
                                                                            <div className="flex h-9 w-full items-center justify-center rounded-md border border-neutral-200/50 bg-neutral-50 text-xs text-neutral-400 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-600">
                                                                                —
                                                                            </div>
                                                                        ) : (
                                                                            (() => {
                                                                                const raw =
                                                                                    getNominal(
                                                                                        kegiatan.id,
                                                                                        petugas.id,
                                                                                        'pendataan',
                                                                                    );
                                                                                const invalid =
                                                                                    raw >
                                                                                        0 &&
                                                                                    (raw %
                                                                                        1000 !==
                                                                                        0 ||
                                                                                        raw >
                                                                                            MAX_PULSA_PER_PETUGAS);
                                                                                return (
                                                                                    <div className="space-y-1">
                                                                                        <Input
                                                                                            type="text"
                                                                                            inputMode="numeric"
                                                                                            value={formatThousands(
                                                                                                raw,
                                                                                            )}
                                                                                            onChange={(
                                                                                                e,
                                                                                            ) =>
                                                                                                setNominal(
                                                                                                    kegiatan.id,
                                                                                                    petugas.id,
                                                                                                    'pendataan',
                                                                                                    parseRaw(
                                                                                                        e
                                                                                                            .target
                                                                                                            .value,
                                                                                                    ),
                                                                                                )
                                                                                            }
                                                                                            className={`w-full text-right ${invalid ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                                                                                            placeholder="0"
                                                                                        />
                                                                                        {invalid && (
                                                                                            <p className="text-xs text-red-500">
                                                                                                Kelipatan
                                                                                                1.000,
                                                                                                maks
                                                                                                100.000
                                                                                            </p>
                                                                                        )}
                                                                                    </div>
                                                                                );
                                                                            })()
                                                                        )}
                                                                    </td>
                                                                </tr>
                                                            );
                                                        },
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    </ContentCard>
                                );
                            },
                        )}

                        {/* Catatan */}
                        <ContentCard>
                            <h3 className="mb-3 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                Catatan (Opsional)
                            </h3>
                            <Textarea
                                value={catatan}
                                onChange={(e) => setCatatan(e.target.value)}
                                placeholder="Catatan tambahan untuk pengajuan pulsa ini..."
                                rows={3}
                                className="max-w-xl"
                            />
                        </ContentCard>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3">
                            <Button variant="outline" asChild>
                                <Link href="/pengajuan-pulsa">Batal</Link>
                            </Button>
                            <Button
                                onClick={handleSubmit}
                                disabled={isSubmitting}
                                className="gap-2"
                            >
                                <Send className="h-4 w-4" />
                                {isSubmitting
                                    ? 'Mengirim...'
                                    : 'Kirim Pengajuan'}
                            </Button>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}

PengajuanPulsaCreate.layout = (page: React.ReactNode) => page;
