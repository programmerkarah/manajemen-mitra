import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import FileText from 'lucide-react/icons/file-text';
import Loader2 from 'lucide-react/icons/loader2';
import Upload from 'lucide-react/icons/upload';
import { useState } from 'react';

interface SpkItem {
    spk_id: number;
    spk_hashed_id: string;
    nomor_spk: string;
    petugas: {
        id: number | null;
        nama: string | null;
        nik: string;
    };
    has_bapp: boolean;
    bapp_hashed_id: string | null;
    nomor_bapp?: string | null;
    tanggal_bapp?: string | null;
    signed_file_path?: string | null;
    signed_uploaded_at?: string | null;
}

interface Props {
    tahun: number;
    termin: number;
    termin_hashed: string;
    termin_roman: string;
    bulan_label: string;
    persentase: number;
    spk_list: SpkItem[];
}

export default function Manual({
    tahun,
    termin,
    termin_hashed,
    termin_roman,
    bulan_label,
    persentase,
    spk_list,
}: Props) {
    const [files, setFiles] = useState<Record<number, File | null>>({});
    const [nomor, setNomor] = useState<Record<number, string>>({});
    const [tanggal, setTanggal] = useState<Record<number, string>>({});
    const [uploading, setUploading] = useState<number | null>(null);

    const upload = (item: SpkItem) => {
        const file = files[item.spk_id];
        if (!file) return;

        setUploading(item.spk_id);
        router.post(
            '/bapp/manual-upload',
            {
                spk_hashed_id: item.spk_hashed_id,
                termin,
                file,
                nomor_bapp: nomor[item.spk_id] ?? item.nomor_bapp ?? '',
                tanggal_bapp: tanggal[item.spk_id] ?? item.tanggal_bapp ?? '',
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => setUploading(null),
                onSuccess: () =>
                    setFiles((current) => ({
                        ...current,
                        [item.spk_id]: null,
                    })),
            },
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'BAPP SE2026', href: '/bapp' },
        {
            title: `Termin ${termin_roman}`,
            href: `/bapp/create?termin=${termin_hashed}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`BAPP SE2026 Termin ${termin_roman}`} />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title={`BAPP Termin ${termin_roman} — Upload Manual`}
                    description={`${bulan_label} ${tahun} · ${persentase}% · unggah PDF final per petugas`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/bapp" prefetch>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <div className="rounded-xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200">
                        SIMANTIK tidak membuat ulang isi BAPP. Nomor dan tanggal
                        di bawah hanya metadata pencarian; PDF yang diunggah
                        menjadi dokumen final yang tersedia untuk petugas.
                    </div>
                </ContentCard>

                <div className="space-y-4">
                    {spk_list.map((item) => {
                        const available = Boolean(item.signed_file_path);
                        const selected = files[item.spk_id] ?? null;
                        return (
                            <ContentCard key={item.spk_id}>
                                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(220px,.6fr)_minmax(160px,.35fr)_auto] lg:items-end">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="font-semibold">
                                                {item.petugas.nama ?? 'Petugas'}
                                            </h3>
                                            <Badge
                                                variant={
                                                    available
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {available
                                                    ? 'Tersedia'
                                                    : 'Belum diunggah'}
                                            </Badge>
                                        </div>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            PK {item.nomor_spk}
                                        </p>
                                        {item.signed_uploaded_at && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Upload terakhir:{' '}
                                                {item.signed_uploaded_at}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-xs font-medium text-muted-foreground">
                                            Nomor BAPP
                                        </label>
                                        <Input
                                            value={
                                                nomor[item.spk_id] ??
                                                item.nomor_bapp ??
                                                ''
                                            }
                                            onChange={(event) =>
                                                setNomor((current) => ({
                                                    ...current,
                                                    [item.spk_id]:
                                                        event.target.value,
                                                }))
                                            }
                                            placeholder="Opsional"
                                        />
                                    </div>

                                    <div>
                                        <label className="mb-1.5 block text-xs font-medium text-muted-foreground">
                                            Tanggal
                                        </label>
                                        <Input
                                            type="date"
                                            value={
                                                tanggal[item.spk_id] ??
                                                item.tanggal_bapp ??
                                                ''
                                            }
                                            onChange={(event) =>
                                                setTanggal((current) => ({
                                                    ...current,
                                                    [item.spk_id]:
                                                        event.target.value,
                                                }))
                                            }
                                        />
                                    </div>

                                    <div className="flex flex-wrap gap-2 lg:justify-end">
                                        {available && item.bapp_hashed_id && (
                                            <Button variant="outline" asChild>
                                                <a
                                                    href={`/bapp/${item.bapp_hashed_id}/download-signed`}
                                                >
                                                    <FileText className="mr-2 h-4 w-4" />
                                                    File
                                                </a>
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                <div className="mt-4 flex flex-col gap-3 border-t border-border pt-4 sm:flex-row sm:items-center">
                                    <Input
                                        type="file"
                                        accept="application/pdf,.pdf"
                                        className="min-w-0 flex-1"
                                        onChange={(event) =>
                                            setFiles((current) => ({
                                                ...current,
                                                [item.spk_id]:
                                                    event.target.files?.[0] ??
                                                    null,
                                            }))
                                        }
                                    />
                                    <Button
                                        onClick={() => upload(item)}
                                        disabled={
                                            !selected ||
                                            uploading === item.spk_id
                                        }
                                    >
                                        {uploading === item.spk_id ? (
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        ) : available ? (
                                            <Upload className="mr-2 h-4 w-4" />
                                        ) : (
                                            <CheckCircle2 className="mr-2 h-4 w-4" />
                                        )}
                                        {available ? 'Ganti PDF' : 'Upload PDF'}
                                    </Button>
                                </div>
                            </ContentCard>
                        );
                    })}

                    {spk_list.length === 0 && (
                        <ContentCard>
                            <div className="py-10 text-center text-sm text-muted-foreground">
                                Belum ada Perjanjian Kerja yang dapat dikaitkan
                                dengan Termin {termin_roman}.
                            </div>
                        </ContentCard>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
