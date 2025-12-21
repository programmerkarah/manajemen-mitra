# Icon Conversion Documentation

## SVG to PNG Conversion

File `icon.svg` telah dikonversi ke format PNG dengan tetap mempertahankan warna fill dari SVG original.

### Warna yang Dipertahankan:
- **Secondary Path** (Orange/Yellow): `oklch(0.769 0.188 70.08)`
- **Primary Path** (Blue): `oklch(0.6 0.118 184.704)`

### Files Generated:

| Filename | Size | Use Case |
|----------|------|----------|
| `icon-16.png` | 16x16px | Browser favicon (small) |
| `icon-32.png` | 32x32px | Browser favicon (standard) |
| `icon-48.png` | 48x48px | Browser favicon (high DPI) |
| `icon-64.png` | 64x64px | Windows small icon |
| `icon-128.png` | 128x128px | iOS/Android icon |
| `icon-256.png` | 256x256px | Large icon/tile |
| `icon.png` | 512x512px | Default high resolution |

### Conversion Scripts:

**Single Size (512px):**
```bash
node convert-svg-to-png.mjs
```

**Multiple Sizes:**
```bash
node convert-svg-to-png-all.mjs
```

### Technology Used:
- **@resvg/resvg-js**: High-quality SVG to PNG conversion
- Preserves all colors and styling from original SVG
- Generates crisp, anti-aliased PNG output

### Location:
All PNG files saved to: `public/`

### Re-generate Icons:
Jika SVG diupdate, jalankan kembali:
```bash
npm run convert-icons  # or node convert-svg-to-png-all.mjs
```

---
**Generated**: 2025-12-21
