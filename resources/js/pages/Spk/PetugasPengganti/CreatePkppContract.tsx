import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    downloadFileFromPost,
    previewFileFromPost,
} from '@/utils/downloadUtils';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, FileText } from 'lucide-react';
import { useState } from 'react';

interface ReplacementSummary {
    id: number;
    hashed_id: string;
    petugas_berhenti_nama: string | null;
    petugas_pengganti_nama: string | null;
    pml_cover_nama: string | null;
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
} | null;

interface CreateProps {
    replacement: ReplacementSummary;
    existing_contract: ExistingContract;
    action: string;
    default_tanggal_kontrak: string;
    default_tanggal_mulai_lapangan: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Perjanjian Kerja', href: '/spk' },
    { title: 'PK Petugas Pengganti', href: '/spk/petugas-pengganti' },
    { title: 'Generate PK Petugas Pengganti', href: '#' },
];

export default function CreatePkppContract({
    replacement,
    existing_contract,
    action,
    default_tanggal_kontrak,
    default_tanggal_mulai_lapangan,
}: CreateProps) {
    const [saving, setSaving] = useState(false);
    const [previewing, setPreviewing] = useState(false);
    const [downloading, setDownloading] = useState(false);
    const [formData, setFormData] = useState({
        tanggal_kontrak: default_tanggal_kontrak,
        tanggal_mulai_lapangan:
            default_tanggal_mulai_lapangan ??
            replacement.tanggal_mulai_pkpp ??
            '',
        status: 'draft',
    });

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

    const previewSpk = async (): Promise<void> => {
        if (
            !replacement.periode_hashed_id ||
            !replacement.petugas_pengganti_hashed_id
        ) {
            return;
        }

        setPreviewing(true);

        try {
            await previewFileFromPost(
                `/spk/periode/${replacement.periode_hashed_id}/petugas/${replacement.petugas_pengganti_hashed_id}/preview`,
                {
                    nomor_spk: replacement.petugas_pengganti_nama
                        ? `PKPP/${replacement.petugas_pengganti_nama}`
                        : 'PKPP',
                    tanggal_spk: formData.tanggal_kontrak,
                    response_mode: 'url',
                },
                `Preview_PKPP_${replacement.petugas_pengganti_nama ?? 'petugas'}.pdf`,
                {
                    responseMode: 'url',
                },
            );
        } finally {
            setPreviewing(false);
        }
    };

    const downloadSpk = async (): Promise<void> => {
        if (
            !replacement.periode_hashed_id ||
            !replacement.petugas_pengganti_hashed_id
        ) {
            return;
        }

        setDownloading(true);

        try {
            await downloadFileFromPost(
                `/spk/periode/${replacement.periode_hashed_id}/petugas/${replacement.petugas_pengganti_hashed_id}/preview`,
                {
                    nomor_spk: replacement.petugas_pengganti_nama
                        ? `PKPP/${replacement.petugas_pengganti_nama}`
                        : 'PKPP',
                    tanggal_spk: formData.tanggal_kontrak,
                    response_mode: 'binary',
                },
                `PKPP_${replacement.petugas_pengganti_nama ?? 'petugas'}.pdf`,
            );
        } finally {
            setDownloading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Generate PK Petugas Pengganti" />

            <div className="space-y-6 p-6">
                <PageHeader
                    title="Generate PK Petugas Pengganti"
                    description="Form ini dipakai untuk membuat PK baru petugas pengganti langsung dari workflow replacement."
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
                                    {existing_contract.tanggal_kontrak ?? '-'}
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
                                    {replacement.status}
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
                                        PML cover
                                    </div>
                                    <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {replacement.pml_cover_nama ?? '-'}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-neutral-500">
                                        Tanggal berhenti
                                    </div>
                                    <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {replacement.tanggal_berhenti ?? '-'}
                                    </div>
                                </div>
                                <div>
                                    <div className="text-neutral-500">
                                        Mulai PKPP
                                    </div>
                                    <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {replacement.tanggal_mulai_pkpp ?? '-'}
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

                            <div className="flex flex-wrap gap-2">
                                <Button
                                    onClick={handleSubmit}
                                    disabled={saving}
                                >
                                    {saving
                                        ? 'Menyimpan...'
                                        : 'Generate PK Petugas Pengganti'}
                                </Button>
                                <Button
                                    variant="secondary"
                                    onClick={previewSpk}
                                    disabled={previewing || saving}
                                >
                                    {previewing
                                        ? 'Memuat preview...'
                                        : 'Preview PK'}
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={downloadSpk}
                                    disabled={downloading || saving}
                                >
                                    {downloading ? 'Mengunduh...' : 'Unduh PK'}
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
                                    Jalur ini dipakai untuk menghasilkan PK baru
                                    petugas pengganti dari replacement yang
                                    sudah disetujui.
                                </p>
                                <p>
                                    Data tanggal mulai lapangan mengikuti
                                    replacement aktif, bukan input ulang dari
                                    BAPP.
                                </p>
                            </div>
                        </div>
                    </ContentCard>
                </div>
            </div>
        </AppLayout>
    );
}
