/**
 * Utility functions for optimized SPK downloads
 */

/**
 * Convert month number to Indonesian month label
 */
const getBulanLabel = (bulan: number): string => {
    const bulanMap: Record<number, string> = {
        1: 'Januari',
        2: 'Februari',
        3: 'Maret',
        4: 'April',
        5: 'Mei',
        6: 'Juni',
        7: 'Juli',
        8: 'Agustus',
        9: 'September',
        10: 'Oktober',
        11: 'November',
        12: 'Desember',
    };
    return bulanMap[bulan] || 'Unknown';
};

/**
 * Sanitize filename - same pattern as PHP backend
 */
const sanitizeFilename = (name: string): string => {
    return name.replace(/[/\\:*?"<>|]/g, '_');
};

const decodeSegment = (segment: string): string => {
    try {
        return decodeURIComponent(segment);
    } catch {
        return segment;
    }
};

const resolveDownloadFilename = (filePath: string): string => {
    const fallbackName = 'download-file';

    try {
        const url = normalizeDownloadUrl(filePath);
        const parsedUrl = new URL(url, window.location.origin);
        const lastSegment = parsedUrl.pathname.split('/').filter(Boolean).pop();

        if (!lastSegment) {
            return fallbackName;
        }

        return decodeSegment(lastSegment);
    } catch {
        return fallbackName;
    }
};

const getCsrfToken = (): string => {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || ''
    );
};

const sanitizeDownloadFilename = (filename: string): string => {
    return filename.replace(/[/\\:*?"<>|]/g, '_');
};

type DownloadPayloadValue = string | number | null | undefined;

type DownloadPayload = Record<
    string,
    DownloadPayloadValue | DownloadPayloadValue[]
>;

interface PreviewFromPostOptions {
    responseMode?: 'binary' | 'url';
}

const appendPayloadToForm = (
    form: HTMLFormElement,
    payload: DownloadPayload,
): void => {
    Object.entries(payload).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach((item) => {
                if (item !== null && item !== undefined) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = String(item);
                    form.appendChild(input);
                }
            });

            return;
        }

        if (value !== null && value !== undefined) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = String(value);
            form.appendChild(input);
        }
    });
};

const appendCsrfTokenToForm = (form: HTMLFormElement): void => {
    const token = getCsrfToken();
    if (!token) {
        return;
    }

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = token;
    form.appendChild(csrfInput);
};

const buildOverlayCard = (
    isDark: boolean,
): { overlay: HTMLDivElement; card: HTMLDivElement } => {
    const overlay = document.createElement('div');
    overlay.style.cssText = [
        'position:fixed',
        'inset:0',
        'z-index:9999',
        'display:flex',
        'align-items:center',
        'justify-content:center',
        'background:rgba(0,0,0,0.5)',
        'backdrop-filter:blur(4px)',
        '-webkit-backdrop-filter:blur(4px)',
    ].join(';');

    const card = document.createElement('div');
    card.style.cssText = [
        'display:flex',
        'flex-direction:column',
        'align-items:center',
        'gap:16px',
        'padding:32px 40px',
        'border-radius:16px',
        `background:${isDark ? 'rgba(23,23,23,0.97)' : 'rgba(255,255,255,0.97)'}`,
        `border:1px solid ${isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)'}`,
        'box-shadow:0 25px 50px -12px rgba(0,0,0,0.4)',
        'text-align:center',
        'max-width:280px',
    ].join(';');

    return { overlay, card };
};

const showPdfLoadingOverlay = (): (() => void) => {
    const isDark = document.documentElement.classList.contains('dark');
    const { overlay, card } = buildOverlayCard(isDark);

    const spinnerNs = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(spinnerNs, 'svg');
    svg.setAttribute('width', '40');
    svg.setAttribute('height', '40');
    svg.setAttribute('viewBox', '0 0 40 40');
    svg.setAttribute('fill', 'none');
    svg.style.cssText = 'animation:spin 0.8s linear infinite';

    const circle = document.createElementNS(spinnerNs, 'circle');
    circle.setAttribute('cx', '20');
    circle.setAttribute('cy', '20');
    circle.setAttribute('r', '16');
    circle.setAttribute('stroke', isDark ? '#525252' : '#e5e7eb');
    circle.setAttribute('stroke-width', '4');
    svg.appendChild(circle);

    const arc = document.createElementNS(spinnerNs, 'path');
    arc.setAttribute('d', 'M 20 4 A 16 16 0 0 1 36 20');
    arc.setAttribute('stroke', isDark ? '#d4d4d4' : '#111827');
    arc.setAttribute('stroke-width', '4');
    arc.setAttribute('stroke-linecap', 'round');
    svg.appendChild(arc);

    const style = document.createElement('style');
    style.textContent =
        '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
    document.head.appendChild(style);

    const title = document.createElement('p');
    title.textContent = 'Menyiapkan PDF...';
    title.style.cssText = [
        'margin:0',
        'font-size:15px',
        'font-weight:600',
        `color:${isDark ? '#f5f5f5' : '#111827'}`,
        'font-family:system-ui,sans-serif',
    ].join(';');

    const subtitle = document.createElement('p');
    subtitle.textContent = 'Harap tunggu sebentar';
    subtitle.style.cssText = [
        'margin:0',
        'font-size:13px',
        `color:${isDark ? '#a3a3a3' : '#6b7280'}`,
        'font-family:system-ui,sans-serif',
    ].join(';');

    card.appendChild(svg);
    card.appendChild(title);
    card.appendChild(subtitle);
    overlay.appendChild(card);
    document.body.appendChild(overlay);

    return () => {
        overlay.remove();
        style.remove();
    };
};

