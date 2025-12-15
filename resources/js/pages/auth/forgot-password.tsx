// Components
import { login } from '@/routes';
import { email } from '@/routes/password';
// (duplikat di bawah, hapus baris ini)
import { LoaderCircle } from 'lucide-react';


import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Head, Form, Link } from '@inertiajs/react';
import { Mail, Shield } from 'lucide-react';
import { store } from '@/actions/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';

export default function ForgotPassword() {
    return (
        <>
            <Head title="Lupa Password" />
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-blue-950">
                {/* Header */}
                <header className="border-b border-neutral-200/50 backdrop-blur-sm dark:border-neutral-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 dark:bg-blue-500">
                                <Shield className="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-neutral-900 dark:text-white">
                                    Manajemen Mitra
                                </h1>
                                <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                    BPS Kota Sawahlunto
                                </p>
                            </div>
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
                                    Lupa Password
                                </h2>
                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Masukkan email Anda untuk menerima link reset password
                                </p>
                            </div>

                            <Form {...store.form()} resetOnSuccess={['email']} className="flex flex-col gap-6">
                                {({ processing, errors, wasSuccessful }) => (
                                    <>
                                        {wasSuccessful && (
                                            <div className="rounded-md bg-green-100 p-4 text-sm text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Link reset password telah dikirim ke email Anda!
                                            </div>
                                        )}
                                        <div className="grid gap-5">
                                            <div className="grid gap-2">
                                                <Label htmlFor="email" className="text-neutral-900 dark:text-neutral-100">
                                                    Email
                                                </Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    name="email"
                                                    required
                                                    autoFocus
                                                    autoComplete="username"
                                                    placeholder="Masukkan email"
                                                    className="h-11"
                                                />
                                                <InputError message={errors.email} />
                                            </div>

                                            <Button
                                                type="submit"
                                                className="h-11 w-full bg-blue-600 text-base font-medium hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                                disabled={processing}
                                                data-test="send-reset-link-button"
                                            >
                                                {processing && <Spinner />}
                                                Kirim Link Reset
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </div>
                    </div>
                </main>

                {/* Footer */}
                <footer className="border-t border-neutral-200/50 py-4 dark:border-neutral-800">
                    <div className="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                        <p className="text-xs text-neutral-600 dark:text-neutral-400">
                            © {new Date().getFullYear()} BPS Kota Sawahlunto
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

