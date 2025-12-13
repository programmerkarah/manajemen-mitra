import { Head, Link, useForm, router } from '@inertiajs/react'
import AppLayout from '@/layouts/app-layout'
import { Button } from '@/components/ui/button'
import InputError from '@/components/input-error'
import type { Kegiatan, Mitra, RateHonor, Satuan, AlokasiMitra } from '@/types'
import { useState } from 'react'

interface Props {
    kegiatan: Kegiatan & {
        penanggung_jawab: {
            id: number
            name: string
            email: string
        }
        alokasi: Array<
            AlokasiMitra & {
                mitra: Mitra
                rate_honor: RateHonor & {
                    satuan: Satuan
                }
            }
        >
    }
    mitras: Mitra[]
    rateHonors: Array<
        RateHonor & {
            satuan: Satuan
        }
    >
}

interface AlokasiForm {
    mitra_id: string
    rate_honor_id: string
    bulan: number
    tahun: number
    jumlah_satuan: string
    catatan: string
}

export default function Manage({ kegiatan, mitras, rateHonors }: Props) {
    const [alokasiList, setAlokasiList] = useState<AlokasiForm[]>([
        {
            mitra_id: '',
            rate_honor_id: '',
            bulan: new Date().getMonth() + 1,
            tahun: new Date().getFullYear(),
            jumlah_satuan: '',
            catatan: '',
        },
    ])

    const { data, setData, post, processing, errors, reset } = useForm({
        alokasi: alokasiList,
    })

    const addAlokasi = () => {
        const newAlokasi: AlokasiForm = {
            mitra_id: '',
            rate_honor_id: '',
            bulan: new Date().getMonth() + 1,
            tahun: new Date().getFullYear(),
            jumlah_satuan: '',
            catatan: '',
        }
        const updated = [...alokasiList, newAlokasi]
        setAlokasiList(updated)
        setData('alokasi', updated)
    }

    const removeAlokasi = (index: number) => {
        const updated = alokasiList.filter((_, i) => i !== index)
        setAlokasiList(updated)
        setData('alokasi', updated)
    }

    const updateAlokasi = (index: number, field: keyof AlokasiForm, value: string | number) => {
        const updated = [...alokasiList]
        updated[index] = { ...updated[index], [field]: value }
        setAlokasiList(updated)
        setData('alokasi', updated)
    }

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        post(`/alokasi/kegiatan/${kegiatan.hashed_id}/store-multiple`)
    }

    const handleDelete = (alokasiId: string) => {
        if (confirm('Apakah Anda yakin ingin menghapus alokasi ini?')) {
            router.delete(`/alokasi/${alokasiId}`, {
                preserveScroll: true,
            })
        }
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount)
    }

    const calculateTotal = (rateHonorId: string, jumlahSatuan: string) => {
        const rateHonor = rateHonors.find((r) => r.id === rateHonorId)
        if (rateHonor && jumlahSatuan) {
            return rateHonor.honor_satuan * Number(jumlahSatuan)
        }
        return 0
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

    const statusColors = {
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        diajukan: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        disetujui_pj: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        disetujui: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        ditolak: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }

    return (
        <AppLayout>
            <Head title={`Kelola Alokasi Mitra - ${kegiatan.nama_kegiatan}`} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Kelola Alokasi Mitra
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {kegiatan.nama_kegiatan}
                        </p>
                        <p className="text-sm text-gray-500 dark:text-gray-500">
                            {kegiatan.kode_kegiatan}
                        </p>
                    </div>
                    <Link href="/alokasi">
                        <Button variant="outline">Kembali</Button>
                    </Link>
                </div>

                {/* Kegiatan Info */}
                <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Penanggung Jawab
                            </label>
                            <p className="mt-1 text-gray-900 dark:text-white">
                                {kegiatan.penanggung_jawab?.name}
                            </p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tahun Anggaran
                            </label>
                            <p className="mt-1 text-gray-900 dark:text-white">
                                {kegiatan.tahun_anggaran}
                            </p>
                        </div>
                        <div>
                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Pagu Anggaran
                            </label>
                            <p className="mt-1 text-gray-900 dark:text-white">
                                {formatCurrency(kegiatan.pagu_anggaran)}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Existing Alokasi */}
                {kegiatan.alokasi.length > 0 && (
                    <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Mitra yang Sudah Dialokasikan ({kegiatan.alokasi.length})
                            </h2>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Mitra
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Rate Honor
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Periode
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Volume
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Total
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    {kegiatan.alokasi.map((alokasi) => (
                                        <tr key={alokasi.id}>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                    {alokasi.mitra.nama}
                                                </div>
                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                    {alokasi.mitra.nik}
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {alokasi.rate_honor.nama}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {months[alokasi.bulan - 1]?.label} {alokasi.tahun}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {alokasi.jumlah_satuan}{' '}
                                                {alokasi.rate_honor.satuan.nama}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                                {formatCurrency(alokasi.total_honor)}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2 py-1 text-xs font-semibold ${statusColors[alokasi.status as keyof typeof statusColors]}`}
                                                >
                                                    {alokasi.status === 'draft' && 'Draft'}
                                                    {alokasi.status === 'diajukan' && 'Diajukan'}
                                                    {alokasi.status === 'disetujui_pj' &&
                                                        'Disetujui PJ'}
                                                    {alokasi.status === 'disetujui' && 'Disetujui'}
                                                    {alokasi.status === 'ditolak' && 'Ditolak'}
                                                </span>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm">
                                                <div className="flex gap-2">
                                                    <Link href={`/alokasi/${alokasi.hashed_id}`}>
                                                        <Button variant="outline" size="sm">
                                                            Detail
                                                        </Button>
                                                    </Link>
                                                    {alokasi.status === 'draft' && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleDelete(alokasi.id)}
                                                            className="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400"
                                                        >
                                                            Hapus
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Add New Alokasi Form */}
                <form onSubmit={handleSubmit}>
                    <div className="space-y-4">
                        <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                        Tambah Mitra Baru
                                    </h2>
                                    <Button type="button" onClick={addAlokasi} variant="outline">
                                        + Tambah Baris
                                    </Button>
                                </div>
                            </div>

                            <div className="space-y-6 p-6">
                                {alokasiList.map((alokasi, index) => (
                                    <div
                                        key={index}
                                        className="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900"
                                    >
                                        <div className="flex items-center justify-between">
                                            <h3 className="font-medium text-gray-900 dark:text-white">
                                                Mitra #{index + 1}
                                            </h3>
                                            {alokasiList.length > 1 && (
                                                <Button
                                                    type="button"
                                                    onClick={() => removeAlokasi(index)}
                                                    variant="outline"
                                                    size="sm"
                                                    className="border-red-300 text-red-700 hover:bg-red-50"
                                                >
                                                    Hapus
                                                </Button>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            {/* Mitra */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Mitra <span className="text-red-500">*</span>
                                                </label>
                                                <select
                                                    value={alokasi.mitra_id}
                                                    onChange={(e) =>
                                                        updateAlokasi(
                                                            index,
                                                            'mitra_id',
                                                            e.target.value
                                                        )
                                                    }
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                >
                                                    <option value="">Pilih Mitra</option>
                                                    {mitras.map((mitra) => (
                                                        <option key={mitra.id} value={mitra.id}>
                                                            {mitra.nama} - {mitra.nik}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={errors[`alokasi.${index}.mitra_id`]}
                                                    className="mt-2"
                                                />
                                            </div>

                                            {/* Rate Honor */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Rate Honor{' '}
                                                    <span className="text-red-500">*</span>
                                                </label>
                                                <select
                                                    value={alokasi.rate_honor_id}
                                                    onChange={(e) =>
                                                        updateAlokasi(
                                                            index,
                                                            'rate_honor_id',
                                                            e.target.value
                                                        )
                                                    }
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                >
                                                    <option value="">Pilih Rate Honor</option>
                                                    {rateHonors.map((rate) => (
                                                        <option key={rate.id} value={rate.id}>
                                                            {rate.nama} -{' '}
                                                            {formatCurrency(rate.honor_satuan)}/
                                                            {rate.satuan.nama}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={
                                                        errors[`alokasi.${index}.rate_honor_id`]
                                                    }
                                                    className="mt-2"
                                                />
                                            </div>

                                            {/* Bulan */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Bulan <span className="text-red-500">*</span>
                                                </label>
                                                <select
                                                    value={alokasi.bulan}
                                                    onChange={(e) =>
                                                        updateAlokasi(
                                                            index,
                                                            'bulan',
                                                            parseInt(e.target.value)
                                                        )
                                                    }
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                >
                                                    {months.map((month) => (
                                                        <option key={month.value} value={month.value}>
                                                            {month.label}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={errors[`alokasi.${index}.bulan`]}
                                                    className="mt-2"
                                                />
                                            </div>

                                            {/* Tahun */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Tahun <span className="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    value={alokasi.tahun}
                                                    onChange={(e) =>
                                                        updateAlokasi(
                                                            index,
                                                            'tahun',
                                                            parseInt(e.target.value)
                                                        )
                                                    }
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                    min="2020"
                                                    max="2099"
                                                />
                                                <InputError
                                                    message={errors[`alokasi.${index}.tahun`]}
                                                    className="mt-2"
                                                />
                                            </div>

                                            {/* Jumlah Satuan */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Jumlah Satuan{' '}
                                                    <span className="text-red-500">*</span>
                                                </label>
                                                <input
                                                    type="number"
                                                    value={alokasi.jumlah_satuan}
                                                    onChange={(e) =>
                                                        updateAlokasi(
                                                            index,
                                                            'jumlah_satuan',
                                                            e.target.value
                                                        )
                                                    }
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                    placeholder="0"
                                                    min="1"
                                                />
                                                <InputError
                                                    message={errors[`alokasi.${index}.jumlah_satuan`]}
                                                    className="mt-2"
                                                />
                                            </div>

                                            {/* Estimasi Total */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Estimasi Total
                                                </label>
                                                <div className="mt-1 block w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-lg font-bold text-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-blue-400">
                                                    {formatCurrency(
                                                        calculateTotal(
                                                            alokasi.rate_honor_id,
                                                            alokasi.jumlah_satuan
                                                        )
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Catatan */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Catatan
                                            </label>
                                            <textarea
                                                rows={2}
                                                value={alokasi.catatan}
                                                onChange={(e) =>
                                                    updateAlokasi(index, 'catatan', e.target.value)
                                                }
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                placeholder="Catatan tambahan (opsional)"
                                            />
                                            <InputError
                                                message={errors[`alokasi.${index}.catatan`]}
                                                className="mt-2"
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Submit Buttons */}
                            <div className="flex items-center justify-end gap-4 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                                <Link href="/alokasi">
                                    <Button variant="outline">Batal</Button>
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Menyimpan...'
                                        : `Simpan ${alokasiList.length} Alokasi`}
                                </Button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
