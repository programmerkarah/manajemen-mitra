import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/input-error'
import { type BreadcrumbItem, type AlokasiPetugas } from '@/types'
import { Head, Link, useForm } from '@inertiajs/react'
import { useEffect, useState } from 'react'
import { ArrowLeft } from 'lucide-react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Alokasi petugas', href: '/alokasi' },
    { title: 'Edit Alokasi', href: '#' },
]

interface Kegiatan {
    id: string
    kode_kegiatan: string
    nama_kegiatan: string
    rate_honor?: {
        posisi: string
        rate: number
        satuan: {
            nama: string
        }
    } | null
}

interface petugas {
    id: string
    nama: string
    nik: string
    email: string
}

interface RateHonor {
    id: string
    nama: string
    honor_satuan: number
    satuan: {
        id: string
        nama: string
    }
}

interface AlokasiEditProps {
    alokasi: AlokasiPetugas
    kegiatans: Kegiatan[]
    Petugas: petugas[]
    rateHonors: RateHonor[]
}

export default function Edit({ alokasi, kegiatans, Petugas, rateHonors }: AlokasiEditProps) {
    const { data, setData, put, processing, errors } = useForm({
        kegiatan_id: alokasi.kegiatan_id || '',
        petugas_id: alokasi.petugas_id || '',
        bulan: alokasi.bulan || new Date().getMonth() + 1,
        tahun: alokasi.tahun || new Date().getFullYear(),
        jumlah_satuan: alokasi.jumlah_satuan.toString() || '',
        catatan: alokasi.catatan || '',
    })

    const [selectedRateHonor, setSelectedRateHonor] = useState<RateHonor | null>(null)
    const [estimatedTotal, setEstimatedTotal] = useState(0)

    // Calculate total based on kegiatan's rate honor
    useEffect(() => {
        const selectedKegiatan = kegiatans.find((k) => k.id === data.kegiatan_id)
        if (selectedKegiatan?.rate_honor && data.jumlah_satuan) {
            setEstimatedTotal(selectedKegiatan.rate_honor.rate * Number(data.jumlah_satuan))
        } else {
            setEstimatedTotal(0)
        }
    }, [data.kegiatan_id, data.jumlah_satuan, kegiatans])

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        put(`/alokasi/${alokasi.hashed_id}`)
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
            <Head title="Edit Alokasi Petugas" />

            <PageHeader
                title="Edit Alokasi Petugas"
                description="Ubah informasi alokasi petugas"
            >
                <Button variant="outline" asChild>
                    <Link href={`/alokasi/${alokasi.hashed_id}`}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Kembali
                    </Link>
                </Button>
            </PageHeader>

            {/* Form */}
            <form onSubmit={handleSubmit}>
                <ContentCard>
                    <div className="space-y-6">
                            {/* Kegiatan */}
                            <div className="space-y-2">
                                <Label htmlFor="kegiatan_id">
                                    Kegiatan <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="kegiatan_id"
                                    value={data.kegiatan_id}
                                    onChange={(e) => setData('kegiatan_id', e.target.value)}
                                    className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                >
                                    <option value="">Pilih Kegiatan</option>
                                    {kegiatans.map((kegiatan) => (
                                        <option key={kegiatan.id} value={kegiatan.id}>
                                            {kegiatan.kode_kegiatan} - {kegiatan.nama_kegiatan}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.kegiatan_id} className="mt-2" />
                            </div>

                            {/* Petugas */}
                            <div className="space-y-2">
                                <Label htmlFor="petugas_id">
                                    Petugas <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="petugas_id"
                                    value={data.petugas_id}
                                    onChange={(e) => setData('petugas_id', e.target.value)}
                                    className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                >
                                    <option value="">Pilih Petugas</option>
                                    {Petugas.map((petugas) => (
                                        <option key={petugas.id} value={petugas.id}>
                                            {petugas.nama} - {petugas.nik}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.petugas_id} className="mt-2" />
                            </div>

                            {/* Info Rate Honor dari Kegiatan */}
                            {kegiatans.find((k) => k.id === data.kegiatan_id)?.rate_honor && (
                                <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                    <p className="text-sm text-blue-700 dark:text-blue-400">
                                        <strong>Rate Honor:</strong>{' '}
                                        {kegiatans.find((k) => k.id === data.kegiatan_id)?.rate_honor?.posisi} -{' '}
                                        {formatCurrency(kegiatans.find((k) => k.id === data.kegiatan_id)?.rate_honor?.rate || 0)}/
                                        {kegiatans.find((k) => k.id === data.kegiatan_id)?.rate_honor?.satuan.nama}
                                    </p>
                                    <p className="mt-1 text-xs text-blue-600 dark:text-blue-500">
                                        Rate honor ditentukan oleh kegiatan yang dipilih
                                    </p>
                                </div>
                            )}

                            {/* Grid untuk periode */}
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Bulan */}
                                <div className="space-y-2">
                                    <Label htmlFor="bulan">
                                        Bulan <span className="text-red-500">*</span>
                                    </Label>
                                    <select
                                        id="bulan"
                                        value={data.bulan}
                                        onChange={(e) =>
                                            setData('bulan', parseInt(e.target.value))
                                        }
                                        className="flex h-10 w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                    >
                                        {months.map((month) => (
                                            <option key={month.value} value={month.value}>
                                                {month.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.bulan} className="mt-2" />
                                </div>

                                {/* Tahun */}
                                <div className="space-y-2">
                                    <Label htmlFor="tahun">
                                        Tahun <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        type="number"
                                        id="tahun"
                                        value={data.tahun}
                                        onChange={(e) =>
                                            setData('tahun', parseInt(e.target.value))
                                        }
                                        min="2020"
                                        max="2099"
                                    />
                                    <InputError message={errors.tahun} className="mt-2" />
                                </div>
                            </div>

                            {/* Jumlah Satuan */}
                            <div className="space-y-2">
                                <Label htmlFor="jumlah_satuan">
                                    Jumlah {selectedRateHonor?.satuan.nama || 'Satuan'}{' '}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    type="number"
                                    id="jumlah_satuan"
                                    value={data.jumlah_satuan}
                                    onChange={(e) => setData('jumlah_satuan', e.target.value)}
                                    placeholder="0"
                                    min="1"
                                />
                                <InputError message={errors.jumlah_satuan} className="mt-2" />
                            </div>

                            {/* Estimasi Total */}
                            {estimatedTotal > 0 && (
                                <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-medium text-blue-900 dark:text-blue-200">
                                            Estimasi Honor:
                                        </span>
                                        <span className="text-lg font-bold text-blue-900 dark:text-blue-200">
                                            {formatCurrency(estimatedTotal)}
                                        </span>
                                    </div>
                                </div>
                            )}

                            {/* Catatan */}
                            <div className="space-y-2">
                                <Label htmlFor="catatan">Catatan</Label>
                                <textarea
                                    id="catatan"
                                    rows={3}
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    className="flex w-full rounded-lg border border-neutral-200/70 bg-white px-3 py-2 text-sm shadow-sm transition-colors hover:border-neutral-300 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                    placeholder="Catatan tambahan (opsional)"
                                />
                                <InputError message={errors.catatan} className="mt-2" />
                            </div>
                        </div>
                    </ContentCard>

                    {/* Footer Buttons */}
                    <div className="flex items-center justify-end gap-3">
                        <Button variant="outline" asChild>
                            <Link href={`/alokasi/${alokasi.hashed_id}`}>Batal</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                    </div>
                </form>
        </AppLayout>
    )
}

