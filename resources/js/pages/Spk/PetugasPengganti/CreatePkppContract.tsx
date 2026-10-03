import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { FileUpload } from '@/components/ui/file-upload';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import FileText from 'lucide-react/icons/file-text';
import { useState } from 'react';

interface ReplacementSummary {
    id: number;
    hashed_id: string;
    petugas_berhenti_nama: string | null;
    petugas_pengganti_nama: string | null;
    tanggal_berhenti: string | null;
    tanggal_mulai_pkpp: string | null;
    target_sisa: number;
    status: string;
    periode_hashed_id: string | null;
    petugas_pengganti_hashed_id: string | null;
}

type ExistingContract = {
    hashed_id: string;
    nomor_pkpp: string;
    tanggal_kontrak: string | null;
    tanggal_mulai_lapangan: string | null;
    status: string;
    spk_hashed_id?: string | null;
    spk_nomor_spk?: string | null;
    spk_signed_uploaded?: boolean;
} | null;

interface CreateProps {
    replacement: ReplacementSummary;
    existing_contract: ExistingContract;
    action: string;
    default_tanggal_kontrak: string;
    default_tanggal_mulai_lapangan: string | null;
}

const formatDate = (value: string | null | undefined): string => {
    if (!value) return '-';
    const date = new Date(`${value.slice(0, 10)}T00:00:00`);
    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString('id-ID', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          });
};

const statusLabel = (value: string): string =>
    value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Perjanjian Kerja', href: '/spk' },
    {
        title: 'Pergantian Petugas SE2026',
        href: '/spk/petugas-pengganti',
    },
    { title: 'Skema & PK Pengganti', href: '#' },
];

