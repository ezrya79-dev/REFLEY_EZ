---
name: scan-input-module
description: Add barcode/QR scanning input to a web app — camera scanning via html5-qrcode (QR + EAN-13/8, Code 128/39, UPC, ITF, Data Matrix), USB/Bluetooth scanner-gun support, manual entry fallback, debouncing, torch/camera switching, and a flow to bind unknown codes to records. Use this whenever the user mentions scanning, barcodes, QR codes, scanner guns ("douchette"), inventory counting by scan, or fast physical-item identification in a browser app.
---

# Scan input module

Three input paths converge on one handler: **camera** (html5-qrcode), **scanner gun** (a keyboard that types fast and ends with Enter), **manual entry**. Design the module around a single `resolveCode(code) → record | unknown` service so the paths stay interchangeable.

## Method

1. **Camera** — html5-qrcode with QR + retail barcode formats enabled; controls for torch and camera switching (rear default); HTTPS or `localhost` only (getUserMedia restriction — warn in the UI on plain HTTP, offer the other two paths).
2. **Scanner gun** — a global keystroke listener that detects gun-speed input (inter-key < ~50ms) terminated by Enter; works on every screen the flow needs, no focus management for the user.
3. **Debounce** ("anti-rebond") — after a successful read, ignore identical codes for ~1.5s; camera streams re-read the same label dozens of times per second. Add an optional "auto" mode that validates on first read, and haptic/sound feedback for headless confirmation.
4. **Code resolution** — a `codes` table binding external barcodes to records (a record can carry many supplier barcodes). Unknown code → offer a two-gesture "bind this code to…" picker instead of an error; that's how the mapping table populates itself during real work.
5. **Business guards server-side** — e.g. stock decrement clamped to available quantity with a database-level lock; the scanner UI is a convenience, the invariant lives in the service.
6. **Offline** — pair with `pwa-offline-shell`'s queue so scans survive dead network.

## Verification checklist

- [ ] Same physical scan can be completed via camera, gun, and typing
- [ ] Double-read within debounce window applies once
- [ ] Unknown code path binds and immediately applies the action
- [ ] Server rejects the invariant-breaking case even if the UI misbehaves

## Reference implementation

OrthoZ: `resources/js/camera.js`, `resources/js/scanner.js`, `app/Models/ProductBarcode.php`, Vitest suites in `tests/js/`.
