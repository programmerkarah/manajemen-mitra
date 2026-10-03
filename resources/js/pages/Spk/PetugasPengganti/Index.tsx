import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { SearchableSelect } from '@/components/searchable-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { FileUpload } from '@/components/ui/file-upload';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router } from '@inertiajs/react';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import Circle from 'lucide-react/icons/circle';
import FileText from 'lucide-react/icons/file-text';
import UserRoundCheck from 'lucide-react/icons/user-round-check';
import UserRoundMinus from 'lucide-react/icons/user-round-minus';
import UsersRound from 'lucide-react/icons/users-round';
import { useMemo, useState } from 'react';

interface PkppSummary {
    nomor: string | null;
    skema_kode: string | null;
    termin_count: number;
    honor_ob: number;
    tanggal_kontrak: string | null;
    tanggal_mulai_lapangan: string | null;
    has_spk: boolean;
    pk_uploaded: boolean;
}

interface ReplacementItem {
    id: number;
    hashed_id: string;
    spk_lama_id: number | null;
    spk_lama_nomor: string | null;
    petugas_berhenti_nama: string | null;
    petugas_pengganti_nama: string | null;
    termination_type: 'diberhentikan' | 'mengundurkan_diri' | null;
    termin_i_paid: boolean | null;
    requires_old_documents: boolean;
    tanggal_berhenti: string | null;
    tanggal_mulai_pkpp: string | null;
    status: string;
    has_pkpp_contract: boolean;
    pkpp: PkppSummary | null;
    replacement_bast: {
        nomor: string;
        nomor_urut: string | null;
        tanggal: string | null;
        uploaded_at: string | null;
    } | null;
}

interface StoppedCandidate {
    spk_id: number;
    spk_hashed_id: string;
    nomor_spk: string;
    petugas_id: number;
    petugas_nama: string | null;
    petugas_nik: string | null;
}

interface ReplacementCandidate {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string | null;
}

interface IndexProps {
    replacements: ReplacementItem[];
    stopped_candidates: StoppedCandidate[];
    replacement_candidates: ReplacementCandidate[];
    can_manage: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Perjanjian Kerja', href: '/spk' },
    { title: 'Pergantian Petugas SE2026', href: '#' },
];

const prettyScheme = (code: string | null) => {
    if (!code) return '-';
    return `Skema ${code.replace('scheme_', '')}`;
};

const stopLabel = (value: ReplacementItem['termination_type']): string => {
    if (value === 'diberhentikan') return 'Diberhentikan';
    if (value === 'mengundurkan_diri') return 'Mengundurkan diri';
    return 'Berhenti';
};

