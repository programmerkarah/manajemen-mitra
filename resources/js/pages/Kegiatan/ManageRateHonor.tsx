import { ContentCard } from '@/components/content-card';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useSsoFormPreserve } from '@/hooks/use-sso-form-preserve';
import AppLayout from '@/layouts/app-layout';
import {
    type BreadcrumbItem,
    type Kegiatan,
    type RateHonor,
    type Satuan,
} from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface RateHonorEntry {
    status_kepegawaian: 'non_organik' | 'organik';
    jenis_penugasan:
        | 'pcl_ppl'
        | 'pml'
        | 'koseka'
        | 'pengolahan'
        | 'pengawas_pengolahan';
    rate: number;
    satuan_id?: number | null;
    satuan_listing_id?: number | null;
    rate_listing?: number;
}

interface RateHonorPayload {
    rate_honors: RateHonorEntry[];
    satuan_id: number | null;
    kode_coa: string | null;
    satuan_listing_id?: number | null;
    satuan_pengolahan_pencacahan_id?: number | null;
    satuan_pengolahan_listing_id?: number | null;
}

interface Props {
    kegiatan: Kegiatan & {
        rate_honors?: RateHonor[];
    };
    satuans: Satuan[];
}

const normalizeSatuanValue = (
    value: number | string | null | undefined,
): string => {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return String(value);
};

const COA_PREFIX = '054.01.GG.';

// Kombinasi rate honor berdasarkan jenis kegiatan
const getCombinations = (jenisKegiatan: 'sensus' | 'survei') => {
    const combinations: Array<{
        status_kepegawaian: 'non_organik' | 'organik';
        jenis_penugasan:
            | 'pcl_ppl'
            | 'pml'
            | 'koseka'
            | 'pengolahan'
            | 'pengawas_pengolahan';
        label: string;
    }> = [];

    if (jenisKegiatan === 'survei') {
        // Survei: 8 kombinasi (both organik and non-organik support pengolahan and pemeriksa pengolahan)
        // Non-Organik: PCL/PPL, PML, Pengolahan, Pengawas Pengolahan (4)
        combinations.push(
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'pcl_ppl',
                label: 'Non-Organik - PCL/PPL',
            },
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'pml',
                label: 'Non-Organik - PML',
            },
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'koseka',
                label: 'Non-Organik - Koseka',
            },
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'pengolahan',
                label: 'Non-Organik - Pengolahan',
            },
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'pengawas_pengolahan',
                label: 'Non-Organik - Pengawas Pengolahan',
            },
        );
        // Organik: PCL/PPL, PML, Pengolahan, Pengawas Pengolahan (4)
        combinations.push(
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'pcl_ppl',
                label: 'Organik (PNS/PPPK) - PCL/PPL',
            },
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'pml',
                label: 'Organik (PNS/PPPK) - PML',
            },
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'koseka',
                label: 'Organik (PNS/PPPK) - Koseka',
            },
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'pengolahan',
                label: 'Organik (PNS/PPPK) - Pengolahan',
            },
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'pengawas_pengolahan',
                label: 'Organik (PNS/PPPK) - Pengawas Pengolahan',
            },
        );
    } else {
        // Sensus: 8 kombinasi
        // Non-Organik: PCL/PPL, PML, Pengolahan, Pengawas Pengolahan (4)
        combinations.push(
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'pcl_ppl',
                label: 'Non-Organik - PCL/PPL',
            },
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'pml',
                label: 'Non-Organik - PML',
            },
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'koseka',
                label: 'Non-Organik - Koseka',
            },
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'pengolahan',
                label: 'Non-Organik - Pengolahan',
            },
            {
                status_kepegawaian: 'non_organik',
                jenis_penugasan: 'pengawas_pengolahan',
                label: 'Non-Organik - Pengawas Pengolahan',
            },
        );
        // Organik: PCL/PPL, PML, Pengolahan, Pengawas Pengolahan (4)
        combinations.push(
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'pcl_ppl',
                label: 'Organik (PNS/PPPK) - PCL/PPL',
            },
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'pml',
                label: 'Organik (PNS/PPPK) - PML',
            },
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'koseka',
                label: 'Organik (PNS/PPPK) - Koseka',
            },
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'pengolahan',
                label: 'Organik (PNS/PPPK) - Pengolahan',
            },
            {
                status_kepegawaian: 'organik',
                jenis_penugasan: 'pengawas_pengolahan',
                label: 'Organik (PNS/PPPK) - Pengawas Pengolahan',
            },
        );
    }

    return combinations;
};

