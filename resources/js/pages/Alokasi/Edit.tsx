import AppLayout from '@/layouts/app-layout'
import InputError from '@/components/input-error'
import { type BreadcrumbItem, type AlokasiPetugas } from '@/types'
import { Head, Link, useForm } from '@inertiajs/react'
import { useEffect, useState } from 'react'

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
            <Head title="Edit Alokasi petugas" />

            <div className="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div className="p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-2xl font-semibold text-gray-900 dark:text-white">
                                    Edit Alokasi petugas
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Ubah informasi alokasi petugas
                                </p>
                            </div>
                            <Link
                                href={`/alokasi/${alokasi.hashed_id}`}
                                className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                            >
                                Kembali
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit}>
                    <div className="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                        <div className="space-y-6 p-6">
                            {/* Kegiatan */}
                            <div>
                                <label
                                    htmlFor="kegiatan_id"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Kegiatan <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="kegiatan_id"
                                    value={data.kegiatan_id}
                                    onChange={(e) => setData('kegiatan_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
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

                            {/* petugas */}
                            <div>
                                <label
                                    htmlFor="petugas_id"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    petugas <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="petugas_id"
                                    value={data.petugas_id}
                                    onChange={(e) => setData('petugas_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                >
                                    <option value="">Pilih petugas</option>
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
                                <div className="rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
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
                                <div>
                                    <label
                                        htmlFor="bulan"
                                        className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Bulan <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="bulan"
                                        value={data.bulan}
                                        onChange={(e) =>
                                            setData('bulan', parseInt(e.target.value))
                                        }
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
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
                                <div>
                                    <label
                                        htmlFor="tahun"
                                        className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Tahun <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        id="tahun"
                                        value={data.tahun}
                                        onChange={(e) =>
                                            setData('tahun', parseInt(e.target.value))
                                        }
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        min="2020"
                                        max="2099"
                                    />
                                    <InputError message={errors.tahun} className="mt-2" />
                                </div>
                            </div>

                            {/* Jumlah Satuan */}
                            <div>
                                <label
                                    htmlFor="jumlah_satuan"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Jumlah {selectedRateHonor?.satuan.nama || 'Satuan'}{' '}
                                    <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="jumlah_satuan"
                                    value={data.jumlah_satuan}
                                    onChange={(e) => setData('jumlah_satuan', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
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
                            <div>
                                <label
                                    htmlFor="catatan"
                                    className="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                >
                                    Catatan
                                </label>
                                <textarea
                                    id="catatan"
                                    rows={3}
                                    value={data.catatan}
                                    onChange={(e) => setData('catatan', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    placeholder="Catatan tambahan (opsional)"
                                />
                                <InputError message={errors.catatan} className="mt-2" />
                            </div>

                            {/* Submit Button */}
                            <div className="flex items-center justify-end gap-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                                <Link
                                    href={`/alokasi/${alokasi.hashed_id}`}
                                    className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800"
                                >
                                    Batal
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-gray-800"
                                >
                                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}

