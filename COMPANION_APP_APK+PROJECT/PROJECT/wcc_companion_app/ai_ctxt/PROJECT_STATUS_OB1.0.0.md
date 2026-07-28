# Project status — Open Beta 1.0.0 (OB1.0.0)

**Sole author:** Project owner  
**Last status sync:** 2026-07-28  
**Channel:** Open Beta  
**versionName:** `1.0.0` · **versionCode:** `10000` · **DISPLAY_VERSION:** `OB1.0.0`  
**applicationId:** `com.wcc.companion`  
**Kotlin namespace:** `com.example.wcc_companion_app` (unchanged package path; public id is `com.wcc.companion`)

---

## 1. What this app is

WCC Companion is the **floor technician** Android client for the Workshop Control Center PHP CMMS.

- **Not** a full web clone (no procurement admin, full KPIs board, etc.).
- **Is** a greasy-glove MMM carousel for tickets, work orders, equipment, toolings, inventory, history.
- Talks to plant server via **hybrid** networking: REST `api/v1` + legacy PHP session cookie.
- Supports **offline plant work**: local Room queue + WorkManager drain when plant is reachable.
- **Plant-reachable** ≠ “has cellular internet.” Cellular / plant-down ⇒ offline UX.

---

## 2. Release identity (current)

| Field | Value |
|-------|--------|
| Product label | WCC Companion Open Beta 1.0.0 |
| `BuildConfig.CHANNEL` | `OPEN_BETA` |
| `BuildConfig.DISPLAY_VERSION` | `OB1.0.0` |
| `versionName` | `1.0.0` |
| `versionCode` | `10000` |
| `applicationId` | `com.wcc.companion` |
| Min / target / compile SDK | 23 / 34 / 34 |
| Minify (R8) | Off for first beta |
| Cleartext HTTP | Allowed (plant LAN HTTP common) |
| Backup | `wcc_prefs.xml` **excluded** from cloud backup & device transfer |
| HTTP log level | DEBUG → BASIC; release → **NONE** |
| First-run | Open Beta disclaimer dialog |
| Ongoing | Dismissible Open Beta banner |
| Profile | App version footer (`AppVersionFooter`) |

---

## 3. Architecture map (as shipped)

```
MainActivity (FragmentActivity)
  ├─ LoginScreen / LoginViewModel
  ├─ BiometricGateScreen  (if biometric_lock && !processUnlocked)
  └─ AppShell
       ├─ OpenBetaDisclaimer / OpenBetaBanner
       ├─ MmmLayout (categories + items + panels)
       ├─ WccTopBar (Live, FH, help, profile, language×34, theme)
       └─ FloorOverlayState → detail/scan/filter/outbox overlays
```

### Data / sync

```
AuthRepository (SharedPreferences wcc_prefs)
Room WccDatabase
  tickets, work_orders, equipment/parts/toolings cache
  pending_ticket_op, pending_media
SyncCoordinator (badgeState, syncNow, drainOutbox, drainMedia)
NetworkMonitor (plant TCP / cellular → OfflineReason)
SyncScheduler + OutboxSyncWorker (WorkManager)
```

### Key packages under `com.example.wcc_companion_app`

| Area | Role |
|------|------|
| `ui/shell` | AppShell, FloorOverlayState |
| `ui/components` | MMM, top bar, haptics, filters, evidence, beta chrome |
| `ui/tickets` | Detail, takeover, hold, closeout |
| `ui/workorders` | Execution overlay |
| `ui/equipment` / `inventory` / `history` / `toolings` | Rails + details |
| `ui/scan` | CameraX + ML Kit + result modal |
| `ui/auth` | Login + biometric gate |
| `ui/profile` / `panels` | Profile, My Shift, Search |
| `data/sync` | Live badge, plant monitor, outbox |
| `data/repository` | Cycles, evidence, stock, auth, reference |
| `data/local` | Room entities/DAOs |
| `data/locale` | AppLocale × 34, LocaleController |
| `data/security` | DeviceBiometricLock (local only) |
| `di` | NetworkModule, DatabaseModule |

---

## 4. Feature checklist (implemented)

### 4.1 Core floor
- [x] Tickets rail: OPEN/PENDING/HOLD/ESCALATED; takeover / hold / closeout / comments  
- [x] Work orders: start / complete + parts  
- [x] Equipment search/filter/detail + scan entry  
- [x] Inventory stock chips + detail  
- [x] History Events | WOs filter + details  
- [x] Toolings rail (registry pattern; backend-dependent)  
- [x] My Shift panel (PIC / assigned)  
- [x] Search panel + scanner  

