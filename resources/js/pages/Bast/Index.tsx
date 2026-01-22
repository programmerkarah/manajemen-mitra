import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDecryptedData } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileCheck, FileText, Plus } from 'lucide-react';

interface PeriodeData {
    bulan: number;
    bulan_label: string;
    tahun: number;
    total_spk: number;
    spk_with_bast: number;
    spk_without_bast: number;
    has_spk: boolean;
    all_completed: boolean;
    first_bast_hashed_id: string | null;
}

interface IndexProps {
    data: {
        encrypted: string;
    };
    filters: {
        search?: string;
    };
    active_year: number;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'BAST', href: '/bast' }];

export default function Index({ data, filters, active_year }: IndexProps) {
    const decryptedData = useDecryptedData<PeriodeData>(data.encrypted);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="BAST" />

            <div className="space-y-6">
                <PageHeader
                    title="Berita Acara Serah Terima (BAST)"
                    description={`Kelola BAST hasil pekerjaan petugas tahun ${active_year}`}
                />

                {/* Table */}
                <ContentCard>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead className="bg-neutral-50 dark:bg-neutral-800">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Periode
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Total Perjanjian Kerja
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        BAST Dibuat
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Belum BAST
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-center text-xs font-medium tracking-wider text-neutral-700 uppercase dark:text-neutral-300">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                                {decryptedData.map((item) => (
                                    <tr
                                        key={`${item.tahun}-${item.bulan}`}
                                        className="hover:bg-neutral-50 dark:hover:bg-neutral-800"
                                    >
                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                            <div className="font-medium">
                                                {item.bulan_label} {item.tahun}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap text-neutral-900 dark:text-neutral-100">
                                            {item.total_spk}
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap">
                                            <span className="text-green-600 dark:text-green-400 font-medium">
                                                {item.spk_with_bast}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap">
                                            <span className="text-orange-600 dark:text-orange-400 font-medium">
                                                {item.spk_without_bast}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap">
                                            {!item.has_spk ? (
                                                <Badge variant="secondary">
                                                    Tidak ada Perjanjian Kerja
                                                </Badge>
                                            ) : item.all_completed ? (
                                                <Badge variant="default">
                                                    <FileCheck className="mr-1 h-3 w-3" />
                                                    Selesai
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    <FileText className="mr-1 h-3 w-3" />
                                                    Perlu BAST
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-center text-sm whitespace-nowrap">
                                            <div className="flex items-center justify-center gap-2">
                                                {item.has_spk &&
                                                    !item.all_completed && (
                                                        <Link
                                                            href={`/bast/create?bulan=${item.bulan}&tahun=${item.tahun}`}
                                                        >
                                                            <Button size="sm">
                                                                <Plus className="mr-1 h-4 w-4" />
                                                                Generate BAST
                                                            </Button>
                                                        </Link>
                                                    )}
                                                {item.spk_with_bast > 0 &&
                                                    item.first_bast_hashed_id && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/bast/${item.first_bast_hashed_id}`}
                                                            >
                                                                <FileText className="mr-1 h-4 w-4" />
                                                                Detail BAST
                                                            </Link>
                                                        </Button>
                                                    )}
                                                {item.has_spk &&
                                                    item.all_completed &&
                                                    item.spk_with_bast === 0 && (
                                                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                            Tidak ada BAST
                                                        </span>
                                                    )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </ContentCard>
            </div>
        </AppLayout>
    );
}
