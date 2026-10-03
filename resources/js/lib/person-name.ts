export function formatPersonName(value: string | null | undefined): string {
    const normalized = String(value ?? '')
        .trim()
        .replace(/\s+/g, ' ');

    if (!normalized) return '';

    return normalized
        .toLocaleLowerCase('id-ID')
        .replace(/(^|[\s.'’-])([\p{L}])/gu, (_, prefix: string, letter: string) =>
            prefix + letter.toLocaleUpperCase('id-ID'),
        );
}

const PERSON_NAME_KEYS = new Set([
    'petugas_nama',
    'nama_petugas',
    'user_name',
    'nama_user',
    'created_by_name',
    'updated_by_name',
    'reviewed_by_name',
    'submitted_by_name',
]);

const PERSON_OBJECT_KEYS = new Set([
    'petugas',
    'user',
    'submitted_by',
    'reviewed_by',
    'created_by_user',
    'updated_by_user',
]);

export function normalizePersonNames<T>(value: T, parentKey?: string): T {
    if (Array.isArray(value)) {
        return value.map((item) => normalizePersonNames(item, parentKey)) as T;
    }

    if (!value || typeof value !== 'object') {
        return value;
    }

    const record = value as Record<string, unknown>;
    const normalized: Record<string, unknown> = {};

    for (const [key, child] of Object.entries(record)) {
        const isDirectNameKey = PERSON_NAME_KEYS.has(key);
        const isNestedPersonName =
            PERSON_OBJECT_KEYS.has(parentKey ?? '') &&
            (key === 'name' || key === 'nama');

        normalized[key] =
            typeof child === 'string' && (isDirectNameKey || isNestedPersonName)
                ? formatPersonName(child)
                : normalizePersonNames(child, key);
    }

    return normalized as T;
}
