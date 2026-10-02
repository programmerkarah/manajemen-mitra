import { AuthPublicShell } from '@/components/auth-public-shell';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Head, Link, useForm } from '@inertiajs/react';
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

        document
            .querySelector('meta[name="csrf-token"]')
            ?.setAttribute('content', token);

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
            // Fall back to the CSRF token already present in the page.
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

    const headerAction = (
        <Link
            href="/login"
            className="hidden h-9 items-center rounded-lg border border-border bg-card px-3 text-sm font-medium text-foreground transition-colors hover:bg-muted sm:inline-flex"
        >
            Masuk
        </Link>
    );

    return (
        <>
            <Head title="Daftar" />

            <AuthPublicShell
                title="Daftar akun"
                description={
                    ssoActive
                        ? 'Pendaftaran akun dipusatkan melalui SSO BPS.'
                        : 'Lengkapi data berikut untuk membuat akun SIMANTIK.'
                }
                headerAction={headerAction}
                asideTitle="Mulai dari satu akun yang konsisten."
                asideDescription="Akun SIMANTIK digunakan untuk mengakses pekerjaan sesuai peran dan kewenangan yang diberikan."
            >
                {ssoActive ? (
                    <div className="space-y-4">
                        {ssoRegisterUrl ? (
                            <a
                                href={ssoRegisterUrl}
                                className="flex h-11 w-full items-center justify-center rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                            >
                                Lanjutkan daftar via SSO
                            </a>
                        ) : (
                            <div className="rounded-lg border border-[var(--pastel-orange)]/70 bg-[var(--pastel-orange)]/15 p-3.5 text-sm text-foreground">
                                Konfigurasi SSO belum lengkap. Hubungi
                                administrator.
                            </div>
                        )}

                        <div className="border-t border-border pt-4 text-center text-sm text-muted-foreground">
                            Sudah punya akun?{' '}
                            <Link
                                href="/login"
                                className="font-semibold text-primary hover:underline"
                            >
                                Masuk sekarang
                            </Link>
                        </div>
                    </div>
                ) : (
                    <form
                        onSubmit={submitRegisterForm}
                        className="grid gap-4 sm:grid-cols-2"
                    >
                        <div className="space-y-1.5 sm:col-span-2">
                            <Label htmlFor="name">Nama lengkap</Label>
                            <Input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autoFocus
                                autoComplete="name"
                                placeholder="Masukkan nama lengkap"
                                className="h-11"
                                value={registerForm.data.name}
                                onChange={(event) =>
                                    registerForm.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={registerForm.errors.name} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="username">Username</Label>
                            <Input
                                id="username"
                                type="text"
                                name="username"
                                required
                                autoComplete="username"
                                placeholder="Masukkan username"
                                className="h-11"
                                value={registerForm.data.username}
                                onChange={(event) =>
                                    registerForm.setData(
                                        'username',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={registerForm.errors.username}
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoComplete="email"
                                placeholder="nama@contoh.id"
                                className="h-11"
                                value={registerForm.data.email}
                                onChange={(event) =>
                                    registerForm.setData(
                                        'email',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={registerForm.errors.email} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autoComplete="new-password"
                                placeholder="Minimal 8 karakter"
                                className="h-11"
                                value={registerForm.data.password}
                                onChange={(event) =>
                                    registerForm.setData(
                                        'password',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={registerForm.errors.password}
                            />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="password_confirmation">
                                Konfirmasi password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                                autoComplete="new-password"
                                placeholder="Ulangi password"
                                className="h-11"
                                value={registerForm.data.password_confirmation}
                                onChange={(event) =>
                                    registerForm.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={
                                    registerForm.errors.password_confirmation
                                }
                            />
                        </div>

                        <div className="sm:col-span-2">
                            <Button
                                type="submit"
                                className="h-11 w-full"
                                disabled={registerForm.processing}
                            >
                                {registerForm.processing && <Spinner />}
                                Daftar
                            </Button>
                        </div>

                        <div className="border-t border-border pt-4 text-center text-sm text-muted-foreground sm:col-span-2 sm:hidden">
                            Sudah punya akun?{' '}
                            <Link
                                href="/login"
                                className="font-semibold text-primary hover:underline"
                            >
                                Masuk sekarang
                            </Link>
                        </div>
                    </form>
                )}
            </AuthPublicShell>
        </>
    );
}
