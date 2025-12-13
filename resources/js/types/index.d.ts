import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
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
    roles?: Array<{ id: number; name: string }>;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Kegiatan {
    id: string;
    hashed_id: string;
    kode_kegiatan: string;
    nama_kegiatan: string;
    deskripsi: string | null;
    tahun_anggaran: number;
    pagu_anggaran: number | null;
    pj_user_id: number;
    tanggal_mulai: string;
    tanggal_selesai: string;
    status: 'draft' | 'divalidasi' | 'selesai' | 'dibatalkan';
    tanggal_validasi: string | null;
    created_at: string;
    updated_at: string;
}

export interface Mitra {
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
    status: 'aktif' | 'nonaktif';
    created_at: string;
    updated_at: string;
}

export interface RateHonor {
    id: string;
    hashed_id: string;
    nama: string;
    honor_satuan: number;
    satuan_id: string;
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

export interface AlokasiMitra {
    id: string;
    hashed_id: string;
    kegiatan_id: string;
    mitra_id: string;
    rate_honor_id: string;
    bulan: number;
    tahun: number;
    jumlah_satuan: number;
    total_honor: number;
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
