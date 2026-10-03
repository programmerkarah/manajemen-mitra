import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import AlertCircle from 'lucide-react/icons/alert-circle';
import ArrowLeft from 'lucide-react/icons/arrow-left';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import FileText from 'lucide-react/icons/file-text';
import FileUp from 'lucide-react/icons/file-up';
import UserRoundCheck from 'lucide-react/icons/user-round-check';
import UserRoundMinus from 'lucide-react/icons/user-round-minus';
import UsersRound from 'lucide-react/icons/users-round';

interface TerminData {
    termin: number;
    termin_hashed: string;
    termin_roman: string;
    bulan: number;
    bulan_label: string;
    persentase: number;
    bapp_count: number;
    spk_count: number;
    is_complete: boolean;
}

interface WorkflowData {
    key: string;
    label: string;
    description: string;
    document_type: 'regular' | 'stopped_petugas' | 'replacement_pkpp';
    replacement_termin_count: number;
    termin_data: TerminData[];
    total_spk: number;
    total_uploaded: number;
}

interface IndexProps {
    tahun: number;
    termin_data: TerminData[];
    workflow_data?: WorkflowData[];
    has_kegiatan: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'BAPP SE2026', href: '/bapp' }];

const getWorkflowIcon = (type: WorkflowData['document_type']) => {
    if (type === 'stopped_petugas') return UserRoundMinus;
    if (type === 'replacement_pkpp') return UserRoundCheck;

    return UsersRound;
};

