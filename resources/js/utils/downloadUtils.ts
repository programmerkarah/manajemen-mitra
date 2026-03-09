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
    const { blob, filename } = await requestFileFromPost(
        url,
        payload,
        defaultFilename,
    );

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

    const fallbackDirectDownload = () => {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.rel = 'noopener noreferrer';
        document.body.appendChild(link);
        link.click();
        link.remove();
    };

    void fetch(url, {
        method: 'GET',
        credentials: 'include',
        cache: 'no-store',
    })
        .then(async (response) => {
            if (!response.ok) {
                throw new Error(
                    `Download failed with status ${response.status}`,
                );
            }

            const contentType = resolveContentType(
                response.headers.get('content-type'),
                filename,
            );
            const fileBuffer = await response.arrayBuffer();
            const blob = new Blob([fileBuffer], { type: contentType });
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
        })
        .catch(() => {
            fallbackDirectDownload();
        });
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
