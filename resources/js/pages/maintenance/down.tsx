import InputError from '@/components/input-error';
import { MaintenanceAccessShell } from '@/components/maintenance-access-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head } from '@inertiajs/react';
import AlertTriangle from 'lucide-react/icons/alert-triangle';
import KeyRound from 'lucide-react/icons/key-round';
import LoaderCircle from 'lucide-react/icons/loader-circle';
import Wrench from 'lucide-react/icons/wrench';
import { FormEvent, useState } from 'react';

export default function Down() {
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<{ key?: string; message?: string }>({});
    const [key, setKey] = useState('');
    const [message, setMessage] = useState('');

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setProcessing(true);
        setErrors({});

        const csrf =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        try {
            const response = await fetch('/mt', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ key, message }),
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                setErrors(
                    data.errors ?? {
                        message:
                            data.message ??
                            'Permintaan maintenance gagal diproses.',
                    },
                );
                return;
            }

            window.location.assign('/dashboard');
        } catch {
            setErrors({
                message: 'Koneksi gagal saat mengaktifkan maintenance.',
            });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <>
            <Head title="Masuk Maintenance Mode" />
            <MaintenanceAccessShell
                title="Masuk ke mode maintenance"
                description="Gunakan mode ini ketika SIMANTIK perlu ditutup sementara untuk pemeliharaan, migrasi, atau perubahan yang berisiko mengganggu transaksi pengguna."
                eyebrow="Kontrol Maintenance"
                icon={Wrench}
                note={
                    <span className="flex items-start gap-2">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0 text-amber-600" />
                        Pesan yang disimpan di sini juga menjadi pesan yang
                        ditampilkan pada halaman 503 dan System Settings.
                    </span>
                }
            >
                <form onSubmit={handleSubmit} className="space-y-5">
                    <div>
                        <h2 className="font-semibold">Aktifkan maintenance</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Verifikasi kunci administrator sebelum menutup
                            layanan.
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="key">Kunci Maintenance</Label>
                        <div className="relative">
                            <KeyRound className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="key"
                                type="password"
                                value={key}
                                onChange={(event) =>
                                    setKey(event.target.value)
                                }
                                required
                                autoFocus
                                className="pl-9"
                                placeholder="Masukkan kunci maintenance"
                            />
                        </div>
                        <InputError message={errors.key} />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between gap-3">
                            <Label htmlFor="message">
                                Pesan untuk pengguna
                            </Label>
                            <span className="text-xs text-muted-foreground">
                                {message.length}/500
                            </span>
                        </div>
                        <Textarea
                            id="message"
                            value={message}
                            onChange={(event) =>
                                setMessage(event.target.value)
                            }
                            rows={5}
                            maxLength={500}
                            placeholder="Contoh: Sistem sedang ditingkatkan dan akan kembali tersedia setelah proses selesai."
                        />
                        <InputError message={errors.message} />
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing}
                    >
                        {processing ? (
                            <LoaderCircle className="mr-2 size-4 animate-spin" />
                        ) : (
                            <Wrench className="mr-2 size-4" />
                        )}
                        Aktifkan Maintenance
                    </Button>
                </form>
            </MaintenanceAccessShell>
        </>
    );
}
