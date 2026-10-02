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

interface CreateProps {
    kategoriOptions: KategoriOption[];
    dasarHukumList: DasarHukumItem[];
}

export default function Create(props: CreateProps) {
    return <DasarHukumFormPage mode="create" {...props} />;
}
