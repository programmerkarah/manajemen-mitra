import { AuthPublicShell } from '@/components/auth-public-shell';
import { FlashMessage } from '@/components/flash-message';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { request } from '@/routes/password';
import { Head, Link, useForm } from '@inertiajs/react';
import ArrowRight from 'lucide-react/icons/arrow-right';
import Eye from 'lucide-react/icons/eye';
import EyeOff from 'lucide-react/icons/eye-off';

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
        } catch {
            // Fall back to the CSRF token already present in the page.
        }

        loginForm.transform((data) => ({ ...data, _token: csrfToken }));
        loginForm.post('/login', {
            preserveScroll: true,
            onSuccess: () => loginForm.reset('password'),
        });
    };

    const headerAction =
        ssoActive && ssoRegisterUrl ? (
            <a
                href={ssoRegisterUrl}
                className="hidden h-9 items-center rounded-lg border border-border bg-card px-3 text-sm font-medium text-foreground transition-colors hover:bg-muted sm:inline-flex"
            >
                Daftar
            </a>
        ) : (
            <Link
                href="/register"
                className="hidden h-9 items-center rounded-lg border border-border bg-card px-3 text-sm font-medium text-foreground transition-colors hover:bg-muted sm:inline-flex"
            >
                Daftar
            </Link>
        );

    return (
        <>
            <Head title="Masuk" />
            <FlashMessage />

            <AuthPublicShell
                compact
                title="Masuk ke SIMANTIK"
                description={
                    ssoActive
                        ? 'Gunakan akun SSO BPS Anda untuk melanjutkan.'
                        : 'Masukkan username dan password untuk melanjutkan.'
                }
                headerAction={headerAction}
                asideTitle="Akses ruang kerja kegiatan statistik."
                asideDescription="Kelola kegiatan, petugas, dokumen, honor, dan monitoring melalui satu akses yang konsisten."
            >
                {status && (
                    <div className="mb-4 rounded-lg border border-[var(--pastel-green)]/70 bg-[var(--pastel-green)]/15 px-3 py-2.5 text-sm text-foreground">
                        {status}
                    </div>
                )}

                {error && (
                    <div className="mb-4 rounded-lg border border-destructive/40 bg-destructive/10 px-3 py-2.5 text-sm text-destructive">
                        {error}
                    </div>
                )}

                {ssoActive ? (
                    <div className="space-y-4">
                        <div className="rounded-lg border border-border bg-muted p-3.5">
                            <p className="text-sm font-semibold text-foreground">
                                Single Sign-On BPS
                            </p>
                            <p className="mt-1 text-xs leading-5 text-muted-foreground">
                                Anda akan diarahkan ke layanan SSO untuk
                                verifikasi akun.
                            </p>
                        </div>

                        <a
                            href={ssoLoginUrl}
                            data-test="login-sso-button"
                            className="flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            Masuk dengan SSO
                            <ArrowRight className="size-4" />
                        </a>

                        <p className="text-center text-xs leading-5 text-muted-foreground">
                            Autentikasi dan keamanan akun dikelola melalui SSO
                            BPS.
                        </p>

                        <div className="border-t border-border pt-4 text-center text-sm text-muted-foreground sm:hidden">
                            Belum punya akun?{' '}
                            {ssoRegisterUrl ? (
                                <a
                                    href={ssoRegisterUrl}
                                    className="font-semibold text-primary hover:underline"
                                >
                                    Daftar
                                </a>
                            ) : (
                                <Link
                                    href="/register"
                                    className="font-semibold text-primary hover:underline"
                                >
                                    Daftar
                                </Link>
                            )}
                        </div>
                    </div>
                ) : (
                    <form onSubmit={submitLoginForm} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="username">Username</Label>
                            <Input
                                id="username"
                                name="username"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="username"
                                placeholder="Masukkan username"
                                className="h-11"
                                value={loginForm.data.username}
                                onChange={(e) =>
                                    loginForm.setData(
                                        'username',
                                        e.target.value,
                                    )
                                }
                            />
                            <InputError message={loginForm.errors.username} />
                        </div>

                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between gap-3">
                                <Label htmlFor="password">Password</Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="text-xs font-medium text-primary hover:underline"
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
                                        isPasswordVisible ? 'text' : 'password'
                                    }
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Masukkan password"
                                    className="h-11 pr-10"
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
                                            (visible) => !visible,
                                        )
                                    }
                                    className="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
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
                            <InputError message={loginForm.errors.password} />
                        </div>

                        <label className="flex cursor-pointer items-center gap-2.5 text-sm text-muted-foreground">
                            <Checkbox
                                checked={loginForm.data.remember}
                                onCheckedChange={(checked) =>
                                    loginForm.setData(
                                        'remember',
                                        checked === true,
                                    )
                                }
                            />
                            Ingat saya
                        </label>

                        <Button
                            type="submit"
                            className="h-11 w-full"
                            disabled={loginForm.processing}
                            data-test="login-button"
                        >
                            {loginForm.processing && <Spinner />}
                            Masuk
                        </Button>

                        <div className="border-t border-border pt-4 text-center text-sm text-muted-foreground sm:hidden">
                            Belum punya akun?{' '}
                            <Link
                                href="/register"
                                className="font-semibold text-primary hover:underline"
                            >
                                Daftar
                            </Link>
                        </div>
                    </form>
                )}
            </AuthPublicShell>
        </>
    );
}
