import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/input-error'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/react'
import { useState, useEffect } from 'react'
import { ArrowLeft, Plus, Trash2 } from 'lucide-react'

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
}

export default function Create({ kegiatans, petugas, selectedKegiatan: preSelectedKegiatan, active_year }: AlokasiCreateProps) {
    const [selectedKegiatanId, setSelectedKegiatanId] = useState(preSelectedKegiatan?.id || '')
    const [bulan, setBulan] = useState(new Date().getMonth() + 1)
    const [jenisKegiatan, setJenisKegiatan] = useState<'sensus' | 'survei'>('survei')
    const [showPengolahan, setShowPengolahan] = useState(false)
    const [jumlahPetugas, setJumlahPetugas] = useState(1)
    const [alokasiItems, setAlokasiItems] = useState<AlokasiItem[]>([
        { petugas_id: '', peran: '', jumlah_satuan: '', estimasi_honor: 0, catatan: '' }
    ])
    const [processing, setProcessing] = useState(false)
    const [errors, setErrors] = useState<any>({})

    const selectedKegiatan = kegiatans.find(k => k.id === selectedKegiatanId)

    // Debug: Log selected kegiatan and its rate honors
    if (selectedKegiatan) {
        console.log('Selected Kegiatan:', selectedKegiatan)
        console.log('Rate Honors:', selectedKegiatan.rate_honors)
    }

    // Recalculate all estimasi when selectedKegiatan or jenisKegiatan changes
    useEffect(() => {
        if (!selectedKegiatan) return
        
        const updatedItems = alokasiItems.map(item => {
            if (item.petugas_id && item.peran && item.jumlah_satuan) {
                return {
                    ...item,
                    estimasi_honor: calculateEstimasi(item.petugas_id, item.peran, item.jumlah_satuan)
                }
            }
            return item
        })
        
        setAlokasiItems(updatedItems)
    }, [selectedKegiatanId, jenisKegiatan])

    // Calculate estimasi honor for a petugas
    const calculateEstimasi = (petugasId: string, peran: string, jumlahSatuan: string) => {
        if (!petugasId || !peran || !jumlahSatuan || !selectedKegiatan) return 0
        
        const selectedPetugas = petugas.find(p => String(p.id) === String(petugasId))
        if (!selectedPetugas) return 0

        const statusKepegawaian = selectedPetugas.jenis_petugas === 'organik' ? 'organik' : 'non_organik'
        
        // Map peran to jenis_penugasan
        let jenisPenugasan = ''
        if (peran === 'PCL') jenisPenugasan = 'pcl_ppl'
        else if (peran === 'PML') jenisPenugasan = 'pml'
        else if (peran === 'Pengolahan') jenisPenugasan = 'pengolahan'
        else if (peran === 'Pengawas Pengolahan') jenisPenugasan = 'pengawas_pengolahan'
        
        if (!jenisPenugasan) return 0
        
        // Filter rate honors by jenis_kegiatan, status_kepegawaian, and jenis_penugasan
        const matchingRateHonors = selectedKegiatan.rate_honors?.filter(
            r => r.status_kepegawaian === statusKepegawaian && 
                 r.jenis_kegiatan === jenisKegiatan &&
                 r.jenis_penugasan === jenisPenugasan
        )

        if (!matchingRateHonors || matchingRateHonors.length === 0) {
            console.warn('No matching rate honor found for:', {
                petugasId,
                peran,
                jenisPenugasan,
                jenis_petugas: selectedPetugas.jenis_petugas,
                statusKepegawaian,
                jenisKegiatan,
                availableRateHonors: selectedKegiatan.rate_honors
            })
            return 0
        }

        // Use the first matching rate honor
        const rateHonor = matchingRateHonors[0]

        return rateHonor.rate * Number(jumlahSatuan)
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

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount)
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
                                <select
                                    id="kegiatan_id"
                                    value={selectedKegiatanId}
                                    onChange={(e) => setSelectedKegiatanId(e.target.value)}
                                    className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                >
                                    <option value="">Pilih Kegiatan</option>
                                    {kegiatans.map((kegiatan) => (
                                        <option key={kegiatan.id} value={kegiatan.id}>
                                            {kegiatan.kode_kegiatan} - {kegiatan.nama_kegiatan}
                                        </option>
                                    ))}
                                </select>
                                {errors.kegiatan_id && (
                                    <p className="text-sm text-red-500">{errors.kegiatan_id}</p>
                                )}
                            </div>

                            {/* Jenis Kegiatan */}
                            <div className="space-y-2">
                                <Label htmlFor="jenis_kegiatan">
                                    Jenis Kegiatan <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="jenis_kegiatan"
                                    value={jenisKegiatan}
                                    onChange={(e) => setJenisKegiatan(e.target.value as 'sensus' | 'survei')}
                                    className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                >
                                    <option value="survei">Survei</option>
                                    <option value="sensus">Sensus</option>
                                </select>
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
                                        className="h-4 w-4 rounded border-neutral-300 text-blue-600 focus:ring-2 focus:ring-blue-600 dark:border-neutral-700 dark:bg-neutral-900"
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
                                    className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                >
                                    {months.map((month) => (
                                        <option key={month.value} value={month.value}>
                                            {month.label}
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
                                                <select
                                                    id={`petugas_${index}`}
                                                    value={item.petugas_id}
                                                    onChange={(e) => updateAlokasiItem(index, 'petugas_id', e.target.value)}
                                                    className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                                >
                                                    <option value="">Pilih Petugas</option>
                                                    {petugas.map((p) => {
                                                        // Check if this petugas is already selected in another row
                                                        const isSelectedInOtherRow = alokasiItems.some(
                                                            (otherItem, otherIndex) => 
                                                                otherIndex !== index && 
                                                                String(otherItem.petugas_id) === String(p.id)
                                                        );
                                                        
                                                        return (
                                                            <option 
                                                                key={p.id} 
                                                                value={p.id}
                                                                disabled={isSelectedInOtherRow}
                                                            >
                                                                {p.nama} {isSelectedInOtherRow ? '(sudah dipilih)' : ''}
                                                            </option>
                                                        );
                                                    })}
                                                </select>
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

                                            {/* Jumlah Satuan */}
                                            <div className="space-y-2">
                                                <Label htmlFor={`satuan_${index}`}>
                                                    Jumlah Satuan <span className="text-red-500">*</span>
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

                {/* Total Estimasi Honor */}
                {selectedKegiatanId && totalEstimasi > 0 && (
                    <ContentCard>
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-900/20">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-blue-900 dark:text-blue-200">
                                        Total Estimasi Honor
                                    </p>
                                    <p className="text-xs text-blue-700 dark:text-blue-400">
                                        Untuk {jumlahPetugas} petugas
                                    </p>
                                </div>
                                <p className="text-2xl font-bold text-blue-900 dark:text-blue-200">
                                    {formatCurrency(totalEstimasi)}
                                </p>
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
