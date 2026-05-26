import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpen,
    ChevronRight,
    Eye,
    GitMerge,
    Loader2,
    Save,
    Search,
    X,
} from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
    { title: 'Edit Dasar Hukum', href: '#' },
];

interface KategoriOption {
    value: string;
    label: string;
}

interface DasarHukumItem {
    id: number;
    kategori: string;
    instansi: string | null;
    nomor: string;
    tentang: string;
    tahun: number;
    nomor_ln: string | null;
    tahun_ln: number | null;
    nomor_tln: string | null;
    nomor_bn: string | null;
    tahun_bn: number | null;
    perubahan_count: number;
}

interface DasarHukum {
    id: number;
    kategori: string;
    instansi: string | null;
    nomor: string;
    tentang: string;
    tahun: number;
    status: 'aktif' | 'nonaktif';
    jenis: 'pertama' | 'perubahan';
    induk_id: number | null;
    nomor_ln: string | null;
    tahun_ln: number | null;
    nomor_tln: string | null;
    nomor_bn: string | null;
    tahun_bn: number | null;
}

interface Props {
    dasarHukum: DasarHukum;
    kategoriOptions: KategoriOption[];
    dasarHukumList: DasarHukumItem[];
}

const KATEGORI_DENGAN_LN = [
    'undang_undang',
    'peraturan_pemerintah',
    'peraturan_presiden',
];

const KATEGORI_DENGAN_INSTANSI = [
    'peraturan_menteri_badan',
    'keputusan_menteri_kepala_badan',
];

function buildKategoriLabel(kategori: string, instansi: string | null): string {
    if (kategori === 'undang_undang') return 'Undang-Undang';
    if (kategori === 'peraturan_pemerintah') return 'Peraturan Pemerintah';
    if (kategori === 'peraturan_presiden') return 'Peraturan Presiden';
    if (kategori === 'peraturan_menteri_badan') {
        return instansi?.toLowerCase().startsWith('badan')
            ? `Peraturan ${instansi}`
            : `Peraturan Menteri ${instansi ?? ''}`;
    }
    if (kategori === 'keputusan_menteri_kepala_badan') {
        return instansi?.toLowerCase().startsWith('badan')
            ? `Keputusan Kepala ${instansi}`
            : `Keputusan Menteri ${instansi ?? ''}`;
    }
    if (kategori === 'peraturan_kepala_badan') {
        return 'Peraturan Kepala Badan Pusat Statistik';
    }
    return kategori;
}

function getKategoriBadgeClass(kategori: string): string {
    if (kategori === 'undang_undang') {
        return 'border-fuchsia-300 text-fuchsia-700 dark:border-fuchsia-700 dark:text-fuchsia-300';
    }
    if (kategori === 'peraturan_pemerintah') {
        return 'border-blue-300 text-blue-700 dark:border-blue-700 dark:text-blue-300';
    }
    if (kategori === 'peraturan_presiden') {
        return 'border-indigo-300 text-indigo-700 dark:border-indigo-700 dark:text-indigo-300';
    }
    if (kategori === 'peraturan_menteri_badan') {
        return 'border-teal-300 text-teal-700 dark:border-teal-700 dark:text-teal-300';
    }
    if (kategori === 'peraturan_kepala_badan') {
        return 'border-cyan-300 text-cyan-700 dark:border-cyan-700 dark:text-cyan-300';
    }
    if (kategori === 'keputusan_menteri_kepala_badan') {
        return 'border-amber-300 text-amber-700 dark:border-amber-700 dark:text-amber-300';
    }

    return 'border-neutral-300 text-neutral-700 dark:border-neutral-700 dark:text-neutral-300';
}

