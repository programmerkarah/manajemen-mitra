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

interface CreateProps {
    kegiatan: Kegiatan
    dasarHukumList: DasarHukum[]
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

export default function Create({ kegiatan, dasarHukumList, oldInput }: CreateProps) {
    const [formData, setFormData] = useState({
        nomor_sk: oldInput?.nomor_sk || '',
        tanggal_sk: oldInput?.tanggal_sk || '',
    })
    const [selectedDasarHukum, setSelectedDasarHukum] = useState<number[]>(
        oldInput?.dasar_hukum_ids?.map(id => parseInt(id)) || []
    )
    const [processing, setProcessing] = useState(false)

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
                                    />
                                    <label
                                        htmlFor={`dh-${dh.id}`}
                                        className="cursor-pointer text-sm text-neutral-900 dark:text-white"
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
