import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Copy } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Alokasi petugas', href: '/alokasi' },
    { title: 'Tambah Alokasi', href: '/alokasi/create' },
];

interface Kegiatan {
    id: string;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    deskripsi?: string | null;
    jenis_kegiatan: 'sensus' | 'survei';
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
}

interface AlokasiItem {
    petugas_id: string;
    peran: string;
    jumlah_satuan: string;
    estimasi_honor: number;
    jumlah_satuan_listing?: string;
    estimasi_honor_listing?: number;
    catatan: string;
}

interface AlokasiCreateProps {
    kegiatans: Kegiatan[];
    petugas: Petugas[];
    selectedKegiatan?: Kegiatan | null;
    active_year: number;
    copiedAlokasi?: any[] | null;
    sourcePeriode?: { 
        bulan: string; 
        tahun: number; 
        tahapan?: 'both' | 'listing_only' | 'pencacahan_only';
        tanggal_mulai?: string | null;
        tanggal_selesai?: string | null;
        tanggal_mulai_listing?: string | null;
        tanggal_selesai_listing?: string | null;
    } | null;
    budget_info: Record<
        number,
        { pagu_pencacahan: number; current_total_spent: number }
    >;
    used_months_info: Record<number, number[]>;
    isEditMode?: boolean;
    isRevisiMode?: boolean;
    isViewMode?: boolean;
}

