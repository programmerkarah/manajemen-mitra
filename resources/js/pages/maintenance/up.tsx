import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { up } from '@/routes/maintenance';
import { Form, Head } from '@inertiajs/react';
import { Key, LoaderCircle, PowerIcon } from 'lucide-react';

export default function Up() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-green-50 via-white to-green-50 px-4 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
            <Head title="Aktifkan Kembali Layanan" />

            <div className="w-full max-w-md">
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-green-600 to-green-700 p-6 text-center dark:from-green-500 dark:to-green-600">
                        <div className="mb-3 flex justify-center">
                            <div className="rounded-full bg-white/20 p-3">
                                <PowerIcon className="h-8 w-8 text-white" />
                            </div>
                        </div>
                        <h1 className="mb-2 text-2xl font-bold text-white">
                            Aktifkan Kembali Layanan
                        </h1>
                        <p className="text-sm text-green-100">
                            Masukkan kunci untuk keluar dari maintenance mode
                        </p>
                    </div>

                    {/* Form */}
                    <div className="p-8">
                        <Form {...up.form()} className="space-y-6">
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="key"
                                            className="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            <Key className="h-4 w-4" />
                                            Kunci Aktivasi
                                        </Label>
                                        <Input
                                            id="key"
                                            type="password"
                                            name="key"
                                            required
                                            autoFocus
                                            placeholder="Masukkan kunci aktivasi"
                                            className="h-12 border-gray-300 bg-gray-50 focus:border-green-500 focus:ring-green-500/20 dark:border-zinc-600 dark:bg-zinc-900 dark:focus:border-green-400"
                                        />
                                        <InputError message={errors.key} />
                                    </div>

                                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/30">
                                        <p className="text-sm text-green-800 dark:text-green-200">
                                            <strong>Perhatian:</strong> Sistem
                                            akan keluar dari maintenance mode
                                            dan layanan akan kembali normal
                                            untuk semua pengguna.
                                        </p>
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="h-12 w-full bg-green-600 text-base font-semibold text-white shadow-lg transition-all hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                                    >
                                        {processing ? (
                                            <>
                                                <LoaderCircle className="mr-2 h-5 w-5 animate-spin" />
                                                Memproses...
                                            </>
                                        ) : (
                                            <>
                                                <PowerIcon className="mr-2 h-5 w-5" />
                                                Aktifkan Layanan
                                            </>
                                        )}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                </div>

                {/* Footer */}
                <div className="mt-6 text-center">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Akses terbatas hanya untuk administrator
                    </p>
                </div>
            </div>
        </div>
    );
}