function buildSingleTeks(
    kategori: string,
    instansi: string | null,
    nomor: string,
    tahun: string,
    tentang: string,
    nomorLn?: string,
    tahunLn?: string,
    nomorTln?: string,
    nomorBn?: string,
    tahunBn?: string,
): string {
    const label = buildKategoriLabel(kategori, instansi);
    let text = `${label} Nomor ${nomor} Tahun ${tahun} tentang ${tentang}`;

    if (KATEGORI_DENGAN_LN.includes(kategori) && nomorLn && tahunLn) {
        const tln = nomorTln
            ? `, Tambahan Lembaran Negara Republik Indonesia Nomor ${nomorTln}`
            : '';
        text += ` (Lembaran Negara Republik Indonesia Tahun ${tahunLn} Nomor ${nomorLn}${tln})`;
    }
    if (kategori === 'peraturan_menteri_badan' && nomorBn && tahunBn) {
        text += ` (Berita Negara Republik Indonesia Tahun ${tahunBn} Nomor ${nomorBn})`;
    }
    return text;
}

function buildPreviewTeks(params: {
    jenis: string;
    kategori: string;
    instansi: string | null;
    nomor: string;
    tahun: string;
    tentang: string;
    nomorLn: string;
    tahunLn: string;
    nomorTln: string;
    nomorBn: string;
    tahunBn: string;
    induk: DasarHukumItem | null;
}): string {
    const {
        jenis,
        kategori,
        instansi,
        nomor,
        tahun,
        tentang,
        nomorLn,
        tahunLn,
        nomorTln,
        nomorBn,
        tahunBn,
        induk,
    } = params;
    if (!kategori || !nomor || !tahun || !tentang) return '';

    const thisTeks = buildSingleTeks(
        kategori,
        instansi,
        nomor,
        tahun,
        tentang,
        nomorLn,
        tahunLn,
        nomorTln,
        nomorBn,
        tahunBn,
    );

    if (jenis === 'perubahan' && induk) {
        const phrase =
            (induk.perubahan_count ?? 0) > 1
                ? 'sebagaimana telah beberapa kali diubah terakhir dengan'
                : 'sebagaimana telah diubah dengan';
        const indukTeks = buildSingleTeks(
            induk.kategori,
            induk.instansi,
            induk.nomor,
            induk.tahun.toString(),
            induk.tentang,
            induk.nomor_ln ?? undefined,
            induk.tahun_ln?.toString() ?? undefined,
            induk.nomor_tln ?? undefined,
            induk.nomor_bn ?? undefined,
            induk.tahun_bn?.toString() ?? undefined,
        );
        return `${indukTeks} ${phrase} ${thisTeks}`;
    }

    return thisTeks;
}

