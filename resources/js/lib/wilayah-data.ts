export interface DesaKelurahan {
    kode: string;
    nama: string;
}

export interface KecamatanData {
    kode: string;
    nama: string;
    desaKelurahan: DesaKelurahan[];
}

export const KECAMATAN_LIST: KecamatanData[] = [
    {
        kode: '010',
        nama: 'Silungkang',
        desaKelurahan: [
            { kode: '001', nama: 'Silungkang Oso' },
            { kode: '002', nama: 'Taratak Boncah' },
            { kode: '003', nama: 'Muaro Kalaban' },
            { kode: '004', nama: 'Silungkang Tigo' },
            { kode: '005', nama: 'Silungkang Duo' },
            { kode: '006', nama: 'Muaro Kalaban Selatan' },
        ],
    },
    {
        kode: '020',
        nama: 'Lembah Segar',
        desaKelurahan: [
            { kode: '001', nama: 'Lunto Barat' },
            { kode: '002', nama: 'Lunto Timur' },
            { kode: '003', nama: 'Pasa Kubang' },
            { kode: '004', nama: 'Kubang Tangah' },
            { kode: '005', nama: 'Kubang Utara Sikabu' },
            { kode: '006', nama: 'Pasar' },
            { kode: '007', nama: 'Kubang Sirakuk Utara' },
            { kode: '008', nama: 'Kubang Sirakuk Selatan' },
            { kode: '009', nama: 'Aur Mulyo' },
            { kode: '010', nama: 'Tanah Lapang' },
            { kode: '011', nama: 'Aia Dingin' },
        ],
    },
    {
        kode: '030',
        nama: 'Barangin',
        desaKelurahan: [
            { kode: '001', nama: 'Lumindai' },
            { kode: '002', nama: 'Balai Batu Sandaran' },
            { kode: '003', nama: 'Saringan' },
            { kode: '004', nama: 'Lubang Panjang' },
            { kode: '005', nama: 'Durian I' },
            { kode: '006', nama: 'Durian II' },
            { kode: '007', nama: 'Talago Gunung' },
            { kode: '008', nama: 'Santur' },
            { kode: '009', nama: 'Kolok Mudiak' },
            { kode: '010', nama: 'Kolok Nan Tuo' },
        ],
    },
    {
        kode: '040',
        nama: 'Talawi',
        desaKelurahan: [
            { kode: '001', nama: 'Sikalang' },
            { kode: '002', nama: 'Rantih' },
            { kode: '003', nama: 'Salak' },
            { kode: '004', nama: 'Sijantang Koto' },
            { kode: '005', nama: 'Talawi Hilir' },
            { kode: '006', nama: 'Talawi Mudiak' },
            { kode: '007', nama: 'Bukit Gadang' },
            { kode: '008', nama: 'Batu Tanjung' },
            { kode: '009', nama: 'Kumbayau' },
            { kode: '010', nama: 'Data Mansiang' },
            { kode: '011', nama: 'Tumpuk Tangah' },
        ],
    },
];

export function getDesaByKecamatan(kecamatan: string): DesaKelurahan[] {
    const kec = KECAMATAN_LIST.find((k) => k.nama === kecamatan);
    return kec?.desaKelurahan ?? [];
}
