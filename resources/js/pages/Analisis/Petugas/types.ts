export interface DistribusiItem {
    [key: string]: unknown;
    label: string;
    value?: string;
    count: number;
}

export interface KecamatanItem {
    [key: string]: unknown;
    kecamatan: string;
    count: number;
}

export interface DesaKelurahanItem {
    [key: string]: unknown;
    desa_kelurahan: string;
    count: number;
}

export interface DistribusiTugasDesaKelurahanItem {
    kecamatan: string;
    desa_kelurahan: string;
    jumlah_petugas: number;
}

export interface PendidikanItem {
    pendidikan: string;
    count: number;
}

export interface AlokasiPerBulan {
    bulan: number;
    jumlah_petugas: number;
    jumlah_kegiatan: number;
}

export interface KegiatanItem {
    id: number;
    nama: string;
    kode: string;
}

export interface PetugasKegiatanItem {
    petugas_id: number;
    petugas_nama: string;
    kegiatan: KegiatanItem[];
    jumlah_kegiatan: number;
}

export interface KegiatanListItem {
    id: number;
    nama_kegiatan: string;
    kode_kegiatan: string;
}

export interface PetugasAlokasiDetail {
    petugas_id: number;
    petugas_nama: string;
    bulan: Record<number, number>;
    honor: Record<number, number>;
    total: number;
    total_honor: number;
}

export interface PetugasListItem {
    id: number;
    nama: string;
}

export interface PetugasBelumDialokasikanItem {
    id: number;
    nama: string;
    kecamatan: string | null;
    jenis_kelamin: string | null;
    telepon: string | null;
}

export interface KegiatanRutinItem {
    kegiatan_id: number;
    nama_kegiatan: string;
    kode_kegiatan: string;
    jumlah_bulan: number;
    bulan_list: string[];
}

export interface PetugasRutinItem {
    petugas_id: number;
    petugas_nama: string;
    jumlah_kegiatan_rutin: number;
    kegiatan_rutin: KegiatanRutinItem[];
}

export interface AnalisisPetugasProps {
    distribusiJenisKelamin: DistribusiItem[];
    distribusiKecamatan: KecamatanItem[];
    distribusiDesaKelurahan: DesaKelurahanItem[];
    distribusiTugasDesaKelurahan: DistribusiTugasDesaKelurahanItem[];
    distribusiUsia: DistribusiItem[];
    distribusiPendidikan: PendidikanItem[];
    alokasiPerBulan: AlokasiPerBulan[];
    petugasKegiatan: PetugasKegiatanItem[];
    kegiatanList: KegiatanListItem[];
    petugasAlokasiDetail: PetugasAlokasiDetail[];
    petugasList: PetugasListItem[];
    petugasBelumDialokasikan: PetugasBelumDialokasikanItem[];
    petugasRutin: PetugasRutinItem[];
    totalPetugas: number;
    currentYear: number;
}