const showPdfLoadingOverlayWithProgress = (): {
    hide: () => void;
    setProgress: (pct: number) => void;
} => {
    const isDark = document.documentElement.classList.contains('dark');
    const { overlay, card } = buildOverlayCard(isDark);

    // Circular progress SVG
    const svgNs = 'http://www.w3.org/2000/svg';
    const svgSize = 72;
    const radius = 28;
    const circumference = 2 * Math.PI * radius;

    const svgEl = document.createElementNS(svgNs, 'svg');
    svgEl.setAttribute('width', String(svgSize));
    svgEl.setAttribute('height', String(svgSize));
    svgEl.setAttribute('viewBox', `0 0 ${svgSize} ${svgSize}`);
    svgEl.style.transform = 'rotate(-90deg)';

    const bgCircle = document.createElementNS(svgNs, 'circle');
    bgCircle.setAttribute('cx', String(svgSize / 2));
    bgCircle.setAttribute('cy', String(svgSize / 2));
    bgCircle.setAttribute('r', String(radius));
    bgCircle.setAttribute('fill', 'none');
    bgCircle.setAttribute('stroke', isDark ? '#404040' : '#e5e7eb');
    bgCircle.setAttribute('stroke-width', '6');
    svgEl.appendChild(bgCircle);

    const progressCircle = document.createElementNS(svgNs, 'circle');
    progressCircle.setAttribute('cx', String(svgSize / 2));
    progressCircle.setAttribute('cy', String(svgSize / 2));
    progressCircle.setAttribute('r', String(radius));
    progressCircle.setAttribute('fill', 'none');
    progressCircle.setAttribute('stroke', isDark ? '#d4d4d4' : '#111827');
    progressCircle.setAttribute('stroke-width', '6');
    progressCircle.setAttribute('stroke-linecap', 'round');
    progressCircle.setAttribute('stroke-dasharray', String(circumference));
    progressCircle.setAttribute('stroke-dashoffset', String(circumference));
    progressCircle.style.transition = 'stroke-dashoffset 0.35s ease';
    svgEl.appendChild(progressCircle);

    const svgWrapper = document.createElement('div');
    svgWrapper.style.cssText =
        'position:relative;display:inline-flex;align-items:center;justify-content:center;';
    svgWrapper.appendChild(svgEl);

    const pctLabel = document.createElement('span');
    pctLabel.textContent = '0%';
    pctLabel.style.cssText = [
        'position:absolute',
        'font-size:13px',
        'font-weight:700',
        `color:${isDark ? '#f5f5f5' : '#111827'}`,
        'font-family:system-ui,sans-serif',
    ].join(';');
    svgWrapper.appendChild(pctLabel);

    const title = document.createElement('p');
    title.textContent = 'Menyiapkan PDF...';
    title.style.cssText = [
        'margin:0',
        'font-size:15px',
        'font-weight:600',
        `color:${isDark ? '#f5f5f5' : '#111827'}`,
        'font-family:system-ui,sans-serif',
    ].join(';');

    const subtitle = document.createElement('p');
    subtitle.textContent = 'Memproses dokumen, harap tunggu';
    subtitle.style.cssText = [
        'margin:0',
        'font-size:13px',
        `color:${isDark ? '#a3a3a3' : '#6b7280'}`,
        'font-family:system-ui,sans-serif',
    ].join(';');

    card.style.maxWidth = '300px';
    card.appendChild(svgWrapper);
    card.appendChild(title);
    card.appendChild(subtitle);
    overlay.appendChild(card);
    document.body.appendChild(overlay);

    let lastPct = 0;

    const setProgress = (pct: number): void => {
        const clamped = Math.max(lastPct, Math.min(100, Math.round(pct)));
        lastPct = clamped;
        const offset = circumference - (clamped / 100) * circumference;
        progressCircle.setAttribute('stroke-dashoffset', String(offset));
        pctLabel.textContent = `${clamped}%`;

        if (clamped >= 100) {
            title.textContent = 'Membuka PDF...';
            subtitle.textContent = 'Selesai, sedang membuka dokumen';
        } else if (clamped > 80) {
            subtitle.textContent = 'Mengunduh dokumen...';
        }
    };

    return { hide: () => overlay.remove(), setProgress };
};