export default function Index({
    tahun,
    termin_data,
    workflow_data,
    has_kegiatan,
}: IndexProps) {
    const workflows: WorkflowData[] =
        workflow_data && workflow_data.length > 0
            ? workflow_data
            : [
                  {
                      key: 'regular',
                      label: 'Petugas utama',
                      description: 'BAPP petugas utama SE2026.',
                      document_type: 'regular',
                      replacement_termin_count: 0,
                      termin_data,
                      total_spk: termin_data.reduce(
                          (sum, item) => sum + item.spk_count,
                          0,
                      ),
                      total_uploaded: termin_data.reduce(
                          (sum, item) => sum + item.bapp_count,
                          0,
                      ),
                  },
              ];

    const totalRequired = workflows.reduce(
        (sum, workflow) => sum + workflow.total_spk,
        0,
    );
    const totalUploaded = workflows.reduce(
        (sum, workflow) => sum + workflow.total_uploaded,
        0,
    );
    const activeFlows = workflows.filter((workflow) =>
        workflow.termin_data.some((item) => item.spk_count > 0),
    ).length;

    if (!has_kegiatan) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="BAPP SE2026" />
                <div className="space-y-5">
                    <PageHeader
                        title="BAPP SE2026"
                        description="Inventaris Berita Acara Pemeriksaan Pekerjaan Sensus Ekonomi 2026"
                    />
                    <ContentCard>
                        <div className="flex flex-col items-center gap-3 py-12 text-center">
                            <AlertCircle className="h-10 w-10 text-amber-500" />
                            <p className="text-lg font-medium">
                                Kegiatan Sensus Ekonomi tidak ditemukan
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Pastikan kegiatan Sensus Ekonomi sudah
                                dikonfigurasi.
                            </p>
                        </div>
                    </ContentCard>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="BAPP SE2026" />

            <div className="space-y-5">
                <PageHeader
                    title="BAPP SE2026"
                    description={`Kelola upload BAPP manual per jenis petugas · ${tahun}`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/dashboard" prefetch>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <div className="grid gap-3 md:grid-cols-3">
                    <ContentCard>
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Dokumen tersedia
                                </p>
                                <p className="mt-1 text-2xl font-semibold">
                                    {totalUploaded}/{totalRequired}
                                </p>
                            </div>
                            <FileText className="h-6 w-6 text-muted-foreground" />
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Alur aktif
                                </p>
                                <p className="mt-1 text-2xl font-semibold">
                                    {activeFlows}
                                </p>
                            </div>
                            <UsersRound className="h-6 w-6 text-muted-foreground" />
                        </div>
                    </ContentCard>
                    <ContentCard>
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">
                                    Penomoran
                                </p>
                                <p className="mt-1 text-sm font-semibold">
                                    Input nomor saja
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Kode surat dibuat otomatis
                                </p>
                            </div>
                            <CheckCircle2 className="h-6 w-6 text-emerald-600" />
                        </div>
                    </ContentCard>
                </div>

                <ContentCard>
                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 className="font-semibold">Alur dokumen</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Petugas utama, petugas berhenti, dan petugas
                                pengganti dikelola terpisah sehingga kewajiban
                                dokumennya tidak saling memengaruhi.
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href="/spk/petugas-pengganti" prefetch>
                                <UserRoundCheck className="mr-2 h-4 w-4" />
                                Pergantian Petugas
                            </Link>
                        </Button>
                    </div>
                </ContentCard>

                <div className="grid gap-4 xl:grid-cols-2">
                    {workflows.map((workflow) => {
                        const Icon = getWorkflowIcon(workflow.document_type);
                        const availableTermins = workflow.termin_data.filter(
                            (item) => item.spk_count > 0,
                        );
                        const complete =
                            workflow.total_spk > 0 &&
                            workflow.total_uploaded >= workflow.total_spk;

                        return (
                            <ContentCard key={workflow.key}>
                                <div className="space-y-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex min-w-0 items-start gap-3">
                                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                                <Icon className="h-5 w-5 text-muted-foreground" />
                                            </span>
                                            <div className="min-w-0">
                                                <h3 className="font-semibold">
                                                    {workflow.label}
                                                </h3>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {workflow.description}
                                                </p>
                                            </div>
                                        </div>
                                        <Badge
                                            variant={
                                                complete
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {workflow.total_uploaded}/
                                            {workflow.total_spk}
                                        </Badge>
                                    </div>

                                    {availableTermins.length > 0 ? (
                                        <div className="space-y-2">
                                            {availableTermins.map((termin) => {
                                                const uploaded = Math.min(
                                                    termin.bapp_count,
                                                    termin.spk_count,
                                                );
                                                const terminComplete =
                                                    termin.spk_count > 0 &&
                                                    uploaded >= termin.spk_count;
                                                const href =
                                                    `/bapp/create?termin=${termin.termin_hashed}` +
                                                    `&document_type=${workflow.document_type}` +
                                                    `&replacement_termin_count=${workflow.replacement_termin_count}`;

                                                return (
                                                    <div
                                                        key={`${workflow.key}-${termin.termin}`}
                                                        className="flex flex-col gap-3 rounded-xl border border-border bg-muted/20 p-3 sm:flex-row sm:items-center sm:justify-between"
                                                    >
                                                        <div>
                                                            <div className="flex items-center gap-2">
                                                                <p className="text-sm font-medium">
                                                                    Termin{' '}
                                                                    {
                                                                        termin.termin_roman
                                                                    }
                                                                </p>
                                                                <Badge
                                                                    variant={
                                                                        terminComplete
                                                                            ? 'default'
                                                                            : 'outline'
                                                                    }
                                                                >
                                                                    {terminComplete
                                                                        ? 'Lengkap'
                                                                        : `${uploaded}/${termin.spk_count}`}
                                                                </Badge>
                                                            </div>
                                                            <p className="mt-1 text-xs text-muted-foreground">
                                                                {
                                                                    termin.bulan_label
                                                                }{' '}
                                                                ·{' '}
                                                                {
                                                                    termin.persentase
                                                                }
                                                                %
                                                            </p>
                                                        </div>

                                                        <Button
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={href}
                                                                prefetch
                                                            >
                                                                <FileUp className="mr-2 h-4 w-4" />
                                                                Kelola
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    ) : (
                                        <div className="rounded-xl border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                                            Belum ada petugas pada alur ini.
                                        </div>
                                    )}
                                </div>
                            </ContentCard>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
