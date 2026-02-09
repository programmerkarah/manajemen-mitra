import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { up } from '@/routes/maintenance';
import { Form, Head } from '@inertiajs/react';
import { Key, LoaderCircle, PowerIcon } from 'lucide-react';

export default function Up() {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 via-white to-green-50 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900 px-4">
            <Head title="Aktifkan Kembali Layanan" />

            <div className="w-full max-w-md">
                <div className="bg-white dark:bg-zinc-800 shadow-xl rounded-2xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-green-600 to-green-700 dark:from-green-500 dark:to-green-600 p-6 text-center">
                        <div className="flex justify-center mb-3">
                            <div className="bg-white/20 p-3 rounded-full">
                                <PowerIcon className="w-8 h-8 text-white" />
                            </div>
                        </div>
                        <h1 className="text-2xl font-bold text-white mb-2">Aktifkan Kembali Layanan</h1>
                        <p className="text-green-100 text-sm">Masukkan kunci untuk keluar dari maintenance mode</p>
                    </div>

                    {/* Form */}
                    <div className="p-8">
                        <Form
                            {...up.form()}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="key" className="text-gray-700 dark:text-gray-300 font-medium flex items-center gap-2">
                                            <Key className="w-4 h-4" />
                                            Kunci Aktivasi
                                        </Label>
                                        <Input
                                            id="key"
                                            type="password"
                                            name="key"
                                            required
                                            autoFocus
                                            placeholder="Masukkan kunci aktivasi"
                                            className="h-12 bg-gray-50 dark:bg-zinc-900 border-gray-300 dark:border-zinc-600 focus:border-green-500 focus:ring-green-500/20 dark:focus:border-green-400"
                                        />
                                        <InputError message={errors.key} />
                                    </div>

                                    <div className="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                        <p className="text-sm text-green-800 dark:text-green-200">
                                            <strong>Perhatian:</strong> Sistem akan keluar dari maintenance mode dan layanan akan kembali normal untuk semua pengguna.
                                        </p>
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full h-12 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-semibold text-base shadow-lg transition-all"
                                    >
                                        {processing ? (
                                            <>
                                                <LoaderCircle className="w-5 h-5 mr-2 animate-spin" />
                                                Memproses...
                                            </>
                                        ) : (
                                            <>
                                                <PowerIcon className="w-5 h-5 mr-2" />
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
                <div className="text-center mt-6">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Akses terbatas hanya untuk administrator
                    </p>
                </div>
            </div>
        </div>
    );
}