export default function Index({
    replacements,
    stopped_candidates,
    replacement_candidates,
    can_manage,
}: IndexProps) {
    const [stoppedSpkId, setStoppedSpkId] = useState('');
    const [terminationType, setTerminationType] = useState<
        'diberhentikan' | 'mengundurkan_diri'
    >('mengundurkan_diri');
    const [stopDate, setStopDate] = useState('');
    const [savingStop, setSavingStop] = useState(false);
    const [replacementForm, setReplacementForm] = useState<
        Record<
            number,
            {
                petugasId: string;
                startDate: string;
            }
        >
    >({});
    const [assigning, setAssigning] = useState<number | null>(null);
    const [bastFiles, setBastFiles] = useState<Record<number, File | null>>({});
    const [bastNumbers, setBastNumbers] = useState<Record<number, string>>({});
    const [bastDates, setBastDates] = useState<Record<number, string>>({});
    const [uploadingBast, setUploadingBast] = useState<number | null>(null);

    const availableStopCandidates = useMemo(
        () => stopped_candidates,
        [stopped_candidates],
    );

    const registerStop = () => {
        if (!stoppedSpkId || !stopDate) return;

        setSavingStop(true);
        router.post(
            '/sensus-ekonomi/replacements/register-stop',
            {
                spk_id: Number(stoppedSpkId),
                termination_type: terminationType,
                tanggal_berhenti: stopDate,
            },
            {
                preserveScroll: true,
                onFinish: () => setSavingStop(false),
                onSuccess: () => {
                    setStoppedSpkId('');
                    setStopDate('');
                },
            },
        );
    };

    const assignReplacement = (item: ReplacementItem) => {
        const form = replacementForm[item.id];
        if (!form?.petugasId || !form.startDate) return;

        setAssigning(item.id);
        router.post(
            `/sensus-ekonomi/replacements/${item.hashed_id}/assign`,
            {
                petugas_pengganti_id: Number(form.petugasId),
                tanggal_mulai_pkpp: form.startDate,
            },
            {
                preserveScroll: true,
                onFinish: () => setAssigning(null),
            },
        );
    };

    const uploadReplacementBast = (item: ReplacementItem) => {
        const file = bastFiles[item.id];
        const number =
            bastNumbers[item.id] ?? item.replacement_bast?.nomor_urut ?? '';
        const date = bastDates[item.id] ?? item.replacement_bast?.tanggal ?? '';

        if (!file || !number.trim()) return;

        setUploadingBast(item.id);
        router.post(
            `/sensus-ekonomi/replacements/${item.hashed_id}/bast`,
            {
                nomor_bast: number.replace(/\D/g, ''),
                tanggal_bast: date,
                file,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => setUploadingBast(null),
                onSuccess: () =>
                    setBastFiles((current) => ({
                        ...current,
                        [item.id]: null,
                    })),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pergantian Petugas SE2026" />

            <div className="space-y-5">
                <PageHeader
                    title="Pergantian Petugas SE2026"
                    description="Satu alur untuk mencatat petugas berhenti, menentukan pengganti, menetapkan skema, lalu menginventaris PK, BAPP, dan BAST."
                >
                    <Button variant="outline" asChild>
                        <Link href="/spk" prefetch>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Perjanjian Kerja
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <div className="grid gap-3 md:grid-cols-4">
                        {[
                            ['1', 'Status petugas', 'Berhenti / mundur'],
                            [
                                '2',
                                'Petugas pengganti',
                                'Tidak menunggu upload dokumen',
                            ],
                            [
                                '3',
                                'Skema PKPP',
                                'Otomatis dari tanggal kontrak',
                            ],
                            ['4', 'Dokumen pengganti', 'PK → BAPP → BAST'],
                        ].map(([step, title, desc]) => (
                            <div
                                key={step}
                                className="rounded-xl border border-border bg-muted/20 p-4"
                            >
                                <div className="flex items-center gap-3">
                                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                                        {step}
                                    </span>
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold">
                                            {title}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {desc}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </ContentCard>

                {can_manage && (
                    <ContentCard>
                        <div className="space-y-4">
                            <div>
                                <h2 className="flex items-center gap-2 text-base font-semibold">
                                    <UserRoundMinus className="h-4 w-4" />
                                    Catat petugas berhenti
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Tahap ini hanya mencatat status dan tanggal
                                    berhenti. Petugas pengganti ditentukan pada
                                    tahap berikutnya, tanpa menunggu status
                                    upload BAPP/BAST petugas lama.
                                </p>
                            </div>

                            <div className="grid gap-3 lg:grid-cols-[minmax(260px,1fr)_220px_220px_auto] lg:items-end">
                                <div className="space-y-1.5">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Petugas / PK Sensus Ekonomi
                                    </p>
                                    <Select
                                        value={stoppedSpkId}
                                        onValueChange={setStoppedSpkId}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih petugas yang berhenti" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableStopCandidates.map(
                                                (candidate) => (
                                                    <SelectItem
                                                        key={candidate.spk_id}
                                                        value={String(
                                                            candidate.spk_id,
                                                        )}
                                                    >
                                                        {candidate.petugas_nama}{' '}
                                                        · {candidate.nomor_spk}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Status berhenti
                                    </p>
                                    <Select
                                        value={terminationType}
                                        onValueChange={(value) =>
                                            setTerminationType(
                                                value as
                                                    | 'diberhentikan'
                                                    | 'mengundurkan_diri',
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="mengundurkan_diri">
                                                Mengundurkan diri
                                            </SelectItem>
                                            <SelectItem value="diberhentikan">
                                                Diberhentikan
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Tanggal berhenti
                                    </p>
                                    <DatePicker
                                        value={stopDate}
                                        onChange={setStopDate}
                                        placeholder="Pilih tanggal"
                                    />
                                </div>

                                <Button
                                    onClick={registerStop}
                                    disabled={
                                        !stoppedSpkId || !stopDate || savingStop
                                    }
                                >
                                    Simpan Status
                                </Button>
                            </div>
                        </div>
                    </ContentCard>
                )}

                <div className="space-y-4">
                    {replacements.map((item) => {
                        const form = replacementForm[item.id] ?? {
                            petugasId: '',
                            startDate: item.tanggal_mulai_pkpp ?? '',
                        };
                        const replacementAssigned = Boolean(
                            item.petugas_pengganti_nama,
                        );
                        const schemeReady = Boolean(item.pkpp);

                        return (
                            <ContentCard key={item.id}>
                                <div className="space-y-5">
                                    <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-lg font-semibold">
                                                    {item.petugas_berhenti_nama ??
                                                        'Petugas'}
                                                </h3>
                                                <Badge variant="secondary">
                                                    {stopLabel(
                                                        item.termination_type,
                                                    )}
                                                </Badge>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {item.spk_lama_nomor ?? '-'} ·{' '}
                                                {item.tanggal_berhenti ?? '-'}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href="/bapp" prefetch>
                                                    BAPP Petugas Lama
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href="/berita-acara?mode=sensus-ekonomi"
                                                    prefetch
                                                >
                                                    BAST Petugas Lama
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>

                                    {item.termination_type ===
                                        'diberhentikan' && (
                                        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                            Petugas diberhentikan: BAPP dan BAST
                                            petugas lama tetap wajib
                                            diinventaris. Kelengkapan upload
                                            tidak menghalangi penetapan petugas
                                            pengganti.
                                        </div>
                                    )}

                                    <div className="grid gap-3 xl:grid-cols-3">
                                        <div className="rounded-xl border border-border p-4">
                                            <div className="flex items-start gap-3">
                                                <CheckCircle2 className="mt-0.5 h-5 w-5 text-emerald-600" />
                                                <div>
                                                    <p className="text-sm font-semibold">
                                                        1. Status petugas
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {stopLabel(
                                                            item.termination_type,
                                                        )}{' '}
                                                        pada{' '}
                                                        {item.tanggal_berhenti ??
                                                            '-'}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="rounded-xl border border-border p-4">
                                            <div className="mb-3 flex items-start gap-3">
                                                {replacementAssigned ? (
                                                    <CheckCircle2 className="mt-0.5 h-5 w-5 text-emerald-600" />
                                                ) : (
                                                    <Circle className="mt-0.5 h-5 w-5 text-muted-foreground" />
                                                )}
                                                <div>
                                                    <p className="text-sm font-semibold">
                                                        2. Petugas pengganti
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {replacementAssigned
                                                            ? item.petugas_pengganti_nama
                                                            : 'Belum ditentukan'}
                                                    </p>
                                                </div>
                                            </div>

                                            {!replacementAssigned &&
                                                can_manage && (
                                                    <div className="space-y-2">
                                                        <Select
                                                            value={
                                                                form.petugasId
                                                            }
                                                            onValueChange={(
                                                                value,
                                                            ) =>
                                                                setReplacementForm(
                                                                    (
                                                                        current,
                                                                    ) => ({
                                                                        ...current,
                                                                        [item.id]:
                                                                            {
                                                                                ...form,
                                                                                petugasId:
                                                                                    value,
                                                                            },
                                                                    }),
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Pilih petugas pengganti" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {replacement_candidates.map(
                                                                    (
                                                                        candidate,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                candidate.id
                                                                            }
                                                                            value={String(
                                                                                candidate.id,
                                                                            )}
                                                                        >
                                                                            {
                                                                                candidate.nama
                                                                            }{' '}
                                                                            ·{' '}
                                                                            {candidate.nik ??
                                                                                '-'}
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        <DatePicker
                                                            value={
                                                                form.startDate
                                                            }
                                                            min={
                                                                item.tanggal_berhenti ??
                                                                undefined
                                                            }
                                                            onChange={(value) =>
                                                                setReplacementForm(
                                                                    (
                                                                        current,
                                                                    ) => ({
                                                                        ...current,
                                                                        [item.id]:
                                                                            {
                                                                                ...form,
                                                                                startDate:
                                                                                    value,
                                                                            },
                                                                    }),
                                                                )
                                                            }
                                                            placeholder="Tanggal mulai pengganti"
                                                        />
                                                        <Button
                                                            size="sm"
                                                            className="w-full"
                                                            disabled={
                                                                !form.petugasId ||
                                                                !form.startDate ||
                                                                assigning ===
                                                                    item.id
                                                            }
                                                            onClick={() =>
                                                                assignReplacement(
                                                                    item,
                                                                )
                                                            }
                                                        >
                                                            <UserRoundCheck className="mr-2 h-4 w-4" />
                                                            Tetapkan Pengganti
                                                        </Button>
                                                    </div>
                                                )}
                                        </div>

                                        <div className="rounded-xl border border-border p-4">
                                            <div className="mb-3 flex items-start gap-3">
                                                {schemeReady ? (
                                                    <CheckCircle2 className="mt-0.5 h-5 w-5 text-emerald-600" />
                                                ) : (
                                                    <Circle className="mt-0.5 h-5 w-5 text-muted-foreground" />
                                                )}
                                                <div>
                                                    <p className="text-sm font-semibold">
                                                        3. Skema & PK pengganti
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {schemeReady
                                                            ? `${prettyScheme(
                                                                  item.pkpp
                                                                      ?.skema_kode ??
                                                                      null,
                                                              )} · ${item.pkpp?.termin_count} termin`
                                                            : replacementAssigned
                                                              ? 'Tentukan skema berdasarkan tanggal kontrak'
                                                              : 'Menunggu petugas pengganti'}
                                                    </p>
                                                </div>
                                            </div>

                                            {replacementAssigned && (
                                                <Button
                                                    size="sm"
                                                    className="w-full"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/spk/petugas-pengganti/${item.hashed_id}/pkpp-contracts/create`}
                                                        prefetch
                                                    >
                                                        <FileText className="mr-2 h-4 w-4" />
                                                        {schemeReady
                                                            ? 'Kelola Skema & PK'
                                                            : 'Tentukan Skema'}
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    </div>

                                    {schemeReady && (
                                        <div className="rounded-xl border border-border bg-muted/20 p-4">
                                            <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                                <div>
                                                    <p className="flex items-center gap-2 text-sm font-semibold">
                                                        <UsersRound className="h-4 w-4" />
                                                        4. Dokumen petugas
                                                        pengganti
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        PK{' '}
                                                        {item.pkpp?.pk_uploaded
                                                            ? 'sudah diunggah'
                                                            : 'belum diunggah'}{' '}
                                                        · BAPP{' '}
                                                        {item.pkpp
                                                            ?.termin_count === 2
                                                            ? 'Termin I & II'
                                                            : '1 termin'}{' '}
                                                        · BAST final
                                                    </p>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/spk/petugas-pengganti/${item.hashed_id}/pkpp-contracts/create`}
                                                            prefetch
                                                        >
                                                            Kelola PK
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href="/bapp"
                                                            prefetch
                                                        >
                                                            Kelola BAPP
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </div>

                                            <div className="mt-4 grid gap-3 border-t border-border pt-4 lg:grid-cols-[minmax(0,1fr)_220px]">
                                                <div className="space-y-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-muted-foreground">
                                                            Nomor BAST pengganti
                                                        </p>
                                                        <div className="mt-1.5 flex min-w-0 items-stretch rounded-md border border-input bg-background focus-within:ring-2 focus-within:ring-ring/30">
                                                            <span className="flex items-center border-r border-input px-3 text-sm font-medium text-muted-foreground">
                                                                B-
                                                            </span>
                                                            <input
                                                                inputMode="numeric"
                                                                pattern="[0-9]*"
                                                                value={
                                                                    bastNumbers[
                                                                        item.id
                                                                    ] ??
                                                                    item
                                                                        .replacement_bast
                                                                        ?.nomor_urut ??
                                                                    ''
                                                                }
                                                                onChange={(
                                                                    event,
                                                                ) => {
                                                                    const value =
                                                                        event.target.value.replace(
                                                                            /\D/g,
                                                                            '',
                                                                        );
                                                                    setBastNumbers(
                                                                        (
                                                                            current,
                                                                        ) => ({
                                                                            ...current,
                                                                            [item.id]:
                                                                                value,
                                                                        }),
                                                                    );
                                                                }}
                                                                placeholder="Nomor"
                                                                className="h-10 w-24 min-w-[72px] bg-transparent px-3 text-sm outline-none"
                                                            />
                                                            <span className="flex min-w-0 flex-1 items-center overflow-hidden border-l border-input px-3 text-xs text-muted-foreground">
                                                                <span className="truncate">
                                                                    /BAST-SE2026/1373/PL.200/2026
                                                                </span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <FileUpload
                                                        value={
                                                            bastFiles[
                                                                item.id
                                                            ] ?? null
                                                        }
                                                        maxSizeMb={20}
                                                        label={
                                                            item.replacement_bast
                                                                ? 'Pilih PDF pengganti BAST'
                                                                : 'Pilih atau jatuhkan PDF BAST pengganti'
                                                        }
                                                        helperText="PDF final BAST petugas pengganti"
                                                        onChange={(file) =>
                                                            setBastFiles(
                                                                (current) => ({
                                                                    ...current,
                                                                    [item.id]:
                                                                        file,
                                                                }),
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="space-y-3">
                                                    <div>
                                                        <p className="text-xs font-medium text-muted-foreground">
                                                            Tanggal BAST
                                                        </p>
                                                        <DatePicker
                                                            value={
                                                                bastDates[
                                                                    item.id
                                                                ] ??
                                                                item
                                                                    .replacement_bast
                                                                    ?.tanggal ??
                                                                ''
                                                            }
                                                            onChange={(value) =>
                                                                setBastDates(
                                                                    (
                                                                        current,
                                                                    ) => ({
                                                                        ...current,
                                                                        [item.id]:
                                                                            value,
                                                                    }),
                                                                )
                                                            }
                                                            placeholder="Pilih tanggal"
                                                        />
                                                    </div>
                                                    <Button
                                                        className="w-full"
                                                        disabled={
                                                            !bastFiles[
                                                                item.id
                                                            ] ||
                                                            !(
                                                                bastNumbers[
                                                                    item.id
                                                                ] ??
                                                                item
                                                                    .replacement_bast
                                                                    ?.nomor_urut ??
                                                                ''
                                                            ).trim() ||
                                                            uploadingBast ===
                                                                item.id
                                                        }
                                                        onClick={() =>
                                                            uploadReplacementBast(
                                                                item,
                                                            )
                                                        }
                                                    >
                                                        {uploadingBast ===
                                                        item.id
                                                            ? 'Mengunggah...'
                                                            : item.replacement_bast
                                                              ? 'Perbarui BAST'
                                                              : 'Upload BAST'}
                                                    </Button>
                                                    {item.replacement_bast && (
                                                        <p className="text-xs text-emerald-600 dark:text-emerald-400">
                                                            BAST pengganti sudah
                                                            tersimpan.
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </ContentCard>
                        );
                    })}

                    {replacements.length === 0 && (
                        <ContentCard>
                            <div className="py-10 text-center">
                                <UserRoundMinus className="mx-auto h-10 w-10 text-muted-foreground" />
                                <p className="mt-3 font-medium">
                                    Belum ada petugas berhenti
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Catat status petugas terlebih dahulu.
                                    Petugas pengganti baru ditentukan sesudah
                                    tahap itu.
                                </p>
                            </div>
                        </ContentCard>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
