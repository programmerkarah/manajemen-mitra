import AppLogo from '@/components/app-logo';
import { FlashMessage } from '@/components/flash-message';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { request } from '@/routes/password';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, Eye, EyeOff, LockKeyhole } from 'lucide-react';
import { FormEvent, useState } from 'react';

interface LoginProps {
    status?: string;
    error?: string;
    canResetPassword: boolean;
    ssoActive?: boolean;
    ssoLoginUrl?: string;
    ssoRegisterUrl?: string;
}

export default function Login({
    status,
    error,
    canResetPassword,
    ssoActive = false,
    ssoLoginUrl = '/auth/sso/redirect',
    ssoRegisterUrl,
}: LoginProps) {
    const [isPasswordVisible, setIsPasswordVisible] = useState(false);
    const loginForm = useForm({ username: '', password: '', remember: false });

    const refreshCsrfToken = async (): Promise<string> => {
        const response = await fetch('/csrf-token', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error('Gagal memperbarui CSRF token.');
        const payload = (await response.json()) as { token?: string };
        if (!payload.token) throw new Error('CSRF token tidak tersedia.');
        document
            .querySelector('meta[name="csrf-token"]')
            ?.setAttribute('content', payload.token);
        return payload.token;
    };

    const submitLoginForm = async (
        event: FormEvent<HTMLFormElement>,
    ): Promise<void> => {
        event.preventDefault();

        let csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        try {
            csrfToken = await refreshCsrfToken();
        } catch {}

        loginForm.transform((data) => ({ ...data, _token: csrfToken }));
        loginForm.post('/login', {
            preserveScroll: true,
            onSuccess: () => loginForm.reset('password'),
        });
    };

    return (
        <>
            <Head title="Masuk" />
            <FlashMessage />

            <div className="min-h-screen bg-[linear-gradient(180deg,#f8fafc_0%,#f3f6fb_100%)] text-slate-900 dark:bg-[linear-gradient(180deg,#020617_0%,#0f172a_100%)] dark:text-slate-100">
                <header className="border-b border-slate-200/80 bg-white/90 shadow-[0_1px_0_rgba(15,23,42,0.02)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8">
                        <Link href="/" className="flex items-center">
                            <AppLogo />
                        </Link>
                        <div className="text-sm text-slate-500 dark:text-slate-400">
                            Belum punya akun?{' '}
                            {ssoActive && ssoRegisterUrl ? (
                                <a
                                    href={ssoRegisterUrl}
                                    className="font-medium text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    Daftar
                                </a>
                            ) : (
                                <Link
                                    href="/register"
                                    className="font-medium text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    Daftar
                                </Link>
                            )}
                        </div>
                    </div>
                </header>

                <main className="mx-auto flex min-h-[calc(100vh-8rem)] max-w-6xl items-center justify-center px-5 py-10 sm:px-8">
                    <div className="w-full max-w-md">
                        <div className="mb-6">
                            <div className="mb-4 flex size-11 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-700 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300">
                                <LockKeyhole className="size-4" />
                            </div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                Masuk ke SIMANTIK
                            </h1>
                            <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                {ssoActive
                                    ? 'Gunakan akun SSO BPS Anda untuk melanjutkan.'
                                    : 'Masukkan username dan password untuk melanjutkan.'}
                            </p>
                        </div>

                        <div className="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-6 shadow-[0_12px_35px_rgba(15,23,42,0.08)] sm:p-7 dark:border-slate-800 dark:bg-slate-900 dark:shadow-[0_16px_40px_rgba(0,0,0,0.28)]">
                            <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-600 via-emerald-500 to-orange-500" />

                            {status && (
                                <div className="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    {status}
                                </div>
                            )}

                            {error && (
                                <div className="mb-5 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                                    {error}
                                </div>
                            )}

                            {ssoActive ? (
                                <div>
                                    <a
                                        href={ssoLoginUrl}
                                        data-test="login-sso-button"
                                        className="flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-px hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/20"
                                    >
                                        Masuk dengan SSO
                                        <ArrowRight className="size-4" />
                                    </a>
                                    <p className="mt-4 text-center text-xs leading-5 text-slate-500 dark:text-slate-400">
                                        Autentikasi akun dikelola melalui
                                        layanan SSO BPS.
                                    </p>
                                </div>
                            ) : (
                                <form
                                    onSubmit={submitLoginForm}
                                    className="space-y-5"
                                >
                                    <div className="space-y-2">
                                        <Label htmlFor="username">
                                            Username
                                        </Label>
                                        <Input
                                            id="username"
                                            name="username"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="username"
                                            placeholder="Masukkan username"
                                            className="h-11 border-slate-200 bg-slate-50/60 shadow-inner shadow-slate-950/[0.02] focus-visible:bg-white dark:border-slate-700 dark:bg-slate-950/40"
                                            value={loginForm.data.username}
                                            onChange={(e) =>
                                                loginForm.setData(
                                                    'username',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={loginForm.errors.username}
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label htmlFor="password">
                                                Password
                                            </Label>
                                            {canResetPassword && (
                                                <TextLink
                                                    href={request()}
                                                    className="text-xs font-medium text-blue-600 hover:underline dark:text-blue-400"
                                                    tabIndex={5}
                                                >
                                                    Lupa password?
                                                </TextLink>
                                            )}
                                        </div>

                                        <div className="relative">
                                            <Input
                                                id="password"
                                                type={
                                                    isPasswordVisible
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                name="password"
                                                required
                                                tabIndex={2}
                                                autoComplete="current-password"
                                                placeholder="Masukkan password"
                                                className="h-11 border-slate-200 bg-slate-50/60 pr-11 shadow-inner shadow-slate-950/[0.02] focus-visible:bg-white dark:border-slate-700 dark:bg-slate-950/40"
                                                value={loginForm.data.password}
                                                onChange={(e) =>
                                                    loginForm.setData(
                                                        'password',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setIsPasswordVisible(
                                                        (v) => !v,
                                                    )
                                                }
                                                className="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-200"
                                                aria-label={
                                                    isPasswordVisible
                                                        ? 'Sembunyikan password'
                                                        : 'Tampilkan password'
                                                }
                                            >
                                                {isPasswordVisible ? (
                                                    <EyeOff className="size-4" />
                                                ) : (
                                                    <Eye className="size-4" />
                                                )}
                                            </button>
                                        </div>
                                        <InputError
                                            message={loginForm.errors.password}
                                        />
                                    </div>

                                    <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                        <input
                                            type="checkbox"
                                            checked={loginForm.data.remember}
                                            onChange={(e) =>
                                                loginForm.setData(
                                                    'remember',
                                                    e.target.checked,
                                                )
                                            }
                                            className="size-4 rounded border-slate-300"
                                        />
                                        Ingat saya
                                    </label>

                                    <Button
                                        type="submit"
                                        className="h-11 w-full bg-blue-600 font-semibold shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-px hover:bg-blue-700 hover:shadow-md hover:shadow-blue-600/20"
                                        disabled={loginForm.processing}
                                        data-test="login-button"
                                    >
                                        {loginForm.processing && <Spinner />}
                                        Masuk
                                    </Button>
                                </form>
                            )}
                        </div>

                        <p className="mt-6 text-center text-xs text-slate-400">
                            BPS Kota Sawahlunto
                        </p>
                    </div>
                </main>
            </div>
        </>
    );
}
