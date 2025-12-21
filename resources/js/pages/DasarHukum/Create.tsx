import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/react'
import { FormEventHandler, useState } from 'react'
import { ArrowLeft } from 'lucide-react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master', href: '#' },
    { title: 'Dasar Hukum SK', href: '/dasar-hukum' },
    { title: 'Tambah Dasar Hukum', href: '/dasar-hukum/create' },
]

interface KategoriOption {
    value: string
    label: string
}

interface CreateProps {
    kategoriOptions: KategoriOption[]
}

export default function Create({ kategoriOptions }: CreateProps) {
    const currentYear = new Date().getFullYear()

    const [kategori, setKategori] = useState('')
    const [instansi, setInstansi] = useState('')
    const [nomor, setNomor] = useState('')
    const [tentang, setTentang] = useState('')
    const [tahun, setTahun] = useState(currentYear.toString())
    const [status, setStatus] = useState<'aktif' | 'nonaktif'>('aktif')

    const [processing, setProcessing] = useState(false)
    const [errors, setErrors] = useState<any>({})

    // Kategori yang memerlukan instansi
    const kategoriDenganInstansi = [
        'peraturan_menteri_badan',
        'keputusan_menteri_kepala_badan',
    ]
    const needsInstansi = kategoriDenganInstansi.includes(kategori)

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault()
        setProcessing(true)
        setErrors({})

        router.post(
            '/dasar-hukum',
            {
                kategori: kategori,
                instansi: instansi || undefined,
                nomor: nomor,
                tentang: tentang,
                tahun: parseInt(tahun),
                status: status,
            },
            {
                onError: (errors) => {
                    setErrors(errors)
                    setProcessing(false)
                },
                onSuccess: () => {
                    setProcessing(false)
                },
            },
        )
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Dasar Hukum SK" />

            <div className="space-y-6">
                <PageHeader
                    title="Tambah Dasar Hukum SK"
                    description="Tambahkan dasar hukum baru untuk digunakan pada SK KPA"
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/dasar-hukum">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={handleSubmit}>
                    <ContentCard>
                        <div className="space-y-6">
                            {/* Kategori */}
                            <div className="space-y-2">
                                <Label htmlFor="kategori">
                                    Kategori <span className="text-red-500">*</span>
                                </Label>
                                <Select
                                    value={kategori}
                                    onValueChange={setKategori}
                                    disabled={processing}
                                >
                                    <SelectTrigger id="kategori">
                                        <SelectValue placeholder="Pilih Kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {kategoriOptions.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.kategori && (
                                    <p className="text-sm text-red-500">
                                        {errors.kategori}
                                    </p>
                                )}
                            </div>

                            {/* Instansi - Conditional */}
                            {needsInstansi && (
                                <div className="space-y-2">
                                    <Label htmlFor="instansi">
                                        Nama Instansi{' '}
                                        <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="instansi"
                                        type="text"
                                        value={instansi}
                                        onChange={(e) => setInstansi(e.target.value)}
                                        placeholder="Contoh: Keuangan, Badan Pusat Statistik"
                                        disabled={processing}
                                    />
                                    {errors.instansi && (
                                        <p className="text-sm text-red-500">
                                            {errors.instansi}
                                        </p>
                                    )}
                                </div>
                            )}

                            {/* Nomor */}
                            <div className="space-y-2">
                                <Label htmlFor="nomor">
                                    Nomor <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="nomor"
                                    type="text"
                                    value={nomor}
                                    onChange={(e) => setNomor(e.target.value)}
                                    placeholder="Contoh: 16"
                                    disabled={processing}
                                />
                                {errors.nomor && (
                                    <p className="text-sm text-red-500">
                                        {errors.nomor}
                                    </p>
                                )}
                            </div>

                            {/* Tentang */}
                            <div className="space-y-2">
                                <Label htmlFor="tentang">
                                    Tentang <span className="text-red-500">*</span>
                                </Label>
                                <Textarea
                                    id="tentang"
                                    value={tentang}
                                    onChange={(e) => setTentang(e.target.value)}
                                    placeholder="Contoh: Standar Biaya Masukan Tahun Anggaran 2023"
                                    rows={3}
                                    disabled={processing}
                                />
                                {errors.tentang && (
                                    <p className="text-sm text-red-500">
                                        {errors.tentang}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tahun */}
                                <div className="space-y-2">
                                    <Label htmlFor="tahun">
                                        Tahun <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="tahun"
                                        type="number"
                                        value={tahun}
                                        onChange={(e) => setTahun(e.target.value)}
                                        min="1900"
                                        max="2100"
                                        disabled={processing}
                                    />
                                    {errors.tahun && (
                                        <p className="text-sm text-red-500">
                                            {errors.tahun}
                                        </p>
                                    )}
                                </div>

                                {/* Status */}
                                <div className="space-y-2">
                                    <Label htmlFor="status">
                                        Status <span className="text-red-500">*</span>
                                    </Label>
                                    <Select
                                        value={status}
                                        onValueChange={(value) =>
                                            setStatus(value as 'aktif' | 'nonaktif')
                                        }
                                        disabled={processing}
                                    >
                                        <SelectTrigger id="status">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="aktif">Aktif</SelectItem>
                                            <SelectItem value="nonaktif">
                                                Nonaktif
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.status && (
                                        <p className="text-sm text-red-500">
                                            {errors.status}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    {/* Submit Buttons */}
                    <div className="mt-6 flex items-center justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/dasar-hukum')}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
