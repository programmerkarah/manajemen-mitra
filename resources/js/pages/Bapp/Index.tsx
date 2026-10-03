import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import AlertCircle from 'lucide-react/icons/alert-circle';
import ArrowLeft from 'lucide-react/icons/arrow-left';
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

    if (!has_kegiatan) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="BAPP SE2026" />
                <div className="flex flex-col gap-6 p-6">
                    <PageHeader
                        title="BAPP SE2026"
                        description="Berita Acara Pemeriksaan Pekerjaan Sensus Ekonomi 2026"
                    />
                    <ContentCard>
                        <div className="flex flex-col items-center gap-3 py-12 text-center">
                            <AlertCircle className="h-12 w-12 text-yellow-500" />
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
            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title="BAPP SE2026"
                    description={`Inventaris dokumen manual BAPP Sensus Ekonomi 2026 · Tahun ${tahun}`}
                >
                    <Button variant="outline" asChild>
                        <Link href="/dashboard" prefetch>
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <div className="space-y-2 rounded-xl border border-blue-200 bg-blue-50/70 p-4 text-sm text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200">
                        <p className="font-medium">Alur dokumen dipisahkan agar tidak saling mengganggu.</p>
                        <p>
                            Petugas utama, petugas berhenti, dan petugas pengganti
                            disimpan sebagai konteks berbeda. Jika petugas lama
                            berhenti sebelum Termin II wajib, Termin II tidak lagi
                            dihitung sebagai dokumen yang harus tersedia.
                        </p>
                    </div>
                </ContentCard>

                <div className="space-y-5">
                    {workflows.map((workflow) => {
                        const Icon = getWorkflowIcon(workflow.document_type);
                        const hasItems = workflow.termin_data.some(
                            (item) => item.spk_count > 0,
                        );

                        return (
                            <ContentCard key={workflow.key}>
                                <div className="space-y-5">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="flex items-start gap-3">
                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                                <Icon className="h-5 w-5 text-muted-foreground" />
                                            </div>
                                            <div>
                                                <h2 className="text-lg font-semibold">
                                                    {workflow.label}
                                                </h2>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {workflow.description}
                                                </p>
                                            </div>
                                        </div>
                                        <Badge variant={hasItems ? 'secondary' : 'outline'}>
                                            {workflow.total_uploaded} upload · {workflow.total_spk} kewajiban
                                        </Badge>
                                    </div>

                                    {hasItems ? (
                                        <div className="grid gap-4 lg:grid-cols-2">
                                            {workflow.termin_data.map((termin) => {
                                                const uploaded = Math.min(
                                                    termin.bapp_count,
                                                    termin.spk_count,
                                                );
                                                const complete =
                                                    termin.spk_count > 0 &&
                                                    uploaded >= termin.spk_count;

                                                const href =
                                                    `/bapp/create?termin=${termin.termin_hashed}` +
                                                    `&document_type=${workflow.document_type}` +
                                                    `&replacement_termin_count=${workflow.replacement_termin_count}`;

                                                return (
                                                    <div
                                                        key={`${workflow.key}-${termin.termin}`}
                                                        className="rounded-xl border border-border bg-muted/20 p-4"
                                                    >
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p className="text-sm font-medium text-muted-foreground">
                                                                    Termin {termin.termin_roman} · {termin.bulan_label}
                                                                </p>
                                                                <p className="mt-1 text-base font-semibold">
                                                                    BAPP {termin.persentase}%
                                                                </p>
                                                            </div>
                                                            <Badge variant={complete ? 'default' : 'secondary'}>
                                                                {complete
                                                                    ? 'Lengkap'
                                                                    : `${uploaded}/${termin.spk_count}`}
                                                            </Badge>
                                                        </div>

                                                        <Button asChild className="mt-4 w-full">
                                                            <Link href={href} prefetch>
                                                                <FileUp className="mr-2 h-4 w-4" />
                                                                Kelola Upload
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    ) : (
                                        <div className="rounded-xl border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                                            Belum ada dokumen pada alur ini.
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
