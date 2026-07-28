# WCC Companion App Overview

**Sole author:** Project owner  
**Status:** Open Beta 1.0.0 (`OB1.0.0`) · `com.wcc.companion` · versionName `1.0.0` / versionCode `10000`

The WCC (Workshop Control Center) Companion App is a native Android application built with Jetpack Compose.
It is the **floor-tech** interface for the PHP CMMS: fast data acquisition and registration with greasy-glove MMM swipe navigation.

For the full living status matrix, read `PROJECT_STATUS_OB1.0.0.md`.

## Core Purpose
Technicians use the app to:
- **Manage** live tickets (not create them): takeover / resume HOLD, hold, closeout, comments, photo evidence.
- **Start and complete work orders** (notes + parts consumption) via companion APIs, including offline queue.
- Inspect **equipment** (search/scan asset tags) and **inventory** (stock status, offline stock holds).
- Review **history** (closed events + completed WOs with Events | WOs filter).
- Use **My Shift** / **Search** / **Profile** (achievements, device settings: haptics, biometric lock, version).
- **Toolings** rail follows equipment-style registry (backend-dependent).

## Primary Interfaces
- **MMM carousel:** Tickets → Work Orders → Equipment → Toolings → Inventory → History.
- **Orbiter actions** on focused cards (Takeover, Close, Hold, Resume, Info, Scan, Open WO).
- **Glassmorphic overlays** for ticket detail, intervention forms, WO execution, equipment/part detail, outbox sheet, scan result.
- **My Shift / Search** panels off the category rail ends; profile from the top bar.
- **Landscape + portrait:** compact cards; history landscape height-capped.
- **Open Beta chrome:** first-run disclaimer + dismissible banner + profile version footer.

## Networking
- Hybrid: REST `X-API-Key` / Basic for `api/v1/resources/*`, plus `PHPSESSID` CookieJar for legacy `/api/*.php` and `/api/companion/*`.
- Silent boot `loginForm` restores the session cookie.
- **Plant link:** TCP reachability / cellular ⇒ offline (not mere INTERNET capability).
- HTTP logging: BASIC in debug, **NONE** in release (no credential body dumps).

## Offline / sync
- Room outbox: pending ticket/WO ops + pending media.
- WorkManager drain when plant reachable.
- Live badge: Live / Syncing / Offline / OfflineUnsynced / Conflict.
- Outbox sheet for transparency + retry.

## Security (device-local)
- Optional biometric / device-credential app lock (prefs only; **never** plant DB).
- Session prefs excluded from Android Auto Backup / device transfer.

## Localization
- **34 languages** catalog aligned with web `lang/*` (see `LANGUAGES.md`).
- Companion localizes floor chrome/login; many overlays still English (known beta limit).
- Web packs: full key parity (747 keys × 34).

## Device testing
See `DEVICE_TESTING.md`. Package after OB1.0.0:  
`com.wcc.companion/com.example.wcc_companion_app.MainActivity`

---

**Sole author: Project owner**
