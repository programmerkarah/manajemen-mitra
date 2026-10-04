import InputError from '@/components/input-error';
import { MaintenanceAccessShell } from '@/components/maintenance-access-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form, Head } from '@inertiajs/react';
import KeyRound from 'lucide-react/icons/key-round';
import LoaderCircle from 'lucide-react/icons/loader-circle';
import ShieldCheck from 'lucide-react/icons/shield-check';

export default function Bypass() {
    return (
        <>
            <Head title="Bypass Maintenance Mode" />
            <MaintenanceAccessShell
                title="Akses sistem saat maintenance"
                description="Bypass memberi akses sementara kepada administrator tanpa membuka SIMANTIK untuk pengguna lain. Gunakan hanya untuk verifikasi dan pekerjaan teknis."
                eyebrow="Akses Terbatas"
                icon={ShieldCheck}
                note="Maintenance tetap aktif untuk pengguna lain. Cookie bypass hanya diberikan setelah kunci diverifikasi oleh server."
            >
                <Form method="post" action="/bypass" className="space-y-5">
                    {({ processing, errors }) => (
                        <>
                            <div>
                                <h2 className="font-semibold">
                                    Verifikasi akses
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Masukkan kunci bypass maintenance.
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="key">Kunci Bypass</Label>
                                <div className="relative">
                                    <KeyRound className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="key"
                                        type="password"
                                        name="key"
                                        required
                                        autoFocus
                                        className="pl-9"
                                        placeholder="Masukkan kunci bypass"
                                    />
                                </div>
                                <InputError message={errors.key} />
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing ? (
                                    <LoaderCircle className="mr-2 size-4 animate-spin" />
                                ) : (
                                    <ShieldCheck className="mr-2 size-4" />
                                )}
                                Masuk dengan Bypass
                            </Button>
                        </>
                    )}
                </Form>
            </MaintenanceAccessShell>
        </>
    );
}
