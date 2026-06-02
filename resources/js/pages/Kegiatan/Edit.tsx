import { ContentCard } from '@/components/content-card';
import { FrameSampelTahapanSelect } from '@/components/frame-sampel-tahapan-select';
import InputError from '@/components/input-error';
import { MultiSelectCheckbox } from '@/components/multi-select-checkbox';
import { PageHeader } from '@/components/page-header';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Kegiatan, type SharedData } from '@/types';
import {
    downloadFrameSampelTemplate,
    importFrameSampelPreview,
} from '@/utils/frameSampelExcel';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Loader2, Save, X } from 'lucide-react';
import { useEffect, useState } from 'react';

const BULAN_OPTIONS = [
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
] as const;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kegiatan', href: '/kegiatan' },
    { title: 'Edit Kegiatan', href: '#' },
];

interface User {
    id: number;
    name: string;
    email: string;
}

interface MasterSampelOption {
    id: number;
    nama: string;
    kode: string;
}

interface KegiatanFrameSampelRow {
    id?: number;
    tahapan: 'listing' | 'pencacahan';
    target_unit_sampel: Record<string, string>;
    identitas_tambahan?: Record<string, string> | null;
}

interface MetadataItem {
    code: string;
    codeValue: string;
    labelValue: string;
}

interface MetadataColumn {
    code: string;
    label: string;
    description: string;
}

const DEFAULT_METADATA_COLUMNS: MetadataColumn[] = [
    {
        code: 'kdkec',
        label: 'Kecamatan',
        description: 'Kode wilayah kecamatan.',
    },
    {
        code: 'kddes',
        label: 'Desa/Kelurahan',
        description: 'Kode wilayah desa atau kelurahan.',
    },
    {
        code: 'kdsls',
        label: 'SLS',
        description: 'Kode satuan lingkungan setempat.',
    },
    {
        code: 'kdsubsls',
        label: 'Sub SLS',
        description: 'Kode sub satuan lingkungan setempat.',
    },
    {
        code: 'kdsegmen',
        label: 'Segmen',
        description: 'Kode segmen wilayah kerja atau sampel.',
    },
];

const metadataLabelKey = (code: string): string => `${code}_label`;

const resolveIdentitasValue = (
    identitas: Record<string, string> | null | undefined,
    candidateKeys: string[],
): string => {
    if (!identitas) {
        return '';
    }

    for (const candidateKey of candidateKeys) {
        const matchedEntry = Object.entries(identitas).find(
            ([actualKey]) =>
                actualKey.toLowerCase() === candidateKey.toLowerCase(),
        );

        if (matchedEntry) {
            return matchedEntry[1] ?? '';
        }
    }

    return '';
};

const buildMetadataItems = (
    identitas: Record<string, string> | null | undefined,
): MetadataItem[] => {
    if (!identitas || Object.keys(identitas).length === 0) {
        return [{ code: 'kdkec', codeValue: '', labelValue: '' }];
    }

    const keys = Object.keys(identitas);
    const lowerKeys = keys.map((key) => key.toLowerCase());

    const orderedKeys = [
        ...DEFAULT_METADATA_COLUMNS.map((column) => column.code).filter((key) =>
            lowerKeys.includes(key),
        ),
        ...keys.filter(
            (key) =>
                !key.toLowerCase().endsWith('_label') &&
                !DEFAULT_METADATA_COLUMNS.map((column) => column.code).includes(
                    key.toLowerCase(),
                ),
        ),
    ];

    return orderedKeys.map((key) => ({
        code: key,
        codeValue: resolveIdentitasValue(identitas, [key]),
        labelValue: resolveIdentitasValue(identitas, [metadataLabelKey(key)]),
    }));
};

const buildMetadataColumnsFromRows = (
    rows: KegiatanFrameSampelRow[],
): MetadataColumn[] => {
    const columns: MetadataColumn[] = [];

    rows.forEach((row) => {
        buildMetadataItems(row.identitas_tambahan).forEach((item) => {
            const normalizedCode = item.code.trim().toLowerCase();
            if (
                normalizedCode &&
                !columns.some(
                    (existing) =>
                        existing.code.trim().toLowerCase() === normalizedCode,
                )
            ) {
                columns.push({
                    code: item.code,
                    label:
                        DEFAULT_METADATA_COLUMNS.find(
                            (column) =>
                                column.code.toLowerCase() === normalizedCode,
                        )?.label || item.code,
                    description:
                        DEFAULT_METADATA_COLUMNS.find(
                            (column) =>
                                column.code.toLowerCase() === normalizedCode,
                        )?.description || item.code,
                });
            }
        });
    });

    return columns.length > 0 ? columns : DEFAULT_METADATA_COLUMNS.slice(0, 4);
};

interface KegiatanEditProps {
    kegiatan: Kegiatan;
    ketuaTimUsers: User[];
    tahunOptions: number[];
    pjLainnyaUsers: User[];
    masterFrameSampel: MasterSampelOption[];
    masterUnitSampel: MasterSampelOption[];
    kegiatanFrameSampel: KegiatanFrameSampelRow[];
}

