import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import {
    type BreadcrumbItem,
    type Kegiatan,
    type RateHonor,
    type Satuan,
} from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    kegiatan: Kegiatan & {
        rate_honors?: RateHonor[];
    };
    satuans: Satuan[];
}

// Kombinasi rate honor berdasarkan jenis kegiatan
const getCombinations = (jenisKegiatan: 'sensus' | 'survei') => {
    const statusKepegawaian = ['non_organik', 'organik'] as const;
    const combinations: Array<{
        status_kepegawaian: 'non_organik' | 'organik';
        jenis_penugasan:
            | 'pcl_ppl'
            | 'pml'
            | 'pengolahan'
            | 'pengawas_pengolahan';
        label: string;
    }> = [];

    if (jenisKegiatan === 'survei') {
        // Survei: 7 kombinasi
        // Non-Organik: PCL/PPL, PML, Pengolahan (3)
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
                jenis_penugasan: 'pengolahan',
                label: 'Non-Organik - Pengolahan',
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
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Kegiatan', href: '/kegiatan' },
        {
            title: kegiatan.nama_kegiatan,
            href: `/kegiatan/${kegiatan.hashed_id}`,
        },
        { title: 'Kelola Rate Honor', href: '#' },
    ];

    // Get default satuan (OHK or first available)
    const defaultSatuan =
        satuans.find((s) => s.nama === 'OHK' || s.nama === 'Hari') ||
        satuans[0];

    // State untuk mengaktifkan/menonaktifkan jenis penugasan
    const [enabledJenisPenugasan, setEnabledJenisPenugasan] = useState(() => {
        const initial = {
            pcl_ppl: true, // Selalu aktif
            pml: true, // Selalu aktif
            pengolahan: false,
            pengawas_pengolahan: false,
        };

        // Cek apakah ada data existing untuk setiap jenis
        kegiatan.rate_honors?.forEach((rh) => {
            if (rh.jenis_penugasan === 'pengolahan' && rh.rate > 0) {
                initial.pengolahan = true;
            }
            if (rh.jenis_penugasan === 'pengawas_pengolahan' && rh.rate > 0) {
                initial.pengawas_pengolahan = true;
            }
        });

        return initial;
    });

    const combinations = getCombinations(kegiatan.jenis_kegiatan);

    // Initialize form data dengan rate honors yang sudah ada atau nilai kosong
    const [formData, setFormData] = useState(() => {
        const data: Record<string, string> = {};
        // Ambil satuan_id dan satuan_listing_id dari rate_honors pertama (jika ada)
        const first = kegiatan.rate_honors?.[0];
        data['satuan_id'] = first?.satuan_id?.toString() || '';
        if (kegiatan.has_listing_updating) {
            data['satuan_listing_id'] =
                first?.satuan_listing_id?.toString() || '';
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
            }
        });
        return data;
    });

    // State untuk satuan_id - satu satuan untuk seluruh kegiatan
    const [selectedSatuan, setSelectedSatuan] = useState<string>(() => {
        // Ambil satuan dari rate honor yang pertama kali ditemukan
        const existingSatuan = kegiatan.rate_honors?.[0]?.satuan_id;
        return existingSatuan || defaultSatuan?.id || '';
    });

    // Ambil error dari Inertia props jika ada (untuk flash message error backend)
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const pageProps = (window as any).routePageProps || {};
    const inertiaErrors = (pageProps.errors as Record<string, string>) || {};
    const [errors, setErrors] = useState<Record<string, string>>(inertiaErrors);
    const [processing, setProcessing] = useState(false);

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
        if (!formData['satuan_id']) {
            newErrors['satuan_id'] = 'Satuan pencacahan wajib dipilih.';
        }
        if (kegiatan.has_listing_updating && !formData['satuan_listing_id']) {
            newErrors['satuan_listing_id'] =
                'Satuan listing/updating wajib dipilih.';
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
                const base = {
                    status_kepegawaian: combo.status_kepegawaian,
                    jenis_penugasan: combo.jenis_penugasan,
                    rate: parseInt(formData[key]) || 0,
                };
                if (kegiatan.has_listing_updating) {
                    return {
                        ...base,
                        rate_listing: parseInt(formData[`${key}_listing`]) || 0,
                    };
                }
                return base;
            });

        const payload: any = {
            rate_honors: rateHonors,
            satuan_id: formData['satuan_id']
                ? parseInt(formData['satuan_id'])
                : null,
        };
        if (kegiatan.has_listing_updating) {
            payload.satuan_listing_id = formData['satuan_listing_id']
                ? parseInt(formData['satuan_listing_id'])
                : null;
        }
        router.post(
            `/kegiatan/${kegiatan.hashed_id}/rate-honor/bulk`,
            payload,
            {
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

            <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Kelola Rate Honor
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {kegiatan.nama_kegiatan} (
                                    {kegiatan.kode_kegiatan})
                                </p>
                                <p className="mt-1 text-sm font-medium text-blue-600 dark:text-blue-400">
                                    Jenis Kegiatan:{' '}
                                    {kegiatan.jenis_kegiatan === 'sensus'
                                        ? 'Sensus'
                                        : 'Survei'}
                                    {' - '}
                                    {
                                        combinations.filter((combo) => {
                                            if (
                                                combo.jenis_penugasan ===
                                                    'pcl_ppl' ||
                                                combo.jenis_penugasan === 'pml'
                                            ) {
                                                return true;
                                            }
                                            return enabledJenisPenugasan[
                                                combo.jenis_penugasan
                                            ];
                                        }).length
                                    }{' '}
                                    kombinasi rate honor aktif
                                </p>
                            </div>
                            <Link
                                href={`/kegiatan/${kegiatan.hashed_id}`}
                                className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                            >
                                Kembali
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Info Alert */}
                <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950">
                    <div className="flex items-start space-x-3">
                        <svg
                            className="mt-0.5 size-5 flex-shrink-0 text-blue-600 dark:text-blue-400"
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
                                Lengkapi rate honor untuk semua jenis penugasan
                                sesuai dengan jenis kegiatan{' '}
                                <span className="font-semibold">
                                    {kegiatan.jenis_kegiatan === 'sensus'
                                        ? 'Sensus'
                                        : 'Survei'}
                                </span>
                                . Rate honor ini akan digunakan untuk menghitung
                                honor petugas dalam kegiatan ini.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Checkbox Jenis Penugasan */}
                <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div className="p-6">
                        <h3 className="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                            Aktifkan Jenis Penugasan
                        </h3>
                        <div className="space-y-3">
                            <label className="flex items-center space-x-3">
                                <input
                                    type="checkbox"
                                    checked={enabledJenisPenugasan.pcl_ppl}
                                    disabled
                                    className="size-4 rounded border-gray-300 text-blue-600 opacity-50 dark:border-gray-600"
                                />
                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                    PCL/PPL (Wajib)
                                </span>
                            </label>
                            <label className="flex items-center space-x-3">
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
                            <label className="flex cursor-pointer items-center space-x-3">
                                <input
                                    type="checkbox"
                                    checked={enabledJenisPenugasan.pengolahan}
                                    onChange={(e) =>
                                        setEnabledJenisPenugasan({
                                            ...enabledJenisPenugasan,
                                            pengolahan: e.target.checked,
                                        })
                                    }
                                    className="size-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                    Petugas Pengolahan
                                </span>
                            </label>
                            <label className="flex cursor-pointer items-center space-x-3">
                                <input
                                    type="checkbox"
                                    checked={
                                        enabledJenisPenugasan.pengawas_pengolahan
                                    }
                                    onChange={(e) =>
                                        setEnabledJenisPenugasan({
                                            ...enabledJenisPenugasan,
                                            pengawas_pengolahan:
                                                e.target.checked,
                                        })
                                    }
                                    className="size-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                    Pengawas Pengolahan
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="overflow-x-auto">
                            {/* Rate Honor Listing/Updating */}
                            {kegiatan.has_listing_updating && (
                                <>
                                    <h4 className="px-6 pt-8 pb-2 text-base font-semibold text-gray-900 dark:text-white">
                                        Rate Honor Listing/Updating
                                    </h4>
                                    {/* Dropdown satuan global untuk listing/updating */}
                                    <div className="flex items-center gap-2 px-6 pt-4 pb-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Satuan Listing/Updating
                                        </label>
                                        <select
                                            value={
                                                formData['satuan_listing_id'] ||
                                                ''
                                            }
                                            onChange={(e) =>
                                                handleInputChange(
                                                    'satuan_listing_id',
                                                    e.target.value,
                                                    true,
                                                )
                                            }
                                            className="block w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="">
                                                Pilih Satuan
                                            </option>
                                            {satuans.map((satuan: any) => (
                                                <option
                                                    key={satuan.id}
                                                    value={satuan.id}
                                                >
                                                    {satuan.nama}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <table className="w-full border-collapse">
                                        <thead>
                                            <tr className="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
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
                                        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                            {combinations
                                                .filter((combo) => {
                                                    if (
                                                        combo.jenis_penugasan ===
                                                            'pcl_ppl' ||
                                                        combo.jenis_penugasan ===
                                                            'pml'
                                                    ) {
                                                        return true;
                                                    }
                                                    return enabledJenisPenugasan[
                                                        combo.jenis_penugasan
                                                    ];
                                                })
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
                                                                            `${combo.status_kepegawaian}_${combo.jenis_penugasan}_listing`
                                                                        ]
                                                                            ? formatCurrency(
                                                                                  formData[
                                                                                      `${combo.status_kepegawaian}_${combo.jenis_penugasan}_listing`
                                                                                  ],
                                                                              )
                                                                            : ''
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        handleInputChange(
                                                                            `${combo.status_kepegawaian}_${combo.jenis_penugasan}_listing`,
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                                    placeholder="0"
                                                                />
                                                                {errors[
                                                                    `${combo.status_kepegawaian}_${combo.jenis_penugasan}_listing`
                                                                ] && (
                                                                    <InputError
                                                                        message={
                                                                            errors[
                                                                                `${combo.status_kepegawaian}_${combo.jenis_penugasan}_listing`
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
                                    {/* Rate Honor Pencacahan */}
                                    <h4 className="px-6 pt-6 pb-2 text-base font-semibold text-gray-900 dark:text-white">
                                        Rate Honor Pencacahan
                                    </h4>
                                    {/* Dropdown satuan global untuk pencacahan */}
                                    <div className="flex items-center gap-2 px-6 pt-4 pb-2">
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Satuan Pencacahan
                                        </label>
                                        <select
                                            value={formData['satuan_id'] || ''}
                                            onChange={(e) =>
                                                handleInputChange(
                                                    'satuan_id',
                                                    e.target.value,
                                                    true,
                                                )
                                            }
                                            className="block w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        >
                                            <option value="">
                                                Pilih Satuan
                                            </option>
                                            {satuans.map((satuan: any) => (
                                                <option
                                                    key={satuan.id}
                                                    value={satuan.id}
                                                >
                                                    {satuan.nama}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <table className="w-full border-collapse">
                                        <thead>
                                            <tr className="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
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
                                                    Rate Honor Pencacahan (Rp){' '}
                                                    <span className="text-red-500">
                                                        *
                                                    </span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                            {combinations
                                                .filter((combo) => {
                                                    if (
                                                        combo.jenis_penugasan ===
                                                            'pcl_ppl' ||
                                                        combo.jenis_penugasan ===
                                                            'pml'
                                                    ) {
                                                        return true;
                                                    }
                                                    return enabledJenisPenugasan[
                                                        combo.jenis_penugasan
                                                    ];
                                                })
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
                                </>
                            )}
                        </div>
                    </div>
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        {/* Flash Message Error */}
                        {(Object.keys(errors).length > 0 ||
                            Object.keys(inertiaErrors).length > 0) && (
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
                                            {Object.values(inertiaErrors).map(
                                                (msg, i) => (
                                                    <li key={i + 1000}>
                                                        {msg}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                            <Link
                                href={`/kegiatan/${kegiatan.hashed_id}`}
                                className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-gray-800"
                            >
                                {processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Rate Honor'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
