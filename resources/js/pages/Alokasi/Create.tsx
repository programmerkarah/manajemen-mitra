import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Copy,
    Download,
    FileUp,
    Loader2,
    Plus,
    Save,
    Send,
    Trash2,
    X,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alokasi petugas', href: '/alokasi' },
    { title: 'Tambah Alokasi', href: '/alokasi/create' },
];

interface Kegiatan {
    pj_lainnya_id: number;
    id: string;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    deskripsi?: string | null;
    jenis_kegiatan: 'sensus' | 'survei';
    pagu_pencacahan?: number | null;
    ketua_tim_user_id: number;
    rate_honors: RateHonor[];
    has_listing_updating?: boolean;
    pagu_listing?: number | null;
    tanggal_mulai?: string | null; // format: YYYY-MM-DD
    tanggal_selesai?: string | null; // format: YYYY-MM-DD
}

interface Petugas {
    id: string;
    nama: string;
    nik: string;
    email: string;
    jenis_petugas: 'organik' | 'non-organik';
    peran?: string;
    jabatan?: string | null;
}

interface RateHonor {
    id: string;
    posisi: string;
    jenis_kegiatan: 'sensus' | 'survei';
    status_kepegawaian: 'organik' | 'non_organik';
    jenis_penugasan: string;
    rate: number;
    satuan: {
        id: string;
        nama: string;
    };
    rate_listing?: number;
    satuan_listing_id?: string;
    sbml_limit?: number | null;
}

interface AlokasiItem {
    petugas_id: string;
    peran: string;
    jumlah_satuan: string;
    estimasi_honor: number;
    jumlah_satuan_listing?: string;
    estimasi_honor_listing?: number;
    catatan: string;
    is_partial_payment?: boolean;
    partial_jumlah_satuan?: string;
    estimasi_honor_partial?: number;
    is_partial_payment_listing?: boolean;
    partial_jumlah_satuan_listing?: string;
    estimasi_honor_partial_listing?: number;
}

interface ImportPreviewRow extends AlokasiItem {
    petugas_nama: string;
    nik: string;
}

/** Shape of alokasi data coming from the backend (edit/copy mode) */
interface BackendAlokasiItem {
    petugas_id?: string | number;
    peran?: string;
    jumlah_satuan?: string | number;
    jumlah_satuan_listing?: string | number;
    total_honor: string | number;
    total_honor_listing?: string | number;
    catatan?: string;
    is_partial_payment?: boolean;
    partial_jumlah_satuan?: string | number;
    estimasi_honor_partial?: string | number;
    is_partial_payment_listing?: boolean;
    partial_jumlah_satuan_listing?: string | number;
    estimasi_honor_partial_listing?: string | number;
}

interface AlokasiCreateProps {
    kegiatans: Kegiatan[];
    petugas: Petugas[];
    selectedKegiatan?: Kegiatan | null;
    active_year: number;
    copiedAlokasi?: BackendAlokasiItem[] | null;
    sourcePeriode?: {
        id?: number;
        hashed_id?: string;
        bulan: string;
        tahun: number;
        tahapan?: 'both' | 'listing_only' | 'pencacahan_only';
        tanggal_mulai?: string | null;
        tanggal_selesai?: string | null;
        tanggal_mulai_listing?: string | null;
        tanggal_selesai_listing?: string | null;
        jadwal_pengolahan_listing_mulai?: string | null;
        jadwal_pengolahan_listing_selesai?: string | null;
        jadwal_pengolahan_pencacahan_mulai?: string | null;
        jadwal_pengolahan_pencacahan_selesai?: string | null;
    } | null;
    budget_info: Record<
        number,
        { pagu_pencacahan: number; current_total_spent: number }
    >;
    used_months_info: Record<
        number,
        {
            has_listing: boolean;
            periods?: Record<number, string[]>; // For listing kegiatan: {1: ['listing', 'pencacahan'], 2: ['listing']}
            months?: number[]; // For non-listing kegiatan: [1, 2, 3]
        }
    >;
    existing_allocations: Array<{
        petugas_id: number;
        bulan: number;
        tahun: number;
        total_honor_pencacahan: number;
        total_honor_listing: number;
        total_honor_combined: number;
    }>;
    isEditMode?: boolean;
    isRevisiMode?: boolean;
    isViewMode?: boolean;
}

type JenisPerubahanRevisi = 'perubahan_beban_tugas' | 'perubahan_petugas';

