import { Button } from '@/components/ui/button';
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
import {
    AlertCircle,
    CheckCircle2,
    ChevronDown,
    Download,
    Eye,
    FileText,
    Loader2,
    Search,
} from 'lucide-react';
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
    bast_status: string;
    bapp_termin_i_status: string | null;
    bapp_termin_ii_status: string | null;
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
const DOCUMENT_PROGRESS_RADIUS = 52;
const DOCUMENT_PROGRESS_CIRCUMFERENCE = 2 * Math.PI * DOCUMENT_PROGRESS_RADIUS;

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
    const [dokumenTipe, setDokumenTipe] = useState<
        'pk' | 'bast' | 'bapp_i' | 'bapp_ii'
    >('pk');
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [documentProgressOpen, setDocumentProgressOpen] = useState(false);
    const [documentProgressPercent, setDocumentProgressPercent] = useState(0);
    const [documentProgressStatus, setDocumentProgressStatus] = useState(
        'Menyiapkan dokumen PDF...',
    );
    const [documentProgressTitle, setDocumentProgressTitle] = useState(
        'Menyiapkan dokumen PDF...',
    );
    const [expandedStep, setExpandedStep] = useState<1 | 2 | 3 | null>(1);

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
        setDokumenTipe('pk');
        setExpandedStep(1);
    }, [nama, nik, telepon4Digit]);

    const availableJenisOptions = useMemo(() => {
        const options: Array<{ value: 'survei' | 'sensus'; label: string }> =
            [];

        const periodsWithHonor = ownedSurveiPeriods.filter((period) =>
            ownedPenugasanList.some(
                (item) =>
                    item.jenis_kegiatan === 'survei' &&
                    item.periode_key === period.value &&
                    item.honor > 0,
            ),
        );

        if (periodsWithHonor.length > 0) {
            options.push({ value: 'survei', label: 'Survei' });
        }

        if (ownedSensusKegiatans.length > 0) {
            options.push({ value: 'sensus', label: 'Sensus' });
        }

        return options;
    }, [ownedSurveiPeriods, ownedSensusKegiatans, ownedPenugasanList]);

    const filteredSurveiPeriods = useMemo(() => {
        return ownedSurveiPeriods.filter((period) =>
            ownedPenugasanList.some(
                (item) =>
                    item.jenis_kegiatan === 'survei' &&
                    item.periode_key === period.value &&
                    item.honor > 0,
            ),
        );
    }, [ownedSurveiPeriods, ownedPenugasanList]);

    useEffect(() => {
        if (isOptionsLoaded) {
            setExpandedStep(2);
        }
    }, [isOptionsLoaded]);

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

    useEffect(() => {
        if (!isOptionsLoaded) {
            return;
        }
        if (canSubmit) {
            setExpandedStep(3);
        } else {
            setExpandedStep(2);
        }
    }, [canSubmit, isOptionsLoaded]);

    const selectedPenugasanList = useMemo(() => {
        if (jenisKegiatan === 'survei' && surveiPeriode) {
            return ownedPenugasanList.filter(
                (item) =>
                    item.jenis_kegiatan === 'survei' &&
                    item.periode_key === surveiPeriode &&
                    item.honor > 0,
            );
        }

        if (jenisKegiatan === 'sensus' && sensusKegiatan) {
            return ownedPenugasanList.filter(
                (item) =>
                    item.jenis_kegiatan === 'sensus' &&
                    item.kegiatan_hashed_id === sensusKegiatan &&
                    item.honor > 0,
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

    const requestPdf = async (aksi: 'preview' | 'download'): Promise<void> => {
        setErrorMessage(null);

        if (!canSubmit) {
            setErrorMessage('Lengkapi data terlebih dahulu.');
            return;
        }

        setDocumentProgressOpen(true);
        setDocumentProgressPercent(0);
        setDocumentProgressTitle(
            aksi === 'preview'
                ? 'Menyiapkan dokumen Print/Preview...'
                : 'Menyiapkan dokumen Unduh PDF...',
        );
        setDocumentProgressStatus(
            aksi === 'preview'
                ? 'Memproses file PDF untuk print/preview...'
                : 'Memproses file PDF untuk diunduh...',
        );

        setProcessing(true);

        let previewProgressTimer: number | null = null;
        if (aksi === 'preview') {
            setDocumentProgressPercent(3);
            previewProgressTimer = window.setInterval(() => {
                setDocumentProgressPercent((current) => {
                    if (current >= 92) {
                        return current;
                    }

                    const next =
                        current +
                        Math.max(1, Math.round((92 - current) * 0.15));
                    return Math.min(92, next);
                });
            }, 250);
        }

        try {
            const formData = new FormData();
            formData.append('nama', nama.trim());
            formData.append('nik', nik.trim());
            formData.append('telepon_4_digit', telepon4Digit);
            formData.append('jenis_kegiatan', jenisKegiatan);
            formData.append('aksi', aksi);

            const backendDokumenTipe =
                dokumenTipe === 'bapp_i' || dokumenTipe === 'bapp_ii'
                    ? 'bapp'
                    : dokumenTipe;
            formData.append('dokumen_tipe', backendDokumenTipe);

            if (dokumenTipe === 'bapp_i') {
                formData.append('bapp_termin', '1');
            } else if (dokumenTipe === 'bapp_ii') {
                formData.append('bapp_termin', '2');
            }

            if (aksi === 'preview') {
                formData.append('response_mode', 'url');
            }

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
                        Accept:
                            aksi === 'preview'
                                ? 'application/json'
                                : 'application/pdf,application/json,*/*',
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
                setDocumentProgressOpen(false);
                return;
            }

            if (aksi === 'preview') {
                if (previewProgressTimer !== null) {
                    window.clearInterval(previewProgressTimer);
                    previewProgressTimer = null;
                }

                const payload = (await response.json()) as {
                    preview_url?: string;
                    message?: string;
                };

                if (!payload.preview_url) {
                    setErrorMessage(
                        payload.message ||
                            'URL preview tidak tersedia. Silakan coba lagi.',
                    );
                    setDocumentProgressOpen(false);
                    return;
                }

                setDocumentProgressTitle('Membuka tab Print/Preview...');
                setDocumentProgressStatus(
                    'File siap. Membuka tab print/preview...',
                );
                setDocumentProgressPercent(100);

                window.setTimeout(() => {
                    const previewTab = window.open(
                        payload.preview_url,
                        '_blank',
                    );
                    if (!previewTab || previewTab.closed) {
                        setErrorMessage(
                            'Pratinjau sudah siap, tetapi browser memblokir popup. Izinkan popup lalu coba lagi.',
                        );
                    } else {
                        previewTab.opener = null;
                        previewTab.focus();
                    }

                    setDocumentProgressOpen(false);
                }, 250);

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

                setDocumentProgressStatus('Menyiapkan file PDF...');
                setDocumentProgressPercent(0);

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
                            const progressValue =
                                (loadedBytes / totalBytes) * 100;

                            setDocumentProgressStatus('Menyiapkan file PDF...');
                            setDocumentProgressPercent(
                                Math.max(
                                    0,
                                    Math.min(100, Math.round(progressValue)),
                                ),
                            );
                        }
                    }
                }

                blob = new Blob(chunks, { type: 'application/pdf' });
            } else {
                setDocumentProgressStatus('Menyiapkan file PDF...');
                blob = await response.blob();
            }

            const objectUrl = URL.createObjectURL(
                blob.type === 'application/pdf'
                    ? blob
                    : new Blob([await blob.arrayBuffer()], {
                          type: 'application/pdf',
                      }),
            );

            const fileName = extractFilename(
                response.headers.get('content-disposition'),
            );

            setDocumentProgressPercent(100);

            setDocumentProgressTitle('Mengunduh PDF...');
            setDocumentProgressStatus('File siap. Memulai unduh PDF...');

            window.setTimeout(() => {
                const link = document.createElement('a');
                link.href = objectUrl;
                link.download = fileName;
                link.rel = 'noopener noreferrer';
                document.body.appendChild(link);
                link.click();
                link.remove();

                setDocumentProgressOpen(false);
            }, 250);

            window.setTimeout(() => {
                URL.revokeObjectURL(objectUrl);
            }, 60000);
        } catch (error) {
            setDocumentProgressOpen(false);

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
            if (previewProgressTimer !== null) {
                window.clearInterval(previewProgressTimer);
            }

            setProcessing(false);
        }
    };

    return (
        <>
            <Head title={`Portal Dokumen Mitra ${active_year}`} />

            {/* Page shell */}
            <div className="min-h-screen bg-slate-50/70 dark:bg-[radial-gradient(ellipse_at_top_left,_#0a1f3d_0%,_#0d1e2e_40%,_#061a1a_80%,_#031212_100%)]">
                {/* Top bar */}
                <div className="fixed top-0 right-0 left-0 z-50 border-b border-neutral-200/70 bg-white/90 backdrop-blur-md dark:border-white/10 dark:bg-slate-900/85">
                    <div className="mx-auto flex max-w-6xl items-center gap-4 px-6 py-5 sm:px-10">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 shadow-sm shadow-indigo-500/20">
                            <FileText className="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <p className="text-[11px] font-bold tracking-[0.18em] text-indigo-500 uppercase dark:text-indigo-400">
                                BPS · Layanan Mitra
                            </p>
                            <h1 className="text-lg leading-tight font-bold text-neutral-900 dark:text-white">
                                Portal Dokumen Mitra {active_year}
                            </h1>
                        </div>
                    </div>
                </div>

                <div className="mx-auto max-w-6xl space-y-5 px-6 pt-[88px] pb-8 sm:px-10 sm:pb-10">
                    {/* Error banner */}
                    {errorMessage && (
                        <div className="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                            <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{errorMessage}</span>
                        </div>
                    )}

                    {/* ── Step 1 · Identitas ──────────────────────────────────── */}
                    <div className="overflow-hidden rounded-2xl border border-white/70 bg-white/75 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5 dark:backdrop-blur-md">
                        <button
                            type="button"
                            onClick={() =>
                                setExpandedStep((v) => (v === 1 ? null : 1))
                            }
                            className="flex w-full cursor-pointer items-center gap-3 border-b border-neutral-200/60 bg-white/50 px-6 py-4 text-left hover:bg-white/70 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10"
                        >
                            <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                                1
                            </span>
                            <span className="text-sm font-semibold text-neutral-800 dark:text-white">
                                Verifikasi Identitas
                            </span>
                            {expandedStep !== 1 && loadedPetugasName && (
                                <span className="ml-2 flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    <CheckCircle2 className="h-3 w-3" />
                                    {loadedPetugasName}
                                </span>
                            )}
                            {isOptionsLoaded ? (
                                <ChevronDown
                                    className={`ml-auto h-4 w-4 shrink-0 text-neutral-500 transition-transform duration-200 dark:text-neutral-300 ${
                                        expandedStep !== 1 ? '' : 'rotate-180'
                                    }`}
                                />
                            ) : (
                                <ChevronDown className="ml-auto h-4 w-4 shrink-0 rotate-180 text-neutral-500 dark:text-neutral-300" />
                            )}
                        </button>

                        {expandedStep === 1 && (
                            <div className="p-6 sm:p-8">
                                <div className="grid gap-5 sm:grid-cols-[1fr_1fr_190px]">
                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="nama"
                                            className="text-sm font-semibold text-neutral-700 dark:text-neutral-100"
                                        >
                                            Nama Lengkap
                                        </Label>
                                        <Input
                                            id="nama"
                                            value={nama}
                                            onChange={(e) =>
                                                setNama(e.target.value)
                                            }
                                            placeholder="Contoh: Sena Susanto"
                                            autoComplete="name"
                                            className="h-11 text-base"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="nik"
                                            className="text-sm font-semibold text-neutral-700 dark:text-neutral-100"
                                        >
                                            NIK
                                        </Label>
                                        <Input
                                            id="nik"
                                            value={nik}
                                            onChange={(e) =>
                                                setNik(e.target.value)
                                            }
                                            placeholder="16 digit NIK"
                                            inputMode="numeric"
                                            autoComplete="off"
                                            className="h-11 text-base"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label
                                            htmlFor="telepon-4-digit"
                                            className="text-sm font-semibold text-neutral-700 dark:text-neutral-100"
                                        >
                                            4 Digit Terakhir HP
                                        </Label>
                                        <Input
                                            id="telepon-4-digit"
                                            value={telepon4Digit}
                                            onChange={(e) =>
                                                setTelepon4Digit(
                                                    e.target.value
                                                        .replace(/\D/g, '')
                                                        .slice(0, 4),
                                                )
                                            }
                                            placeholder="1234"
                                            inputMode="numeric"
                                            maxLength={4}
                                            autoComplete="off"
                                            className="h-11 text-base"
                                        />
                                    </div>
                                </div>

                                <p className="mt-3 text-sm text-neutral-500 dark:text-neutral-300">
                                    Nomor HP yang terdaftar di SOBAT/sistem BPS.
                                </p>

                                <div className="mt-5 flex flex-wrap items-center gap-4">
                                    <Button
                                        type="button"
                                        onClick={() =>
                                            void loadPetugasOptions()
                                        }
                                        disabled={
                                            loadingOptions ||
                                            processing ||
                                            (!recaptchaReady &&
                                                !!recaptcha_site_key)
                                        }
                                        className="gap-2"
                                    >
                                        {loadingOptions ? (
                                            <>
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                                Memuat Data...
                                            </>
                                        ) : (
                                            <>
                                                <Search className="h-4 w-4" />
                                                Cari Data Saya
                                            </>
                                        )}
                                    </Button>

                                    {isOptionsLoaded && loadedPetugasName && (
                                        <div className="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            <CheckCircle2 className="h-4 w-4 shrink-0" />
                                            <span>{loadedPetugasName}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* ── Step 2 · Pilih Kegiatan ─────────────────────────────── */}
                    {isOptionsLoaded && (
                        <div className="overflow-hidden rounded-2xl border border-white/70 bg-white/75 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5 dark:backdrop-blur-md">
                            <button
                                type="button"
                                onClick={() =>
                                    canSubmit
                                        ? setExpandedStep((v) =>
                                              v === 2 ? null : 2,
                                          )
                                        : undefined
                                }
                                className={`flex w-full items-center gap-3 bg-white/50 px-6 py-4 text-left dark:bg-white/5 ${
                                    canSubmit
                                        ? 'cursor-pointer hover:bg-white/70 dark:hover:bg-white/10'
                                        : 'cursor-default'
                                } ${
                                    expandedStep === 2
                                        ? 'border-b border-neutral-200/60 dark:border-white/10'
                                        : ''
                                }`}
                            >
                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                                    2
                                </span>
                                <span className="text-sm font-semibold text-neutral-800 dark:text-white">
                                    Pilih Kegiatan
                                </span>
                                {expandedStep !== 2 && canSubmit && (
                                    <span className="ml-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-800/60 dark:bg-indigo-950/40 dark:text-indigo-300">
                                        {jenisKegiatan === 'survei'
                                            ? `Survei · ${
                                                  filteredSurveiPeriods.find(
                                                      (p) =>
                                                          p.value ===
                                                          surveiPeriode,
                                                  )?.label ?? surveiPeriode
                                              }`
                                            : `Sensus · ${
                                                  ownedSensusKegiatans.find(
                                                      (k) =>
                                                          k.value ===
                                                          sensusKegiatan,
                                                  )?.label ?? sensusKegiatan
                                              }`}
                                    </span>
                                )}
                                {canSubmit && (
                                    <ChevronDown
                                        className={`ml-auto h-4 w-4 shrink-0 text-neutral-500 transition-transform duration-200 dark:text-neutral-300 ${
                                            expandedStep !== 2
                                                ? ''
                                                : 'rotate-180'
                                        }`}
                                    />
                                )}
                            </button>

                            {expandedStep === 2 && (
                                <div className="p-6 sm:p-8">
                                    <div className="grid gap-5 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label
                                                htmlFor="jenis-kegiatan"
                                                className="text-sm font-semibold text-neutral-700 dark:text-neutral-100"
                                            >
                                                Jenis Kegiatan
                                            </Label>
                                            <Select
                                                value={jenisKegiatan}
                                                onValueChange={(
                                                    value: 'survei' | 'sensus',
                                                ) => setJenisKegiatan(value)}
                                                disabled={
                                                    availableJenisOptions.length ===
                                                    0
                                                }
                                            >
                                                <SelectTrigger
                                                    id="jenis-kegiatan"
                                                    className="h-11 text-base"
                                                >
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
                                                                {option.label}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        {jenisKegiatan && (
                                            <div className="space-y-2">
                                                <Label
                                                    htmlFor="opsi-kegiatan"
                                                    className="text-sm font-semibold text-neutral-700 dark:text-neutral-100"
                                                >
                                                    {jenisKegiatan === 'survei'
                                                        ? 'Periode Bulan Survei'
                                                        : 'Kegiatan Sensus'}
                                                </Label>

                                                {jenisKegiatan === 'survei' ? (
                                                    <Select
                                                        value={surveiPeriode}
                                                        onValueChange={
                                                            setSurveiPeriode
                                                        }
                                                        disabled={
                                                            filteredSurveiPeriods.length ===
                                                            0
                                                        }
                                                    >
                                                        <SelectTrigger
                                                            id="opsi-kegiatan"
                                                            className="h-11 text-base"
                                                        >
                                                            <SelectValue placeholder="Pilih periode survei" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {filteredSurveiPeriods.map(
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
                                                        onValueChange={
                                                            setSensusKegiatan
                                                        }
                                                        disabled={
                                                            ownedSensusKegiatans.length ===
                                                            0
                                                        }
                                                    >
                                                        <SelectTrigger
                                                            id="opsi-kegiatan"
                                                            className="h-11 text-base"
                                                        >
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
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* ── Step 3 · Penugasan & Dokumen ────────────────────────── */}
                    {canSubmit && selectedPenugasanList.length > 0 && (
                        <div className="overflow-hidden rounded-2xl border border-white/70 bg-white/75 shadow-sm backdrop-blur-md dark:border-white/10 dark:bg-white/5 dark:backdrop-blur-md">
                            {/* Step 3 header */}
                            <button
                                type="button"
                                onClick={() =>
                                    setExpandedStep((v) => (v === 3 ? null : 3))
                                }
                                className={`flex w-full cursor-pointer items-center gap-3 bg-white/50 px-6 py-4 text-left hover:bg-white/70 dark:bg-white/5 dark:hover:bg-white/10 ${
                                    expandedStep === 3
                                        ? 'border-b border-neutral-200/60 dark:border-white/10'
                                        : ''
                                }`}
                            >
                                <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                                    3
                                </span>
                                <span className="text-sm font-semibold text-neutral-800 dark:text-white">
                                    Penugasan &amp; Dokumen
                                </span>
                                {expandedStep !== 3 && (
                                    <span className="ml-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-800/60 dark:bg-indigo-950/40 dark:text-indigo-300">
                                        {selectedPenugasanCardTitle} &middot;{' '}
                                        {`Rp ${new Intl.NumberFormat('id-ID').format(selectedPenugasanTotalHonor)}`}
                                    </span>
                                )}
                                <ChevronDown
                                    className={`ml-auto h-4 w-4 shrink-0 text-neutral-500 transition-transform duration-200 dark:text-neutral-300 ${
                                        expandedStep !== 3 ? '' : 'rotate-180'
                                    }`}
                                />
                            </button>

                            {expandedStep === 3 && (
                                <div className="space-y-0 divide-y divide-neutral-200/60 dark:divide-white/10">
                                    {/* Penugasan section */}
                                    <div>
                                        <div className="border-b border-neutral-200/60 bg-white/50 px-6 py-5 dark:border-white/10 dark:bg-white/5">
                                            <div className="flex flex-wrap items-start justify-between gap-4">
                                                <div>
                                                    <h2 className="text-lg font-bold text-neutral-900 dark:text-white">
                                                        {
                                                            selectedPenugasanCardTitle
                                                        }
                                                    </h2>
                                                    {selectedPenugasanPeriodLabel && (
                                                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                            {jenisKegiatan ===
                                                            'sensus'
                                                                ? `Periode: ${selectedPenugasanPeriodLabel}`
                                                                : selectedPenugasanPeriodLabel}
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="flex flex-wrap gap-2">
                                                    <span className="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                        PK:{' '}
                                                        {
                                                            selectedPenugasanList[0]
                                                                ?.document_status
                                                        }
                                                    </span>
                                                    {selectedPenugasanList[0]
                                                        ?.bapp_termin_i_status !==
                                                        null && (
                                                        <span className="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 dark:border-violet-900/50 dark:bg-violet-950/30 dark:text-violet-300">
                                                            BAPP I:{' '}
                                                            {
                                                                selectedPenugasanList[0]
                                                                    ?.bapp_termin_i_status
                                                            }
                                                        </span>
                                                    )}
                                                    {selectedPenugasanList[0]
                                                        ?.bapp_termin_ii_status !==
                                                        null && (
                                                        <span className="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 dark:border-violet-900/50 dark:bg-violet-950/30 dark:text-violet-300">
                                                            BAPP II:{' '}
                                                            {
                                                                selectedPenugasanList[0]
                                                                    ?.bapp_termin_ii_status
                                                            }
                                                        </span>
                                                    )}
                                                    <span className="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
                                                        BAST:{' '}
                                                        {
                                                            selectedPenugasanList[0]
                                                                ?.bast_status
                                                        }
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-neutral-100 dark:divide-neutral-800">
                                                <thead>
                                                    <tr className="bg-neutral-50/50 dark:bg-neutral-950/40">
                                                        <th className="px-6 py-3 text-left text-xs font-bold tracking-wide text-neutral-500 uppercase dark:text-neutral-300">
                                                            Kegiatan
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-bold tracking-wide text-neutral-500 uppercase dark:text-neutral-300">
                                                            Target Pekerjaan
                                                        </th>
                                                        <th className="px-6 py-3 text-right text-xs font-bold tracking-wide text-neutral-500 uppercase dark:text-neutral-300">
                                                            Honor
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-neutral-100 dark:divide-neutral-800">
                                                    {selectedPenugasanList.map(
                                                        (item) => (
                                                            <tr
                                                                key={item.id}
                                                                className="transition-colors hover:bg-indigo-50/30 dark:hover:bg-white/5"
                                                            >
                                                                <td className="px-6 py-4">
                                                                    <div className="font-semibold text-neutral-900 dark:text-white">
                                                                        {
                                                                            item.nama_kegiatan
                                                                        }
                                                                    </div>
                                                                </td>
                                                                <td className="px-6 py-4 text-neutral-700 dark:text-neutral-200">
                                                                    {
                                                                        item.target_pekerjaan
                                                                    }
                                                                </td>
                                                                <td className="px-6 py-4 text-right font-bold text-neutral-900 dark:text-white">
                                                                    {
                                                                        item.honor_label
                                                                    }
                                                                </td>
                                                            </tr>
                                                        ),
                                                    )}
                                                </tbody>
                                                <tfoot>
                                                    <tr className="border-t border-neutral-200 bg-neutral-50/60 dark:border-neutral-800 dark:bg-neutral-950/40">
                                                        <td
                                                            colSpan={2}
                                                            className="px-6 py-4 text-sm font-medium text-neutral-600 dark:text-neutral-300"
                                                        >
                                                            Total{' '}
                                                            {
                                                                selectedPenugasanList.length
                                                            }{' '}
                                                            penugasan
                                                        </td>
                                                        <td className="px-6 py-4 text-right text-base font-bold text-neutral-900 dark:text-white">
                                                            {`Rp ${new Intl.NumberFormat('id-ID').format(selectedPenugasanTotalHonor)}`}
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                    {/* Dokumen section */}
                                    <div className="p-6 sm:p-8">
                                        <div
                                            className={`grid gap-3 ${
                                                jenisKegiatan === 'sensus'
                                                    ? 'sm:grid-cols-2 lg:grid-cols-4'
                                                    : 'sm:grid-cols-2'
                                            }`}
                                        >
                                            {/* PK */}
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setDokumenTipe('pk')
                                                }
                                                className={`rounded-xl border p-5 text-left transition-all ${
                                                    dokumenTipe === 'pk'
                                                        ? 'border-indigo-300 bg-indigo-50 ring-2 ring-indigo-200 dark:border-indigo-700 dark:bg-indigo-950/30 dark:ring-indigo-800'
                                                        : 'border-neutral-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/40 dark:border-neutral-700 dark:bg-neutral-800/40 dark:hover:border-indigo-800'
                                                }`}
                                            >
                                                <div className="text-[11px] font-bold tracking-widest text-neutral-500 uppercase dark:text-neutral-300"></div>
                                                <div
                                                    className={`mt-2 text-sm font-semibold ${
                                                        dokumenTipe === 'pk'
                                                            ? 'text-indigo-700 dark:text-indigo-300'
                                                            : 'text-neutral-700 dark:text-neutral-100'
                                                    }`}
                                                >
                                                    Perjanjian Kerja
                                                </div>
                                                <div className="mt-1 text-xs text-neutral-500 dark:text-neutral-300">
                                                    Kontrak penugasan resmi
                                                </div>
                                            </button>

                                            {/* BAPP I */}
                                            {jenisKegiatan === 'sensus' && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setDokumenTipe('bapp_i')
                                                    }
                                                    className={`rounded-xl border p-5 text-left transition-all ${
                                                        dokumenTipe === 'bapp_i'
                                                            ? 'border-violet-300 bg-violet-50 ring-2 ring-violet-200 dark:border-violet-700 dark:bg-violet-950/30 dark:ring-violet-800'
                                                            : 'border-neutral-200 bg-white hover:border-violet-200 hover:bg-violet-50/40 dark:border-neutral-700 dark:bg-neutral-800/40 dark:hover:border-violet-800'
                                                    }`}
                                                >
                                                    <div
                                                        className={`mt-2 text-sm font-semibold ${
                                                            dokumenTipe ===
                                                            'bapp_i'
                                                                ? 'text-violet-700 dark:text-violet-300'
                                                                : 'text-neutral-700 dark:text-neutral-100'
                                                        }`}
                                                    >
                                                        Pemeriksaan Tahap I
                                                    </div>
                                                    <div className="mt-1 text-xs text-neutral-500 dark:text-neutral-300">
                                                        Realisasi 40%
                                                    </div>
                                                </button>
                                            )}

                                            {/* BAPP II */}
                                            {jenisKegiatan === 'sensus' && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setDokumenTipe(
                                                            'bapp_ii',
                                                        )
                                                    }
                                                    className={`rounded-xl border p-5 text-left transition-all ${
                                                        dokumenTipe ===
                                                        'bapp_ii'
                                                            ? 'border-violet-300 bg-violet-50 ring-2 ring-violet-200 dark:border-violet-700 dark:bg-violet-950/30 dark:ring-violet-800'
                                                            : 'border-neutral-200 bg-white hover:border-violet-200 hover:bg-violet-50/40 dark:border-neutral-700 dark:bg-neutral-800/40 dark:hover:border-violet-800'
                                                    }`}
                                                >
                                                    <div
                                                        className={`mt-2 text-sm font-semibold ${
                                                            dokumenTipe ===
                                                            'bapp_ii'
                                                                ? 'text-violet-700 dark:text-violet-300'
                                                                : 'text-neutral-700 dark:text-neutral-100'
                                                        }`}
                                                    >
                                                        Pemeriksaan Tahap II
                                                    </div>
                                                    <div className="mt-1 text-xs text-neutral-500 dark:text-neutral-300">
                                                        Realisasi 60%
                                                    </div>
                                                </button>
                                            )}

                                            {/* BAST */}
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setDokumenTipe('bast')
                                                }
                                                className={`rounded-xl border p-5 text-left transition-all ${
                                                    dokumenTipe === 'bast'
                                                        ? 'border-sky-300 bg-sky-50 ring-2 ring-sky-200 dark:border-sky-700 dark:bg-sky-950/30 dark:ring-sky-800'
                                                        : 'border-neutral-200 bg-white hover:border-sky-200 hover:bg-sky-50/40 dark:border-neutral-700 dark:bg-neutral-800/40 dark:hover:border-sky-800'
                                                }`}
                                            >
                                                <div
                                                    className={`mt-2 text-sm font-semibold ${
                                                        dokumenTipe === 'bast'
                                                            ? 'text-sky-700 dark:text-sky-300'
                                                            : 'text-neutral-700 dark:text-neutral-100'
                                                    }`}
                                                >
                                                    Serah Terima
                                                </div>
                                                <div className="mt-1 text-xs text-neutral-500 dark:text-neutral-300">
                                                    Penyelesaian pekerjaan
                                                </div>
                                            </button>
                                        </div>

                                        <div className="mt-6 flex flex-wrap gap-3">
                                            <Button
                                                type="button"
                                                onClick={() => {
                                                    void requestPdf('preview');
                                                }}
                                                disabled={
                                                    processing || !canSubmit
                                                }
                                                className="gap-2"
                                            >
                                                <Eye className="h-4 w-4" />
                                                {processing
                                                    ? 'Memproses...'
                                                    : 'Print / Preview PDF'}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    void requestPdf('download');
                                                }}
                                                disabled={
                                                    processing || !canSubmit
                                                }
                                                className="gap-2"
                                            >
                                                <Download className="h-4 w-4" />
                                                {processing
                                                    ? 'Memproses...'
                                                    : 'Unduh PDF'}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Privacy note */}
                    <p className="text-center text-sm text-neutral-500 dark:text-neutral-400">
                        Data yang ditampilkan hanya milik petugas yang
                        bersangkutan.
                    </p>
                </div>
            </div>

            {/* Progress overlay */}
            {documentProgressOpen && (
                <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-black/55 px-4">
                    <div className="w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">
                        <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                            {documentProgressTitle}
                        </h3>
                        <p className="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">
                            {documentProgressStatus}
                        </p>
                        <div className="mt-5 flex justify-center">
                            <div className="relative flex h-36 w-36 items-center justify-center">
                                <svg
                                    className="h-36 w-36 -rotate-90"
                                    viewBox="0 0 140 140"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="70"
                                        cy="70"
                                        r={DOCUMENT_PROGRESS_RADIUS}
                                        stroke="currentColor"
                                        strokeWidth="10"
                                        fill="none"
                                        className="text-neutral-200 dark:text-neutral-700"
                                    />
                                    <circle
                                        cx="70"
                                        cy="70"
                                        r={DOCUMENT_PROGRESS_RADIUS}
                                        stroke="currentColor"
                                        strokeWidth="10"
                                        fill="none"
                                        strokeLinecap="round"
                                        className="text-emerald-500 transition-all duration-300 ease-out"
                                        style={{
                                            strokeDasharray:
                                                DOCUMENT_PROGRESS_CIRCUMFERENCE,
                                            strokeDashoffset:
                                                DOCUMENT_PROGRESS_CIRCUMFERENCE -
                                                (documentProgressPercent /
                                                    100) *
                                                    DOCUMENT_PROGRESS_CIRCUMFERENCE,
                                        }}
                                    />
                                </svg>
                                <div className="absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-3xl font-bold text-neutral-900 dark:text-neutral-100">
                                        {documentProgressPercent}%
                                    </span>
                                    <span className="mt-1 text-[11px] font-medium tracking-[0.2em] text-neutral-500 uppercase dark:text-neutral-400">
                                        Progress
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p className="mt-4 text-xs text-neutral-400 dark:text-neutral-500">
                            Mohon tunggu. Browser akan membuka tab preview atau
                            memulai unduh PDF sesuai aksi yang dipilih.
                        </p>
                    </div>
                </div>
            )}
        </>
    );
}
