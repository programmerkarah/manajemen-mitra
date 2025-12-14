import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/input-error'
import { SearchableSelect } from '@/components/searchable-select'
import { type BreadcrumbItem, type SharedData } from '@/types'
import { Head, Link, router, usePage } from '@inertiajs/react'
import { useState, useEffect, useMemo } from 'react'
import { ArrowLeft, Plus, Trash2, Copy } from 'lucide-react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Alokasi petugas', href: '/alokasi' },
    { title: 'Tambah Alokasi', href: '/alokasi/create' },
]

interface Kegiatan {
    id: string
    hashed_id: string
    kode_kegiatan: string
    nama_kegiatan: string
    deskripsi?: string | null
    jenis_kegiatan: 'sensus' | 'survei'
    ketua_tim_user_id: number
    rate_honors: RateHonor[]
}

interface Petugas {
    id: string
    nama: string
    nik: string
    email: string
    jenis_petugas: 'organik' | 'non-organik'
    peran?: string
}

interface RateHonor {
    id: string
    posisi: string
    jenis_kegiatan: 'sensus' | 'survei'
    status_kepegawaian: 'organik' | 'non_organik'
    jenis_penugasan: string
    rate: number
    satuan: {
        id: string
        nama: string
    }
}

interface AlokasiItem {
    petugas_id: string
    peran: string
    jumlah_satuan: string
    estimasi_honor: number
    catatan: string
}

interface AlokasiCreateProps {
    kegiatans: Kegiatan[]
    petugas: Petugas[]
    selectedKegiatan?: Kegiatan | null
    active_year: number
    copiedAlokasi?: any[] | null
    sourcePeriode?: { bulan: string; tahun: number } | null
    budget_info: Record<number, { pagu_anggaran: number; current_total_spent: number }>
    used_months_info: Record<number, number[]>
    isEditMode?: boolean
    isRevisiMode?: boolean
}

