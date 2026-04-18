import AppLogo from '@/components/app-logo';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/react';
import { LogIn } from 'lucide-react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
    ssoEnabled?: boolean;
    ssoLoginUrl?: string;
    ssoRegisterUrl?: string;
}

export default function Login({
    status,
    canResetPassword,
    canRegister: _canRegister,
    ssoEnabled = false,
    ssoLoginUrl = '/auth/sso/redirect',
    ssoRegisterUrl,
}: LoginProps) {
    return (
        <>
            <Head title="Masuk" />
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-blue-950">
                {/* Header */}
                <header className="border-b border-neutral-200/50 backdrop-blur-sm dark:border-neutral-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <AppLogo />
                        </Link>
                        <a
                            href={ssoRegisterUrl || '/register'}
                            className="rounded-lg px-4 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                        >
                            Daftar
                        </a>
                    </div>
                </header>

                {/* Main Content */}
                <main className="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                    <div className="w-full max-w-md">
                        <div className="rounded-2xl border border-neutral-200/70 bg-white p-8 shadow-lg dark:border-neutral-800 dark:bg-neutral-900">
                            {/* Icon & Title */}
                            <div className="mb-8 text-center">
                                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <LogIn className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h2 className="text-2xl font-bold text-neutral-900 dark:text-white">
                                    Masuk ke Akun Anda
                                </h2>
                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Gunakan akun SSO untuk mengakses aplikasi
                                </p>
                            </div>

                            {status && (
                                <div className="mb-6 rounded-lg bg-green-50 p-4 text-center text-sm font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400">
                                    {status}
                                </div>
                            )}

                            <div className="mb-6 space-y-3">
                                <a
                                    href={ssoLoginUrl}
                                    className="flex h-11 w-full items-center justify-center rounded-lg bg-blue-600 text-base font-medium text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                    data-test="login-sso-button"
                                >
                                    Masuk
                                </a>
                                <p className="text-center text-xs text-neutral-500 dark:text-neutral-400">
                                    Login menggunakan akun SSO terpusat.
                                </p>
                            </div>

                            {!ssoEnabled && (
                                <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300">
                                    Konfigurasi SSO belum aktif. Login lokal
                                    ditampilkan sebagai cadangan.
                                </div>
                            )}

                            {!ssoEnabled && (
                                <Form
                                    {...store.form()}
                                    resetOnSuccess={['password']}
                                    className="flex flex-col gap-6"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid gap-5">
                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor="username"
                                                        className="text-neutral-900 dark:text-neutral-100"
                                                    >
                                                        Username
                                                    </Label>
                                                    <Input
                                                        id="username"
                                                        type="text"
                                                        name="username"
                                                        required
                                                        autoFocus
                                                        tabIndex={1}
                                                        autoComplete="username"
                                                        placeholder="Masukkan username"
                                                        className="h-11"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.username
                                                        }
                                                    />
                                                </div>

                                                <div className="grid gap-2">
                                                    <div className="flex items-center justify-between">
                                                        <Label
                                                            htmlFor="password"
                                                            className="text-neutral-900 dark:text-neutral-100"
                                                        >
                                                            Password
                                                        </Label>
                                                        {canResetPassword && (
                                                            <TextLink
                                                                href={request()}
                                                                className="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                                                tabIndex={5}
                                                            >
                                                                Lupa password?
                                                            </TextLink>
                                                        )}
                                                    </div>
                                                    <Input
                                                        id="password"
                                                        type="password"
                                                        name="password"
                                                        required
                                                        tabIndex={2}
                                                        autoComplete="current-password"
                                                        placeholder="Masukkan password"
                                                        className="h-11"
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.password
                                                        }
                                                    />
                                                </div>

                                                <Button
                                                    type="submit"
                                                    className="h-11 w-full bg-blue-600 text-base font-medium hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                                    tabIndex={4}
                                                    disabled={processing}
                                                    data-test="login-button"
                                                >
                                                    {processing && <Spinner />}
                                                    Masuk
                                                </Button>
                                            </div>
                                        </>
                                    )}
                                </Form>
                            )}

                            <div className="text-center text-sm text-neutral-600 dark:text-neutral-400">
                                Belum punya akun?{' '}
                                <a
                                    href={ssoRegisterUrl || '/register'}
                                    tabIndex={5}
                                    className="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Daftar via SSO
                                </a>
                            </div>
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
