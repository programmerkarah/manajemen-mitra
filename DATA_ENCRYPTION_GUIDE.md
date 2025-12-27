# Data Encryption Implementation Guide

## Overview
Implementasi enkripsi data untuk melindungi data sensitif saat transmisi dari backend ke frontend, dengan tetap memungkinkan filter/sort di frontend.

## Architecture

### Backend Encryption
Data dienkripsi sebelum dikirim menggunakan AES-256-CBC dengan format kompatibel CryptoJS:

```php
// Helper function di app/Helpers/helpers.php
encryptData($data) // Encrypt array/object ke string
decryptData($encrypted) // Decrypt string ke array/object

// Controller example
return Inertia::render('Page', [
    'alokasi' => [
        'encrypted' => encryptData($alokasiData),
        'meta' => [...], // Metadata tidak dienkripsi
    ]
]);
```

### Frontend Decryption
Data di-decrypt **SEKALI** saat component mount menggunakan `useMemo` untuk caching:

```typescript
// Import hook
import { useDecryptedData } from '@/hooks/useDecryptedData'

// Decrypt data dengan memoization
const decryptedAlokasi = useDecryptedData<AlokasiType>(alokasi.encrypted);

// Use untuk render, filter, sort
{decryptedAlokasi.map(item => (
    <div>{item.nama_kegiatan}</div>
))}
```

## Benefits

✅ **Security**: Data terenkripsi di network layer
✅ **Performance**: Decrypt hanya sekali dengan `useMemo` caching
✅ **Functionality**: Frontend tetap bisa filter/sort data yang sudah di-decrypt
✅ **Developer Experience**: Simple API dengan hook

## Flow Diagram

```
Backend Controller
    ↓
encryptData($data) → Encrypted String
    ↓
Network (HTTPS + Encryption) → Double protection
    ↓
Frontend Component receives { encrypted: "..." }
    ↓
useDecryptedData(encrypted) → useMemo caching
    ↓
Decrypted Array in Memory
    ↓
Filter/Sort Operations (in-memory)
    ↓
Render (display decrypted data)
```

## Usage Examples

### Backend - Encrypt Data
```php
// In Controller
$data = Model::query()->get()->map(function($item) {
    return [
        'id' => $item->id,
        'sensitive_field' => $item->sensitive_field,
        // ... more fields
    ];
});

return Inertia::render('Page', [
    'items' => [
        'encrypted' => encryptData($data),
        'meta' => [
            'total' => count($data),
            // Other non-sensitive metadata
        ]
    ]
]);
```

### Frontend - Decrypt & Use
```typescript
interface Props {
    items: {
        encrypted: string
        meta: {
            total: number
        }
    }
}

export default function Page({ items }: Props) {
    // Decrypt once with memoization
    const decryptedItems = useDecryptedData<ItemType>(items.encrypted);
    
    // State for filtering
    const [search, setSearch] = useState('');
    
    // Filter in-memory (works because data is decrypted)
    const filteredItems = decryptedItems.filter(item => 
        item.name.toLowerCase().includes(search.toLowerCase())
    );
    
    return (
        <div>
            <input value={search} onChange={e => setSearch(e.target.value)} />
            
            {filteredItems.map(item => (
                <div key={item.id}>
                    {/* Display decrypted data */}
                    {item.name}: {item.sensitive_field}
                </div>
            ))}
        </div>
    );
}
```

## Available Hooks

### `useDecryptedData<T>(encrypted: string): T[]`
Decrypt array data dengan memoization.

### `useDecryptedObject<T>(encrypted: string): T | null`
Decrypt single object dengan memoization.

## Security Notes

1. **HTTPS Required**: Always use HTTPS in production
2. **Key Management**: `FILTER_ENCRYPTION_KEY` di `.env` harus strong & unique
3. **Memory Safety**: Decrypted data di client memory - clear saat component unmount
4. **Double Encryption**: Network encryption (HTTPS) + Data encryption (AES)

## Performance Considerations

- ✅ Decrypt **only once** per data change (useMemo)
- ✅ No re-decrypt on re-renders
- ✅ Client-side filter/sort tanpa network request
- ⚠️ Initial decrypt ada overhead (acceptable untuk data <10MB)

## Migration Guide

Untuk mengupdate halaman existing:

1. Update Controller:
```php
'data' => [ 'encrypted' => encryptData($items) ]
```

2. Update Frontend:
```typescript
import { useDecryptedData } from '@/hooks/useDecryptedData'
const decrypted = useDecryptedData<Type>(data.encrypted)
// Replace all data.items with decrypted
```

## Implemented Pages

- ✅ /alokasi - AlokasiPetugasController
- ⏳ Add more as needed...
