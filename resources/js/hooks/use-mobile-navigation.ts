import { useCallback } from 'react';

export function useMobileNavigation() {
    return useCallback(() => {
        // Keep as no-op to preserve existing call sites without mutating body styles.
    }, []);
}
