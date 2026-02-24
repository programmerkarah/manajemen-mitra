import CryptoJS from 'crypto-js';

// Secret key untuk enkripsi - harus sama dengan FILTER_ENCRYPTION_KEY di .env
const SECRET_KEY =
    import.meta.env.VITE_FILTER_ENCRYPTION_KEY ||
    'manajemen-mitra-filter-key-2025';

/**
 * Encrypt data using AES encryption
 * Compatible with Laravel's AES decryption
 */
export function encryptData(data: unknown): string {
    const jsonString = JSON.stringify(data);
    const encrypted = CryptoJS.AES.encrypt(jsonString, SECRET_KEY).toString();
    return encrypted;
}

/**
 * Decrypt data encrypted with AES
 */
export function decryptData<T = unknown>(encryptedData: string): T | null {
    try {
        const bytes = CryptoJS.AES.decrypt(encryptedData, SECRET_KEY);
        const decryptedString = bytes.toString(CryptoJS.enc.Utf8);
        return JSON.parse(decryptedString) as T;
    } catch (error) {
        console.error('Decryption failed:', error);
        return null;
    }
}

/**
 * Encrypt filter parameters before sending to backend
 */
export function encryptFilters(filters: Record<string, unknown>): string {
    // Remove empty values
    const cleanFilters = Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) =>
                value !== null && value !== '' && value !== undefined,
        ),
    );

    return encryptData(cleanFilters);
}

/**
 * Decrypt filter parameters received from backend
 */
export function decryptFilters(
    encryptedFilters: string,
): Record<string, unknown> {
    return decryptData<Record<string, unknown>>(encryptedFilters) || {};
}
