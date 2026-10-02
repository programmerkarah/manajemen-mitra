import { useCallback, useSyncExternalStore } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

const listeners = new Set<() => void>();
let initialized = false;
let currentAppearance: Appearance = 'system';

function getMediaQuery(): MediaQueryList | null {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
}

function prefersDark(): boolean {
    return getMediaQuery()?.matches ?? false;
}

function readStoredAppearance(): Appearance {
    if (typeof window === 'undefined') {
        return 'system';
    }

    const stored = window.localStorage.getItem('appearance');

    return stored === 'light' || stored === 'dark' || stored === 'system'
        ? stored
        : 'system';
}

function setCookie(name: string, value: string, days = 365): void {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
}

function applyTheme(appearance: Appearance): void {
    if (typeof document === 'undefined') {
        return;
    }

    const isDark =
        appearance === 'dark' || (appearance === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
}

function emitChange(): void {
    listeners.forEach((listener) => listener());
}

function subscribe(listener: () => void): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

function getSnapshot(): Appearance {
    return currentAppearance;
}

function getServerSnapshot(): Appearance {
    return 'system';
}

function handleSystemThemeChange(): void {
    if (currentAppearance === 'system') {
        applyTheme('system');
    }
}

export function initializeTheme(): void {
    if (initialized || typeof window === 'undefined') {
        return;
    }

    currentAppearance = readStoredAppearance();
    applyTheme(currentAppearance);
    getMediaQuery()?.addEventListener('change', handleSystemThemeChange);
    initialized = true;
}

export function useAppearance() {
    const appearance = useSyncExternalStore(
        subscribe,
        getSnapshot,
        getServerSnapshot,
    );

    const updateAppearance = useCallback((mode: Appearance) => {
        currentAppearance = mode;

        if (typeof window !== 'undefined') {
            window.localStorage.setItem('appearance', mode);
        }

        setCookie('appearance', mode);
        applyTheme(mode);
        emitChange();
    }, []);

    return { appearance, updateAppearance } as const;
}
