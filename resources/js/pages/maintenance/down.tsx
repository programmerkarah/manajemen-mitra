import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head } from '@inertiajs/react';
import { AlertTriangle, Key, LoaderCircle } from 'lucide-react';
import { FormEvent, useState } from 'react';

export default function Down() {
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<{ key?: string; message?: string }>({});
    const [key, setKey] = useState('');
    const [message, setMessage] = useState('');

    const getCsrfToken = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta?.getAttribute('content') || '';
    };

    const handleSubmit = async (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            const response = await fetch('/mt', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ key, message }),
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to dashboard after cookie is set
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 300);
            } else if (data.errors) {
                setErrors(data.errors);
                setProcessing(false);
            }
        } catch (error) {
            console.error('Error:', error);
            setErrors({ message: 'Terjadi kesalahan saat memproses permintaan' });
            setProcessing(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-orange-50 via-white to-orange-50 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900 px-4">
            <Head title="Masuk Maintenance Mode" />

            <div className="w-full max-w-md">
                <div className="bg-white dark:bg-zinc-800 shadow-xl rounded-2xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-orange-600 to-orange-700 dark:from-orange-500 dark:to-orange-600 p-6 text-center">
                        <div className="flex justify-center mb-3">
                            <div className="bg-white/20 p-3 rounded-full">
                                <AlertTriangle className="w-8 h-8 text-white" />
                            </div>
                        </div>
                        <h1 className="text-2xl font-bold text-white mb-2">Maintenance Mode</h1>
                        <p className="text-orange-100 text-sm">Aktifkan mode maintenance untuk sistem</p>
                    </div>

                    {/* Form */}
                    <div className="p-8">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="key" className="text-gray-700 dark:text-gray-300 font-medium flex items-center gap-2">
                                    <Key className="w-4 h-4" />
                                    Kunci Maintenance
                                </Label>
                                <Input
                                    id="key"
                                    type="password"
                                    name="key"
                                    value={key}
                                    onChange={(e) => setKey(e.target.value)}
                                    required
                                    autoFocus
                                    placeholder="Masukkan kunci maintenance"
                                    className="h-12 bg-gray-50 dark:bg-zinc-900 border-gray-300 dark:border-zinc-600 focus:border-orange-500 focus:ring-orange-500/20 dark:focus:border-orange-400"
                                />
                                <InputError message={errors.key} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="message" className="text-gray-700 dark:text-gray-300 font-medium">
                                    Pesan Informasi (Opsional)
                                </Label>
                                <textarea
                                    id="message"
                                    name="message"
                                    value={message}
                                    onChange={(e) => setMessage(e.target.value)}
                                    rows={4}
                                    placeholder="Contoh: Sistem sedang dalam perbaikan dan akan kembali normal dalam 2 jam"
                                    className="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-900 border border-gray-300 dark:border-zinc-600 rounded-lg focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 dark:focus:border-orange-400 resize-none"
                                />
                                <InputError message={errors.message} />
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Pesan ini akan ditampilkan kepada pengguna saat mengakses sistem
                                </p>
                            </div>

                            <div className="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
                                <p className="text-sm text-red-800 dark:text-red-200">
                                    <strong>Peringatan:</strong> Sistem akan masuk maintenance mode dan tidak dapat diakses oleh pengguna biasa.
                                </p>
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full h-12 bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 text-white font-semibold text-base shadow-lg transition-all"
                            >
                                {processing ? (
                                    <>
                                        <LoaderCircle className="w-5 h-5 mr-2 animate-spin" />
                                        Memproses...
                                    </>
                                ) : (
                                    <>
                                        <AlertTriangle className="w-5 h-5 mr-2" />
                                        Aktifkan Maintenance Mode
                                    </>
                                )}
                            </Button>
                        </form>
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
