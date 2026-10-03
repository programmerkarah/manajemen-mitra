export interface UtilisasiAnggaran {
    kegiatan_id: number;
    nama_kegiatan: string;
    kode_kegiatan: string;
    jenis_kegiatan: string;
    total_pagu: number;
    total_terpakai: number;
    persentase: number;
}

export interface DistribusiBebanKerja {
    [key: string]: unknown;
    label: string;
    count: number;
}

export interface TrenAlokasi {
    bulan: number;
    jumlah_petugas: number;
    total_honor: number;
    total_kegiatan: number;
}

export interface RingkasanKPI {
    total_pagu: number;
    total_terpakai: number;
    serapan_persen: number;
    total_petugas_aktif: number;
    total_kegiatan_aktif: number;
}

export interface RingkasanJenisKegiatan {
    jenis: string;
    label: string;
    jumlah_kegiatan: number;
    total_pagu: number;
    total_terpakai: number;
    serapan_persen: number;
}

export interface TopPetugas {
    petugas_id: number;
    nama: string;
    jabatan: string | null;
    jumlah_kegiatan: number;
    total_honor: number;
}

export interface AnalisisUmumProps {
    utilisasiAnggaran: UtilisasiAnggaran[];
    distribusiBebanKerja: DistribusiBebanKerja[];
    trenAlokasi: TrenAlokasi[];
    ringkasanKPI: RingkasanKPI;
    ringkasanJenisKegiatan: RingkasanJenisKegiatan[];
    topPetugas: TopPetugas[];
    currentYear: number;
    currentMonth: number;
}
