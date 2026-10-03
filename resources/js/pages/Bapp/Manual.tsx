import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { FileUpload } from '@/components/ui/file-upload';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import FileText from 'lucide-react/icons/file-text';
import Loader2 from 'lucide-react/icons/loader2';
import Search from 'lucide-react/icons/search';
import Upload from 'lucide-react/icons/upload';
import { useMemo, useState } from 'react';

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
    nomor_bapp_urut?: string | null;
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
    document_type?: 'regular' | 'stopped_petugas' | 'replacement_pkpp';
    replacement_termin_count?: number;
    nomor_bapp_suffix: string;
}

export default function Manual({
    tahun,
    termin,
    termin_hashed,
    termin_roman,
    bulan_label,
    persentase,
    spk_list,
    document_type = 'regular',
    replacement_termin_count = 0,
    nomor_bapp_suffix,
}: Props) {
    const [files, setFiles] = useState<Record<number, File | null>>({});
    const [nomor, setNomor] = useState<Record<number, string>>({});
    const [tanggal, setTanggal] = useState<Record<number, string>>({});
    const [uploading, setUploading] = useState<number | null>(null);
    const [search, setSearch] = useState('');

    const upload = (item: SpkItem) => {
        const file = files[item.spk_id];
        const nomorUrut = (
            nomor[item.spk_id] ??
            item.nomor_bapp_urut ??
            ''
        ).trim();

        if (!file || !nomorUrut) return;

        setUploading(item.spk_id);
        router.post(
            '/bapp/manual-upload',
            {
                spk_hashed_id: item.spk_hashed_id,
                termin,
                file,
                nomor_bapp: nomorUrut,
                tanggal_bapp: tanggal[item.spk_id] ?? item.tanggal_bapp ?? '',
                document_type,
                replacement_termin_count,
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

    const contextLabel =
        document_type === 'stopped_petugas'
            ? 'Petugas berhenti'
            : document_type === 'replacement_pkpp'
              ? `Petugas pengganti · ${replacement_termin_count} termin`
              : 'Petugas utama';

    const uploadedCount = spk_list.filter((item) =>
        Boolean(item.signed_file_path),
    ).length;

    const filteredItems = useMemo(() => {
        const keyword = search.trim().toLocaleLowerCase('id-ID');
        if (!keyword) return spk_list;

        return spk_list.filter((item) =>
            [item.petugas.nama, item.petugas.nik, item.nomor_spk]
                .filter(Boolean)
                .some((value) =>
                    String(value)
                        .toLocaleLowerCase('id-ID')
                        .includes(keyword),
                ),
        );
    }, [search, spk_list]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'BAPP SE2026', href: '/bapp' },
        {
            title: `Termin ${termin_roman}`,
            href: `/bapp/create?termin=${termin_hashed}&document_type=${document_type}&replacement_termin_count=${replacement_termin_count}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`BAPP SE2026 Termin ${termin_roman}`} />

            <div className="space-y-5">
                <PageHeader
                    title={`BAPP Termin ${termin_roman}`}
                    description={`${contextLabel} · ${bulan_label} ${tahun} · ${persentase}%`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/bapp" prefetch>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <div className="grid gap-3 md:grid-cols-3">
                    <ContentCard>
                        <p className="text-xs font-medium text-muted-foreground">
                            Dokumen
                        </p>
                        <p className="mt-1 text-2xl font-semibold">
                            {uploadedCount}/{spk_list.length}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            BAPP sudah diunggah
                        </p>
                    </ContentCard>
                    <ContentCard className="md:col-span-2">
                        <p className="text-xs font-medium text-muted-foreground">
                            Format nomor
                        </p>
                        <p className="mt-1 break-all font-mono text-sm font-semibold">
                            B-{'{nomor}'}
                            {nomor_bapp_suffix}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Anda cukup mengisi bagian nomor. SIMANTIK menyusun
                            kode BAPP secara otomatis.
                        </p>
                    </ContentCard>
                </div>

                <ContentCard>
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 className="font-semibold">Daftar petugas</h2>
                            <p className="text-sm text-muted-foreground">
                                Isi nomor, tanggal, lalu unggah PDF final pada
                                baris petugas yang sesuai.
                            </p>
                        </div>
                        <div className="relative w-full md:w-80">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Cari petugas, NIK, atau PK"
                                className="pl-9"
                            />
                        </div>
                    </div>
                </ContentCard>

                <div className="space-y-3">
                    {filteredItems.map((item) => {
                        const available = Boolean(item.signed_file_path);
                        const selected = files[item.spk_id] ?? null;
                        const nomorValue =
                            nomor[item.spk_id] ??
                            item.nomor_bapp_urut ??
                            '';

                        return (
                            <ContentCard key={item.spk_id}>
                                <div className="grid gap-4 xl:grid-cols-[minmax(220px,.7fr)_minmax(430px,1.25fr)_minmax(190px,.45fr)] xl:items-start">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="truncate font-semibold">
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
                                                    ? 'Sudah upload'
                                                    : 'Belum upload'}
                                            </Badge>
                                        </div>
                                        <p className="mt-1 truncate text-sm text-muted-foreground">
                                            {item.nomor_spk}
                                        </p>
                                        {item.signed_uploaded_at && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Terakhir {item.signed_uploaded_at}
                                            </p>
                                        )}
                                        {available && item.bapp_hashed_id && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                                className="mt-2 px-0"
                                            >
                                                <a
                                                    href={`/bapp/${item.bapp_hashed_id}/download-signed`}
                                                >
                                                    <FileText className="mr-2 h-4 w-4" />
                                                    Buka PDF
                                                </a>
                                            </Button>
                                        )}
                                    </div>

                                    <div className="space-y-3">
                                        <div>
                                            <label className="mb-1.5 block text-xs font-medium text-muted-foreground">
                                                Nomor BAPP
                                            </label>
                                            <div className="flex min-w-0 items-stretch rounded-md border border-input bg-background focus-within:ring-2 focus-within:ring-ring/30">
                                                <span className="flex items-center border-r border-input px-3 text-sm font-medium text-muted-foreground">
                                                    B-
                                                </span>
                                                <input
                                                    inputMode="numeric"
                                                    pattern="[0-9]*"
                                                    value={nomorValue}
                                                    onChange={(event) => {
                                                        const value =
                                                            event.target.value.replace(
                                                                /\D/g,
                                                                '',
                                                            );
                                                        setNomor((current) => ({
                                                            ...current,
                                                            [item.spk_id]: value,
                                                        }));
                                                    }}
                                                    placeholder="Nomor"
                                                    className="h-10 min-w-[72px] w-24 bg-transparent px-3 text-sm outline-none"
                                                />
                                                <span className="flex min-w-0 flex-1 items-center overflow-hidden border-l border-input px-3 text-xs text-muted-foreground">
                                                    <span className="truncate">
                                                        {nomor_bapp_suffix}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <FileUpload
                                            value={selected}
                                            maxSizeMb={20}
                                            label={
                                                available
                                                    ? 'Pilih PDF pengganti BAPP'
                                                    : 'Pilih atau jatuhkan PDF BAPP'
                                            }
                                            helperText="PDF final BAPP"
                                            onChange={(file) =>
                                                setFiles((current) => ({
                                                    ...current,
                                                    [item.spk_id]: file,
                                                }))
                                            }
                                        />
                                    </div>

                                    <div className="space-y-3">
                                        <div>
                                            <label className="mb-1.5 block text-xs font-medium text-muted-foreground">
                                                Tanggal BAPP
                                            </label>
                                            <DatePicker
                                                value={
                                                    tanggal[item.spk_id] ??
                                                    item.tanggal_bapp ??
                                                    ''
                                                }
                                                onChange={(value) =>
                                                    setTanggal((current) => ({
                                                        ...current,
                                                        [item.spk_id]: value,
                                                    }))
                                                }
                                                placeholder="Pilih tanggal"
                                            />
                                        </div>
                                        <Button
                                            className="w-full"
                                            onClick={() => upload(item)}
                                            disabled={
                                                !selected ||
                                                !nomorValue.trim() ||
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
                                            {available
                                                ? 'Perbarui BAPP'
                                                : 'Simpan BAPP'}
                                        </Button>
                                    </div>
                                </div>
                            </ContentCard>
                        );
                    })}

                    {filteredItems.length === 0 && (
                        <ContentCard>
                            <div className="py-10 text-center text-sm text-muted-foreground">
                                {spk_list.length === 0
                                    ? `Belum ada Perjanjian Kerja untuk Termin ${termin_roman}.`
                                    : 'Petugas tidak ditemukan.'}
                            </div>
                        </ContentCard>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
