# Agent Instructions

**Sole author of this project: Project owner.**  
AI assistants are tools only — never list them as authors or co-authors.

This document is binding for any coding assistant modifying the WCC Companion App.

## 0. Status before you edit
Read in order:
1. `AUTHORSHIP.md` (sole author Project owner)
2. `PROJECT_STATUS_OB1.0.0.md` (what is shipped)
3. This file
4. `STYLING_DESIGN_STANDARDS.md` before UI chrome changes

## 1. Plant backend
- Default: prefer **in-app** fixes.
- Do **not** rewrite plant main DB schema or production PHP unless the project owner explicitly orders it.
- `C:\xampp\htdocs\` is the CMMS; use it to understand endpoints; do not casually mutate production data.

## 2. Hybrid networking
- `v1/resources/` uses API key / Basic.
- Legacy PHP endpoints need `PHPSESSID` via OkHttp `CookieJar` + silent `/login.php`.
- Do **not** remove CookieJar session bridging.
- Plant offline ≠ “no internet”: respect `NetworkMonitor` plant probe / cellular offline.

## 3. Offline stack (do not regress)
- Pending ops + media in Room; drain via `SyncCoordinator` / WorkManager.
- Outbox sheet + Live badge states must stay honest.
- Stock offline holds until outbox drains.

## 4. UI/UX
- Glassmorphism; obey `STYLING_DESIGN_STANDARDS.md`.
- `MmmLayout` distance-based animation — no boolean mid-drag snaps.
- Job screens: keep-screen-on via `KeepScreenOn`.
- Keyboard-aware modals: `imePadding` + scroll.

## 5. Security / beta
- Biometrics: **device-local only** (`DeviceBiometricLock` + prefs). No plant sync.
- Open Beta identity: `OB1.0.0`, `com.wcc.companion`, version `1.0.0` / `10000`.
- Do not log passwords or HTTP bodies in release.

## 6. Localization
- 34 locales in `AppLocale` match web catalog.
- Prefer `stringResource` for chrome; regenerate packs with `tools/gen_locale_strings.py`.

## 7. Authorship
- Credit **only the project owner**.
- Never add co-author headers, multi-party copyrights, or AI-as-author lines.

## 8. Shell
- Entry: `MainActivity` → login / biometric gate / `AppShell`.
- Overlays: `FloorOverlayState` + host composition in `AppShell`.
- Shell phase 2 (rail coordinators) is optional, not a silent rewrite of working MMM physics.

---

**Sole author: Project owner**
