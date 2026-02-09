import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form, Head } from '@inertiajs/react';
import { Key, LoaderCircle, ShieldCheck } from 'lucide-react';

export default function Bypass() {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-blue-50 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900 px-4">
            <Head title="Bypass Maintenance Mode" />

            <div className="w-full max-w-md">
                <div className="bg-white dark:bg-zinc-800 shadow-xl rounded-2xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-500 dark:to-blue-600 p-6 text-center">
                        <div className="flex justify-center mb-3">
                            <div className="bg-white/20 p-3 rounded-full">
                                <ShieldCheck className="w-8 h-8 text-white" />
                            </div>
                        </div>
                        <h1 className="text-2xl font-bold text-white mb-2">Bypass Maintenance Mode</h1>
                        <p className="text-blue-100 text-sm">Masukkan kunci bypass untuk mengakses sistem</p>
                    </div>

                    {/* Form */}
                    <div className="p-8">
                        <Form
                            method="post"
                            action="/bypass"
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="key" className="text-gray-700 dark:text-gray-300 font-medium flex items-center gap-2">
                                            <Key className="w-4 h-4" />
                                            Kunci Bypass
                                        </Label>
                                        <Input
                                            id="key"
                                            type="password"
                                            name="key"
                                            required
                                            autoFocus
                                            placeholder="Masukkan kunci bypass"
                                            className="h-12 bg-gray-50 dark:bg-zinc-900 border-gray-300 dark:border-zinc-600 focus:border-blue-500 focus:ring-blue-500/20 dark:focus:border-blue-400"
                                        />
                                        <InputError message={errors.key} />
                                    </div>

                                    <div className="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                        <p className="text-sm text-amber-800 dark:text-amber-200">
                                            <strong>Informasi:</strong> Setelah bypass berhasil, Anda dapat mengakses sistem secara sementara. 
                                            Sistem tetap dalam maintenance mode untuk pengguna lain.
                                        </p>
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full h-12 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold text-base shadow-lg transition-all"
                                    >
                                        {processing ? (
                                            <>
                                                <LoaderCircle className="w-5 h-5 mr-2 animate-spin" />
                                                Memproses...
                                            </>
                                        ) : (
                                            <>
                                                <ShieldCheck className="w-5 h-5 mr-2" />
                                                Bypass Sekarang
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