export default function Create({ kegiatans, petugas, selectedKegiatan: preSelectedKegiatan, active_year, copiedAlokasi, sourcePeriode, budget_info, used_months_info, isEditMode = false, isRevisiMode = false }: AlokasiCreateProps) {
    const { auth } = usePage<SharedData>().props
    const [selectedKegiatanId, setSelectedKegiatanId] = useState(preSelectedKegiatan?.id || '')
    const [bulan, setBulan] = useState(isEditMode && sourcePeriode ? parseInt(sourcePeriode.bulan) : new Date().getMonth() + 1)
    const [jenisKegiatan, setJenisKegiatan] = useState<'sensus' | 'survei'>('survei')
    const [showPengolahan, setShowPengolahan] = useState(() => {
        // In edit mode, check if any alokasi has pengolahan roles
        if (isEditMode && copiedAlokasi) {
            return copiedAlokasi.some(alok => 
                alok.peran === 'pengolahan' || alok.peran === 'pengawas_pengolahan'
            )
        }
        return false
    })
    const [jumlahPetugas, setJumlahPetugas] = useState(isEditMode && copiedAlokasi ? copiedAlokasi.length : 1)
    const [alokasiItems, setAlokasiItems] = useState<AlokasiItem[]>([
        { petugas_id: '', peran: '', jumlah_satuan: '', estimasi_honor: 0, catatan: '' }
    ])
    const [processing, setProcessing] = useState(false)
    const [errors, setErrors] = useState<any>({})

    // Filter kegiatan based on ketua_tim role
    const filteredKegiatans = useMemo(() => {
        if (auth.activeRole?.name === 'ketua_tim') {
            return kegiatans.filter(k => k.ketua_tim_user_id === auth.user.id)
        }
        return kegiatans
    }, [kegiatans, auth.activeRole, auth.user.id])

    const selectedKegiatan = filteredKegiatans.find(k => String(k.id) === String(selectedKegiatanId))

    // Get budget info for selected kegiatan
    const currentBudget = selectedKegiatan && selectedKegiatanId
        ? budget_info[Number(selectedKegiatan.id)] || { pagu_anggaran: 0, current_total_spent: 0 }
        : { pagu_anggaran: 0, current_total_spent: 0 }

    const pagu_anggaran = currentBudget.pagu_anggaran
    const current_total_spent = currentBudget.current_total_spent

    // Get used months for selected kegiatan
    const usedMonths = selectedKegiatan && selectedKegiatanId
        ? used_months_info[Number(selectedKegiatan.id)] || []
        : []

    // Initialize with copied data if available
    useEffect(() => {
        if (copiedAlokasi && copiedAlokasi.length > 0) {
            const initialItems = copiedAlokasi.map(alokasi => ({
                petugas_id: String(alokasi.petugas_id || ''),
                peran: alokasi.peran === 'pcl_ppl' ? 'PCL' : 
                      alokasi.peran === 'pml' ? 'PML' : 
                      alokasi.peran === 'pengolahan' ? 'Pengolahan' : 
                      alokasi.peran === 'pengawas_pengolahan' ? 'Pengawas Pengolahan' : '',
                jumlah_satuan: String(alokasi.jumlah_satuan),
                estimasi_honor: 0, // Will be recalculated
                catatan: alokasi.catatan || '',
            }))
            setAlokasiItems(initialItems)
            setJumlahPetugas(initialItems.length)
        }
    }, [copiedAlokasi])

    // Debug: Log kegiatans data
    console.log('All Kegiatans:', kegiatans)
    console.log('All Kegiatan IDs:', kegiatans.map(k => ({ id: k.id, type: typeof k.id })))
    console.log('Selected Kegiatan ID:', selectedKegiatanId, 'type:', typeof selectedKegiatanId)
    console.log('Selected Kegiatan Object:', selectedKegiatan)

    // Debug: Log selected kegiatan and its rate honors
    if (selectedKegiatan) {
        console.log('Selected Kegiatan jenis_kegiatan:', selectedKegiatan.jenis_kegiatan)
        console.log('Has jenis_kegiatan?', 'jenis_kegiatan' in selectedKegiatan)
        console.log('Rate Honors:', selectedKegiatan.rate_honors)
    }

    // Set jenisKegiatan from selectedKegiatan and recalculate estimasi
    useEffect(() => {
        if (!selectedKegiatan) return
        
        // Update jenis kegiatan from selected kegiatan
        const newJenisKegiatan = selectedKegiatan.jenis_kegiatan
        console.log('Setting jenisKegiatan to:', newJenisKegiatan)
        setJenisKegiatan(newJenisKegiatan)
        
        // Recalculate estimasi for all items using the calculateEstimasi function
        setAlokasiItems(prevItems => {
            return prevItems.map(item => {
                if (item.petugas_id && item.peran && item.jumlah_satuan) {
                    const newEstimasi = calculateEstimasi(item.petugas_id, item.peran, item.jumlah_satuan)
                    return { ...item, estimasi_honor: newEstimasi }
                }
                return item
            })
        })
    }, [selectedKegiatanId, selectedKegiatan, petugas])

    // Calculate estimasi honor for a petugas
    const calculateEstimasi = (petugasId: string, peran: string, jumlahSatuan: string) => {
        if (!petugasId || !peran || !jumlahSatuan || !selectedKegiatan) return 0
        
        const selectedPetugas = petugas.find(p => String(p.id) === String(petugasId))
        if (!selectedPetugas) return 0

        const statusKepegawaian = selectedPetugas.jenis_petugas === 'organik' ? 'organik' : 'non_organik'
        
        // Map peran to jenis_penugasan - this determines which rate_honor to use
        let jenisPenugasan = ''
        if (peran === 'PCL') jenisPenugasan = 'pcl_ppl'
        else if (peran === 'PML') jenisPenugasan = 'pml'
        else if (peran === 'Pengolahan') jenisPenugasan = 'pengolahan'
        else if (peran === 'Pengawas Pengolahan') jenisPenugasan = 'pengawas_pengolahan'
        
        if (!jenisPenugasan) return 0
        
        // Find matching rate honor based on:
        // 1. status_kepegawaian (organik/non_organik from petugas)
        // 2. jenis_penugasan (from peran mapping above)
        // Note: jenis_kegiatan in rate_honor is just metadata, the key is jenis_penugasan
        const matchingRateHonor = selectedKegiatan.rate_honors?.find(
            r => r.status_kepegawaian === statusKepegawaian && 
                 r.jenis_penugasan === jenisPenugasan
        )

        if (!matchingRateHonor) {
            console.warn('No matching rate honor found for:', {
                petugasId,
                petugas: selectedPetugas.nama,
                peran,
                jenisPenugasan,
                jenis_petugas: selectedPetugas.jenis_petugas,
                statusKepegawaian,
                availableRateHonors: selectedKegiatan.rate_honors
            })
            return 0
        }

        const estimasi = matchingRateHonor.rate * Number(jumlahSatuan)
        
        console.log('Calculate estimasi:', {
            petugas: selectedPetugas.nama,
            peran,
            jenisPenugasan,
            statusKepegawaian,
            rate: matchingRateHonor.rate,
            jumlah: jumlahSatuan,
            estimasi
        })

        return estimasi
    }

    // Handle jumlah petugas change
    const handleJumlahPetugasChange = (value: number) => {
        const newValue = Math.max(1, Math.min(50, value))
        setJumlahPetugas(newValue)
        
        const currentItems = [...alokasiItems]
        if (newValue > currentItems.length) {
            // Add new items
            for (let i = currentItems.length; i < newValue; i++) {
                currentItems.push({ petugas_id: '', peran: '', jumlah_satuan: '', estimasi_honor: 0, catatan: '' })
            }
        } else if (newValue < currentItems.length) {
            // Remove excess items
            currentItems.splice(newValue)
        }
        setAlokasiItems(currentItems)
    }

    // Update alokasi item
    const updateAlokasiItem = (index: number, field: keyof AlokasiItem, value: any) => {
        const newItems = [...alokasiItems]
        newItems[index] = { ...newItems[index], [field]: value }
        
        // Recalculate estimasi if petugas, peran, or jumlah_satuan changed
        if (field === 'petugas_id' || field === 'peran' || field === 'jumlah_satuan') {
            newItems[index].estimasi_honor = calculateEstimasi(
                newItems[index].petugas_id,
                newItems[index].peran,
                newItems[index].jumlah_satuan
            )
        }
        
        setAlokasiItems(newItems)
    }

    // Calculate total estimasi
    const totalEstimasi = alokasiItems.reduce((sum, item) => sum + item.estimasi_honor, 0)

    // Calculate sisa pagu
    const sisaPagu = pagu_anggaran - current_total_spent - totalEstimasi
    const isSufficient = sisaPagu >= 0

    // Format currency
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount)
    }

    // Handle submit
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        setProcessing(true)
        setErrors({})

        // Validate
        if (!selectedKegiatanId) {
            setErrors({ kegiatan_id: 'Pilih kegiatan terlebih dahulu' })
            setProcessing(false)
            return
        }

        // Validate all items
        const hasEmpty = alokasiItems.some(item => !item.petugas_id || !item.peran || !item.jumlah_satuan)
        if (hasEmpty) {
            setErrors({ alokasi: 'Lengkapi semua data petugas termasuk peran' })
            setProcessing(false)
            return
        }

        // Prepare data
        const formData = {
            alokasi: alokasiItems.map(item => ({
                petugas_id: item.petugas_id,
                peran: item.peran,
                bulan,
                tahun: active_year,
                jumlah_satuan: Number(item.jumlah_satuan),
                jenis_kegiatan: jenisKegiatan,
                catatan: item.catatan || ''
            }))
        }

        // Use hashed_id for the route (model uses HasHashedRouteKey)
        const kegiatanHashedId = selectedKegiatan?.hashed_id
        
        if (!kegiatanHashedId) {
            setErrors({ kegiatan_id: 'Kegiatan tidak valid' })
            setProcessing(false)
            return
        }

        router.post(`/alokasi/kegiatan/${kegiatanHashedId}/store-multiple`, formData, {
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: (errors) => {
                setErrors(errors)
                setProcessing(false)
            },
            onFinish: () => {
                setProcessing(false)
            }
        })
    }

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
    ]

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Periode Kegiatan" />

            <PageHeader
                title="Tambah Periode Kegiatan"
                description="Alokasikan petugas ke kegiatan untuk periode yang dipilih"
            >
                <Button variant="outline" asChild>
                    <Link href="/alokasi">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            {/* Source Period Info - Only show for copy mode, not edit mode */}
            {sourcePeriode && !isEditMode && (
                <div className="rounded-lg border-2 border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                    <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <Copy className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div className="flex-1">
                            <h3 className="text-sm font-semibold text-blue-800 dark:text-blue-300">
                                Menyalin Alokasi dari Periode Sebelumnya
                            </h3>
                            <p className="mt-1 text-sm text-blue-700 dark:text-blue-400">
                                Data alokasi berikut disalin dari periode {months.find(m => m.value === parseInt(sourcePeriode.bulan))?.label} {sourcePeriode.tahun}. 
                                Anda dapat mengubah petugas, jumlah beban tugas, atau menambah/mengurangi petugas sesuai kebutuhan.
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
                                    Kegiatan <span className="text-red-500">*</span>
                                </Label>
                                <SearchableSelect
                                    options={filteredKegiatans.map((kegiatan) => ({
                                        value: kegiatan.id,
                                        label: kegiatan.nama_kegiatan,
                                        searchKeywords: `${kegiatan.kode_kegiatan} ${kegiatan.nama_kegiatan} ${kegiatan.deskripsi || ''}`,
                                    }))}
                                    value={selectedKegiatanId}
                                    onValueChange={setSelectedKegiatanId}
                                    placeholder="Pilih Kegiatan"
                                    searchPlaceholder="Cari kegiatan..."
                                    disabled={isEditMode}
                                />
                                {errors.kegiatan_id && (
                                    <p className="text-sm text-red-500">{errors.kegiatan_id}</p>
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
                                    value={selectedKegiatan ? (selectedKegiatan.jenis_kegiatan === 'sensus' ? 'Sensus' : 'Survei') : '-'}
                                    disabled
                                    className="bg-neutral-100 dark:bg-neutral-900 cursor-not-allowed capitalize"
                                />
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                    Jenis kegiatan diambil dari data kegiatan
                                </p>
                            </div>

                            {/* Checkbox Petugas Pengolahan */}
                            <div className="space-y-2">
                                <Label>&nbsp;</Label>
                                <div className="flex items-center space-x-2 pt-2">
                                    <input
                                        type="checkbox"
                                        id="show_pengolahan"
                                        checked={showPengolahan}
                                        onChange={(e) => setShowPengolahan(e.target.checked)}
                                        disabled={isRevisiMode}
                                        className={`h-4 w-4 rounded border-neutral-300 text-blue-600 focus:ring-2 focus:ring-blue-600 dark:border-neutral-700 dark:bg-neutral-900 ${isRevisiMode ? 'disabled:cursor-not-allowed disabled:opacity-50' : ''}`}
                                    />
                                    <Label htmlFor="show_pengolahan" className="cursor-pointer font-normal">
                                        Tampilkan Petugas Pengolahan
                                    </Label>
                                </div>
                            </div>

                            {/* Bulan */}
                            <div className="space-y-2">
                                <Label htmlFor="bulan">
                                    Bulan <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="bulan"
                                    value={bulan}
                                    onChange={(e) => setBulan(parseInt(e.target.value))}
                                    disabled={isRevisiMode}
                                    className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                >
                                    {months.map((month) => (
                                        <option 
                                            key={month.value} 
                                            value={month.value}
                                            disabled={usedMonths.includes(month.value)}
                                        >
                                            {month.label} {usedMonths.includes(month.value) ? '(Sudah digunakan)' : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Tahun (from Active Year) */}
                            <div className="space-y-2">
                                <Label htmlFor="tahun">
                                    Tahun
                                </Label>
                                <Input
                                    type="text"
                                    id="tahun"
                                    value={active_year}
                                    disabled
                                    className="bg-neutral-100 dark:bg-neutral-900 cursor-not-allowed"
                                />
                            </div>
                        </div>
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
                                Tentukan berapa petugas yang akan dialokasikan (PML, PCL, dll)
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="jumlah_petugas">
                                Jumlah Petugas <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                type="number"
                                id="jumlah_petugas"
                                value={jumlahPetugas}
                                onChange={(e) => handleJumlahPetugasChange(parseInt(e.target.value) || 1)}
                                min="1"
                                max="50"
                                placeholder="Masukkan jumlah petugas"
                                disabled={isRevisiMode}
                                className={isRevisiMode ? "bg-neutral-100 dark:bg-neutral-900 cursor-not-allowed" : ""}
                            />
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                {jumlahPetugas} baris input petugas akan ditampilkan
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
                                    Isi data setiap petugas yang akan dialokasikan
                                </p>
                            </div>

                            {errors.alokasi && (
                                <div className="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20">
                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.alokasi}</p>
                                </div>
                            )}

                            <div className="space-y-4">
                                {alokasiItems.map((item, index) => (
                                    <div key={index} className="rounded-lg border border-neutral-200/70 p-4 dark:border-neutral-800">
                                        <div className="mb-3 flex items-center justify-between">
                                            <h4 className="font-medium text-neutral-900 dark:text-white">
                                                Petugas #{index + 1}
                                            </h4>
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
                                            {/* Nama Petugas */}
                                            <div className="space-y-2 md:col-span-2">
                                                <Label htmlFor={`petugas_${index}`}>
                                                    Nama Petugas <span className="text-red-500">*</span>
                                                </Label>
                                                <SearchableSelect
                                                    options={petugas.map((p) => {
                                                        const isSelectedInOtherRow = alokasiItems.some(
                                                            (otherItem, otherIndex) => 
                                                                otherIndex !== index && 
                                                                String(otherItem.petugas_id) === String(p.id)
                                                        )
                                                        return {
                                                            value: String(p.id),
                                                            label: p.nama,
                                                            disabled: isSelectedInOtherRow,
                                                        }
                                                    })}
                                                    value={item.petugas_id}
                                                    onValueChange={(value) => updateAlokasiItem(index, 'petugas_id', value)}
                                                    placeholder="Pilih Petugas"
                                                    searchPlaceholder="Cari petugas..."
                                                />
                                            </div>

                                            {/* Peran */}
                                            <div className="space-y-2">
                                                <Label htmlFor={`peran_${index}`}>
                                                    Peran <span className="text-red-500">*</span>
                                                </Label>
                                                <select
                                                    id={`peran_${index}`}
                                                    value={item.peran}
                                                    onChange={(e) => updateAlokasiItem(index, 'peran', e.target.value)}
                                                    className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                                >
                                                    <option value="">Pilih Peran</option>
                                                    <option value="PCL">PCL</option>
                                                    <option value="PML">PML</option>
                                                    {(() => {
                                                        if (!showPengolahan) return null;
                                                        
                                                        const selectedPetugas = petugas.find(p => String(p.id) === String(item.petugas_id));
                                                        const isOrganik = selectedPetugas?.jenis_petugas === 'organik';
                                                        
                                                        console.log('Peran Debug:', {
                                                            index,
                                                            showPengolahan,
                                                            jenisKegiatan,
                                                            petugasId: item.petugas_id,
                                                            selectedPetugas,
                                                            jenisPetugas: selectedPetugas?.jenis_petugas,
                                                            isOrganik
                                                        });
                                                        
                                                        if (jenisKegiatan === 'sensus') {
                                                            // Sensus: both options for all petugas
                                                            return (
                                                                <>
                                                                    <option value="Pengolahan">Petugas Pengolahan</option>
                                                                    <option value="Pengawas Pengolahan">Pengawas Pengolahan</option>
                                                                </>
                                                            );
                                                        } else if (jenisKegiatan === 'survei') {
                                                            // Survei: Petugas Pengolahan for all, Pengawas Pengolahan only for organik
                                                            return (
                                                                <>
                                                                    <option value="Pengolahan">Petugas Pengolahan</option>
                                                                    {isOrganik && (
                                                                        <option value="Pengawas Pengolahan">Pengawas Pengolahan</option>
                                                                    )}
                                                                </>
                                                            );
                                                        }
                                                        
                                                        return null;
                                                    })()}
                                                </select>
                                            </div>

                                            {/* Jumlah Beban Tugas */}
                                            <div className="space-y-2">
                                                <Label htmlFor={`satuan_${index}`}>
                                                    Jumlah Beban Tugas <span className="text-red-500">*</span>
                                                </Label>
                                                <Input
                                                    type="number"
                                                    id={`satuan_${index}`}
                                                    value={item.jumlah_satuan}
                                                    onChange={(e) => updateAlokasiItem(index, 'jumlah_satuan', e.target.value)}
                                                    min="1"
                                                    placeholder="0"
                                                />
                                            </div>

                                            {/* Estimasi Honor (Read only) */}
                                            <div className="space-y-2 md:col-span-4">
                                                <Label htmlFor={`estimasi_${index}`}>
                                                    Estimasi Honor
                                                </Label>
                                                <Input
                                                    type="text"
                                                    id={`estimasi_${index}`}
                                                    value={formatCurrency(item.estimasi_honor)}
                                                    readOnly
                                                    className="bg-neutral-50 dark:bg-neutral-900"
                                                />
                                            </div>

                                            {/* Catatan */}
                                            <div className="space-y-2 md:col-span-4">
                                                <Label htmlFor={`catatan_${index}`}>Catatan</Label>
                                                <Input
                                                    type="text"
                                                    id={`catatan_${index}`}
                                                    value={item.catatan}
                                                    onChange={(e) => updateAlokasiItem(index, 'catatan', e.target.value)}
                                                    placeholder="Catatan tambahan (opsional)"
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
                {selectedKegiatanId && totalEstimasi > 0 && (
                    <ContentCard>
                        <div className={`rounded-lg border-2 p-6 ${isSufficient ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : 'border-red-500 bg-red-50 dark:bg-red-950/20'}`}>
                            <div className="flex items-start gap-4">
                                <div className={`flex-shrink-0 rounded-full p-3 ${isSufficient ? 'bg-green-500' : 'bg-red-500'}`}>
                                    {isSufficient ? (
                                        <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                        </svg>
                                    ) : (
                                        <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    )}
                                </div>
                                <div className="flex-1">
                                    <h3 className={`text-lg font-bold ${isSufficient ? 'text-green-900 dark:text-green-300' : 'text-red-900 dark:text-red-300'}`}>
                                        {isSufficient ? 'Pagu Anggaran Mencukupi' : 'Pagu Anggaran Tidak Mencukupi'}
                                    </h3>
                                    <p className={`mt-1 text-sm ${isSufficient ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'}`}>
                                        Untuk {jumlahPetugas} petugas
                                    </p>
                                    
                                    <div className="mt-4 space-y-2.5">
                                        <div className={`flex justify-between text-sm ${isSufficient ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}>
                                            <span className="font-medium">Pagu Anggaran:</span>
                                            <span className="font-semibold">{formatCurrency(pagu_anggaran)}</span>
                                        </div>
                                        <div className={`flex justify-between text-sm ${isSufficient ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}>
                                            <span className="font-medium">Total Terpakai (Periode Lain):</span>
                                            <span className="font-semibold">{formatCurrency(current_total_spent)}</span>
                                        </div>
                                        <div className={`flex justify-between text-sm ${isSufficient ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300'}`}>
                                            <span className="font-medium">Estimasi Periode Ini:</span>
                                            <span className="font-semibold">{formatCurrency(totalEstimasi)}</span>
                                        </div>
                                        <div className={`flex justify-between border-t pt-2.5 text-base ${isSufficient ? 'border-green-400 dark:border-green-700' : 'border-red-400 dark:border-red-700'}`}>
                                            <span className={`font-bold ${isSufficient ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'}`}>Estimasi Sisa Pagu:</span>
                                            <span className={`text-xl font-bold ${isSufficient ? 'text-green-900 dark:text-green-200' : 'text-red-900 dark:text-red-200'}`}>{formatCurrency(sisaPagu)}</span>
                                        </div>
                                    </div>
                                    
                                    {!isSufficient && (
                                        <div className="mt-4 rounded-md border border-red-300 bg-red-100 p-3 dark:border-red-800 dark:bg-red-950/40">
                                            <p className="text-sm font-medium text-red-900 dark:text-red-300">
                                                ⚠️ Estimasi total honor melebihi pagu anggaran yang tersisa. Silakan periksa lagi isian atau ubah pagu anggaran melalui Fitur Revisi di halaman Kegiatan.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </ContentCard>
                )}

                {/* Footer Buttons */}
                <div className="flex items-center justify-end gap-3">
                    <Button variant="outline" type="button" asChild>
                        <Link href="/alokasi">Batal</Link>
                    </Button>
                    <Button type="submit" disabled={processing || !selectedKegiatanId}>
                        {processing ? 'Menyimpan...' : 'Simpan Alokasi'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    )
}