export default function Create({
    kegiatans,
    petugas,
    selectedKegiatan: preSelectedKegiatan,
    active_year,
    copiedAlokasi,
    sourcePeriode,
    budget_info,
    used_months_info,
    isEditMode = false,
    isRevisiMode = false,
    isViewMode = false,
}: AlokasiCreateProps) {
    // Debug: Log petugas data
    console.log('🔍 Petugas data in Create:', petugas);
    
    const { auth } = usePage<SharedData>().props;
    const [selectedKegiatanId, setSelectedKegiatanId] = useState(
        preSelectedKegiatan?.id || '',
    );

    // Helper function to find first available month
    const getFirstAvailableMonth = (
        usedMonthsList: number[],
        startMonth: number = 1,
    ): number => {
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
        
        // Get used months for the selected kegiatan
        const kegiatanId = preSelectedKegiatan?.id;
        const usedMonthsList = kegiatanId ? (used_months_info[Number(kegiatanId)] || []) : [];
        
        if (sourcePeriode) {
            // Copy mode: find first available month starting from source month + 1
            const nextMonth = (parseInt(sourcePeriode.bulan) % 12) + 1;
            return getFirstAvailableMonth(usedMonthsList, nextMonth);
        }
        
        // New mode: find first available month starting from month 1
        return getFirstAvailableMonth(usedMonthsList, 1);
    });
    const [tahapan, setTahapan] = useState<'both' | 'listing_only' | 'pencacahan_only'>(() => {
        if (sourcePeriode?.tahapan) {
            return sourcePeriode.tahapan as 'both' | 'listing_only' | 'pencacahan_only';
        }
        return 'both';
    });
    const [jenisKegiatan, setJenisKegiatan] = useState<'sensus' | 'survei'>(
        preSelectedKegiatan?.jenis_kegiatan || 'survei',
    );
    // Store original values from copied/edited alokasi for restoration
    const [originalAlokasiValues, setOriginalAlokasiValues] = useState<Array<{
        jumlah_satuan: string;
        jumlah_satuan_listing: string;
        estimasi_honor: number;
        estimasi_honor_listing: number;
    }>>([]);
    // Tidak perlu showPengolahan, dropdown peran akan dinamis dari rate_honors
    const [jumlahPetugas, setJumlahPetugas] = useState(
        isEditMode && copiedAlokasi ? copiedAlokasi.length : 1,
    );
    const [alokasiItems, setAlokasiItems] = useState<AlokasiItem[]>([
        {
            petugas_id: '',
            peran: '',
            jumlah_satuan: '',
            estimasi_honor: 0,
            catatan: '',
        },
    ]);
    // Jadwal Kegiatan states
    const [tanggalMulai, setTanggalMulai] = useState(sourcePeriode?.tanggal_mulai || '');
    const [tanggalSelesai, setTanggalSelesai] = useState(sourcePeriode?.tanggal_selesai || '');
    const [tanggalMulaiListing, setTanggalMulaiListing] = useState(sourcePeriode?.tanggal_mulai_listing || '');
    const [tanggalSelesaiListing, setTanggalSelesaiListing] = useState(sourcePeriode?.tanggal_selesai_listing || '');
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<any>({});

    // Filter kegiatan based on ketua_tim role
    const filteredKegiatans = useMemo(() => {
        if (auth.activeRole?.name === 'ketua_tim') {
            return kegiatans.filter(
                (k) => k.ketua_tim_user_id === auth.user.id,
            );
        }
        return kegiatans;
    }, [kegiatans, auth.activeRole, auth.user.id]);

    const selectedKegiatan = filteredKegiatans.find(
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
    const pagu_pencacahan = currentBudget.pagu_pencacahan;
    const current_total_spent = currentBudget.current_total_spent;
    const pagu_listing =
        'pagu_listing' in currentBudget
            ? (currentBudget as any).pagu_listing
            : selectedKegiatan?.pagu_listing || 0;
    const current_total_spent_listing =
        'current_total_spent_listing' in currentBudget
            ? (currentBudget as any).current_total_spent_listing
            : 0;

    // Get used months for selected kegiatan
    const usedMonths =
        selectedKegiatan && selectedKegiatanId
            ? used_months_info[Number(selectedKegiatan.id)] || []
            : [];

    // Initialize with copied data if available
    useEffect(() => {
        if (copiedAlokasi && copiedAlokasi.length > 0) {
            console.log('🔍 copiedAlokasi data:', copiedAlokasi);
            
            // Store original values first for restoration
            const originalValues = copiedAlokasi.map((alokasi) => ({
                jumlah_satuan: String(alokasi.jumlah_satuan || 0),
                jumlah_satuan_listing: String(alokasi.jumlah_satuan_listing || 0),
                estimasi_honor: parseFloat(alokasi.total_honor) || 0,
                estimasi_honor_listing: parseFloat(alokasi.total_honor_listing) || 0,
            }));
            setOriginalAlokasiValues(originalValues);
            
            const initialItems = copiedAlokasi.map((alokasi) => {
                // Map backend peran format to frontend display format
                let peranDisplay = '';
                const peranLower = (alokasi.peran || '').toLowerCase();
                
                console.log(`🔍 Mapping peran: "${alokasi.peran}" (lowercase: "${peranLower}")`);
                
                if (peranLower === 'pcl_ppl' || peranLower === 'pcl') {
                    peranDisplay = 'PCL';
                } else if (peranLower === 'pml') {
                    peranDisplay = 'PML';
                } else if (peranLower === 'pengolahan' || peranLower === 'petugas pengolahan') {
                    peranDisplay = 'Petugas Pengolahan';
                } else if (peranLower === 'pengawas_pengolahan' || peranLower === 'pengawas pengolahan') {
                    peranDisplay = 'Pengawas Pengolahan';
                } else if (alokasi.peran) {
                    // If peran exists but doesn't match any known format, keep it as is
                    peranDisplay = alokasi.peran;
                }
                
                console.log(`✅ Mapped to: "${peranDisplay}"`);
                
                // Ensure numeric values are properly parsed
                const estimasiHonor = parseFloat(alokasi.total_honor) || 0;
                const estimasiHonorListing = parseFloat(alokasi.total_honor_listing) || 0;
                
                return {
                    petugas_id: String(alokasi.petugas_id || ''),
                    peran: peranDisplay,
                    jumlah_satuan: String(alokasi.jumlah_satuan || 0),
                    jumlah_satuan_listing: String(alokasi.jumlah_satuan_listing || 0),
                    estimasi_honor: estimasiHonor,
                    estimasi_honor_listing: estimasiHonorListing,
                    catatan: alokasi.catatan || '',
                };
            });
            
            console.log('✅ Final initialItems:', initialItems);
            console.log('📝 [1] Setting alokasiItems from copiedAlokasi');
            setAlokasiItems(initialItems);
            setJumlahPetugas(initialItems.length);
            
            // Auto-scroll to budget info if any estimasi honor exists
            const hasEstimasi = initialItems.some(item => item.estimasi_honor > 0 || (item.estimasi_honor_listing && item.estimasi_honor_listing > 0));
            if (hasEstimasi) {
                // Delay scroll to ensure DOM is rendered
                setTimeout(() => {
                    const budgetSection = document.querySelector('[data-budget-info]');
                    if (budgetSection) {
                        budgetSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 500);
            }
        }
    }, [copiedAlokasi]);

    // Set jenisKegiatan from selectedKegiatan and recalculate estimasi
    useEffect(() => {
        console.log('🔄 Recalculate useEffect triggered', {
            hasSelectedKegiatan: !!selectedKegiatan,
            selectedKegiatanId
        });
        
        if (!selectedKegiatan) return;

        // Update jenis kegiatan from selected kegiatan
        const newJenisKegiatan = selectedKegiatan.jenis_kegiatan;
        setJenisKegiatan(newJenisKegiatan);

        // Recalculate estimasi for all items using the calculateEstimasi function
        setAlokasiItems((prevItems) => {
            console.log('🔄 Recalculating with prevItems:', prevItems);
            
            // If prevItems is empty or all items have empty petugas_id, don't process
            if (prevItems.length === 0 || prevItems.every(item => !item.petugas_id)) {
                console.log('⚠️ Skipping recalculation - no valid items');
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
                if (item.petugas_id && item.peran && item.jumlah_satuan_listing && selectedKegiatan.has_listing_updating) {
                    const newEstimasiListing = calculateEstimasiListing(
                        item.petugas_id,
                        item.peran,
                        item.jumlah_satuan_listing,
                    );
                    updates.estimasi_honor_listing = newEstimasiListing;
                }
                
                return { ...item, ...updates };
            });
            
            console.log('✅ Recalculated newItems:', newItems);
            console.log('📝 [2] Setting alokasiItems from recalculate useEffect');
            return newItems;
        });
    }, [selectedKegiatanId, selectedKegiatan]);

    // Handle tahapan change - clear/restore values based on tahapan
    useEffect(() => {
        console.log('🔄 Tahapan useEffect triggered', { tahapan, originalAlokasiValuesLength: originalAlokasiValues.length });
        
        if (originalAlokasiValues.length === 0) return; // Wait until original values are loaded
        
        console.log('📝 [3] Setting alokasiItems from tahapan useEffect');
        setAlokasiItems((prevItems) => {
            return prevItems.map((item, index) => {
                const updates: Partial<AlokasiItem> = {};
                const originalValues = originalAlokasiValues[index];
                
                if (!originalValues) return item;
                
                if (tahapan === 'listing_only') {
                    // Clear pencacahan values, restore listing values
                    updates.jumlah_satuan = '0';
                    updates.estimasi_honor = 0;
                    updates.jumlah_satuan_listing = originalValues.jumlah_satuan_listing || '0';
                    updates.estimasi_honor_listing = originalValues.estimasi_honor_listing || 0;
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
                    updates.jumlah_satuan_listing = originalValues.jumlah_satuan_listing || '0';
                    updates.estimasi_honor_listing = originalValues.estimasi_honor_listing || 0;
                }
                
                return { ...item, ...updates };
            });
        });
    }, [tahapan, originalAlokasiValues]);

    // Calculate estimasi honor for a petugas
    // Calculate estimasi honor for a petugas (pencacahan)
    const calculateEstimasi = (
        petugasId: string,
        peran: string,
        jumlahSatuan: string,
    ) => {
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
        else if (peran === 'Petugas Pengolahan') jenisPenugasan = 'pengolahan';
        else if (peran === 'Pengawas Pengolahan')
            jenisPenugasan = 'pengawas_pengolahan';
        if (!jenisPenugasan) return 0;
        const matchingRateHonor = selectedKegiatan.rate_honors?.find(
            (r) =>
                r.status_kepegawaian === statusKepegawaian &&
                r.jenis_penugasan === jenisPenugasan,
        );
        if (!matchingRateHonor) return 0;
        return matchingRateHonor.rate * Number(jumlahSatuan);
    };

    // Calculate estimasi honor for listing phase
    const calculateEstimasiListing = (
        petugasId: string,
        peran: string,
        jumlahSatuanListing: string,
    ) => {
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
        else if (peran === 'Petugas Pengolahan') jenisPenugasan = 'pengolahan';
        else if (peran === 'Pengawas Pengolahan')
            jenisPenugasan = 'pengawas_pengolahan';
        if (!jenisPenugasan) return 0;
        const matchingRateHonor = selectedKegiatan.rate_honors?.find(
            (r) =>
                r.status_kepegawaian === statusKepegawaian &&
                r.jenis_penugasan === jenisPenugasan,
        );
        if (!matchingRateHonor || !matchingRateHonor.rate_listing) return 0;
        return matchingRateHonor.rate_listing * Number(jumlahSatuanListing);
    };

    // Handle jumlah petugas change
    const handleJumlahPetugasChange = (value: number) => {
        const newValue = Math.max(1, Math.min(50, value));
        setJumlahPetugas(newValue);

        const currentItems = [...alokasiItems];
        if (newValue > currentItems.length) {
            // Add new items - restore from copiedAlokasi if available
            for (let i = currentItems.length; i < newValue; i++) {
                // Check if we have data in copiedAlokasi for this index
                if (copiedAlokasi && copiedAlokasi[i]) {
                    const alokasi = copiedAlokasi[i];
                    
                    // Map backend peran format to frontend display format
                    let peranDisplay = '';
                    const peranLower = (alokasi.peran || '').toLowerCase();
                    
                    if (peranLower === 'pcl_ppl' || peranLower === 'pcl') {
                        peranDisplay = 'PCL';
                    } else if (peranLower === 'pml') {
                        peranDisplay = 'PML';
                    } else if (peranLower === 'pengolahan' || peranLower === 'petugas pengolahan') {
                        peranDisplay = 'Petugas Pengolahan';
                    } else if (peranLower === 'pengawas_pengolahan' || peranLower === 'pengawas pengolahan') {
                        peranDisplay = 'Pengawas Pengolahan';
                    } else if (alokasi.peran) {
                        peranDisplay = alokasi.peran;
                    }
                    
                    currentItems.push({
                        petugas_id: String(alokasi.petugas_id || ''),
                        peran: peranDisplay,
                        jumlah_satuan: String(alokasi.jumlah_satuan || 0),
                        jumlah_satuan_listing: String(alokasi.jumlah_satuan_listing || 0),
                        estimasi_honor: alokasi.total_honor || 0,
                        estimasi_honor_listing: alokasi.total_honor_listing || 0,
                        catatan: alokasi.catatan || '',
                    });
                } else {
                    // No data in copiedAlokasi, add empty item
                    currentItems.push({
                        petugas_id: '',
                        peran: '',
                        jumlah_satuan: '',
                        estimasi_honor: 0,
                        catatan: '',
                    });
                }
            }
        } else if (newValue < currentItems.length) {
            // Remove excess items
            currentItems.splice(newValue);
        }
        console.log('📝 [4] Setting alokasiItems from handleJumlahPetugasChange');
        setAlokasiItems(currentItems);
    };

    // Update alokasi item
    // Update alokasi item
    const updateAlokasiItem = (
        index: number,
        field: keyof AlokasiItem,
        value: any,
    ) => {
        // Guard: Don't update peran with empty value if item already has a peran
        if (field === 'peran' && !value && alokasiItems[index]?.peran) {
            console.log('⚠️ Prevented empty peran from overwriting existing value');
            return;
        }
        
        const newItems = [...alokasiItems];
        newItems[index] = { ...newItems[index], [field]: value };
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
        console.log('📝 [5] Setting alokasiItems from updateAlokasiItem', { index, field, value });
        setAlokasiItems(newItems);
    };

    // Calculate total estimasi for each phase
    const totalEstimasiPencacahan = alokasiItems.reduce(
        (sum, item) => sum + (item.estimasi_honor || 0),
        0,
    );
    const totalEstimasiListing = selectedKegiatan?.has_listing_updating
        ? alokasiItems.reduce(
              (sum, item) => sum + (item.estimasi_honor_listing || 0),
              0,
          )
        : 0;

    // Calculate sisa pagu for each phase
    const sisaPaguPencacahan =
        pagu_pencacahan - current_total_spent - totalEstimasiPencacahan;
    const sisaPaguListing =
        pagu_listing - current_total_spent_listing - totalEstimasiListing;
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
            (item) => !item.petugas_id || !item.peran
        );
        
        // Validate based on tahapan selection
        if (selectedKegiatan?.has_listing_updating) {
            if (tahapan === 'both') {
                hasEmpty = hasEmpty || alokasiItems.some(item => !item.jumlah_satuan || !item.jumlah_satuan_listing);
            } else if (tahapan === 'listing_only') {
                hasEmpty = hasEmpty || alokasiItems.some(item => !item.jumlah_satuan_listing);
            } else if (tahapan === 'pencacahan_only') {
                hasEmpty = hasEmpty || alokasiItems.some(item => !item.jumlah_satuan);
            }
        } else {
            hasEmpty = hasEmpty || alokasiItems.some(item => !item.jumlah_satuan);
        }
        
        if (hasEmpty) {
            let errorMsg = 'Lengkapi semua data petugas termasuk peran';
            if (selectedKegiatan?.has_listing_updating) {
                if (tahapan === 'both') {
                    errorMsg = 'Lengkapi semua data petugas termasuk peran, jumlah satuan listing dan pencacahan';
                } else if (tahapan === 'listing_only') {
                    errorMsg = 'Lengkapi semua data petugas termasuk peran dan jumlah satuan listing';
                } else if (tahapan === 'pencacahan_only') {
                    errorMsg = 'Lengkapi semua data petugas termasuk peran dan jumlah satuan pencacahan';
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
            tanggal_mulai: tanggalMulai || undefined,
            tanggal_selesai: tanggalSelesai || undefined,
            tanggal_mulai_listing: (tahapan === 'both' || tahapan === 'listing_only') ? (tanggalMulaiListing || undefined) : undefined,
            tanggal_selesai_listing: (tahapan === 'both' || tahapan === 'listing_only') ? (tanggalSelesaiListing || undefined) : undefined,
            alokasi: alokasiItems.map((item) => {
                const base = {
                    petugas_id: item.petugas_id,
                    peran: item.peran,
                    bulan,
                    tahun: active_year,
                    jenis_kegiatan: jenisKegiatan,
                    catatan: item.catatan || '',
                    tahapan: selectedKegiatan?.has_listing_updating ? tahapan : 'both', // Always send tahapan
                };
                
                // Handle based on tahapan
                if (selectedKegiatan?.has_listing_updating) {
                    if (tahapan === 'both') {
                        return {
                            ...base,
                            jumlah_satuan: Number(item.jumlah_satuan) || 0,
                            estimasi_honor: item.estimasi_honor || 0,
                            jumlah_satuan_listing: Number(item.jumlah_satuan_listing) || 0,
                            estimasi_honor_listing: item.estimasi_honor_listing || 0,
                        };
                    } else if (tahapan === 'listing_only') {
                        return {
                            ...base,
                            jumlah_satuan: 0,
                            estimasi_honor: 0,
                            jumlah_satuan_listing: Number(item.jumlah_satuan_listing) || 0,
                            estimasi_honor_listing: item.estimasi_honor_listing || 0,
                        };
                    } else { // pencacahan_only
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

    const months = [
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
    ];

    // Filter months based on kegiatan's tanggal_mulai and tanggal_selesai
    const filteredMonths = useMemo(() => {
        if (!selectedKegiatan || !selectedKegiatan.tanggal_mulai || !selectedKegiatan.tanggal_selesai) {
            return months;
        }
        const start = new Date(selectedKegiatan.tanggal_mulai);
        const end = new Date(selectedKegiatan.tanggal_selesai);

        
        // Only show months in the correct year and within the kegiatan's date range
        const filtered = months.filter((m) => {
            // The year must be within kegiatan range
            if (active_year < start.getFullYear() || active_year > end.getFullYear()) {
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
            return active_year > start.getFullYear() && active_year < end.getFullYear();
        });
        
        return filtered;
    }, [selectedKegiatan, months, active_year]);

    // Set default bulan to first available month in filteredMonths
    useEffect(() => {
        // Skip if in edit mode or if there's a source periode
        if (isEditMode || sourcePeriode) {
            return;
        }

        // Find first available month from filteredMonths that's not used
        const availableMonths = filteredMonths.filter(
            (month) => !usedMonths.includes(month.value)
        );

        if (availableMonths.length > 0) {
            const firstAvailableMonth = availableMonths[0].value;
            // Only update if current bulan is not in available months
            if (!availableMonths.some(m => m.value === bulan)) {
                setBulan(firstAvailableMonth);
            }
        } else if (filteredMonths.length > 0) {
            // If all months are used, use the first month from filteredMonths
            setBulan(filteredMonths[0].value);
        }
    }, [filteredMonths, usedMonths, isEditMode, sourcePeriode]);

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

            {/* Source Period Info - Only show for copy mode, not edit mode */}
            {sourcePeriode && !isEditMode && !isViewMode && (
                <div className="rounded-lg border-2 border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                    <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
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
                                    options={filteredKegiatans.map(
                                        (kegiatan) => ({
                                            value: kegiatan.id,
                                            label: kegiatan.nama_kegiatan,
                                            searchKeywords: `${kegiatan.kode_kegiatan} ${kegiatan.nama_kegiatan} ${kegiatan.deskripsi || ''}`,
                                        }),
                                    )}
                                    value={selectedKegiatanId}
                                    onValueChange={setSelectedKegiatanId}
                                    placeholder="Pilih Kegiatan"
                                    searchPlaceholder="Cari kegiatan..."
                                    disabled={isEditMode || isViewMode}
                                />
                                {errors.kegiatan_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.kegiatan_id}
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
                                        {filteredMonths.map((month) => (
                                            <SelectItem
                                                key={month.value}
                                                value={month.value.toString()}
                                                disabled={usedMonths.includes(month.value)}
                                            >
                                                {month.label}{' '}
                                                {usedMonths.includes(month.value)
                                                    ? '(Sudah digunakan)'
                                                    : ''}
                                            </SelectItem>
                                        ))}
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
                                        Tentukan tanggal pelaksanaan kegiatan untuk periode ini
                                    </p>
                                </div>

                                {/* Jadwal Listing - Show only if tahapan includes listing */}
                                {(tahapan === 'both' || tahapan === 'listing_only') && selectedKegiatan?.has_listing_updating && (
                                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                        <h5 className="mb-3 text-sm font-semibold text-blue-900 dark:text-blue-300">
                                            Jadwal Listing
                                        </h5>
                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="tanggal_mulai_listing">
                                                    Tanggal Mulai Listing
                                                    <span className="text-red-500">*</span>
                                                </Label>
                                                <Input
                                                    type="date"
                                                    id="tanggal_mulai_listing"
                                                    value={tanggalMulaiListing}
                                                    onChange={(e) => setTanggalMulaiListing(e.target.value)}
                                                    disabled={isViewMode}
                                                    className={isViewMode ? 'cursor-not-allowed bg-neutral-100 dark:bg-neutral-900' : ''}
                                                />
                                                {errors.tanggal_mulai_listing && (
                                                    <p className="text-sm text-red-500">{errors.tanggal_mulai_listing}</p>
                                                )}
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="tanggal_selesai_listing">
                                                    Tanggal Selesai Listing
                                                    <span className="text-red-500">*</span>
                                                </Label>
                                                <Input
                                                    type="date"
                                                    id="tanggal_selesai_listing"
                                                    value={tanggalSelesaiListing}
                                                    onChange={(e) => setTanggalSelesaiListing(e.target.value)}
                                                    min={tanggalMulaiListing}
                                                    disabled={isViewMode}
                                                    className={isViewMode ? 'cursor-not-allowed bg-neutral-100 dark:bg-neutral-900' : ''}
                                                />
                                                {errors.tanggal_selesai_listing && (
                                                    <p className="text-sm text-red-500">{errors.tanggal_selesai_listing}</p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Jadwal Pencacahan */}
                                <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                                    <h5 className="mb-3 text-sm font-semibold text-green-900 dark:text-green-300">
                                        Jadwal {tahapan === 'listing_only' ? 'Kegiatan' : 'Pencacahan'}
                                    </h5>
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label htmlFor="tanggal_mulai">
                                                Tanggal Mulai
                                                <span className="text-red-500">*</span>
                                            </Label>
                                            <Input
                                                type="date"
                                                id="tanggal_mulai"
                                                value={tanggalMulai}
                                                onChange={(e) => setTanggalMulai(e.target.value)}
                                                disabled={isViewMode}
                                                className={isViewMode ? 'cursor-not-allowed bg-neutral-100 dark:bg-neutral-900' : ''}
                                            />
                                            {errors.tanggal_mulai && (
                                                <p className="text-sm text-red-500">{errors.tanggal_mulai}</p>
                                            )}
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="tanggal_selesai">
                                                Tanggal Selesai
                                                <span className="text-red-500">*</span>
                                            </Label>
                                            <Input
                                                type="date"
                                                id="tanggal_selesai"
                                                value={tanggalSelesai}
                                                onChange={(e) => setTanggalSelesai(e.target.value)}
                                                min={tanggalMulai}
                                                disabled={isViewMode}
                                                className={isViewMode ? 'cursor-not-allowed bg-neutral-100 dark:bg-neutral-900' : ''}
                                            />
                                            {errors.tanggal_selesai && (
                                                <p className="text-sm text-red-500">{errors.tanggal_selesai}</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
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
                                value={jumlahPetugas}
                                onChange={(e) =>
                                    handleJumlahPetugasChange(
                                        parseInt(e.target.value) || 1,
                                    )
                                }
                                min="1"
                                max="50"
                                placeholder="Masukkan jumlah petugas"
                                disabled={isRevisiMode || isViewMode}
                                className={
                                    isRevisiMode || isViewMode
                                        ? 'cursor-not-allowed bg-neutral-100 dark:bg-neutral-900'
                                        : ''
                                }
                            />
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                {jumlahPetugas} baris input petugas akan
                                ditampilkan
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

                            {errors.alokasi && (
                                <div className="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                                    <p className="text-sm text-red-600 dark:text-red-400">
                                        {errors.alokasi}
                                    </p>
                                </div>
                            )}

                            <div className="space-y-4">
                                {alokasiItems.map((item, index) => (
                                    <div
                                        key={index}
                                        className="rounded-lg border border-neutral-200/70 p-4 dark:border-neutral-800"
                                    >
                                        <div className="mb-3 flex items-center justify-between">
                                            <h4 className="font-medium text-neutral-900 dark:text-white">
                                                Petugas #{index + 1}
                                            </h4>
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
                                                    options={petugas.map(
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
                                                            
                                                            const jenisPetugasLabel = p.jenis_petugas === 'organik' ? 'Organik' : 'Non-Organik';
                                                            const jabatanLabel = p.jabatan || '-';
                                                            
                                                            return {
                                                                value: String(
                                                                    p.id,
                                                                ),
                                                                label: `${p.nama} - ${jenisPetugasLabel} - ${jabatanLabel}`,
                                                                displayLabel: p.nama,
                                                                disabled:
                                                                    isSelectedInOtherRow,
                                                            };
                                                        },
                                                    )}
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
                                                    disabled={isRevisiMode || isViewMode}
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
                                                    value={item.peran || undefined}
                                                    onValueChange={(value) =>
                                                        updateAlokasiItem(
                                                            index,
                                                            'peran',
                                                            value,
                                                        )
                                                    }
                                                    disabled={isRevisiMode || isViewMode}
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
                                                            
                                                            console.log(`🔍 Rendering Select for item ${index}:`, {
                                                                itemPeran: item.peran,
                                                                petugasId: item.petugas_id,
                                                                hasSelectedKegiatan: !!selectedKegiatan,
                                                                hasSelectedPetugas: !!selectedPetugas,
                                                                kegiatanId: selectedKegiatan?.id,
                                                                rateHonorsCount: selectedKegiatan?.rate_honors?.length
                                                            });
                                                            
                                                            if (
                                                                !selectedKegiatan ||
                                                                !selectedPetugas
                                                            )
                                                                return null;
                                                            const statusKepegawaian =
                                                                selectedPetugas.jenis_petugas ===
                                                                'organik'
                                                                    ? 'organik'
                                                                    : 'non_organik';
                                                            // Ambil jenis_penugasan unik dari rate_honors yang cocok status_kepegawaian
                                                            const jenisPenugasanList =
                                                                selectedKegiatan.rate_honors
                                                                    .filter(
                                                                        (rh) =>
                                                                            rh.status_kepegawaian ===
                                                                            statusKepegawaian,
                                                                    )
                                                                    .map(
                                                                        (rh) =>
                                                                            rh.jenis_penugasan,
                                                                    );
                                                            const uniqueJenisPenugasan =
                                                                Array.from(
                                                                    new Set(
                                                                        jenisPenugasanList,
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
                                                            };
                                                            
                                                            const options = uniqueJenisPenugasan.map(jp => ({
                                                                key: jp,
                                                                value: labelMap[jp] || jp,
                                                                label: labelMap[jp] || jp
                                                            }));
                                                            
                                                            console.log(`✅ Available options:`, options);
                                                            
                                                            return options.map(
                                                                (opt) => (
                                                                    <SelectItem
                                                                        key={opt.key}
                                                                        value={opt.value}
                                                                    >
                                                                        {opt.label}
                                                                    </SelectItem>
                                                                ),
                                                            );
                                                        })()}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            {/* Dual-phase: Listing fields */}
                                            {selectedKegiatan?.has_listing_updating && 
                                             (tahapan === 'both' || tahapan === 'listing_only') && (
                                                <>
                                                    <div className="space-y-2">
                                                        <Label
                                                            htmlFor={`satuan_listing_${index}`}
                                                        >
                                                            Jumlah Beban Tugas
                                                            Listing{' '}
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
                                                            min="1"
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
                                                </>
                                            )}

                                            {/* Jumlah Beban Tugas Pencacahan */}
                                            {(!selectedKegiatan?.has_listing_updating || 
                                              tahapan === 'both' || 
                                              tahapan === 'pencacahan_only') && (
                                            <>
                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor={`satuan_${index}`}
                                                >
                                                    Jumlah Beban Tugas
                                                    {selectedKegiatan?.has_listing_updating ? ' Pencacahan' : ''}{' '}
                                                    <span className="text-red-500">
                                                        *
                                                    </span>
                                                </Label>
                                                <Input
                                                    type="number"
                                                    id={`satuan_${index}`}
                                                    value={item.jumlah_satuan}
                                                    onChange={(e) =>
                                                        updateAlokasiItem(
                                                            index,
                                                            'jumlah_satuan',
                                                            e.target.value,
                                                        )
                                                    }
                                                    min="1"
                                                    placeholder="0"
                                                    disabled={isViewMode}
                                                />
                                            </div>

                                            {/* Estimasi Honor Pencacahan (Read only) */}
                                            <div className="space-y-2 md:col-span-4">
                                                <Label
                                                    htmlFor={`estimasi_${index}`}
                                                >
                                                    Estimasi Honor{selectedKegiatan?.has_listing_updating ? ' Pencacahan' : ''}
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
                                                    disabled={isRevisiMode || isViewMode}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </ContentCard>
                )}

                {/* Estimasi Sisa Pagu */}
                {selectedKegiatanId &&
                    alokasiItems.length > 0 &&
                    alokasiItems.some(item => item.petugas_id && item.peran) && (
                        <ContentCard data-budget-info>
                            {selectedKegiatan?.has_listing_updating ? (
                                <div className="space-y-8">
                                    {/* Listing Phase - Show if tahapan is 'both' or 'listing_only' */}
                                    {(tahapan === 'both' || tahapan === 'listing_only') && (
                                    <div
                                        className={`rounded-lg border-2 p-6 ${isSufficientListing ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/20' : 'border-red-500 bg-red-50 dark:bg-red-950/20'}`}
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
                                                            (Periode Lain):
                                                        </span>
                                                        <span className="font-semibold">
                                                            {formatCurrency(
                                                                current_total_spent_listing,
                                                            )}
                                                        </span>
                                                    </div>
                                                    <div
                                                        className={`flex justify-between text-sm ${isSufficientListing ? 'text-blue-800 dark:text-blue-300' : 'text-red-800 dark:text-red-300'}`}
                                                    >
                                                        <span className="font-medium">
                                                            Estimasi Periode Ini
                                                            (Listing):
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
                                                            Estimasi Sisa Pagu
                                                            Listing:
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
                                                            ⚠️ Estimasi total
                                                            honor listing
                                                            melebihi pagu
                                                            listing yang
                                                            tersisa. Silakan
                                                            periksa lagi isian
                                                            atau ubah pagu
                                                            listing melalui
                                                            Fitur Revisi di
                                                            halaman Kegiatan.
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    )}
                                    {/* Pencacahan Phase - Show if tahapan is 'both' or 'pencacahan_only' */}
                                    {(tahapan === 'both' || tahapan === 'pencacahan_only') && (
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
                                                            (Periode Lain):
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
                                                            Estimasi Periode Ini
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
                                                            Estimasi Sisa Pagu
                                                            Pencacahan:
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
                                                            ⚠️ Estimasi total
                                                            honor pencacahan
                                                            melebihi pagu
                                                            pencacahan yang
                                                            tersisa. Silakan
                                                            periksa lagi isian
                                                            atau ubah pagu
                                                            pencacahan melalui
                                                            Fitur Revisi di
                                                            halaman Kegiatan.
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
                        <Button variant="outline" type="button" asChild>
                            <Link href="/alokasi">Batal</Link>
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || !selectedKegiatanId}
                        >
                            {processing ? 'Menyimpan...' : 'Simpan Alokasi'}
                        </Button>
                    </div>
                )}
            </form>
        </AppLayout>
    );
}