export default function ManageRateHonor({ kegiatan, satuans }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Kegiatan', href: '/kegiatan' },
        {
            title: kegiatan.nama_kegiatan,
            href: `/kegiatan/${kegiatan.hashed_id}`,
        },
        { title: 'Kelola Rate Honor', href: '#' },
    ];

    const isFasihOnly =
        kegiatan.metode_pendataan_pencacahan === 'CAPI' &&
        (!kegiatan.has_listing_updating ||
            kegiatan.metode_pendataan_listing === 'CAPI');
    const isSensus = kegiatan.jenis_kegiatan === 'sensus';

    const obSatuan = useMemo(
        () =>
            satuans.find((s) => {
                const nama = s.nama.toLowerCase().replace(/[-\s_/]/g, '');
                const satuanWithKode = s as Satuan & { kode?: string };
                const kode = (satuanWithKode.kode || '')
                    .toLowerCase()
                    .replace(/[-\s_/]/g, '');

                return (
                    nama === 'ob' ||
                    nama === 'orangbulan' ||
                    kode === 'ob' ||
                    kode === 'orangbulan'
                );
            }) ?? null,
        [satuans],
    );

    const obSatuanId = obSatuan?.id ? Number(obSatuan.id) : null;
    const obSatuanValue = obSatuanId !== null ? String(obSatuanId) : '';
    const obSatuanLabel = 'O-B (Orang/Bulan)';
    const initialPencacahanSatuanValue = useMemo(() => {
        if (isSensus) {
            return obSatuanValue;
        }

        return normalizeSatuanValue(kegiatan.rate_honors?.[0]?.satuan_id);
    }, [isSensus, kegiatan.rate_honors, obSatuanValue]);
    const initialListingSatuanValue = useMemo(() => {
        if (isSensus) {
            return obSatuanValue;
        }

        const listingSatuanId = kegiatan.rate_honors?.find(
            (rateHonor) => rateHonor.satuan_listing_id,
        )?.satuan_listing_id;

        return normalizeSatuanValue(
            listingSatuanId ?? kegiatan.rate_honors?.[0]?.satuan_id,
        );
    }, [isSensus, kegiatan.rate_honors, obSatuanValue]);

    // State untuk mengaktifkan/menonaktifkan jenis penugasan
    const [enabledJenisPenugasan, setEnabledJenisPenugasan] = useState(() => {
        const initial = {
            pcl_ppl: true, // Selalu aktif
            pml: true, // Selalu aktif
            koseka: false,
            pengolahan: false,
            pengawas_pengolahan: false,
        };

        // Cek apakah ada data existing untuk setiap jenis
        kegiatan.rate_honors?.forEach((rh) => {
            if (rh.jenis_penugasan === 'koseka' && rh.rate > 0) {
                initial.koseka = true;
            }
            if (
                !isFasihOnly &&
                rh.jenis_penugasan === 'pengolahan' &&
                rh.rate > 0
            ) {
                initial.pengolahan = true;
            }
            if (
                !isFasihOnly &&
                rh.jenis_penugasan === 'pengawas_pengolahan' &&
                rh.rate > 0
            ) {
                initial.pengawas_pengolahan = true;
            }
        });

        return initial;
    });

    const combinations = getCombinations(kegiatan.jenis_kegiatan);
    const hasPengolahanPenugasanEnabled =
        enabledJenisPenugasan.pengolahan ||
        enabledJenisPenugasan.pengawas_pengolahan;
    const activeCombinationCount = combinations.filter((combo) => {
        if (
            combo.jenis_penugasan === 'pcl_ppl' ||
            combo.jenis_penugasan === 'pml'
        ) {
            return true;
        }

        return enabledJenisPenugasan[combo.jenis_penugasan];
    }).length;

    // Initialize form data dengan rate honors yang sudah ada atau nilai kosong
    const [formData, setFormData] = useState(() => {
        const data: Record<string, string> = {};
        data['satuan_id'] = initialPencacahanSatuanValue;
        const existingCoa = kegiatan.kode_coa || '';
        data['kode_coa'] = existingCoa.startsWith(COA_PREFIX)
            ? existingCoa.substring(COA_PREFIX.length)
            : existingCoa;
        if (kegiatan.has_listing_updating) {
            data['satuan_listing_id'] = initialListingSatuanValue;
        }
        data['satuan_pengolahan_pencacahan_id'] = initialPencacahanSatuanValue;
        if (kegiatan.has_listing_updating) {
            data['satuan_pengolahan_listing_id'] = initialListingSatuanValue;
        }
        combinations.forEach((combo) => {
            const key = `${combo.status_kepegawaian}_${combo.jenis_penugasan}`;
            const existing = kegiatan.rate_honors?.find(
                (rh) =>
                    rh.status_kepegawaian === combo.status_kepegawaian &&
                    rh.jenis_penugasan === combo.jenis_penugasan,
            );
            data[key] = existing?.rate?.toString() || '';
            if (kegiatan.has_listing_updating) {
                data[`${key}_listing`] =
                    existing?.rate_listing?.toString() || '';
                data[`${key}_satuan_id`] = obSatuanValue;
                data[`${key}_satuan_listing_id`] = obSatuanValue;
            }
        });
        return data;
    });

    // Ambil error dari Inertia props jika ada (untuk flash message error backend)
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const pageProps = (window as any).routePageProps || {};
    const inertiaErrors = (pageProps.errors as Record<string, string>) || {};
    const [errors, setErrors] = useState<Record<string, string>>(inertiaErrors);
    const [processing, setProcessing] = useState(false);

    // Preserve form state across SSO redirects. When the SSO sync navigates
    // away and then returns to this same URL, the saved enabledJenisPenugasan
    // and formData are restored so the user doesn't lose their unsaved edits.
    useSsoFormPreserve(
        () => ({ enabledJenisPenugasan, formData }),
        ({ enabledJenisPenugasan: savedEJP, formData: savedFD }) => {
            setEnabledJenisPenugasan(savedEJP);
            setFormData(savedFD);
        },
    );

    // Format currency untuk display
    const formatCurrency = (value: string): string => {
        const number = value.replace(/\D/g, '');
        return number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    // Parse currency untuk submit
    const parseCurrency = (value: string): string => {
        return value.replace(/\./g, '');
    };

    const handleInputChange = (
        key: string,
        value: string,
        isDropdown = false,
    ) => {
        const raw = isDropdown ? value : parseCurrency(value);
        setFormData((prev) => ({
            ...prev,
            [key]: raw,
        }));
        if (errors[key]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[key];
                return newErrors;
            });
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        // Validasi satuan wajib diisi
        const newErrors: Record<string, string> = {};
        const selectedPencacahanSatuanId = isSensus
            ? obSatuanId
            : Number.parseInt(formData['satuan_id'] || '', 10);
        const selectedListingSatuanId = isSensus
            ? obSatuanId
            : Number.parseInt(formData['satuan_listing_id'] || '', 10);
        const selectedPengolahanPencacahanSatuanId = isSensus
            ? obSatuanId
            : Number.parseInt(
                  formData['satuan_pengolahan_pencacahan_id'] || '',
                  10,
              );
        const selectedPengolahanListingSatuanId = isSensus
            ? obSatuanId
            : Number.parseInt(
                  formData['satuan_pengolahan_listing_id'] || '',
                  10,
              );

        if (isSensus && obSatuanId === null) {
            newErrors['satuan_id'] =
                'Satuan O-B (Orang/Bulan) belum tersedia. Hubungi admin untuk menambahkan satuan O-B.';
        }
        if (!isSensus && Number.isNaN(selectedPencacahanSatuanId)) {
            newErrors['satuan_id'] = 'Satuan pencacahan wajib dipilih.';
        }
        if (
            kegiatan.has_listing_updating &&
            !isSensus &&
            Number.isNaN(selectedListingSatuanId)
        ) {
            newErrors['satuan_listing_id'] =
                'Satuan listing/updating wajib dipilih.';
        }
        if (
            hasPengolahanPenugasanEnabled &&
            !isSensus &&
            Number.isNaN(selectedPengolahanPencacahanSatuanId)
        ) {
            newErrors['satuan_pengolahan_pencacahan_id'] =
                'Satuan pengolahan dokumen pencacahan wajib dipilih.';
        }
        if (
            hasPengolahanPenugasanEnabled &&
            kegiatan.has_listing_updating &&
            !isSensus &&
            Number.isNaN(selectedPengolahanListingSatuanId)
        ) {
            newErrors['satuan_pengolahan_listing_id'] =
                'Satuan pengolahan dokumen listing/updating wajib dipilih.';
        }
        if (Object.keys(newErrors).length > 0) {
            setProcessing(false);
            setErrors(newErrors);
            return;
        }

        // Transform data ke format yang dibutuhkan backend
        // Filter hanya kombinasi yang enabled
        const rateHonors = combinations
            .filter((combo) => {
                if (
                    combo.jenis_penugasan === 'pcl_ppl' ||
                    combo.jenis_penugasan === 'pml'
                ) {
                    return true;
                }
                return enabledJenisPenugasan[combo.jenis_penugasan];
            })
            .map((combo) => {
                const key = `${combo.status_kepegawaian}_${combo.jenis_penugasan}`;
                const rateValue = parseInt(formData[key], 10);
                const isPengolahanPenugasan =
                    combo.jenis_penugasan === 'pengolahan' ||
                    combo.jenis_penugasan === 'pengawas_pengolahan';
                const selectedSatuanId = isPengolahanPenugasan
                    ? selectedPengolahanPencacahanSatuanId
                    : selectedPencacahanSatuanId;
                const entry: RateHonorEntry = {
                    status_kepegawaian: combo.status_kepegawaian,
                    jenis_penugasan: combo.jenis_penugasan,
                    rate: isNaN(rateValue) ? 0 : rateValue,
                    satuan_id:
                        selectedSatuanId === null ||
                        Number.isNaN(selectedSatuanId)
                            ? null
                            : selectedSatuanId,
                };

                if (kegiatan.has_listing_updating) {
                    const rateListingValue = parseInt(
                        formData[`${key}_listing`],
                        10,
                    );
                    const selectedListingSatuan = isPengolahanPenugasan
                        ? selectedPengolahanListingSatuanId
                        : selectedListingSatuanId;
                    entry.rate_listing = isNaN(rateListingValue)
                        ? 0
                        : rateListingValue;
                    entry.satuan_listing_id =
                        selectedListingSatuan === null ||
                        Number.isNaN(selectedListingSatuan)
                            ? null
                            : selectedListingSatuan;
                }

                return entry;
            });

        const payload: RateHonorPayload = {
            rate_honors: rateHonors,
            satuan_id:
                selectedPencacahanSatuanId === null ||
                isNaN(selectedPencacahanSatuanId)
                    ? null
                    : selectedPencacahanSatuanId,
            kode_coa: formData['kode_coa']
                ? COA_PREFIX + formData['kode_coa']
                : null,
        };

        if (kegiatan.has_listing_updating) {
            payload.satuan_listing_id =
                selectedListingSatuanId === null ||
                isNaN(selectedListingSatuanId)
                    ? null
                    : selectedListingSatuanId;
        }

        payload.satuan_pengolahan_pencacahan_id =
            selectedPengolahanPencacahanSatuanId === null ||
            isNaN(selectedPengolahanPencacahanSatuanId)
                ? null
                : selectedPengolahanPencacahanSatuanId;

        if (kegiatan.has_listing_updating) {
            payload.satuan_pengolahan_listing_id =
                selectedPengolahanListingSatuanId === null ||
                isNaN(selectedPengolahanListingSatuanId)
                    ? null
                    : selectedPengolahanListingSatuanId;
        }

        router.post(
            `/kegiatan/${kegiatan.hashed_id}/rate-honor/bulk`,
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            payload as unknown as any,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setProcessing(false);
                },
                onError: (errors) => {
                    setProcessing(false);
                    setErrors(errors as Record<string, string>);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Kelola Rate Honor - ${kegiatan.nama_kegiatan}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Kelola Rate Honor"
                    description={`${kegiatan.nama_kegiatan} (${kegiatan.kode_kegiatan}) · Jenis Kegiatan: ${kegiatan.jenis_kegiatan === 'sensus' ? 'Sensus' : 'Survei'} · ${activeCombinationCount} kombinasi rate honor aktif`}
                >
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="w-full sm:w-auto"
                    >
                        <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={handleSubmit} className="max-w-full min-w-0">
                    <ContentCard
                        className="max-w-full min-w-0 overflow-hidden"
                        padding="none"
                    >
                        <div className="border-b border-neutral-200/30 bg-blue-500/10 px-4 py-3 sm:px-6 sm:py-4 dark:border-neutral-700/30 dark:bg-blue-500/5">
                            <div className="flex items-start gap-3">
                                <svg
                                    className="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <div>
                                    <h3 className="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                        Petunjuk Pengisian
                                    </h3>
                                    <p className="mt-1 text-sm text-blue-800 dark:text-blue-200">
                                        Lengkapi rate honor untuk semua jenis
                                        penugasan sesuai dengan jenis kegiatan{' '}
                                        <span className="font-semibold">
                                            {kegiatan.jenis_kegiatan ===
                                            'sensus'
                                                ? 'Sensus'
                                                : 'Survei'}
                                        </span>
                                        . Rate honor ini akan digunakan untuk
                                        menghitung honor petugas dalam kegiatan
                                        ini.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid max-w-full min-w-0 gap-px border-b border-neutral-200/30 bg-neutral-200/30 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)] dark:border-neutral-700/30 dark:bg-neutral-700/30">
                            <div className="min-w-0 bg-white/30 px-4 py-4 sm:px-6 sm:py-5 dark:bg-neutral-900/30">
                                <h3 className="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                                    Kode CoA (Beban Anggaran)
                                </h3>
                                <div className="space-y-2">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Kode CoA
                                        <span className="ml-1 text-xs text-gray-500 dark:text-gray-400">
                                            (Berlaku sama untuk listing dan
                                            pencacahan)
                                        </span>
                                    </label>
                                    <div className="flex rounded-md shadow-sm">
                                        <span className="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 select-none dark:border-neutral-700/30 dark:bg-neutral-800/80 dark:text-neutral-300">
                                            {COA_PREFIX}
                                        </span>
                                        <input
                                            type="text"
                                            value={formData['kode_coa'] || ''}
                                            onChange={(e) =>
                                                handleInputChange(
                                                    'kode_coa',
                                                    e.target.value,
                                                    true,
                                                )
                                            }
                                            placeholder="2905.BMA.004.005.A.521211"
                                            className="block min-w-0 flex-1 rounded-none rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-neutral-700/30 dark:bg-neutral-800/60 dark:text-white dark:placeholder-neutral-400"
                                        />
                                    </div>
                                    {errors['kode_coa'] && (
                                        <InputError
                                            message={errors['kode_coa']}
                                        />
                                    )}
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Kode CoA akan digunakan dalam lampiran
                                        Perjanjian Kerja. Prefix{' '}
                                        <span className="font-medium">
                                            054.01.GG.
                                        </span>{' '}
                                        sudah ditetapkan untuk honor.
                                    </p>
                                </div>
                            </div>

                            <div className="min-w-0 bg-white/30 px-4 py-4 sm:px-6 sm:py-5 dark:bg-neutral-900/30">
                                <h3 className="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                                    Aktifkan Jenis Penugasan
                                </h3>
                                <div className="space-y-3">
                                    <label className="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            checked={
                                                enabledJenisPenugasan.pcl_ppl
                                            }
                                            disabled
                                            className="size-4 rounded border-gray-300 text-blue-600 opacity-50 dark:border-gray-600"
                                        />
                                        <span className="text-sm text-gray-700 dark:text-gray-300">
                                            PCL/PPL (Wajib)
                                        </span>
                                    </label>
                                    <label className="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            checked={enabledJenisPenugasan.pml}
                                            disabled
                                            className="size-4 rounded border-gray-300 text-blue-600 opacity-50 dark:border-gray-600"
                                        />
                                        <span className="text-sm text-gray-700 dark:text-gray-300">
                                            PML (Wajib)
                                        </span>
                                    </label>
                                    {isSensus ? (
                                        <label className="flex cursor-pointer items-center gap-3">
                                            <input
                                                type="checkbox"
                                                checked={
                                                    enabledJenisPenugasan.koseka
                                                }
                                                onChange={(e) =>
                                                    setEnabledJenisPenugasan({
                                                        ...enabledJenisPenugasan,
                                                        koseka: e.target
                                                            .checked,
                                                    })
                                                }
                                                className="size-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                                            />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                                Koseka (Koordinator Sensus
                                                Kecamatan)
                                            </span>
                                        </label>
                                    ) : null}
                                    {!isFasihOnly ? (
                                        <>
                                            <label className="flex cursor-pointer items-center gap-3">
                                                <input
                                                    type="checkbox"
                                                    checked={
                                                        enabledJenisPenugasan.pengolahan
                                                    }
                                                    onChange={(e) =>
                                                        setEnabledJenisPenugasan(
                                                            {
                                                                ...enabledJenisPenugasan,
                                                                pengolahan:
                                                                    e.target
                                                                        .checked,
                                                            },
                                                        )
                                                    }
                                                    className="size-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                                    Petugas Pengolahan
                                                </span>
                                            </label>
                                            <label className="flex cursor-pointer items-center gap-3">
                                                <input
                                                    type="checkbox"
                                                    checked={
                                                        enabledJenisPenugasan.pengawas_pengolahan
                                                    }
                                                    onChange={(e) =>
                                                        setEnabledJenisPenugasan(
                                                            {
                                                                ...enabledJenisPenugasan,
                                                                pengawas_pengolahan:
                                                                    e.target
                                                                        .checked,
                                                            },
                                                        )
                                                    }
                                                    className="size-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                                                />
                                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                                    Pengawas Pengolahan
                                                </span>
                                            </label>
                                        </>
                                    ) : (
                                        <p className="text-sm text-amber-700 dark:text-amber-300">
                                            Metode pendataan FASIH aktif, tidak
                                            ada jenis penugasan pengolahan yang
                                            dapat diaktifkan.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="space-y-5 px-4 py-4 sm:px-6 sm:py-5">
                            {kegiatan.has_listing_updating && (
                                <>
                                    <h4 className="text-sm font-semibold text-gray-900 sm:text-base dark:text-white">
                                        Rate Honor Listing/Updating
                                    </h4>
                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Satuan Listing/Updating
                                        </label>
                                        {isSensus ? (
                                            <input
                                                type="text"
                                                value={obSatuanLabel}
                                                disabled
                                                className="block w-full max-w-xs rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            />
                                        ) : (
                                            <Select
                                                value={
                                                    formData[
                                                        'satuan_listing_id'
                                                    ] || ''
                                                }
                                                onValueChange={(val) =>
                                                    handleInputChange(
                                                        'satuan_listing_id',
                                                        val,
                                                        true,
                                                    )
                                                }
                                            >
                                                <SelectTrigger className="w-full sm:w-56">
                                                    <SelectValue placeholder="Pilih satuan" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {satuans.map((satuan) => (
                                                        <SelectItem
                                                            key={satuan.id}
                                                            value={String(
                                                                satuan.id,
                                                            )}
                                                        >
                                                            {satuan.kode
                                                                ? `${satuan.kode} - ${satuan.nama}`
                                                                : satuan.nama}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}
                                    </div>
                                    {errors['satuan_listing_id'] && (
                                        <InputError
                                            message={
                                                errors['satuan_listing_id']
                                            }
                                        />
                                    )}
                                    {hasPengolahanPenugasanEnabled && (
                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                                Satuan Pengolahan Dokumen
                                                Listing/Updating
                                            </label>
                                            {isSensus ? (
                                                <input
                                                    type="text"
                                                    value={obSatuanLabel}
                                                    disabled
                                                    className="block w-full max-w-xs rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                />
                                            ) : (
                                                <Select
                                                    value={
                                                        formData[
                                                            'satuan_pengolahan_listing_id'
                                                        ] || ''
                                                    }
                                                    onValueChange={(val) =>
                                                        handleInputChange(
                                                            'satuan_pengolahan_listing_id',
                                                            val,
                                                            true,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="w-full sm:w-56">
                                                        <SelectValue placeholder="Pilih satuan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {satuans.map(
                                                            (satuan) => (
                                                                <SelectItem
                                                                    key={
                                                                        satuan.id
                                                                    }
                                                                    value={String(
                                                                        satuan.id,
                                                                    )}
                                                                >
                                                                    {satuan.kode
                                                                        ? `${satuan.kode} - ${satuan.nama}`
                                                                        : satuan.nama}
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                            )}
                                        </div>
                                    )}
                                    {errors['satuan_pengolahan_listing_id'] && (
                                        <InputError
                                            message={
                                                errors[
                                                    'satuan_pengolahan_listing_id'
                                                ]
                                            }
                                        />
                                    )}
                                    <div className="w-full max-w-full touch-pan-x overflow-x-auto rounded-2xl">
                                        <table className="w-full min-w-[760px] border-collapse">
                                            <thead>
                                                <tr className="border-b border-neutral-200/20 bg-white/60 backdrop-blur-sm dark:border-neutral-700/30 dark:bg-neutral-900/60">
                                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-700 uppercase dark:text-gray-300">
                                                        No
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-700 uppercase dark:text-gray-300">
                                                        Status Kepegawaian
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-700 uppercase dark:text-gray-300">
                                                        Jenis Penugasan
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-700 uppercase dark:text-gray-300">
                                                        Rate Honor Listing (Rp){' '}
                                                        <span className="text-red-500">
                                                            *
                                                        </span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-neutral-200/20 bg-white/30 dark:divide-neutral-700/20 dark:bg-neutral-800/30">
                                                {combinations
                                                    .filter(
                                                        (combo) =>
                                                            combo.jenis_penugasan ===
                                                                'pcl_ppl' ||
                                                            combo.jenis_penugasan ===
                                                                'pml' ||
                                                            enabledJenisPenugasan[
                                                                combo
                                                                    .jenis_penugasan
                                                            ],
                                                    )
                                                    .map((combo, index) => {
                                                        const key = `${combo.status_kepegawaian}_${combo.jenis_penugasan}_listing`;
                                                        const statusLabel =
                                                            combo.status_kepegawaian ===
                                                            'organik'
                                                                ? 'Organik (PNS/PPPK)'
                                                                : 'Non-Organik';
                                                        const penugasanLabels: Record<
                                                            string,
                                                            string
                                                        > = {
                                                            pcl_ppl: 'PCL/PPL',
                                                            pml: 'PML',
                                                            koseka: 'Koseka (Koordinator Sensus Kecamatan)',
                                                            pengolahan:
                                                                'Petugas Pengolahan',
                                                            pengawas_pengolahan:
                                                                'Pengawas Pengolahan',
                                                        };
                                                        const penugasanLabel =
                                                            penugasanLabels[
                                                                combo
                                                                    .jenis_penugasan
                                                            ] ||
                                                            combo.jenis_penugasan;

                                                        return (
                                                            <tr
                                                                key={key}
                                                                className="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                                            >
                                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                                                    {index + 1}
                                                                </td>
                                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                                                    {
                                                                        statusLabel
                                                                    }
                                                                </td>
                                                                <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                                                    {
                                                                        penugasanLabel
                                                                    }
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap">
                                                                    <input
                                                                        type="text"
                                                                        value={
                                                                            formData[
                                                                                key
                                                                            ]
                                                                                ? formatCurrency(
                                                                                      formData[
                                                                                          key
                                                                                      ],
                                                                                  )
                                                                                : ''
                                                                        }
                                                                        onChange={(
                                                                            e,
                                                                        ) =>
                                                                            handleInputChange(
                                                                                key,
                                                                                e
                                                                                    .target
                                                                                    .value,
                                                                            )
                                                                        }
                                                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                                        placeholder="0"
                                                                    />
                                                                    {errors[
                                                                        key
                                                                    ] && (
                                                                        <InputError
                                                                            message={
                                                                                errors[
                                                                                    key
                                                                                ]
                                                                            }
                                                                            className="mt-1"
                                                                        />
                                                                    )}
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                            </tbody>
                                        </table>
                                    </div>
                                </>
                            )}

                            <h4 className="text-sm font-semibold text-gray-900 sm:text-base dark:text-white">
                                Rate Honor
                            </h4>
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Satuan Pencacahan
                                </label>
                                {isSensus ? (
                                    <input
                                        type="text"
                                        value={obSatuanLabel}
                                        disabled
                                        className="block w-full max-w-xs rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                ) : (
                                    <Select
                                        value={formData['satuan_id'] || ''}
                                        onValueChange={(val) =>
                                            handleInputChange(
                                                'satuan_id',
                                                val,
                                                true,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full sm:w-56">
                                            <SelectValue placeholder="Pilih satuan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {satuans.map((satuan) => (
                                                <SelectItem
                                                    key={satuan.id}
                                                    value={String(satuan.id)}
                                                >
                                                    {satuan.kode
                                                        ? `${satuan.kode} - ${satuan.nama}`
                                                        : satuan.nama}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            </div>
                            {errors['satuan_id'] && (
                                <InputError message={errors['satuan_id']} />
                            )}

                            {hasPengolahanPenugasanEnabled && (
                                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Satuan Pengolahan Dokumen Pencacahan
                                    </label>
                                    {isSensus ? (
                                        <input
                                            type="text"
                                            value={obSatuanLabel}
                                            disabled
                                            className="block w-full max-w-xs rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                    ) : (
                                        <Select
                                            value={
                                                formData[
                                                    'satuan_pengolahan_pencacahan_id'
                                                ] || ''
                                            }
                                            onValueChange={(val) =>
                                                handleInputChange(
                                                    'satuan_pengolahan_pencacahan_id',
                                                    val,
                                                    true,
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full sm:w-56">
                                                <SelectValue placeholder="Pilih satuan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {satuans.map((satuan) => (
                                                    <SelectItem
                                                        key={satuan.id}
                                                        value={String(
                                                            satuan.id,
                                                        )}
                                                    >
                                                        {satuan.kode
                                                            ? `${satuan.kode} - ${satuan.nama}`
                                                            : satuan.nama}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </div>
                            )}
                            {errors['satuan_pengolahan_pencacahan_id'] && (
                                <InputError
                                    message={
                                        errors[
                                            'satuan_pengolahan_pencacahan_id'
                                        ]
                                    }
                                />
                            )}
                            <div className="w-full max-w-full touch-pan-x overflow-x-auto rounded-2xl">
                                <table className="w-full min-w-[760px] border-collapse">
                                    <thead>
                                        <tr className="border-b border-neutral-200/20 bg-white/60 backdrop-blur-sm dark:border-neutral-700/30 dark:bg-neutral-900/60">
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-700 uppercase dark:text-gray-300">
                                                No
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-700 uppercase dark:text-gray-300">
                                                Status Kepegawaian
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-700 uppercase dark:text-gray-300">
                                                Jenis Penugasan
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-700 uppercase dark:text-gray-300">
                                                Rate Honor (Rp){' '}
                                                <span className="text-red-500">
                                                    *
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-200/20 bg-white/30 dark:divide-neutral-700/20 dark:bg-neutral-800/30">
                                        {combinations
                                            .filter(
                                                (combo) =>
                                                    combo.jenis_penugasan ===
                                                        'pcl_ppl' ||
                                                    combo.jenis_penugasan ===
                                                        'pml' ||
                                                    enabledJenisPenugasan[
                                                        combo.jenis_penugasan
                                                    ],
                                            )
                                            .map((combo, index) => {
                                                const key = `${combo.status_kepegawaian}_${combo.jenis_penugasan}`;
                                                const statusLabel =
                                                    combo.status_kepegawaian ===
                                                    'organik'
                                                        ? 'Organik (PNS/PPPK)'
                                                        : 'Non-Organik';
                                                const penugasanLabels: Record<
                                                    string,
                                                    string
                                                > = {
                                                    pcl_ppl: 'PCL/PPL',
                                                    pml: 'PML',
                                                    koseka: 'Koseka (Koordinator Sensus Kecamatan)',
                                                    pengolahan:
                                                        'Petugas Pengolahan',
                                                    pengawas_pengolahan:
                                                        'Pengawas Pengolahan',
                                                };
                                                const penugasanLabel =
                                                    penugasanLabels[
                                                        combo.jenis_penugasan
                                                    ] || combo.jenis_penugasan;

                                                return (
                                                    <tr
                                                        key={key}
                                                        className="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                                    >
                                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                                            {index + 1}
                                                        </td>
                                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                                            {statusLabel}
                                                        </td>
                                                        <td className="px-6 py-4 text-sm whitespace-nowrap text-gray-900 dark:text-gray-100">
                                                            {penugasanLabel}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <input
                                                                type="text"
                                                                value={
                                                                    formData[
                                                                        key
                                                                    ]
                                                                        ? formatCurrency(
                                                                              formData[
                                                                                  key
                                                                              ],
                                                                          )
                                                                        : ''
                                                                }
                                                                onChange={(e) =>
                                                                    handleInputChange(
                                                                        key,
                                                                        e.target
                                                                            .value,
                                                                    )
                                                                }
                                                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                                placeholder="0"
                                                            />
                                                            {errors[key] && (
                                                                <InputError
                                                                    message={
                                                                        errors[
                                                                            key
                                                                        ]
                                                                    }
                                                                    className="mt-1"
                                                                />
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {(Object.keys(errors).length > 0 ||
                            Object.keys(inertiaErrors).length > 0) && (
                            <div className="border-t border-neutral-200/30 px-4 py-4 sm:px-6 dark:border-neutral-700/30">
                                <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950">
                                    <div className="flex items-start space-x-3">
                                        <svg
                                            className="mt-0.5 size-5 flex-shrink-0 text-red-600 dark:text-red-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <div>
                                            <h3 className="text-sm font-semibold text-red-900 dark:text-red-100">
                                                Terjadi kesalahan validasi
                                            </h3>
                                            <ul className="mt-1 list-inside list-disc text-sm text-red-800 dark:text-red-200">
                                                {Object.values(errors).map(
                                                    (msg, i) => (
                                                        <li key={i}>{msg}</li>
                                                    ),
                                                )}
                                                {Object.values(
                                                    inertiaErrors,
                                                ).map((msg, i) => (
                                                    <li key={i + 1000}>
                                                        {msg}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="flex flex-col gap-3 border-t border-neutral-200/30 px-4 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6 dark:border-neutral-700/30">
                            <Button
                                variant="outline"
                                asChild
                                className="w-full sm:w-auto"
                            >
                                <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                                    Batal
                                </Link>
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full sm:w-auto"
                            >
                                {processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Rate Honor'}
                            </Button>
                        </div>
                    </ContentCard>
                </form>
            </div>
        </AppLayout>
    );
}