export const downloadFileFromPost = async (
    url: string,
    payload: DownloadPayload,
    defaultFilename: string,
): Promise<void> => {
    void defaultFilename;

    const hideOverlay = showPdfLoadingOverlay();

    try {
        const iframeName = `download-frame-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        const iframe = document.createElement('iframe');
        iframe.name = iframeName;
        iframe.style.display = 'none';
        document.body.appendChild(iframe);

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.target = iframeName;
        form.style.display = 'none';

        appendCsrfTokenToForm(form);
        appendPayloadToForm(form, payload);

        document.body.appendChild(form);
        form.submit();
        form.remove();

        window.setTimeout(() => {
            iframe.remove();
        }, 30000);

        await new Promise<void>((resolve) => {
            window.setTimeout(resolve, 1200);
        });
    } finally {
        hideOverlay();
    }
};

export const downloadFileFromGet = async (
    url: string,
    defaultFilename: string,
): Promise<void> => {
    void defaultFilename;

    const hideOverlay = showPdfLoadingOverlay();

    try {
        const link = document.createElement('a');
        link.href = url;
        link.download = sanitizeDownloadFilename(defaultFilename);
        link.rel = 'noopener noreferrer';
        document.body.appendChild(link);
        link.click();
        link.remove();

        await new Promise<void>((resolve) => {
            window.setTimeout(resolve, 900);
        });
    } finally {
        hideOverlay();
    }
};

export const previewFileFromPost = async (
    url: string,
    payload: DownloadPayload,
    defaultFilename: string,
    options: PreviewFromPostOptions = {},
): Promise<void> => {
    void defaultFilename;

    const responseMode = options.responseMode ?? 'binary';

    const { hide, setProgress } = showPdfLoadingOverlayWithProgress();

    // Simulated progress ticker for server-side PDF generation phase (0 → 88%)
    let fakePercent = 2;
    let useFakeProgress = true;
    const fakeInterval = window.setInterval(() => {
        if (!useFakeProgress) {
            return;
        }
        const remaining = 88 - fakePercent;
        fakePercent += Math.max(0.4, remaining * 0.045);
        setProgress(Math.min(88, fakePercent));
    }, 400);

    try {
        const formData = new FormData();
        const token = getCsrfToken();
        if (token) {
            formData.append('_token', token);
        }

        Object.entries(payload).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((item) => {
                    if (item !== null && item !== undefined) {
                        formData.append(key, String(item));
                    }
                });

                return;
            }

            if (value !== null && value !== undefined) {
                formData.append(key, String(value));
            }
        });

        if (responseMode === 'url') {
            formData.append('response_mode', 'url');

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
                body: formData,
            });

            if (!response.ok) {
                throw new Error(
                    `Server mengembalikan status ${response.status}.`,
                );
            }

            const payloadJson = (await response.json()) as {
                preview_url?: string;
                message?: string;
            };

            const previewUrl = payloadJson.preview_url;
            if (!previewUrl) {
                throw new Error(
                    payloadJson.message || 'URL preview tidak tersedia.',
                );
            }

            useFakeProgress = false;
            window.clearInterval(fakeInterval);
            setProgress(100);

            await new Promise<void>((resolve) =>
                window.setTimeout(resolve, 500),
            );

            const previewWindow = window.open(previewUrl, '_blank');
            if (!previewWindow || previewWindow.closed) {
                throw new Error('Browser memblokir popup.');
            }

            previewWindow.opener = null;
            previewWindow.focus();

            return;
        }

        const blob = await new Promise<Blob>((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url);
            xhr.responseType = 'blob';

            xhr.onprogress = (event) => {
                if (event.lengthComputable && event.total > 0) {
                    useFakeProgress = false;
                    window.clearInterval(fakeInterval);
                    // Map real download progress onto the 88–99% range
                    const realPct = (event.loaded / event.total) * 11 + 88;
                    setProgress(Math.min(99, realPct));
                }
            };

            xhr.onload = () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(xhr.response as Blob);
                } else {
                    reject(
                        new Error(`Server mengembalikan status ${xhr.status}.`),
                    );
                }
            };

            xhr.onerror = () => reject(new Error('Gagal terhubung ke server.'));
            xhr.onabort = () => reject(new Error('Permintaan dibatalkan.'));

            xhr.send(formData);
        });

        useFakeProgress = false;
        window.clearInterval(fakeInterval);
        setProgress(100);

        const blobUrl = URL.createObjectURL(blob);

        // Brief pause so user sees 100% complete
        await new Promise<void>((resolve) => window.setTimeout(resolve, 500));

        const previewWindow = window.open(blobUrl, '_blank');
        if (!previewWindow || previewWindow.closed) {
            throw new Error('Browser memblokir popup.');
        }

        previewWindow.opener = null;
        previewWindow.focus();

        // Revoke blob URL after 2 minutes (enough time for the tab to load it)
        window.setTimeout(() => URL.revokeObjectURL(blobUrl), 120_000);
    } finally {
        window.clearInterval(fakeInterval);
        hide();
    }
};

/**
 * Normalize user-provided file path into a safely encoded URL.
 * Handles existing encoded segments and file names with spaces.
 */
export const normalizeDownloadUrl = (filePath: string): string => {
    const trimmedPath = filePath.trim();

    if (/^https?:\/\//i.test(trimmedPath)) {
        try {
            const parsedUrl = new URL(trimmedPath);
            const normalizedPath = parsedUrl.pathname
                .split('/')
                .filter(Boolean)
                .map((segment) => encodeURIComponent(decodeSegment(segment)))
                .join('/');

            parsedUrl.pathname = `/${normalizedPath}`;

            return parsedUrl.toString();
        } catch {
            return trimmedPath;
        }
    }

    const normalizedSegments = trimmedPath
        .replace(/^\/+/, '')
        .split('/')
        .filter(Boolean)
        .map((segment) => encodeURIComponent(decodeSegment(segment)));

    return `/${normalizedSegments.join('/')}`;
};

/**
 * Download file with explicit filename and MIME fallback.
 * This prevents hosted environments from saving as generic "All Files".
 */
export const openFastDownload = (filePath: string): void => {
    const url = normalizeDownloadUrl(filePath);
    const filename = resolveDownloadFilename(filePath);

    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.rel = 'noopener noreferrer';
    document.body.appendChild(link);
    link.click();
    link.remove();
};

/**
 * Construct deterministic ZIP filename for downloadAll
 */
export const constructDownloadAllFilename = (
    bulan: number,
    tahun: number,
): string => {
    const bulanLabel = getBulanLabel(bulan);
    return `SPK_${bulanLabel}_${tahun}.zip`;
};

/**
 * Construct deterministic ZIP filename for BAST downloadAll
 */
export const constructBastDownloadFilename = (
    bulan: number,
    tahun: number,
    isLegacy = false,
): string => {
    const bulanLabel = getBulanLabel(bulan);
    return isLegacy
        ? `BAST_Signed_${bulanLabel}_${tahun}.zip`
        : `BAST_${bulanLabel}_${tahun}.zip`;
};

/**
 * Construct deterministic ZIP filename for downloadByKegiatan
 */
export const constructDownloadByKegiatanFilename = (
    kegiatanName: string,
    bulan: number,
    tahun: number,
): string => {
    const bulanLabel = getBulanLabel(bulan);
    const sanitizedKegiatan = sanitizeFilename(kegiatanName);
    return `SPK_${sanitizedKegiatan}_${bulanLabel}_${tahun}.zip`;
};

/**
 * Check if a static download URL exists and is accessible
 */
export const checkStaticDownloadUrl = async (
    filename: string,
): Promise<boolean> => {
    try {
        const url = `/downloads/${encodeURIComponent(filename)}`;
        const response = await fetch(url, {
            method: 'HEAD',
            cache: 'no-cache',
        });
        return response.ok;
    } catch {
        return false;
    }
};

/**
 * Optimized download with direct static URL attempt and fallback
 * Returns true if direct download initiated, false if needs generation
 */
export const tryDirectDownload = async (
    filename: string,
    fallbackUrl: string,
): Promise<void> => {
    const staticUrl = `/downloads/${encodeURIComponent(filename)}`;

    // Try HEAD request first to check if file exists
    const exists = await checkStaticDownloadUrl(filename);

    if (exists) {
        // Direct navigation to static URL - fast!
        window.location.href = staticUrl;
    } else {
        // Fallback to Laravel route for generation
        window.location.href = fallbackUrl;
    }
};
