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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useDecryptedData, useDecryptedObject } from '@/hooks/useDecryptedData';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { previewFileFromPost } from '@/utils/downloadUtils';
import { encryptFilters } from '@/utils/encryption';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Calendar, Eye, FileText, Upload, User } from 'lucide-react';
import { useRef, useState } from 'react';

interface Petugas {
    id: number;
    nama: string;
    nik: string;
    tempat_lahir: string | null;
    tanggal_lahir: string | null;
    alamat: string | null;
    no_rekening: string | null;
    nama_bank: string | null;
    npwp: string | null;
}

interface KetuaTim {
    nama: string | null;
    nip: string | null;
}

interface KegiatanDetail {
    kegiatan_id: number;
    kode_kegiatan: string;
    nama_kegiatan: string;
    tanggal_selesai: string;
    tanggal_selesai_label: string;
    peran: string;
    hasil_listing: number | null;
    hasil_pendataan_lapangan: number | null;
    hasil_pengolahan: number | null;
    spk_id: number;
    nomor_spk: string;
}

interface SpkItem {
    spk_id: number;
    spk_hashed_id: string;
    nomor_spk: string;
    nomor_bast_preview: string | null;
    tanggal_spk: string;
    tanggal_mulai_kerja: string;
    tanggal_selesai_kerja_asli: string;
    tanggal_berakhir_paling_akhir: string;
    nama_ppk: string;
    nip_ppk: string | null;
    petugas: Petugas;
    ketua_tim: KetuaTim;
    kegiatan_list: KegiatanDetail[];
    jumlah_kegiatan: number;
    has_bast?: boolean;
    existing_bast_hashed_id?: string | null;
    existing_bast_nomor?: string | null;
    lampiran_total?: number;
    lampiran_generated?: number;
    lampiran_signed?: number;
    is_sensus_ekonomi?: boolean;
    muatan_prelist_default?: number;
    muatan_prelist_keluarga_default?: number;
    muatan_prelist_usaha_default?: number;
    unit_sampel_pencacahan_items?: { id: number; nama: string }[];
}

interface CreateForMonthProps {
    bulan: number;
    tahun: number;
    bulan_label: string;
    spk_list: {
        encrypted: string;
    };
    imported_sensus_inputs?: {
        encrypted: string;
    };
    mode?: 'create' | 'detail';
}

interface SensusImportRow {
    nomor_spk?: string;
    nik_petugas?: string;
    nama_petugas?: string;
    muatan_prelist_keluarga?: number | null;
    muatan_prelist_usaha?: number | null;
    realisasi_keluarga?: number | null;
    realisasi_usaha?: number | null;
    realisasi_unit_sampel?: Record<string, number>;
}

interface SensusReferenceInput {
    realisasi_unit_sampel?: Record<string, string>;
    fasih_screenshot_path?: string | null;
    fasih_screenshot_uploaded_at?: string | null;
}

interface ImagePreviewState {
    open: boolean;
    title: string;
    src: string;
    alt: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'BAST', href: '/bast' },
    { title: 'Generate BAST', href: '#' },
];

const getPeranLabel = (peran: string): string => {
    const labels: Record<string, string> = {
        pcl_ppl: 'Petugas Lapangan',
        pml: 'Petugas Pemeriksa Lapangan',
        pengolahan: 'Petugas Pengolahan',
        pengawas_pengolahan: 'Pemeriksa Pengolahan',
    };
    return labels[peran] || peran;
};

const getUnitKey = (unit: { id: number; nama: string }): string =>
    unit.nama.trim().toLowerCase().replace(/\s+/g, '_');

const getUnitLabel = (unit: { id: number; nama: string }): string => unit.nama;

const getPrelistTargetByUnit = (
    spk: SpkItem,
    unitKey: string,
): number | null => {
    if (unitKey.includes('usaha')) {
        return spk.muatan_prelist_usaha_default ?? 0;
    }

    if (unitKey.includes('keluarga') || unitKey.includes('rumah_tangga')) {
        return spk.muatan_prelist_keluarga_default ?? 0;
    }

    return null;
};

