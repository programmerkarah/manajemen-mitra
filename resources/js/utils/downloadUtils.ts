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

const inferMimeType = (filename: string): string => {
    const lowerFilename = filename.toLowerCase();

    if (lowerFilename.endsWith('.pdf')) {
        return 'application/pdf';
    }

    if (lowerFilename.endsWith('.zip')) {
        return 'application/zip';
    }

    return 'application/octet-stream';
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

const resolveContentType = (
    responseContentType: string | null,
    filename: string,
): string => {
    if (!responseContentType) {
        return inferMimeType(filename);
    }

    const normalized = responseContentType.toLowerCase();

    if (
        normalized.includes('application/octet-stream') ||
        normalized.includes('binary/octet-stream') ||
        normalized.includes('text/plain')
    ) {
        return inferMimeType(filename);
    }

    return responseContentType.split(';')[0].trim();
};

const getCsrfToken = (): string => {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || ''
    );
};

const extractFilenameFromContentDisposition = (
    contentDisposition: string | null,
): string | null => {
    if (!contentDisposition) {
        return null;
    }

    const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) {
        return decodeSegment(utf8Match[1]);
    }

    const quotedMatch = contentDisposition.match(/filename="([^"]+)"/i);
    if (quotedMatch?.[1]) {
        return quotedMatch[1];
    }

    const plainMatch = contentDisposition.match(/filename=([^;]+)/i);
    if (plainMatch?.[1]) {
        return plainMatch[1].trim();
    }

    return null;
};

const sanitizeDownloadFilename = (filename: string): string => {
    return filename.replace(/[/\\:*?"<>|]/g, '_');
};

type DownloadPayloadValue = string | number | null | undefined;

type DownloadPayload = Record<
    string,
    DownloadPayloadValue | DownloadPayloadValue[]
>;

interface PostFileResult {
    blob: Blob;
    filename: string;
}

const requestFileFromPost = async (
    url: string,
    payload: DownloadPayload,
    defaultFilename: string,
): Promise<PostFileResult> => {
    const formData = new FormData();

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

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/pdf,application/octet-stream,*/*',
        },
        body: formData,
    });

    if (!response.ok) {
        throw new Error(`Download failed with status ${response.status}`);
    }

    const contentDisposition = response.headers.get('content-disposition');
    const filenameFromHeader =
        extractFilenameFromContentDisposition(contentDisposition);
    const resolvedFilename = sanitizeDownloadFilename(
        filenameFromHeader || defaultFilename,
    );

    const rawBlob = await response.blob();
    const resolvedType = resolveContentType(
        response.headers.get('content-type') || rawBlob.type,
        resolvedFilename,
    );

    const blob =
        rawBlob.type === resolvedType
            ? rawBlob
            : new Blob([await rawBlob.arrayBuffer()], { type: resolvedType });

    return {
        blob,
        filename: resolvedFilename,
    };
};

const showPdfLoadingOverlay = (): (() => void) => {
    const isDark = document.documentElement.classList.contains('dark');

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

export const downloadFileFromPost = async (
    url: string,
    payload: DownloadPayload,
    defaultFilename: string,
): Promise<void> => {
    const { blob, filename } = await requestFileFromPost(
        url,
        payload,
        defaultFilename,
    );

    const blobUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = blobUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();

    setTimeout(() => {
        URL.revokeObjectURL(blobUrl);
    }, 0);
};

export const previewFileFromPost = async (
    url: string,
    payload: DownloadPayload,
    defaultFilename: string,
): Promise<void> => {
    const hideOverlay = showPdfLoadingOverlay();

    let blob: Blob;
    let filename: string;

    try {
        ({ blob, filename } = await requestFileFromPost(
            url,
            payload,
            defaultFilename,
        ));
    } finally {
        hideOverlay();
    }

    const blobUrl = URL.createObjectURL(blob);
    const previewWindow = window.open('', '_blank');

    if (!previewWindow) {
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => {
            URL.revokeObjectURL(blobUrl);
        }, 0);

        return;
    }

    const safeFilename = filename
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    previewWindow.document.open();
    previewWindow.document.write(`<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>${safeFilename}</title>
    <style>
        html, body { height: 100%; margin: 0; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-bottom: 1px solid #e5e7eb; font-family: sans-serif; }
        .filename { font-size: 14px; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .btn { border: 1px solid #d1d5db; border-radius: 6px; padding: 6px 10px; background: #fff; color: #111827; cursor: pointer; }
        iframe { width: 100%; height: calc(100% - 48px); border: 0; display: block; }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="filename" title="${safeFilename}">${safeFilename}</div>
        <button class="btn" id="download-btn" type="button">Download PDF</button>
    </div>
    <iframe src="${blobUrl}" title="Preview PDF"></iframe>
    <script>
        const blobUrl = ${JSON.stringify(blobUrl)};
        const filename = ${JSON.stringify(filename)};
        document.getElementById('download-btn')?.addEventListener('click', () => {
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
        window.addEventListener('beforeunload', () => {
            try { URL.revokeObjectURL(blobUrl); } catch {}
        });
    </script>
</body>
</html>`);
    previewWindow.document.close();
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
): string => {
    const bulanLabel = getBulanLabel(bulan);
    return `BAST_${bulanLabel}_${tahun}.zip`;
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
