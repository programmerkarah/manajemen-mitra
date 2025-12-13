import AppLayout from '@/layouts/app-layout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { BreadcrumbItem, Sbml } from '@/types'
import { Head, router } from '@inertiajs/react'
import { FormEventHandler, useState } from 'react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'SBML', href: '/sbml' },
    { title: 'Edit SBML', href: '/sbml/edit' },
]

interface SbmlEntry {
    id: number
    jenis_kegiatan: 'sensus' | 'survei'
    status_kepegawaian: 'organik' | 'non_organik'
    jenis_penugasan: 'pcl_ppl' | 'pml' | 'pengolahan' | 'pengawas_pengolahan'
    honor_max: string
}

interface Props {
    sbml: Sbml
    entries: Sbml[]
    tahun: number
}

export default function Edit({ sbml, entries, tahun }: Props) {
    const [keterangan, setKeterangan] = useState(sbml.keterangan || '')
    const [status, setStatus] = useState<'aktif' | 'nonaktif'>(sbml.status)
    const [processing, setProcessing] = useState(false)
    const [errors, setErrors] = useState<any>({})

    // Initialize form entries from fetched data
    const [formEntries, setFormEntries] = useState<SbmlEntry[]>(
        entries.map(entry => ({
            id: entry.id,
            jenis_kegiatan: entry.jenis_kegiatan,
            status_kepegawaian: entry.status_kepegawaian,
            jenis_penugasan: entry.jenis_penugasan,
            honor_max: entry.honor_max.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'),
        }))
    )

    const getJenisKegiatanLabel = (jenis: string) => {
        return jenis === 'sensus' ? 'Sensus' : 'Survei'
    }

    const getStatusKepegawaianLabel = (status: string) => {
        return status === 'organik' ? 'Organik (PNS/PPPK)' : 'Non-Organik'
    }

    const getJenisPenugasanLabel = (jenis: string) => {
        const labels: Record<string, string> = {
            pcl_ppl: 'PCL/PPL (Petugas Pencacahan/Pendataan Lapangan)',
            pml: 'PML (Petugas Pemeriksaan Lapangan)',
            pengolahan: 'Petugas Pengolahan Data',
            pengawas_pengolahan: 'Pengawas Pengolahan',
        }
        return labels[jenis] || jenis
    }

    const formatNumber = (value: string) => {
        const numericValue = value.replace(/\D/g, '')
        return numericValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    }

    const handleHonorChange = (index: number, value: string) => {
        const formatted = formatNumber(value)
        const newEntries = [...formEntries]
        newEntries[index].honor_max = formatted
        setFormEntries(newEntries)
    }

    const submit: FormEventHandler = (e) => {
        e.preventDefault()
        setProcessing(true)
        setErrors({})

        const payload = {
            entries: formEntries.map(entry => ({
                id: entry.id,
                honor_max: parseFloat(entry.honor_max.replace(/\./g, '')) || 0,
            })),
            keterangan,
            status,
        }

        router.put(`/sbml/${sbml.hashed_id}`, payload, {
            onSuccess: () => {
                setProcessing(false)
            },
            onError: (errors) => {
                setErrors(errors)
                setProcessing(false)
            },
        })
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit SBML" />

            <div className="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Edit SBML</h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Edit batas maksimal honor per bulan untuk tahun {tahun}
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="space-y-6">
                            {/* Tahun Anggaran - Read Only */}
                            <div>
                                <Label htmlFor="tahun_anggaran">Tahun Anggaran</Label>
                                <Input
                                    id="tahun_anggaran"
                                    type="text"
                                    value={tahun}
                                    disabled
                                    className="bg-gray-100 dark:bg-gray-900"
                                />
                                <p className="mt-1 text-sm text-gray-500">
                                    Tahun anggaran tidak dapat diubah
                                </p>
                            </div>

                            {/* Table of all combinations */}
                            <div>
                                <Label className="mb-3 block">
                                    Batas Honor Maksimal <span className="text-red-500">*</span>
                                </Label>
                                <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                    No
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                    Jenis Kegiatan
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                    Status Kepegawaian
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                    Jenis Penugasan
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                                    Honor Maksimal (Rp)
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                            {formEntries.map((entry, index) => (
                                                <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                        {index + 1}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                        {getJenisKegiatanLabel(entry.jenis_kegiatan)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                        {getStatusKepegawaianLabel(entry.status_kepegawaian)}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                        {getJenisPenugasanLabel(entry.jenis_penugasan)}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Input
                                                            type="text"
                                                            value={entry.honor_max}
                                                            onChange={(e) => handleHonorChange(index, e.target.value)}
                                                            placeholder="0"
                                                            className="w-full"
                                                        />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {errors.entries && (
                                    <p className="mt-2 text-sm text-red-600">{errors.entries}</p>
                                )}
                            </div>

                            {/* Keterangan */}
                            <div>
                                <Label htmlFor="keterangan">Keterangan</Label>
                                <textarea
                                    id="keterangan"
                                    value={keterangan}
                                    onChange={(e) => setKeterangan(e.target.value)}
                                    rows={3}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                    placeholder="Catatan tambahan (opsional)"
                                />
                            </div>

                            {/* Status */}
                            <div>
                                <Label htmlFor="status">
                                    Status <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="status"
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value as 'aktif' | 'nonaktif')}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                >
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => window.history.back()}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}