export default function Edit({
    kegiatan,
    ketuaTimUsers,
    tahunOptions,
    pjLainnyaUsers,
    masterFrameSampel,
    masterUnitSampel,
    kegiatanFrameSampel,
}: KegiatanEditProps) {
    const { auth, errors: pageErrors } = usePage<
        SharedData & { errors?: Record<string, string> }
    >().props;
    const errors = pageErrors ?? {};
    const isKetuaTim = auth.activeRole?.name === 'ketua_tim';

    // Format tanggal dari Carbon ke Y-m-d format
    const formatDateForInput = (dateString: string | null): string => {
        if (!dateString) return '';
        // Laravel sudah mengirim dalam format Y-m-d, langsung return
        return dateString;
    };

    // Format currency untuk display
    const formatCurrency = (value: string | number | null): string => {
        if (value === null || value === undefined) return '';
        const str = String(value);
        const number = str.replace(/\D/g, '');
        return number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    };

    // Parse currency untuk submit
    const parseCurrency = (value: string): string => {
        return value.replace(/\./g, '');
    };

    // Helper untuk konversi nominal float ke string tanpa desimal
    const nominalToString = (val: number | null | undefined): string => {
        if (val === null || val === undefined) return '';
        // Jika float, bulatkan ke integer (misal 4941000.00 -> '4941000')
        return Math.round(val).toString();
    };

    const initialMetadataColumns =
        buildMetadataColumnsFromRows(kegiatanFrameSampel);
    const initialFrameTahapan: 'listing' | 'pencacahan' =
        kegiatanFrameSampel.some((row) => row.tahapan === 'listing')
            ? 'listing'
            : 'pencacahan';
    const initialMetadataSaved = kegiatanFrameSampel.length > 0;

    const { data, setData, processing } = useForm({
        kode_kegiatan: kegiatan.kode_kegiatan || '',
        nama_kegiatan: kegiatan.nama_kegiatan || '',
        jenis_kegiatan: kegiatan.jenis_kegiatan || 'survei',
        deskripsi: kegiatan.deskripsi || '',
        tahun_anggaran: kegiatan.tahun_anggaran || new Date().getFullYear(),
        pagu_pencacahan: nominalToString(kegiatan.pagu_pencacahan),
        pagu_listing: nominalToString(kegiatan.pagu_listing),
        has_listing_updating: kegiatan.has_listing_updating || false,
        metode_pendataan_pencacahan: (kegiatan.metode_pendataan_pencacahan ||
            '') as '' | 'PAPI' | 'CAPI',
        metode_pendataan_listing: (kegiatan.metode_pendataan_listing || '') as
            | ''
            | 'PAPI'
            | 'CAPI',
        metode_pelatihan: (kegiatan.metode_pelatihan || '') as
            | ''
            | 'daring'
            | 'luring'
            | 'hybrid'
            | 'tidak_ada_pelatihan',
        bulan_pelatihan: kegiatan.bulan_pelatihan
            ? kegiatan.bulan_pelatihan.toString()
            : '',
        frame_sampel_listing_id: kegiatan.frame_sampel_listing_id
            ? String(kegiatan.frame_sampel_listing_id)
            : '',
        frame_sampel_pencacahan_id: kegiatan.frame_sampel_pencacahan_id
            ? String(kegiatan.frame_sampel_pencacahan_id)
            : '',
        unit_sampel_listing_ids: kegiatan.unit_sampel_listing_ids ?? [],
        unit_sampel_pencacahan_ids: kegiatan.unit_sampel_pencacahan_ids ?? [],
        ketua_tim_user_id: kegiatan.ketua_tim_user_id?.toString() || '',
        pj_lainnya_id: kegiatan.pj_lainnya_id
            ? kegiatan.pj_lainnya_id.toString()
            : '',
        tanggal_mulai: formatDateForInput(kegiatan.tanggal_mulai),
        tanggal_selesai: formatDateForInput(kegiatan.tanggal_selesai),
        frame_tahapan: initialFrameTahapan,
        frame_metadata_columns: initialMetadataColumns,
        kegiatan_frame_sampel:
            kegiatanFrameSampel.length > 0
                ? kegiatanFrameSampel.map((row) => ({
                      tahapan: row.tahapan,
                      target_unit_sampel: Object.fromEntries(
                          Object.entries(
                              (row.target_unit_sampel as unknown as Record<
                                  string,
                                  number
                              >) ?? {},
                          ).map(([k, v]) => [k, String(v)]),
                      ) as Record<string, string>,
                      metadata_items: buildMetadataItems(
                          row.identitas_tambahan,
                      ),
                  }))
                : [
                      {
                          tahapan: 'pencacahan' as const,
                          target_unit_sampel: {} as Record<string, string>,
                          metadata_items: initialMetadataColumns.map(
                              (column) => ({
                                  code: column.code,
                                  codeValue: '',
                                  labelValue: '',
                              }),
                          ),
                      },
                  ],
    });

    const isSensus = data.jenis_kegiatan === 'sensus';
    const [isMetadataSaved, setIsMetadataSaved] =
        useState(initialMetadataSaved);
    const [isEditingMetadata, setIsEditingMetadata] =
        useState(!initialMetadataSaved);
    const [metadataActionError, setMetadataActionError] = useState('');
    const [frameImportFile, setFrameImportFile] = useState<File | null>(null);
    const [frameImportProcessing, setFrameImportProcessing] = useState(false);
    const [frameImportMessage, setFrameImportMessage] = useState('');
    const [frameImportError, setFrameImportError] = useState('');

    const isMetadataComplete =
        data.frame_metadata_columns.length > 0 &&
        data.frame_metadata_columns.every(
            (column) =>
                column.code.trim() !== '' &&
                column.label.trim() !== '' &&
                column.description.trim() !== '',
        );
    const canManageDetailFrame = isMetadataSaved && !isEditingMetadata;
    const activeUnitSampelIds =
        data.frame_tahapan === 'listing' &&
        !isSensus &&
        data.has_listing_updating
            ? data.unit_sampel_listing_ids
            : data.unit_sampel_pencacahan_ids;
    const activeUnitSampelList = activeUnitSampelIds
        .map((id) => masterUnitSampel.find((item) => item.id === id))
        .filter((item): item is MasterSampelOption => item !== undefined);
    const activeFrameRows = data.kegiatan_frame_sampel
        .map((row, index) => ({ row, index }))
        .filter(({ row }) => row.tahapan === data.frame_tahapan);

    useEffect(() => {
        if (isSensus && data.has_listing_updating) {
            setData('has_listing_updating', false);
        }

        if (isSensus && data.pagu_listing !== '') {
            setData('pagu_listing', '');
        }

        if (isSensus && data.metode_pendataan_listing !== '') {
            setData('metode_pendataan_listing', '');
        }

        if (isSensus && data.frame_sampel_listing_id !== '') {
            setData('frame_sampel_listing_id', '');
        }

        if (isSensus && data.unit_sampel_listing_ids.length > 0) {
            setData('unit_sampel_listing_ids', []);
        }

        if (!isSensus && !data.has_listing_updating) {
            if (
                data.kegiatan_frame_sampel.some(
                    (row) => row.tahapan === 'listing',
                )
            ) {
                setData(
                    'kegiatan_frame_sampel',
                    data.kegiatan_frame_sampel
                        .filter((row) => row.tahapan !== 'listing')
                        .map((row) => ({
                            tahapan: row.tahapan,
                            target_unit_sampel: row.target_unit_sampel,
                            metadata_items: row.metadata_items,
                        })),
                );
            }

            if (data.frame_tahapan === 'listing') {
                setData('frame_tahapan', 'pencacahan');
                setData(
                    'kegiatan_frame_sampel',
                    data.kegiatan_frame_sampel.map((row) => ({
                        ...row,
                        tahapan: 'pencacahan',
                    })),
                );
            }
        }

        if (isSensus && data.metode_pelatihan === 'tidak_ada_pelatihan') {
            setData('metode_pelatihan', '');
        }
    }, [
        isSensus,
        data.has_listing_updating,
        data.pagu_listing,
        data.metode_pendataan_listing,
        data.frame_sampel_listing_id,
        data.unit_sampel_listing_ids,
        data.frame_tahapan,
        data.kegiatan_frame_sampel,
        data.metode_pelatihan,
        setData,
    ]);

    const addFrameSampelRow = () => {
        setData('kegiatan_frame_sampel', [
            ...data.kegiatan_frame_sampel,
            {
                tahapan: data.frame_tahapan,
                target_unit_sampel: {} as Record<string, string>,
                metadata_items: data.frame_metadata_columns.map((column) => ({
                    code: column.code,
                    codeValue: '',
                    labelValue: '',
                })),
            },
        ]);
    };

    const updateFrameTahapan = (value: 'listing' | 'pencacahan') => {
        setData('frame_tahapan', value);
    };

    const updateFrameSampelRowTarget = (
        index: number,
        unitSampelId: string,
        value: string,
    ) => {
        setData(
            'kegiatan_frame_sampel',
            data.kegiatan_frame_sampel.map((row, rowIndex) => {
                if (rowIndex !== index) {
                    return row;
                }

                return {
                    ...row,
                    target_unit_sampel: {
                        ...row.target_unit_sampel,
                        [unitSampelId]: value,
                    },
                };
            }),
        );
    };

    const addMetadataColumn = () => {
        if (canManageDetailFrame) {
            return;
        }

        setMetadataActionError('');
        setData('frame_metadata_columns', [
            ...data.frame_metadata_columns,
            { code: '', label: '', description: '' },
        ]);
    };

    const updateMetadataColumn = (
        columnIndex: number,
        key: 'code' | 'label' | 'description',
        value: string,
    ) => {
        const previousCode =
            data.frame_metadata_columns[columnIndex]?.code ?? '';
        const nextColumns = [...data.frame_metadata_columns];
        nextColumns[columnIndex] = {
            ...nextColumns[columnIndex],
            [key]: value,
        };
        setData('frame_metadata_columns', nextColumns);
        setMetadataActionError('');

        if (
            key !== 'code' ||
            previousCode.trim() === '' ||
            previousCode === value
        ) {
            return;
        }

        setData(
            'kegiatan_frame_sampel',
            data.kegiatan_frame_sampel.map((row) => {
                return {
                    ...row,
                    metadata_items: (row.metadata_items || []).map((item) =>
                        item.code.trim().toLowerCase() ===
                        previousCode.trim().toLowerCase()
                            ? { ...item, code: value }
                            : item,
                    ),
                };
            }),
        );
    };

    const removeMetadataColumn = (columnIndex: number) => {
        const removedCode =
            data.frame_metadata_columns[columnIndex]?.code ?? '';
        setMetadataActionError('');

        setData(
            'frame_metadata_columns',
            data.frame_metadata_columns.filter(
                (_, index) => index !== columnIndex,
            ),
        );

        if (!removedCode.trim()) {
            return;
        }

        setData(
            'kegiatan_frame_sampel',
            data.kegiatan_frame_sampel.map((row) => {
                return {
                    ...row,
                    metadata_items: (row.metadata_items || []).filter(
                        (item) =>
                            item.code.trim().toLowerCase() !==
                            removedCode.trim().toLowerCase(),
                    ),
                };
            }),
        );
    };

    const updateFrameMetadataValue = (
        rowIndex: number,
        columnCode: string,
        key: 'codeValue' | 'labelValue',
        value: string,
    ) => {
        setData(
            'kegiatan_frame_sampel',
            data.kegiatan_frame_sampel.map((row, currentIndex) => {
                if (currentIndex !== rowIndex) {
                    return row;
                }

                const existingIndex = (row.metadata_items || []).findIndex(
                    (item) =>
                        item.code.trim().toLowerCase() ===
                        columnCode.trim().toLowerCase(),
                );

                if (existingIndex === -1) {
                    return {
                        ...row,
                        metadata_items: [
                            ...(row.metadata_items || []),
                            {
                                code: columnCode,
                                codeValue: key === 'codeValue' ? value : '',
                                labelValue: key === 'labelValue' ? value : '',
                            },
                        ],
                    };
                }

                return {
                    ...row,
                    metadata_items: (row.metadata_items || []).map(
                        (item, itemIndex) =>
                            itemIndex === existingIndex
                                ? { ...item, [key]: value }
                                : item,
                    ),
                };
            }),
        );
    };

    const getFrameMetadataValue = (
        row: { metadata_items?: MetadataItem[] },
        columnCode: string,
        key: 'codeValue' | 'labelValue',
    ): string => {
        const found = (row.metadata_items || []).find(
            (item) =>
                item.code.trim().toLowerCase() ===
                columnCode.trim().toLowerCase(),
        );

        return found?.[key] || '';
    };

    const removeFrameSampelRow = (index: number) => {
        setData(
            'kegiatan_frame_sampel',
            data.kegiatan_frame_sampel.filter(
                (_, rowIndex) => rowIndex !== index,
            ),
        );
    };

    const saveMetadataColumns = () => {
        if (!isMetadataComplete) {
            setMetadataActionError(
                'Lengkapi kode, label, dan deskripsi metadata sebelum disimpan.',
            );

            return;
        }

        setIsMetadataSaved(true);
        setIsEditingMetadata(false);
        setMetadataActionError('');
    };

    const enableMetadataEditing = () => {
        setIsMetadataSaved(false);
        setIsEditingMetadata(true);
        setMetadataActionError('');
        setFrameImportMessage('');
        setFrameImportError('');
    };

    const handleGenerateFrameTemplate = async () => {
        try {
            setFrameImportMessage('');
            setFrameImportError('');

            await downloadFrameSampelTemplate(
                data.frame_metadata_columns,
                activeUnitSampelList,
            );
        } catch (error) {
            setFrameImportError(
                error instanceof Error
                    ? error.message
                    : 'Gagal menghasilkan template Excel frame sampel.',
            );
        }
    };

    const handleImportFrameSampel = async () => {
        if (!frameImportFile) {
            setFrameImportError('Pilih file Excel terlebih dahulu.');

            return;
        }

        setFrameImportProcessing(true);
        setFrameImportMessage('');
        setFrameImportError('');

        try {
            const payload = await importFrameSampelPreview(
                frameImportFile,
                data.frame_metadata_columns,
                activeUnitSampelList,
            );

            setData('kegiatan_frame_sampel', [
                ...data.kegiatan_frame_sampel.filter(
                    (row) => row.tahapan !== data.frame_tahapan,
                ),
                ...payload.rows.map((row) => ({
                    tahapan: data.frame_tahapan,
                    target_unit_sampel: row.target_unit_sampel,
                    metadata_items: buildMetadataItems(row.identitas_tambahan),
                })),
            ]);

            setFrameImportMessage(
                `Berhasil memuat ${payload.summary.valid_rows} baris dari file Excel.`,
            );

            if (payload.errors.length > 0) {
                setFrameImportError(payload.errors.join(' | '));
            }
        } catch (error) {
            setFrameImportError(
                error instanceof Error
                    ? error.message
                    : 'Gagal mengimpor detail frame sampel.',
            );
        } finally {
            setFrameImportProcessing(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Transform data: convert string currency values to numbers before submitting
        const transformedData = {
            nama_kegiatan: data.nama_kegiatan,
            jenis_kegiatan: data.jenis_kegiatan,
            deskripsi: data.deskripsi,
            tahun_anggaran: data.tahun_anggaran,
            pagu_pencacahan: data.pagu_pencacahan
                ? Number(data.pagu_pencacahan)
                : null,
            pagu_listing: data.pagu_listing ? Number(data.pagu_listing) : null,
            has_listing_updating: isSensus ? false : data.has_listing_updating,
            metode_pendataan_pencacahan:
                data.metode_pendataan_pencacahan || null,
            metode_pendataan_listing:
                !isSensus && data.has_listing_updating
                    ? data.metode_pendataan_listing || null
                    : null,
            metode_pelatihan: data.metode_pelatihan || null,
            bulan_pelatihan: data.bulan_pelatihan
                ? Number(data.bulan_pelatihan)
                : null,
            frame_sampel_listing_id:
                !isSensus && data.frame_sampel_listing_id
                    ? Number(data.frame_sampel_listing_id)
                    : null,
            frame_sampel_pencacahan_id: data.frame_sampel_pencacahan_id
                ? Number(data.frame_sampel_pencacahan_id)
                : null,
            unit_sampel_listing_ids:
                !isSensus && data.has_listing_updating
                    ? data.unit_sampel_listing_ids
                    : null,
            unit_sampel_pencacahan_ids: data.unit_sampel_pencacahan_ids,
            kegiatan_frame_sampel: data.kegiatan_frame_sampel
                .filter((row) => {
                    const hasAnyTarget = Object.values(
                        row.target_unit_sampel,
                    ).some((v) => v !== '' && Number(v) >= 1);

                    if (!hasAnyTarget) {
                        return false;
                    }

                    if (isSensus || !data.has_listing_updating) {
                        return row.tahapan === 'pencacahan';
                    }

                    return true;
                })
                .map((row) => ({
                    tahapan: row.tahapan,
                    target_unit_sampel: Object.fromEntries(
                        Object.entries(row.target_unit_sampel)
                            .filter(([, v]) => v !== '' && Number(v) >= 0)
                            .map(([k, v]) => [k, Number(v)]),
                    ),
                    identitas_tambahan: (row.metadata_items || []).reduce<
                        Record<string, string>
                    >((accumulator, item: MetadataItem) => {
                        const code = item.code?.trim();
                        const codeValue = item.codeValue?.trim();
                        const labelValue = item.labelValue?.trim();

                        if (!code) {
                            return accumulator;
                        }

                        if (codeValue) {
                            accumulator[code] = codeValue;
                        }

                        if (labelValue) {
                            accumulator[metadataLabelKey(code)] = labelValue;
                        }

                        return accumulator;
                    }, {}),
                })),
            ketua_tim_user_id: data.ketua_tim_user_id || null,
            pj_lainnya_id: data.pj_lainnya_id || null,
            tanggal_mulai: data.tanggal_mulai,
            tanggal_selesai: data.tanggal_selesai,
        };

        router.put(`/kegiatan/${kegiatan.hashed_id}`, transformedData, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Kegiatan - ${kegiatan.nama_kegiatan}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Edit Kegiatan"
                    description="Ubah informasi kegiatan"
                >
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="gap-2"
                    >
                        <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <ContentCard>
                        <div className="space-y-6">
                            {/* Kode Kegiatan - Read Only */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="kode_kegiatan"
                                    className="text-base font-semibold"
                                >
                                    Kode Kegiatan
                                </Label>
                                <Input
                                    id="kode_kegiatan"
                                    value={data.kode_kegiatan}
                                    disabled
                                    className="h-11 bg-neutral-100 text-base dark:bg-neutral-800/60"
                                />
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    🔒 Kode kegiatan tidak dapat diubah
                                </p>
                            </div>

                            {/* Nama Kegiatan */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="nama_kegiatan"
                                    className="text-base font-semibold"
                                >
                                    Nama Kegiatan{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="nama_kegiatan"
                                    value={data.nama_kegiatan}
                                    onChange={(e) =>
                                        setData('nama_kegiatan', e.target.value)
                                    }
                                    placeholder="Masukkan nama kegiatan..."
                                    className="h-11 text-base"
                                />
                                <InputError
                                    message={errors.nama_kegiatan}
                                    className="mt-2"
                                />
                            </div>

                            {/* Jenis Kegiatan */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="jenis_kegiatan"
                                    className="text-base font-semibold"
                                >
                                    Jenis Kegiatan
                                </Label>
                                <Input
                                    id="jenis_kegiatan"
                                    value={
                                        data.jenis_kegiatan === 'sensus'
                                            ? 'Sensus'
                                            : 'Survei'
                                    }
                                    disabled
                                    className="h-11 bg-neutral-100 text-base capitalize dark:bg-neutral-800/60"
                                />
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    🔒 Jenis kegiatan tidak dapat diubah setelah
                                    kegiatan dibuat
                                </p>
                            </div>

                            {/* Deskripsi */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="deskripsi"
                                    className="text-base font-semibold"
                                >
                                    Deskripsi
                                </Label>
                                <Textarea
                                    id="deskripsi"
                                    rows={4}
                                    value={data.deskripsi}
                                    onChange={(e) =>
                                        setData('deskripsi', e.target.value)
                                    }
                                    placeholder="Masukkan deskripsi kegiatan... (opsional)"
                                    className="text-base"
                                />
                                <InputError
                                    message={errors.deskripsi}
                                    className="mt-2"
                                />
                            </div>

                            {/* Grid untuk 2 kolom */}
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tahun Anggaran */}
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="tahun_anggaran"
                                        className="text-base font-semibold"
                                    >
                                        Tahun Anggaran{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <SearchableSelect
                                        options={tahunOptions.map((tahun) => ({
                                            value: tahun.toString(),
                                            label: tahun.toString(),
                                        }))}
                                        value={data.tahun_anggaran.toString()}
                                        onValueChange={(value) =>
                                            setData(
                                                'tahun_anggaran',
                                                parseInt(value, 10),
                                            )
                                        }
                                        placeholder="Pilih tahun anggaran"
                                        searchPlaceholder="Cari tahun..."
                                        className="mt-1"
                                    />
                                    <InputError
                                        message={errors.tahun_anggaran}
                                        className="mt-2"
                                    />
                                </div>

                                {/* Pagu Anggaran */}
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="pagu_pencacahan"
                                        className="text-base font-semibold"
                                    >
                                        Pagu Pencacahan (Rp)
                                    </Label>
                                    <Input
                                        id="pagu_pencacahan"
                                        value={
                                            data.pagu_pencacahan
                                                ? formatCurrency(
                                                      data.pagu_pencacahan,
                                                  )
                                                : ''
                                        }
                                        onChange={(e) => {
                                            const raw = parseCurrency(
                                                e.target.value,
                                            );
                                            setData('pagu_pencacahan', raw);
                                        }}
                                        placeholder="Masukkan nominal pagu..."
                                        className="h-11 text-base"
                                    />
                                    <InputError
                                        message={errors.pagu_pencacahan}
                                        className="mt-2"
                                    />
                                </div>
                            </div>

                            {!isSensus && (
                                <div className="space-y-2">
                                    <label
                                        htmlFor="has_listing_updating"
                                        className="block text-base font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        Apakah kegiatan ini memiliki tahapan
                                        Listing/Updating?
                                    </label>
                                    <div className="mt-3 flex items-start gap-3">
                                        <input
                                            type="checkbox"
                                            id="has_listing_updating"
                                            checked={data.has_listing_updating}
                                            onChange={(e) =>
                                                setData(
                                                    'has_listing_updating',
                                                    e.target.checked,
                                                )
                                            }
                                            className="mt-1 h-5 w-5 rounded border-2 border-neutral-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-gray-700"
                                        />
                                        <span className="text-base text-gray-700 dark:text-gray-300">
                                            Aktifkan jika ada tahapan
                                            listing/updating sebelum
                                            pencacahan/pendataan lapangan.
                                        </span>
                                    </div>
                                </div>
                            )}

                            {/* Pagu Listing */}
                            {data.has_listing_updating && (
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="pagu_listing"
                                        className="text-base font-semibold"
                                    >
                                        Pagu Listing/Updating (Rp)
                                    </Label>
                                    <Input
                                        id="pagu_listing"
                                        value={
                                            data.pagu_listing
                                                ? formatCurrency(
                                                      data.pagu_listing,
                                                  )
                                                : ''
                                        }
                                        onChange={(e) => {
                                            const raw = parseCurrency(
                                                e.target.value,
                                            );
                                            setData('pagu_listing', raw);
                                        }}
                                        placeholder="Masukkan nominal pagu listing..."
                                        className="h-11 text-base"
                                    />
                                    <InputError
                                        message={errors.pagu_listing}
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            {/* Metode Pendataan Pencacahan */}
                            <div className="space-y-2">
                                <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                    Metode Pendataan Pencacahan{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    CAPI = menggunakan aplikasi FASIH di
                                    smartphone. PAPI = menggunakan kertas.
                                </p>
                                <div className="flex gap-4">
                                    {['PAPI', 'CAPI'].map((metode) => (
                                        <label
                                            key={metode}
                                            className={`flex flex-1 cursor-pointer items-center gap-3 rounded-lg border-2 px-4 py-3 transition-colors ${
                                                data.metode_pendataan_pencacahan ===
                                                metode
                                                    ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-300 dark:bg-neutral-800'
                                                    : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="metode_pendataan_pencacahan"
                                                value={metode}
                                                checked={
                                                    data.metode_pendataan_pencacahan ===
                                                    metode
                                                }
                                                onChange={() =>
                                                    setData(
                                                        'metode_pendataan_pencacahan',
                                                        metode as
                                                            | 'PAPI'
                                                            | 'CAPI',
                                                    )
                                                }
                                                className="h-4 w-4 text-neutral-900"
                                            />
                                            <div>
                                                <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                    {metode}
                                                </span>
                                                <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                    {metode === 'CAPI'
                                                        ? '(FASIH)'
                                                        : '(Kertas)'}
                                                </span>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                                <InputError
                                    message={errors.metode_pendataan_pencacahan}
                                    className="mt-2"
                                />
                            </div>

                            {/* Metode Pendataan Listing */}
                            {data.has_listing_updating && (
                                <div className="space-y-2">
                                    <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                        Metode Pendataan Listing{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Metode pendataan khusus untuk tahap
                                        listing/updating.
                                    </p>
                                    <div className="flex gap-4">
                                        {['PAPI', 'CAPI'].map((metode) => (
                                            <label
                                                key={metode}
                                                className={`flex flex-1 cursor-pointer items-center gap-3 rounded-lg border-2 px-4 py-3 transition-colors ${
                                                    data.metode_pendataan_listing ===
                                                    metode
                                                        ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-300 dark:bg-neutral-800'
                                                        : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="metode_pendataan_listing"
                                                    value={metode}
                                                    checked={
                                                        data.metode_pendataan_listing ===
                                                        metode
                                                    }
                                                    onChange={() =>
                                                        setData(
                                                            'metode_pendataan_listing',
                                                            metode as
                                                                | 'PAPI'
                                                                | 'CAPI',
                                                        )
                                                    }
                                                    className="h-4 w-4 text-neutral-900"
                                                />
                                                <div>
                                                    <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                        {metode}
                                                    </span>
                                                    <span className="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                                        {metode === 'CAPI'
                                                            ? '(FASIH)'
                                                            : '(Kertas)'}
                                                    </span>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError
                                        message={
                                            errors.metode_pendataan_listing
                                        }
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                        Frame Sampel Pencacahan
                                    </label>
                                    <SearchableSelect
                                        options={[
                                            {
                                                value: '',
                                                label: 'Pilih Frame Sampel Pencacahan',
                                            },
                                            ...masterFrameSampel.map(
                                                (item) => ({
                                                    value: String(item.id),
                                                    label: `${item.nama} (${item.kode})`,
                                                }),
                                            ),
                                        ]}
                                        value={data.frame_sampel_pencacahan_id}
                                        onValueChange={(value) =>
                                            setData(
                                                'frame_sampel_pencacahan_id',
                                                value,
                                            )
                                        }
                                        placeholder="Pilih Frame Sampel Pencacahan"
                                        searchPlaceholder="Cari frame sampel..."
                                        className="mt-2"
                                    />
                                    <InputError
                                        message={
                                            errors.frame_sampel_pencacahan_id
                                        }
                                        className="mt-2"
                                    />
                                </div>

                                <div>
                                    <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                        Unit Sampel Pencacahan{' '}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Pilih minimal 1 unit sampel. Bisa lebih
                                        dari 1 (mis. Sensus Ekonomi).
                                    </p>
                                    <MultiSelectCheckbox
                                        className="mt-2"
                                        options={masterUnitSampel.map(
                                            (item) => ({
                                                value: item.id,
                                                label: item.nama,
                                                subLabel: item.kode,
                                            }),
                                        )}
                                        values={data.unit_sampel_pencacahan_ids}
                                        onValuesChange={(values) =>
                                            setData(
                                                'unit_sampel_pencacahan_ids',
                                                values,
                                            )
                                        }
                                        placeholder="Pilih unit sampel pencacahan..."
                                    />
                                    <InputError
                                        message={
                                            errors.unit_sampel_pencacahan_ids
                                        }
                                        className="mt-2"
                                    />
                                </div>

                                {!isSensus && data.has_listing_updating && (
                                    <>
                                        <div>
                                            <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                                Frame Sampel Listing
                                            </label>
                                            <SearchableSelect
                                                options={[
                                                    {
                                                        value: '',
                                                        label: 'Pilih Frame Sampel Listing',
                                                    },
                                                    ...masterFrameSampel.map(
                                                        (item) => ({
                                                            value: String(
                                                                item.id,
                                                            ),
                                                            label: `${item.nama} (${item.kode})`,
                                                        }),
                                                    ),
                                                ]}
                                                value={
                                                    data.frame_sampel_listing_id
                                                }
                                                onValueChange={(value) =>
                                                    setData(
                                                        'frame_sampel_listing_id',
                                                        value,
                                                    )
                                                }
                                                placeholder="Pilih Frame Sampel Listing"
                                                searchPlaceholder="Cari frame sampel..."
                                                className="mt-2"
                                            />
                                            <InputError
                                                message={
                                                    errors.frame_sampel_listing_id
                                                }
                                                className="mt-2"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                                Unit Sampel Listing
                                            </label>
                                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Pilih satu atau lebih unit
                                                sampel listing.
                                            </p>
                                            <MultiSelectCheckbox
                                                className="mt-2"
                                                options={masterUnitSampel.map(
                                                    (item) => ({
                                                        value: item.id,
                                                        label: item.nama,
                                                        subLabel: item.kode,
                                                    }),
                                                )}
                                                values={
                                                    data.unit_sampel_listing_ids
                                                }
                                                onValuesChange={(values) =>
                                                    setData(
                                                        'unit_sampel_listing_ids',
                                                        values,
                                                    )
                                                }
                                                placeholder="Pilih unit sampel listing..."
                                            />
                                            <InputError
                                                message={
                                                    errors.unit_sampel_listing_ids
                                                }
                                                className="mt-2"
                                            />
                                        </div>
                                    </>
                                )}
                            </div>

                            <div className="space-y-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                                <div className="space-y-3">
                                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                                        Frame Sampel
                                    </h3>

                                    <FrameSampelTahapanSelect
                                        value={data.frame_tahapan}
                                        onValueChange={updateFrameTahapan}
                                        allowListing={
                                            !isSensus &&
                                            data.has_listing_updating
                                        }
                                        className="w-full md:w-auto"
                                    />

                                    <div className="space-y-2 rounded-md border border-neutral-200 p-3 dark:border-neutral-700">
                                        <div className="flex items-center justify-between">
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Metadata Frame (isi nama kolom
                                                dulu)
                                            </label>
                                            {!canManageDetailFrame && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={addMetadataColumn}
                                                >
                                                    Tambah Metadata
                                                </Button>
                                            )}
                                        </div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Susun berurutan dari tingkat
                                            tertinggi ke rendah.
                                        </p>
                                        <div className="space-y-2">
                                            {data.frame_metadata_columns.map(
                                                (column, columnIndex) => (
                                                    <div
                                                        key={`column-${columnIndex}`}
                                                        className="grid grid-cols-1 gap-2 md:grid-cols-[1fr_1.5fr_2fr_auto]"
                                                    >
                                                        <input
                                                            type="text"
                                                            value={column.code}
                                                            disabled={
                                                                canManageDetailFrame
                                                            }
                                                            onChange={(e) =>
                                                                updateMetadataColumn(
                                                                    columnIndex,
                                                                    'code',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                                            placeholder="Kode metadata (contoh: kdkec)"
                                                        />
                                                        <input
                                                            type="text"
                                                            value={column.label}
                                                            disabled={
                                                                canManageDetailFrame
                                                            }
                                                            onChange={(e) =>
                                                                updateMetadataColumn(
                                                                    columnIndex,
                                                                    'label',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                                            placeholder="Label UI (contoh: Kecamatan)"
                                                        />
                                                        <input
                                                            type="text"
                                                            value={
                                                                column.description
                                                            }
                                                            disabled={
                                                                canManageDetailFrame
                                                            }
                                                            onChange={(e) =>
                                                                updateMetadataColumn(
                                                                    columnIndex,
                                                                    'description',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="h-10 rounded-lg border border-neutral-200 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                                            placeholder="Deskripsi (contoh: Kode Kecamatan)"
                                                        />
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="destructive"
                                                            disabled={
                                                                canManageDetailFrame
                                                            }
                                                            onClick={() =>
                                                                removeMetadataColumn(
                                                                    columnIndex,
                                                                )
                                                            }
                                                        >
                                                            Hapus
                                                        </Button>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                        {metadataActionError && (
                                            <p className="text-sm text-red-600 dark:text-red-400">
                                                {metadataActionError}
                                            </p>
                                        )}
                                        <div className="flex justify-end gap-2">
                                            {canManageDetailFrame ? (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={
                                                        enableMetadataEditing
                                                    }
                                                >
                                                    Ubah Metadata
                                                </Button>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    onClick={
                                                        saveMetadataColumns
                                                    }
                                                    disabled={
                                                        !isMetadataComplete
                                                    }
                                                >
                                                    Simpan Metadata
                                                </Button>
                                            )}
                                        </div>
                                    </div>

                                    {canManageDetailFrame && (
                                        <div className="space-y-3">
                                            <div className="flex flex-wrap items-center justify-between gap-3">
                                                <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    Detail Frame Sampel
                                                </h4>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={
                                                            handleGenerateFrameTemplate
                                                        }
                                                    >
                                                        Generate Excel
                                                    </Button>
                                                    <input
                                                        type="file"
                                                        accept=".xlsx,.xls,.csv"
                                                        onChange={(e) =>
                                                            setFrameImportFile(
                                                                e.target
                                                                    .files?.[0] ||
                                                                    null,
                                                            )
                                                        }
                                                        className="block text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white dark:text-gray-300 dark:file:bg-neutral-200 dark:file:text-neutral-900"
                                                    />
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={
                                                            handleImportFrameSampel
                                                        }
                                                        disabled={
                                                            frameImportProcessing ||
                                                            !frameImportFile
                                                        }
                                                    >
                                                        {frameImportProcessing
                                                            ? 'Mengimpor...'
                                                            : 'Import Excel'}
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={
                                                            addFrameSampelRow
                                                        }
                                                    >
                                                        Tambah Frame
                                                    </Button>
                                                </div>
                                            </div>
                                            {frameImportMessage && (
                                                <p className="text-sm text-green-700 dark:text-green-400">
                                                    {frameImportMessage}
                                                </p>
                                            )}
                                            {frameImportError && (
                                                <p className="text-sm text-red-600 dark:text-red-400">
                                                    {frameImportError}
                                                </p>
                                            )}
                                        </div>
                                    )}
                                </div>

                                {!canManageDetailFrame && (
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Simpan metadata terlebih dahulu sebelum
                                        mengisi detail frame sampel, generate
                                        template, atau import Excel.
                                    </p>
                                )}

                                {canManageDetailFrame &&
                                    activeFrameRows.length === 0 && (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            Belum ada data frame sampel.
                                        </p>
                                    )}

                                {canManageDetailFrame && (
                                    <div className="space-y-3">
                                        {activeFrameRows.map(
                                            ({ row, index }) => (
                                                <div
                                                    key={`frame-row-${row.tahapan}-${index}`}
                                                    className="space-y-3 rounded-md border border-neutral-200 p-3 dark:border-neutral-700"
                                                >
                                                    <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                        {data.frame_metadata_columns.map(
                                                            (
                                                                column,
                                                                columnIndex,
                                                            ) => (
                                                                <div
                                                                    key={`frame-${index}-col-${columnIndex}`}
                                                                >
                                                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                        {column.label ||
                                                                            `Kolom ${columnIndex + 1}`}
                                                                    </label>
                                                                    <div className="mt-1 grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                        <input
                                                                            type="text"
                                                                            value={getFrameMetadataValue(
                                                                                row,
                                                                                column.code,
                                                                                'codeValue',
                                                                            )}
                                                                            onChange={(
                                                                                e,
                                                                            ) =>
                                                                                updateFrameMetadataValue(
                                                                                    index,
                                                                                    column.code,
                                                                                    'codeValue',
                                                                                    e
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                            className="block h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                                                            placeholder={`Kode ${column.label || 'metadata'}`}
                                                                        />
                                                                        <input
                                                                            type="text"
                                                                            value={getFrameMetadataValue(
                                                                                row,
                                                                                column.code,
                                                                                'labelValue',
                                                                            )}
                                                                            onChange={(
                                                                                e,
                                                                            ) =>
                                                                                updateFrameMetadataValue(
                                                                                    index,
                                                                                    column.code,
                                                                                    'labelValue',
                                                                                    e
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                            className="block h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                                                            placeholder={
                                                                                column.label ||
                                                                                'metadata'
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                                        {
                                                                            column.code
                                                                        }
                                                                        {column.description
                                                                            ? ` - ${column.description}`
                                                                            : ''}
                                                                    </p>
                                                                </div>
                                                            ),
                                                        )}
                                                    </div>

                                                    <div className="flex items-end justify-between gap-3">
                                                        <div className="flex flex-wrap gap-3">
                                                            {activeUnitSampelList.length ===
                                                            0 ? (
                                                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                                                    Pilih unit
                                                                    sampel
                                                                    terlebih
                                                                    dahulu untuk
                                                                    mengisi
                                                                    jumlah.
                                                                </p>
                                                            ) : (
                                                                activeUnitSampelList.map(
                                                                    (
                                                                        unitSampel,
                                                                    ) => (
                                                                        <div
                                                                            key={
                                                                                unitSampel.id
                                                                            }
                                                                            className="w-full max-w-xs"
                                                                        >
                                                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                                Jumlah{' '}
                                                                                {
                                                                                    unitSampel.nama
                                                                                }{' '}
                                                                                dalam
                                                                                frame
                                                                            </label>
                                                                            <input
                                                                                type="number"
                                                                                min={
                                                                                    0
                                                                                }
                                                                                value={
                                                                                    row
                                                                                        .target_unit_sampel[
                                                                                        String(
                                                                                            unitSampel.id,
                                                                                        )
                                                                                    ] ??
                                                                                    ''
                                                                                }
                                                                                onChange={(
                                                                                    e,
                                                                                ) =>
                                                                                    updateFrameSampelRowTarget(
                                                                                        index,
                                                                                        String(
                                                                                            unitSampel.id,
                                                                                        ),
                                                                                        e
                                                                                            .target
                                                                                            .value,
                                                                                    )
                                                                                }
                                                                                className="mt-1 block h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white"
                                                                                placeholder="Contoh: 2"
                                                                            />
                                                                            <InputError
                                                                                message={
                                                                                    errors[
                                                                                        `kegiatan_frame_sampel.${index}.target_unit_sampel.${unitSampel.id}`
                                                                                    ]
                                                                                }
                                                                                className="mt-1"
                                                                            />
                                                                        </div>
                                                                    ),
                                                                )
                                                            )}
                                                        </div>

                                                        <Button
                                                            type="button"
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() =>
                                                                removeFrameSampelRow(
                                                                    index,
                                                                )
                                                            }
                                                        >
                                                            Hapus
                                                        </Button>
                                                    </div>
                                                </div>
                                            ),
                                        )}
                                    </div>
                                )}
                            </div>

                            {/* Metode Pelatihan */}
                            <div className="space-y-2">
                                <label className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                    Metode Pelatihan Petugas{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Apakah pelatihan petugas dilaksanakan secara
                                    daring, luring, atau hybrid?
                                </p>
                                <div
                                    className={`grid grid-cols-1 gap-3 ${isSensus ? 'sm:grid-cols-3' : 'sm:grid-cols-4'}`}
                                >
                                    {(
                                        [
                                            {
                                                value: 'daring',
                                                label: 'Daring',
                                                desc: '(Online)',
                                            },
                                            {
                                                value: 'luring',
                                                label: 'Luring',
                                                desc: '(Tatap Muka)',
                                            },
                                            {
                                                value: 'hybrid',
                                                label: 'Hybrid',
                                                desc: '(Campuran)',
                                            },
                                            {
                                                value: 'tidak_ada_pelatihan',
                                                label: 'Tidak Ada',
                                                desc: '(Tidak ada pelatihan)',
                                            },
                                        ] as const
                                    )
                                        .filter(
                                            (opt) =>
                                                !isSensus ||
                                                opt.value !==
                                                    'tidak_ada_pelatihan',
                                        )
                                        .map((opt) => (
                                            <label
                                                key={opt.value}
                                                className={`flex cursor-pointer items-center gap-3 rounded-lg border-2 px-4 py-3 transition-colors ${
                                                    data.metode_pelatihan ===
                                                    opt.value
                                                        ? 'border-neutral-900 bg-neutral-50 dark:border-neutral-300 dark:bg-neutral-800'
                                                        : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-700 dark:hover:border-neutral-600'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="metode_pelatihan"
                                                    value={opt.value}
                                                    checked={
                                                        data.metode_pelatihan ===
                                                        opt.value
                                                    }
                                                    onChange={() =>
                                                        setData(
                                                            'metode_pelatihan',
                                                            opt.value,
                                                        )
                                                    }
                                                    className="h-4 w-4 text-neutral-900"
                                                />
                                                <div>
                                                    <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                        {opt.label}
                                                    </span>
                                                    <span className="ml-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {opt.desc}
                                                    </span>
                                                </div>
                                            </label>
                                        ))}
                                </div>
                                <InputError
                                    message={errors.metode_pelatihan}
                                    className="mt-2"
                                />
                            </div>
                            {data.metode_pelatihan !== '' &&
                                data.metode_pelatihan !==
                                    'tidak_ada_pelatihan' && (
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="bulan_pelatihan"
                                            className="text-base font-semibold"
                                        >
                                            Bulan Pelatihan{' '}
                                            <span className="text-red-500">
                                                *
                                            </span>
                                        </Label>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            Pilih bulan pelaksanaan pelatihan
                                            untuk sinkronisasi pengajuan pulsa
                                            pelatihan.
                                        </p>
                                        <SearchableSelect
                                            options={BULAN_OPTIONS.map(
                                                (bulan) => ({
                                                    value: bulan.value,
                                                    label: bulan.label,
                                                }),
                                            )}
                                            value={data.bulan_pelatihan}
                                            onValueChange={(value) =>
                                                setData(
                                                    'bulan_pelatihan',
                                                    value,
                                                )
                                            }
                                            placeholder="Pilih Bulan Pelatihan"
                                            searchPlaceholder="Cari bulan..."
                                            className="mt-1"
                                        />
                                        <InputError
                                            message={errors.bulan_pelatihan}
                                            className="mt-2"
                                        />
                                    </div>
                                )}

                            {!isKetuaTim && (
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="ketua_tim_user_id"
                                        className="text-base font-semibold"
                                    >
                                        Ketua Tim{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <SearchableSelect
                                        options={[
                                            {
                                                value: '',
                                                label: 'Pilih Ketua Tim',
                                            },
                                            ...ketuaTimUsers.map((user) => ({
                                                value: user.id.toString(),
                                                label: `${user.name} (${user.email})`,
                                                searchKeywords: `${user.name} ${user.email}`,
                                            })),
                                        ]}
                                        value={data.ketua_tim_user_id}
                                        onValueChange={(value) =>
                                            setData('ketua_tim_user_id', value)
                                        }
                                        placeholder="Pilih Ketua Tim"
                                        searchPlaceholder="Cari ketua tim..."
                                        className="mt-1"
                                    />
                                    <InputError
                                        message={errors.ketua_tim_user_id}
                                        className="mt-2"
                                    />
                                </div>
                            )}

                            {/* PJ Lainnya - Optional */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="pj_lainnya_id"
                                    className="text-base font-semibold"
                                >
                                    Ketua Tim Lainnya (opsional)
                                </Label>
                                <SearchableSelect
                                    options={[
                                        {
                                            value: '',
                                            label: 'Pilih Ketua Tim Lainnya (opsional)',
                                        },
                                        ...pjLainnyaUsers.map((user) => ({
                                            value: user.id.toString(),
                                            label: `${user.name} (${user.email})`,
                                            searchKeywords: `${user.name} ${user.email}`,
                                        })),
                                    ]}
                                    value={data.pj_lainnya_id}
                                    onValueChange={(value) =>
                                        setData('pj_lainnya_id', value)
                                    }
                                    placeholder="Pilih Ketua Tim Lainnya (opsional)"
                                    searchPlaceholder="Cari ketua tim..."
                                    className="mt-1"
                                />
                                <InputError
                                    message={errors.pj_lainnya_id}
                                    className="mt-2"
                                />
                            </div>

                            {/* Grid untuk tanggal */}
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tanggal Mulai */}
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="tanggal_mulai"
                                        className="text-base font-semibold"
                                    >
                                        Tanggal Mulai{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <DatePicker
                                        id="tanggal_mulai"
                                        value={data.tanggal_mulai}
                                        onChange={(v) =>
                                            setData('tanggal_mulai', v)
                                        }
                                        className="h-11"
                                    />
                                    <InputError
                                        message={errors.tanggal_mulai}
                                        className="mt-2"
                                    />
                                </div>

                                {/* Tanggal Selesai */}
                                <div className="space-y-2">
                                    <Label
                                        htmlFor="tanggal_selesai"
                                        className="text-base font-semibold"
                                    >
                                        Tanggal Selesai{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <DatePicker
                                        id="tanggal_selesai"
                                        value={data.tanggal_selesai}
                                        onChange={(v) =>
                                            setData('tanggal_selesai', v)
                                        }
                                        className="h-11"
                                    />
                                    <InputError
                                        message={errors.tanggal_selesai}
                                        className="mt-2"
                                    />
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            asChild
                            className="gap-2"
                            disabled={processing}
                        >
                            <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                                <X className="h-5 w-5" />
                                Batal
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="min-w-[200px] gap-2"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <Save className="h-5 w-5" />
                                    Simpan Perubahan
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
