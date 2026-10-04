import InputError from '@/components/input-error';
import { MaintenanceAccessShell } from '@/components/maintenance-access-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { up } from '@/routes/maintenance';
import { Form, Head } from '@inertiajs/react';
import CheckCircle2 from 'lucide-react/icons/check-circle2';
import KeyRound from 'lucide-react/icons/key-round';
import LoaderCircle from 'lucide-react/icons/loader-circle';
import Power from 'lucide-react/icons/power';

export default function Up() {
    return (
        <>
            <Head title="Aktifkan Kembali Layanan" />
            <MaintenanceAccessShell
                title="Aktifkan kembali SIMANTIK"
                description="Gunakan kunci aktivasi setelah pekerjaan maintenance selesai dan layanan sudah siap digunakan kembali oleh seluruh pengguna."
                eyebrow="Pemulihan Layanan"
                icon={Power}
                note={
                    <span className="flex items-start gap-2">
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-emerald-600" />
                        Keluar dari maintenance akan membuka kembali seluruh
                        route normal SIMANTIK untuk pengguna yang berhak.
                    </span>
                }
            >
                <Form {...up.form()} className="space-y-5">
                    {({ processing, errors }) => (
                        <>
                            <div>
                                <h2 className="font-semibold">
                                    Buka kembali layanan
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Pastikan migrasi, build, dan pemeriksaan
                                    aplikasi sudah selesai.
                                </p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="key">Kunci Aktivasi</Label>
                                <div className="relative">
                                    <KeyRound className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="key"
                                        type="password"
                                        name="key"
                                        required
                                        autoFocus
                                        className="pl-9"
                                        placeholder="Masukkan kunci aktivasi"
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
                                    <Power className="mr-2 size-4" />
                                )}
                                Aktifkan Layanan
                            </Button>
                        </>
                    )}
                </Form>
            </MaintenanceAccessShell>
        </>
    );
}
