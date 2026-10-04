import InputError from '@/components/input-error';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatNumber, formatRupiah } from '@/lib/format-number';
import type {
    AlokasiPetugas,
    BreadcrumbItem,
    Kegiatan,
    Petugas,
    RateHonor,
    Satuan,
    SharedData,
} from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import BriefcaseBusiness from 'lucide-react/icons/briefcase-business';
import CalendarDays from 'lucide-react/icons/calendar-days';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import CircleDollarSign from 'lucide-react/icons/circle-dollar-sign';
import Clock3 from 'lucide-react/icons/clock-3';
import FilePenLine from 'lucide-react/icons/file-pen-line';
import Pencil from 'lucide-react/icons/pencil';
import Send from 'lucide-react/icons/send';
import UserRound from 'lucide-react/icons/user-round';
import XCircle from 'lucide-react/icons/x-circle';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alokasi', href: '/alokasi' },
    { title: 'Detail Alokasi Petugas', href: '#' },
];

interface Props {
    alokasi: AlokasiPetugas & {
        hashed_id: string;
        status: string;
        bulan: number;
        tahun: number;
        jumlah_satuan: number;
        total_honor: number;
        catatan?: string | null;
        submitted_at?: string | null;
        approved_at?: string | null;
        catatan_approval?: string | null;
        kegiatan: Kegiatan & {
            penanggung_jawab: {
                id: number;
                name: string;
                email: string;
            };
            rate_honor: RateHonor & {
                satuan: Satuan;
            };
        };
        petugas: Petugas;
        submitted_by?: {
            id: number;
            name: string;
            email: string;
        };
        approved_by?: {
            id: number;
            name: string;
            email: string;
        };
    };
}

const MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft',
    diajukan: 'Menunggu Persetujuan',
    disetujui_pj: 'Disetujui PJ',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
};

function formatDate(date: string | null | undefined): string {
    if (!date) return '-';

    if (date.includes('T')) {
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(date));
    }

    const [year, month, day] = date.split('-').map(Number);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(year, month - 1, day));
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'disetujui') return 'default';
    if (status === 'ditolak') return 'destructive';
    if (status === 'draft') return 'secondary';

    return 'outline';
}

