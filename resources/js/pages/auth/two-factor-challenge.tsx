import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { Form, Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState(false);
    const { data, setData, processing } = useForm({
        code: '',
        recovery_code: '',
    });
    const { errors } = usePage().props as { errors: Record<string, string> };

    return (
        <>
            <Head title="Two Factor Challenge" />
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-blue-950">
                {/* Header */}
                <header className="border-b border-neutral-200/50 backdrop-blur-sm dark:border-neutral-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <a href="/" className="flex items-center gap-3">
                            <AppLogo />
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
                                    <AppLogoIcon className="size-8 fill-current text-white dark:text-black" />
                                </div>
                                <h2 className="text-2xl font-bold text-neutral-900 dark:text-white">
                                    Two Factor Challenge
                                </h2>
                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Masukkan kode autentikasi atau recovery code
                                    Anda
                                </p>
                            </div>

                            <Form method="post" className="flex flex-col gap-6">
                                <div className="grid gap-5">
                                    {!showRecoveryInput ? (
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="code"
                                                className="text-neutral-900 dark:text-neutral-100"
                                            >
                                                Kode Autentikasi
                                            </Label>
                                            <InputOTP
                                                id="code"
                                                name="code"
                                                maxLength={6}
                                                pattern="[0-9]*"
                                                inputMode="numeric"
                                                autoFocus
                                                value={data.code}
                                                onChange={(val: string) =>
                                                    setData('code', val)
                                                }
                                                containerClassName="justify-center"
                                            >
                                                <InputOTPGroup>
                                                    {[...Array(6)].map(
                                                        (_, i) => (
                                                            <InputOTPSlot
                                                                key={i}
                                                                index={i}
                                                            />
                                                        ),
                                                    )}
                                                </InputOTPGroup>
                                            </InputOTP>
                                            <InputError message={errors.code} />
                                            <Button
                                                type="button"
                                                variant="link"
                                                className="mt-2 px-0 text-sm"
                                                onClick={() =>
                                                    setShowRecoveryInput(true)
                                                }
                                            >
                                                Login dengan Recovery Code
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="recovery_code"
                                                className="text-neutral-900 dark:text-neutral-100"
                                            >
                                                Recovery Code
                                            </Label>
                                            <input
                                                id="recovery_code"
                                                name="recovery_code"
                                                type="text"
                                                autoFocus
                                                autoComplete="one-time-code"
                                                placeholder="Recovery code"
                                                className="h-11 w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                value={data.recovery_code}
                                                onChange={(e) =>
                                                    setData(
                                                        'recovery_code',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errors.recovery_code}
                                            />
                                            <Button
                                                type="button"
                                                variant="link"
                                                className="mt-2 px-0 text-sm"
                                                onClick={() =>
                                                    setShowRecoveryInput(false)
                                                }
                                            >
                                                Kembali ke Kode Autentikasi
                                            </Button>
                                        </div>
                                    )}
                                    <Button
                                        type="submit"
                                        className="h-11 w-full bg-blue-600 text-base font-medium hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                        disabled={processing}
                                        data-test="two-factor-challenge-button"
                                    >
                                        Autentikasi
                                    </Button>
                                </div>
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
