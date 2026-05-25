# QR Code Design Expansion Plan (QRCode Monkey-style)

## Goal
Add Body Shape, Eye Frame Shape, and Eye Ball Shape options similar to [QRCode Monkey](https://www.qrcode-monkey.com/).

---

## Phase 1: Database & Model

### Migration
- Add `eye_frame_style` (string, default 'square') to `qr_designs`
- Add `eye_ball_style` (string, default 'square') to `qr_designs`
- Rename/clarify: `dot_style` → body shape, keep `eye_style` for backward compat or merge into new columns

### Design Options (curated subset, scannable)

| Category | Options | Implementation |
|----------|---------|----------------|
| **Body** | square, dots, diamond, rounded_square | chillerlan + custom |
| **Eye Frame** | square, rounded, circle, dot | custom QRGdImage |
| **Eye Ball** | square, rounded, circle, dot | custom QRGdImage |

---

## Phase 2: Custom QRGdImage Output

### Create `App\QRCode\CustomQRGdImage`
- Extend `chillerlan\QRCode\Output\QRGdImagePNG`
- Override `module(int $x, int $y, int $M_TYPE)` to:
  - **Body modules** (M_DATA_DARK, M_DATA): draw based on `dot_style`
  - **Finder/Alignment** (M_FINDER_DARK, M_FINDER, M_FINDER_DOT, M_ALIGNMENT_DARK, M_ALIGNMENT): draw based on `eye_frame_style` and `eye_ball_style`
- Use GD: `imagefilledrectangle`, `imagefilledellipse`, `imagefilledpolygon`, `imagefilledarc` for rounded rects

### Shape Drawing Logic
- **square**: imagefilledrectangle
- **dots / circle**: imagefilledellipse (existing)
- **diamond**: imagefilledpolygon (4 points)
- **rounded_square**: 4× imagefilledarc + 2× imagefilledrectangle
- **rounded** (eye): same as rounded_square for outer frame
- **dot** (eye ball): small circle centered

---

## Phase 3: QrCodeGeneratorService

- Accept `eye_frame_style`, `eye_ball_style` from design
- Pass custom options to QROptions (via custom output class)
- Use `outputType` => CustomQRGdImage::class when custom shapes selected
- Fallback to standard QRGdImage when all shapes are 'square'

---

## Phase 4: UI (Livewire + Blade)

### Body Shape
- Grid of 4–6 options with small preview icons
- Labels: Square, Rounded, Diamond, Extra Rounded

### Eye Frame Shape
- Grid of 4 options
- Labels: Square, Rounded, Circle, Dotted

### Eye Ball Shape
- Grid of 4 options
- Labels: Square, Rounded, Circle, Dot

### State Sync
- Add new fields to URL state payload (base64 JSON)
- Add to `fillFromExisting` for edit mode

---

## Phase 5: API & Bulk

- Update `QrCodeApiController` to accept new design params
- Update `BulkGenerateQrCodesJob` defaults
- Update `QrDesign` model fillable

---

## Implementation Order

1. ✅ Migration
2. ✅ QrDesign model + fillable
3. ✅ CustomQRGdImage class
4. ✅ QrCodeGeneratorService integration
5. ✅ QrCodeBuilder Livewire (state, mount, save)
6. ✅ Blade UI
7. ✅ API
8. Lang files (en/es) - optional, using inline labels for now

## Completed (2026-02-21)

- Body Shape: square, dots, diamond, rounded_square (Squircle)
- Eye Frame Shape: square, rounded, circle, dot (Dotted)
- Eye Ball Shape: square, rounded, circle, dot
- Custom QRGdImage extends QRGdImagePNG with per-module-type shape drawing
- URL state sync includes new fields
