import DasarHukumFormPage from './FormPage';

interface KategoriOption {
    value: string;
    label: string;
}

interface DasarHukumItem {
    id: number;
    kategori: string;
    instansi: string | null;
    nomor: string;
    tentang: string;
    tahun: number;
    nomor_ln: string | null;
    tahun_ln: number | null;
    nomor_tln: string | null;
    nomor_bn: string | null;
    tahun_bn: number | null;
    perubahan_count: number;
}

interface DasarHukum {
    id: number;
    kategori: string;
    instansi: string | null;
    nomor: string;
    tentang: string;
    tahun: number;
    status: 'aktif' | 'nonaktif';
    jenis: 'pertama' | 'perubahan';
    induk_id: number | null;
    nomor_ln: string | null;
    tahun_ln: number | null;
    nomor_tln: string | null;
    nomor_bn: string | null;
    tahun_bn: number | null;
}

interface EditProps {
    dasarHukum: DasarHukum;
    kategoriOptions: KategoriOption[];
    dasarHukumList: DasarHukumItem[];
}

export default function Edit(props: EditProps) {
    return <DasarHukumFormPage mode="edit" {...props} />;
}
