import { useState } from 'react'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'
import { router } from '@inertiajs/react'
import { FileText } from 'lucide-react'

interface DasarHukum {
    id: number
    kategori: string
    instansi: string | null
    nomor: string
    tentang: string
    tahun: number
    status: string
}

interface GenerateSkDialogProps {
    open: boolean
    onOpenChange: (open: boolean) => void
    kegiatanHashedId: string
    tahun: number
    bulan: number
    dasarHukumList: DasarHukum[]
}

export function GenerateSkDialog({
    open,
    onOpenChange,
    kegiatanHashedId,
    tahun,
    bulan,
    dasarHukumList,
}: GenerateSkDialogProps) {
    const currentYear = new Date().getFullYear()
    const currentDate = new Date().toISOString().split('T')[0]

    const [nomorSk, setNomorSk] = useState('')
    const [tanggalSk, setTanggalSk] = useState(currentDate)
    const [kategoriKeputusan, setKategoriKeputusan] = useState('KEPUTUSAN')
    const [kepalaBps, setKepalaBps] = useState('ARIESWATY')
    const [dipa, setDipa] = useState('DIPA-054.01.2.428001/2025')
    const [tanggalDipa, setTanggalDipa] = useState('2 Desember 2024')
    const [selectedDasarHukum, setSelectedDasarHukum] = useState<number[]>([])
    const [processing, setProcessing] = useState(false)

    const formatDasarHukum = (dh: DasarHukum) => {
        let nama = ''
        if (dh.kategori === 'undang_undang') {
            nama = 'Undang-Undang'
        } else if (dh.kategori === 'peraturan_pemerintah') {
            nama = 'Peraturan Pemerintah'
        } else if (dh.kategori === 'peraturan_presiden') {
            nama = 'Peraturan Presiden'
        } else if (dh.kategori === 'peraturan_menteri_badan') {
            if (dh.instansi && dh.instansi.toLowerCase().startsWith('badan')) {
                nama = `Peraturan ${dh.instansi}`
            } else {
                nama = `Peraturan Menteri ${dh.instansi}`
            }
        } else if (dh.kategori === 'keputusan_menteri_kepala_badan') {
            if (dh.instansi && dh.instansi.toLowerCase().startsWith('badan')) {
                nama = `Keputusan Kepala ${dh.instansi}`
            } else {
                nama = `Keputusan Menteri ${dh.instansi}`
            }
        }
        return `${nama} Nomor ${dh.nomor} Tahun ${dh.tahun}`
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        setProcessing(true)

        const formData = new FormData()
        formData.append('nomor_sk', nomorSk)
        formData.append('tanggal_sk', tanggalSk)
        formData.append('kategori_keputusan', kategoriKeputusan)
        formData.append('kepala_bps', kepalaBps)
        formData.append('dipa', dipa)
        formData.append('tanggal_dipa', tanggalDipa)
        selectedDasarHukum.forEach((id, index) => {
            formData.append(`dasar_hukum_ids[${index}]`, id.toString())
        })

        // Open in new window for PDF preview
        const form = document.createElement('form')
        form.method = 'POST'
        form.action = `/alokasi/periode/${kegiatanHashedId}/${tahun}/${bulan}/generate-sk`
        form.target = '_blank'

        // Add CSRF token
        const csrfInput = document.createElement('input')
        csrfInput.type = 'hidden'
        csrfInput.name = '_token'
        csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        form.appendChild(csrfInput)

        // Add all form data
        for (const [key, value] of formData.entries()) {
            const input = document.createElement('input')
            input.type = 'hidden'
            input.name = key
            input.value = value.toString()
            form.appendChild(input)
        }

        document.body.appendChild(form)
        form.submit()
        document.body.removeChild(form)

        setProcessing(false)
        onOpenChange(false)
    }

    const toggleDasarHukum = (id: number) => {
        setSelectedDasarHukum((prev) =>
            prev.includes(id) ? prev.filter((dhId) => dhId !== id) : [...prev, id]
        )
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Generate SK Petugas</DialogTitle>
                    <DialogDescription>
                        Isi data untuk generate Surat Keputusan Petugas
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="nomor_sk">
                                Nomor SK <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="nomor_sk"
                                value={nomorSk}
                                onChange={(e) => setNomorSk(e.target.value)}
                                placeholder="Contoh: 053"
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="tanggal_sk">
                                Tanggal SK <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="tanggal_sk"
                                type="date"
                                value={tanggalSk}
                                onChange={(e) => setTanggalSk(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="kategori_keputusan">
                            Jenis Keputusan <span className="text-red-500">*</span>
                        </Label>
                        <Select
                            value={kategoriKeputusan}
                            onValueChange={setKategoriKeputusan}
                        >
                            <SelectTrigger id="kategori_keputusan">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="KEPUTUSAN">Keputusan</SelectItem>
                                <SelectItem value="SURAT KEPUTUSAN">
                                    Surat Keputusan
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="kepala_bps">
                            Nama Kepala BPS <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="kepala_bps"
                            value={kepalaBps}
                            onChange={(e) => setKepalaBps(e.target.value)}
                            placeholder="Contoh: ARIESWATY"
                            required
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="dipa">
                                Nomor DIPA <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="dipa"
                                value={dipa}
                                onChange={(e) => setDipa(e.target.value)}
                                placeholder="Contoh: DIPA-054.01.2.428001/2025"
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="tanggal_dipa">
                                Tanggal DIPA <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="tanggal_dipa"
                                value={tanggalDipa}
                                onChange={(e) => setTanggalDipa(e.target.value)}
                                placeholder="Contoh: 2 Desember 2024"
                                required
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label>
                            Pilih Dasar Hukum <span className="text-red-500">*</span>
                        </Label>
                        <div className="space-y-2 max-h-60 overflow-y-auto border rounded-md p-4">
                            {dasarHukumList.map((dh) => (
                                <div key={dh.id} className="flex items-start gap-2">
                                    <Checkbox
                                        id={`dh-${dh.id}`}
                                        checked={selectedDasarHukum.includes(dh.id)}
                                        onCheckedChange={() => toggleDasarHukum(dh.id)}
                                    />
                                    <label
                                        htmlFor={`dh-${dh.id}`}
                                        className="text-sm cursor-pointer flex-1"
                                    >
                                        {formatDasarHukum(dh)} tentang {dh.tentang}
                                    </label>
                                </div>
                            ))}
                        </div>
                        {selectedDasarHukum.length === 0 && (
                            <p className="text-sm text-red-500">
                                Minimal pilih 1 dasar hukum
                            </p>
                        )}
                    </div>

                    <div className="flex items-center justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || selectedDasarHukum.length === 0}
                            className="gap-2"
                        >
                            <FileText className="h-4 w-4" />
                            {processing ? 'Memproses...' : 'Generate SK'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    )
}
