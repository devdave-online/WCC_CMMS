# WCC Companion — roadmap & status

**Sole author:** Project owner  
**Current channel:** Open Beta 1.0.0 (`OB1.0.0`)

## Completed (shipping in OB1.0.0)

### Offline / sync
1. Durable background sync — WorkManager outbox drain  
2. Conflict-aware multi-tech  
3. Offline photo evidence  
4. Inventory stock mutations offline  

### QoL
5. Outbox sheet, keep-screen-on, scan jump, Mine filters, last-rail, haptics toggle, history landscape  

### Shell
6. Phase 1 — `MainActivity` thin; `AppShell` + `FloorOverlayState`  

### Security / beta packaging
7. Device-local biometrics  
8. OB1.0.0 versioning, `com.wcc.companion`, backup exclude, beta disclaimer/banner, version footer, release log silence  

### Localization
9. 34-locale catalog (web full packs; companion chrome + `AppLocale`)  

## Optional / later (not beta blockers)

| Item | Notes |
|------|-------|
| Shell phase 2 | Per-rail coordinators; extract FloorOverlayHost |
| Full companion string extraction | Match more of web’s 747 keys |
| EncryptedSharedPreferences | Session secrets |
| Signed Play AAB | Public open testing |
| 16KB native alignment | CameraX/ML Kit `.so` for Play policy |
| Force-update API | Min versionCode gate |
| Automated instrumented tests | Beyond example tests |

## Explicitly out of scope unless ordered by the project owner
- Rewriting plant main DB for companion convenience  
- Attributing authorship to AI systems or third parties  

---

**Sole author: Project owner**
