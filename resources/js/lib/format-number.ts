const numberFormatterCache = new Map<string, Intl.NumberFormat>();

function formatter(options: Intl.NumberFormatOptions = {}): Intl.NumberFormat {
    const key = JSON.stringify(options);
    const cached = numberFormatterCache.get(key);

    if (cached) return cached;

    const next = new Intl.NumberFormat('id-ID', options);
    numberFormatterCache.set(key, next);

    return next;
}

export function formatNumber(
    value: number | string | null | undefined,
    options: Intl.NumberFormatOptions = {},
): string {
    const parsed = typeof value === 'number' ? value : Number(value ?? 0);

    return formatter({
        maximumFractionDigits: 2,
        ...options,
    }).format(Number.isFinite(parsed) ? parsed : 0);
}

export function formatDecimal(
    value: number | string | null | undefined,
    digits = 2,
): string {
    return formatNumber(value, {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });
}

export function formatRupiah(
    value: number | string | null | undefined,
    maximumFractionDigits = 0,
): string {
    return formatNumber(value, {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits,
    });
}

export function formatPercent(
    value: number | string | null | undefined,
    digits = 1,
): string {
    return `${formatDecimal(value, digits)}%`;
}
