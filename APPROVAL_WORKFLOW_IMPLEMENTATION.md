# Approval Workflow Implementation

## Overview
Implementasi workflow approval untuk kegiatan dengan status: draft → diajukan → divalidasi → aktif

## Feature Locks
Rate Honor dan Alokasi Petugas hanya bisa dikelola setelah kegiatan status = 'divalidasi' atau 'aktif'

---

## Frontend Changes

### 1. Index.tsx - Approval Actions in Table
**File**: `resources/js/pages/Kegiatan/Index.tsx`

**Changes**:
- Added auth prop to interface (user id & role)
- Added state for reject dialog (selectedKegiatan, showRejectDialog, rejectNotes, processing)
- Added handler functions: handleSubmit, handleApprove, handleReject, handleRejectClick
- Added permission checks: canSubmit, canApprove, canReject
- Added inline action buttons in table:
  - **Ajukan** button (blue) - shown if canSubmit (draft + ketua tim/admin/operator)
  - **Setujui** button (green) - shown if canApprove (draft/diajukan + admin/approver)
  - **Tolak** button (red) - shown if canReject (draft/diajukan + admin/approver)
- Added reject dialog component with catatan input
- Updated status badges to include 'diajukan' (yellow) and 'aktif' (blue)

**Authorization Logic**:
```typescript
canSubmit = status === 'draft' && (admin || operator || ketuaTim)
canApprove = (admin || approver) && (status === 'draft' || status === 'diajukan')
canReject = canApprove
```

### 2. Show.tsx - Remove Approval & Lock Features
**File**: `resources/js/pages/Kegiatan/Show.tsx`

**Changes**:
- Removed all approval-related imports (Check, X, Send, Dialog, Label, Input, useState)
- Removed approval button components (Submit, Approve, Reject)
- Removed Reject Dialog component
- Removed handler functions (handleSubmit, handleApprove, handleReject)
- Removed canSubmit logic
- Added isApproved check: `status === 'divalidasi' || status === 'aktif'`
- **Locked features**:
  - "Kelola Rate Honor" button - disabled if !isApproved
  - "Kelola Alokasi" button - disabled if !isApproved (added Users icon)
  - Added tooltip: "Kegiatan harus divalidasi terlebih dahulu"

---

## Backend Changes

### 3. KegiatanController - Pass Auth & Lock Rate Honor
**File**: `app/Http/Controllers/KegiatanController.php`

**Changes in index()**:
```php
return Inertia::render('Kegiatan/Index', [
    'kegiatans' => $kegiatans,
    'filters' => $request->only(['search', 'status', 'tahun']),
    'auth' => [
        'user' => [
            'id' => auth()->id(),
            'role' => auth()->user()->active_role,
        ],
    ],
]);
```

**Changes in manageRateHonor()**:
```php
// Check if kegiatan is approved
if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
    return back()->with('error', 'Rate honor hanya bisa dikelola untuk kegiatan yang sudah divalidasi.');
}
```

**Changes in bulkUpdateRateHonor()**:
```php
// Check if kegiatan is approved
if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
    return back()->with('error', 'Rate honor hanya bisa dikelola untuk kegiatan yang sudah divalidasi.');
}
```

### 4. AlokasiPetugasController - Lock Alokasi Features
**File**: `app/Http/Controllers/AlokasiPetugasController.php`

**Changes in create()**:
```php
// Changed filter from whereNotIn(['selesai', 'dibatalkan'])
// to whereIn(['divalidasi', 'aktif'])
$kegiatans = Kegiatan::whereIn('status', ['divalidasi', 'aktif'])
    ->with([...])
    ->get();

// Also filter pre-selected kegiatan
$selectedKegiatan = Kegiatan::where('id', $decodedId)
    ->whereIn('status', ['divalidasi', 'aktif'])
    ->with([...])
    ->first();
```

**Changes in manage()**:
```php
// Check if kegiatan is approved
if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
    return back()->with('error', 'Alokasi petugas hanya bisa dikelola untuk kegiatan yang sudah divalidasi.');
}
```

