import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { FlashMessage } from '@/components/flash-message';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { request } from '@/routes/password';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    Eye,
    EyeOff,
    LockKeyhole,
} from 'lucide-react';
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
            <div className="min-h-screen bg-[#f7f9fc] text-slate-950 dark:bg-slate-950 dark:text-white">
                <div className="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(460px,42%)]">
                    <aside className="hidden border-r border-slate-200 bg-white lg:flex lg:flex-col dark:border-slate-800 dark:bg-slate-950">
                        <div className="flex h-16 items-center border-b border-slate-200 px-10 dark:border-slate-800">
                            <Link href="/" className="flex items-center"><AppLogo /></Link>
                        </div>
                        <div className="flex flex-1 items-center px-10 xl:px-16">
                            <div className="max-w-xl">
                                <p className="text-sm font-semibold text-blue-600 dark:text-blue-400">Ruang kerja SIMANTIK</p>
                                <h1 className="mt-4 text-4xl font-semibold leading-tight tracking-[-0.035em]">Masuk dan lanjutkan pekerjaan dari titik terakhir.</h1>
                                <p className="mt-5 max-w-lg text-sm leading-7 text-slate-500 dark:text-slate-400">Kegiatan, petugas, dokumen, honor, dan monitoring tersedia dalam satu ruang kerja BPS Kota Sawahlunto.</p>
                                <div className="mt-10 border-t border-slate-200 pt-5 text-xs text-slate-400 dark:border-slate-800">Badan Pusat Statistik Kota Sawahlunto</div>
                            </div>
                        </div>
                    </aside>

                    <main className="flex min-h-screen flex-col">
                        <header className="flex h-20 items-center justify-between border-b border-slate-200/70 bg-white/70 px-5 backdrop-blur sm:px-8 lg:border-0 lg:bg-transparent dark:border-white/10 dark:bg-slate-950/70">
                            <Link
                                href="/"
                                className="flex items-center lg:hidden"
                            >
                                <AppLogo />
                            </Link>
                            <div className="ml-auto text-sm text-slate-500 dark:text-slate-400">
                                Belum punya akun?{' '}
                                {ssoActive && ssoRegisterUrl ? (
                                    <a
                                        href={ssoRegisterUrl}
                                        className="font-semibold text-slate-950 hover:text-blue-600 dark:text-white"
                                    >
                                        Daftar
                                    </a>
                                ) : (
                                    <Link
                                        href="/register"
                                        className="font-semibold text-slate-950 hover:text-blue-600 dark:text-white"
                                    >
                                        Daftar
                                    </Link>
                                )}
                            </div>
                        </header>

                        <div className="flex flex-1 items-center justify-center px-5 py-10 sm:px-8">
                            <div className="w-full max-w-[420px]">
                                <div className="mb-8">
                                    <div className="mb-5 flex size-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        <LockKeyhole className="size-4" />
                                    </div>
                                    <h2 className="text-2xl font-semibold tracking-[-0.025em]">
                                        Selamat datang kembali
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                        {ssoActive
                                            ? 'Masuk menggunakan akun SSO BPS untuk melanjutkan.'
                                            : 'Masukkan kredensial SIMANTIK Anda untuk melanjutkan.'}
                                    </p>
                                </div>

                                {status && (
                                    <div className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                                        {status}
                                    </div>
                                )}
                                {error && (
                                    <div className="mb-5 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                                        {error}
                                    </div>
                                )}

                                {ssoActive ? (
                                    <div className="space-y-5">
                                        <a
                                            href={ssoLoginUrl}
                                            data-test="login-sso-button"
                                            className="flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 text-sm font-semibold text-white transition hover:bg-blue-700"
                                        >
                                            Masuk dengan SSO{' '}
                                            <ArrowRight className="size-4" />
                                        </a>
                                        <div className="flex items-center gap-3 text-xs text-slate-400">
                                            <span className="h-px flex-1 bg-slate-200 dark:bg-white/10" />{' '}
                                            Single Sign-On BPS{' '}
                                            <span className="h-px flex-1 bg-slate-200 dark:bg-white/10" />
                                        </div>
                                        <p className="text-center text-xs leading-5 text-slate-500 dark:text-slate-400">
                                            Autentikasi dan keamanan akun
                                            dikelola secara terpusat melalui
                                            SSO.
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
                                                className="h-11 rounded-lg bg-white dark:bg-white/5"
                                                value={loginForm.data.username}
                                                onChange={(e) =>
                                                    loginForm.setData(
                                                        'username',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    loginForm.errors.username
                                                }
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
                                                        className="text-xs font-semibold text-blue-600"
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
                                                    className="h-11 rounded-lg bg-white pr-12 dark:bg-white/5"
                                                    value={
                                                        loginForm.data.password
                                                    }
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
                                                    className="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white"
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
                                                message={
                                                    loginForm.errors.password
                                                }
                                            />
                                        </div>
                                        <label className="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                                            <input
                                                type="checkbox"
                                                checked={
                                                    loginForm.data.remember
                                                }
                                                onChange={(e) =>
                                                    loginForm.setData(
                                                        'remember',
                                                        e.target.checked,
                                                    )
                                                }
                                                className="size-4 rounded"
                                            />{' '}
                                            Ingat saya
                                        </label>
                                        <Button
                                            type="submit"
                                            className="h-11 w-full rounded-lg bg-blue-600 font-semibold hover:bg-blue-700"
                                            disabled={loginForm.processing}
                                            data-test="login-button"
                                        >
                                            {loginForm.processing && (
                                                <Spinner />
                                            )}{' '}
                                            Masuk{' '}
                                            <ArrowRight className="size-4" />
                                        </Button>
                                    </form>
                                )}
                            </div>
                        </div>
                        <footer className="px-5 py-6 text-center text-xs text-slate-400">
                            © {new Date().getFullYear()} BPS Kota Sawahlunto
                        </footer>
                    </main>
                </div>
            </div>
        </>
    );
}
