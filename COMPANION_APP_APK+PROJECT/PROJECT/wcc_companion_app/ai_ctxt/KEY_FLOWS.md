# Key Flows

**Sole author:** Project owner  
**Status:** Open Beta 1.0.0

## 1. Authentication lifecycle
1. LoginScreen: server URL + username + password.
2. Normalize URL to `…/api/v1/`.
3. `loginForm` + `getCurrentUser` / me.
4. CookieJar stores `PHPSESSID`; credentials + URL in `AuthRepository`.
5. Cold start with session: optional **biometric gate** if `biometric_lock` enabled.
6. Password login marks process unlocked for this session.

## 2. Open Beta first run
1. After entering AppShell, if disclaimer not accepted → `OpenBetaDisclaimerDialog`.
2. Optional `OpenBetaBanner` until dismissed.
3. Profile shows version footer (`OB1.0.0` / 1.0.0 / 10000).

## 3. Ticket takeover / hold / closeout
1. Focus ticket → orbiter actions by status.
2. Forms enqueue or post via cycle repositories.
3. Offline → `pending_ticket_op`; Live badge / outbox reflect queue.
4. Conflict → CONFLICT + badge; keep/discard paths on entity.

## 4. Work order execution
1. Focus WO → execution overlay.
2. Start / complete via companion WO API; offline-capable through outbox.
3. Parts consumption may apply local stock holds (`StockMutationHelper`).

## 5. Photo evidence
1. Capture/pick → `EvidenceRepository` → Room `pending_media` + file.
2. Drain multipart when plant Live.
3. Strip UI on ticket/WO screens.

## 6. Scan jump
1. ScannerScreen (CameraX + ML Kit QR/DataMatrix).
2. `scanLookup` or local equip fallback.
3. ScanResultModal: auto-open single actionable hit; primary CTA otherwise.
4. Focus category via `MmmNavController.focusCategory`.

## 7. Mine filters
- Tickets/WO item rails: All · N / Mine · N (PIC / assigned_to).

## 8. Sync / Live badge
- Tap Live with queue → OutboxSheet; clean Live → resync.
- Failed drain → SyncUiEvent snackbar + Retry.

## 9. Carousel navigation
- Portrait: horizontal categories; depth up/down; BACK out of depth.
- Landscape: vertical categories; enter/back on depth axis.
- Dead-tap focused category → long haptic + swipe warn banner.

## 10. Locale switch
- Top bar language chip → scrollable 34-locale picker → `LocaleController.setLocale`.

---

**Sole author: Project owner**
