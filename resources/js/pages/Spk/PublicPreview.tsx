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

interface PenugasanItem {
    id: number;
    jenis_kegiatan: 'survei' | 'sensus';
    kegiatan_hashed_id: string;
    periode_key: string;
    periode_label: string;
    nama_kegiatan: string;
    target_pekerjaan: string;
    honor: number;
    honor_label: string;
    document_status: string;
}

interface PublicPreviewProps {
    survei_periods: OptionItem[];
    sensus_kegiatans: OptionItem[];
    penugasan_list: PenugasanItem[];
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

const PDF_REQUEST_TIMEOUT_MS = 120000;

const loadingWindowHtml = (action: 'preview' | 'download'): string => {
    const title =
        action === 'preview'
            ? 'Memuat pratinjau PDF...'
            : 'Menyiapkan unduhan PDF...';

    return `<!DOCTYPE html><html><head><meta charset="utf-8"/><title>${title}</title></head><body style="font-family:Arial,sans-serif;padding:16px;"><h3 style="margin:0 0 8px 0;">${title}</h3><p id="status-text" style="margin:0 0 10px 0;color:#666;font-size:13px;">Memulai proses...</p><div style="height:10px;border:1px solid #ddd;border-radius:999px;overflow:hidden;background:#f5f5f5;"><div id="progress-bar" style="height:100%;width:0%;background:#16a34a;transition:width .2s ease;"></div></div><p id="progress-label" style="margin:8px 0 0 0;color:#444;font-size:12px;">0%</p><p style="margin-top:12px;color:#666;font-size:12px;">Jangan tutup tab ini sampai proses selesai.</p></body></html>`;
};

const updateActionWindowStatus = (
    actionWindow: Window | null,
    statusText: string,
    percent?: number,
): void => {
    if (!actionWindow || actionWindow.closed) {
        return;
    }

    const statusNode = actionWindow.document.getElementById('status-text');
    if (statusNode) {
        statusNode.textContent = statusText;
    }

    if (typeof percent === 'number') {
        const bounded = Math.max(0, Math.min(100, Math.round(percent)));
        const progressBar =
            actionWindow.document.getElementById('progress-bar');
        const progressLabel =
            actionWindow.document.getElementById('progress-label');

        if (progressBar) {
            progressBar.style.width = `${bounded}%`;
        }

        if (progressLabel) {
            progressLabel.textContent = `${bounded}%`;
        }
    }
};

export default function PublicPreview({
    survei_periods,
    sensus_kegiatans,
    penugasan_list,
    active_year,
    recaptcha_site_key,
}: PublicPreviewProps) {
    const [nama, setNama] = useState('');
    const [nik, setNik] = useState('');
    const [telepon4Digit, setTelepon4Digit] = useState('');
    const [jenisKegiatan, setJenisKegiatan] = useState<
        'survei' | 'sensus' | ''
    >('');
    const [surveiPeriode, setSurveiPeriode] = useState('');
    const [sensusKegiatan, setSensusKegiatan] = useState('');
    const [ownedSurveiPeriods, setOwnedSurveiPeriods] =
        useState<OptionItem[]>(survei_periods);
    const [ownedSensusKegiatans, setOwnedSensusKegiatans] =
        useState<OptionItem[]>(sensus_kegiatans);
    const [ownedPenugasanList, setOwnedPenugasanList] =
        useState<PenugasanItem[]>(penugasan_list);
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
        if (!nama.trim() || !nik.trim() || telepon4Digit.length !== 4) {
            setIsOptionsLoaded(false);
            setLoadedPetugasName(null);
            setOwnedSurveiPeriods([]);
            setOwnedSensusKegiatans([]);
            setOwnedPenugasanList([]);
            setJenisKegiatan('');
            setSurveiPeriode('');
            setSensusKegiatan('');
            return;
        }

        setIsOptionsLoaded(false);
        setLoadedPetugasName(null);
        setOwnedSurveiPeriods([]);
        setOwnedSensusKegiatans([]);
        setOwnedPenugasanList([]);
        setJenisKegiatan('');
        setSurveiPeriode('');
        setSensusKegiatan('');
    }, [nama, nik, telepon4Digit]);

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

        if (!nama.trim() || !nik.trim() || telepon4Digit.length !== 4) {
            setErrorMessage(
                'Isi Nama, NIK, dan 4 digit nomor HP terlebih dahulu.',
            );
            return;
        }

        setLoadingOptions(true);

        try {
            const recaptchaToken = await getRecaptchaToken('mitra_options');

            const formData = new FormData();
            formData.append('nama', nama.trim());
            formData.append('nik', nik.trim());
            formData.append('telepon_4_digit', telepon4Digit);
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
                penugasan_list: PenugasanItem[];
            };

            setLoadedPetugasName(payload.petugas_nama);
            setOwnedSurveiPeriods(payload.survei_periods ?? []);
            setOwnedSensusKegiatans(payload.sensus_kegiatans ?? []);
            setOwnedPenugasanList(payload.penugasan_list ?? []);
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
        if (!nama.trim() || !nik.trim() || telepon4Digit.length !== 4) {
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
        telepon4Digit,
        isOptionsLoaded,
        jenisKegiatan,
        surveiPeriode,
        sensusKegiatan,
    ]);

    const selectedPenugasanList = useMemo(() => {
        if (jenisKegiatan === 'survei' && surveiPeriode) {
            return ownedPenugasanList.filter(
                (item) =>
                    item.jenis_kegiatan === 'survei' &&
                    item.periode_key === surveiPeriode,
            );
        }

        if (jenisKegiatan === 'sensus' && sensusKegiatan) {
            return ownedPenugasanList.filter(
                (item) =>
                    item.jenis_kegiatan === 'sensus' &&
                    item.kegiatan_hashed_id === sensusKegiatan,
            );
        }

        return [];
    }, [ownedPenugasanList, jenisKegiatan, surveiPeriode, sensusKegiatan]);

    const selectedPenugasanTotalHonor = useMemo(() => {
        return selectedPenugasanList.reduce(
            (total, item) => total + item.honor,
            0,
        );
    }, [selectedPenugasanList]);

    const selectedPenugasanPeriodLabel = useMemo(() => {
        if (selectedPenugasanList.length === 0) {
            return '';
        }

        if (jenisKegiatan === 'sensus') {
            return `15 Juni - 31 Agustus ${active_year}`;
        }

        const uniquePeriods = Array.from(
            new Set(selectedPenugasanList.map((item) => item.periode_label)),
        );

        if (uniquePeriods.length === 1) {
            return uniquePeriods[0] ?? '';
        }

        return `${uniquePeriods[0]} - ${uniquePeriods[uniquePeriods.length - 1]}`;
    }, [active_year, jenisKegiatan, selectedPenugasanList]);

    const selectedPenugasanCardTitle = useMemo(() => {
        if (jenisKegiatan === 'survei') {
            return selectedPenugasanPeriodLabel
                ? `Daftar Penugasan Survei`
                : 'Daftar Penugasan Survei';
        }

        if (jenisKegiatan === 'sensus') {
            const selectedSensusKegiatanName =
                selectedPenugasanList[0]?.nama_kegiatan ??
                ownedSensusKegiatans.find(
                    (item) => item.value === sensusKegiatan,
                )?.label;

            return selectedSensusKegiatanName
                ? `Alokasi Tugas ${selectedSensusKegiatanName}`
                : 'Alokasi Tugas Sensus';
        }

        return 'Daftar Penugasan';
    }, [
        jenisKegiatan,
        selectedPenugasanList,
        ownedSensusKegiatans,
        sensusKegiatan,
        selectedPenugasanPeriodLabel,
    ]);

    const selectedPenugasanCardDescription = useMemo(() => {
        if (jenisKegiatan === 'survei') {
            if (selectedPenugasanPeriodLabel) {
                return `Menampilkan penugasan pada bulan ${selectedPenugasanPeriodLabel}.`;
            } else {
                return 'Menampilkan penugasan pada periode survei yang dipilih.';
            }
        }

        if (jenisKegiatan === 'sensus') {
            const selectedSensusKegiatanName =
                selectedPenugasanList[0]?.nama_kegiatan ??
                ownedSensusKegiatans.find(
                    (item) => item.value === sensusKegiatan,
                )?.label;

            return selectedSensusKegiatanName
                ? `Menampilkan alokasi tugas ${selectedSensusKegiatanName}.`
                : 'Menampilkan alokasi tugas sensus sesuai kegiatan yang dipilih.';
        }

        return 'Menampilkan penugasan sesuai periode atau kegiatan yang dipilih.';
    }, [
        jenisKegiatan,
        selectedPenugasanPeriodLabel,
        selectedPenugasanList,
        ownedSensusKegiatans,
        sensusKegiatan,
    ]);

    const requestPdf = async (
        aksi: 'preview' | 'download',
        openedWindow: Window | null = null,
    ): Promise<void> => {
        setErrorMessage(null);

        if (!canSubmit) {
            setErrorMessage('Lengkapi data terlebih dahulu.');
            if (openedWindow && !openedWindow.closed) {
                openedWindow.close();
            }
            return;
        }

        const actionWindow = openedWindow;
        if (actionWindow && !actionWindow.closed) {
            actionWindow.document.open();
            actionWindow.document.write(loadingWindowHtml(aksi));
            actionWindow.document.close();
        }

        setProcessing(true);

        try {
            const formData = new FormData();
            formData.append('nama', nama.trim());
            formData.append('nik', nik.trim());
            formData.append('telepon_4_digit', telepon4Digit);
            formData.append('jenis_kegiatan', jenisKegiatan);
            formData.append('aksi', aksi);

            if (jenisKegiatan === 'survei') {
                formData.append('survei_periode', surveiPeriode);
            }

            if (jenisKegiatan === 'sensus') {
                formData.append('sensus_kegiatan', sensusKegiatan);
            }

            const controller = new AbortController();
            const timeoutId = window.setTimeout(() => {
                controller.abort();
            }, PDF_REQUEST_TIMEOUT_MS);

            let response: globalThis.Response;
            try {
                response = await fetch('/mitra', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/pdf,application/json,*/*',
                    },
                    body: formData,
                    signal: controller.signal,
                });
            } finally {
                window.clearTimeout(timeoutId);
            }

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
                if (actionWindow && !actionWindow.closed) {
                    actionWindow.close();
                }
                return;
            }

            const totalBytes = Number(
                response.headers.get('content-length') ?? '0',
            );
            let loadedBytes = 0;
            let blob: Blob;

            if (response.body) {
                const reader = response.body.getReader();
                const chunks: ArrayBuffer[] = [];

                updateActionWindowStatus(
                    actionWindow,
                    'Mengunduh file PDF...',
                    0,
                );

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) {
                        break;
                    }

                    if (value) {
                        const normalizedChunk = new Uint8Array(
                            value.byteLength,
                        );
                        normalizedChunk.set(value);
                        chunks.push(normalizedChunk.buffer);
                        loadedBytes += value.length;

                        if (totalBytes > 0) {
                            updateActionWindowStatus(
                                actionWindow,
                                'Mengunduh file PDF...',
                                (loadedBytes / totalBytes) * 100,
                            );
                        } else {
                            updateActionWindowStatus(
                                actionWindow,
                                'Mengunduh file PDF...',
                            );
                        }
                    }
                }

                blob = new Blob(chunks, { type: 'application/pdf' });
            } else {
                updateActionWindowStatus(actionWindow, 'Mengunduh file PDF...');
                blob = await response.blob();
            }

            const fileName = extractFilename(
                response.headers.get('content-disposition'),
            );
            const objectUrl = URL.createObjectURL(
                blob.type === 'application/pdf'
                    ? blob
                    : new Blob([await blob.arrayBuffer()], {
                          type: 'application/pdf',
                      }),
            );

            if (aksi === 'preview') {
                updateActionWindowStatus(
                    actionWindow,
                    'Membuka pratinjau...',
                    100,
                );
                if (actionWindow && !actionWindow.closed) {
                    actionWindow.location.href = objectUrl;
                } else {
                    const fallbackLink = document.createElement('a');
                    fallbackLink.href = objectUrl;
                    fallbackLink.download = fileName;
                    document.body.appendChild(fallbackLink);
                    fallbackLink.click();
                    fallbackLink.remove();
                }
            } else {
                if (actionWindow && !actionWindow.closed) {
                    updateActionWindowStatus(
                        actionWindow,
                        'Memicu proses unduh...',
                        100,
                    );
                    actionWindow.onbeforeunload = null;
                    const link = actionWindow.document.createElement('a');
                    link.href = objectUrl;
                    link.download = fileName;
                    actionWindow.document.body.appendChild(link);
                    link.click();

                    const statusNode =
                        actionWindow.document.getElementById('status-text');
                    if (statusNode) {
                        statusNode.textContent =
                            'Unduhan berhasil dipicu. Anda dapat menutup tab ini.';
                    }
                } else {
                    const link = document.createElement('a');
                    link.href = objectUrl;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }
            }

            setTimeout(() => {
                URL.revokeObjectURL(objectUrl);
            }, 60000);
        } catch (error) {
            if (actionWindow && !actionWindow.closed) {
                actionWindow.onbeforeunload = null;
                actionWindow.close();
            }

            if (
                error instanceof Error &&
                error.message === 'reCAPTCHA belum siap.'
            ) {
                setErrorMessage(
                    'reCAPTCHA belum siap. Tunggu sebentar lalu coba lagi.',
                );
                return;
            }

            if (error instanceof DOMException && error.name === 'AbortError') {
                setErrorMessage(
                    'Proses melebihi batas waktu. Silakan coba lagi beberapa saat.',
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

            <div className="flex min-h-screen justify-center bg-neutral-50 bg-[radial-gradient(circle_at_top_left,rgba(245,158,11,0.18),transparent_40%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.2),transparent_40%)] px-4 py-8 text-neutral-900 sm:px-6 lg:px-8 dark:bg-neutral-950 dark:text-neutral-100">
                <div className="max-w-8xl mx-auto w-full">
                    <Card className="border-amber-200/60 dark:border-neutral-700/60">
                        <CardHeader className="gap-2 border-b border-amber-100/70 pb-5 dark:border-neutral-800">
                            <p className="text-xs font-semibold tracking-[0.2em] text-amber-700 uppercase dark:text-amber-300">
                                Layanan Mitra
                            </p>
                            <CardTitle className="text-2xl">
                                Riwayat Pekerjaan Survei/Sensus {active_year}.
                            </CardTitle>
                            <CardDescription className="text-sm">
                                Lihat riwayat pekerjaan dan preview perjanjian
                                kerja tahun {active_year}.
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            {errorMessage && (
                                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
                                    {errorMessage}
                                </div>
                            )}

                            <div className="rounded-3xl border border-neutral-200 bg-white/80 p-4 shadow-sm backdrop-blur sm:p-5 dark:border-neutral-800 dark:bg-neutral-900/70">
                                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,450px)_auto] lg:items-end">
                                    <div className="space-y-2">
                                        <Label htmlFor="nama">
                                            Nama Lengkap
                                        </Label>
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

                                    <div className="space-y-2">
                                        <Label htmlFor="telepon-4-digit">
                                            4 Digit Terakhir No. HP terdaftar di
                                            SOBAT
                                        </Label>
                                        <Input
                                            id="telepon-4-digit"
                                            value={telepon4Digit}
                                            onChange={(event) =>
                                                setTelepon4Digit(
                                                    event.target.value
                                                        .replace(/\D/g, '')
                                                        .slice(0, 4),
                                                )
                                            }
                                            placeholder="Contoh: 1234"
                                            inputMode="numeric"
                                            maxLength={4}
                                            autoComplete="off"
                                        />
                                    </div>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            void loadPetugasOptions()
                                        }
                                        disabled={
                                            loadingOptions ||
                                            processing ||
                                            (!recaptchaReady &&
                                                !!recaptcha_site_key)
                                        }
                                        className="w-full lg:w-auto"
                                    >
                                        {loadingOptions
                                            ? 'Memuat Data...'
                                            : 'Muat Data'}
                                    </Button>
                                </div>
                            </div>

                            {loadedPetugasName && (
                                <div className="rounded-3xl border border-emerald-200 bg-emerald-50/90 px-4 py-4 shadow-sm sm:px-5 dark:border-emerald-900/60 dark:bg-emerald-950/30">
                                    <p className="text-xs font-semibold tracking-[0.18em] text-emerald-700 uppercase dark:text-emerald-300">
                                        Data ditemukan
                                    </p>
                                    <div className="mt-1 flex flex-wrap items-center gap-2">
                                        <span className="text-base font-semibold text-emerald-950 dark:text-emerald-100">
                                            {loadedPetugasName}
                                        </span>
                                    </div>
                                    <p className="mt-2 text-sm text-emerald-700/90 dark:text-emerald-300/90">
                                        Pilih jenis kegiatan dan
                                        periode/kegiatan yang tersedia untuk
                                        petugas ini.
                                    </p>
                                </div>
                            )}

                            {isOptionsLoaded && (
                                <div className="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
                                    <div className="rounded-3xl border border-neutral-200 bg-white/80 p-4 shadow-sm backdrop-blur sm:p-5 dark:border-neutral-800 dark:bg-neutral-900/70">
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="jenis-kegiatan">
                                                    Jenis Kegiatan
                                                </Label>
                                                <Select
                                                    value={jenisKegiatan}
                                                    onValueChange={(
                                                        value:
                                                            | 'survei'
                                                            | 'sensus',
                                                    ) =>
                                                        setJenisKegiatan(value)
                                                    }
                                                    disabled={
                                                        !isOptionsLoaded ||
                                                        availableJenisOptions.length ===
                                                            0
                                                    }
                                                >
                                                    <SelectTrigger id="jenis-kegiatan">
                                                        <SelectValue placeholder="Pilih jenis kegiatan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {availableJenisOptions.map(
                                                            (option) => (
                                                                <SelectItem
                                                                    key={
                                                                        option.value
                                                                    }
                                                                    value={
                                                                        option.value
                                                                    }
                                                                >
                                                                    {
                                                                        option.label
                                                                    }
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
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            setSurveiPeriode(
                                                                value,
                                                            )
                                                        }
                                                        disabled={
                                                            !isOptionsLoaded ||
                                                            ownedSurveiPeriods.length ===
                                                                0
                                                        }
                                                    >
                                                        <SelectTrigger id="opsi-kegiatan">
                                                            <SelectValue placeholder="Pilih periode survei" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {ownedSurveiPeriods.map(
                                                                (period) => (
                                                                    <SelectItem
                                                                        key={
                                                                            period.value
                                                                        }
                                                                        value={
                                                                            period.value
                                                                        }
                                                                    >
                                                                        {
                                                                            period.label
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                ) : (
                                                    <Select
                                                        value={sensusKegiatan}
                                                        onValueChange={(
                                                            value,
                                                        ) =>
                                                            setSensusKegiatan(
                                                                value,
                                                            )
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
                                                                        key={
                                                                            kegiatan.value
                                                                        }
                                                                        value={
                                                                            kegiatan.value
                                                                        }
                                                                    >
                                                                        {
                                                                            kegiatan.label
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                )}
                                            </div>

                                            <div className="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-600 dark:border-neutral-800 dark:bg-neutral-950/60 dark:text-neutral-300">
                                                Pilih jenis kegiatan dan periode
                                                atau kegiatan untuk melihat
                                                daftar penugasan yang tersedia.
                                            </div>
                                        </div>
                                    </div>

                                    <div className="overflow-hidden rounded-3xl border border-neutral-200 bg-white/80 shadow-sm backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/70">
                                        <div className="border-b border-neutral-200 px-4 py-4 sm:px-5 dark:border-neutral-800">
                                            <h3 className="text-sm font-semibold text-neutral-900 dark:text-white">
                                                {selectedPenugasanCardTitle}
                                            </h3>
                                            {selectedPenugasanPeriodLabel && (
                                                <p className="mt-2 text-sm font-medium text-neutral-700 dark:text-neutral-200">
                                                    {jenisKegiatan === 'sensus'
                                                        ? `Periode Pelaksanaan: ${selectedPenugasanPeriodLabel}`
                                                        : ''}
                                                </p>
                                            )}
                                            <p className="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                                {
                                                    selectedPenugasanCardDescription
                                                }
                                            </p>
                                        </div>

                                        {selectedPenugasanList.length > 0 ? (
                                            <div className="overflow-x-auto">
                                                <table className="min-w-full divide-y divide-neutral-200 text-left text-sm dark:divide-neutral-800">
                                                    <thead className="bg-neutral-50 dark:bg-neutral-950/60">
                                                        <tr>
                                                            <th className="px-4 py-3 font-semibold text-neutral-700 dark:text-neutral-300">
                                                                Nama Kegiatan
                                                            </th>
                                                            <th className="px-4 py-3 font-semibold text-neutral-700 dark:text-neutral-300">
                                                                Target Pekerjaan
                                                            </th>
                                                            <th className="px-4 py-3 font-semibold text-neutral-700 dark:text-neutral-300">
                                                                Honor
                                                            </th>
                                                            <th className="px-4 py-3 font-semibold text-neutral-700 dark:text-neutral-300">
                                                                Status
                                                                PK/Addendum
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-neutral-200 dark:divide-neutral-800">
                                                        {selectedPenugasanList.map(
                                                            (item) => (
                                                                <tr
                                                                    key={
                                                                        item.id
                                                                    }
                                                                    className="bg-white/60 dark:bg-neutral-900/40"
                                                                >
                                                                    <td className="px-4 py-3 align-top">
                                                                        <div className="font-medium text-neutral-900 dark:text-white">
                                                                            {
                                                                                item.nama_kegiatan
                                                                            }
                                                                        </div>
                                                                        <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                                            {item.jenis_kegiatan ===
                                                                            'survei'
                                                                                ? 'Survei'
                                                                                : 'Sensus'}
                                                                        </div>
                                                                    </td>
                                                                    <td className="px-4 py-3 align-top text-neutral-700 dark:text-neutral-300">
                                                                        {
                                                                            item.target_pekerjaan
                                                                        }
                                                                    </td>
                                                                    <td className="px-4 py-3 align-top font-medium text-neutral-900 dark:text-white">
                                                                        {
                                                                            item.honor_label
                                                                        }
                                                                    </td>
                                                                    <td className="px-4 py-3 align-top">
                                                                        <span className="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                                            {
                                                                                item.document_status
                                                                            }
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            ),
                                                        )}
                                                    </tbody>
                                                    <tfoot className="bg-neutral-50 dark:bg-neutral-950/60">
                                                        <tr>
                                                            <th
                                                                colSpan={2}
                                                                className="px-4 py-3 text-sm font-semibold text-neutral-900 dark:text-white"
                                                            >
                                                                Total{' '}
                                                                {
                                                                    selectedPenugasanList.length
                                                                }{' '}
                                                                penugasan
                                                            </th>
                                                            <td className="px-4 py-3 text-sm font-semibold text-neutral-900 dark:text-white">
                                                                {`Rp ${new Intl.NumberFormat('id-ID').format(selectedPenugasanTotalHonor)}`}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm text-neutral-500 dark:text-neutral-400">
                                                                Akumulasi honor
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        ) : (
                                            <div className="px-4 py-8 text-sm text-neutral-500 sm:px-5 dark:text-neutral-400">
                                                Pilih jenis kegiatan dan periode
                                                atau kegiatan untuk menampilkan
                                                daftar penugasan.
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            <div className="rounded-xl border border-cyan-200 bg-cyan-50/80 px-4 py-3 text-sm text-cyan-800 dark:border-cyan-900/60 dark:bg-cyan-950/20 dark:text-cyan-300">
                                Data yang ditampilkan hanya milik petugas yang
                                diinput.
                            </div>

                            <div className="flex flex-wrap gap-3 pt-1">
                                <Button
                                    type="button"
                                    onClick={() => {
                                        const popup = window.open('', '_blank');
                                        void requestPdf('preview', popup);
                                    }}
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
                                    onClick={() => {
                                        const popup = window.open('', '_blank');
                                        if (popup && !popup.closed) {
                                            popup.onbeforeunload = () => {
                                                return 'Unduhan sedang diproses. Tunggu hingga selesai.';
                                            };
                                        }
                                        void requestPdf('download', popup);
                                    }}
                                    disabled={processing || !canSubmit}
                                    className="min-w-[160px]"
                                >
                                    <Download className="h-4 w-4" />
                                    {processing ? 'Memproses...' : 'Unduh PDF'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
