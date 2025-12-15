import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import AuthLayout from '@/layouts/auth-layout';
import { store } from '@/routes/two-factor/login';
import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useMemo, useState } from 'react';

interface TwoFactorChallengeProps {
    isTrustedDevice?: boolean;
}

export default function TwoFactorChallenge({
    isTrustedDevice = false,
}: TwoFactorChallengeProps) {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');
    const [rememberDevice, setRememberDevice] = useState<boolean>(true);

    const authConfigContent = useMemo(() => {
        // { title, description, toggleText }
        if (showRecoveryInput) {
            return {
                title: 'Recovery Code',
                description:
                    'Please confirm access to your account by entering one of your emergency recovery codes.',
                toggleText: 'login using an authentication code',
            };
        }


        return {
            title: 'Authentication Code',
            description:
                'Enter the authentication code provided by your authenticator application.',
            toggleText: 'login using a recovery code',
        };
    }, [showRecoveryInput]);


    return (
        <>
            <Head title="Two Factor Challenge" />
            <div className="flex min-h-screen flex-col bg-gradient-to-br from-blue-50 via-white to-indigo-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-blue-950">
                {/* Header */}
                <header className="border-b border-neutral-200/50 backdrop-blur-sm dark:border-neutral-800">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <a href="/" className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 dark:bg-blue-500">
                                {/* Icon placeholder */}
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-neutral-900 dark:text-white">
                                    Manajemen Mitra
                                </h1>
                                <p className="text-xs text-neutral-600 dark:text-neutral-400">
                                    BPS Kota Sawahlunto
                                </p>
                            </div>
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
                                    {/* Icon placeholder */}
                                </div>
                                <h2 className="text-2xl font-bold text-neutral-900 dark:text-white">
                                    Two Factor Challenge
                                </h2>
                                <p className="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    Masukkan kode autentikasi atau recovery code Anda
                                </p>
                            </div>

                            <Form {...store.form()} resetOnSuccess={['code', 'recovery_code']} className="flex flex-col gap-6">
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-5">
                                            <div className="grid gap-2">
                                                <Label htmlFor="code" className="text-neutral-900 dark:text-neutral-100">
                                                    Kode Autentikasi
                                                </Label>
                                                <Input
                                                    id="code"
                                                    type="text"
                                                    name="code"
                                                    autoFocus
                                                    autoComplete="one-time-code"
                                                    placeholder="Kode autentikasi"
                                                    className="h-11"
                                                />
                                                <InputError message={errors.code} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="recovery_code" className="text-neutral-900 dark:text-neutral-100">
                                                    Recovery Code
                                                </Label>
                                                <Input
                                                    id="recovery_code"
                                                    type="text"
                                                    name="recovery_code"
                                                    autoComplete="one-time-code"
                                                    placeholder="Recovery code"
                                                    className="h-11"
                                                />
                                                <InputError message={errors.recovery_code} />
                                            </div>
                                            <Button
                                                type="submit"
                                                className="h-11 w-full bg-blue-600 text-base font-medium hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                                disabled={processing}
                                                data-test="two-factor-challenge-button"
                                            >
                                                Autentikasi
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