export default function CreatePkppContract({
    replacement,
    existing_contract,
    action,
    default_tanggal_kontrak,
    default_tanggal_mulai_lapangan,
}: CreateProps) {
    const [saving, setSaving] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [formData, setFormData] = useState({
        tanggal_kontrak: default_tanggal_kontrak,
        tanggal_mulai_lapangan:
            default_tanggal_mulai_lapangan ??
            replacement.tanggal_mulai_pkpp ??
            '',
        status: 'draft',
    });

    const schemePreview = (() => {
        if (!formData.tanggal_kontrak) return null;

        const date = formData.tanggal_kontrak;
        const year = Number(date.slice(0, 4)) || 2026;
        const rules = [
            {
                code: 'Skema 1',
                deadline: `${year}-06-26`,
                field: `${year}-07-01`,
                honor: 2,
                terms: 2,
                insurance: 'Juli–Agustus penuh',
            },
            {
                code: 'Skema 2',
                deadline: `${year}-07-02`,
                field: `${year}-07-06`,
                honor: 1.75,
                terms: 2,
                insurance: 'Juli proporsional, Agustus penuh',
            },
            {
                code: 'Skema 3',
                deadline: `${year}-07-14`,
                field: `${year}-07-18`,
                honor: 1.5,
                terms: 1,
                insurance: 'Juli proporsional, Agustus penuh',
            },
            {
                code: 'Skema 4',
                deadline: `${year}-07-21`,
                field: `${year}-07-25`,
                honor: 1.25,
                terms: 1,
                insurance: 'Juli proporsional, Agustus penuh',
            },
            {
                code: 'Skema 5',
                deadline: `${year}-07-27`,
                field: `${year}-08-01`,
                honor: 1,
                terms: 1,
                insurance: 'Agustus penuh',
            },
        ];

        return rules.find((rule) => date <= rule.deadline) ?? null;
    })();

    const handleSignedUpload = (file: File | null) => {
        if (!file || !existing_contract) return;

        setUploading(true);
        router.post(
            `/sensus-ekonomi/replacements/${replacement.hashed_id}/pkpp-contracts/upload-signed`,
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => setUploading(false),
            },
        );
    };

    const handleSubmit = () => {
        setSaving(true);

        router.post(
            action,
            {
                tanggal_kontrak: formData.tanggal_kontrak,
                tanggal_mulai_lapangan: formData.tanggal_mulai_lapangan,
                status: formData.status,
            },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Skema & PK Petugas Pengganti" />

            <div className="space-y-4">
                <PageHeader
                    title="Kelola PK Petugas Pengganti"
                    description="Tetapkan skema berdasarkan tanggal kontrak dan inventaris PDF PK final yang sudah disiapkan."
                >
                    <Button variant="outline" asChild>
                        <Link href="/spk/petugas-pengganti">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                {existing_contract && (
                    <ContentCard className="border-emerald-200/70 bg-emerald-50/70 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                        <div className="flex items-start gap-3">
                            <CheckCircle2 className="mt-0.5 h-5 w-5 text-emerald-600" />
                            <div className="space-y-1">
                                <div className="font-medium text-emerald-800 dark:text-emerald-200">
                                    PK Petugas Pengganti sudah pernah dibuat
                                </div>
                                <div className="text-sm text-emerald-700 dark:text-emerald-300">
                                    Nomor {existing_contract.nomor_pkpp} |
                                    Tanggal kontrak{' '}
                                    {formatDate(existing_contract.tanggal_kontrak)}
                                </div>
                            </div>
                        </div>
                    </ContentCard>
                )}

                <div className="grid gap-4 xl:grid-cols-3">
                    <ContentCard className="xl:col-span-2">
                        <div className="space-y-4">
                            <div className="flex items-center gap-2">
                                <Badge variant="secondary">
                                    Replacement #{replacement.id}
                                </Badge>
                                <Badge variant="outline">
                                    {statusLabel(replacement.status)}
                                </Badge>
                            </div>

                            <div className="grid gap-3 rounded-lg bg-neutral-50 p-4 text-sm md:grid-cols-2 dark:bg-neutral-800/40">
                                <div>
                                    <div className="text-neutral-500">
                                        Petugas berhenti
                                    </div>
                                    <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {replacement.petugas_berhenti_nama ??
                                            '-'}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-neutral-500">
                                        Petugas pengganti
                                    </div>
                                    <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {replacement.petugas_pengganti_nama ??
                                            '-'}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-neutral-500">
                                        Tanggal berhenti
                                    </div>
                                    <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {formatDate(replacement.tanggal_berhenti)}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-neutral-500">
                                        Mulai PKPP
                                    </div>
                                    <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {formatDate(replacement.tanggal_mulai_pkpp)}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-neutral-500">
                                        Target sisa
                                    </div>
                                    <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {replacement.target_sisa.toLocaleString(
                                            'id-ID',
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Tanggal Kontrak</Label>
                                    <DatePicker
                                        value={formData.tanggal_kontrak}
                                        onChange={(value) =>
                                            setFormData((prev) => ({
                                                ...prev,
                                                tanggal_kontrak: value,
                                            }))
                                        }
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label>Tanggal Mulai Lapangan</Label>
                                    <DatePicker
                                        value={formData.tanggal_mulai_lapangan}
                                        min={
                                            formData.tanggal_kontrak ||
                                            undefined
                                        }
                                        onChange={(value) =>
                                            setFormData((prev) => ({
                                                ...prev,
                                                tanggal_mulai_lapangan: value,
                                            }))
                                        }
                                    />
                                </div>
                            </div>

                            {schemePreview && (
                                <div className="rounded-xl border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p className="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                                {schemePreview.code}
                                            </p>
                                            <p className="mt-1 text-xs text-blue-700 dark:text-blue-300">
                                                Kontrak paling lambat{' '}
                                                {formatDate(
                                                    schemePreview.deadline,
                                                )} · mulai lapangan paling lambat{' '}
                                                {formatDate(
                                                    schemePreview.field,
                                                )}
                                            </p>
                                        </div>
                                        <Badge variant="secondary">
                                            {schemePreview.terms} termin ·{' '}
                                            {schemePreview.honor.toLocaleString(
                                                'id-ID',
                                            )}{' '}
                                            OB
                                        </Badge>
                                    </div>
                                    <p className="mt-2 text-xs text-blue-700 dark:text-blue-300">
                                        Asuransi: {schemePreview.insurance}
                                    </p>
                                </div>
                            )}

                            {existing_contract && (
                                <div className="space-y-2 rounded-xl border border-border bg-muted/20 p-4">
                                    <div>
                                        <p className="text-sm font-semibold">
                                            PDF PK final
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Upload dokumen PK yang sudah
                                            ditandatangani. File disimpan pada
                                            record PK yang terhubung.
                                        </p>
                                    </div>
                                    <FileUpload
                                        disabled={uploading}
                                        maxSizeMb={10}
                                        label={
                                            existing_contract.spk_signed_uploaded
                                                ? 'Pilih PDF pengganti PK final'
                                                : 'Pilih atau jatuhkan PDF PK final'
                                        }
                                        helperText={
                                            uploading
                                                ? 'Sedang mengunggah...'
                                                : 'PDF PK petugas pengganti'
                                        }
                                        onChange={handleSignedUpload}
                                    />
                                    {existing_contract.spk_signed_uploaded && (
                                        <div className="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                                            <CheckCircle2 className="h-4 w-4" />
                                            PDF PK final sudah tersimpan
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="flex flex-wrap gap-2">
                                <Button
                                    onClick={handleSubmit}
                                    disabled={saving}
                                >
                                    {saving
                                        ? 'Menyimpan...'
                                        : existing_contract
                                          ? 'Perbarui Data PK'
                                          : 'Simpan Data PK'}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={`/spk/petugas-pengganti/${replacement.hashed_id}/pkpp-contracts/create`}
                                    >
                                        <FileText className="mr-2 h-4 w-4" />
                                        Muat ulang form
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </ContentCard>

                    <ContentCard>
                        <div className="space-y-3">
                            <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                Ringkasan
                            </h3>
                            <div className="space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                                <p>
                                    Tanggal kontrak menentukan Skema 1–5 secara
                                    otomatis. Skema 1–2 memakai 2 termin; Skema
                                    3–5 memakai 1 termin. Setelah data skema
                                    disimpan, unggah PDF PK final yang sudah
                                    disiapkan.
                                </p>
                                <p>
                                    BAPP dan BAST tidak diunggah di halaman ini.
                                    Setelah PK tercatat, kelola BAPP pada menu
                                    BAPP dan BAST pada menu Berita Acara dengan
                                    konteks petugas pengganti.
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                </div>
            </div>
        </AppLayout>
    );
}
