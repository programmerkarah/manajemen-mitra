import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/react'
import { FormEventHandler, useState } from 'react'
import { ArrowLeft } from 'lucide-react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'SBML', href: '/sbml' },
    { title: 'Tambah SBML', href: '/sbml/create' },
]

interface SbmlEntry {
    jenis_kegiatan: 'sensus' | 'survei'
    status_kepegawaian: 'organik' | 'non_organik'
    jenis_penugasan: 'pcl_ppl' | 'pml' | 'pengolahan' | 'pengawas_pengolahan'
    honor_max: string
}

export default function Create() {
    const currentYear = new Date().getFullYear()
    const tahunOptions = Array.from({ length: 8 }, (_, i) => currentYear - 5 + i)

    const [tahun, setTahun] = useState(currentYear)
    const [keterangan, setKeterangan] = useState('')
    const [status, setStatus] = useState<'aktif' | 'nonaktif'>('aktif')

    // Define all 15 combinations in the order specified
    const initialEntries: SbmlEntry[] = [
        // Survei - Non Organik
        { jenis_kegiatan: 'survei', status_kepegawaian: 'non_organik', jenis_penugasan: 'pcl_ppl', honor_max: '' },
        { jenis_kegiatan: 'survei', status_kepegawaian: 'non_organik', jenis_penugasan: 'pml', honor_max: '' },
        { jenis_kegiatan: 'survei', status_kepegawaian: 'non_organik', jenis_penugasan: 'pengolahan', honor_max: '' },
        // Survei - Organik
        { jenis_kegiatan: 'survei', status_kepegawaian: 'organik', jenis_penugasan: 'pcl_ppl', honor_max: '' },
        { jenis_kegiatan: 'survei', status_kepegawaian: 'organik', jenis_penugasan: 'pml', honor_max: '' },
        { jenis_kegiatan: 'survei', status_kepegawaian: 'organik', jenis_penugasan: 'pengolahan', honor_max: '' },
        { jenis_kegiatan: 'survei', status_kepegawaian: 'organik', jenis_penugasan: 'pengawas_pengolahan', honor_max: '' },
        // Sensus - Non Organik
        { jenis_kegiatan: 'sensus', status_kepegawaian: 'non_organik', jenis_penugasan: 'pcl_ppl', honor_max: '' },
        { jenis_kegiatan: 'sensus', status_kepegawaian: 'non_organik', jenis_penugasan: 'pml', honor_max: '' },
        { jenis_kegiatan: 'sensus', status_kepegawaian: 'non_organik', jenis_penugasan: 'pengolahan', honor_max: '' },
        { jenis_kegiatan: 'sensus', status_kepegawaian: 'non_organik', jenis_penugasan: 'pengawas_pengolahan', honor_max: '' },
        // Sensus - Organik
        { jenis_kegiatan: 'sensus', status_kepegawaian: 'organik', jenis_penugasan: 'pcl_ppl', honor_max: '' },
        { jenis_kegiatan: 'sensus', status_kepegawaian: 'organik', jenis_penugasan: 'pml', honor_max: '' },
        { jenis_kegiatan: 'sensus', status_kepegawaian: 'organik', jenis_penugasan: 'pengolahan', honor_max: '' },
        { jenis_kegiatan: 'sensus', status_kepegawaian: 'organik', jenis_penugasan: 'pengawas_pengolahan', honor_max: '' },
    ]

    const [entries, setEntries] = useState<SbmlEntry[]>(initialEntries)
    const [processing, setProcessing] = useState(false)
    const [errors, setErrors] = useState<any>({})

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
        // Remove non-numeric characters
        const numericValue = value.replace(/\D/g, '')
        // Format with thousand separators
        return numericValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    }

    const handleHonorChange = (index: number, value: string) => {
        const formatted = formatNumber(value)
        const newEntries = [...entries]
        newEntries[index].honor_max = formatted
        setEntries(newEntries)
    }

    const submit: FormEventHandler = (e) => {
        e.preventDefault()
        setProcessing(true)
        setErrors({})

        // Convert entries to API format
        const payload = {
            tahun_anggaran: tahun,
            entries: entries.map(entry => ({
                jenis_kegiatan: entry.jenis_kegiatan,
                status_kepegawaian: entry.status_kepegawaian,
                jenis_penugasan: entry.jenis_penugasan,
                honor_max: parseFloat(entry.honor_max.replace(/\./g, '')) || 0,
            })),
            keterangan,
            status,
        }

        // Use Inertia router to post
        router.post('/sbml', payload, {
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
            <Head title="Tambah SBML" />

            <div className="space-y-6">
                <PageHeader
                    title="Tambah SBML"
                    description="Tentukan batas maksimal honor per bulan untuk semua kategori"
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/sbml">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={submit} className="space-y-6">
                    <ContentCard>
                        <div className="space-y-6">
                            {/* Tahun Anggaran */}
                            <div>
                                <Label htmlFor="tahun_anggaran">
                                    Tahun Anggaran <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="tahun_anggaran"
                                    value={tahun}
                                    onChange={(e) => setTahun(parseInt(e.target.value))}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white sm:text-sm"
                                >
                                    {tahunOptions.map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </select>
                                {errors.tahun_anggaran && (
                                    <p className="mt-2 text-sm text-red-600">{errors.tahun_anggaran}</p>
                                )}
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
                                            {entries.map((entry, index) => (
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
                    </ContentCard>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/sbml">Batal</Link>
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