### 4.2 Offline / sync (features 1–4)
- [x] WorkManager outbox for ticket/WO ops  
- [x] Multi-tech conflict detection (CONFLICT status)  
- [x] Offline photo evidence queue + upload  
- [x] Offline stock mutation reservations until drain  
- [x] Plant-link NetworkMonitor (cellular / unreachable)  
- [x] Live badge states: Live / Syncing / Offline / OfflineUnsynced / Conflict  
- [x] Outbox transparency sheet + retry  

### 4.3 QoL
- [x] Keep-screen-on on ticket/WO/scanner overlays  
- [x] Scan auto-jump / primary CTA  
- [x] Mine filters (All / Mine) on tickets & WO  
- [x] Last-rail category memory  
- [x] Haptics master toggle + OEM-safe re-fire path  
- [x] History landscape clip mitigation  

### 4.4 Shell
- [x] Phase 1: MainActivity thinned; AppShell + FloorOverlayState  
- [ ] Phase 2 (optional): per-rail coordinators, FloorOverlayHost extract  

### 4.5 Security (device)
- [x] Biometric / device-credential app lock (**local prefs only**, no plant DB)  
- [x] Re-lock on process background when enabled  
- [x] “Use password instead” → logout to login  

### 4.6 Localization
- [x] Web SoT: 34 JSON packs × 747 keys (verified)  
- [x] Companion `AppLocale` × 34 matching codes  
- [x] `values-*` generated (`tools/gen_locale_strings.py`)  
- [x] Scrollable language picker  
- [ ] Full companion string extraction for all overlays (partial chrome only)  

### 4.7 Open Beta packaging
- [x] OB1.0.0 branding + version fields  
- [x] applicationId `com.wcc.companion`  
- [x] Backup exclude session prefs  
- [x] Release HTTP log silence  
- [x] Disclaimer + banner + version footer  
- [ ] Signed Play release keystore / AAB (sideload pilot OK without)  

---

## 5. Networking truth

| Path | Auth | Notes |
|------|------|--------|
| `api/v1/resources/*` | X-API-Key and/or Basic | Dynamic base URL from prefs |
| `/login.php` form | Session cookie | Silent on boot; CookieJar |
| `/api/submit_*.php` legacy | PHPSESSID | Takeover/hold/closeout |
| `/api/companion/*` | Session | WO actions, comments, etc. |
| Evidence multipart | Session | When plant Live |

**Offline rule:** `NetworkMonitor` plant probe — not `ConnectivityManager` internet alone.

---

## 6. QA evidence (device)

Primary device: **Samsung SM-A566B**.

| Report | Path |
|--------|------|
| Open Beta CQA/FQA | `qa_ob100/OPEN_BETA_1.0.0_REPORT.md` |
| Shell drive | `qa_shell_drive/QA_REPORT.md` |
| Locale launch | `qa_launch/QA_REPORT.md` |
| QoL Mine chips | `qa_qol_drive/QA_REPORT.md` |
| Full chip QA | `qa_full_drive/QA_REPORT.md` |

ADB package after OB1.0.0:  
`adb shell am start -n com.wcc.companion/com.example.wcc_companion_app.MainActivity`

---

## 7. Known limitations (honest)

1. Many overlay strings still hardcoded English.  
2. Session credentials in SharedPreferences (not encrypted); excluded from backup.  
3. Debug builds may show 16KB ELF system dialog (native CameraX/ML Kit).  
4. Companion ≠ web feature parity.  
5. Offline is plant-LAN aware by design.  
6. Public Play open testing still needs signed release AAB for store policy.

---

## 8. Explicit non-goals (unless the project owner orders otherwise)

- Rewriting plant main database schema for companion convenience  
- Claiming AI systems as co-authors  
- Full shell phase 2 as a beta blocker  
- Treating cellular as “online” for plant ops  

---

## 9. File map for agents

| Path | Purpose |
|------|---------|
| `ai_agent.ini` | Bootstrap |
| `ai_ctxt/*` | Living truth |
| `ai_ctxt/REPOMIX_SOURCE_WALL.txt` | Concatenated source wall (generated; do not delete originals) |
| `AUTHOR.md` | sole author Project owner |
| `app/src/main/java/...` | Application code |
| `tools/gen_locale_strings.py` | Locale string generation |

---

**Sole author: Project owner**