export default function Create({
    kegiatans,
    petugas,
    selectedKegiatan: preSelectedKegiatan,
    active_year,
    copiedAlokasi,
    sourcePeriode,
    budget_info,
    used_months_info,
    existing_allocations,
    isEditMode = false,
    isRevisiMode = false,
    isViewMode = false,
}: AlokasiCreateProps) {
    // Debug: Log petugas data
    const pageProps = usePage<SharedData & { errors: Record<string, string> }>()
        .props;
    const { auth, errors: backendErrors = {} } = pageProps;
    const errorAlertRef = useRef<HTMLDivElement>(null);
    const [selectedKegiatanId, setSelectedKegiatanId] = useState(
        preSelectedKegiatan?.id || '',
    );
    const isCopyMode = Boolean(sourcePeriode || copiedAlokasi?.length);

    // Helper function to find first available month
    const getFirstAvailableMonth = (
        usedInfo:
            | {
                  has_listing: boolean;
                  periods?: Record<number, string[]>;
                  months?: number[];
              }
            | undefined,
        startMonth: number = 1,
    ): number => {
        // Extract used months array from new structure
        const usedMonthsList = usedInfo
            ? usedInfo.has_listing
                ? Object.keys(usedInfo.periods || {}).map(Number)
                : usedInfo.months || []
            : [];

        for (let month = startMonth; month <= 12; month++) {
            if (!usedMonthsList.includes(month)) {
                return month;
            }
        }
        // If all months from startMonth to 12 are used, try from 1
        for (let month = 1; month < startMonth; month++) {
            if (!usedMonthsList.includes(month)) {
                return month;
            }
        }
        // If all months are used, return startMonth
        return startMonth;
    };

    const [bulan, setBulan] = useState(() => {
        if (isEditMode && sourcePeriode) {
            // Edit mode: use source periode bulan
            return parseInt(sourcePeriode.bulan);
        }

        // Get used months info for the selected kegiatan
        const kegiatanId = preSelectedKegiatan?.id;
        const usedInfo = kegiatanId
            ? used_months_info[Number(kegiatanId)]
            : undefined;

        if (sourcePeriode) {
            // Copy mode: find first available month starting from source month + 1
            const nextMonth = (parseInt(sourcePeriode.bulan) % 12) + 1;
            return getFirstAvailableMonth(usedInfo, nextMonth);
        }

        // New mode: find first available month starting from month 1
        return getFirstAvailableMonth(usedInfo, 1);
    });
    const [tahapan, setTahapan] = useState<
        'both' | 'listing_only' | 'pencacahan_only'
    >(() => {
        if (sourcePeriode?.tahapan) {
            return sourcePeriode.tahapan as
                | 'both'
                | 'listing_only'
                | 'pencacahan_only';
        }
        return 'both';
    });
    const [jenisKegiatan, setJenisKegiatan] = useState<'sensus' | 'survei'>(
        preSelectedKegiatan?.jenis_kegiatan || 'survei',
    );
    // Store original values from copied/edited alokasi for restoration
    const [originalAlokasiValues, setOriginalAlokasiValues] = useState<
        Array<{
            jumlah_satuan: string;
            jumlah_satuan_listing: string;
            estimasi_honor: number;
            estimasi_honor_listing: number;
        }>
    >([]);
    // Tidak perlu showPengolahan, dropdown peran akan dinamis dari rate_honors
    const [jumlahPetugas, setJumlahPetugas] = useState<number | string>(
        isEditMode && copiedAlokasi ? copiedAlokasi.length : 1,
    );
    const [alokasiItems, setAlokasiItems] = useState<AlokasiItem[]>([
        {
            petugas_id: '',
            peran: '',
            jumlah_satuan: '',
            estimasi_honor: 0,
            catatan: '',
            is_partial_payment: false,
            partial_jumlah_satuan: '',
            estimasi_honor_partial: 0,
            is_partial_payment_listing: false,
            partial_jumlah_satuan_listing: '',
            estimasi_honor_partial_listing: 0,
        },
    ]);
    const [restorableItemsByCount, setRestorableItemsByCount] = useState<
        AlokasiItem[]
    >([]);
    // Jadwal Kegiatan states
    const [tanggalMulai, setTanggalMulai] = useState(
        sourcePeriode?.tanggal_mulai || '',
    );
    const [tanggalSelesai, setTanggalSelesai] = useState(
        sourcePeriode?.tanggal_selesai || '',
    );
    const [tanggalMulaiListing, setTanggalMulaiListing] = useState(
        sourcePeriode?.tanggal_mulai_listing || '',
    );
    const [tanggalSelesaiListing, setTanggalSelesaiListing] = useState(
        sourcePeriode?.tanggal_selesai_listing || '',
    );
    // Jadwal Pengolahan states
    const [jadwalPengolahanListingMulai, setJadwalPengolahanListingMulai] =
        useState(sourcePeriode?.jadwal_pengolahan_listing_mulai || '');
    const [jadwalPengolahanListingSelesai, setJadwalPengolahanListingSelesai] =
        useState(sourcePeriode?.jadwal_pengolahan_listing_selesai || '');
    const [
        jadwalPengolahanPencacahanMulai,
        setJadwalPengolahanPencacahanMulai,
    ] = useState(sourcePeriode?.jadwal_pengolahan_pencacahan_mulai || '');
    const [
        jadwalPengolahanPencacahanSelesai,
        setJadwalPengolahanPencacahanSelesai,
    ] = useState(sourcePeriode?.jadwal_pengolahan_pencacahan_selesai || '');
    const [processing, setProcessing] = useState(false);
    const [importProcessing, setImportProcessing] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [importPreviewRows, setImportPreviewRows] = useState<
        ImportPreviewRow[]
    >([]);
    const [importPreviewErrors, setImportPreviewErrors] = useState<string[]>(
        [],
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [jenisPerubahanRevisi, setJenisPerubahanRevisi] =
        useState<JenisPerubahanRevisi>('perubahan_beban_tugas');

    const isPerubahanPetugasMode =
        isRevisiMode && jenisPerubahanRevisi === 'perubahan_petugas';
    const isRevisiLockedMode = isRevisiMode && !isPerubahanPetugasMode;

    // Combine local errors with backend errors
    const allErrors = useMemo(
        () => ({ ...backendErrors, ...errors }),
        [backendErrors, errors],
    );

    // Debug: log errors to console
    useEffect(() => {
        if (Object.keys(backendErrors || {}).length > 0) {
            console.log('Backend Errors:', backendErrors);
        }
        if (Object.keys(allErrors).length > 0) {
            console.log('All Errors:', allErrors);
        }
    }, [backendErrors, allErrors]);

    // Auto-scroll to error alert when errors occur
    useEffect(() => {
        if (Object.keys(allErrors).length > 0) {
            errorAlertRef.current?.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });
            errorAlertRef.current?.focus();
        }
    }, [allErrors]);

    // Filter kegiatan based on ketua_tim role
    const filteredKegiatans = useMemo(() => {
        let filtered = kegiatans;

        // First, filter by role permission
        if (auth.activeRole?.name === 'ketua_tim') {
            filtered = kegiatans.filter((k) => {
                const ketuaMatch =
                    Number(k.ketua_tim_user_id) === Number(auth.user.id);
                // If pj_lainnya_id is null/undefined, treat as 0 (never match user.id)
                const pjLainnyaId =
                    typeof k.pj_lainnya_id === 'undefined' ||
                    k.pj_lainnya_id === null
                        ? 0
                        : Number(k.pj_lainnya_id);
                const pjLainnyaMatch = pjLainnyaId === Number(auth.user.id);
                return ketuaMatch || pjLainnyaMatch;
            });
        }

        // Second, filter out kegiatan where ALL months are already used
        // Only apply this filter when NOT in edit mode
        if (!isEditMode && !isViewMode && !isCopyMode) {
            filtered = filtered.filter((k) => {
                const usedInfo = used_months_info[Number(k.id)];

                // If no used info, kegiatan is available
                if (!usedInfo) return true;

                // If kegiatan has listing (has_listing_updating = true), it can have up to 24 periods (12 * 2 tahapan)
                // If no listing, max 12 periods (1 per month)
                const maxPeriods = k.has_listing_updating ? 24 : 12;

                if (usedInfo.has_listing) {
                    // For listing kegiatan: count total used tahapan slots
                    // usedInfo.periods is object: {1: ['listing', 'pencacahan'], 2: ['listing']}
                    const totalUsed = Object.values(
                        usedInfo.periods || {},
                    ).flat().length;
                    return totalUsed < maxPeriods;
                } else {
                    // For non-listing kegiatan: simple month array
                    // usedInfo.months is array: [1, 2, 3]
                    return (usedInfo.months || []).length < 12;
                }
            });
        }

        // Third, filter out kegiatan where there are no available months within date range
        // Only apply this filter when NOT in edit mode
        if (!isEditMode && !isViewMode && !isCopyMode) {
            filtered = filtered.filter((k) => {
                // Check if kegiatan has valid date range
                if (!k.tanggal_mulai || !k.tanggal_selesai) {
                    return true; // Keep kegiatan if no date range specified
                }

                const start = new Date(k.tanggal_mulai);
                const end = new Date(k.tanggal_selesai);

                // Get months within kegiatan's date range for active_year
                const availableMonthsInRange: number[] = [];

                for (let m = 1; m <= 12; m++) {
                    // Check if month is within kegiatan's date range
                    if (
                        active_year < start.getFullYear() ||
                        active_year > end.getFullYear()
                    ) {
                        continue;
                    }

                    if (start.getFullYear() === end.getFullYear()) {
                        if (
                            active_year === start.getFullYear() &&
                            m >= start.getMonth() + 1 &&
                            m <= end.getMonth() + 1
                        ) {
                            availableMonthsInRange.push(m);
                        }
                    } else if (active_year === start.getFullYear()) {
                        if (m >= start.getMonth() + 1) {
                            availableMonthsInRange.push(m);
                        }
                    } else if (active_year === end.getFullYear()) {
                        if (m <= end.getMonth() + 1) {
                            availableMonthsInRange.push(m);
                        }
                    } else if (
                        active_year > start.getFullYear() &&
                        active_year < end.getFullYear()
                    ) {
                        availableMonthsInRange.push(m);
                    }
                }

                // If no months in range, hide kegiatan
                if (availableMonthsInRange.length === 0) {
                    return false;
                }

                // Check if any months are actually available (not all used)
                const usedInfo = used_months_info[Number(k.id)];
                if (!usedInfo) {
                    return true; // If no used info, all months in range are available
                }

                // For non-listing kegiatan: check if all months in range are used
                if (!k.has_listing_updating) {
                    const usedMonths = usedInfo.months || [];
                    const hasAvailableMonth = availableMonthsInRange.some(
                        (m) => !usedMonths.includes(m),
                    );
                    return hasAvailableMonth;
                }

                // For listing kegiatan: check if any month has at least one available tahapan slot
                if (usedInfo.has_listing && usedInfo.periods) {
                    const hasAvailableSlot = availableMonthsInRange.some(
                        (m) => {
                            const usedTahapan = usedInfo.periods?.[m] || [];
                            // At least one tahapan slot must be available
                            return usedTahapan.length < 2; // Less than 2 means not both tahapan are used
                        },
                    );
                    return hasAvailableSlot;
                }

                return true;
            });
        }
        return filtered;
    }, [
        kegiatans,
        auth.activeRole,
        auth.user.id,
        used_months_info,
        isEditMode,
        isViewMode,
        isCopyMode,
        active_year,
    ]);

    const availableKegiatanIds = useMemo(
        () => new Set(filteredKegiatans.map((item) => String(item.id))),
        [filteredKegiatans],
    );

    const kegiatanOptions = useMemo(() => {
        const combinedKegiatans = preSelectedKegiatan
            ? [preSelectedKegiatan, ...kegiatans]
            : kegiatans;

        const uniqueKegiatans = Array.from(
            new Map(
                combinedKegiatans.map((item) => [String(item.id), item]),
            ).values(),
        );

        const availableKegiatans = uniqueKegiatans.filter((item) =>
            availableKegiatanIds.has(String(item.id)),
        );
        const unavailableKegiatans = uniqueKegiatans.filter(
            (item) => !availableKegiatanIds.has(String(item.id)),
        );

        return [...availableKegiatans, ...unavailableKegiatans];
    }, [kegiatans, preSelectedKegiatan, availableKegiatanIds]);

    const selectedKegiatan = kegiatanOptions.find(
        (k) => String(k.id) === String(selectedKegiatanId),
    );

    // Get budget info for selected kegiatan
    const currentBudget =
        selectedKegiatan && selectedKegiatanId
            ? budget_info[Number(selectedKegiatan.id)] || {
                  pagu_pencacahan: 0,
                  current_total_spent: 0,
                  pagu_listing: 0,
                  current_total_spent_listing: 0,
              }
            : {
                  pagu_pencacahan: 0,
                  current_total_spent: 0,
                  pagu_listing: 0,
                  current_total_spent_listing: 0,
              };

    // For backward compatibility, fallback to selectedKegiatan.pagu_listing if not in budget_info
    const pagu_pencacahan =
        'pagu_pencacahan' in currentBudget
            ? (currentBudget as { pagu_pencacahan: number }).pagu_pencacahan
            : selectedKegiatan?.pagu_pencacahan || 0;
    const current_total_spent = currentBudget.current_total_spent;
    const pagu_listing =
        'pagu_listing' in currentBudget
            ? (currentBudget as { pagu_listing: number }).pagu_listing
            : selectedKegiatan?.pagu_listing || 0;
    const current_total_spent_listing =
        'current_total_spent_listing' in currentBudget
            ? (currentBudget as { current_total_spent_listing: number })
                  .current_total_spent_listing
            : 0;

    // Get used months for selected kegiatan
    const usedMonths = useMemo(() => {
        if (!selectedKegiatan || !selectedKegiatanId) return [];

        const usedInfo = used_months_info[Number(selectedKegiatan.id)];
        if (!usedInfo) return [];

        if (usedInfo.has_listing) {
            // For listing kegiatan: extract unique months from periods object
            return Object.keys(usedInfo.periods || {}).map(Number);
        } else {
            // For non-listing kegiatan: use months array directly
            return usedInfo.months || [];
        }
    }, [selectedKegiatan, selectedKegiatanId, used_months_info]);

    // Initialize with copied data if available
    useEffect(() => {
        if (copiedAlokasi && copiedAlokasi.length > 0) {
            // Store original values first for restoration
            const originalValues = copiedAlokasi.map((alokasi) => ({
                jumlah_satuan: String(alokasi.jumlah_satuan || 0),
                jumlah_satuan_listing: String(
                    alokasi.jumlah_satuan_listing || 0,
                ),
                estimasi_honor: parseFloat(String(alokasi.total_honor)) || 0,
                estimasi_honor_listing:
                    parseFloat(String(alokasi.total_honor_listing ?? 0)) || 0,
            }));
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setOriginalAlokasiValues(originalValues);

            const initialItems = copiedAlokasi.map((alokasi) => {
                // Map backend peran format to frontend display format
                let peranDisplay = '';
                const peranLower = (alokasi.peran || '').toLowerCase();

                if (peranLower === 'pcl_ppl' || peranLower === 'pcl') {
                    peranDisplay = 'PCL';
                } else if (peranLower === 'pml') {
                    peranDisplay = 'PML';
                } else if (
                    peranLower === 'pengolahan' ||
                    peranLower === 'petugas pengolahan'
                ) {
                    peranDisplay = 'Petugas Pengolahan';
                } else if (
                    peranLower === 'pengawas_pengolahan' ||
                    peranLower === 'pengawas pengolahan'
                ) {
                    peranDisplay = 'Pengawas Pengolahan';
                } else if (peranLower === 'koseka') {
                    peranDisplay = 'Koseka';
                } else if (alokasi.peran) {
                    // If peran exists but doesn't match any known format, keep it as is
                    peranDisplay = alokasi.peran;
                }

                // Ensure numeric values are properly parsed
                const estimasiHonor =
                    parseFloat(String(alokasi.total_honor)) || 0;
                const estimasiHonorListing =
                    parseFloat(String(alokasi.total_honor_listing ?? 0)) || 0;
                const estimasiHonorPartial =
                    parseFloat(String(alokasi.estimasi_honor_partial ?? 0)) ||
                    0;
                const estimasiHonorPartialListing =
                    parseFloat(
                        String(alokasi.estimasi_honor_partial_listing ?? 0),
                    ) || 0;
                const partialJumlahSatuan = String(
                    alokasi.partial_jumlah_satuan ?? '',
                );
                const partialJumlahSatuanListing = String(
                    alokasi.partial_jumlah_satuan_listing ?? '',
                );
                const hasPartialPayment = Boolean(alokasi.is_partial_payment);
                const hasPartialPaymentListing = Boolean(
                    alokasi.is_partial_payment_listing,
                );

                return {
                    petugas_id: String(alokasi.petugas_id || ''),
                    peran: peranDisplay,
                    jumlah_satuan: String(alokasi.jumlah_satuan || 0),
                    jumlah_satuan_listing: String(
                        alokasi.jumlah_satuan_listing || 0,
                    ),
                    estimasi_honor: estimasiHonor,
                    estimasi_honor_listing: estimasiHonorListing,
                    catatan: alokasi.catatan || '',
                    is_partial_payment: hasPartialPayment,
                    partial_jumlah_satuan: hasPartialPayment
                        ? partialJumlahSatuan
                        : '',
                    estimasi_honor_partial: hasPartialPayment
                        ? estimasiHonorPartial
                        : 0,
                    is_partial_payment_listing: hasPartialPaymentListing,
                    partial_jumlah_satuan_listing: hasPartialPaymentListing
                        ? partialJumlahSatuanListing
                        : '',
                    estimasi_honor_partial_listing: hasPartialPaymentListing
                        ? estimasiHonorPartialListing
                        : 0,
                };
            });

            setAlokasiItems(initialItems);
            setJumlahPetugas(initialItems.length);
            setRestorableItemsByCount([]);

            // Auto-scroll to budget info if any estimasi honor exists
            const hasEstimasi = initialItems.some(
                (item) =>
                    item.estimasi_honor > 0 ||
                    (item.estimasi_honor_listing &&
                        item.estimasi_honor_listing > 0),
            );
            if (hasEstimasi) {
                // Delay scroll to ensure DOM is rendered
                setTimeout(() => {
                    const budgetSection =
                        document.querySelector('[data-budget-info]');
                    if (budgetSection) {
                        budgetSection.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        });
                    }
                }, 500);
            }
        }
    }, [copiedAlokasi]);

    // Calculate estimasi honor for a petugas (pencacahan)
    const calculateEstimasi = useCallback(
        (petugasId: string, peran: string, jumlahSatuan: string) => {
            if (!petugasId || !peran || !jumlahSatuan || !selectedKegiatan)
                return 0;
            const selectedPetugas = petugas.find(
                (p) => String(p.id) === String(petugasId),
            );
            if (!selectedPetugas) return 0;
            const statusKepegawaian =
                selectedPetugas.jenis_petugas === 'organik'
                    ? 'organik'
                    : 'non_organik';
            let jenisPenugasan = '';
            if (peran === 'PCL') jenisPenugasan = 'pcl_ppl';
            else if (peran === 'PML') jenisPenugasan = 'pml';
            else if (peran === 'Koseka') jenisPenugasan = 'koseka';
            else if (peran === 'Petugas Pengolahan')
                jenisPenugasan = 'pengolahan';
            else if (peran === 'Pengawas Pengolahan')
                jenisPenugasan = 'pengawas_pengolahan';
            if (!jenisPenugasan) return 0;
            const matchingRateHonor = selectedKegiatan.rate_honors?.find(
                (r) =>
                    r.status_kepegawaian === statusKepegawaian &&
                    r.jenis_penugasan === jenisPenugasan,
            );
            if (!matchingRateHonor) return 0;
            const parsedJumlah = parseFloat(jumlahSatuan) || 0;
            return matchingRateHonor.rate * parsedJumlah;
        },
        [selectedKegiatan, petugas],
    );

    // Calculate estimasi honor for listing phase
    const calculateEstimasiListing = useCallback(
        (petugasId: string, peran: string, jumlahSatuanListing: string) => {
            if (
                !petugasId ||
                !peran ||
                !jumlahSatuanListing ||
                !selectedKegiatan ||
                !selectedKegiatan.has_listing_updating
            )
                return 0;
            const selectedPetugas = petugas.find(
                (p) => String(p.id) === String(petugasId),
            );
            if (!selectedPetugas) return 0;
            const statusKepegawaian =
                selectedPetugas.jenis_petugas === 'organik'
                    ? 'organik'
                    : 'non_organik';
            let jenisPenugasan = '';
            if (peran === 'PCL') jenisPenugasan = 'pcl_ppl';
            else if (peran === 'PML') jenisPenugasan = 'pml';
            else if (peran === 'Petugas Pengolahan')
                jenisPenugasan = 'pengolahan';
            else if (peran === 'Pengawas Pengolahan')
                jenisPenugasan = 'pengawas_pengolahan';
            else if (peran === 'Koseka') jenisPenugasan = 'koseka';
            if (!jenisPenugasan) return 0;
            const matchingRateHonor = selectedKegiatan.rate_honors?.find(
                (r) =>
                    r.status_kepegawaian === statusKepegawaian &&
                    r.jenis_penugasan === jenisPenugasan,
            );
            if (!matchingRateHonor || !matchingRateHonor.rate_listing) return 0;
            const parsedJumlahListing = parseFloat(jumlahSatuanListing) || 0;
            return matchingRateHonor.rate_listing * parsedJumlahListing;
        },
        [selectedKegiatan, petugas],
    );

    // Calculate estimasi honor for partial payment (pencacahan)
    const calculateEstimasiPartial = useCallback(
        (petugasId: string, peran: string, partialJumlahSatuan: string) => {
            if (
                !petugasId ||
                !peran ||
                !partialJumlahSatuan ||
                !selectedKegiatan
            )
                return 0;
            const selectedPetugas = petugas.find(
                (p) => String(p.id) === String(petugasId),
            );
            if (!selectedPetugas) return 0;
            const statusKepegawaian =
                selectedPetugas.jenis_petugas === 'organik'
                    ? 'organik'
                    : 'non_organik';
            let jenisPenugasan = '';
            if (peran === 'PCL') jenisPenugasan = 'pcl_ppl';
            else if (peran === 'PML') jenisPenugasan = 'pml';
            else if (peran === 'Petugas Pengolahan')
                jenisPenugasan = 'pengolahan';
            else if (peran === 'Pengawas Pengolahan')
                jenisPenugasan = 'pengawas_pengolahan';
            else if (peran === 'Koseka') jenisPenugasan = 'koseka';
            if (!jenisPenugasan) return 0;
            const matchingRateHonor = selectedKegiatan.rate_honors?.find(
                (r) =>
                    r.status_kepegawaian === statusKepegawaian &&
                    r.jenis_penugasan === jenisPenugasan,
            );
            if (!matchingRateHonor) return 0;
            const parsedJumlah = parseFloat(partialJumlahSatuan) || 0;
            return matchingRateHonor.rate * parsedJumlah;
        },
        [selectedKegiatan, petugas],
    );

    // Calculate estimasi honor for partial payment listing
    const calculateEstimasiPartialListing = useCallback(
        (
            petugasId: string,
            peran: string,
            partialJumlahSatuanListing: string,
        ) => {
            if (
                !petugasId ||
                !peran ||
                !partialJumlahSatuanListing ||
                !selectedKegiatan ||
                !selectedKegiatan.has_listing_updating
            )
                return 0;
            const selectedPetugas = petugas.find(
                (p) => String(p.id) === String(petugasId),
            );
            if (!selectedPetugas) return 0;
            const statusKepegawaian =
                selectedPetugas.jenis_petugas === 'organik'
                    ? 'organik'
                    : 'non_organik';
            let jenisPenugasan = '';
            if (peran === 'PCL') jenisPenugasan = 'pcl_ppl';
            else if (peran === 'PML') jenisPenugasan = 'pml';
            else if (peran === 'Petugas Pengolahan')
                jenisPenugasan = 'pengolahan';
            else if (peran === 'Pengawas Pengolahan')
                jenisPenugasan = 'pengawas_pengolahan';
            else if (peran === 'Koseka') jenisPenugasan = 'koseka';
            if (!jenisPenugasan) return 0;
            const matchingRateHonor = selectedKegiatan.rate_honors?.find(
                (r) =>
                    r.status_kepegawaian === statusKepegawaian &&
                    r.jenis_penugasan === jenisPenugasan,
            );
            if (!matchingRateHonor || !matchingRateHonor.rate_listing) return 0;
            const parsedJumlahListing =
                parseFloat(partialJumlahSatuanListing) || 0;
            return matchingRateHonor.rate_listing * parsedJumlahListing;
        },
        [selectedKegiatan, petugas],
    );

    // Show partial payment toggle when full estimated honor exceeds SBML,
    // and keep it visible after the user enables partial payment.
    const shouldShowPartialPaymentToggle = useCallback(
        (item: AlokasiItem, currentIndex?: number): boolean => {
            if (!selectedKegiatan || !item.petugas_id || !item.peran) {
                return false;
            }

            const petugas_ = petugas.find(
                (p) => String(p.id) === String(item.petugas_id),
            );
            if (!petugas_) {
                return false;
            }

            const statusKepegawaian =
                petugas_.jenis_petugas === 'organik'
                    ? 'organik'
                    : 'non_organik';

            // Map frontend peran to backend jenis_penugasan
            let jenisPenugasan = '';
            if (item.peran === 'PCL') jenisPenugasan = 'pcl_ppl';
            else if (item.peran === 'PML') jenisPenugasan = 'pml';
            else if (item.peran === 'Petugas Pengolahan')
                jenisPenugasan = 'pengolahan';
            else if (item.peran === 'Pengawas Pengolahan')
                jenisPenugasan = 'pengawas_pengolahan';
            else if (item.peran === 'Koseka') jenisPenugasan = 'koseka';

            if (!jenisPenugasan) {
                return false;
            }

            const matchingRateHonor = selectedKegiatan.rate_honors?.find(
                (r) =>
                    r.status_kepegawaian === statusKepegawaian &&
                    r.jenis_penugasan === jenisPenugasan,
            );

            if (!matchingRateHonor || !matchingRateHonor.sbml_limit) {
                return false;
            }

            if (item.is_partial_payment || item.is_partial_payment_listing) {
                return true;
            }

            const petugasIdNum = parseInt(String(item.petugas_id), 10);
            const existingAllocation = existing_allocations.find(
                (a) =>
                    a.petugas_id === petugasIdNum &&
                    a.tahun === Number(active_year) &&
                    a.bulan === Number(bulan),
            );

            const existingHonor = existingAllocation?.total_honor_combined || 0;
            const currentFormHonor = alokasiItems.reduce(
                (sum, row, rowIndex) => {
                    if (String(row.petugas_id) !== String(item.petugas_id)) {
                        return sum;
                    }

                    const activeRow =
                        typeof currentIndex === 'number' &&
                        rowIndex === currentIndex
                            ? item
                            : row;

                    const pencacahanHonor = activeRow.is_partial_payment
                        ? Number(activeRow.estimasi_honor_partial || 0)
                        : Number(activeRow.estimasi_honor || 0);
                    const listingHonor = activeRow.is_partial_payment_listing
                        ? Number(activeRow.estimasi_honor_partial_listing || 0)
                        : Number(activeRow.estimasi_honor_listing || 0);

                    return sum + pencacahanHonor + listingHonor;
                },
                0,
            );
            const totalHonor = existingHonor + currentFormHonor;

            return totalHonor > matchingRateHonor.sbml_limit;
        },
        [
            selectedKegiatan,
            petugas,
            existing_allocations,
            active_year,
            bulan,
            alokasiItems,
        ],
    );

    // Set jenisKegiatan from selectedKegiatan and recalculate estimasi
    useEffect(() => {
        if (!selectedKegiatan) return;

        // Update jenis kegiatan from selected kegiatan
        const newJenisKegiatan = selectedKegiatan.jenis_kegiatan;
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setJenisKegiatan(newJenisKegiatan);

        // Recalculate estimasi for all items using the calculateEstimasi function
        setAlokasiItems((prevItems) => {
            // If prevItems is empty or all items have empty petugas_id, don't process
            if (
                prevItems.length === 0 ||
                prevItems.every((item) => !item.petugas_id)
            ) {
                return prevItems;
            }

            const newItems = prevItems.map((item) => {
                // Preserve all existing fields
                const updates: Partial<AlokasiItem> = {};

                // Recalculate pencacahan estimasi
                if (item.petugas_id && item.peran && item.jumlah_satuan) {
                    const newEstimasi = calculateEstimasi(
                        item.petugas_id,
                        item.peran,
                        item.jumlah_satuan,
                    );
                    updates.estimasi_honor = newEstimasi;
                }

                // Recalculate listing estimasi if applicable
                if (
                    item.petugas_id &&
                    item.peran &&
                    item.jumlah_satuan_listing &&
                    selectedKegiatan.has_listing_updating
                ) {
                    const newEstimasiListing = calculateEstimasiListing(
                        item.petugas_id,
                        item.peran,
                        item.jumlah_satuan_listing,
                    );
                    updates.estimasi_honor_listing = newEstimasiListing;
                }

                return { ...item, ...updates };
            });

            return newItems;
        });
    }, [
        selectedKegiatanId,
        selectedKegiatan,
        calculateEstimasi,
        calculateEstimasiListing,
    ]);

    // For sensus kegiatan: auto-set jumlah_satuan to 1 and recalculate estimasi
    useEffect(() => {
        if (jenisKegiatan !== 'sensus') return;

        // eslint-disable-next-line react-hooks/set-state-in-effect
        setAlokasiItems((prevItems) =>
            prevItems.map((item) => {
                if (item.jumlah_satuan === '1') return item;

                const newEstimasi = calculateEstimasi(
                    item.petugas_id,
                    item.peran,
                    '1',
                );

                return {
                    ...item,
                    jumlah_satuan: '1',
                    estimasi_honor: newEstimasi,
                };
            }),
        );
    }, [jenisKegiatan, calculateEstimasi]);

    // Handle tahapan change - clear/restore values based on tahapan
    useEffect(() => {
        if (originalAlokasiValues.length === 0) return; // Wait until original values are loaded

        // eslint-disable-next-line react-hooks/set-state-in-effect
        setAlokasiItems((prevItems) => {
            return prevItems.map((item, index) => {
                const updates: Partial<AlokasiItem> = {};
                const originalValues = originalAlokasiValues[index];

                if (!originalValues) return item;

                if (tahapan === 'listing_only') {
                    // Clear pencacahan values, restore listing values
                    updates.jumlah_satuan = '0';
                    updates.estimasi_honor = 0;
                    updates.jumlah_satuan_listing =
                        originalValues.jumlah_satuan_listing || '0';
                    updates.estimasi_honor_listing =
                        originalValues.estimasi_honor_listing || 0;
                } else if (tahapan === 'pencacahan_only') {
                    // Clear listing values, restore pencacahan values
                    updates.jumlah_satuan_listing = '0';
                    updates.estimasi_honor_listing = 0;
                    updates.jumlah_satuan = originalValues.jumlah_satuan || '0';
                    updates.estimasi_honor = originalValues.estimasi_honor || 0;
                } else {
                    // Both - restore all original values
                    updates.jumlah_satuan = originalValues.jumlah_satuan || '0';
                    updates.estimasi_honor = originalValues.estimasi_honor || 0;
                    updates.jumlah_satuan_listing =
                        originalValues.jumlah_satuan_listing || '0';
                    updates.estimasi_honor_listing =
                        originalValues.estimasi_honor_listing || 0;
                }

                return { ...item, ...updates };
            });
        });
    }, [tahapan, originalAlokasiValues]);

    // Handle jumlah petugas change
    const handleJumlahPetugasChange = (value: number) => {
        const newValue = Math.max(1, Math.min(100, value));
        const currentItems = [...alokasiItems];

        if (newValue > currentItems.length) {
            const needed = newValue - currentItems.length;
            const restored = restorableItemsByCount.slice(0, needed);
            const stillNeeded = needed - restored.length;

            const emptyRows: AlokasiItem[] = Array.from(
                { length: stillNeeded },
                () => ({
                    petugas_id: '',
                    peran: '',
                    jumlah_satuan: jenisKegiatan === 'sensus' ? '1' : '',
                    estimasi_honor: 0,
                    catatan: '',
                    is_partial_payment: false,
                    partial_jumlah_satuan: '',
                    estimasi_honor_partial: 0,
                    is_partial_payment_listing: false,
                    partial_jumlah_satuan_listing: '',
                    estimasi_honor_partial_listing: 0,
                }),
            );

            setAlokasiItems([...currentItems, ...restored, ...emptyRows]);
            setRestorableItemsByCount((prev) => prev.slice(needed));
            setJumlahPetugas(newValue);
            return;
        }

        if (newValue < currentItems.length) {
            const removedItems = currentItems.slice(newValue);
            setAlokasiItems(currentItems.slice(0, newValue));
            setRestorableItemsByCount((prev) => [...removedItems, ...prev]);
            setJumlahPetugas(newValue);
            return;
        }

        setJumlahPetugas(newValue);
    };

    const handleDeleteAlokasiRow = (index: number) => {
        if (alokasiItems.length <= 1) {
            return;
        }

        const updatedItems = alokasiItems.filter((_, idx) => idx !== index);
        setAlokasiItems(updatedItems);
        setJumlahPetugas(updatedItems.length);

        // Delete action should permanently remove that row from current payload,
        // so the next added row is a fresh empty row.
        setRestorableItemsByCount([]);
    };

    const handleAddPetugasAfter = (index: number) => {
        const newItem: AlokasiItem = {
            petugas_id: '',
            peran: '',
            jumlah_satuan: jenisKegiatan === 'sensus' ? '1' : '',
            estimasi_honor: 0,
            catatan: '',
            is_partial_payment: false,
            partial_jumlah_satuan: '',
            estimasi_honor_partial: 0,
            is_partial_payment_listing: false,
            partial_jumlah_satuan_listing: '',
            estimasi_honor_partial_listing: 0,
        };

        const newItems = [
            ...alokasiItems.slice(0, index + 1),
            newItem,
            ...alokasiItems.slice(index + 1),
        ];

        setAlokasiItems(newItems);
        setJumlahPetugas(newItems.length);
    };

    // Update alokasi item
    // Update alokasi item
    const updateAlokasiItem = (
        index: number,
        field: keyof AlokasiItem,
        value: AlokasiItem[keyof AlokasiItem],
    ) => {
        // Guard: Don't update peran with empty value if item already has a peran
        if (field === 'peran' && !value && alokasiItems[index]?.peran) {
            return;
        }

        // Guard: For sensus kegiatan, jumlah_satuan is always 1
        if (field === 'jumlah_satuan' && jenisKegiatan === 'sensus') {
            value = '1';
        }

        const clampPartialValue = (
            inputValue: AlokasiItem[keyof AlokasiItem],
            maxValue: number,
        ): string => {
            if (
                inputValue === '' ||
                inputValue === null ||
                inputValue === undefined
            ) {
                return '';
            }

            const parsedValue = Number(inputValue);
            if (Number.isNaN(parsedValue)) {
                return '';
            }

            const normalizedMax = Math.max(0, maxValue);
            const clampedValue = Math.min(
                Math.max(0, parsedValue),
                normalizedMax,
            );

            return String(clampedValue);
        };

        const newItems = [...alokasiItems];
        let nextValue = value;

        if (field === 'partial_jumlah_satuan') {
            const maxPencacahan = Number(newItems[index].jumlah_satuan) || 0;
            nextValue = clampPartialValue(value, maxPencacahan);
        }

        if (field === 'partial_jumlah_satuan_listing') {
            const maxListing =
                Number(newItems[index].jumlah_satuan_listing) || 0;
            nextValue = clampPartialValue(value, maxListing);
        }

        newItems[index] = { ...newItems[index], [field]: nextValue };

        if (field === 'jumlah_satuan') {
            const maxPencacahan = Number(newItems[index].jumlah_satuan) || 0;
            newItems[index].partial_jumlah_satuan = clampPartialValue(
                newItems[index].partial_jumlah_satuan,
                maxPencacahan,
            );
        }

        if (field === 'jumlah_satuan_listing') {
            const maxListing =
                Number(newItems[index].jumlah_satuan_listing) || 0;
            newItems[index].partial_jumlah_satuan_listing = clampPartialValue(
                newItems[index].partial_jumlah_satuan_listing,
                maxListing,
            );
        }

        // Recalculate estimasi honor for pencacahan
        if (
            field === 'petugas_id' ||
            field === 'peran' ||
            field === 'jumlah_satuan'
        ) {
            newItems[index].estimasi_honor = calculateEstimasi(
                newItems[index].petugas_id,
                newItems[index].peran,
                newItems[index].jumlah_satuan,
            );
        }

        // Recalculate estimasi honor for listing
        if (
            selectedKegiatan?.has_listing_updating &&
            (field === 'petugas_id' ||
                field === 'peran' ||
                field === 'jumlah_satuan_listing')
        ) {
            newItems[index].estimasi_honor_listing = calculateEstimasiListing(
                newItems[index].petugas_id,
                newItems[index].peran,
                newItems[index].jumlah_satuan_listing || '',
            );
        }

        // Recalculate estimasi honor for partial payment (pencacahan)
        if (
            field === 'petugas_id' ||
            field === 'peran' ||
            field === 'partial_jumlah_satuan'
        ) {
            if (
                newItems[index].is_partial_payment &&
                newItems[index].partial_jumlah_satuan
            ) {
                newItems[index].estimasi_honor_partial =
                    calculateEstimasiPartial(
                        newItems[index].petugas_id,
                        newItems[index].peran,
                        newItems[index].partial_jumlah_satuan || '',
                    );
            }
        }

        // Recalculate estimasi honor for partial payment listing
        if (
            selectedKegiatan?.has_listing_updating &&
            (field === 'petugas_id' ||
                field === 'peran' ||
                field === 'partial_jumlah_satuan_listing')
        ) {
            if (
                newItems[index].is_partial_payment_listing &&
                newItems[index].partial_jumlah_satuan_listing
            ) {
                newItems[index].estimasi_honor_partial_listing =
                    calculateEstimasiPartialListing(
                        newItems[index].petugas_id,
                        newItems[index].peran,
                        newItems[index].partial_jumlah_satuan_listing || '',
                    );
            }
        }

        // Clear partial payment data when toggled off
        if (field === 'is_partial_payment' && !value) {
            newItems[index].partial_jumlah_satuan = '';
            newItems[index].estimasi_honor_partial = 0;
        }

        if (field === 'is_partial_payment_listing' && !value) {
            newItems[index].partial_jumlah_satuan_listing = '';
            newItems[index].estimasi_honor_partial_listing = 0;
        }

        setAlokasiItems(newItems);
    };

    // Calculate total estimasi for each phase
    // If partial payment is enabled, use partial honor instead
    const totalEstimasiPencacahan = alokasiItems.reduce(
        (sum, item) =>
            sum +
            (item.is_partial_payment
                ? item.estimasi_honor_partial || 0
                : item.estimasi_honor || 0),
        0,
    );
    const totalEstimasiListing = selectedKegiatan?.has_listing_updating
        ? alokasiItems.reduce(
              (sum, item) =>
                  sum +
                  (item.is_partial_payment_listing
                      ? item.estimasi_honor_partial_listing || 0
                      : item.estimasi_honor_listing || 0),
              0,
          )
        : 0;

    // Calculate sisa pagu for each phase
    // Total terpakai = total honor periode lain + estimasi periode ini
    const totalTerpakaiPencacahan =
        current_total_spent + totalEstimasiPencacahan;
    const totalTerpakaiListing =
        current_total_spent_listing + totalEstimasiListing;
    const sisaPaguPencacahan = pagu_pencacahan - totalTerpakaiPencacahan;
    const sisaPaguListing = pagu_listing - totalTerpakaiListing;
    const isSufficientPencacahan = sisaPaguPencacahan >= 0;
    const isSufficientListing = sisaPaguListing >= 0;

    // Format currency
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    // Handle submit
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        // Validate
        if (!selectedKegiatanId) {
            setErrors({ kegiatan_id: 'Pilih kegiatan terlebih dahulu' });
            setProcessing(false);
            return;
        }

        // Validate all items
        let hasEmpty = alokasiItems.some(
            (item) => !item.petugas_id || !item.peran,
        );

        // Validate based on tahapan selection
        if (selectedKegiatan?.has_listing_updating) {
            if (tahapan === 'both') {
                hasEmpty =
                    hasEmpty ||
                    alokasiItems.some(
                        (item) =>
                            !item.jumlah_satuan || !item.jumlah_satuan_listing,
                    );
            } else if (tahapan === 'listing_only') {
                hasEmpty =
                    hasEmpty ||
                    alokasiItems.some((item) => !item.jumlah_satuan_listing);
            } else if (tahapan === 'pencacahan_only') {
                hasEmpty =
                    hasEmpty ||
                    alokasiItems.some((item) => !item.jumlah_satuan);
            }
        } else {
            hasEmpty =
                hasEmpty || alokasiItems.some((item) => !item.jumlah_satuan);
        }

        if (hasEmpty) {
            let errorMsg = 'Lengkapi semua data petugas termasuk peran';
            if (selectedKegiatan?.has_listing_updating) {
                if (tahapan === 'both') {
                    errorMsg =
                        'Lengkapi semua data petugas termasuk peran, jumlah satuan listing dan pencacahan';
                } else if (tahapan === 'listing_only') {
                    errorMsg =
                        'Lengkapi semua data petugas termasuk peran dan jumlah satuan listing';
                } else if (tahapan === 'pencacahan_only') {
                    errorMsg =
                        'Lengkapi semua data petugas termasuk peran dan jumlah satuan pencacahan';
                }
            }
            setErrors({
                alokasi: errorMsg,
            });
            setProcessing(false);
            return;
        }

        // Prepare data
        const formData = {
            tahun: active_year,
            bulan: bulan,
            jenis_perubahan_revisi: isRevisiMode
                ? jenisPerubahanRevisi
                : undefined,
            tanggal_mulai:
                tahapan !== 'listing_only'
                    ? tanggalMulai || undefined
                    : undefined,
            tanggal_selesai:
                tahapan !== 'listing_only'
                    ? tanggalSelesai || undefined
                    : undefined,
            tanggal_mulai_listing:
                tahapan === 'both' || tahapan === 'listing_only'
                    ? tanggalMulaiListing || undefined
                    : undefined,
            tanggal_selesai_listing:
                tahapan === 'both' || tahapan === 'listing_only'
                    ? tanggalSelesaiListing || undefined
                    : undefined,
            jadwal_pengolahan_listing_mulai:
                jadwalPengolahanListingMulai || undefined,
            jadwal_pengolahan_listing_selesai:
                jadwalPengolahanListingSelesai || undefined,
            jadwal_pengolahan_pencacahan_mulai:
                jadwalPengolahanPencacahanMulai || undefined,
            jadwal_pengolahan_pencacahan_selesai:
                jadwalPengolahanPencacahanSelesai || undefined,
            alokasi: alokasiItems.map((item) => {
                const base = {
                    petugas_id: item.petugas_id,
                    peran: item.peran,
                    bulan,
                    tahun: active_year,
                    jenis_kegiatan: jenisKegiatan,
                    catatan: item.catatan || '',
                    tahapan: selectedKegiatan?.has_listing_updating
                        ? tahapan
                        : 'both', // Always send tahapan
                    is_partial_payment: item.is_partial_payment || false,
                    partial_jumlah_satuan: item.is_partial_payment
                        ? Number(item.partial_jumlah_satuan) || 0
                        : undefined,
                    is_partial_payment_listing:
                        item.is_partial_payment_listing || false,
                    partial_jumlah_satuan_listing:
                        item.is_partial_payment_listing
                            ? Number(item.partial_jumlah_satuan_listing) || 0
                            : undefined,
                };

                // Handle based on tahapan
                if (selectedKegiatan?.has_listing_updating) {
                    if (tahapan === 'both') {
                        return {
                            ...base,
                            jumlah_satuan: Number(item.jumlah_satuan) || 0,
                            estimasi_honor: item.estimasi_honor || 0,
                            jumlah_satuan_listing:
                                Number(item.jumlah_satuan_listing) || 0,
                            estimasi_honor_listing:
                                item.estimasi_honor_listing || 0,
                        };
                    } else if (tahapan === 'listing_only') {
                        return {
                            ...base,
                            jumlah_satuan: 0,
                            estimasi_honor: 0,
                            jumlah_satuan_listing:
                                Number(item.jumlah_satuan_listing) || 0,
                            estimasi_honor_listing:
                                item.estimasi_honor_listing || 0,
                        };
                    } else {
                        // pencacahan_only
                        return {
                            ...base,
                            jumlah_satuan: Number(item.jumlah_satuan) || 0,
                            estimasi_honor: item.estimasi_honor || 0,
                            jumlah_satuan_listing: 0,
                            estimasi_honor_listing: 0,
                        };
                    }
                }

                return {
                    ...base,
                    jumlah_satuan: Number(item.jumlah_satuan) || 0,
                    estimasi_honor: item.estimasi_honor || 0,
                };
            }),
        };
        // Use hashed_id for the route (model uses HasHashedRouteKey)
        const kegiatanHashedId = selectedKegiatan?.hashed_id;

        if (!kegiatanHashedId) {
            setErrors({ kegiatan_id: 'Kegiatan tidak valid' });
            setProcessing(false);
            return;
        }

        // Use different routes for create vs edit mode
        if (isEditMode || isRevisiMode) {
            // Edit/Revisi mode - use PUT to update endpoint
            // Ensure tahun and bulan are in formData for backend validation
            const tahunValue = formData.tahun || active_year;
            const bulanValue = formData.bulan || bulan;

            const tahunStr = String(tahunValue);
            const bulanStr = String(bulanValue).padStart(2, '0');

            // Update formData to ensure consistent values
            formData.tahun = tahunValue;
            formData.bulan = bulanValue;

            router.put(
                `/alokasi/periode/${kegiatanHashedId}/${tahunStr}/${bulanStr}`,
                formData,
                {
                    onSuccess: () => {
                        // Success handled by Inertia
                    },
                    onError: (errors) => {
                        setErrors(errors);
                        setProcessing(false);
                    },
                    onFinish: () => {
                        setProcessing(false);
                    },
                },
            );
        } else {
            // Create mode - use POST to store-multiple endpoint
            router.post(
                `/alokasi/kegiatan/${kegiatanHashedId}/store-multiple`,
                formData,
                {
                    onSuccess: () => {
                        // Success handled by Inertia
                    },
                    onError: (errors) => {
                        setErrors(errors);
                        setProcessing(false);
                    },
                    onFinish: () => {
                        setProcessing(false);
                    },
                },
            );
        }
    };

    const handleImportAlokasi = () => {
        if (!importFile) {
            setErrors((prev) => ({
                ...prev,
                file: 'Pilih file template alokasi terlebih dahulu.',
            }));
            return;
        }

        if (!selectedKegiatan?.hashed_id) {
            setErrors((prev) => ({
                ...prev,
                file: 'Pilih kegiatan terlebih dahulu sebelum impor.',
            }));
            return;
        }

        setImportProcessing(true);
        setImportPreviewRows([]);
        setImportPreviewErrors([]);
        setErrors((prev) => {
            const nextErrors = { ...prev };
            delete nextErrors.file;
            return nextErrors;
        });

        const formData = new FormData();
        formData.append('file', importFile);
        formData.append('tahapan', tahapan);

        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') || '';

        fetch(
            `/alokasi/kegiatan/${selectedKegiatan.hashed_id}/import-preview`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: formData,
            },
        )
            .then(async (response) => {
                const payload = await response.json();

                if (!response.ok) {
                    const message =
                        payload?.message ||
                        payload?.errors?.file?.[0] ||
                        'Gagal membaca file impor.';
                    throw new Error(message);
                }

                const rows = payload.rows || [];
                const errors = payload.errors || [];

                if (rows.length === 0 && errors.length === 0) {
                    const totalRows = Number(payload?.summary?.total_rows ?? 0);
                    setImportPreviewErrors([
                        totalRows > 0
                            ? 'Tidak ada baris data yang bisa dipreview. Pastikan kolom NIK dan Kode Penugasan sudah dipilih dari dropdown template.'
                            : 'File tidak berisi data alokasi yang dapat dipreview.',
                    ]);
                    setImportPreviewRows([]);
                    return;
                }

                setImportPreviewRows(rows);
                setImportPreviewErrors(errors);
            })
            .catch((error: Error) => {
                setErrors((prev) => ({
                    ...prev,
                    file: error.message,
                }));
            })
            .finally(() => {
                setImportProcessing(false);
            });
    };

    const applyImportPreviewToForm = () => {
        if (importPreviewRows.length === 0) {
            return;
        }

        const mappedItems: AlokasiItem[] = importPreviewRows.map((row) => ({
            petugas_id: row.petugas_id,
            peran: row.peran,
            jumlah_satuan: row.jumlah_satuan,
            estimasi_honor: row.estimasi_honor,
            jumlah_satuan_listing: row.jumlah_satuan_listing,
            estimasi_honor_listing: row.estimasi_honor_listing,
            catatan: row.catatan || '',
            is_partial_payment: row.is_partial_payment,
            partial_jumlah_satuan: row.partial_jumlah_satuan,
            estimasi_honor_partial: row.estimasi_honor_partial,
            is_partial_payment_listing: row.is_partial_payment_listing,
            partial_jumlah_satuan_listing: row.partial_jumlah_satuan_listing,
            estimasi_honor_partial_listing: row.estimasi_honor_partial_listing,
        }));

        setAlokasiItems(mappedItems);
        setJumlahPetugas(mappedItems.length);
        setImportPreviewRows([]);
        setImportPreviewErrors([]);
        setImportFile(null);
    };

    const exportTemplateUrl =
        isEditMode || isRevisiMode
            ? sourcePeriode?.hashed_id
                ? `/alokasi/periode/${sourcePeriode.hashed_id}/export/edit`
                : null
            : selectedKegiatan?.hashed_id
              ? `/alokasi/periode/export/create?kegiatan=${selectedKegiatan.hashed_id}&tahapan=${tahapan}`
              : null;

    const months = useMemo(
        () => [
            { value: 1, label: 'Januari' },
            { value: 2, label: 'Februari' },
            { value: 3, label: 'Maret' },
            { value: 4, label: 'April' },
            { value: 5, label: 'Mei' },
            { value: 6, label: 'Juni' },
            { value: 7, label: 'Juli' },
            { value: 8, label: 'Agustus' },
            { value: 9, label: 'September' },
            { value: 10, label: 'Oktober' },
            { value: 11, label: 'November' },
            { value: 12, label: 'Desember' },
        ],
        [],
    );

    // Filter months based on kegiatan's tanggal_mulai and tanggal_selesai
    const filteredMonths = useMemo(() => {
        if (
            !selectedKegiatan ||
            !selectedKegiatan.tanggal_mulai ||
            !selectedKegiatan.tanggal_selesai
        ) {
            return months;
        }
        const start = new Date(selectedKegiatan.tanggal_mulai);
        const end = new Date(selectedKegiatan.tanggal_selesai);

        // Only show months in the correct year and within the kegiatan's date range
        const filtered = months.filter((m) => {
            // The year must be within kegiatan range
            if (
                active_year < start.getFullYear() ||
                active_year > end.getFullYear()
            ) {
                return false;
            }

            // If kegiatan starts and ends in the same year
            if (start.getFullYear() === end.getFullYear()) {
                return (
                    active_year === start.getFullYear() &&
                    m.value >= start.getMonth() + 1 &&
                    m.value <= end.getMonth() + 1
                );
            }

            // If this is the start year
            if (active_year === start.getFullYear()) {
                return m.value >= start.getMonth() + 1;
            }

            // If this is the end year
            if (active_year === end.getFullYear()) {
                return m.value <= end.getMonth() + 1;
            }

            // If this is a year between start and end, show all months
            return (
                active_year > start.getFullYear() &&
                active_year < end.getFullYear()
            );
        });

        return filtered;
    }, [selectedKegiatan, months, active_year]);

    // Filter months by tahapan availability for listing kegiatan
    const availableMonthsForTahapan = useMemo(() => {
        if (!selectedKegiatan || !selectedKegiatan.has_listing_updating) {
            // For non-listing kegiatan: filter out fully used months
            return filteredMonths.filter(
                (month) => !usedMonths.includes(month.value),
            );
        }

        // For listing kegiatan: filter based on selected tahapan
        const usedInfo = used_months_info[Number(selectedKegiatan.id)];
        if (!usedInfo || !usedInfo.has_listing) {
            return filteredMonths.filter(
                (month) => !usedMonths.includes(month.value),
            );
        }

        return filteredMonths.filter((month) => {
            const usedTahapan = usedInfo.periods?.[month.value] || [];

            if (tahapan === 'both') {
                // Need both slots available
                return (
                    !usedTahapan.includes('listing') &&
                    !usedTahapan.includes('pencacahan')
                );
            } else if (tahapan === 'listing_only') {
                return !usedTahapan.includes('listing');
            } else if (tahapan === 'pencacahan_only') {
                return !usedTahapan.includes('pencacahan');
            }

            return true;
        });
    }, [
        selectedKegiatan,
        filteredMonths,
        usedMonths,
        tahapan,
        used_months_info,
    ]);

    const selectedPeriodeMinDate = useMemo(() => {
        if (!bulan) {
            return undefined;
        }

        return `${active_year}-${String(bulan).padStart(2, '0')}-01`;
    }, [active_year, bulan]);

    const selectedPeriodeMaxDate = useMemo(() => {
        if (!bulan) {
            return undefined;
        }

        const lastDay = new Date(active_year, bulan, 0).getDate();

        return `${active_year}-${String(bulan).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
    }, [active_year, bulan]);

    const mergeDateMin = useCallback(
        (dynamicMin?: string) => {
            if (selectedPeriodeMinDate && dynamicMin) {
                return dynamicMin > selectedPeriodeMinDate
                    ? dynamicMin
                    : selectedPeriodeMinDate;
            }

            return dynamicMin || selectedPeriodeMinDate;
        },
        [selectedPeriodeMinDate],
    );

    const mergeDateMax = useCallback(
        (dynamicMax?: string) => {
            if (selectedPeriodeMaxDate && dynamicMax) {
                return dynamicMax < selectedPeriodeMaxDate
                    ? dynamicMax
                    : selectedPeriodeMaxDate;
            }

            return dynamicMax || selectedPeriodeMaxDate;
        },
        [selectedPeriodeMaxDate],
    );

    // Set default bulan to first available month in availableMonthsForTahapan
    useEffect(() => {
        // Skip if in edit mode or if there's a source periode
        if (isEditMode || sourcePeriode) {
            return;
        }

        // Find first available month from availableMonthsForTahapan
        const availableMonths = availableMonthsForTahapan;

        if (availableMonths.length > 0) {
            const firstAvailableMonth = availableMonths[0].value;
            // Only update if current bulan is not in available months
            if (!availableMonths.some((m) => m.value === bulan)) {
                // eslint-disable-next-line react-hooks/set-state-in-effect
                setBulan(firstAvailableMonth);
            }
        } else if (filteredMonths.length > 0) {
            // If all months are used, use the first month from filteredMonths

            setBulan(filteredMonths[0].value);
        }
    }, [
        filteredMonths,
        usedMonths,
        isEditMode,
        sourcePeriode,
        availableMonthsForTahapan,
        bulan,
    ]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={
                    isViewMode
                        ? 'Detail Periode Kegiatan'
                        : 'Tambah Periode Kegiatan'
                }
            />

            <PageHeader
                title={
                    isViewMode
                        ? 'Detail Periode Kegiatan'
                        : 'Tambah Periode Kegiatan'
                }
                description={
                    isViewMode
                        ? 'Detail alokasi petugas pada periode ini'
                        : 'Alokasikan petugas ke kegiatan untuk periode yang dipilih'
                }
            >
                <Button variant="outline" asChild>
                    <Link href="/alokasi">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            <ContentCard>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label className="text-base font-semibold">
                            Template Impor Alokasi
                        </Label>
                        <p className="text-sm text-neutral-600 dark:text-neutral-400">
                            {isEditMode || isRevisiMode
                                ? 'Download template berisi data alokasi periode ini untuk diedit massal.'
                                : selectedKegiatan
                                  ? 'Download template yang menyesuaikan jenis kegiatan yang dipilih.'
                                  : 'Pilih kegiatan terlebih dahulu untuk mengunduh template yang sesuai.'}
                        </p>
                        <Button
                            variant="outline"
                            asChild
                            className="gap-2"
                            disabled={!exportTemplateUrl}
                        >
                            <a href={exportTemplateUrl ?? '#'}>
                                <Download className="h-4 w-4" />
                                Download Template
                            </a>
                        </Button>
                    </div>

                    <div className="space-y-2">
                        <Label
                            htmlFor="alokasi_import_file"
                            className="cursor-pointer text-base font-semibold"
                        >
                            Impor File Alokasi
                        </Label>
                        <Input
                            id="alokasi_import_file"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            onChange={(e) =>
                                setImportFile(e.target.files?.[0] ?? null)
                            }
                            disabled={isViewMode}
                        />
                        {allErrors.file && (
                            <p className="text-sm text-red-500">
                                {allErrors.file}
                            </p>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            className="cursor-pointergap-2"
                            onClick={handleImportAlokasi}
                            disabled={importProcessing || isViewMode}
                        >
                            {importProcessing ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <FileUp className="h-4 w-4" />
                            )}
                            Preview Data Impor
                        </Button>

                        {(importPreviewRows.length > 0 ||
                            importPreviewErrors.length > 0) && (
                            <div className="mt-3 space-y-2 rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                <p className="text-sm font-medium">
                                    Preview Impor ({importPreviewRows.length}{' '}
                                    baris valid)
                                </p>

                                {importPreviewErrors.length > 0 && (
                                    <div className="space-y-1 rounded-md bg-red-50 p-2 text-xs text-red-700 dark:bg-red-950/30 dark:text-red-300">
                                        {importPreviewErrors.map((error) => (
                                            <p key={error}>{error}</p>
                                        ))}
                                    </div>
                                )}

                                {importPreviewRows.length > 0 && (
                                    <div className="max-h-40 overflow-auto rounded-md border border-neutral-200 dark:border-neutral-700">
                                        <table className="w-full text-xs">
                                            <thead className="bg-neutral-100 dark:bg-neutral-800">
                                                <tr>
                                                    <th className="px-2 py-1 text-left">
                                                        NIK
                                                    </th>
                                                    <th className="px-2 py-1 text-left">
                                                        Nama
                                                    </th>
                                                    <th className="px-2 py-1 text-left">
                                                        Peran
                                                    </th>
                                                    <th className="px-2 py-1 text-right">
                                                        Pencacahan
                                                    </th>
                                                    <th className="px-2 py-1 text-right">
                                                        Listing
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {importPreviewRows.map(
                                                    (row, index) => (
                                                        <tr
                                                            key={`${row.petugas_id}-${index}`}
                                                            className="border-t border-neutral-200 dark:border-neutral-700"
                                                        >
                                                            <td className="px-2 py-1">
                                                                {row.nik}
                                                            </td>
                                                            <td className="px-2 py-1">
                                                                {
                                                                    row.petugas_nama
                                                                }
                                                            </td>
                                                            <td className="px-2 py-1">
                                                                {row.peran}
                                                            </td>
                                                            <td className="px-2 py-1 text-right">
                                                                {
                                                                    row.jumlah_satuan
                                                                }
                                                            </td>
                                                            <td className="px-2 py-1 text-right">
                                                                {row.jumlah_satuan_listing ||
                                                                    0}
                                                            </td>
                                                        </tr>
                                                    ),
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                )}

                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        onClick={applyImportPreviewToForm}
                                        disabled={
                                            importPreviewRows.length === 0
                                        }
                                    >
                                        Konfirmasi & Terapkan ke Form
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => {
                                            setImportPreviewRows([]);
                                            setImportPreviewErrors([]);
                                        }}
                                    >
                                        Batal
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </ContentCard>

            {isRevisiMode && (
                <div className="rounded-xl border border-indigo-400/30 bg-gradient-to-r from-indigo-500/15 via-sky-500/10 to-indigo-500/15 p-4 shadow-lg backdrop-blur-xl dark:border-indigo-500/25 dark:from-indigo-600/15 dark:via-sky-600/10 dark:to-indigo-600/15">
                    <div className="flex flex-col gap-1">
                        <p className="text-sm font-semibold text-indigo-900 dark:text-indigo-300">
                            Mode Revisi Aktif
                        </p>
                        <p className="text-sm text-indigo-800 dark:text-indigo-400">
                            {isPerubahanPetugasMode
                                ? 'Jenis revisi: Perubahan Petugas (jumlah petugas, nama petugas, dan peran dapat diubah).'
                                : 'Jenis revisi: Perubahan Beban Tugas (hanya beban tugas dan nilai terkait yang diubah).'}
                        </p>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="jenis_perubahan_revisi">
                            Jenis Perubahan/Revisi{' '}
                            <span className="text-red-500">*</span>
                        </Label>
                        <div
                            id="jenis_perubahan_revisi"
                            className="inline-flex w-full rounded-lg border border-neutral-300 p-1 dark:border-neutral-700"
                        >
                            <Button
                                type="button"
                                variant={
                                    jenisPerubahanRevisi ===
                                    'perubahan_beban_tugas'
                                        ? 'default'
                                        : 'ghost'
                                }
                                className="w-1/2 rounded-r-none"
                                disabled={isViewMode}
                                onClick={() =>
                                    setJenisPerubahanRevisi(
                                        'perubahan_beban_tugas',
                                    )
                                }
                            >
                                Perubahan Beban Tugas
                            </Button>
                            <Button
                                type="button"
                                variant={
                                    jenisPerubahanRevisi === 'perubahan_petugas'
                                        ? 'default'
                                        : 'ghost'
                                }
                                className="w-1/2 rounded-l-none"
                                disabled={isViewMode}
                                onClick={() =>
                                    setJenisPerubahanRevisi('perubahan_petugas')
                                }
                            >
                                Perubahan Petugas
                            </Button>
                        </div>
                        <p className="text-xs text-neutral-500 dark:text-neutral-400">
                            Perubahan beban tugas: edit beban tugas saja.
                            Perubahan petugas: jumlah petugas, nama petugas, dan
                            peran dapat diubah.
                        </p>
                    </div>
                </div>
            )}

            {/* Source Period Info - Only show for copy mode, not edit mode */}
            {sourcePeriode && !isEditMode && !isViewMode && (
                <div className="rounded-xl border border-amber-400/30 bg-gradient-to-br from-amber-500/10 via-amber-400/5 to-amber-300/10 p-4 shadow-xl backdrop-blur-xl dark:border-amber-500/20 dark:from-amber-600/10 dark:via-amber-500/5 dark:to-amber-400/10">
                    <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-500/20 backdrop-blur-sm dark:bg-amber-400/20">
                            <Copy className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div className="flex-1">
                            <h3 className="text-sm font-semibold text-amber-800 dark:text-amber-300">
                                Menyalin Alokasi dari Periode Sebelumnya
                            </h3>
                            <p className="mt-1 text-sm text-amber-700 dark:text-amber-400">
                                Data alokasi berikut disalin dari periode{' '}
                                {
                                    months.find(
                                        (m) =>
                                            m.value ===
                                            parseInt(sourcePeriode.bulan),
                                    )?.label
                                }{' '}
                                {sourcePeriode.tahun}. Anda dapat mengubah
                                petugas, jumlah beban tugas, atau
                                menambah/mengurangi petugas sesuai kebutuhan.
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* Display SBML and Budget Errors */}
            {Object.keys(allErrors).length > 0 && (
                <div
                    ref={errorAlertRef}
                    tabIndex={-1}
                    className="rounded-xl border border-red-400/30 bg-gradient-to-br from-red-500/10 via-red-400/5 to-red-300/10 p-4 shadow-2xl backdrop-blur-xl focus:ring-2 focus:ring-red-400/50 focus:ring-offset-2 focus:outline-none dark:border-red-500/20 dark:from-red-600/10 dark:via-red-500/5 dark:to-red-400/10"
                >
                    <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-500/20 backdrop-blur-sm dark:bg-red-400/20">
                            <X className="h-5 w-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div className="flex-1">
                            <h3 className="text-base font-bold text-red-800 dark:text-red-300">
                                Validasi Gagal
                            </h3>
                            <div className="mt-2 space-y-1">
                                {Object.entries(allErrors).map(
                                    ([key, value]) => (
                                        <div
                                            key={key}
                                            className="text-sm whitespace-pre-line text-red-700 dark:text-red-400"
                                        >
                                            {typeof value === 'string'
                                                ? value
                                                : JSON.stringify(value)}
                                        </div>
                                    ),
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Step 1: Periode Kegiatan */}
                <ContentCard>
                    <div className="space-y-6">
                        <div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                1. Periode Kegiatan
                            </h3>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Pilih kegiatan dan periode alokasi
                            </p>
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            {/* Kegiatan */}
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="kegiatan_id">
                                    Kegiatan{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <SearchableSelect
                                    options={kegiatanOptions.map(
                                        (kegiatan) => ({
                                            value: kegiatan.id,
                                            label: kegiatan.nama_kegiatan,
                                            searchKeywords: `${kegiatan.kode_kegiatan} ${kegiatan.nama_kegiatan} ${kegiatan.deskripsi || ''}`,
                                        }),
                                    )}
                                    value={selectedKegiatanId}
                                    onValueChange={(value) => {
                                        setSelectedKegiatanId(value);
                                        setRestorableItemsByCount([]);
                                    }}
                                    placeholder="Pilih Kegiatan"
                                    searchPlaceholder="Cari kegiatan..."
                                    defaultVisibleCount={15}
                                    disabled={isEditMode || isViewMode}
                                />
                                {allErrors.kegiatan_id && (
                                    <p className="text-sm text-red-500">
                                        {allErrors.kegiatan_id}
                                    </p>
                                )}
                            </div>

                            {/* Jenis Kegiatan (dari Kegiatan) */}
                            <div className="space-y-2">
                                <Label htmlFor="jenis_kegiatan">
                                    Jenis Kegiatan
                                </Label>
                                <Input
                                    type="text"
                                    id="jenis_kegiatan"
                                    value={
                                        selectedKegiatan
                                            ? selectedKegiatan.jenis_kegiatan ===
                                              'sensus'
                                                ? 'Sensus'
                                                : 'Survei'
                                            : '-'
                                    }
                                    disabled
                                    className="cursor-not-allowed bg-neutral-100 capitalize dark:bg-neutral-900"
                                />
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                    Jenis kegiatan diambil dari data kegiatan
                                </p>
                            </div>

                            {/* Tahapan Dropdown - only for kegiatan with listing */}
                            {selectedKegiatan?.has_listing_updating && (
                                <div className="space-y-2">
                                    <Label htmlFor="tahapan">
                                        Tahapan Periode{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={tahapan}
                                        onValueChange={(value) =>
                                            setTahapan(
                                                value as
                                                    | 'both'
                                                    | 'listing_only'
                                                    | 'pencacahan_only',
                                            )
                                        }
                                        disabled={isRevisiMode || isViewMode}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih Tahapan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="both">
                                                Listing dan Pencacahan
                                            </SelectItem>
                                            <SelectItem value="listing_only">
                                                Listing Saja
                                            </SelectItem>
                                            <SelectItem value="pencacahan_only">
                                                Pencacahan Saja
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            {/* Bulan */}
                            <div className="space-y-2">
                                <Label htmlFor="bulan">
                                    Bulan{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={bulan.toString()}
                                    onValueChange={(value) =>
                                        setBulan(parseInt(value))
                                    }
                                    disabled={isRevisiMode || isViewMode}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableMonthsForTahapan.map(
                                            (month) => (
                                                <SelectItem
                                                    key={month.value}
                                                    value={month.value.toString()}
                                                >
                                                    {month.label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Tahun (from Active Year) */}
                            <div className="space-y-2">
                                <Label htmlFor="tahun">Tahun</Label>
                                <Input
                                    type="text"
                                    id="tahun"
                                    value={active_year}
                                    disabled
                                    className="cursor-not-allowed bg-neutral-100 dark:bg-neutral-900"
                                />
                            </div>
                        </div>

                        {/* Jadwal Kegiatan Section */}
                        {selectedKegiatanId && bulan && (
                            <div className="space-y-4 border-t pt-6">
                                <div>
                                    <h4 className="text-base font-semibold text-neutral-900 dark:text-white">
                                        Jadwal Kegiatan
                                    </h4>
                                    <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                        Tentukan tanggal pelaksanaan kegiatan
                                        untuk periode ini
                                    </p>
                                </div>

                                {/* Jadwal Listing - Show only if tahapan includes listing */}
                                {tahapan === 'both' &&
                                    selectedKegiatan?.has_listing_updating && (
                                        <div className="rounded-lg border border-blue-400/30 bg-gradient-to-br from-blue-500/20 via-blue-400/10 to-blue-300/10 p-4 shadow-lg backdrop-blur-xl dark:border-blue-400/20 dark:from-blue-500/10 dark:via-neutral-800/20 dark:to-neutral-800/10">
                                            <h5 className="mb-3 text-sm font-semibold text-blue-900 dark:text-blue-300">
                                                Jadwal Listing
                                            </h5>
                                            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div className="space-y-2">
                                                    <Label htmlFor="tanggal_mulai_listing">
                                                        Tanggal Mulai Listing
                                                        <span className="text-red-500">
                                                            *
                                                        </span>
                                                    </Label>
                                                    <DatePicker
                                                        id="tanggal_mulai_listing"
                                                        value={
                                                            tanggalMulaiListing
                                                        }
                                                        onChange={(v) =>
                                                            setTanggalMulaiListing(
                                                                v,
                                                            )
                                                        }
                                                        min={mergeDateMin()}
                                                        max={mergeDateMax()}
                                                        disabled={isViewMode}
                                                    />
                                                    {allErrors.tanggal_mulai_listing && (
                                                        <p className="text-sm text-red-500">
                                                            {
                                                                allErrors.tanggal_mulai_listing
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="space-y-2">
                                                    <Label htmlFor="tanggal_selesai_listing">
                                                        Tanggal Selesai Listing
                                                        <span className="text-red-500">
                                                            *
                                                        </span>
                                                    </Label>
                                                    <DatePicker
                                                        id="tanggal_selesai_listing"
                                                        value={
                                                            tanggalSelesaiListing
                                                        }
                                                        onChange={(v) =>
                                                            setTanggalSelesaiListing(
                                                                v,
                                                            )
                                                        }
                                                        min={mergeDateMin(
                                                            tanggalMulaiListing,
                                                        )}
                                                        max={mergeDateMax()}
                                                        disabled={isViewMode}
                                                    />
                                                    {allErrors.tanggal_selesai_listing && (
                                                        <p className="text-sm text-red-500">
                                                            {
                                                                allErrors.tanggal_selesai_listing
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}

                                {/* Jadwal Pencacahan */}
                                <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                                    <h5 className="mb-3 text-sm font-semibold text-green-900 dark:text-green-300">
                                        Jadwal{' '}
                                        {tahapan === 'listing_only'
                                            ? 'Listing'
                                            : 'Pencacahan'}
                                    </h5>
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label
                                                htmlFor={
                                                    tahapan === 'listing_only'
                                                        ? 'tanggal_mulai_listing'
                                                        : 'tanggal_mulai'
                                                }
                                            >
                                                Tanggal Mulai
                                                <span className="text-red-500">
                                                    *
                                                </span>
                                            </Label>
                                            <DatePicker
                                                id={
                                                    tahapan === 'listing_only'
                                                        ? 'tanggal_mulai_listing'
                                                        : 'tanggal_mulai'
                                                }
                                                value={
                                                    tahapan === 'listing_only'
                                                        ? tanggalMulaiListing
                                                        : tanggalMulai
                                                }
                                                onChange={(v) =>
                                                    tahapan === 'listing_only'
                                                        ? setTanggalMulaiListing(
                                                              v,
                                                          )
                                                        : setTanggalMulai(v)
                                                }
                                                min={mergeDateMin()}
                                                max={mergeDateMax()}
                                                disabled={isViewMode}
                                            />
                                            {tahapan === 'listing_only'
                                                ? allErrors.tanggal_mulai_listing && (
                                                      <p className="text-sm text-red-500">
                                                          {
                                                              allErrors.tanggal_mulai_listing
                                                          }
                                                      </p>
                                                  )
                                                : allErrors.tanggal_mulai && (
                                                      <p className="text-sm text-red-500">
                                                          {
                                                              allErrors.tanggal_mulai
                                                          }
                                                      </p>
                                                  )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label
                                                htmlFor={
                                                    tahapan === 'listing_only'
                                                        ? 'tanggal_selesai_listing'
                                                        : 'tanggal_selesai'
                                                }
                                            >
                                                Tanggal Selesai
                                                <span className="text-red-500">
                                                    *
                                                </span>
                                            </Label>
                                            <DatePicker
                                                id={
                                                    tahapan === 'listing_only'
                                                        ? 'tanggal_selesai_listing'
                                                        : 'tanggal_selesai'
                                                }
                                                value={
                                                    tahapan === 'listing_only'
                                                        ? tanggalSelesaiListing
                                                        : tanggalSelesai
                                                }
                                                onChange={(v) =>
                                                    tahapan === 'listing_only'
                                                        ? setTanggalSelesaiListing(
                                                              v,
                                                          )
                                                        : setTanggalSelesai(v)
                                                }
                                                min={mergeDateMin(
                                                    tahapan === 'listing_only'
                                                        ? tanggalMulaiListing
                                                        : tanggalMulai,
                                                )}
                                                max={mergeDateMax()}
                                                disabled={isViewMode}
                                            />
                                            {tahapan === 'listing_only'
                                                ? allErrors.tanggal_selesai_listing && (
                                                      <p className="text-sm text-red-500">
                                                          {
                                                              allErrors.tanggal_selesai_listing
                                                          }
                                                      </p>
                                                  )
                                                : allErrors.tanggal_selesai && (
                                                      <p className="text-sm text-red-500">
                                                          {
                                                              allErrors.tanggal_selesai
                                                          }
                                                      </p>
                                                  )}
                                        </div>
                                    </div>
                                </div>

                                {/* Jadwal Pengolahan Section - Show conditionally based on rate honor having pengolahan role */}
                                {selectedKegiatan?.rate_honors?.some(
                                    (r) =>
                                        r.jenis_penugasan === 'pengolahan' ||
                                        r.jenis_penugasan ===
                                            'pengawas_pengolahan',
                                ) && (
                                    <>
                                        {/* Jadwal Pengolahan Listing */}
                                        {(tahapan === 'both' ||
                                            tahapan === 'listing_only') &&
                                            selectedKegiatan?.has_listing_updating && (
                                                <div className="rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
                                                    <h5 className="mb-3 text-sm font-semibold text-purple-900 dark:text-purple-300">
                                                        Jadwal Pengolahan
                                                        Listing (Opsional)
                                                    </h5>
                                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                        <div className="space-y-2">
                                                            <Label htmlFor="jadwal_pengolahan_listing_mulai">
                                                                Tanggal Mulai
                                                                Pengolahan
                                                                Listing
                                                            </Label>
                                                            <DatePicker
                                                                id="jadwal_pengolahan_listing_mulai"
                                                                value={
                                                                    jadwalPengolahanListingMulai
                                                                }
                                                                onChange={(v) =>
                                                                    setJadwalPengolahanListingMulai(
                                                                        v,
                                                                    )
                                                                }
                                                                min={mergeDateMin()}
                                                                max={mergeDateMax()}
                                                                disabled={
                                                                    isViewMode
                                                                }
                                                            />
                                                            {allErrors.jadwal_pengolahan_listing_mulai && (
                                                                <p className="text-sm text-red-500">
                                                                    {
                                                                        allErrors.jadwal_pengolahan_listing_mulai
                                                                    }
                                                                </p>
                                                            )}
                                                        </div>
                                                        <div className="space-y-2">
                                                            <Label htmlFor="jadwal_pengolahan_listing_selesai">
                                                                Tanggal Selesai
                                                                Pengolahan
                                                                Listing
                                                            </Label>
                                                            <DatePicker
                                                                id="jadwal_pengolahan_listing_selesai"
                                                                value={
                                                                    jadwalPengolahanListingSelesai
                                                                }
                                                                onChange={(v) =>
                                                                    setJadwalPengolahanListingSelesai(
                                                                        v,
                                                                    )
                                                                }
                                                                min={mergeDateMin(
                                                                    jadwalPengolahanListingMulai,
                                                                )}
                                                                max={mergeDateMax()}
                                                                disabled={
                                                                    isViewMode
                                                                }
                                                            />
                                                            {allErrors.jadwal_pengolahan_listing_selesai && (
                                                                <p className="text-sm text-red-500">
                                                                    {
                                                                        allErrors.jadwal_pengolahan_listing_selesai
                                                                    }
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                        {/* Jadwal Pengolahan Pencacahan */}
                                        {(tahapan === 'both' ||
                                            tahapan === 'pencacahan_only') && (
                                            <div className="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20">
                                                <h5 className="mb-3 text-sm font-semibold text-orange-900 dark:text-orange-300">
                                                    Jadwal Pengolahan Pencacahan
                                                    Lapangan (Opsional)
                                                </h5>
                                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <div className="space-y-2">
                                                        <Label htmlFor="jadwal_pengolahan_pencacahan_mulai">
                                                            Tanggal Mulai
                                                            Pengolahan
                                                        </Label>
                                                        <DatePicker
                                                            id="jadwal_pengolahan_pencacahan_mulai"
                                                            value={
                                                                jadwalPengolahanPencacahanMulai
                                                            }
                                                            onChange={(v) =>
                                                                setJadwalPengolahanPencacahanMulai(
                                                                    v,
                                                                )
                                                            }
                                                            min={mergeDateMin()}
                                                            max={mergeDateMax()}
                                                            disabled={
                                                                isViewMode
                                                            }
                                                        />
                                                        {allErrors.jadwal_pengolahan_pencacahan_mulai && (
                                                            <p className="text-sm text-red-500">
                                                                {
                                                                    allErrors.jadwal_pengolahan_pencacahan_mulai
                                                                }
                                                            </p>
                                                        )}
                                                    </div>
                                                    <div className="space-y-2">
                                                        <Label htmlFor="jadwal_pengolahan_pencacahan_selesai">
                                                            Tanggal Selesai
                                                            Pengolahan
                                                        </Label>
                                                        <DatePicker
                                                            id="jadwal_pengolahan_pencacahan_selesai"
                                                            value={
                                                                jadwalPengolahanPencacahanSelesai
                                                            }
                                                            onChange={(v) =>
                                                                setJadwalPengolahanPencacahanSelesai(
                                                                    v,
                                                                )
                                                            }
                                                            min={mergeDateMin(
                                                                jadwalPengolahanPencacahanMulai,
                                                            )}
                                                            max={mergeDateMax()}
                                                            disabled={
                                                                isViewMode
                                                            }
                                                        />
                                                        {allErrors.jadwal_pengolahan_pencacahan_selesai && (
                                                            <p className="text-sm text-red-500">
                                                                {
                                                                    allErrors.jadwal_pengolahan_pencacahan_selesai
                                                                }
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                </ContentCard>

                {/* Step 2: Jumlah Petugas */}
                <ContentCard>
                    <div className="space-y-4">
                        <div>
                            <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                2. Jumlah Petugas
                            </h3>
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Tentukan berapa petugas yang akan dialokasikan
                                (PML, PCL, dll)
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="jumlah_petugas">
                                Jumlah Petugas{' '}
                                <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                type="number"
                                id="jumlah_petugas"
                                value={
                                    jumlahPetugas === '' ? '' : jumlahPetugas
                                }
                                onChange={(e) => {
                                    const inputValue = e.target.value;
                                    if (inputValue === '') {
                                        // Allow blank but don't change alokasiItems (keep at least 1)
                                        setJumlahPetugas('');
                                    } else {
                                        const value = parseInt(inputValue);
                                        if (!isNaN(value)) {
                                            handleJumlahPetugasChange(value);
                                        }
                                    }
                                }}
                                onBlur={(e) => {
                                    // When focus is lost, restore actual count or minimum 1
                                    const value = parseInt(e.target.value);
                                    if (isNaN(value) || value < 1) {
                                        // Set to actual alokasiItems length or 1
                                        setJumlahPetugas(
                                            Math.max(1, alokasiItems.length),
                                        );
                                    } else {
                                        handleJumlahPetugasChange(value);
                                    }
                                }}
                                min="1"
                                max="100"
                                placeholder="Masukkan jumlah petugas"
                                disabled={isRevisiLockedMode || isViewMode}
                                className={
                                    isRevisiLockedMode || isViewMode
                                        ? 'cursor-not-allowed bg-neutral-100 dark:bg-neutral-900'
                                        : ''
                                }
                            />
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Minimal 1 petugas. {jumlahPetugas} baris input
                                petugas akan ditampilkan.
                            </p>
                        </div>
                    </div>
                </ContentCard>

                {/* Step 3: Data Petugas */}
                {selectedKegiatanId && (
                    <ContentCard>
                        <div className="space-y-4">
                            <div>
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-white">
                                    3. Data Petugas
                                </h3>
                                <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                    Isi data setiap petugas yang akan
                                    dialokasikan
                                </p>
                            </div>

                            {allErrors.alokasi && (
                                <div className="rounded-xl border border-red-400/30 bg-gradient-to-br from-red-500/10 via-red-400/5 to-red-300/10 p-3 backdrop-blur-xl dark:border-red-500/20 dark:from-red-600/10 dark:via-red-500/5 dark:to-red-400/10">
                                    <p className="text-sm text-red-600 dark:text-red-400">
                                        {allErrors.alokasi}
                                    </p>
                                </div>
                            )}

                            <div className="space-y-4">
                                {alokasiItems.map((item, index) => (
                                    <div
                                        key={index}
                                        className="rounded-2xl border border-neutral-300/30 bg-white/30 p-4 backdrop-blur-xl dark:border-neutral-700/30 dark:bg-neutral-800/30"
                                    >
                                        <div className="mb-3 flex items-center justify-between">
                                            <h4 className="font-medium text-neutral-900 dark:text-white">
                                                Petugas #{index + 1}
                                            </h4>
                                            {!isViewMode && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        handleDeleteAlokasiRow(
                                                            index,
                                                        )
                                                    }
                                                    disabled={
                                                        isRevisiLockedMode ||
                                                        alokasiItems.length <= 1
                                                    }
                                                    className="h-8 cursor-pointer gap-1.5"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    Hapus
                                                </Button>
                                            )}
                                        </div>
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                            {/* Nama Petugas */}
                                            <div className="space-y-2 md:col-span-2">
                                                <Label
                                                    htmlFor={`petugas_${index}`}
                                                >
                                                    Nama Petugas{' '}
                                                    <span className="text-red-500">
                                                        *
                                                    </span>
                                                </Label>
                                                <SearchableSelect
                                                    options={(() => {
                                                        // Group and sort petugas
                                                        const organik = petugas
                                                            .filter(
                                                                (p) =>
                                                                    p.jenis_petugas ===
                                                                    'organik',
                                                            )
                                                            .sort((a, b) =>
                                                                a.nama.localeCompare(
                                                                    b.nama,
                                                                ),
                                                            );
                                                        const nonOrganik =
                                                            petugas
                                                                .filter(
                                                                    (p) =>
                                                                        p.jenis_petugas ===
                                                                        'non-organik',
                                                                )
                                                                .sort((a, b) =>
                                                                    a.nama.localeCompare(
                                                                        b.nama,
                                                                    ),
                                                                );

                                                        const sortedPetugas = [
                                                            ...organik,
                                                            ...nonOrganik,
                                                        ];

                                                        // Separate selected and unselected
                                                        const selectedIds =
                                                            alokasiItems
                                                                .map((item) =>
                                                                    String(
                                                                        item.petugas_id,
                                                                    ),
                                                                )
                                                                .filter(
                                                                    Boolean,
                                                                );
                                                        const selectedPetugas =
                                                            sortedPetugas.filter(
                                                                (p) =>
                                                                    selectedIds.includes(
                                                                        String(
                                                                            p.id,
                                                                        ),
                                                                    ),
                                                            );
                                                        const unselectedPetugas =
                                                            sortedPetugas.filter(
                                                                (p) =>
                                                                    !selectedIds.includes(
                                                                        String(
                                                                            p.id,
                                                                        ),
                                                                    ),
                                                            );

                                                        // Combine: selected first, then unselected
                                                        const finalOrderedPetugas =
                                                            [
                                                                ...selectedPetugas,
                                                                ...unselectedPetugas,
                                                            ];

                                                        return finalOrderedPetugas.map(
                                                            (p) => {
                                                                const isSelectedInOtherRow =
                                                                    alokasiItems.some(
                                                                        (
                                                                            otherItem,
                                                                            otherIndex,
                                                                        ) =>
                                                                            otherIndex !==
                                                                                index &&
                                                                            String(
                                                                                otherItem.petugas_id,
                                                                            ) ===
                                                                                String(
                                                                                    p.id,
                                                                                ),
                                                                    );

                                                                const jenisPetugasLabel =
                                                                    p.jenis_petugas ===
                                                                    'organik'
                                                                        ? 'Organik'
                                                                        : 'Non-Organik';
                                                                const jabatanLabel =
                                                                    p.jabatan ||
                                                                    '-';

                                                                return {
                                                                    value: String(
                                                                        p.id,
                                                                    ),
                                                                    label: `${p.nama} - ${jenisPetugasLabel} - ${jabatanLabel}`,
                                                                    displayLabel:
                                                                        p.nama,
                                                                    disabled:
                                                                        isSelectedInOtherRow,
                                                                };
                                                            },
                                                        );
                                                    })()}
                                                    value={item.petugas_id}
                                                    onValueChange={(value) =>
                                                        updateAlokasiItem(
                                                            index,
                                                            'petugas_id',
                                                            value,
                                                        )
                                                    }
                                                    placeholder="Pilih Petugas"
                                                    searchPlaceholder="Cari petugas..."
                                                    disabled={
                                                        isRevisiLockedMode ||
                                                        isViewMode
                                                    }
                                                />
                                            </div>

                                            {/* Peran */}
                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor={`peran_${index}`}
                                                >
                                                    Peran{' '}
                                                    <span className="text-red-500">
                                                        *
                                                    </span>
                                                </Label>
                                                <Select
                                                    value={String(
                                                        item.peran || '',
                                                    )}
                                                    onValueChange={(value) =>
                                                        updateAlokasiItem(
                                                            index,
                                                            'peran',
                                                            value,
                                                        )
                                                    }
                                                    disabled={
                                                        isRevisiLockedMode ||
                                                        isViewMode
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Pilih Peran" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {(() => {
                                                            const selectedPetugas =
                                                                petugas.find(
                                                                    (p) =>
                                                                        String(
                                                                            p.id,
                                                                        ) ===
                                                                        String(
                                                                            item.petugas_id,
                                                                        ),
                                                                );

                                                            // Mapping ke label
                                                            const labelMap: Record<
                                                                string,
                                                                string
                                                            > = {
                                                                pcl_ppl: 'PCL',
                                                                pml: 'PML',
                                                                pengolahan:
                                                                    'Petugas Pengolahan',
                                                                pengawas_pengolahan:
                                                                    'Pengawas Pengolahan',
                                                                koseka: 'Koseka',
                                                            };

                                                            const statusKepegawaian =
                                                                selectedPetugas
                                                                    ? selectedPetugas.jenis_petugas ===
                                                                      'organik'
                                                                        ? 'organik'
                                                                        : 'non_organik'
                                                                    : null;

                                                            const uniqueJenisPenugasan =
                                                                !selectedKegiatan ||
                                                                !statusKepegawaian
                                                                    ? []
                                                                    : Array.from(
                                                                          new Set(
                                                                              selectedKegiatan.rate_honors
                                                                                  .filter(
                                                                                      (
                                                                                          rh,
                                                                                      ) =>
                                                                                          rh.status_kepegawaian ===
                                                                                          statusKepegawaian,
                                                                                  )
                                                                                  .map(
                                                                                      (
                                                                                          rh,
                                                                                      ) =>
                                                                                          rh.jenis_penugasan,
                                                                                  ),
                                                                          ),
                                                                      );

                                                            const options =
                                                                uniqueJenisPenugasan.map(
                                                                    (jp) => ({
                                                                        key: jp,
                                                                        value:
                                                                            labelMap[
                                                                                jp
                                                                            ] ||
                                                                            jp,
                                                                        label:
                                                                            labelMap[
                                                                                jp
                                                                            ] ||
                                                                            jp,
                                                                    }),
                                                                );

                                                            // Fallback for copy/revisi rows: keep existing peran selectable
                                                            // even when no active rate option is available for selected petugas.
                                                            if (
                                                                item.peran &&
                                                                !options.some(
                                                                    (opt) =>
                                                                        opt.value ===
                                                                        item.peran,
                                                                )
                                                            ) {
                                                                options.unshift(
                                                                    {
                                                                        key: `fallback-${item.peran}`,
                                                                        value: item.peran,
                                                                        label: item.peran,
                                                                    },
                                                                );
                                                            }

                                                            return options.map(
                                                                (opt) => (
                                                                    <SelectItem
                                                                        key={
                                                                            opt.key
                                                                        }
                                                                        value={
                                                                            opt.value
                                                                        }
                                                                    >
                                                                        {
                                                                            opt.label
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            );
                                                        })()}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            {/* Dual-phase: Listing fields */}
                                            {selectedKegiatan?.has_listing_updating &&
                                                (tahapan === 'both' ||
                                                    tahapan ===
                                                        'listing_only') && (
                                                    <>
                                                        <div className="space-y-2">
                                                            <Label
                                                                htmlFor={`satuan_listing_${index}`}
                                                            >
                                                                Jumlah Beban
                                                                Tugas Listing{' '}
                                                                <span className="text-red-500">
                                                                    *
                                                                </span>
                                                            </Label>
                                                            <Input
                                                                type="number"
                                                                id={`satuan_listing_${index}`}
                                                                value={
                                                                    item.jumlah_satuan_listing ||
                                                                    ''
                                                                }
                                                                onChange={(e) =>
                                                                    updateAlokasiItem(
                                                                        index,
                                                                        'jumlah_satuan_listing',
                                                                        e.target
                                                                            .value,
                                                                    )
                                                                }
                                                                min="0"
                                                                placeholder="0"
                                                                disabled={
                                                                    isViewMode
                                                                }
                                                            />
                                                        </div>
                                                        <div className="space-y-2 md:col-span-4">
                                                            <Label
                                                                htmlFor={`estimasi_listing_${index}`}
                                                            >
                                                                Estimasi Honor
                                                                Listing
                                                            </Label>
                                                            <Input
                                                                type="text"
                                                                id={`estimasi_listing_${index}`}
                                                                value={formatCurrency(
                                                                    item.estimasi_honor_listing ||
                                                                        0,
                                                                )}
                                                                readOnly
                                                                className="bg-neutral-50 dark:bg-neutral-900"
                                                            />
                                                        </div>

                                                        {/* Pembayaran Parsial Listing */}
                                                        {shouldShowPartialPaymentToggle(
                                                            item,
                                                            index,
                                                        ) && (
                                                            <>
                                                                <div className="space-y-2 md:col-span-4">
                                                                    <div className="flex items-center justify-between">
                                                                        <Label
                                                                            htmlFor={`partial_payment_listing_${index}`}
                                                                        >
                                                                            Pembayaran
                                                                            Parsial
                                                                            Listing?
                                                                        </Label>
                                                                        <Switch
                                                                            id={`partial_payment_listing_${index}`}
                                                                            checked={
                                                                                item.is_partial_payment_listing ||
                                                                                false
                                                                            }
                                                                            onCheckedChange={(
                                                                                checked: boolean,
                                                                            ) => {
                                                                                updateAlokasiItem(
                                                                                    index,
                                                                                    'is_partial_payment_listing',
                                                                                    checked,
                                                                                );
                                                                            }}
                                                                            disabled={
                                                                                isViewMode
                                                                            }
                                                                        />
                                                                    </div>
                                                                    <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                        Aktifkan
                                                                        jika
                                                                        honor
                                                                        listing
                                                                        yang
                                                                        dibayarkan
                                                                        berbeda
                                                                        dari
                                                                        estimasi
                                                                    </p>
                                                                </div>

                                                                {item.is_partial_payment_listing && (
                                                                    <>
                                                                        <div className="space-y-2 md:col-span-4">
                                                                            <Label
                                                                                htmlFor={`partial_jumlah_satuan_listing_value_${index}`}
                                                                            >
                                                                                Jumlah
                                                                                Beban
                                                                                Tugas
                                                                                Listing
                                                                                Parsial{' '}
                                                                                <span className="text-red-500">
                                                                                    *
                                                                                </span>
                                                                            </Label>
                                                                            <Input
                                                                                type="number"
                                                                                id={`partial_jumlah_satuan_listing_value_${index}`}
                                                                                value={
                                                                                    item.partial_jumlah_satuan_listing ||
                                                                                    ''
                                                                                }
                                                                                onChange={(
                                                                                    e,
                                                                                ) =>
                                                                                    updateAlokasiItem(
                                                                                        index,
                                                                                        'partial_jumlah_satuan_listing',
                                                                                        e
                                                                                            .target
                                                                                            .value,
                                                                                    )
                                                                                }
                                                                                min="0"
                                                                                max={
                                                                                    item.jumlah_satuan_listing ||
                                                                                    undefined
                                                                                }
                                                                                placeholder="0"
                                                                                disabled={
                                                                                    isViewMode
                                                                                }
                                                                            />
                                                                            <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                                Maksimal:{' '}
                                                                                {item.jumlah_satuan_listing ||
                                                                                    0}{' '}
                                                                                (jumlah
                                                                                beban
                                                                                tugas
                                                                                listing
                                                                                awal)
                                                                            </p>
                                                                        </div>

                                                                        <div className="space-y-2 md:col-span-4">
                                                                            <Label
                                                                                htmlFor={`estimasi_honor_partial_listing_${index}`}
                                                                            >
                                                                                Estimasi
                                                                                Honor
                                                                                Listing
                                                                                Parsial
                                                                            </Label>
                                                                            <Input
                                                                                type="text"
                                                                                id={`estimasi_honor_partial_listing_${index}`}
                                                                                value={formatCurrency(
                                                                                    item.estimasi_honor_partial_listing ||
                                                                                        0,
                                                                                )}
                                                                                readOnly
                                                                                className="bg-neutral-50 dark:bg-neutral-900"
                                                                            />
                                                                            <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                                Dihitung
                                                                                otomatis
                                                                                berdasarkan
                                                                                jumlah
                                                                                beban
                                                                                tugas
                                                                                listing
                                                                                parsial
                                                                            </p>
                                                                        </div>
                                                                    </>
                                                                )}
                                                            </>
                                                        )}
                                                    </>
                                                )}

                                            {/* Jumlah Beban Tugas Pencacahan */}
                                            {(!selectedKegiatan?.has_listing_updating ||
                                                tahapan === 'both' ||
                                                tahapan ===
                                                    'pencacahan_only') && (
                                                <>
                                                    <div className="space-y-2">
                                                        <Label
                                                            htmlFor={`satuan_${index}`}
                                                        >
                                                            Jumlah Beban Tugas
                                                            {selectedKegiatan?.has_listing_updating
                                                                ? ' Pencacahan'
                                                                : ''}{' '}
                                                            <span className="text-red-500">
                                                                *
                                                            </span>
                                                        </Label>
                                                        <Input
                                                            type="number"
                                                            id={`satuan_${index}`}
                                                            value={
                                                                jenisKegiatan ===
                                                                'sensus'
                                                                    ? '1'
                                                                    : item.jumlah_satuan
                                                            }
                                                            onChange={(e) =>
                                                                updateAlokasiItem(
                                                                    index,
                                                                    'jumlah_satuan',
                                                                    e.target
                                                                        .value,
                                                                )
                                                            }
                                                            min="0"
                                                            placeholder="0"
                                                            disabled={
                                                                isViewMode ||
                                                                jenisKegiatan ===
                                                                    'sensus'
                                                            }
                                                            className={
                                                                jenisKegiatan ===
                                                                'sensus'
                                                                    ? 'cursor-not-allowed bg-neutral-100 dark:bg-neutral-900'
                                                                    : ''
                                                            }
                                                        />
                                                        {jenisKegiatan ===
                                                            'sensus' && (
                                                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                🔒 Beban tugas
                                                                sensus otomatis
                                                                1 (Orang/Bulan)
                                                            </p>
                                                        )}
                                                    </div>

                                                    {/* Estimasi Honor Pencacahan (Read only) */}
                                                    <div className="space-y-2 md:col-span-4">
                                                        <Label
                                                            htmlFor={`estimasi_${index}`}
                                                        >
                                                            Estimasi Honor
                                                            {selectedKegiatan?.has_listing_updating
                                                                ? ' Pencacahan'
                                                                : ''}
                                                        </Label>
                                                        <Input
                                                            type="text"
                                                            id={`estimasi_${index}`}
                                                            value={formatCurrency(
                                                                item.estimasi_honor,
                                                            )}
                                                            readOnly
                                                            className="bg-neutral-50 dark:bg-neutral-900"
                                                        />
                                                    </div>

                                                    {/* Pembayaran Parsial Pencacahan */}
                                                    {shouldShowPartialPaymentToggle(
                                                        item,
                                                        index,
                                                    ) && (
                                                        <>
                                                            <div className="space-y-2 md:col-span-4">
                                                                <div className="flex items-center justify-between">
                                                                    <Label
                                                                        htmlFor={`partial_payment_${index}`}
                                                                    >
                                                                        Pembayaran
                                                                        Parsial?
                                                                    </Label>
                                                                    <Switch
                                                                        id={`partial_payment_${index}`}
                                                                        checked={
                                                                            item.is_partial_payment ||
                                                                            false
                                                                        }
                                                                        onCheckedChange={(
                                                                            checked: boolean,
                                                                        ) => {
                                                                            updateAlokasiItem(
                                                                                index,
                                                                                'is_partial_payment',
                                                                                checked,
                                                                            );
                                                                        }}
                                                                        disabled={
                                                                            isViewMode
                                                                        }
                                                                    />
                                                                </div>
                                                                <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                    Aktifkan
                                                                    jika honor
                                                                    yang
                                                                    dibayarkan
                                                                    berbeda dari
                                                                    estimasi
                                                                </p>
                                                            </div>

                                                            {item.is_partial_payment && (
                                                                <>
                                                                    <div className="space-y-2 md:col-span-4">
                                                                        <Label
                                                                            htmlFor={`partial_jumlah_satuan_value_${index}`}
                                                                        >
                                                                            Jumlah
                                                                            Beban
                                                                            Tugas
                                                                            Parsial{' '}
                                                                            <span className="text-red-500">
                                                                                *
                                                                            </span>
                                                                        </Label>
                                                                        <Input
                                                                            type="number"
                                                                            id={`partial_jumlah_satuan_value_${index}`}
                                                                            value={
                                                                                item.partial_jumlah_satuan ||
                                                                                ''
                                                                            }
                                                                            onChange={(
                                                                                e,
                                                                            ) =>
                                                                                updateAlokasiItem(
                                                                                    index,
                                                                                    'partial_jumlah_satuan',
                                                                                    e
                                                                                        .target
                                                                                        .value,
                                                                                )
                                                                            }
                                                                            min="0"
                                                                            max={
                                                                                item.jumlah_satuan ||
                                                                                undefined
                                                                            }
                                                                            placeholder="0"
                                                                            disabled={
                                                                                isViewMode
                                                                            }
                                                                        />
                                                                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                            Maksimal:{' '}
                                                                            {item.jumlah_satuan ||
                                                                                0}{' '}
                                                                            (jumlah
                                                                            beban
                                                                            tugas
                                                                            awal)
                                                                        </p>
                                                                    </div>

                                                                    <div className="space-y-2 md:col-span-4">
                                                                        <Label
                                                                            htmlFor={`estimasi_honor_partial_${index}`}
                                                                        >
                                                                            Estimasi
                                                                            Honor
                                                                            Parsial
                                                                        </Label>
                                                                        <Input
                                                                            type="text"
                                                                            id={`estimasi_honor_partial_${index}`}
                                                                            value={formatCurrency(
                                                                                item.estimasi_honor_partial ||
                                                                                    0,
                                                                            )}
                                                                            readOnly
                                                                            className="bg-neutral-50 dark:bg-neutral-900"
                                                                        />
                                                                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                                                            Dihitung
                                                                            otomatis
                                                                            berdasarkan
                                                                            jumlah
                                                                            beban
                                                                            tugas
                                                                            parsial
                                                                        </p>
                                                                    </div>
                                                                </>
                                                            )}
                                                        </>
                                                    )}
                                                </>
                                            )}

                                            {/* Catatan */}
                                            <div className="space-y-2 md:col-span-4">
                                                <Label
                                                    htmlFor={`catatan_${index}`}
                                                >
                                                    Catatan
                                                </Label>
                                                <Input
                                                    type="text"
                                                    id={`catatan_${index}`}
                                                    value={item.catatan}
                                                    onChange={(e) =>
                                                        updateAlokasiItem(
                                                            index,
                                                            'catatan',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Catatan tambahan (opsional)"
                                                    disabled={
                                                        isRevisiMode ||
                                                        isViewMode
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>
                                ))}

                                {/* Add Petugas Button - Outside individual cards */}
                                {!isViewMode && (
                                    <div className="mt-4 flex justify-center border-t border-neutral-200 pt-4 dark:border-neutral-700">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                handleAddPetugasAfter(
                                                    alokasiItems.length - 1,
                                                )
                                            }
                                            disabled={isRevisiLockedMode}
                                            className="gap-1.5"
                                        >
                                            <Plus className="h-4 w-4" />
                                            Tambah Petugas
                                        </Button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </ContentCard>
                )}

                {/* Estimasi Sisa Pagu */}
                {selectedKegiatanId &&
                    alokasiItems.length > 0 &&
                    alokasiItems.some(
                        (item) => item.petugas_id && item.peran,
                    ) && (
                        <ContentCard data-budget-info>
                            {selectedKegiatan?.has_listing_updating ? (
                                <div className="space-y-8">
                                    {/* Listing Phase - Show if tahapan is 'both' or 'listing_only' */}
                                    {(tahapan === 'both' ||
                                        tahapan === 'listing_only') && (
                                        <div
                                            className={`rounded-2xl border p-6 shadow-xl backdrop-blur-xl ${
                                                isSufficientListing
                                                    ? 'border-blue-400/30 bg-gradient-to-br from-blue-500/10 via-blue-400/5 to-blue-300/10 dark:border-blue-400/20 dark:from-blue-500/10 dark:via-neutral-800/20 dark:to-neutral-800/10'
                                                    : 'border-red-400/30 bg-gradient-to-br from-red-500/10 via-red-400/5 to-red-300/10 dark:border-red-500/20 dark:from-red-600/10 dark:via-red-500/5 dark:to-red-400/10'
                                            }`}
                                        >
                                            <div className="flex items-start gap-4">
                                                <div
                                                    className={`flex-shrink-0 rounded-full p-3 ${isSufficientListing ? 'bg-green-500' : 'bg-red-500'}`}
                                                >
                                                    {isSufficientListing ? (
                                                        <svg
                                                            className="h-6 w-6 text-white"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth={2}
                                                                d="M5 13l4 4L19 7"
                                                            />
                                                        </svg>
                                                    ) : (
                                                        <svg
                                                            className="h-6 w-6 text-white"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth={2}
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                                            />
                                                        </svg>
                                                    )}
                                                </div>
                                                <div className="flex-1">
                                                    <h3
                                                        className={`text-lg font-bold ${isSufficientListing ? 'text-blue-900 dark:text-blue-300' : 'text-red-900 dark:text-red-300'}`}
                                                    >
                                                        {isSufficientListing
                                                            ? 'Pagu Listing Mencukupi'
                                                            : 'Pagu Listing Tidak Mencukupi'}
                                                    </h3>
                                                    <p
                                                        className={`mt-1 text-sm ${isSufficientListing ? 'text-blue-700 dark:text-blue-400' : 'text-red-700 dark:text-red-400'}`}
                                                    >
                                                        Untuk {jumlahPetugas}{' '}
                                                        petugas (Listing)
                                                    </p>
                                                    <div className="mt-4 space-y-2.5">
                                                        <div
                                                            className={`flex justify-between text-sm ${isSufficientListing ? 'text-blue-800 dark:text-blue-300' : 'text-red-800 dark:text-red-300'}`}
                                                        >
                                                            <span className="font-medium">
                                                                Pagu Listing:
                                                            </span>
                                                            <span className="font-semibold">
                                                                {formatCurrency(
                                                                    pagu_listing,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div
                                                            className={`flex justify-between text-sm ${isSufficientListing ? 'text-blue-800 dark:text-blue-300' : 'text-red-800 dark:text-red-300'}`}
                                                        >
                                                            <span className="font-medium">
                                                                Total Terpakai
                                                                (Semua Periode):
                                                            </span>
                                                            <span className="font-semibold">
                                                                {formatCurrency(
                                                                    totalTerpakaiListing,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div
                                                            className={`flex justify-between text-sm ${isSufficientListing ? 'text-blue-800 dark:text-blue-300' : 'text-red-800 dark:text-red-300'}`}
                                                        >
                                                            <span className="font-medium">
                                                                Estimasi Periode
                                                                Ini (Listing):
                                                            </span>
                                                            <span className="font-semibold">
                                                                {formatCurrency(
                                                                    totalEstimasiListing,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div
                                                            className={`flex justify-between border-t pt-2.5 text-base ${isSufficientListing ? 'border-blue-400 dark:border-blue-700' : 'border-red-400 dark:border-red-700'}`}
                                                        >
                                                            <span
                                                                className={`font-bold ${isSufficientListing ? 'text-blue-900 dark:text-blue-200' : 'text-red-900 dark:text-red-200'}`}
                                                            >
                                                                Estimasi Sisa
                                                                Pagu Listing:
                                                            </span>
                                                            <span
                                                                className={`text-xl font-bold ${isSufficientListing ? 'text-blue-900 dark:text-blue-200' : 'text-red-900 dark:text-red-200'}`}
                                                            >
                                                                {formatCurrency(
                                                                    sisaPaguListing,
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    {!isSufficientListing && (
                                                        <div className="mt-4 rounded-md border border-red-300 bg-red-100 p-3 dark:border-red-800 dark:bg-red-950/40">
                                                            <p className="text-sm font-medium text-red-900 dark:text-red-300">
                                                                ⚠️ Estimasi
                                                                total honor
                                                                listing melebihi
                                                                pagu listing
                                                                yang tersisa.
                                                                Silakan periksa
                                                                lagi isian atau
                                                                ubah pagu
                                                                listing melalui
                                                                Fitur Revisi di
                                                                halaman
                                                                Kegiatan.
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                    {/* Pencacahan Phase - Show if tahapan is 'both' or 'pencacahan_only' */}
                                    {(tahapan === 'both' ||
                                        tahapan === 'pencacahan_only') && (
                                        <div
                                            className={`rounded-2xl border p-6 shadow-xl backdrop-blur-xl ${
                                                isSufficientPencacahan
                                                    ? 'border-green-400/30 bg-gradient-to-br from-green-500/10 via-green-400/5 to-green-300/10 dark:border-green-500/20 dark:from-green-600/10 dark:via-green-500/5 dark:to-green-400/10'
                                                    : 'border-red-400/30 bg-gradient-to-br from-red-500/10 via-red-400/5 to-red-300/10 dark:border-red-500/20 dark:from-red-600/10 dark:via-red-500/5 dark:to-red-400/10'
                                            }`}
                                        >
                                            <div className="flex items-start gap-4">
                                                <div
                                                    className={`flex-shrink-0 rounded-full p-3 ${isSufficientPencacahan ? 'bg-green-500' : 'bg-red-500'}`}
                                                >
                                                    {isSufficientPencacahan ? (
                                                        <svg
                                                            className="h-6 w-6 text-white"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth={2}
                                                                d="M5 13l4 4L19 7"
                                                            />
                                                        </svg>
                                                    ) : (
                                                        <svg
                                                            className="h-6 w-6 text-white"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                        >
                                                            <path
                                                                strokeLinecap="round"
                                                                strokeLinejoin="round"
                                                                strokeWidth={2}
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                                            />
                                                        </svg>
                                                    )}
                                                </div>
                                                <div className="flex-1">
                                                    <h3
                                                        className={`text-lg font-bold ${isSufficientPencacahan ? 'text-green-900 dark:text-green-300' : 'text-red-900 dark:text-red-300'}`}
                                                    >
                                                        {isSufficientPencacahan
                                                            ? 'Pagu Pencacahan Mencukupi'
                                                            : 'Pagu Pencacahan Tidak Mencukupi'}
                                                    </h3>
                                                    <p
                                                        className={`mt-1 text-sm ${isSufficientPencacahan ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'}`}
                                                    >
                                                        Untuk {jumlahPetugas}{' '}
                                                        petugas (Pencacahan)
                                                    </p>
                                                    <div className="mt-4 space-y-2.5">
                                                        <div
                                                            className={`flex justify-between text-sm ${isSufficientPencacahan ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}
                                                        >
                                                            <span className="font-medium">
                                                                Pagu Pencacahan:
                                                            </span>
                                                            <span className="font-semibold">
                                                                {formatCurrency(
                                                                    pagu_pencacahan,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div
                                                            className={`flex justify-between text-sm ${isSufficientPencacahan ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}
                                                        >
                                                            <span className="font-medium">
                                                                Total Terpakai
                                                                (Semua Periode):
                                                            </span>
                                                            <span className="font-semibold">
                                                                {formatCurrency(
                                                                    totalTerpakaiPencacahan,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div
                                                            className={`flex justify-between text-sm ${isSufficientPencacahan ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}
                                                        >
                                                            <span className="font-medium">
                                                                Estimasi Periode
                                                                Ini
                                                                (Pencacahan):
                                                            </span>
                                                            <span className="font-semibold">
                                                                {formatCurrency(
                                                                    totalEstimasiPencacahan,
                                                                )}
                                                            </span>
                                                        </div>
                                                        <div
                                                            className={`flex justify-between border-t pt-2.5 text-base ${isSufficientPencacahan ? 'border-green-400 dark:border-green-700' : 'border-red-400 dark:border-red-700'}`}
                                                        >
                                                            <span
                                                                className={`font-bold ${isSufficientPencacahan ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'}`}
                                                            >
                                                                Estimasi Sisa
                                                                Pagu Pencacahan:
                                                            </span>
                                                            <span
                                                                className={`text-xl font-bold ${isSufficientPencacahan ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'}`}
                                                            >
                                                                {formatCurrency(
                                                                    sisaPaguPencacahan,
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    {!isSufficientPencacahan && (
                                                        <div className="mt-4 rounded-md border border-red-300 bg-red-100 p-3 dark:border-red-800 dark:bg-red-950/40">
                                                            <p className="text-sm font-medium text-red-900 dark:text-red-300">
                                                                ⚠️ Estimasi
                                                                total honor
                                                                pencacahan
                                                                melebihi pagu
                                                                pencacahan yang
                                                                tersisa. Silakan
                                                                periksa lagi
                                                                isian atau ubah
                                                                pagu pencacahan
                                                                melalui Fitur
                                                                Revisi di
                                                                halaman
                                                                Kegiatan.
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                // Single phase (pencacahan only)
                                <div
                                    className={`rounded-lg border-2 p-6 ${isSufficientPencacahan ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : 'border-red-500 bg-red-50 dark:bg-red-950/20'}`}
                                >
                                    <div className="flex items-start gap-4">
                                        <div
                                            className={`flex-shrink-0 rounded-full p-3 ${isSufficientPencacahan ? 'bg-green-500' : 'bg-red-500'}`}
                                        >
                                            {isSufficientPencacahan ? (
                                                <svg
                                                    className="h-6 w-6 text-white"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth={2}
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                            ) : (
                                                <svg
                                                    className="h-6 w-6 text-white"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                        strokeWidth={2}
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                                    />
                                                </svg>
                                            )}
                                        </div>
                                        <div className="flex-1">
                                            <h3
                                                className={`text-lg font-bold ${isSufficientPencacahan ? 'text-green-900 dark:text-green-300' : 'text-red-900 dark:text-red-300'}`}
                                            >
                                                {isSufficientPencacahan
                                                    ? 'Pagu Anggaran Mencukupi'
                                                    : 'Pagu Anggaran Tidak Mencukupi'}
                                            </h3>
                                            <p
                                                className={`mt-1 text-sm ${isSufficientPencacahan ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'}`}
                                            >
                                                Untuk {jumlahPetugas} petugas
                                            </p>
                                            <div className="mt-4 space-y-2.5">
                                                <div
                                                    className={`flex justify-between text-sm ${isSufficientPencacahan ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}
                                                >
                                                    <span className="font-medium">
                                                        Pagu Pencacahan:
                                                    </span>
                                                    <span className="font-semibold">
                                                        {formatCurrency(
                                                            pagu_pencacahan,
                                                        )}
                                                    </span>
                                                </div>
                                                <div
                                                    className={`flex justify-between text-sm ${isSufficientPencacahan ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}
                                                >
                                                    <span className="font-medium">
                                                        Total Terpakai (Periode
                                                        Lain):
                                                    </span>
                                                    <span className="font-semibold">
                                                        {formatCurrency(
                                                            current_total_spent,
                                                        )}
                                                    </span>
                                                </div>
                                                <div
                                                    className={`flex justify-between text-sm ${isSufficientPencacahan ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}
                                                >
                                                    <span className="font-medium">
                                                        Estimasi Periode Ini:
                                                    </span>
                                                    <span className="font-semibold">
                                                        {formatCurrency(
                                                            totalEstimasiPencacahan,
                                                        )}
                                                    </span>
                                                </div>
                                                <div
                                                    className={`flex justify-between border-t pt-2.5 text-base ${isSufficientPencacahan ? 'border-green-400 dark:border-green-700' : 'border-red-400 dark:border-red-700'}`}
                                                >
                                                    <span
                                                        className={`font-bold ${isSufficientPencacahan ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'}`}
                                                    >
                                                        Estimasi Sisa Pagu:
                                                    </span>
                                                    <span
                                                        className={`text-xl font-bold ${isSufficientPencacahan ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'}`}
                                                    >
                                                        {formatCurrency(
                                                            sisaPaguPencacahan,
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                            {!isSufficientPencacahan && (
                                                <div className="mt-4 rounded-md border border-red-300 bg-red-100 p-3 dark:border-red-800 dark:bg-red-950/40">
                                                    <p className="text-sm font-medium text-red-900 dark:text-red-300">
                                                        ⚠️ Estimasi total honor
                                                        melebihi pagu pencacahan
                                                        yang tersisa. Silakan
                                                        periksa lagi isian atau
                                                        ubah pagu pencacahan
                                                        melalui Fitur Revisi di
                                                        halaman Kegiatan.
                                                    </p>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </ContentCard>
                    )}

                {/* Footer Buttons */}
                {!isViewMode && (
                    <div className="flex items-center justify-end gap-3">
                        <Button
                            variant="outline"
                            type="button"
                            asChild
                            className="gap-2"
                            disabled={processing}
                        >
                            <Link href="/alokasi">
                                <X className="h-5 w-5" />
                                Batal
                            </Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || !selectedKegiatanId}
                            className="min-w-[180px] gap-2"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                    {isRevisiMode
                                        ? 'Mengirim revisi...'
                                        : 'Menyimpan...'}
                                </>
                            ) : (
                                <>
                                    {isRevisiMode ? (
                                        <Send className="h-5 w-5" />
                                    ) : (
                                        <Save className="h-5 w-5" />
                                    )}
                                    {isRevisiMode
                                        ? 'Kirim Revisi'
                                        : 'Simpan Alokasi'}
                                </>
                            )}
                        </Button>
                    </div>
                )}
            </form>
        </AppLayout>
    );
}
