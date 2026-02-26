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
