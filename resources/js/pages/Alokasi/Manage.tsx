import { Head, Link, useForm, router, usePage } from '@inertiajs/react'
import AppLayout from '@/layouts/app-layout'
import { Button } from '@/components/ui/button'
import InputError from '@/components/input-error'
import type { Kegiatan, Petugas, RateHonor, Satuan, AlokasiPetugas, SharedData, BreadcrumbItem } from '@/types'
import { useState } from 'react'

interface Props {
    kegiatan: Kegiatan & {
        penanggung_jawab: {
            id: number
            name: string
            email: string
        }
        rate_honors?: Array<RateHonor & {
            satuan: Satuan
        }>
        alokasi: Array<
            AlokasiPetugas & {
                petugas: Petugas
            }
        >
    }
    petugas: Petugas[]
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alokasi', href: '/alokasi' },
    { title: 'Kelola Alokasi Petugas', href: '#' },
];

interface AlokasiForm {
    petugas_id: string
    jumlah_satuan: string
    catatan: string
}

export default function Manage({ kegiatan, petugas }: Props) {
    const { auth, activeYear } = usePage<SharedData>().props
    
    // Global bulan for all allocations, tahun uses activeYear from global state
    const [selectedBulan, setSelectedBulan] = useState<number>(new Date().getMonth() + 1)
    
    const [alokasiList, setAlokasiList] = useState<AlokasiForm[]>([
        {
            petugas_id: '',
            jumlah_satuan: '',
            catatan: '',
        },
    ])

    // Get available months based on kegiatan dates
    const getAvailableMonths = () => {
        if (!kegiatan.tanggal_mulai || !kegiatan.tanggal_selesai) {
            return Array.from({ length: 12 }, (_, i) => i + 1)
        }

        const startDate = new Date(kegiatan.tanggal_mulai)
        const endDate = new Date(kegiatan.tanggal_selesai)
        const startMonth = startDate.getMonth() + 1
        const endMonth = endDate.getMonth() + 1
        const startYear = startDate.getFullYear()
        const endYear = endDate.getFullYear()

        if (activeYear < startYear || activeYear > endYear) {
            return []
        }

        if (startYear === endYear) {
            return Array.from({ length: endMonth - startMonth + 1 }, (_, i) => startMonth + i)
        }

        if (activeYear === startYear) {
            return Array.from({ length: 12 - startMonth + 1 }, (_, i) => startMonth + i)
        }

        if (activeYear === endYear) {
            return Array.from({ length: endMonth }, (_, i) => i + 1)
        }

        return Array.from({ length: 12 }, (_, i) => i + 1)
    }

    const { data, setData, post, processing, errors, reset } = useForm({
        alokasi: alokasiList.map(item => ({
            ...item,
            bulan: selectedBulan,
            tahun: activeYear,
            jenis_kegiatan: kegiatan.jenis_kegiatan,
        })),
    })

    const addAlokasi = () => {
        const newAlokasi: AlokasiForm = {
            petugas_id: '',
            jumlah_satuan: '',
            catatan: '',
        }
        const updated = [...alokasiList, newAlokasi]
        setAlokasiList(updated)
        setData('alokasi', updated.map(item => ({
            ...item,
            bulan: selectedBulan,
            tahun: activeYear,
            jenis_kegiatan: kegiatan.jenis_kegiatan,
        })))
    }

    const removeAlokasi = (index: number) => {
        const updated = alokasiList.filter((_, i) => i !== index)
        setAlokasiList(updated)
        setData('alokasi', updated.map(item => ({
            ...item,
            bulan: selectedBulan,
            tahun: activeYear,
            jenis_kegiatan: kegiatan.jenis_kegiatan,
        })))
    }

    const updateAlokasi = (index: number, field: keyof AlokasiForm, value: string | number) => {
        const updated = [...alokasiList]
        updated[index] = { ...updated[index], [field]: value }
        setAlokasiList(updated)
        setData('alokasi', updated.map(item => ({
            ...item,
            bulan: selectedBulan,
            tahun: activeYear,
            jenis_kegiatan: kegiatan.jenis_kegiatan,
        })))
    }

    // Update form data when bulan changes
    const handleBulanChange = (bulan: number) => {
        setSelectedBulan(bulan)
        setData('alokasi', alokasiList.map(item => ({
            ...item,
            bulan,
            tahun: activeYear,
            jenis_kegiatan: kegiatan.jenis_kegiatan,
        })))
    }

    const handleSubmitForm = (e: React.FormEvent) => {
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

    const handleSubmitForApproval = (alokasiId: string) => {
        if (confirm('Apakah Anda yakin ingin mengajukan alokasi ini untuk persetujuan?')) {
            router.post(`/alokasi/${alokasiId}/submit`, {}, {
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

    const calculateTotal = (petugasId: string, jumlahSatuan: string) => {
        if (!kegiatan.rate_honors || kegiatan.rate_honors.length === 0 || !jumlahSatuan || !petugasId) {
            return 0
        }

        const jumlah = Number(jumlahSatuan)
        if (isNaN(jumlah) || jumlah <= 0) {
            return 0
        }

        // Find the selected petugas
        const selectedPetugas = petugas.find(p => p.id === petugasId)
        if (!selectedPetugas) {
            return 0
        }

        // Find matching rate honor based on petugas type
        const petugasType = selectedPetugas.jenis_petugas === 'organik' ? 'organik' : 'non_organik'
        const matchingRateHonor = kegiatan.rate_honors.find(rh => rh.status_kepegawaian === petugasType)

        if (!matchingRateHonor) {
            return 0
        }

        const rate = Number(matchingRateHonor.rate)
        if (isNaN(rate)) {
            return 0
        }

        return rate * jumlah
    }

    const getPetugasTypeWarning = (petugasId: string) => {
        if (!petugasId || !kegiatan.rate_honors || kegiatan.rate_honors.length === 0) {
            return null
        }

        const selectedPetugas = petugas.find(p => p.id === petugasId)
        if (!selectedPetugas) {
            return null
        }

        const petugasType = selectedPetugas.jenis_petugas === 'organik' ? 'organik' : 'non_organik'
        const matchingRateHonor = kegiatan.rate_honors.find(rh => rh.status_kepegawaian === petugasType)

        if (!matchingRateHonor) {
            return `Peringatan: Rate honor untuk petugas ${selectedPetugas.jenis_petugas} tidak ditemukan dalam kegiatan ini`
        }

        return null
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Kelola Alokasi petugas - ${kegiatan.nama_kegiatan}`} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Kelola Alokasi petugas
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
                                {formatCurrency(kegiatan.pagu_anggaran || 0)}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Existing Alokasi */}
                {kegiatan.alokasi.length > 0 && (
                    <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                petugas yang Sudah Dialokasikan ({kegiatan.alokasi.length})
                            </h2>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            Petugas
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
                                                    {alokasi.petugas.nama}
                                                </div>
                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                    {alokasi.petugas.nik}
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {alokasi.peran || '-'}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {months[alokasi.bulan - 1]?.label} {alokasi.tahun}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {alokasi.jumlah_satuan} {alokasi.peran || 'OHK'}
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
                                                        <>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleSubmitForApproval(alokasi.hashed_id)}
                                                                className="border-blue-300 text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-400"
                                                            >
                                                                Ajukan
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleDelete(alokasi.hashed_id)}
                                                                className="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400"
                                                            >
                                                                Hapus
                                                            </Button>
                                                        </>
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

                {/* Rate Honor Info */}
                {kegiatan.rate_honors && kegiatan.rate_honors.length > 0 ? (
                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                        <div className="flex items-start">
                            <div className="flex-shrink-0">
                                <svg
                                    className="h-5 w-5 text-blue-400"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div className="ml-3 flex-1">
                                <h3 className="text-sm font-medium text-blue-800 dark:text-blue-300">
                                    Rate Honor untuk Kegiatan Ini
                                </h3>
                                <div className="mt-2 space-y-1 text-sm text-blue-700 dark:text-blue-400">
                                    {kegiatan.rate_honors.map((rateHonor) => (
                                        <p key={rateHonor.id} className="font-semibold">
                                            {rateHonor.posisi} - {formatCurrency(rateHonor.rate)}/
                                            {rateHonor.satuan.nama}
                                        </p>
                                    ))}
                                    <p className="mt-2 font-normal">
                                        Rate honor akan dipilih otomatis berdasarkan jenis petugas (organik/non-organik).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <div className="flex items-start">
                            <div className="flex-shrink-0">
                                <svg
                                    className="h-5 w-5 text-red-400"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div className="ml-3 flex-1">
                                <h3 className="text-sm font-medium text-red-800 dark:text-red-300">
                                    Rate Honor Belum Ditentukan
                                </h3>
                                <div className="mt-2 text-sm text-red-700 dark:text-red-400">
                                    <p>
                                        Silakan set rate honor pada kegiatan terlebih dahulu sebelum menambahkan
                                        alokasi petugas.
                                    </p>
                                    <Link
                                        href={`/kegiatan/${kegiatan.hashed_id}/rate-honor/manage`}
                                        className="mt-2 inline-block font-medium underline"
                                    >
                                        Kelola Rate Honor
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Add New Alokasi Form */}
                <form onSubmit={handleSubmitForm}>
                    <div className="space-y-4">
                        {/* Periode Selection - Global for all allocations */}
                        <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Periode Alokasi
                                </h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    Pilih bulan dan tahun untuk alokasi batch ini
                                </p>
                            </div>
                            <div className="p-6">
                                <div className="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                    <p className="text-sm text-blue-700 dark:text-blue-300">
                                        <span className="font-semibold">Tahun Aktif: {activeYear}</span>
                                        <span className="ml-2 text-xs">
                                            (Gunakan switcher tahun di sidebar untuk mengubah)
                                        </span>
                                    </p>
                                </div>
                                
                                {/* Bulan */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Bulan <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        value={selectedBulan}
                                        onChange={(e) => handleBulanChange(Number(e.target.value))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        required
                                    >
                                        {months
                                            .filter(m => getAvailableMonths().includes(m.value))
                                            .map((month) => (
                                                <option key={month.value} value={month.value}>
                                                    {month.label}
                                                </option>
                                            ))}
                                    </select>
                                    {getAvailableMonths().length === 0 && (
                                        <p className="mt-1 text-sm text-red-600">
                                            Tidak ada bulan yang tersedia untuk tahun {activeYear}. Ubah tahun aktif di sidebar.
                                        </p>
                                    )}
                                    {kegiatan.tanggal_mulai && kegiatan.tanggal_selesai && (
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Periode kegiatan: {new Date(kegiatan.tanggal_mulai).toLocaleDateString('id-ID')} - {new Date(kegiatan.tanggal_selesai).toLocaleDateString('id-ID')}
                                        </p>
                                    )}
                                </div>
                                
                                {errors.alokasi && typeof errors.alokasi === 'string' && (
                                    <InputError message={errors.alokasi} className="mt-2" />
                                )}
                            </div>
                        </div>

                        <div className="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                        Tambah petugas Baru
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
                                                Petugas #{index + 1}
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
                                            {/* petugas */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    Petugas <span className="text-red-500">*</span>
                                                </label>
                                                <select
                                                    value={alokasi.petugas_id}
                                                    onChange={(e) =>
                                                        updateAlokasi(
                                                            index,
                                                            'petugas_id',
                                                            e.target.value
                                                        )
                                                    }
                                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                >
                                                    <option value="">Pilih petugas</option>
                                                    {petugas.map((petugas) => (
                                                        <option key={petugas.id} value={petugas.id}>
                                                            {petugas.nama} - {petugas.nik}
                                                        </option>
                                                    ))}
                                                </select>
                                                <InputError
                                                    message={errors[`alokasi.${index}.petugas_id`]}
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
                                                    Estimasi Honor
                                                </label>
                                                <div className="mt-1 block w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-lg font-bold text-blue-600 dark:border-gray-600 dark:bg-gray-800 dark:text-blue-400">
                                                    {formatCurrency(
                                                        calculateTotal(alokasi.petugas_id, alokasi.jumlah_satuan)
                                                    )}
                                                </div>
                                                {getPetugasTypeWarning(alokasi.petugas_id) && (
                                                    <p className="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                                        {getPetugasTypeWarning(alokasi.petugas_id)}
                                                    </p>
                                                )}
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