export default function Show({ alokasi }: Props) {
    const { auth } = usePage<SharedData>().props;
    const [showApprovalModal, setShowApprovalModal] = useState(false);
    const [approvalAction, setApprovalAction] = useState<
        'approve' | 'approve-pj' | 'reject'
    >('approve');

    const { data, setData, post, processing, errors, reset } = useForm({
        catatan_approval: '',
    });

    const handleSubmit = () => {
        router.post(`/alokasi/${alokasi.hashed_id}/submit`);
    };

    const handleApproval = (event: React.FormEvent) => {
        event.preventDefault();

        let endpoint = `/alokasi/${alokasi.hashed_id}/approve`;
        if (approvalAction === 'approve-pj') {
            endpoint = `/alokasi/${alokasi.hashed_id}/approve-pj`;
        } else if (approvalAction === 'reject') {
            endpoint = `/alokasi/${alokasi.hashed_id}/reject`;
        }

        post(endpoint, {
            onSuccess: () => {
                setShowApprovalModal(false);
                reset();
            },
        });
    };

    const openApprovalModal = (
        action: 'approve' | 'approve-pj' | 'reject',
    ) => {
        setApprovalAction(action);
        setShowApprovalModal(true);
    };

    const canEditDraft =
        alokasi.status === 'draft' && auth.activeRole?.name !== 'guest';
    const canSubmitDraft =
        alokasi.status === 'draft' && auth.activeRole?.name !== 'guest';
    const canApprovePj =
        alokasi.status === 'diajukan' &&
        auth.activeRole?.name === 'pj' &&
        auth.user.id === alokasi.kegiatan.penanggung_jawab?.id;
    const canApprove =
        ['diajukan', 'disetujui_pj'].includes(alokasi.status) &&
        auth.activeRole?.name === 'approver';

    const periodLabel = `${MONTHS[Number(alokasi.bulan) - 1] ?? alokasi.bulan} ${alokasi.tahun}`;
    const rate = Number(alokasi.kegiatan.rate_honor?.rate ?? 0);
    const volume = Number(alokasi.jumlah_satuan ?? 0);
    const totalHonor = Number(alokasi.total_honor ?? 0);
    const unit = alokasi.kegiatan.rate_honor?.satuan?.nama ?? 'satuan';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Alokasi - ${alokasi.petugas.nama}`} />

            <div className="space-y-6 p-4 sm:p-6">
                <PageHeader
                    title="Detail Alokasi Petugas"
                    description={`${alokasi.kegiatan.nama_kegiatan} · ${periodLabel}`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/alokasi">
                            <ArrowLeft className="mr-2 size-4" />
                            Kembali
                        </Link>
                    </Button>

                    {canEditDraft && (
                        <Button variant="outline" asChild>
                            <Link href={`/alokasi/${alokasi.hashed_id}/edit`}>
                                <Pencil className="mr-2 size-4" />
                                Edit
                            </Link>
                        </Button>
                    )}

                    {canSubmitDraft && (
                        <Button onClick={handleSubmit}>
                            <Send className="mr-2 size-4" />
                            Ajukan
                        </Button>
                    )}

                    {canApprovePj && (
                        <>
                            <Button
                                variant="outline"
                                onClick={() => openApprovalModal('reject')}
                            >
                                <XCircle className="mr-2 size-4" />
                                Tolak
                            </Button>
                            <Button
                                onClick={() =>
                                    openApprovalModal('approve-pj')
                                }
                            >
                                <CheckCircle2 className="mr-2 size-4" />
                                Setujui PJ
                            </Button>
                        </>
                    )}

                    {canApprove && (
                        <>
                            <Button
                                variant="outline"
                                onClick={() => openApprovalModal('reject')}
                            >
                                <XCircle className="mr-2 size-4" />
                                Tolak
                            </Button>
                            <Button
                                onClick={() => openApprovalModal('approve')}
                            >
                                <CheckCircle2 className="mr-2 size-4" />
                                Setujui Final
                            </Button>
                        </>
                    )}
                </PageHeader>

                <ContentCard>
                    <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant={statusVariant(alokasi.status)}>
                                    {STATUS_LABELS[alokasi.status] ??
                                        alokasi.status}
                                </Badge>
                                <Badge variant="outline">{periodLabel}</Badge>
                            </div>
                            <h2 className="mt-4 text-xl font-semibold">
                                {alokasi.petugas.nama}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {alokasi.kegiatan.nama_kegiatan}
                            </p>
                        </div>

                        <div className="rounded-2xl border bg-muted/30 px-5 py-4 lg:min-w-60 lg:text-right">
                            <p className="text-xs font-medium text-muted-foreground">
                                Total honor
                            </p>
                            <p className="mt-1 text-2xl font-semibold tracking-tight">
                                {formatRupiah(totalHonor)}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {formatNumber(volume)} {unit}
                            </p>
                        </div>
                    </div>
                </ContentCard>

                <div className="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                    <ContentCard>
                        <div className="mb-5 flex items-center gap-2">
                            <BriefcaseBusiness className="size-5 text-muted-foreground" />
                            <div>
                                <h2 className="font-semibold">
                                    Informasi Penugasan
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Konteks kegiatan, periode, dan beban kerja.
                                </p>
                            </div>
                        </div>

                        <dl className="grid gap-4 sm:grid-cols-2">
                            <div className="rounded-xl border p-4 sm:col-span-2">
                                <dt className="text-xs font-medium text-muted-foreground">
                                    Kegiatan
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {alokasi.kegiatan.nama_kegiatan}
                                </dd>
                            </div>
                            <div className="rounded-xl border p-4">
                                <dt className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                    <CalendarDays className="size-4" />
                                    Periode
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {periodLabel}
                                </dd>
                            </div>
                            <div className="rounded-xl border p-4">
                                <dt className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                    <UserRound className="size-4" />
                                    Penanggung Jawab
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {alokasi.kegiatan.penanggung_jawab?.name ??
                                        '-'}
                                </dd>
                                <dd className="mt-1 text-xs text-muted-foreground">
                                    {alokasi.kegiatan.penanggung_jawab?.email ??
                                        ''}
                                </dd>
                            </div>
                            <div className="rounded-xl border p-4">
                                <dt className="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                    <CircleDollarSign className="size-4" />
                                    Rate Honor
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {formatRupiah(rate)} / {unit}
                                </dd>
                                <dd className="mt-1 text-xs text-muted-foreground">
                                    {alokasi.kegiatan.rate_honor?.posisi ?? '-'}
                                </dd>
                            </div>
                            <div className="rounded-xl border p-4">
                                <dt className="text-xs font-medium text-muted-foreground">
                                    Volume
                                </dt>
                                <dd className="mt-1 font-medium">
                                    {formatNumber(volume)} {unit}
                                </dd>
                            </div>
                        </dl>

                        {alokasi.catatan && (
                            <div className="mt-4 rounded-xl border bg-muted/20 p-4">
                                <p className="text-xs font-medium text-muted-foreground">
                                    Catatan alokasi
                                </p>
                                <p className="mt-1 text-sm leading-6">
                                    {alokasi.catatan}
                                </p>
                            </div>
                        )}
                    </ContentCard>

                    <ContentCard>
                        <div className="mb-5 flex items-center gap-2">
                            <Clock3 className="size-5 text-muted-foreground" />
                            <div>
                                <h2 className="font-semibold">
                                    Timeline Persetujuan
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Jejak pengajuan dan keputusan alokasi.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-3">
                            {alokasi.submitted_by ? (
                                <div className="flex gap-3 rounded-xl border p-4">
                                    <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-500/10 text-blue-600">
                                        <Send className="size-4" />
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-sm font-medium">
                                            Diajukan
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {formatDate(alokasi.submitted_at)}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            oleh {alokasi.submitted_by.name}
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">
                                    Alokasi belum diajukan.
                                </div>
                            )}

                            {alokasi.approved_by && (
                                <div className="flex gap-3 rounded-xl border p-4">
                                    <div
                                        className={
                                            alokasi.status === 'ditolak'
                                                ? 'flex size-9 shrink-0 items-center justify-center rounded-full bg-red-500/10 text-red-600'
                                                : 'flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600'
                                        }
                                    >
                                        {alokasi.status === 'ditolak' ? (
                                            <XCircle className="size-4" />
                                        ) : (
                                            <CheckCircle2 className="size-4" />
                                        )}
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-sm font-medium">
                                            {alokasi.status === 'ditolak'
                                                ? 'Ditolak'
                                                : 'Disetujui'}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {formatDate(alokasi.approved_at)}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            oleh {alokasi.approved_by.name}
                                        </p>
                                        {alokasi.catatan_approval && (
                                            <p className="mt-2 rounded-lg bg-muted/60 p-2.5 text-xs leading-5">
                                                {alokasi.catatan_approval}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </ContentCard>
                </div>
            </div>

            <Dialog
                open={showApprovalModal}
                onOpenChange={(open) => {
                    setShowApprovalModal(open);
                    if (!open) reset();
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {approvalAction === 'approve'
                                ? 'Setujui Final Alokasi'
                                : approvalAction === 'approve-pj'
                                  ? 'Setujui Alokasi (PJ)'
                                  : 'Tolak Alokasi'}
                        </DialogTitle>
                        <DialogDescription>
                            Tambahkan catatan keputusan agar riwayat persetujuan
                            mudah ditelusuri.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleApproval} className="space-y-4">
                        <div>
                            <Textarea
                                id="catatan_approval"
                                rows={5}
                                value={data.catatan_approval}
                                onChange={(event) =>
                                    setData(
                                        'catatan_approval',
                                        event.target.value,
                                    )
                                }
                                placeholder={
                                    approvalAction === 'reject'
                                        ? 'Tuliskan alasan penolakan...'
                                        : 'Catatan opsional...'
                                }
                            />
                            <InputError
                                message={errors.catatan_approval}
                                className="mt-2"
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowApprovalModal(false)}
                                disabled={processing}
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                variant={
                                    approvalAction === 'reject'
                                        ? 'destructive'
                                        : 'default'
                                }
                                disabled={processing}
                            >
                                <FilePenLine className="mr-2 size-4" />
                                {processing
                                    ? 'Memproses...'
                                    : approvalAction === 'approve'
                                      ? 'Setujui Final'
                                      : approvalAction === 'approve-pj'
                                        ? 'Setujui PJ'
                                        : 'Tolak Alokasi'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
