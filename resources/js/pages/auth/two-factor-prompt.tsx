import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { show } from '@/routes/two-factor';
import { Head, Link } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

export default function TwoFactorPrompt() {
    return (
        <AuthLayout
            title="Two-Factor Authentication Required"
            description="To keep your account secure, please enable two-factor authentication before accessing the application."
        >
            <Head title="Enable Two-Factor Authentication" />

            <div className="space-y-6">
                <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                    <div className="flex items-start space-x-3">
                        <ShieldCheck className="mt-0.5 size-5 flex-shrink-0 text-amber-600 dark:text-amber-400" />
                        <div className="space-y-1">
                            <h3 className="font-semibold text-amber-900 dark:text-amber-100">
                                Security Enhancement Required
                            </h3>
                            <p className="text-sm text-amber-800 dark:text-amber-200">
                                Two-factor authentication (2FA) adds an extra
                                layer of security to your account. You'll need
                                to enter a code from your authenticator app each
                                time you log in.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="space-y-3">
                    <h4 className="text-sm font-medium">
                        How to set up 2FA:
                    </h4>
                    <ol className="list-inside list-decimal space-y-2 text-sm text-muted-foreground">
                        <li>
                            Install an authenticator app (Google Authenticator,
                            Authy, etc.)
                        </li>
                        <li>
                            Scan the QR code or enter the setup key manually
                        </li>
                        <li>Enter the 6-digit code to confirm setup</li>
                        <li>Save your recovery codes in a safe place</li>
                    </ol>
                </div>

                <div className="pt-4">
                    <Link href={show.url({ query: { required: 'true' } })}>
                        <Button className="w-full" size="lg">
                            <ShieldCheck />
                            Enable Two-Factor Authentication
                        </Button>
                    </Link>
                </div>
            </div>
        </AuthLayout>
    );
}
