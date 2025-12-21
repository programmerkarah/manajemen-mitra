import AppLogo from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head, Link } from '@inertiajs/react';
import { Mail, LogOut, CheckCircle2 } from 'lucide-react';

export default function VerifyEmail({ status }: { status?: string }) {
    return (
        <>
            <Head title="Verifikasi Email" />
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-blue-950">
                {/* Header */}
                <header className="border-b border-neutral-200/50 backdrop-blur-sm dark:border-neutral-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <AppLogo />
                        </Link>
                    </div>
                </header>

                {/* Main Content */}
                <main className="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md">
                        <div className="rounded-2xl border border-neutral-200/70 bg-white p-8 shadow-lg dark:border-neutral-800 dark:bg-neutral-900">
                            {/* Icon & Title */}
                            <div className="mb-8 text-center">
                                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <Mail className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h2 className="text-2xl font-bold text-neutral-900 dark:text-white">
                                    Verifikasi Email Anda
                                </h2>
                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Silakan verifikasi alamat email Anda dengan mengklik link yang baru saja kami kirimkan kepada Anda.
                                </p>
                            </div>

                            {status === 'verification-link-sent' && (
                                <div className="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                                    <div className="flex items-start gap-3">
                                        <CheckCircle2 className="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-green-900 dark:text-green-100">
                                                Link Verifikasi Berhasil Dikirim!
                                            </p>
                                            <p className="mt-1 text-sm text-green-700 dark:text-green-300">
                                                Link verifikasi baru telah dikirim ke alamat email yang Anda daftarkan. Silakan cek inbox atau folder spam Anda.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <Form {...send.form()} className="space-y-4">
                                {({ processing }) => (
                                    <>
                                        <Button 
                                            disabled={processing} 
                                            className="w-full"
                                            size="lg"
                                        >
                                            {processing && <Spinner className="mr-2" />}
                                            {processing ? 'Mengirim...' : 'Kirim Ulang Email Verifikasi'}
                                        </Button>

                                        <div className="pt-4 border-t border-neutral-200 dark:border-neutral-800">
                                            <Link
                                                href={logout()}
                                                className="flex items-center justify-center gap-2 text-sm text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100 transition-colors"
                                            >
                                                <LogOut className="h-4 w-4" />
                                                Keluar
                                            </Link>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </div>

                        {/* Help Text */}
                        <div className="mt-6 text-center">
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                Tidak menerima email?{' '}
                                <span className="text-neutral-900 dark:text-white font-medium">
                                    Cek folder spam Anda
                                </span>
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
