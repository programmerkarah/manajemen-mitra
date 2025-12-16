import AppLayout from '@/layouts/app-layout';
import { ContentCard } from '@/components/content-card';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/input-error';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

interface Petugas {
    id: number;
    hashed_id: string;
    nama: string;
    nik: string;
    email: string;
    telepon: string;
    alamat: string;
    pendidikan: string;
    tahun_bergabung: number;
    jenis_petugas: 'organik' | 'non-organik';
    jabatan: string | null;
    golongan: string | null;
    npwp: string | null;
    bank: string | null;
    no_rekening: string | null;
    nama_rekening: string | null;
    status: string;
}

interface EditProps {
    petugas: Petugas;
}

const pendidikanOptions = ['SD', 'SMP', 'SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];

export default function Edit({ petugas }: EditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Petugas', href: '/petugas' },
        { title: 'Edit', href: `/petugas/${petugas.hashed_id}/edit` },
    ];

    const [jenisPetugas, setJenisPetugas] = useState<'organik' | 'non-organik'>(petugas.jenis_petugas);

    const { data, setData, put, processing, errors } = useForm({
        nama: petugas.nama,
        nik: petugas.nik,
        email: petugas.email,
        telepon: petugas.telepon,
        alamat: petugas.alamat,
        pendidikan: petugas.pendidikan,
        tahun_bergabung: petugas.tahun_bergabung,
        jenis_petugas: petugas.jenis_petugas,
        jabatan: petugas.jabatan || (petugas.jenis_petugas === 'non-organik' ? 'Mitra Statistik' : ''),
        golongan: petugas.golongan || (petugas.jenis_petugas === 'non-organik' ? 'Non PNS' : ''),
        npwp: petugas.npwp || '',
        bank: petugas.bank || '',
        no_rekening: petugas.no_rekening || '',
        nama_rekening: petugas.nama_rekening || '',
        status: petugas.status,
    });

    const handleJenisPetugasChange = (value: 'organik' | 'non-organik') => {
        setJenisPetugas(value);
        setData('jenis_petugas', value);
        
        // Auto-set jabatan and golongan for non-organik
        if (value === 'non-organik') {
            setData(prevData => ({
                ...prevData,
                jenis_petugas: value,
                jabatan: 'Mitra Statistik',
                golongan: 'Non PNS',
            }));
        } else {
            // Clear for organik to allow user input
            setData(prevData => ({
                ...prevData,
                jenis_petugas: value,
                jabatan: petugas.jabatan || '',
                golongan: petugas.golongan || '',
            }));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/petugas/${petugas.hashed_id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Petugas - ${petugas.nama}`} />

            <div className="space-y-6">
                <PageHeader
                    title="Edit Petugas"
                    description={`Perbarui informasi untuk ${petugas.nama}`}
                >
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/petugas">
                            <ArrowLeft className="h-4 w-4" />
                            Kembali
                        </Link>
                    </Button>
                </PageHeader>

                <ContentCard>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Nama */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Nama Lengkap <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.nama}
                                    onChange={(e) => setData('nama', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                                {errors.nama && (
                                    <p className="mt-1 text-sm text-red-600">{errors.nama}</p>
                                )}
                            </div>

                            {/* NIK */}
                            <div>
                                <label className="block text-sm font-medium">
                                    NIK/NIP <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.nik}
                                    onChange={(e) => setData('nik', e.target.value)}
                                    maxLength={16}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                                {errors.nik && (
                                    <p className="mt-1 text-sm text-red-600">{errors.nik}</p>
                                )}
                            </div>

                            {/* Email */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Email <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                                {errors.email && (
                                    <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                                )}
                            </div>

                            {/* Telepon */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Telepon <span className="text-red-600">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.telepon}
                                    onChange={(e) => setData('telepon', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                                {errors.telepon && (
                                    <p className="mt-1 text-sm text-red-600">{errors.telepon}</p>
                                )}
                            </div>

                            {/* Pendidikan */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Pendidikan <span className="text-red-600">*</span>
                                </label>
                                <select
                                    value={data.pendidikan}
                                    onChange={(e) => setData('pendidikan', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                >
                                    <option value="">Pilih Pendidikan</option>
                                    {pendidikanOptions.map((option) => (
                                        <option key={option} value={option}>
                                            {option}
                                        </option>
                                    ))}
                                </select>
                                {errors.pendidikan && (
                                    <p className="mt-1 text-sm text-red-600">{errors.pendidikan}</p>
                                )}
                            </div>

                            {/* Tahun Bergabung */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Tahun Bergabung <span className="text-red-600">*</span>
                                </label>
                                <select
                                    value={data.tahun_bergabung}
                                    onChange={(e) => setData('tahun_bergabung', parseInt(e.target.value))}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                >
                                    <option value="">Pilih Tahun</option>
                                    {Array.from({ length: new Date().getFullYear() - 2000 + 2 }, (_, i) => new Date().getFullYear() + 1 - i).map(year => (
                                        <option key={year} value={year}>{year}</option>
                                    ))}
                                </select>
                                {errors.tahun_bergabung && (
                                    <p className="mt-1 text-sm text-red-600">{errors.tahun_bergabung}</p>
                                )}
                            </div>

                            {/* Status */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Status <span className="text-red-600">*</span>
                                </label>
                                <select
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                >
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                                {errors.status && (
                                    <p className="mt-1 text-sm text-red-600">{errors.status}</p>
                                )}
                            </div>

                            {/* Jenis Petugas */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Jenis Petugas <span className="text-red-600">*</span>
                                </label>
                                <select
                                    value={data.jenis_petugas}
                                    onChange={(e) => handleJenisPetugasChange(e.target.value as 'organik' | 'non-organik')}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                >
                                    <option value="organik">Organik</option>
                                    <option value="non-organik">Non-Organik</option>
                                </select>
                                {errors.jenis_petugas && (
                                    <p className="mt-1 text-sm text-red-600">{errors.jenis_petugas}</p>
                                )}
                            </div>

                            {/* Jabatan */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Jabatan
                                </label>
                                <input
                                    type="text"
                                    value={data.jabatan}
                                    onChange={(e) => setData('jabatan', e.target.value)}
                                    disabled={jenisPetugas === 'non-organik'}
                                    placeholder={jenisPetugas === 'non-organik' ? 'Mitra Statistik' : 'Contoh: Statistisi Ahli Pertama'}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800 disabled:bg-neutral-100 disabled:cursor-not-allowed dark:disabled:bg-neutral-900"
                                />
                                {jenisPetugas === 'non-organik' && (
                                    <p className="mt-1 text-xs text-muted-foreground">Jabatan untuk non-organik otomatis: Mitra Statistik</p>
                                )}
                                {errors.jabatan && (
                                    <p className="mt-1 text-sm text-red-600">{errors.jabatan}</p>
                                )}
                            </div>

                            {/* Golongan */}
                            <div>
                                <label className="block text-sm font-medium">
                                    Golongan
                                </label>
                                <input
                                    type="text"
                                    value={data.golongan}
                                    onChange={(e) => setData('golongan', e.target.value)}
                                    disabled={jenisPetugas === 'non-organik'}
                                    placeholder={jenisPetugas === 'non-organik' ? 'Non PNS' : 'Contoh: III/b'}
                                    className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800 disabled:bg-neutral-100 disabled:cursor-not-allowed dark:disabled:bg-neutral-900"
                                />
                                {jenisPetugas === 'non-organik' && (
                                    <p className="mt-1 text-xs text-muted-foreground">Golongan untuk non-organik otomatis: Non PNS</p>
                                )}
                                {errors.golongan && (
                                    <p className="mt-1 text-sm text-red-600">{errors.golongan}</p>
                                )}
                            </div>

                            {/* Alamat - Full Width */}
                            <div className="md:col-span-2">
                                <label className="block text-sm font-medium">
                                    Alamat <span className="text-red-600">*</span>
                                </label>
                                <Textarea
                                    value={data.alamat}
                                    onChange={(e) => setData('alamat', e.target.value)}
                                    rows={3}
                                    className="mt-1"
                                />
                                {errors.alamat && (
                                    <p className="mt-1 text-sm text-red-600">{errors.alamat}</p>
                                )}
                            </div>
                        </div>

                        {/* Data Bank */}
                        <div className="space-y-4 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                            <h3 className="font-semibold">Data Bank (Opsional)</h3>
                            
                            <div className="grid gap-6 md:grid-cols-2">
                                {/* NPWP */}
                                <div>
                                    <label className="block text-sm font-medium">NPWP</label>
                                    <input
                                        type="text"
                                        value={data.npwp}
                                        onChange={(e) => setData('npwp', e.target.value)}
                                        maxLength={15}
                                        className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                    />
                                    {errors.npwp && (
                                        <p className="mt-1 text-sm text-red-600">{errors.npwp}</p>
                                    )}
                                </div>

                                {/* Bank */}
                                <div>
                                    <label className="block text-sm font-medium">Bank</label>
                                    <input
                                        type="text"
                                        value={data.bank}
                                        onChange={(e) => setData('bank', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                    />
                                    {errors.bank && (
                                        <p className="mt-1 text-sm text-red-600">{errors.bank}</p>
                                    )}
                                </div>

                                {/* Nomor Rekening */}
                                <div>
                                    <label className="block text-sm font-medium">Nomor Rekening</label>
                                    <input
                                        type="text"
                                        value={data.no_rekening}
                                        onChange={(e) => setData('no_rekening', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                    />
                                    {errors.no_rekening && (
                                        <p className="mt-1 text-sm text-red-600">{errors.no_rekening}</p>
                                    )}
                                </div>

                                {/* Nama Rekening */}
                                <div>
                                    <label className="block text-sm font-medium">Nama Rekening</label>
                                    <input
                                        type="text"
                                        value={data.nama_rekening}
                                        onChange={(e) => setData('nama_rekening', e.target.value)}
                                        className="mt-1 block w-full rounded-md border border-neutral-300 px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
                                    />
                                    {errors.nama_rekening && (
                                        <p className="mt-1 text-sm text-red-600">{errors.nama_rekening}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="mt-6 flex justify-end gap-3 border-t border-neutral-200 pt-6 dark:border-neutral-800">
                            <Button
                                type="button"
                                variant="outline"
                                asChild
                            >
                                <Link href="/petugas">Batal</Link>
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing}
                            >
                                {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                            </Button>
                        </div>
                    </form>
                </ContentCard>
            </div>
        </AppLayout>
    );
}


