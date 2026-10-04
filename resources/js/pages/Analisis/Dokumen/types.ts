export interface SkPerBulan {
    bulan: number;
    total: number;
    draft: number;
    diterbitkan: number;
    ditandatangani: number;
}

export interface SpkPerBulan {
    bulan: number;
    total: number;
    draft: number;
    diterbitkan: number;
    reguler_diterbitkan: number;
    sensus_utama_diterbitkan: number;
}

export interface KelengkapanSKPerKegiatan {
    kegiatan_id: number;
    nama_kegiatan: string;
    kode_kegiatan: string;
    jenis_kegiatan: string;
    total_sk: number;
    sk_draft: number;
    sk_diterbitkan: number;
    sk_ditandatangani: number;
    status_dokumen: 'belum' | 'sebagian' | 'lengkap';
}

export interface SkDraftLama {
    id: number;
    kegiatan_nama: string;
    kegiatan_kode: string;
    bulan: number;
    tahun: number;
    umur_hari: number;
}

export interface AnalisisDokumenProps {
    skPerBulan: SkPerBulan[];
    spkPerBulan: SpkPerBulan[];
    skTotal: number;
    skDiterbitkan: number;
    skDraft: number;
    spkTotal: number;
    spkDiterbitkan: number;
    spkDraft: number;
    kelengkapanSKPerKegiatan: KelengkapanSKPerKegiatan[];
    skDraftLama: SkDraftLama[];
    currentYear: number;
    availableYears: number[];
}

export interface TrenDokumenItem {
    name: string;
    sk_diterbitkan: number;
    sk_draft: number;
    spk_diterbitkan: number;
    spk_reguler: number;
    spk_sensus_utama: number;
    spk_draft: number;
}