**Changes in storeMultiple()**:
```php
// Check if kegiatan is approved
if (! in_array($kegiatan->status, ['divalidasi', 'aktif'])) {
    return back()->with('error', 'Alokasi petugas hanya bisa ditambahkan untuk kegiatan yang sudah divalidasi.');
}
```

---

## Workflow Summary

### Approval Flow
1. **Draft** → User creates kegiatan
2. **Diajukan** → Ketua Tim/Admin/Operator submits for approval (button in Index table)
3. **Divalidasi** → Admin/Approver approves (button in Index table)
4. **Aktif** → Kegiatan is active

### Feature Access
- **Rate Honor Management**: Only accessible when status = divalidasi/aktif
- **Alokasi Petugas**: Only accessible when status = divalidasi/aktif

### User Roles & Permissions
- **Submit** (draft → diajukan): Ketua Tim (own), Admin, Operator
- **Approve** (diajukan → divalidasi): Admin, Approver
- **Reject** (any → draft with notes): Admin, Approver
- **Rate Honor**: Admin, Operator (+ approved kegiatan)
- **Alokasi**: Ketua Tim (own), Admin, Operator (+ approved kegiatan)

### UI Elements
**Index Page**:
- Inline action buttons in table row
- Color-coded by action type (blue=submit, green=approve, red=reject)
- Dialog for rejection with required catatan field

**Show Page**:
- Disabled buttons with tooltip when kegiatan not approved
- Visual feedback (grayed out, cursor not-allowed)

### Error Messages
- "Rate honor hanya bisa dikelola untuk kegiatan yang sudah divalidasi."
- "Alokasi petugas hanya bisa dikelola untuk kegiatan yang sudah divalidasi."
- "Alokasi petugas hanya bisa ditambahkan untuk kegiatan yang sudah divalidasi."

---

## Files Modified

### Frontend (3 files)
1. `resources/js/pages/Kegiatan/Index.tsx` - Added approval buttons + dialog
2. `resources/js/pages/Kegiatan/Show.tsx` - Removed approval, locked features

### Backend (2 files)
1. `app/Http/Controllers/KegiatanController.php` - Auth data + rate honor lock
2. `app/Http/Controllers/AlokasiPetugasController.php` - Alokasi lock + filter

---

## Testing Checklist

### Approval Workflow
- [ ] Ketua Tim can submit own draft kegiatan from Index
- [ ] Admin can submit any draft kegiatan
- [ ] Operator can submit any draft kegiatan
- [ ] Approver can approve diajukan kegiatan from Index
- [ ] Admin can approve diajukan kegiatan from Index
- [ ] Approver can reject with catatan from Index
- [ ] Admin can reject with catatan from Index
- [ ] Rejected kegiatan returns to draft status with notes visible

### Feature Locks
- [ ] Rate Honor button disabled in Show for draft kegiatan
- [ ] Rate Honor button enabled for divalidasi kegiatan
- [ ] Rate Honor direct URL blocked with error message
- [ ] Alokasi button disabled in Show for draft kegiatan
- [ ] Alokasi button enabled for divalidasi kegiatan
- [ ] Alokasi direct URL blocked with error message
- [ ] Alokasi Create dropdown only shows divalidasi/aktif kegiatan

### UI/UX
- [ ] Approval buttons appear in correct rows (based on status + role)
- [ ] Reject dialog opens with empty catatan field
- [ ] Reject requires catatan (button disabled if empty)
- [ ] Processing state shows "Memproses..." during submission
- [ ] Success/error messages display correctly
- [ ] Status badges show correct colors (draft=gray, diajukan=yellow, divalidasi=green, aktif=blue)
- [ ] Locked buttons show tooltip on hover
- [ ] Page refreshes after approval actions

---

## Build Status
✅ Build successful (351.98 kB gzip: 114.62 kB)
✅ Code formatted with Laravel Pint
