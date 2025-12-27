import CryptoJS from 'crypto-js';

// Secret key untuk enkripsi - harus sama dengan FILTER_ENCRYPTION_KEY di .env
const SECRET_KEY = import.meta.env.VITE_FILTER_ENCRYPTION_KEY || 'manajemen-mitra-filter-key-2025';

/**
 * Encrypt data using AES encryption
 * Compatible with Laravel's AES decryption
 */
export function encryptData(data: any): string {
    const jsonString = JSON.stringify(data);
    const encrypted = CryptoJS.AES.encrypt(jsonString, SECRET_KEY).toString();
    return encrypted;
}

/**
 * Decrypt data encrypted with AES
 */
export function decryptData(encryptedData: string): any {
    try {
        const bytes = CryptoJS.AES.decrypt(encryptedData, SECRET_KEY);
        const decryptedString = bytes.toString(CryptoJS.enc.Utf8);
        return JSON.parse(decryptedString);
    } catch (error) {
        console.error('Decryption failed:', error);
        return null;
    }
}

/**
 * Encrypt filter parameters before sending to backend
 */
export function encryptFilters(filters: Record<string, any>): string {
    // Remove empty values
    const cleanFilters = Object.fromEntries(
        Object.entries(filters).filter(([_, value]) => 
            value !== null && value !== '' && value !== undefined
        )
    );

    return encryptData(cleanFilters);
}

/**
 * Decrypt filter parameters received from backend
 */
export function decryptFilters(encryptedFilters: string): Record<string, any> {
    return decryptData(encryptedFilters) || {};
}


