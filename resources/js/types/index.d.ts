import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Role {
    id: number;
    name: string;
    display_name: string;
    description?: string;
}

export interface Auth {
    user: User;
    activeRole: Role | null;
    userRoles: Role[];
    emailVerified: boolean;
    twoFactorEnabled: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    activeYear: number;
    availableYears: number[];
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    roles?: Role[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Kegiatan {
    id: string;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    jenis_kegiatan: 'sensus' | 'survei';
    deskripsi: string | null;
    tahun_anggaran: number;
    pagu_anggaran: number | null;
    ketua_tim_user_id: number;
    rate_honor_id: string | null;
    rate_honor_status: 'pending' | 'approved' | 'rejected' | null;
    rate_honor_approved_by: number | null;
    rate_honor_approved_at: string | null;
    rate_honor_notes: string | null;
    tanggal_mulai: string;
    tanggal_selesai: string;
    status: 'draft' | 'divalidasi' | 'selesai' | 'dibatalkan';
    tanggal_validasi: string | null;
    created_at: string;
    updated_at: string;
}

export interface Petugas {
    id: string;
    hashed_id: string;
    nama: string;
    nik: string;
    email: string;
    telepon: string | null;
    alamat: string | null;
    tanggal_lahir: string | null;
    pendidikan: 'SD' | 'SMP' | 'SMA' | 'D3' | 'S1' | 'S2' | 'S3';
    keahlian: string | null;
    tahun_bergabung: number | null;
    jenis_petugas: 'organik' | 'non-organik';
    status: 'aktif' | 'nonaktif';
    created_at: string;
    updated_at: string;
}

export interface RateHonor {
    id: string;
    hashed_id: string;
    posisi: string;
    jenis_penugasan: 'pcl_ppl' | 'pml' | 'pengolahan' | 'pengawas_pengolahan';
    status_kepegawaian: 'organik' | 'non_organik';
    deskripsi: string | null;
    rate: number;
    satuan_id: string;
    tahun_berlaku: number;
    status: 'aktif' | 'nonaktif';
    kegiatan_id?: string | null;
    jenis_kegiatan?: 'sensus' | 'survei' | null;
    created_at: string;
    updated_at: string;
}

export interface Satuan {
    id: string;
    hashed_id: string;
    nama: string;
    created_at: string;
    updated_at: string;
}

export interface AlokasiPetugas {
    id: string;
    hashed_id: string;
    kegiatan_id: string;
    petugas_id: string;
    bulan: number;
    tahun: number;
    jumlah_satuan: number;
    total_honor: number;
    peran: 'pcl_ppl' | 'pml' | 'pengolahan';
    jenis_kegiatan: 'sensus' | 'survei';
    status_kepegawaian: 'organik' | 'non_organik';
    status: 'draft' | 'diajukan' | 'disetujui_pj' | 'disetujui' | 'ditolak';
    submitted_by: number | null;
    submitted_at: string | null;
    approved_by: number | null;
    approved_at: string | null;
    catatan_approval: string | null;
    catatan: string | null;
    created_at: string;
    updated_at: string;
}

export interface Sbml {
    id: number;
    hashed_id: string;
    tahun_anggaran: number;
    jenis_kegiatan: 'sensus' | 'survei';
    status_kepegawaian: 'organik' | 'non_organik';
    jenis_penugasan: 'pcl_ppl' | 'pml' | 'pengolahan' | 'pengawas_pengolahan';
    honor_max: number;
    keterangan: string | null;
    status: 'aktif' | 'nonaktif';
    created_at: string;
    updated_at: string;
}