export default function CreateForMonth({
    bulan,
    tahun,
    bulan_label,
    spk_list,
    imported_sensus_inputs,
    mode = 'create',
}: CreateForMonthProps) {
    const { auth } = usePage<SharedData>().props;
    const decryptedSpkList = useDecryptedData<SpkItem>(spk_list.encrypted);
    const importedSensusInputs = useDecryptedObject<
        Record<
            number,
            {
                realisasi_unit_sampel?: Record<string, number | string>;
                fasih_screenshot_path?: string | null;
                fasih_screenshot_uploaded_at?: string | null;
            }
        >
    >(imported_sensus_inputs?.encrypted);
    const isAdminOrOperator =
        auth.activeRole?.name === 'admin' ||
        auth.activeRole?.name === 'operator';
    const isDetailMode = mode === 'detail';

    // Urutkan SPKs berdasarkan tanggal_berakhir_paling_akhir (ASC) kemudian nama petugas (A-Z)
    const sortedSpkList = [...decryptedSpkList].sort((a, b) => {
        // Compare by tanggal_berakhir_paling_akhir first
        const dateCompare = a.tanggal_berakhir_paling_akhir.localeCompare(
            b.tanggal_berakhir_paling_akhir,
        );
        if (dateCompare !== 0) return dateCompare;

        // If dates are equal, compare by nama petugas (A-Z)
        return a.petugas.nama.localeCompare(b.petugas.nama);
    });

    const [selectedSpks, setSelectedSpks] = useState<number[]>([]);
    const [isGenerating, setIsGenerating] = useState(false);
    const [isImportingSensusTemplate, setIsImportingSensusTemplate] =
        useState(false);
    const [savingTarget, setSavingTarget] = useState<number | null>(null);
    const [uploadingScreenshotTarget, setUploadingScreenshotTarget] = useState<
        number | null
    >(null);
    const importSensusFileInputRef = useRef<HTMLInputElement | null>(null);
    const [seInputs, setSeInputs] = useState<
        Record<number, SensusReferenceInput>
    >(() => {
        const persistedInputs = importedSensusInputs ?? {};

        return Object.fromEntries(
            Object.entries(persistedInputs).map(([spkId, value]) => [
                Number(spkId),
                {
                    realisasi_unit_sampel: Object.fromEntries(
                        Object.entries(value?.realisasi_unit_sampel ?? {}).map(
                            ([unitType, unitValue]) => [
                                unitType,
                                String(unitValue),
                            ],
                        ),
                    ),
                    fasih_screenshot_path: value?.fasih_screenshot_path ?? null,
                    fasih_screenshot_uploaded_at:
                        value?.fasih_screenshot_uploaded_at ?? null,
                },
            ]),
        );
    });
    const [modalAlert, setModalAlert] = useState<{
        open: boolean;
        title: string;
        message: string;
    }>({
        open: false,
        title: '',
        message: '',
    });
    const [imagePreview, setImagePreview] = useState<ImagePreviewState>({
        open: false,
        title: '',
        src: '',
        alt: '',
    });
    const [lampiranSelectDialog, setLampiranSelectDialog] = useState<{
        open: boolean;
        spk: SpkItem | null;
    }>({ open: false, spk: null });

    const showModalAlert = (title: string, message: string) => {
        setModalAlert({
            open: true,
            title,
            message,
        });
    };

    const openImagePreview = (title: string, src: string, alt: string) => {
        setImagePreview({
            open: true,
            title,
            src,
            alt,
        });
    };

    const handleSelectAll = () => {
        if (selectedSpks.length === sortedSpkList.length) {
            setSelectedSpks([]);
        } else {
            setSelectedSpks(sortedSpkList.map((spk) => spk.spk_id));
        }
    };

    const handleSelectSpk = (spkId: number) => {
        if (selectedSpks.includes(spkId)) {
            setSelectedSpks(selectedSpks.filter((id) => id !== spkId));
        } else {
            setSelectedSpks([...selectedSpks, spkId]);
        }
    };

    const getCsrfToken = () =>
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    const getSensusUnitItems = (spk: SpkItem) =>
        spk.unit_sampel_pencacahan_items ?? [];

    const getFilledRealisasiValues = (spk: SpkItem) =>
        Object.fromEntries(
            getSensusUnitItems(spk)
                .map((unit) => {
                    const unitKey = getUnitKey(unit);
                    const value =
                        seInputs[spk.spk_id]?.realisasi_unit_sampel?.[
                            unitKey
                        ] ?? '';

                    return [unitKey, value] as const;
                })
                .filter(([, value]) => value !== ''),
        );

    const isSensusInputCompleteForSpk = (spkId: number): boolean => {
        const spk = sortedSpkList.find((item) => item.spk_id === spkId);
        if (!spk) {
            return false;
        }

        const unitItems = getSensusUnitItems(spk);

        return (
            unitItems.length > 0 &&
            unitItems.every((unit) => {
                const unitKey = getUnitKey(unit);
                const value =
                    seInputs[spkId]?.realisasi_unit_sampel?.[unitKey] ?? '';

                return value !== '';
            })
        );
    };

    const hasSensusFasihScreenshotForSpk = (spkId: number): boolean => {
        const spk = sortedSpkList.find((item) => item.spk_id === spkId);
        if (!spk || !spk.is_sensus_ekonomi) {
            return true;
        }

        return Boolean(seInputs[spkId]?.fasih_screenshot_path);
    };

    const buildSensusPreviewInput = (spk: SpkItem) => {
        const realisasiByUnit = Object.fromEntries(
            getSensusUnitItems(spk).map((unit) => {
                const unitKey = getUnitKey(unit);

                return [unitKey, getUnitInputValue(spk.spk_id, unitKey)];
            }),
        );

        if (Object.values(realisasiByUnit).some((value) => value === '')) {
            return null;
        }

        const normalizedRealisasi = Object.fromEntries(
            Object.entries(realisasiByUnit).map(([unitKey, value]) => [
                unitKey,
                Number(value),
            ]),
        );

        return {
            muatan_input: Object.values(normalizedRealisasi).reduce(
                (acc, value) => acc + value,
                0,
            ),
            muatan_prelist: spk.muatan_prelist_default ?? 0,
            realisasi_unit_sampel: normalizedRealisasi,
        };
    };

    const persistSensusReference = async (spk: SpkItem) => {
        const realisasiValues = getFilledRealisasiValues(spk);
        if (Object.keys(realisasiValues).length === 0) {
            return;
        }

        setSavingTarget(spk.spk_id);

        try {
            const response = await fetch('/bast/sensus-reference', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    spk_id: spk.spk_id,
                    bulan,
                    tahun,
                    realisasi_unit_sampel: Object.fromEntries(
                        Object.entries(realisasiValues).map(
                            ([unitKey, value]) => [unitKey, Number(value)],
                        ),
                    ),
                }),
            });

            if (!response.ok) {
                throw new Error('Gagal menyimpan referensi realisasi.');
            }
        } catch (error) {
            showModalAlert(
                'Simpan Gagal',
                error instanceof Error
                    ? error.message
                    : 'Referensi realisasi tidak berhasil disimpan.',
            );
        } finally {
            setSavingTarget(null);
        }
    };

    const uploadSharedScreenshot = async (
        spk: SpkItem,
        event: React.ChangeEvent<HTMLInputElement>,
    ) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        setUploadingScreenshotTarget(spk.spk_id);

        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('spk_id', String(spk.spk_id));
            formData.append('bulan', String(bulan));
            formData.append('tahun', String(tahun));

            const response = await fetch(
                '/bast/sensus-reference/upload-fasih-screenshot',
                {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: formData,
                },
            );

            const result = await response.json();

            if (!response.ok) {
                throw new Error(
                    result?.message ??
                        'Screenshot Fasih tidak berhasil diunggah.',
                );
            }

            setSeInputs((prev) => ({
                ...prev,
                [spk.spk_id]: {
                    ...prev[spk.spk_id],
                    fasih_screenshot_path:
                        result?.data?.fasih_screenshot_path ?? null,
                    fasih_screenshot_uploaded_at:
                        result?.data?.fasih_screenshot_uploaded_at ?? null,
                },
            }));
        } catch (error) {
            showModalAlert(
                'Upload Gagal',
                error instanceof Error
                    ? error.message
                    : 'Screenshot Fasih tidak berhasil diunggah.',
            );
        } finally {
            setUploadingScreenshotTarget(null);
            event.target.value = '';
        }
    };

    const handleGenerateBast = () => {
        if (selectedSpks.length === 0) {
            showModalAlert(
                'Data Belum Lengkap',
                'Pilih minimal 1 Perjanjian Kerja untuk generate BAST main.',
            );
            return;
        }

        const selectedSpkItems = sortedSpkList.filter((spk) =>
            selectedSpks.includes(spk.spk_id),
        );

        const hasIncompleteSensusInput = selectedSpkItems.some(
            (spk) =>
                spk.is_sensus_ekonomi &&
                !isSensusInputCompleteForSpk(spk.spk_id),
        );

        if (hasIncompleteSensusInput) {
            showModalAlert(
                'Data Belum Lengkap',
                'Realisasi untuk semua unit sampel wajib diisi pada setiap SPK Sensus Ekonomi yang dipilih.',
            );
            return;
        }

        const seInputsPayload = Object.fromEntries(
            selectedSpkItems
                .filter((spk) => spk.is_sensus_ekonomi)
                .map((spk) => {
                    const item = seInputs[spk.spk_id] ?? {};
                    const realisasiValues = Object.fromEntries(
                        Object.entries(item.realisasi_unit_sampel ?? {}).map(
                            ([unitId, value]) => [
                                unitId,
                                value !== '' ? Number(value) : null,
                            ],
                        ),
                    );

                    const muatanInput = Object.values(realisasiValues).reduce(
                        (acc: number, value) =>
                            acc + (typeof value === 'number' ? value : 0),
                        0,
                    );

                    const muatanPrelist = spk.muatan_prelist_default ?? 0;

                    return [
                        spk.spk_id,
                        {
                            muatan_input: muatanInput,
                            muatan_prelist: muatanPrelist,
                            realisasi_unit_sampel: realisasiValues,
                        },
                    ];
                }),
        );

        const payload = {
            spk_ids: selectedSpks,
            bulan,
            tahun,
            se_inputs: seInputsPayload,
        };

        const encryptedPayload = encryptFilters(payload);

        setIsGenerating(true);
        router.post(
            '/bast/generate-batch',
            {
                encrypted_filters: encryptedPayload,
            },
            {
                onFinish: () => setIsGenerating(false),
            },
        );
    };

    const handlePreviewSpk = async (spk: SpkItem) => {
        const payload: Record<string, unknown> = {
            spk_id: spk.spk_id,
        };

        if (spk.is_sensus_ekonomi) {
            const seInput = buildSensusPreviewInput(spk);
            if (!seInput) {
                showModalAlert(
                    'Data Belum Lengkap',
                    'Isi realisasi untuk semua unit sampel terlebih dahulu sebelum preview BAST.',
                );
                return;
            }

            payload.se_input = seInput;
        }

        // Don't send nomor_bast - let backend generate the latest number
        // if (nomorBast) {
        //     payload.nomor_bast = nomorBast;
        // }

        const encryptedPayload = encryptFilters(payload);

        try {
            await previewFileFromPost(
                '/bast/preview-bast',
                {
                    encrypted_filters: encryptedPayload,
                },
                'Preview_BAST.pdf',
            );
        } catch {
            showModalAlert(
                'Preview Gagal',
                'Gagal membuka preview BAST. Silakan coba lagi.',
            );
        }
    };

    const handlePreviewLampiran = async (spk: SpkItem, kegiatanId?: number) => {
        const payload: Record<string, unknown> = { spk_id: spk.spk_id };

        if (spk.is_sensus_ekonomi) {
            if (!hasSensusFasihScreenshotForSpk(spk.spk_id)) {
                showModalAlert(
                    'Preview Dinonaktifkan',
                    'Screenshot Fasih belum diunggah. Unggah screenshot Fasih terlebih dahulu sebelum preview lampiran.',
                );

                return;
            }

            const seInput = buildSensusPreviewInput(spk);
            if (!seInput) {
                showModalAlert(
                    'Data Belum Lengkap',
                    'Isi realisasi untuk semua unit sampel terlebih dahulu sebelum preview lampiran.',
                );
                return;
            }

            payload.se_input = seInput;
        }

        if (kegiatanId) {
            payload.kegiatan_id = kegiatanId;
        }

        const encryptedPayload = encryptFilters(payload);

        try {
            await previewFileFromPost(
                '/bast/lampiran-action/preview',
                { encrypted_filters: encryptedPayload },
                'Preview_Lampiran.pdf',
            );
        } catch (error) {
            showModalAlert(
                'Preview Gagal',
                error instanceof Error && error.message.trim() !== ''
                    ? error.message
                    : 'Gagal membuka preview lampiran. Silakan coba lagi.',
            );
        }
    };

    const handlePreviewLampiranClick = (spk: SpkItem) => {
        if (
            spk.is_sensus_ekonomi &&
            !hasSensusFasihScreenshotForSpk(spk.spk_id)
        ) {
            showModalAlert(
                'Preview Dinonaktifkan',
                'Screenshot Fasih belum diunggah. Unggah screenshot Fasih terlebih dahulu sebelum preview lampiran.',
            );

            return;
        }

        if (isAdminOrOperator) {
            handlePreviewLampiran(spk);
        } else {
            setLampiranSelectDialog({ open: true, spk });
        }
    };

    const hasSensusEkonomiSpk = sortedSpkList.some(
        (item) => item.is_sensus_ekonomi,
    );

    const updateSeUnitInput = (
        spkId: number,
        unitType: string,
        value: string,
    ) => {
        setSeInputs((prev) => ({
            ...prev,
            [spkId]: {
                ...prev[spkId],
                realisasi_unit_sampel: {
                    ...(prev[spkId]?.realisasi_unit_sampel ?? {}),
                    [unitType]: value,
                },
            },
        }));
    };

    const getUnitInputValue = (spkId: number, unitType: string) => {
        return seInputs[spkId]?.realisasi_unit_sampel?.[unitType] ?? '';
    };

    const applyImportedSensusRows = (rows: SensusImportRow[]) => {
        const mapByNomorSpk = new Map<string, SpkItem>();
        const mapByNik = new Map<string, SpkItem>();

        sortedSpkList.forEach((spk) => {
            mapByNomorSpk.set(spk.nomor_spk.trim().toLowerCase(), spk);
            mapByNik.set((spk.petugas.nik ?? '').replace(/\D+/g, ''), spk);
        });

        const nextInputs: Record<number, SensusReferenceInput> = {};
        const matchedSpkIds = new Set<number>();

        rows.forEach((row) => {
            const nomorSpkKey = (row.nomor_spk ?? '').trim().toLowerCase();
            const nikKey = (row.nik_petugas ?? '').replace(/\D+/g, '');

            const matchedSpk =
                (nomorSpkKey ? mapByNomorSpk.get(nomorSpkKey) : undefined) ??
                (nikKey ? mapByNik.get(nikKey) : undefined);

            if (!matchedSpk) {
                return;
            }

            matchedSpkIds.add(matchedSpk.spk_id);

            const normalizedRealisasi: Record<string, string> =
                Object.fromEntries(
                    Object.entries(row.realisasi_unit_sampel ?? {}).map(
                        ([unitKey, unitValue]) => [unitKey, String(unitValue)],
                    ),
                );

            if (Object.keys(normalizedRealisasi).length === 0) {
                const keluargaValue =
                    row.realisasi_keluarga ??
                    row.realisasi_unit_sampel?.keluarga ??
                    null;
                if (keluargaValue !== null && keluargaValue !== undefined) {
                    normalizedRealisasi.keluarga = String(keluargaValue);
                }

                const usahaValue =
                    row.realisasi_usaha ??
                    row.realisasi_unit_sampel?.usaha ??
                    null;
                if (usahaValue !== null && usahaValue !== undefined) {
                    normalizedRealisasi.usaha = String(usahaValue);
                }
            }

            const existingInput = seInputs[matchedSpk.spk_id];

            nextInputs[matchedSpk.spk_id] = {
                realisasi_unit_sampel: normalizedRealisasi,
                fasih_screenshot_path:
                    existingInput?.fasih_screenshot_path ?? null,
                fasih_screenshot_uploaded_at:
                    existingInput?.fasih_screenshot_uploaded_at ?? null,
            };
        });

        setSeInputs((prev) => ({
            ...prev,
            ...nextInputs,
        }));

        if (matchedSpkIds.size > 0) {
            setSelectedSpks((prev) =>
                Array.from(new Set([...prev, ...matchedSpkIds])),
            );
        }

        showModalAlert(
            'Import Realisasi Selesai',
            `Baris template diproses: ${rows.length}. SPK cocok: ${matchedSpkIds.size}.`,
        );
    };

    const handleImportSensusTemplate = async (
        event: React.ChangeEvent<HTMLInputElement>,
    ) => {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        setIsImportingSensusTemplate(true);
        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('bulan', String(bulan));
            formData.append('tahun', String(tahun));

            const csrfToken =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';

            const response = await fetch('/bast/import/sensus-realisasi', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: formData,
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error(
                    payload?.message ?? 'Gagal mengimpor template.',
                );
            }

            applyImportedSensusRows((payload?.rows ?? []) as SensusImportRow[]);
        } catch (error) {
            const message =
                error instanceof Error
                    ? error.message
                    : 'File template realisasi tidak dapat diproses. Pastikan format mengikuti template terbaru.';
            showModalAlert('Import Gagal', message);
        } finally {
            setIsImportingSensusTemplate(false);
            if (importSensusFileInputRef.current) {
                importSensusFileInputRef.current.value = '';
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Generate BAST - ${bulan_label} ${tahun}`} />

            <Dialog
                open={modalAlert.open}
                onOpenChange={(open) =>
                    setModalAlert((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{modalAlert.title}</DialogTitle>
                        <DialogDescription>
                            {modalAlert.message}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            onClick={() =>
                                setModalAlert((prev) => ({
                                    ...prev,
                                    open: false,
                                }))
                            }
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={lampiranSelectDialog.open}
                onOpenChange={(open) =>
                    setLampiranSelectDialog((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Pilih Kegiatan</DialogTitle>
                        <DialogDescription>
                            Pilih kegiatan yang akan dipreview lampirannya.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2">
                        {lampiranSelectDialog.spk?.kegiatan_list.map((keg) => (
                            <Button
                                key={keg.kegiatan_id}
                                variant="outline"
                                className="w-full justify-start"
                                disabled={
                                    lampiranSelectDialog.spk
                                        ?.is_sensus_ekonomi &&
                                    !hasSensusFasihScreenshotForSpk(
                                        lampiranSelectDialog.spk.spk_id,
                                    )
                                }
                                onClick={() => {
                                    setLampiranSelectDialog((prev) => ({
                                        ...prev,
                                        open: false,
                                    }));
                                    handlePreviewLampiran(
                                        lampiranSelectDialog.spk!,
                                        keg.kegiatan_id,
                                    );
                                }}
                            >
                                {keg.nama_kegiatan}
                            </Button>
                        ))}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() =>
                                setLampiranSelectDialog((prev) => ({
                                    ...prev,
                                    open: false,
                                }))
                            }
                        >
                            Batal
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={imagePreview.open}
                onOpenChange={(open) =>
                    setImagePreview((prev) => ({ ...prev, open }))
                }
            >
                <DialogContent className="max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>{imagePreview.title}</DialogTitle>
                        <DialogDescription>
                            Pratinjau screenshot Fasih tanpa membuka tab baru.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="max-h-[70vh] overflow-auto rounded-lg border border-neutral-200 bg-neutral-50 p-2 dark:border-neutral-700 dark:bg-neutral-900">
                        {imagePreview.src && (
                            <img
                                src={imagePreview.src}
                                alt={imagePreview.alt}
                                className="mx-auto max-h-[65vh] w-full object-contain"
                            />
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                setImagePreview((prev) => ({
                                    ...prev,
                                    open: false,
                                }))
                            }
                        >
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <PageHeader
                title={`${isDetailMode ? 'Detail BAST' : 'Generate BAST'} - ${bulan_label} ${tahun}`}
                description={
                    isDetailMode
                        ? 'Daftar petugas, status BAST, dan lampiran periode ini (termasuk yang belum digenerate).'
                        : 'Pilih perjanjian kerja yang akan dibuatkan dokumen BAST'
                }
            >
                <div className="flex items-center gap-2">
                    <Button variant="outline" asChild>
                        <Link href="/bast">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                    {hasSensusEkonomiSpk && isAdminOrOperator && (
                        <>
                            <Button variant="outline" asChild>
                                <a
                                    href={`/bast/template/sensus-realisasi?bulan=${bulan}&tahun=${tahun}`}
                                >
                                    Download Template Realisasi SE
                                </a>
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() =>
                                    importSensusFileInputRef.current?.click()
                                }
                                disabled={isImportingSensusTemplate}
                            >
                                <Upload className="mr-2 h-4 w-4" />
                                {isImportingSensusTemplate
                                    ? 'Mengimpor...'
                                    : 'Import Realisasi SE'}
                            </Button>
                            <Input
                                ref={importSensusFileInputRef}
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                className="hidden"
                                onChange={handleImportSensusTemplate}
                            />
                        </>
                    )}
                    {!isDetailMode &&
                        isAdminOrOperator &&
                        selectedSpks.length > 0 && (
                            <Button
                                onClick={handleGenerateBast}
                                disabled={
                                    isGenerating ||
                                    sortedSpkList
                                        .filter((spk) =>
                                            selectedSpks.includes(spk.spk_id),
                                        )
                                        .some(
                                            (spk) =>
                                                spk.is_sensus_ekonomi &&
                                                !isSensusInputCompleteForSpk(
                                                    spk.spk_id,
                                                ),
                                        )
                                }
                            >
                                <FileText className="mr-2 h-4 w-4" />
                                Generate {selectedSpks.length} BAST
                            </Button>
                        )}
                </div>
            </PageHeader>

            {!isDetailMode && !isAdminOrOperator && (
                <ContentCard>
                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                        Halaman ini dipakai admin atau operator untuk membuat
                        BAST main. Lampiran per kegiatan dilanjutkan dari
                        halaman detail BAST oleh ketua tim terkait.
                    </p>
                </ContentCard>
            )}

            <ContentCard>
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                Daftar Perjanjian Kerja
                            </h3>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                {isDetailMode
                                    ? `${sortedSpkList.length} Perjanjian Kerja pada periode ini`
                                    : `${sortedSpkList.length} Perjanjian Kerja belum memiliki BAST di periode ini`}
                            </p>
                        </div>
                        {!isDetailMode && isAdminOrOperator && (
                            <Button variant="outline" onClick={handleSelectAll}>
                                {selectedSpks.length === sortedSpkList.length
                                    ? 'Batal Pilih Semua'
                                    : 'Pilih Semua'}
                            </Button>
                        )}
                    </div>

                    <div className="space-y-4">
                        {sortedSpkList.map((spk) => (
                            <div
                                key={spk.spk_id}
                                onClick={() =>
                                    !isDetailMode &&
                                    isAdminOrOperator &&
                                    handleSelectSpk(spk.spk_id)
                                }
                                className={`rounded-lg border p-4 transition-colors ${
                                    !isDetailMode && isAdminOrOperator
                                        ? 'cursor-pointer'
                                        : 'cursor-default'
                                } ${
                                    !isDetailMode &&
                                    isAdminOrOperator &&
                                    selectedSpks.includes(spk.spk_id)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700'
                                }`}
                            >
                                <div className="flex items-start gap-4">
                                    {!isDetailMode && isAdminOrOperator && (
                                        <input
                                            type="checkbox"
                                            checked={selectedSpks.includes(
                                                spk.spk_id,
                                            )}
                                            onChange={() => {}}
                                            onClick={(e) => e.stopPropagation()}
                                            className="pointer-events-none mt-1 h-4 w-4 rounded border-neutral-300"
                                        />
                                    )}
                                    <div className="flex-1 space-y-3">
                                        <div className="flex items-start justify-between">
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <User className="h-4 w-4 text-neutral-500" />
                                                    <span className="font-semibold text-neutral-900 dark:text-white">
                                                        {spk.petugas.nama}
                                                    </span>
                                                    <Badge variant="secondary">
                                                        {spk.petugas.nik}
                                                    </Badge>
                                                </div>
                                                <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                                    Perjanjian Kerja:{' '}
                                                    {spk.nomor_spk}
                                                </p>
                                                {isDetailMode && (
                                                    <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                                        BAST:{' '}
                                                        {spk.existing_bast_nomor ??
                                                            'Belum digenerate'}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="text-right">
                                                <Badge variant="outline">
                                                    <Calendar className="mr-1 h-3 w-3" />
                                                    BAST:{' '}
                                                    {new Date(
                                                        spk.tanggal_berakhir_paling_akhir,
                                                    ).toLocaleDateString(
                                                        'id-ID',
                                                    )}
                                                </Badge>
                                                {isDetailMode && (
                                                    <div className="mt-2">
                                                        <Badge
                                                            variant={
                                                                spk.has_bast
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {spk.has_bast
                                                                ? `Lampiran ${spk.lampiran_generated ?? 0}/${spk.lampiran_total ?? 0}`
                                                                : 'BAST belum digenerate'}
                                                        </Badge>
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="rounded-md bg-neutral-50 p-3 dark:bg-neutral-800">
                                            <p className="mb-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                                Kegiatan yang Diikuti (
                                                {spk.jumlah_kegiatan}):
                                            </p>
                                            <div className="space-y-2">
                                                {spk.kegiatan_list.map(
                                                    (keg, idx) => (
                                                        <div
                                                            key={idx}
                                                            className="flex items-center justify-between text-sm"
                                                        >
                                                            <div className="flex-1">
                                                                <span className="font-medium text-neutral-900 dark:text-white">
                                                                    {
                                                                        keg.nama_kegiatan
                                                                    }
                                                                </span>
                                                                <span className="text-neutral-600 dark:text-neutral-400">
                                                                    {' '}
                                                                    •{' '}
                                                                    {getPeranLabel(
                                                                        keg.peran,
                                                                    )}
                                                                </span>
                                                            </div>
                                                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                Berakhir:{' '}
                                                                {
                                                                    keg.tanggal_selesai_label
                                                                }
                                                            </span>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-4 text-xs text-neutral-600 dark:text-neutral-400">
                                            {spk.ketua_tim.nama && (
                                                <span>
                                                    Ketua Tim:{' '}
                                                    {spk.ketua_tim.nama}
                                                    {spk.ketua_tim.nip &&
                                                        ` (${spk.ketua_tim.nip})`}
                                                </span>
                                            )}
                                        </div>

                                        {spk.is_sensus_ekonomi &&
                                            !isDetailMode &&
                                            isAdminOrOperator && (
                                                <div
                                                    className="rounded-md border border-neutral-200 p-3 dark:border-neutral-700"
                                                    onClick={(e) =>
                                                        e.stopPropagation()
                                                    }
                                                >
                                                    <p className="mb-3 text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                                        Input BAST Sensus
                                                        Ekonomi 2026
                                                    </p>
                                                    <div className="grid gap-3 md:grid-cols-2">
                                                        {getSensusUnitItems(
                                                            spk,
                                                        ).map((unit) => {
                                                            const unitKey =
                                                                getUnitKey(
                                                                    unit,
                                                                );

                                                            return (
                                                                <div
                                                                    key={`prelist-${unit.id}`}
                                                                >
                                                                    <Label>
                                                                        Muatan
                                                                        prelist
                                                                        ({' '}
                                                                        {getUnitLabel(
                                                                            unit,
                                                                        )}{' '}
                                                                        )
                                                                    </Label>
                                                                    <Input
                                                                        value={
                                                                            getPrelistTargetByUnit(
                                                                                spk,
                                                                                unitKey,
                                                                            ) ??
                                                                            '-'
                                                                        }
                                                                        readOnly
                                                                        disabled
                                                                    />
                                                                </div>
                                                            );
                                                        })}
                                                        {getSensusUnitItems(
                                                            spk,
                                                        ).map((unit) => {
                                                            const unitKey =
                                                                getUnitKey(
                                                                    unit,
                                                                );

                                                            return (
                                                                <div
                                                                    key={
                                                                        unit.id
                                                                    }
                                                                >
                                                                    <Label>
                                                                        Realisasi
                                                                        ({' '}
                                                                        {getUnitLabel(
                                                                            unit,
                                                                        )}{' '}
                                                                        )
                                                                    </Label>
                                                                    <Input
                                                                        type="number"
                                                                        min={0}
                                                                        value={getUnitInputValue(
                                                                            spk.spk_id,
                                                                            unitKey,
                                                                        )}
                                                                        onChange={(
                                                                            e,
                                                                        ) => {
                                                                            updateSeUnitInput(
                                                                                spk.spk_id,
                                                                                unitKey,
                                                                                e
                                                                                    .target
                                                                                    .value,
                                                                            );
                                                                        }}
                                                                        onBlur={() =>
                                                                            persistSensusReference(
                                                                                spk,
                                                                            )
                                                                        }
                                                                    />
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                    <div>
                                                        <Label>Status</Label>
                                                        <Input
                                                            value={
                                                                savingTarget ===
                                                                spk.spk_id
                                                                    ? 'Menyimpan referensi...'
                                                                    : 'Referensi tersimpan di database'
                                                            }
                                                            readOnly
                                                            disabled
                                                        />
                                                    </div>

                                                    <div className="mt-4 flex flex-wrap items-center gap-3">
                                                        <Label
                                                            htmlFor={`fasih-screenshot-${spk.spk_id}`}
                                                            className="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-input px-4 text-sm font-semibold transition-colors hover:bg-accent hover:text-accent-foreground"
                                                        >
                                                            <Upload className="h-4 w-4" />
                                                            {uploadingScreenshotTarget ===
                                                            spk.spk_id
                                                                ? 'Mengunggah...'
                                                                : seInputs[
                                                                        spk
                                                                            .spk_id
                                                                    ]
                                                                        ?.fasih_screenshot_path
                                                                  ? 'Ganti Screenshot Fasih'
                                                                  : 'Upload Screenshot Fasih'}
                                                        </Label>
                                                        <Input
                                                            id={`fasih-screenshot-${spk.spk_id}`}
                                                            type="file"
                                                            accept="image/png,image/jpeg,image/webp"
                                                            onChange={(event) =>
                                                                uploadSharedScreenshot(
                                                                    spk,
                                                                    event,
                                                                )
                                                            }
                                                            className="hidden"
                                                        />
                                                        {seInputs[spk.spk_id]
                                                            ?.fasih_screenshot_path && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                className="inline-flex items-center gap-2 px-0 text-sm font-medium text-blue-600 hover:bg-transparent hover:text-blue-700 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                                                onClick={() =>
                                                                    openImagePreview(
                                                                        'Screenshot Fasih',
                                                                        `/${seInputs[spk.spk_id]?.fasih_screenshot_path}`,
                                                                        `Screenshot Fasih ${spk.petugas.nama}`,
                                                                    )
                                                                }
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                                Lihat Screenshot
                                                                Fasih
                                                            </Button>
                                                        )}
                                                    </div>
                                                </div>
                                            )}

                                        <div className="mt-3 flex justify-end gap-2">
                                            {!isDetailMode &&
                                                isAdminOrOperator && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            handlePreviewSpk(
                                                                spk,
                                                            );
                                                        }}
                                                        disabled={
                                                            spk.is_sensus_ekonomi &&
                                                            !isSensusInputCompleteForSpk(
                                                                spk.spk_id,
                                                            )
                                                        }
                                                    >
                                                        <Eye className="mr-1 h-3 w-3" />
                                                        Preview BAST
                                                    </Button>
                                                )}
                                            {!isDetailMode && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        handlePreviewLampiranClick(
                                                            spk,
                                                        );
                                                    }}
                                                    disabled={
                                                        spk.is_sensus_ekonomi &&
                                                        (!isSensusInputCompleteForSpk(
                                                            spk.spk_id,
                                                        ) ||
                                                            !hasSensusFasihScreenshotForSpk(
                                                                spk.spk_id,
                                                            ))
                                                    }
                                                    title={
                                                        spk.is_sensus_ekonomi &&
                                                        !hasSensusFasihScreenshotForSpk(
                                                            spk.spk_id,
                                                        )
                                                            ? 'Screenshot Fasih belum diunggah'
                                                            : undefined
                                                    }
                                                >
                                                    <FileText className="mr-1 h-3 w-3" />
                                                    Preview Lampiran
                                                </Button>
                                            )}
                                            {isDetailMode &&
                                                spk.existing_bast_hashed_id && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/bast/${spk.existing_bast_hashed_id}`}
                                                        >
                                                            <FileText className="mr-1 h-3 w-3" />
                                                            Buka Detail BAST
                                                        </Link>
                                                    </Button>
                                                )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {decryptedSpkList.length === 0 && (
                        <div className="py-12 text-center text-neutral-500">
                            Tidak ada Perjanjian Kerja yang perlu dibuatkan BAST
                        </div>
                    )}
                </div>
            </ContentCard>
        </AppLayout>
    );
}
