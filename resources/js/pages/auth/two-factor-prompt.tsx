import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { show } from '@/routes/two-factor';
import { Head, Link } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

export default function TwoFactorPrompt() {
    return (
        <AuthLayout
            title="Diperlukan Autentikasi Dua Faktor"
            description="Untuk menjaga keamanan akun Anda, aktifkan autentikasi dua faktor sebelum mengakses aplikasi."
        >
            <Head title="Aktifkan Autentikasi Dua Faktor" />

            <div className="space-y-6">
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                    <div className="flex items-start space-x-3">
                        <ShieldCheck className="mt-0.5 size-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                        <div className="space-y-1">
                            <h3 className="font-semibold text-amber-900 dark:text-amber-100">
                                Peningkatan Keamanan Diperlukan
                            </h3>
                            <p className="text-sm text-amber-800 dark:text-amber-200">
                                Autentikasi dua faktor (2FA) menambah lapisan
                                keamanan pada akun Anda. Anda akan diminta
                                memasukkan kode dari aplikasi autentikator
                                setiap kali login.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-sm font-medium">
                        Cara mengaktifkan 2FA:
                    </h4>
                    <ol className="list-inside list-decimal space-y-2 text-sm text-muted-foreground">
                        <li>
                            Instal aplikasi autentikator (Google Authenticator,
                            Authy, dsb)
                        </li>
                        <li>
                            Pindai kode QR atau masukkan kode setup secara
                            manual
                        </li>
                        <li>Masukkan kode 6 digit untuk konfirmasi</li>
                        <li>Simpan kode recovery Anda di tempat aman</li>
                    </ol>
                </div>

                <div className="pt-4">
                    <Link href={show.url({ query: { required: 'true' } })}>
                        <Button className="w-full" size="lg">
                            <ShieldCheck />
                            Aktifkan Autentikasi Dua Faktor
                        </Button>
                    </Link>
                </div>
            </div>
        </AuthLayout>
    );
}
