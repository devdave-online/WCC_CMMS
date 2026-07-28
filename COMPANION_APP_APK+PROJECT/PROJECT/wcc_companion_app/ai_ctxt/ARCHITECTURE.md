# Architecture

**Sole author:** Project owner  
**Status baseline:** Open Beta 1.0.0 — see `PROJECT_STATUS_OB1.0.0.md`

## 1. Clean Architecture + MVVM
- **UI (`ui/`):** Compose screens, ViewModels, shell.
- **Data (`data/`):** Repositories, Room, sync, remote, locale, security.
- **DI (`di/`):** Hilt modules (Network, Database).

## 2. Process shell (phase 1 — shipped)
```
MainActivity (FragmentActivity; biometric prompt host)
  WccAppNavigation
    login | biometric | main(AppShell)
AppShell
  MmmLayout + WccTopBar + OpenBeta chrome
  FloorOverlayState-driven overlays (tickets, WO, scan, outbox, filters, details)
```

## 3. Hybrid networking
Legacy PHP CMMS uses:
1. **API token / Basic** for `api/v1/resources/*`
2. **PHP session cookie** for legacy action endpoints

`NetworkModule` provides OkHttp with CookieJar, dynamic base URL, auth headers.  
Boot / login: `loginForm` captures `PHPSESSID`.  
Logging: BASIC (debug) / NONE (release).

## 4. Offline sync
- **Room** entities for live tickets/WOs + reference cache + `pending_ticket_op` + `pending_media`
- **SyncCoordinator:** pull + drain; owns `LiveBadgeState`
- **NetworkMonitor:** plant TCP / cellular offline reasons
- **WorkManager** `OutboxSyncWorker` for process-death survival

## 5. MmmLayout matrix
- `detectDragGestures` → distance / overscroll
- Focus index from scroll math (critically damped snap)
- Depth: CATEGORY / ITEM / MY_SHIFT / PROFILE / SEARCH
- Last rail id persisted in prefs

## 6. Security
- `DeviceBiometricLock`: process unlock flag + system prompt; prefs `biometric_lock`
- Never written to plant users table / API

## 7. Locale
- `AppLocale` enum (34) + `LocaleController` (AppCompat locales)
- Resources under `res/values*`

## 8. Open Beta packaging
- `applicationId` `com.wcc.companion`
- `BuildConfig.DISPLAY_VERSION` = `OB1.0.0`
- Backup rules exclude `wcc_prefs`

---

**Sole author: Project owner**
