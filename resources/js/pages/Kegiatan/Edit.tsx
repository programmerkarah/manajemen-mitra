import AppLayout from '@/layouts/app-layout'
import { PageHeader } from '@/components/page-header'
import { ContentCard } from '@/components/content-card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/input-error'
import { type BreadcrumbItem, type Kegiatan, type SharedData } from '@/types'
import { Head, Link, useForm, usePage } from '@inertiajs/react'
import { ArrowLeft, Save, X, Loader2 } from 'lucide-react'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kegiatan', href: '/kegiatan' },
    { title: 'Edit Kegiatan', href: '#' },
]

interface User {
    id: number
    name: string
    email: string
}

interface KegiatanEditProps {
    kegiatan: Kegiatan
    ketuaTimUsers: User[]
    tahunOptions: number[]
}

export default function Edit({ kegiatan, ketuaTimUsers, tahunOptions }: KegiatanEditProps) {
    const { auth } = usePage<SharedData>().props
    const isKetuaTim = auth.activeRole?.name === 'ketua_tim'

    // Format tanggal dari Carbon ke Y-m-d format
    const formatDateForInput = (dateString: string | null): string => {
        if (!dateString) return ''
        // Laravel sudah mengirim dalam format Y-m-d, langsung return
        return dateString
    }

    // Format currency untuk display
    const formatCurrency = (value: string): string => {
        const number = value.replace(/\D/g, '')
        return number.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    }

    // Parse currency untuk submit
    const parseCurrency = (value: string): string => {
        return value.replace(/\./g, '')
    }

    // Helper untuk konversi nominal float ke string tanpa desimal
    const nominalToString = (val: number | null | undefined): string => {
        if (val === null || val === undefined) return ''
        // Jika float, bulatkan ke integer (misal 4941000.00 -> '4941000')
        return Math.round(val).toString()
    }

    const { data, setData, put, processing, errors } = useForm({
        kode_kegiatan: kegiatan.kode_kegiatan || '',
        nama_kegiatan: kegiatan.nama_kegiatan || '',
        jenis_kegiatan: kegiatan.jenis_kegiatan || 'survei',
        deskripsi: kegiatan.deskripsi || '',
        tahun_anggaran: kegiatan.tahun_anggaran || new Date().getFullYear(),
        pagu_pencacahan: nominalToString(kegiatan.pagu_pencacahan),
        pagu_listing: nominalToString(kegiatan.pagu_listing),
        has_listing_updating: kegiatan.has_listing_updating || false,
        ketua_tim_user_id: kegiatan.ketua_tim_user_id?.toString() || '',
        tanggal_mulai: formatDateForInput(kegiatan.tanggal_mulai),
        tanggal_selesai: formatDateForInput(kegiatan.tanggal_selesai),
    })

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        // Kirim pagu_pencacahan dan pagu_listing sebagai number jika ada
        const payload = {
            ...data,
            pagu_pencacahan: data.pagu_pencacahan ? Number(data.pagu_pencacahan) : null,
            pagu_listing: data.pagu_listing ? Number(data.pagu_listing) : null,
        }
        put(`/kegiatan/${kegiatan.hashed_id}`, { data: payload })
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Kegiatan - ${kegiatan.nama_kegiatan}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Edit Kegiatan"
                    description="Ubah informasi kegiatan"
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <ContentCard>
                        <div className="space-y-6">
                            {/* Kode Kegiatan - Read Only */}
                            <div className="space-y-2">
                                <Label htmlFor="kode_kegiatan" className="text-base font-semibold">
                                    Kode Kegiatan
                                </Label>
                                <Input
                                    id="kode_kegiatan"
                                    value={data.kode_kegiatan}
                                    disabled
                                    className="bg-gray-100 dark:bg-gray-900 h-11 text-base"
                                />
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    🔒 Kode kegiatan tidak dapat diubah
                                </p>
                            </div>

                            {/* Nama Kegiatan */}
                            <div className="space-y-2">
                                <Label htmlFor="nama_kegiatan" className="text-base font-semibold">
                                    Nama Kegiatan <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="nama_kegiatan"
                                    value={data.nama_kegiatan}
                                    onChange={(e) => setData('nama_kegiatan', e.target.value)}
                                    placeholder="Masukkan nama kegiatan..."
                                    className="h-11 text-base"
                                />
                                <InputError message={errors.nama_kegiatan} className="mt-2" />
                            </div>

                            {/* Jenis Kegiatan */}
                            <div className="space-y-2">
                                <Label htmlFor="jenis_kegiatan" className="text-base font-semibold">
                                    Jenis Kegiatan <span className="text-red-500">*</span>
                                </Label>
                                <select
                                    id="jenis_kegiatan"
                                    value={data.jenis_kegiatan}
                                    onChange={(e) => setData('jenis_kegiatan', e.target.value as 'sensus' | 'survei')}
                                    className="mt-1 block w-full h-11 text-base rounded-lg border-2 border-neutral-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="survei">Survei</option>
                                    <option value="sensus">Sensus</option>
                                </select>
                                <InputError message={errors.jenis_kegiatan} className="mt-2" />
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    💡 Jenis kegiatan akan menentukan rate honor yang tersedia
                                </p>
                            </div>

                            {/* Deskripsi */}
                            <div className="space-y-2">
                                <Label htmlFor="deskripsi" className="text-base font-semibold">
                                    Deskripsi
                                </Label>
                                <Textarea
                                    id="deskripsi"
                                    rows={4}
                                    value={data.deskripsi}
                                    onChange={(e) => setData('deskripsi', e.target.value)}
                                    placeholder="Masukkan deskripsi kegiatan... (opsional)"
                                    className="text-base"
                                />
                                <InputError message={errors.deskripsi} className="mt-2" />
                            </div>

                            {/* Grid untuk 2 kolom */}
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tahun Anggaran */}
                                <div className="space-y-2">
                                    <Label htmlFor="tahun_anggaran" className="text-base font-semibold">
                                        Tahun Anggaran <span className="text-red-500">*</span>
                                    </Label>
                                    <select
                                        id="tahun_anggaran"
                                        value={data.tahun_anggaran}
                                        onChange={(e) =>
                                            setData('tahun_anggaran', parseInt(e.target.value))
                                        }
                                        className="mt-1 block w-full h-11 text-base rounded-lg border-2 border-neutral-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-gray-700 dark:text-white"
                                    >
                                        {tahunOptions.map((tahun) => (
                                            <option key={tahun} value={tahun}>
                                                {tahun}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.tahun_anggaran} className="mt-2" />
                                </div>

                                {/* Pagu Anggaran */}
                                <div className="space-y-2">
                                    <Label htmlFor="pagu_pencacahan" className="text-base font-semibold">
                                        Pagu Pencacahan (Rp)
                                    </Label>
                                    <Input
                                        id="pagu_pencacahan"
                                        value={data.pagu_pencacahan ? formatCurrency(data.pagu_pencacahan) : ''}
                                        onChange={(e) => {
                                            const raw = parseCurrency(e.target.value)
                                            setData('pagu_pencacahan', raw)
                                        }}
                                        placeholder="Masukkan nominal pagu..."
                                        className="h-11 text-base"
                                    />
                                    <InputError message={errors.pagu_pencacahan} className="mt-2" />
                                </div>
                            </div>


                            {/* Tahapan Listing/Updating */}
                            <div className="space-y-2">
                                <label htmlFor="has_listing_updating" className="block text-base font-semibold text-gray-900 dark:text-gray-100">
                                    Apakah kegiatan ini memiliki tahapan Listing/Updating?
                                </label>
                                <div className="mt-3 flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="has_listing_updating"
                                        checked={data.has_listing_updating}
                                        onChange={e => setData('has_listing_updating', e.target.checked)}
                                        className="mt-1 h-5 w-5 rounded border-2 border-neutral-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-gray-700"
                                    />
                                    <span className="text-base text-gray-700 dark:text-gray-300">Aktifkan jika ada tahapan listing/updating sebelum pencacahan/pendataan lapangan.</span>
                                </div>
                            </div>

                            {/* Pagu Listing */}
                            {data.has_listing_updating && (
                                <div className="space-y-2">
                                    <Label htmlFor="pagu_listing" className="text-base font-semibold">
                                        Pagu Listing/Updating (Rp)
                                    </Label>
                                    <Input
                                        id="pagu_listing"
                                        value={data.pagu_listing ? formatCurrency(data.pagu_listing) : ''}
                                        onChange={e => {
                                            const raw = parseCurrency(e.target.value)
                                            setData('pagu_listing', raw)
                                        }}
                                        placeholder="Masukkan nominal pagu listing..."
                                        className="h-11 text-base"
                                    />
                                    <InputError message={errors.pagu_listing} className="mt-2" />
                                </div>
                            )}

                            {/* Ketua Tim - Hidden for ketua_tim role */}
                            {!isKetuaTim && (
                                <div className="space-y-2">
                                    <Label htmlFor="ketua_tim_user_id" className="text-base font-semibold">
                                        Ketua Tim <span className="text-red-500">*</span>
                                    </Label>
                                    <select
                                        id="ketua_tim_user_id"
                                        value={data.ketua_tim_user_id}
                                        onChange={(e) => setData('ketua_tim_user_id', e.target.value)}
                                        className="mt-1 block w-full h-11 text-base rounded-lg border-2 border-neutral-300 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-neutral-700 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="">Pilih Ketua Tim</option>
                                        {ketuaTimUsers.map((user) => (
                                            <option key={user.id} value={user.id}>
                                                {user.name} ({user.email})
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.ketua_tim_user_id} className="mt-2" />
                                </div>
                            )}

                            {/* Grid untuk tanggal */}
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                {/* Tanggal Mulai */}
                                <div className="space-y-2">
                                    <Label htmlFor="tanggal_mulai" className="text-base font-semibold">
                                        Tanggal Mulai <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="tanggal_mulai"
                                        type="date"
                                        value={data.tanggal_mulai}
                                        onChange={(e) => setData('tanggal_mulai', e.target.value)}
                                        className="h-11 text-base"
                                    />
                                    <InputError message={errors.tanggal_mulai} className="mt-2" />
                                </div>

                                {/* Tanggal Selesai */}
                                <div className="space-y-2">
                                    <Label htmlFor="tanggal_selesai" className="text-base font-semibold">
                                        Tanggal Selesai <span className="text-red-500">*</span>
                                    </Label>
                                    <Input
                                        id="tanggal_selesai"
                                        type="date"
                                        value={data.tanggal_selesai}
                                        onChange={(e) => setData('tanggal_selesai', e.target.value)}
                                        className="h-11 text-base"
                                    />
                                    <InputError message={errors.tanggal_selesai} className="mt-2" />
                                </div>
                            </div>
                        </div>
                    </ContentCard>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" asChild className="gap-2" disabled={processing}>
                            <Link href={`/kegiatan/${kegiatan.hashed_id}`}>
                                <X className="h-5 w-5" />
                                Batal
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing} className="gap-2 min-w-[200px]">
                            {processing ? (
                                <>
                                    <Loader2 className="h-5 w-5 animate-spin" />
                                    Menyimpan...
                                </>
                            ) : (
                                <>
                                    <Save className="h-5 w-5" />
                                    Simpan Perubahan
                                </>
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    )
}

