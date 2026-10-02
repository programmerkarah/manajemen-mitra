import AppLogo from '@/components/app-logo';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Head, Link, useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
import { FormEvent } from 'react';

interface RegisterProps {
    ssoActive?: boolean;
    ssoRegisterUrl?: string | null;
}

export default function Register({
    ssoActive = false,
    ssoRegisterUrl,
}: RegisterProps) {
    const registerForm = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const refreshCsrfToken = async (): Promise<string> => {
        const response = await fetch('/csrf-token', {
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Gagal memperbarui CSRF token.');
        }

        const payload = (await response.json()) as { token?: string };
        const token = payload.token;

        if (!token) {
            throw new Error('CSRF token tidak tersedia.');
        }

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        csrfMeta?.setAttribute('content', token);

        return token;
    };

    const submitRegisterForm = async (
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
            // Continue submission; backend will provide localized flash if token is still invalid.
        }

        registerForm.transform((data) => ({
            ...data,
            _token: csrfToken,
        }));

        registerForm.post('/register', {
            preserveScroll: true,
            onSuccess: () => {
                registerForm.reset('password', 'password_confirmation');
            },
        });
    };

    return (
        <>
            <Head title="Daftar" />
            <div className="flex min-h-screen flex-col bg-[#f7f9fc] dark:bg-slate-950">
                {/* Header */}
                <header className="border-b border-slate-200/70 bg-white/75 backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/70">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3">
                            <AppLogo />
                        </Link>
                        <Link
                            href="/login"
                            className="rounded-lg px-4 py-2 text-sm font-medium text-neutral-700 transition-colors hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800"
                        >
                            Masuk
                        </Link>
                    </div>
                </header>

                {/* Main Content */}
                <main className="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                    <div className="w-full max-w-lg">
                        <div className="rounded-[2rem] border border-slate-200/80 bg-white p-7 shadow-sm shadow-slate-900/5 sm:p-9 dark:border-white/10 dark:bg-white/[0.04]">
                            {/* Icon & Title */}
                            <div className="mb-8 text-center">
                                <div className="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                                    <UserPlus className="h-6 w-6 text-white" />
                                </div>
                                <h2 className="text-3xl font-bold tracking-[-0.03em] text-slate-950 dark:text-white">
                                    Daftar Akun
                                </h2>
                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    {ssoActive
                                        ? 'Pendaftaran akun dipusatkan melalui SSO.'
                                        : 'Buat akun baru untuk mengakses aplikasi.'}
                                </p>
                            </div>

                            {ssoActive ? (
                                <div className="space-y-4">
                                    {ssoRegisterUrl ? (
                                        <a
                                            href={ssoRegisterUrl}
                                            className="flex h-12 w-full items-center justify-center rounded-xl bg-blue-600 text-base font-semibold text-white transition hover:bg-blue-700"
                                        >
                                            Lanjutkan Daftar via SSO
                                        </a>
                                    ) : (
                                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800/50 dark:bg-amber-900/30 dark:text-amber-200">
                                            Konfigurasi SSO belum lengkap.
                                            Hubungi administrator.
                                        </div>
                                    )}

                                    <div className="text-center text-sm text-neutral-600 dark:text-neutral-400">
                                        Sudah punya akun?{' '}
                                        <Link
                                            href="/login"
                                            className="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            Masuk sekarang
                                        </Link>
                                    </div>
                                </div>
                            ) : (
                                <form
                                    onSubmit={submitRegisterForm}
                                    className="flex flex-col gap-5"
                                >
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="name"
                                            className="text-neutral-900 dark:text-neutral-100"
                                        >
                                            Nama Lengkap
                                        </Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            name="name"
                                            required
                                            autoFocus
                                            autoComplete="name"
                                            placeholder="Masukkan nama lengkap"
                                            className="dark:bg-white\/5 h-11 rounded-lg bg-white"
                                            value={registerForm.data.name}
                                            onChange={(event) =>
                                                registerForm.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={registerForm.errors.name}
                                        />
                                    </div>

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
                                            autoComplete="username"
                                            placeholder="Masukkan username"
                                            className="dark:bg-white\/5 h-11 rounded-lg bg-white"
                                            value={registerForm.data.username}
                                            onChange={(event) =>
                                                registerForm.setData(
                                                    'username',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                registerForm.errors.username
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="email"
                                            className="text-neutral-900 dark:text-neutral-100"
                                        >
                                            Email
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            required
                                            autoComplete="email"
                                            placeholder="Masukkan email"
                                            className="dark:bg-white\/5 h-11 rounded-lg bg-white"
                                            value={registerForm.data.email}
                                            onChange={(event) =>
                                                registerForm.setData(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={registerForm.errors.email}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password"
                                            className="text-neutral-900 dark:text-neutral-100"
                                        >
                                            Password
                                        </Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                            placeholder="Minimal 8 karakter"
                                            className="dark:bg-white\/5 h-11 rounded-lg bg-white"
                                            value={registerForm.data.password}
                                            onChange={(event) =>
                                                registerForm.setData(
                                                    'password',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                registerForm.errors.password
                                            }
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password_confirmation"
                                            className="text-neutral-900 dark:text-neutral-100"
                                        >
                                            Konfirmasi Password
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                            placeholder="Ulangi password"
                                            className="dark:bg-white\/5 h-11 rounded-lg bg-white"
                                            value={
                                                registerForm.data
                                                    .password_confirmation
                                            }
                                            onChange={(event) =>
                                                registerForm.setData(
                                                    'password_confirmation',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={
                                                registerForm.errors
                                                    .password_confirmation
                                            }
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        className="h-11 w-full rounded-lg bg-blue-600 text-base font-semibold hover:bg-blue-700"
                                        disabled={registerForm.processing}
                                    >
                                        {registerForm.processing && <Spinner />}
                                        Daftar
                                    </Button>

                                    <div className="text-center text-sm text-neutral-600 dark:text-neutral-400">
                                        Sudah punya akun?{' '}
                                        <Link
                                            href="/login"
                                            className="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            Masuk sekarang
                                        </Link>
                                    </div>
                                </form>
                            )}
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
