# Frontend Encryption Update Guide

## Files to Update

Semua file `Index.tsx` berikut perlu diupdate untuk menggunakan encrypted data:

- ✅ resources/js/Pages/Alokasi/Index.tsx (DONE)
- ✅ resources/js/Pages/Kegiatan/Index.tsx (DONE)
- ⏳ resources/js/Pages/Petugas/Index.tsx
- ⏳ resources/js/Pages/Penandatangan/Index.tsx
- ⏳ resources/js/Pages/Dipa/Index.tsx
- ⏳ resources/js/Pages/DasarHukum/Index.tsx
- ⏳ resources/js/Pages/SkKpa/Index.tsx
- ⏳ resources/js/Pages/Spk/Index.tsx

## Update Pattern

### 1. Add Import
```typescript
import { useDecryptedData } from '@/hooks/useDecryptedData';
```

### 2. Update Interface
```typescript
// BEFORE
interface Props {
    items: {
        data: Item[];
        links: any[];
        meta: any;
    };
}

// AFTER
interface Props {
    items: {
        encrypted: string;
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        links: any[];
    };
}
```

### 3. Add Hook in Component
```typescript
export default function Index({ items, filters }: Props) {
    // Add this line after component start
    const decryptedItems = useDecryptedData<ItemType>(items.encrypted);
    
    // Rest of component...
}
```

### 4. Replace All Data References
```typescript
// BEFORE
{items.data.length === 0 ? (
    // ...
) : (
    items.data.map(item => (
        // ...
    ))
)}

// AFTER
{decryptedItems.length === 0 ? (
    // ...
) : (
    decryptedItems.map(item => (
        // ...
    ))
)}
```

### 5. Update Pagination Meta (if needed)
```typescript
// BEFORE
<div>Showing {items.data.length} of {items.total} results</div>

// AFTER
<div>Showing {decryptedItems.length} of {items.meta.total} results</div>
```

## Quick Find & Replace

For each file, search and replace:

1. Add import at top with other imports
2. Find interface definition and update structure
3. Add `useDecryptedData` hook after component declaration
4. Search: `items.data` → Replace: `decryptedItems`
5. Search: `items.total` → Replace: `items.meta.total`
6. Search: `items.current_page` → Replace: `items.meta.current_page`

## Variable Names by File

- Petugas: `petugas.data` → `decryptedPetugas`
- Penandatangan: `PenandatanganList.data` → `decryptedPenandatangan`
- Dipa: `dipaList.data` → `decryptedDipa`
- DasarHukum: `dasarHukum.data` → `decryptedDasarHukum`
- SkKpa: `kegiatan.data` → `decryptedKegiatan`
- Spk: `periodeList.data` → `decryptedPeriode`

## After Updates

Run build:
```bash
npm run build
```

Or dev mode:
```bash
npm run dev
```
