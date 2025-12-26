import AppLayout from '@/layouts/app-layout'
import { ContentCard } from '@/components/content-card'
import { PageHeader } from '@/components/page-header'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { type BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/react'
import { ArrowLeft, Save, X, Loader2 } from 'lucide-react'
import { useState } from 'react'

interface DasarHukum {
    id: number
    kategori: string
    instansi: string | null
    nomor: string
    tentang: string
    tahun: number
    status: string
}

interface Kegiatan {
    id: number
    hashed_id: string
    kode_kegiatan: string
    nama_kegiatan: string
    jenis_kegiatan: 'sensus' | 'survei'
    tahun_anggaran: number
}

interface PeriodChange {
    bulan: number
    bulan_nama: string
    tahun: number
    added_count: number
    removed_count: number
    total_petugas: number
}

interface PersonnelChangeInfo {
    has_changes: boolean
    sk_number: string
    sk_date: string
    sk_month: string
    sk_year: number
    reference_month: string
    reference_year: number
    first_change_month: string
    last_change_month: string
    change_year: number
    estimated_sk_month: string
    estimated_sk_year: number
    total_changes: number
    changes: PeriodChange[]
}

interface CreateProps {
    kegiatan: Kegiatan
    dasarHukumList: DasarHukum[]
    personnelChangeInfo: PersonnelChangeInfo | null
    oldInput?: {
        nomor_sk?: string
        tanggal_sk?: string
        dasar_hukum_ids?: string[]
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SK KPA', href: '/sk-kpa' },
    { title: 'Buat SK', href: '#' },
]

export default function Create({ kegiatan, dasarHukumList, personnelChangeInfo, oldInput }: CreateProps) {
    const [formData, setFormData] = useState({
        nomor_sk: oldInput?.nomor_sk || '',
        tanggal_sk: oldInput?.tanggal_sk || '',
    })
    const [selectedDasarHukum, setSelectedDasarHukum] = useState<number[]>(
        oldInput?.dasar_hukum_ids?.map(id => parseInt(id)) || []
    )
    const [processing, setProcessing] = useState(false)

    // Debug: Log personnelChangeInfo
    console.log('Personnel Change Info:', personnelChangeInfo)

    const handleSelectAllDasarHukum = () => {
        if (selectedDasarHukum.length === dasarHukumList.length) {
            setSelectedDasarHukum([])
        } else {
            setSelectedDasarHukum(dasarHukumList.map(dh => dh.id))
        }
    }

    const handlePreview = () => {
        if (selectedDasarHukum.length === 0) {
            alert('Pilih minimal 1 dasar hukum')
            return
        }

        if (!formData.nomor_sk || !formData.tanggal_sk) {
            alert('Lengkapi form terlebih dahulu')
            return
        }

        // Create a native form and submit to preview endpoint
        const form = document.createElement('form')
        form.method = 'POST'
        form.action = `/sk-kpa/kegiatan/${kegiatan.hashed_id}/preview`
        form.target = '_blank'
        form.style.display = 'none'

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (csrfToken) {
            const csrfInput = document.createElement('input')
            csrfInput.type = 'hidden'
            csrfInput.name = '_token'
            csrfInput.value = csrfToken
            form.appendChild(csrfInput)
        }

        // Add form data
        const formDataToSubmit = {
            nomor_sk: formData.nomor_sk,
            tanggal_sk: formData.tanggal_sk,
        }

        Object.entries(formDataToSubmit).forEach(([key, value]) => {
            const input = document.createElement('input')
            input.type = 'hidden'
            input.name = key
            input.value = value
            form.appendChild(input)
        })

        // Add selected dasar hukum
        selectedDasarHukum.forEach((id) => {
            const input = document.createElement('input')
            input.type = 'hidden'
            input.name = 'dasar_hukum_ids[]'
            input.value = id.toString()
            form.appendChild(input)
        })

        document.body.appendChild(form)
        form.submit()
        document.body.removeChild(form)
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        setProcessing(true)

        // Create a native form and submit
        const form = document.createElement('form')
        form.method = 'POST'
        form.action = `/sk-kpa/kegiatan/${kegiatan.hashed_id}/generate`
        form.style.display = 'none'

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (csrfToken) {
            const csrfInput = document.createElement('input')
            csrfInput.type = 'hidden'
            csrfInput.name = '_token'
            csrfInput.value = csrfToken
            form.appendChild(csrfInput)
        }

        // Add form data
        const formDataToSubmit = {
            nomor_sk: formData.nomor_sk,
            tanggal_sk: formData.tanggal_sk,
        }

        Object.entries(formDataToSubmit).forEach(([key, value]) => {
            const input = document.createElement('input')
            input.type = 'hidden'
            input.name = key
            input.value = value
            form.appendChild(input)
        })

        // Add selected dasar hukum
        selectedDasarHukum.forEach((id) => {
            const input = document.createElement('input')
            input.type = 'hidden'
            input.name = 'dasar_hukum_ids[]'
            input.value = id.toString()
            form.appendChild(input)
        })

        document.body.appendChild(form)
        form.submit()
        document.body.removeChild(form)

        setTimeout(() => setProcessing(false), 2000)
    }

    const handleDasarHukumToggle = (id: number) => {
        setSelectedDasarHukum((prev) =>
            prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
        )
    }

    const formatDasarHukum = (dh: DasarHukum): string => {
        let namaLengkap = ''
        if (dh.kategori === 'undang_undang') {
            namaLengkap = 'Undang-Undang'
        } else if (dh.kategori === 'peraturan_pemerintah') {
            namaLengkap = 'Peraturan Pemerintah'
        } else if (dh.kategori === 'peraturan_presiden') {
            namaLengkap = 'Peraturan Presiden'
        } else if (dh.kategori === 'peraturan_menteri_badan') {
            if (dh.instansi && dh.instansi.toLowerCase().startsWith('badan')) {
                namaLengkap = `Peraturan ${dh.instansi}`
            } else {
                namaLengkap = `Peraturan Menteri ${dh.instansi}`
            }
        } else if (dh.kategori === 'keputusan_menteri_kepala_badan') {
            if (dh.instansi && dh.instansi.toLowerCase().startsWith('badan')) {
                namaLengkap = `Keputusan Kepala ${dh.instansi}`
            } else {
                namaLengkap = `Keputusan Menteri ${dh.instansi}`
            }
        }

        return `${namaLengkap} Nomor ${dh.nomor} Tahun ${dh.tahun} tentang ${dh.tentang}`
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Buat SK - ${kegiatan.nama_kegiatan}`} />

            <PageHeader
                title="Generate SK Petugas"
                description={`Buat SK KPA untuk ${kegiatan.nama_kegiatan}`}
            >
                <Button variant="outline" asChild>
                    <Link href="/sk-kpa">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            <ContentCard>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Personnel Change Info for SK Perubahan */}
                    {personnelChangeInfo ? (
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/20">
                            <div className="flex items-start gap-3">
                                <div className="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/40 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="16" x2="12" y2="12"/>
                                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    </svg>
                                </div>
                                <div className="flex-1 space-y-3">
                                    <h4 className="font-semibold text-blue-900 dark:text-blue-100">
                                        Informasi SK Perubahan
                                    </h4>
                                    
                                    {/* Simplified Info Cards */}
                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="rounded-lg border border-blue-300 bg-white p-3 dark:border-blue-800 dark:bg-blue-950/50">
                                            <div className="text-xs text-blue-600 dark:text-blue-400 mb-1">
                                                SK Terakhir:
                                            </div>
                                            <div className="text-base font-bold text-blue-900 dark:text-blue-100">
                                                {personnelChangeInfo.sk_month} {personnelChangeInfo.sk_year}
                                            </div>
                                        </div>
                                        
                                        {personnelChangeInfo.estimated_sk_month && (
                                            <div className="rounded-lg border border-green-300 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/50">
                                                <div className="text-xs text-green-600 dark:text-green-400 mb-1">
                                                    Perkiraan SK Perubahan:
                                                </div>
                                                <div className="text-base font-bold text-green-900 dark:text-green-100">
                                                    {personnelChangeInfo.estimated_sk_month} {personnelChangeInfo.estimated_sk_year}
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* Detail Perubahan - Only if has changes */}
                                    {personnelChangeInfo.has_changes && personnelChangeInfo.changes.length > 0 && (
                                        <div className="rounded-md border border-blue-300 bg-white/70 p-3 dark:border-blue-800 dark:bg-blue-950/30">
                                            <p className="mb-2 text-xs font-medium text-blue-900 dark:text-blue-100">Detail Perubahan:</p>
                                            <div className="space-y-1">
                                                {personnelChangeInfo.changes.map((change, idx) => (
                                                    <div key={idx} className="flex items-center gap-2 text-xs text-blue-700 dark:text-blue-300">
                                                        <span className="font-medium">{personnelChangeInfo.estimated_sk_month}:</span>
                                                        {change.added_count > 0 && (
                                                            <span className="text-green-600 dark:text-green-400">+{change.added_count}</span>
                                                        )} petugas;
                                                        {change.removed_count > 0 && (
                                                            <span className="text-red-600 dark:text-red-400">-{change.removed_count}</span>
                                                        )}
                                                        <span>Total: ({change.total_petugas} petugas)</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-900/20">
                            <div className="flex items-start gap-3">
                                <div className="rounded-lg bg-green-100 p-2 dark:bg-green-900/40 flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                </div>
                                <div className="flex-1">
                                    <h4 className="font-semibold text-green-900 dark:text-green-100 mb-1">
                                        SK Pertama untuk Kegiatan Ini
                                    </h4>
                                    <p className="text-sm text-green-800 dark:text-green-200">
                                        Ini adalah SK pertama yang akan dibuat untuk kegiatan <strong>{kegiatan.nama_kegiatan}</strong>. Pastikan semua data petugas sudah lengkap dan benar sebelum generate SK.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {/* Kegiatan Info */}
                        <div className="md:col-span-2 rounded-lg bg-neutral-50 p-4 dark:bg-neutral-900">
                            <h3 className="mb-2 font-semibold text-neutral-900 dark:text-white">
                                Informasi Kegiatan
                            </h3>
                            <div className="space-y-1 text-sm">
                                <div>
                                    <span className="text-neutral-600 dark:text-neutral-400">Nama: </span>
                                    <span className="font-medium text-neutral-900 dark:text-white">
                                        {kegiatan.nama_kegiatan}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-neutral-600 dark:text-neutral-400">Kode: </span>
                                    <span className="text-neutral-900 dark:text-white">
                                        {kegiatan.kode_kegiatan}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Nomor SK */}
                        <div className="space-y-2">
                            <Label htmlFor="nomor_sk">
                                Nomor SK <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="nomor_sk"
                                required
                                value={formData.nomor_sk}
                                onChange={(e) => setFormData({ ...formData, nomor_sk: e.target.value })}
                                placeholder="Contoh: 053"
                            />
                        </div>

                        {/* Tanggal SK */}
                        <div className="space-y-2">
                            <Label htmlFor="tanggal_sk">
                                Tanggal SK <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="tanggal_sk"
                                type="date"
                                required
                                value={formData.tanggal_sk}
                                onChange={(e) => setFormData({ ...formData, tanggal_sk: e.target.value })}
                            />
                        </div>
                    </div>

                    {/* Dasar Hukum Selection */}
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <Label>
                                Pilih Dasar Hukum <span className="text-red-500">*</span>
                            </Label>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleSelectAllDasarHukum}
                            >
                                {selectedDasarHukum.length === dasarHukumList.length ? 'Batalkan Semua' : 'Pilih Semua'}
                            </Button>
                        </div>
                        <div className="max-h-60 space-y-2 overflow-y-auto rounded-lg border border-neutral-200 p-4 dark:border-neutral-800">
                            {dasarHukumList.map((dh) => (
                                <div key={dh.id} className="flex items-start gap-3">
                                    <Checkbox
                                        id={`dh-${dh.id}`}
                                        checked={selectedDasarHukum.includes(dh.id)}
                                        onCheckedChange={() => handleDasarHukumToggle(dh.id)}
                                        className="mt-0.5 flex-shrink-0"
                                    />
                                    <label
                                        htmlFor={`dh-${dh.id}`}
                                        className="cursor-pointer text-sm text-neutral-900 dark:text-white break-words flex-1"
                                    >
                                        {formatDasarHukum(dh)}
                                    </label>
                                </div>
                            ))}
                        </div>
                        {selectedDasarHukum.length === 0 && (
                            <p className="text-sm text-red-500">Pilih minimal 1 dasar hukum</p>
                        )}
                    </div>

                    {/* Submit Buttons */}
                    <div className="flex items-center justify-end gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                        <Button type="button" variant="outline" asChild className="gap-2 min-w-[180px]">
                            <Link href="/sk-kpa">
                                <X className="h-5 w-5" />
                                Batal
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handlePreview}
                            disabled={processing || selectedDasarHukum.length === 0}
                            className="gap-2"
                        >
                            Preview SK
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || selectedDasarHukum.length === 0}
                            className="gap-2 min-w-[180px]"
                        >
                            {processing ? (
                                <>
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                    Generating...
                                </>
                            ) : (
                                <>
                                    <Save className="h-5 w-5" />
                                    Generate SK
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </ContentCard>
        </AppLayout>
    )
}
