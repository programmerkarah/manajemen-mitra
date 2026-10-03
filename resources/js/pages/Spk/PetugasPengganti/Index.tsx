import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import FileText from 'lucide-react/icons/file-text';
import FolderOpen from 'lucide-react/icons/folder-open';
import UploadCloud from 'lucide-react/icons/upload-cloud';

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
    petugas_berhenti_nama: string | null;
    petugas_pengganti_nama: string | null;
    pml_cover_nama: string | null;
    tanggal_berhenti: string | null;
    tanggal_mulai_pkpp: string | null;
    status: string;
    has_pkpp_contract: boolean;
    pkpp: PkppSummary | null;
}

interface IndexProps {
    replacements: ReplacementItem[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Perjanjian Kerja', href: '/spk' },
    { title: 'Pergantian Petugas SE2026', href: '#' },
];

function formatStatus(status: string): string {
    switch (status) {
        case 'pengganti_ditetapkan':
            return 'Pengganti Ditetapkan';
        case 'pml_cover':
            return 'PML Cover';
        case 'selesai':
            return 'Selesai';
        case 'dibatalkan':
            return 'Dibatalkan';
        default:
            return 'Draft';
    }
}

function formatScheme(code: string | null): string {
    if (!code) return '-';

    const number = code.replace('scheme_', '');
    return `Skema ${number}`;
}

export default function Index({ replacements }: IndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pergantian Petugas SE2026" />

            <div className="space-y-6 p-6">
                <PageHeader
                    title="Pergantian Petugas SE2026"
                    description="Inventaris alur petugas berhenti dan petugas pengganti tanpa mencampurkannya dengan kewajiban petugas utama."
                >
                    <Button variant="outline" asChild>
                        <Link href="/spk?mode=sensus-ekonomi" prefetch>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali ke Perjanjian Kerja
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <div className="space-y-4">
                        <div>
                            <h2 className="text-base font-semibold">
                                Alur dokumen pergantian
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Data pergantian menjadi pengikat dokumen. BAPP dan
                                BAST tetap diinventaris pada menu dokumennya
                                masing-masing agar arsip tidak terduplikasi.
                            </p>
                        </div>
                        <div className="grid gap-3 md:grid-cols-3">
                            <div className="rounded-xl border border-border bg-muted/20 p-4">
                                <p className="text-xs font-medium text-muted-foreground">
                                    1 · Petugas berhenti
                                </p>
                                <p className="mt-1 text-sm font-medium">
                                    Upload BAPP sesuai tanggal berhenti, lalu BAST
                                </p>
                                <div className="mt-3 flex gap-2">
                                    <Button size="sm" variant="outline" asChild>
                                        <Link href="/bapp" prefetch>
                                            BAPP
                                        </Link>
                                    </Button>
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href="/berita-acara?mode=sensus-ekonomi"
                                            prefetch
                                        >
                                            BAST
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                            <div className="rounded-xl border border-border bg-muted/20 p-4">
                                <p className="text-xs font-medium text-muted-foreground">
                                    2 · Petugas pengganti
                                </p>
                                <p className="mt-1 text-sm font-medium">
                                    Tetapkan skema kontrak dan inventaris PK pengganti
                                </p>
                                <p className="mt-2 text-xs text-muted-foreground">
                                    Skema otomatis mengikuti tanggal kontrak:
                                    Skema 1–2 = 2 termin, Skema 3–5 = 1 termin.
                                </p>
                            </div>
                            <div className="rounded-xl border border-border bg-muted/20 p-4">
                                <p className="text-xs font-medium text-muted-foreground">
                                    3 · Penyelesaian pengganti
                                </p>
                                <p className="mt-1 text-sm font-medium">
                                    Upload BAPP sesuai jumlah termin skema, lalu BAST
                                </p>
                                <div className="mt-3 flex gap-2">
                                    <Button size="sm" variant="outline" asChild>
                                        <Link href="/bapp" prefetch>
                                            BAPP
                                        </Link>
                                    </Button>
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href="/berita-acara?mode=sensus-ekonomi"
                                            prefetch
                                        >
                                            BAST
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </ContentCard>

                <ContentCard>
                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div className="text-sm font-medium">
                                Inventaris pergantian aktif
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Status di bawah membantu melihat bagian dokumen
                                yang masih belum lengkap.
                            </p>
                        </div>
                        <Badge variant="secondary" className="w-fit">
                            {replacements.length} pergantian
                        </Badge>
                    </div>
                </ContentCard>

                <div className="grid gap-4 lg:grid-cols-2">
                    {replacements.length === 0 ? (
                        <ContentCard className="lg:col-span-2">
                            <div className="flex flex-col items-center gap-3 py-12 text-center">
                                <FileText className="h-12 w-12 text-muted-foreground" />
                                <p className="text-lg font-medium">
                                    Belum ada data pergantian
                                </p>
                                <p className="max-w-xl text-sm text-muted-foreground">
                                    Pergantian akan muncul setelah petugas berhenti
                                    dan petugas pengganti dicatat pada workflow
                                    SE2026.
                                </p>
                            </div>
                        </ContentCard>
                    ) : (
                        replacements.map((replacement) => {
                            const pkpp = replacement.pkpp;

                            return (
                                <ContentCard key={replacement.id}>
                                    <div className="flex h-full flex-col gap-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-xs font-medium text-muted-foreground">
                                                    {replacement.petugas_berhenti_nama ??
                                                        'Petugas lama'}{' '}
                                                    → pengganti
                                                </p>
                                                <h3 className="mt-1 text-lg font-semibold">
                                                    {replacement.petugas_pengganti_nama ??
                                                        'Belum ditetapkan'}
                                                </h3>
                                            </div>
                                            <Badge variant="outline">
                                                {formatStatus(replacement.status)}
                                            </Badge>
                                        </div>

                                        <div className="grid gap-3 rounded-xl bg-muted/30 p-4 text-sm sm:grid-cols-2">
                                            <div>
                                                <p className="text-xs text-muted-foreground">
                                                    Tanggal berhenti
                                                </p>
                                                <p className="font-medium">
                                                    {replacement.tanggal_berhenti ??
                                                        '-'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-muted-foreground">
                                                    Mulai PKPP
                                                </p>
                                                <p className="font-medium">
                                                    {replacement.tanggal_mulai_pkpp ??
                                                        '-'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-muted-foreground">
                                                    Skema
                                                </p>
                                                <p className="font-medium">
                                                    {pkpp
                                                        ? `${formatScheme(
                                                              pkpp.skema_kode,
                                                          )} · ${pkpp.termin_count} termin`
                                                        : 'Belum ditetapkan'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-muted-foreground">
                                                    Nilai honor skema
                                                </p>
                                                <p className="font-medium">
                                                    {pkpp
                                                        ? `${pkpp.honor_ob.toLocaleString(
                                                              'id-ID',
                                                          )} OB`
                                                        : '-'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="grid gap-2 sm:grid-cols-3">
                                            <div className="rounded-lg border border-border px-3 py-2">
                                                <p className="text-xs text-muted-foreground">
                                                    Kontrak PKPP
                                                </p>
                                                <div className="mt-1 flex items-center gap-1.5 text-sm font-medium">
                                                    {replacement.has_pkpp_contract ? (
                                                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                                    ) : (
                                                        <FolderOpen className="h-4 w-4 text-muted-foreground" />
                                                    )}
                                                    {replacement.has_pkpp_contract
                                                        ? 'Tercatat'
                                                        : 'Belum'}
                                                </div>
                                            </div>
                                            <div className="rounded-lg border border-border px-3 py-2">
                                                <p className="text-xs text-muted-foreground">
                                                    PK terhubung
                                                </p>
                                                <div className="mt-1 flex items-center gap-1.5 text-sm font-medium">
                                                    {pkpp?.has_spk ? (
                                                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                                    ) : (
                                                        <FolderOpen className="h-4 w-4 text-muted-foreground" />
                                                    )}
                                                    {pkpp?.has_spk
                                                        ? 'Tersedia'
                                                        : 'Belum'}
                                                </div>
                                            </div>
                                            <div className="rounded-lg border border-border px-3 py-2">
                                                <p className="text-xs text-muted-foreground">
                                                    PDF PK final
                                                </p>
                                                <div className="mt-1 flex items-center gap-1.5 text-sm font-medium">
                                                    {pkpp?.pk_uploaded ? (
                                                        <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                                    ) : (
                                                        <UploadCloud className="h-4 w-4 text-muted-foreground" />
                                                    )}
                                                    {pkpp?.pk_uploaded
                                                        ? 'Sudah upload'
                                                        : 'Belum upload'}
                                                </div>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-2 pt-1">
                                            <Button asChild className="flex-1">
                                                <Link
                                                    href={`/spk/petugas-pengganti/${replacement.hashed_id}/pkpp-contracts/create`}
                                                    prefetch
                                                >
                                                    <FileText className="mr-2 h-4 w-4" />
                                                    {replacement.has_pkpp_contract
                                                        ? 'Kelola PK & Skema'
                                                        : 'Tetapkan PK & Skema'}
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                asChild
                                                className="flex-1"
                                            >
                                                <Link href="/bapp" prefetch>
                                                    Kelola BAPP
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                asChild
                                                className="flex-1"
                                            >
                                                <Link
                                                    href="/berita-acara?mode=sensus-ekonomi"
                                                    prefetch
                                                >
                                                    Kelola BAST
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                </ContentCard>
                            );
                        })
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
