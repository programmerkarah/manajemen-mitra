import { decryptData } from '@/utils/encryption';
import { useMemo } from 'react';

/**
 * Hook to decrypt data with memoization
 * Decrypts data once and caches the result until encrypted data changes
 *
 * @param encryptedData - Encrypted string from backend
 * @returns Decrypted data array
 *
 * @example
 * const decryptedItems = useDecryptedData<ItemType>(data.encrypted);
 */
export function useDecryptedData<T = unknown>(
    encryptedData: string | null | undefined,
): T[] {
    return useMemo(() => {
        if (!encryptedData) return [];

        const decrypted = decryptData<T[] | T>(encryptedData);

        if (!decrypted) {
            console.error('Failed to decrypt data');
            return [];
        }

        return Array.isArray(decrypted) ? decrypted : [decrypted];
    }, [encryptedData]);
}

/**
 * Hook to decrypt single object with memoization
 *
 * @param encryptedData - Encrypted string from backend
 * @returns Decrypted single object or null
 *
 * @example
 * const decryptedItem = useDecryptedObject<ItemType>(data.encrypted);
 */
export function useDecryptedObject<T = unknown>(
    encryptedData: string | null | undefined,
): T | null {
    return useMemo(() => {
        if (!encryptedData) return null;

        const decrypted = decryptData<T>(encryptedData);

        if (!decrypted) {
            console.error('Failed to decrypt data');
            return null;
        }

        return decrypted;
    }, [encryptedData]);
}
