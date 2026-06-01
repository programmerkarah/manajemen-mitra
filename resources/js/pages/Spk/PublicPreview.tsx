import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Head } from '@inertiajs/react';
import { Download, Eye } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface OptionItem {
    value: string;
    label: string;
}

interface PublicPreviewProps {
    survei_periods: OptionItem[];
    sensus_kegiatans: OptionItem[];
    active_year: number;
    recaptcha_site_key: string;
}

interface GrecaptchaApi {
    ready: (callback: () => void) => void;
    execute: (
        siteKey: string,
        options: {
            action: string;
        },
    ) => Promise<string>;
}

declare global {
    interface Window {
        grecaptcha?: GrecaptchaApi;
    }
}

const csrfToken = (): string => {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || ''
    );
};

const extractFilename = (contentDisposition: string | null): string => {
    if (!contentDisposition) {
        return 'Preview_SPK.pdf';
    }

    const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) {
        return decodeURIComponent(utf8Match[1]);
    }

    const quotedMatch = contentDisposition.match(/filename="([^"]+)"/i);
    if (quotedMatch?.[1]) {
        return quotedMatch[1];
    }

    return 'Preview_SPK.pdf';
};

export default function PublicPreview({
    survei_periods,
    sensus_kegiatans,
    active_year,
    recaptcha_site_key,
}: PublicPreviewProps) {
    const [nama, setNama] = useState('');
    const [nik, setNik] = useState('');
    const [jenisKegiatan, setJenisKegiatan] = useState<
        'survei' | 'sensus' | ''
    >('');
    const [surveiPeriode, setSurveiPeriode] = useState('');
    const [sensusKegiatan, setSensusKegiatan] = useState('');
    const [ownedSurveiPeriods, setOwnedSurveiPeriods] =
        useState<OptionItem[]>(survei_periods);
    const [ownedSensusKegiatans, setOwnedSensusKegiatans] =
        useState<OptionItem[]>(sensus_kegiatans);
    const [loadedPetugasName, setLoadedPetugasName] = useState<string | null>(
        null,
    );
    const [isOptionsLoaded, setIsOptionsLoaded] = useState(false);
    const [loadingOptions, setLoadingOptions] = useState(false);
    const [recaptchaReady, setRecaptchaReady] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    useEffect(() => {
        if (!recaptcha_site_key || recaptchaReady) {
            return;
        }

        if (window.grecaptcha) {
            window.grecaptcha.ready(() => {
                setRecaptchaReady(true);
            });
            return;
        }

        const existingScript = document.getElementById(
            'google-recaptcha-script',
        );
        if (existingScript) {
            return;
        }

        const script = document.createElement('script');
        script.id = 'google-recaptcha-script';
        script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(recaptcha_site_key)}`;
        script.async = true;
        script.defer = true;
        script.onload = () => {
            if (!window.grecaptcha) {
                return;
            }

            window.grecaptcha.ready(() => {
                setRecaptchaReady(true);
            });
        };
        document.body.appendChild(script);
    }, [recaptcha_site_key, recaptchaReady]);

    useEffect(() => {
        if (!nama.trim() || !nik.trim()) {
            setIsOptionsLoaded(false);
            setLoadedPetugasName(null);
            setOwnedSurveiPeriods([]);
            setOwnedSensusKegiatans([]);
            setJenisKegiatan('');
            setSurveiPeriode('');
            setSensusKegiatan('');
            return;
        }

        setIsOptionsLoaded(false);
        setLoadedPetugasName(null);
        setOwnedSurveiPeriods([]);
        setOwnedSensusKegiatans([]);
        setJenisKegiatan('');
        setSurveiPeriode('');
        setSensusKegiatan('');
    }, [nama, nik]);

    const availableJenisOptions = useMemo(() => {
        const options: Array<{ value: 'survei' | 'sensus'; label: string }> =
            [];

        if (ownedSurveiPeriods.length > 0) {
            options.push({ value: 'survei', label: 'Survei' });
        }

        if (ownedSensusKegiatans.length > 0) {
            options.push({ value: 'sensus', label: 'Sensus' });
        }

        return options;
    }, [ownedSurveiPeriods, ownedSensusKegiatans]);

    const getRecaptchaToken = async (action: string): Promise<string> => {
        if (!recaptcha_site_key) {
            return 'recaptcha-bypass-token';
        }

        if (!window.grecaptcha || !recaptchaReady) {
            throw new Error('reCAPTCHA belum siap.');
        }

        return window.grecaptcha.execute(recaptcha_site_key, { action });
    };

    const loadPetugasOptions = async (): Promise<void> => {
        setErrorMessage(null);

        if (!nama.trim() || !nik.trim()) {
            setErrorMessage('Isi Nama dan NIK terlebih dahulu.');
            return;
        }

        setLoadingOptions(true);

        try {
            const recaptchaToken = await getRecaptchaToken('mitra_options');

            const formData = new FormData();
            formData.append('nama', nama.trim());
            formData.append('nik', nik.trim());
            formData.append('recaptcha_token', recaptchaToken.trim());

            const response = await fetch('/mitra/options', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                let message = `Gagal memuat data petugas (${response.status}).`;

                try {
                    const payload = (await response.json()) as {
                        message?: string;
                    };
                    if (payload.message) {
                        message = payload.message;
                    }
                } catch {
                    // keep fallback message
                }

                setErrorMessage(message);
                return;
            }

            const payload = (await response.json()) as {
                petugas_nama: string;
                survei_periods: OptionItem[];
                sensus_kegiatans: OptionItem[];
            };

            setLoadedPetugasName(payload.petugas_nama);
            setOwnedSurveiPeriods(payload.survei_periods ?? []);
            setOwnedSensusKegiatans(payload.sensus_kegiatans ?? []);
            setIsOptionsLoaded(true);

            const nextJenisKegiatan =
                (payload.survei_periods?.length ?? 0) > 0
                    ? 'survei'
                    : (payload.sensus_kegiatans?.length ?? 0) > 0
                      ? 'sensus'
                      : '';

            setJenisKegiatan(nextJenisKegiatan);
            setSurveiPeriode('');
            setSensusKegiatan('');

            if (nextJenisKegiatan === '') {
                setErrorMessage(
                    'Petugas ditemukan, tetapi belum memiliki alokasi Perjanjian Kerja yang dapat dipreview.',
                );
            }
        } catch (error) {
            if (
                error instanceof Error &&
                error.message === 'reCAPTCHA belum siap.'
            ) {
                setErrorMessage(
                    'reCAPTCHA belum siap. Tunggu sebentar lalu coba lagi.',
                );
                return;
            }

            setErrorMessage('Terjadi kesalahan saat memuat data petugas.');
        } finally {
            setLoadingOptions(false);
        }
    };

    const canSubmit = useMemo(() => {
        if (!nama.trim() || !nik.trim()) {
            return false;
        }

        if (!isOptionsLoaded || jenisKegiatan === '') {
            return false;
        }

        if (jenisKegiatan === 'survei') {
            return surveiPeriode.length > 0;
        }

        return sensusKegiatan.length > 0;
    }, [
        nama,
        nik,
        isOptionsLoaded,
        jenisKegiatan,
        surveiPeriode,
        sensusKegiatan,
    ]);

    const requestPdf = async (aksi: 'preview' | 'download'): Promise<void> => {
        setErrorMessage(null);

        if (!canSubmit) {
            setErrorMessage('Lengkapi data terlebih dahulu.');
            return;
        }

        setProcessing(true);

        try {
            const recaptchaToken = await getRecaptchaToken('mitra_preview');

            const formData = new FormData();
            formData.append('nama', nama.trim());
            formData.append('nik', nik.trim());
            formData.append('jenis_kegiatan', jenisKegiatan);
            formData.append('recaptcha_token', recaptchaToken.trim());
            formData.append('aksi', aksi);

            if (jenisKegiatan === 'survei') {
                formData.append('survei_periode', surveiPeriode);
            }

            if (jenisKegiatan === 'sensus') {
                formData.append('sensus_kegiatan', sensusKegiatan);
            }

            const response = await fetch('/mitra', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/pdf,application/json,*/*',
                },
                body: formData,
            });

            if (!response.ok) {
                let message = `Gagal memproses permintaan (${response.status}).`;
                try {
                    const payload = (await response.json()) as {
                        message?: string;
                    };
                    if (payload.message) {
                        message = payload.message;
                    }
                } catch {
                    // Keep fallback message when response body is not JSON.
                }

                setErrorMessage(message);
                return;
            }

            const blob = await response.blob();
            const fileName = extractFilename(
                response.headers.get('content-disposition'),
            );
            const objectUrl = URL.createObjectURL(
                new Blob([await blob.arrayBuffer()], {
                    type: 'application/pdf',
                }),
            );

            if (aksi === 'preview') {
                const previewWindow = window.open(objectUrl, '_blank');

                if (!previewWindow || previewWindow.closed) {
                    const fallbackLink = document.createElement('a');
                    fallbackLink.href = objectUrl;
                    fallbackLink.download = fileName;
                    document.body.appendChild(fallbackLink);
                    fallbackLink.click();
                    fallbackLink.remove();
                }
            } else {
                const link = document.createElement('a');
                link.href = objectUrl;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                link.remove();
            }

            setTimeout(() => {
                URL.revokeObjectURL(objectUrl);
            }, 60000);
        } catch (error) {
            if (
                error instanceof Error &&
                error.message === 'reCAPTCHA belum siap.'
            ) {
                setErrorMessage(
                    'reCAPTCHA belum siap. Tunggu sebentar lalu coba lagi.',
                );
                return;
            }

            setErrorMessage(
                'Terjadi kesalahan saat mengakses preview perjanjian kerja.',
            );
        } finally {
            setProcessing(false);
        }
    };

    return (
        <>
            <Head title="Preview Perjanjian Kerja" />

            <div className="min-h-screen bg-neutral-50 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.18),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.2),transparent_40%)] px-4 py-8 text-neutral-900 sm:px-6 lg:px-8 dark:bg-neutral-950 dark:text-neutral-100">
                <div className="mx-auto w-full max-w-3xl">
                    <Card className="border-amber-200/60 dark:border-neutral-700/60">
                        <CardHeader className="gap-2 border-b border-amber-100/70 pb-5 dark:border-neutral-800">
                            <p className="text-xs font-semibold tracking-[0.2em] text-amber-700 uppercase dark:text-amber-300">
                                Layanan Mitra
                            </p>
                            <CardTitle className="text-2xl">
                                Cari Perjanjian Kerja
                            </CardTitle>
                            <CardDescription className="text-sm">
                                Cari dokumen preview perjanjian kerja tahun{' '}
                                {active_year}.
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-5">
                            {errorMessage && (
                                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
                                    {errorMessage}
                                </div>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="nama">Nama Lengkap</Label>
                                    <Input
                                        id="nama"
                                        value={nama}
                                        onChange={(event) =>
                                            setNama(event.target.value)
                                        }
                                        placeholder="Contoh: Sena Susanto"
                                        autoComplete="name"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="nik">NIK</Label>
                                    <Input
                                        id="nik"
                                        value={nik}
                                        onChange={(event) =>
                                            setNik(event.target.value)
                                        }
                                        placeholder="16 digit NIK"
                                        inputMode="numeric"
                                        autoComplete="off"
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                                <div className="space-y-2">
                                    <Label htmlFor="recaptcha-status">
                                        Verifikasi reCAPTCHA v3
                                    </Label>
                                    {!recaptcha_site_key && (
                                        <div className="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                            reCAPTCHA belum dikonfigurasi. Set
                                            RECAPTCHA_SITE_KEY dan
                                            RECAPTCHA_SECRET_KEY di environment.
                                        </div>
                                    )}
                                    {!!recaptcha_site_key && (
                                        <div
                                            id="recaptcha-status"
                                            className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300"
                                        >
                                            {recaptchaReady
                                                ? 'reCAPTCHA aktif. Token akan dibuat otomatis saat kirim data.'
                                                : 'Memuat reCAPTCHA...'}
                                        </div>
                                    )}
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="sm:mb-[2px]"
                                    onClick={() => void loadPetugasOptions()}
                                    disabled={
                                        loadingOptions ||
                                        processing ||
                                        (!recaptchaReady &&
                                            !!recaptcha_site_key)
                                    }
                                >
                                    {loadingOptions
                                        ? 'Memuat Data...'
                                        : 'Muat Data Petugas'}
                                </Button>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                {loadedPetugasName && (
                                    <div className="inline-flex items-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        Data ditemukan: {loadedPetugasName}
                                    </div>
                                )}
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="jenis-kegiatan">
                                        Jenis Kegiatan
                                    </Label>
                                    <Select
                                        value={jenisKegiatan}
                                        onValueChange={(
                                            value: 'survei' | 'sensus',
                                        ) => setJenisKegiatan(value)}
                                        disabled={
                                            !isOptionsLoaded ||
                                            availableJenisOptions.length === 0
                                        }
                                    >
                                        <SelectTrigger id="jenis-kegiatan">
                                            <SelectValue
                                                placeholder={
                                                    isOptionsLoaded
                                                        ? 'Pilih jenis kegiatan'
                                                        : 'Muat data petugas terlebih dahulu'
                                                }
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableJenisOptions.map(
                                                (option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="opsi-kegiatan">
                                        {jenisKegiatan === 'survei'
                                            ? 'Periode Bulan Survei'
                                            : 'Kegiatan Sensus'}
                                    </Label>

                                    {jenisKegiatan === 'survei' ? (
                                        <Select
                                            value={surveiPeriode}
                                            onValueChange={(value) =>
                                                setSurveiPeriode(value)
                                            }
                                            disabled={
                                                !isOptionsLoaded ||
                                                ownedSurveiPeriods.length === 0
                                            }
                                        >
                                            <SelectTrigger id="opsi-kegiatan">
                                                <SelectValue placeholder="Pilih periode survei" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {ownedSurveiPeriods.map(
                                                    (period) => (
                                                        <SelectItem
                                                            key={period.value}
                                                            value={period.value}
                                                        >
                                                            {period.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    ) : (
                                        <Select
                                            value={sensusKegiatan}
                                            onValueChange={(value) =>
                                                setSensusKegiatan(value)
                                            }
                                            disabled={
                                                !isOptionsLoaded ||
                                                ownedSensusKegiatans.length ===
                                                    0
                                            }
                                        >
                                            <SelectTrigger id="opsi-kegiatan">
                                                <SelectValue placeholder="Pilih kegiatan sensus" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {ownedSensusKegiatans.map(
                                                    (kegiatan) => (
                                                        <SelectItem
                                                            key={kegiatan.value}
                                                            value={
                                                                kegiatan.value
                                                            }
                                                        >
                                                            {kegiatan.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    )}
                                </div>
                            </div>

                            <div className="rounded-xl border border-cyan-200 bg-cyan-50/80 px-4 py-3 text-sm text-cyan-800 dark:border-cyan-900/60 dark:bg-cyan-950/20 dark:text-cyan-300">
                                Dropdown Jenis Kegiatan serta opsi
                                Periode/Kegiatan hanya menampilkan data alokasi
                                milik petugas yang diinput.
                            </div>

                            <div className="flex flex-wrap gap-3 pt-1">
                                <Button
                                    type="button"
                                    onClick={() => void requestPdf('preview')}
                                    disabled={processing || !canSubmit}
                                    className="min-w-[160px]"
                                >
                                    <Eye className="h-4 w-4" />
                                    {processing
                                        ? 'Memproses...'
                                        : 'Pratinjau PDF'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => void requestPdf('download')}
                                    disabled={processing || !canSubmit}
                                    className="min-w-[160px]"
                                >
                                    <Download className="h-4 w-4" />
                                    {processing
                                        ? 'Memproses...'
                                        : 'Unduh PDF'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
