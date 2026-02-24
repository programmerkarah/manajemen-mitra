import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form, Head } from '@inertiajs/react';
import { Key, LoaderCircle, ShieldCheck } from 'lucide-react';

export default function Bypass() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-50 via-white to-blue-50 px-4 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
            <Head title="Bypass Maintenance Mode" />

            <div className="w-full max-w-md">
                <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                    {/* Header */}
                    <div className="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-center dark:from-blue-500 dark:to-blue-600">
                        <div className="mb-3 flex justify-center">
                            <div className="rounded-full bg-white/20 p-3">
                                <ShieldCheck className="h-8 w-8 text-white" />
                            </div>
                        </div>
                        <h1 className="mb-2 text-2xl font-bold text-white">
                            Bypass Maintenance Mode
                        </h1>
                        <p className="text-sm text-blue-100">
                            Masukkan kunci bypass untuk mengakses sistem
                        </p>
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
                                        <Label
                                            htmlFor="key"
                                            className="flex items-center gap-2 font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            <Key className="h-4 w-4" />
                                            Kunci Bypass
                                        </Label>
                                        <Input
                                            id="key"
                                            type="password"
                                            name="key"
                                            required
                                            autoFocus
                                            placeholder="Masukkan kunci bypass"
                                            className="h-12 border-gray-300 bg-gray-50 focus:border-blue-500 focus:ring-blue-500/20 dark:border-zinc-600 dark:bg-zinc-900 dark:focus:border-blue-400"
                                        />
                                        <InputError message={errors.key} />
                                    </div>

                                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/30">
                                        <p className="text-sm text-amber-800 dark:text-amber-200">
                                            <strong>Informasi:</strong> Setelah
                                            bypass berhasil, Anda dapat
                                            mengakses sistem secara sementara.
                                            Sistem tetap dalam maintenance mode
                                            untuk pengguna lain.
                                        </p>
                                    </div>

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="h-12 w-full bg-blue-600 text-base font-semibold text-white shadow-lg transition-all hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                                    >
                                        {processing ? (
                                            <>
                                                <LoaderCircle className="mr-2 h-5 w-5 animate-spin" />
                                                Memproses...
                                            </>
                                        ) : (
                                            <>
                                                <ShieldCheck className="mr-2 h-5 w-5" />
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
                <div className="mt-6 text-center">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Akses terbatas hanya untuk administrator
                    </p>
                </div>
            </div>
        </div>
    );
}