export default function Edit({
    dasarHukum,
    kategoriOptions,
    dasarHukumList,
}: Props) {
    const [activeStep, setActiveStep] = useState<1 | 2 | 3>(1);

    const [jenis, setJenis] = useState<'pertama' | 'perubahan'>(
        dasarHukum.jenis ?? 'pertama',
    );
    const [kategori, setKategori] = useState(dasarHukum.kategori);
    const [instansi, setInstansi] = useState(dasarHukum.instansi || '');
    const [nomor, setNomor] = useState(dasarHukum.nomor);
    const [tentang, setTentang] = useState(dasarHukum.tentang);
    const [tahun, setTahun] = useState(dasarHukum.tahun.toString());
    const [status, setStatus] = useState<'aktif' | 'nonaktif'>(
        dasarHukum.status,
    );

    const [nomorLn, setNomorLn] = useState(dasarHukum.nomor_ln ?? '');
    const [tahunLn, setTahunLn] = useState(
        dasarHukum.tahun_ln?.toString() ?? '',
    );
    const [nomorTln, setNomorTln] = useState(dasarHukum.nomor_tln ?? '');
    const [nomorBn, setNomorBn] = useState(dasarHukum.nomor_bn ?? '');
    const [tahunBn, setTahunBn] = useState(
        dasarHukum.tahun_bn?.toString() ?? '',
    );

    const [indukId, setIndukId] = useState<number | null>(
        dasarHukum.induk_id ?? null,
    );
    const [indukSearch, setIndukSearch] = useState('');

    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const needsInstansi = KATEGORI_DENGAN_INSTANSI.includes(kategori);
    const hasLN = KATEGORI_DENGAN_LN.includes(kategori);
    const hasBN = kategori === 'peraturan_menteri_badan';
    const hasPenerbitanFields = hasLN || hasBN;

    const selectedInduk = useMemo(
        () => dasarHukumList.find((d) => d.id === indukId) ?? null,
        [dasarHukumList, indukId],
    );

    const filteredIndukList = useMemo(() => {
        if (!indukSearch.trim()) {
            return [...dasarHukumList].sort(
                (a, b) => b.tahun - a.tahun || a.nomor.localeCompare(b.nomor),
            );
        }
        const q = indukSearch.toLowerCase();
        return dasarHukumList
            .filter(
                (d) =>
                    d.nomor.toLowerCase().includes(q) ||
                    d.tentang.toLowerCase().includes(q) ||
                    d.tahun.toString().includes(q),
            )
            .sort(
                (a, b) => b.tahun - a.tahun || a.nomor.localeCompare(b.nomor),
            );
    }, [dasarHukumList, indukSearch]);

    const previewTeks = useMemo(
        () =>
            buildPreviewTeks({
                jenis,
                kategori,
                instansi: instansi || null,
                nomor,
                tahun,
                tentang,
                nomorLn,
                tahunLn,
                nomorTln,
                nomorBn,
                tahunBn,
                induk: selectedInduk,
            }),
        [
            jenis,
            kategori,
            instansi,
            nomor,
            tahun,
            tentang,
            nomorLn,
            tahunLn,
            nomorTln,
            nomorBn,
            tahunBn,
            selectedInduk,
        ],
    );

    const isStep1Done = !!(
        kategori &&
        nomor &&
        tahun &&
        tentang &&
        (!needsInstansi || instansi)
    );
    const isStep3Done = jenis === 'pertama' || indukId !== null;

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        router.patch(
            `/dasar-hukum/${dasarHukum.id}`,
            {
                jenis,
                kategori,
                instansi: instansi || null,
                nomor,
                tentang,
                tahun: parseInt(tahun),
                nomor_ln: nomorLn || null,
                tahun_ln: tahunLn ? parseInt(tahunLn) : null,
                nomor_tln: nomorTln || null,
                nomor_bn: nomorBn || null,
                tahun_bn: tahunBn ? parseInt(tahunBn) : null,
                induk_id: jenis === 'perubahan' ? indukId : null,
                status,
            },
            {
                onError: (errs) => {
                    setErrors(errs);
                    setProcessing(false);
                },
                onSuccess: () => {
                    setProcessing(false);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Dasar Hukum SK" />

            <div className="space-y-6">
                <PageHeader
                    title="Edit Dasar Hukum SK"
                    description="Perbarui informasi dasar hukum"
                >
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="w-full gap-2 sm:w-auto"
                    >
                        <Link href="/dasar-hukum">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* ── Step 1: Informasi Dasar ─────────────────────── */}
                    <ContentCard>
                        <button
                            type="button"
                            onClick={() =>
                                setActiveStep(activeStep === 1 ? 2 : 1)
                            }
                            className="flex w-full items-center gap-3 text-left"
                        >
                            <div
                                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold ${isStep1Done ? 'bg-green-500 text-white' : 'bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200'}`}
                            >
                                {isStep1Done ? '✓' : '1'}
                            </div>
                            <div className="flex-1">
                                <p className="font-semibold text-neutral-900 dark:text-white">
                                    Informasi Dasar
                                </p>
                                {isStep1Done && activeStep !== 1 && (
                                    <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                                        {buildKategoriLabel(
                                            kategori,
                                            instansi || null,
                                        )}{' '}
                                        No. {nomor} Tahun {tahun}
                                    </p>
                                )}
                            </div>
                            <ChevronRight
                                className={`h-4 w-4 text-neutral-400 transition-transform ${activeStep === 1 ? 'rotate-90' : ''}`}
                            />
                        </button>

                        {activeStep === 1 && (
                            <div className="mt-5 space-y-5 border-t border-neutral-100 pt-5 dark:border-neutral-800">
                                <div className="space-y-1.5">
                                    <Label htmlFor="kategori">
                                        Kategori{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={kategori}
                                        onValueChange={(v) => {
                                            setKategori(v);
                                            setInstansi('');
                                        }}
                                        disabled={processing}
                                    >
                                        <SelectTrigger id="kategori">
                                            <SelectValue placeholder="Pilih kategori peraturan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {kategoriOptions.map((o) => (
                                                <SelectItem
                                                    key={o.value}
                                                    value={o.value}
                                                >
                                                    {o.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.kategori && (
                                        <p className="text-sm text-red-500">
                                            {errors.kategori}
                                        </p>
                                    )}
                                </div>

                                {needsInstansi && (
                                    <div className="space-y-1.5">
                                        <Label htmlFor="instansi">
                                            Nama Instansi{' '}
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="instansi"
                                            value={instansi}
                                            onChange={(e) =>
                                                setInstansi(e.target.value)
                                            }
                                            placeholder="Contoh: Keuangan, Badan Pusat Statistik"
                                            disabled={processing}
                                        />
                                        {errors.instansi && (
                                            <p className="text-sm text-red-500">
                                                {errors.instansi}
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="nomor">
                                            Nomor{' '}
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="nomor"
                                            value={nomor}
                                            onChange={(e) =>
                                                setNomor(e.target.value)
                                            }
                                            placeholder="Contoh: 16"
                                            disabled={processing}
                                        />
                                        {errors.nomor && (
                                            <p className="text-sm text-red-500">
                                                {errors.nomor}
                                            </p>
                                        )}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="tahun">
                                            Tahun{' '}
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        </Label>
                                        <Input
                                            id="tahun"
                                            type="number"
                                            min={1900}
                                            max={2100}
                                            value={tahun}
                                            onChange={(e) =>
                                                setTahun(e.target.value)
                                            }
                                            disabled={processing}
                                        />
                                        {errors.tahun && (
                                            <p className="text-sm text-red-500">
                                                {errors.tahun}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="tentang">
                                        Tentang{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Textarea
                                        id="tentang"
                                        value={tentang}
                                        onChange={(e) =>
                                            setTentang(e.target.value)
                                        }
                                        placeholder="Judul lengkap peraturan"
                                        rows={3}
                                        disabled={processing}
                                    />
                                    {errors.tentang && (
                                        <p className="text-sm text-red-500">
                                            {errors.tentang}
                                        </p>
                                    )}
                                </div>

                                {isStep1Done && (
                                    <Button
                                        type="button"
                                        onClick={() => setActiveStep(2)}
                                        className="gap-2"
                                    >
                                        Lanjut{' '}
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        )}
                    </ContentCard>

                    {/* ── Step 2: Informasi Penerbitan ───────────────── */}
                    <ContentCard>
                        <button
                            type="button"
                            onClick={() =>
                                setActiveStep(activeStep === 2 ? 1 : 2)
                            }
                            className="flex w-full items-center gap-3 text-left"
                        >
                            <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200">
                                <BookOpen className="h-3.5 w-3.5" />
                            </div>
                            <div className="flex-1">
                                <p className="font-semibold text-neutral-900 dark:text-white">
                                    Informasi Penerbitan
                                    <span className="ml-2 text-xs font-normal text-neutral-500">
                                        (opsional)
                                    </span>
                                </p>
                                {activeStep !== 2 && (
                                    <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                                        {hasLN && nomorLn
                                            ? `LN No. ${nomorLn}/${tahunLn}`
                                            : hasBN && nomorBn
                                              ? `BN No. ${nomorBn}/${tahunBn}`
                                              : hasPenerbitanFields
                                                ? 'Belum diisi'
                                                : 'Tidak ada untuk kategori ini'}
                                    </p>
                                )}
                            </div>
                            <ChevronRight
                                className={`h-4 w-4 text-neutral-400 transition-transform ${activeStep === 2 ? 'rotate-90' : ''}`}
                            />
                        </button>

                        {activeStep === 2 && (
                            <div className="mt-5 space-y-5 border-t border-neutral-100 pt-5 dark:border-neutral-800">
                                {!hasPenerbitanFields && (
                                    <p className="rounded-lg bg-neutral-50 px-4 py-3 text-sm text-neutral-500 dark:bg-neutral-900 dark:text-neutral-400">
                                        Kategori yang dipilih tidak memiliki
                                        nomor Lembaran Negara atau Berita
                                        Negara.
                                    </p>
                                )}

                                {hasLN && (
                                    <div className="space-y-4 rounded-lg border border-blue-100 bg-blue-50/50 p-4 dark:border-blue-900/40 dark:bg-blue-900/10">
                                        <p className="text-sm font-semibold text-blue-800 dark:text-blue-300">
                                            Lembaran Negara (LN)
                                        </p>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-1.5">
                                                <Label htmlFor="tahun_ln">
                                                    Tahun LN
                                                    {nomorLn && (
                                                        <span className="text-red-500">
                                                            {' '}
                                                            *
                                                        </span>
                                                    )}
                                                </Label>
                                                <Input
                                                    id="tahun_ln"
                                                    type="number"
                                                    min={1900}
                                                    max={2100}
                                                    value={tahunLn}
                                                    onChange={(e) =>
                                                        setTahunLn(
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder={tahun}
                                                    disabled={processing}
                                                />
                                                {errors.tahun_ln && (
                                                    <p className="text-sm text-red-500">
                                                        {errors.tahun_ln}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="space-y-1.5">
                                                <Label htmlFor="nomor_ln">
                                                    Nomor LN
                                                </Label>
                                                <Input
                                                    id="nomor_ln"
                                                    value={nomorLn}
                                                    onChange={(e) =>
                                                        setNomorLn(
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Contoh: 103"
                                                    disabled={processing}
                                                />
                                                {errors.nomor_ln && (
                                                    <p className="text-sm text-red-500">
                                                        {errors.nomor_ln}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <p className="text-sm font-semibold text-blue-800 dark:text-blue-300">
                                            Tambahan Lembaran Negara (TLN)
                                        </p>
                                        <div className="space-y-1.5">
                                            <Label htmlFor="nomor_tln">
                                                Nomor TLN
                                            </Label>
                                            <Input
                                                id="nomor_tln"
                                                value={nomorTln}
                                                onChange={(e) =>
                                                    setNomorTln(e.target.value)
                                                }
                                                placeholder="Contoh: 5423"
                                                disabled={processing}
                                            />
                                            {errors.nomor_tln && (
                                                <p className="text-sm text-red-500">
                                                    {errors.nomor_tln}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {hasBN && (
                                    <div className="space-y-4 rounded-lg border border-teal-100 bg-teal-50/50 p-4 dark:border-teal-900/40 dark:bg-teal-900/10">
                                        <p className="text-sm font-semibold text-teal-800 dark:text-teal-300">
                                            Berita Negara (BN)
                                        </p>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div className="space-y-1.5">
                                                <Label htmlFor="tahun_bn">
                                                    Tahun BN
                                                    {nomorBn && (
                                                        <span className="text-red-500">
                                                            {' '}
                                                            *
                                                        </span>
                                                    )}
                                                </Label>
                                                <Input
                                                    id="tahun_bn"
                                                    type="number"
                                                    min={1900}
                                                    max={2100}
                                                    value={tahunBn}
                                                    onChange={(e) =>
                                                        setTahunBn(
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder={tahun}
                                                    disabled={processing}
                                                />
                                                {errors.tahun_bn && (
                                                    <p className="text-sm text-red-500">
                                                        {errors.tahun_bn}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="space-y-1.5">
                                                <Label htmlFor="nomor_bn">
                                                    Nomor BN
                                                </Label>
                                                <Input
                                                    id="nomor_bn"
                                                    value={nomorBn}
                                                    onChange={(e) =>
                                                        setNomorBn(
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Contoh: 1404"
                                                    disabled={processing}
                                                />
                                                {errors.nomor_bn && (
                                                    <p className="text-sm text-red-500">
                                                        {errors.nomor_bn}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <Button
                                    type="button"
                                    onClick={() => setActiveStep(3)}
                                    className="gap-2"
                                >
                                    Lanjut <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        )}
                    </ContentCard>

                    {/* ── Step 3: Jenis & Status ─────────────────────── */}
                    <ContentCard>
                        <button
                            type="button"
                            onClick={() =>
                                setActiveStep(activeStep === 3 ? 1 : 3)
                            }
                            className="flex w-full items-center gap-3 text-left"
                        >
                            <div
                                className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold ${isStep3Done ? 'bg-green-500 text-white' : 'bg-neutral-200 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200'}`}
                            >
                                <GitMerge className="h-3.5 w-3.5" />
                            </div>
                            <div className="flex-1">
                                <p className="font-semibold text-neutral-900 dark:text-white">
                                    Jenis & Status
                                </p>
                                {activeStep !== 3 && (
                                    <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                                        {jenis === 'pertama'
                                            ? 'Peraturan Pertama'
                                            : selectedInduk
                                              ? `Perubahan atas No. ${selectedInduk.nomor}/${selectedInduk.tahun}`
                                              : 'Peraturan Perubahan'}{' '}
                                        ·{' '}
                                        {status === 'aktif'
                                            ? 'Aktif'
                                            : 'Nonaktif'}
                                    </p>
                                )}
                            </div>
                            <ChevronRight
                                className={`h-4 w-4 text-neutral-400 transition-transform ${activeStep === 3 ? 'rotate-90' : ''}`}
                            />
                        </button>

                        {activeStep === 3 && (
                            <div className="mt-5 space-y-5 border-t border-neutral-100 pt-5 dark:border-neutral-800">
                                <div className="space-y-2">
                                    <Label>Jenis Peraturan</Label>
                                    <div className="grid grid-cols-2 gap-3">
                                        {(
                                            [
                                                [
                                                    'pertama',
                                                    'Peraturan Pertama',
                                                    'Peraturan baru, bukan revisi dari peraturan lain',
                                                ],
                                                [
                                                    'perubahan',
                                                    'Peraturan Perubahan',
                                                    'Mengubah/merevisi peraturan yang sudah ada',
                                                ],
                                            ] as const
                                        ).map(([val, label, desc]) => (
                                            <button
                                                key={val}
                                                type="button"
                                                onClick={() => {
                                                    setJenis(val);
                                                    if (val === 'pertama')
                                                        setIndukId(null);
                                                }}
                                                className={`rounded-lg border-2 p-3 text-left transition-all ${jenis === val ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700'}`}
                                            >
                                                <p
                                                    className={`text-sm font-semibold ${jenis === val ? 'text-blue-700 dark:text-blue-300' : 'text-neutral-800 dark:text-neutral-200'}`}
                                                >
                                                    {label}
                                                </p>
                                                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                                    {desc}
                                                </p>
                                            </button>
                                        ))}
                                    </div>
                                </div>

                                {jenis === 'perubahan' && (
                                    <div className="space-y-3 rounded-lg border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-800 dark:bg-amber-900/10">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                                Pilih Peraturan yang Diubah
                                                (Induk){' '}
                                                <span className="text-red-500">
                                                    *
                                                </span>
                                            </p>
                                            <Badge
                                                variant="outline"
                                                className="text-xs"
                                            >
                                                Menampilkan{' '}
                                                {filteredIndukList.length} dari{' '}
                                                {dasarHukumList.length}
                                            </Badge>
                                        </div>
                                        <p className="text-xs text-amber-700/90 dark:text-amber-300/80">
                                            Pilih ulang regulasi induk untuk
                                            memastikan narasi perubahan tetap
                                            akurat.
                                        </p>

                                        {selectedInduk && (
                                            <div className="rounded-lg border border-amber-300 bg-white p-3 shadow-sm dark:border-amber-700 dark:bg-amber-950/40">
                                                <p className="mb-2 text-xs font-semibold tracking-wide text-amber-700 uppercase dark:text-amber-300">
                                                    Peraturan Induk Terpilih
                                                </p>
                                                <div className="flex items-start gap-2">
                                                    <div className="flex-1 text-sm">
                                                        <p className="leading-relaxed font-medium text-neutral-900 dark:text-white">
                                                            {buildKategoriLabel(
                                                                selectedInduk.kategori,
                                                                selectedInduk.instansi,
                                                            )}{' '}
                                                            No.{' '}
                                                            {
                                                                selectedInduk.nomor
                                                            }{' '}
                                                            Tahun{' '}
                                                            {
                                                                selectedInduk.tahun
                                                            }
                                                        </p>
                                                        <p className="mt-1 line-clamp-2 text-neutral-500 dark:text-neutral-400">
                                                            {
                                                                selectedInduk.tentang
                                                            }
                                                        </p>
                                                        <div className="mt-2 flex flex-wrap items-center gap-2">
                                                            <Badge
                                                                variant="outline"
                                                                className={getKategoriBadgeClass(
                                                                    selectedInduk.kategori,
                                                                )}
                                                            >
                                                                {buildKategoriLabel(
                                                                    selectedInduk.kategori,
                                                                    selectedInduk.instansi,
                                                                )}
                                                            </Badge>
                                                            <Badge variant="secondary">
                                                                Tahun{' '}
                                                                {
                                                                    selectedInduk.tahun
                                                                }
                                                            </Badge>
                                                            {selectedInduk.perubahan_count >
                                                                0 && (
                                                                <Badge variant="secondary">
                                                                    {
                                                                        selectedInduk.perubahan_count
                                                                    }{' '}
                                                                    perubahan
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setIndukId(null)
                                                        }
                                                        className="shrink-0 rounded p-0.5 text-neutral-400 hover:text-neutral-700 dark:hover:text-neutral-200"
                                                    >
                                                        <X className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        )}

                                        <div className="relative">
                                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                                            <Input
                                                value={indukSearch}
                                                onChange={(e) =>
                                                    setIndukSearch(
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Cari nomor atau tentang..."
                                                className="pl-9"
                                                disabled={processing}
                                            />
                                        </div>

                                        <div className="max-h-72 space-y-2 overflow-y-auto pr-1">
                                            {filteredIndukList.length === 0 ? (
                                                <p className="py-4 text-center text-sm text-neutral-500">
                                                    Tidak ada peraturan
                                                    ditemukan
                                                </p>
                                            ) : (
                                                filteredIndukList.map((d) => (
                                                    <button
                                                        key={d.id}
                                                        type="button"
                                                        onClick={() => {
                                                            setIndukId(d.id);
                                                            setIndukSearch('');
                                                        }}
                                                        className={`group w-full rounded-lg border p-3 text-left text-sm transition-all ${indukId === d.id ? 'border-amber-400 bg-amber-100 shadow-sm dark:border-amber-600 dark:bg-amber-900/40' : 'border-amber-200/60 bg-white/90 hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/20 dark:hover:border-amber-700 dark:hover:bg-amber-900/20'}`}
                                                    >
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <Badge
                                                                variant="secondary"
                                                                className="shrink-0 text-xs"
                                                            >
                                                                {d.tahun}
                                                            </Badge>
                                                            <span className="font-medium text-neutral-900 dark:text-white">
                                                                No. {d.nomor}
                                                            </span>
                                                            <Badge
                                                                variant="outline"
                                                                className={getKategoriBadgeClass(
                                                                    d.kategori,
                                                                )}
                                                            >
                                                                {buildKategoriLabel(
                                                                    d.kategori,
                                                                    d.instansi,
                                                                )}
                                                            </Badge>
                                                            {d.perubahan_count >
                                                                0 && (
                                                                <Badge
                                                                    variant="secondary"
                                                                    className="text-xs"
                                                                >
                                                                    {
                                                                        d.perubahan_count
                                                                    }{' '}
                                                                    perubahan
                                                                </Badge>
                                                            )}
                                                            {indukId !==
                                                                d.id && (
                                                                <span className="ml-auto text-xs text-amber-700 opacity-0 transition-opacity group-hover:opacity-100 dark:text-amber-300">
                                                                    Pilih ini
                                                                </span>
                                                            )}
                                                        </div>
                                                        <p className="mt-1 line-clamp-2 text-neutral-600 dark:text-neutral-300">
                                                            {d.tentang}
                                                        </p>
                                                    </button>
                                                ))
                                            )}
                                        </div>
                                        {errors.induk_id && (
                                            <p className="text-sm text-red-500">
                                                {errors.induk_id}
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label>Status</Label>
                                    <div className="grid grid-cols-2 gap-3">
                                        {(
                                            [
                                                [
                                                    'aktif',
                                                    'Aktif',
                                                    'Akan digunakan dalam SK KPA',
                                                ],
                                                [
                                                    'nonaktif',
                                                    'Nonaktif',
                                                    'Tidak digunakan saat ini',
                                                ],
                                            ] as const
                                        ).map(([val, label, desc]) => (
                                            <button
                                                key={val}
                                                type="button"
                                                onClick={() => setStatus(val)}
                                                className={`rounded-lg border-2 p-3 text-left transition-all ${status === val ? (val === 'aktif' ? 'border-green-500 bg-green-50 dark:border-green-400 dark:bg-green-900/20' : 'border-neutral-400 bg-neutral-100 dark:border-neutral-500 dark:bg-neutral-800') : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700'}`}
                                            >
                                                <p
                                                    className={`text-sm font-semibold ${status === val ? (val === 'aktif' ? 'text-green-700 dark:text-green-300' : 'text-neutral-700 dark:text-neutral-300') : 'text-neutral-800 dark:text-neutral-200'}`}
                                                >
                                                    {label}
                                                </p>
                                                <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                                                    {desc}
                                                </p>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                    </ContentCard>

                    {/* ── Live Preview ───────────────────────────────── */}
                    {previewTeks && (
                        <ContentCard>
                            <div className="flex items-start gap-3">
                                <Eye className="mt-0.5 h-5 w-5 shrink-0 text-neutral-400" />
                                <div className="flex-1">
                                    <p className="mb-2 text-sm font-semibold text-neutral-700 dark:text-neutral-300">
                                        Preview Teks di SK
                                    </p>
                                    <p className="text-sm leading-relaxed text-neutral-800 dark:text-neutral-200">
                                        <span className="mr-1 text-neutral-500">
                                            1.
                                        </span>
                                        {previewTeks};
                                    </p>
                                </div>
                            </div>
                        </ContentCard>
                    )}

                    {/* ── Actions ────────────────────────────────────── */}
                    <div className="flex items-center justify-end gap-3">
                        <Button variant="outline" asChild className="gap-2">
                            <Link href="/dasar-hukum">
                                <X className="h-4 w-4" />
                                Batal
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                processing || !isStep1Done || !isStep3Done
                            }
                            className="min-w-[140px] gap-2"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />{' '}
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <Save className="h-4 w-4" /> Simpan
                                    Perubahan
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
