# WCC Companion — Styling Design Standards

**Sole author:** Project owner  
**Status:** LOCKED baseline (user-approved design)  
**Captured:** 2026-07-26 · still authoritative for Open Beta 1.0.0  
**Rollback target:** Prefer this document + the code paths listed under *Source of truth* when UI drifts.

Agents **must** read this before changing rail cards, chips, search strip, orbiter, or detail modals. Do not “improve” spacing/sizes without re-checking against these rules.

---

## 1. Source of truth (code)

| Area | Path |
|------|------|
| Global tokens | `app/.../ui/theme/DesignTokens.kt` → `WccTokens` |
| Per-rail styles + hosts | `app/.../ui/rails/RailContainers.kt` |
| Carousel / safe band | `app/.../ui/components/MmmLayout.kt` |
| Search strip | `app/.../ui/components/RailFilterBar.kt` → `RailFilterStrip` |
| Action dock | `app/.../ui/components/OrbiterMenu.kt` |
| Detail modals | `app/.../ui/components/WccComponents.kt` → `WccDetailModal`, `WccDetailInfoRow`, `WccDetailHeader` |
| Sticky CTAs | `WccStickyActionBar` in `WccComponents.kt` |

**Six rails (independent — never share one “god” chip size):**

1. Tickets → `TicketRailStyle` / `TicketRailContainer`  
2. Work Orders → `WorkOrderRailStyle` / `WorkOrderRailContainer`  
3. Equipment → `EquipmentRailStyle` / `EquipmentRailContainer`  
4. Toolings → `ToolingRailStyle` / `ToolingRailContainer`  
5. Inventory → `InventoryRailStyle` / `InventoryRailContainer`  
6. History → `HistoryRailStyle` / `HistoryRailContainer`  

Host wiring: `MainActivity.kt` → `MmmLayout` `itemContent` dispatches only to these containers.

---

## 2. Design language

- **Glassmorphism:** translucent `Surface` (~0.4–0.55 alpha on rail cards; ~0.97–0.98 on modals), thin `BorderStroke` with primary/status alpha — not solid Material Cards.
- **Industrial / glove-friendly:** primary actions ≥ 48 dp touch; orbiter uses **64 dp** buttons (`WccTokens.orbiterButton`).
- **8 dp grid:** prefer 8 / 12 / 16 / 24 dp. Chip gap **12 dp** (allowed band 10–15 dp).
- **Material 3 alignment (adapted):**
  - Min touch target **48 dp**
  - Search bar height **56 dp** (`WccTokens.searchBarHeight`)
  - Standard FAB is 56 dp; we use **64 dp** for floor actions

---

## 3. Portrait item stack (LOCKED — Tickets / WO / History)

These rails have **no** search strip. Layout must **not** stretch empty glass.

```
[ shrinked category icons ]          ← MmmLayout warp (outside item)
           ↓
[ glass CARD: icon + title + body + CHIPS inside ]
           ↓  chipGap (12 dp)
[ orbiter action buttons ]
           ↓
[ system nav — navigationBarsPadding on safe band ]
```

### Rules

1. **Chips live inside the card**, at the bottom of the card content — not floating orphans between card and orbiter.
2. **Internal card spacing:** `Arrangement.spacedBy(chipGap)` (12 dp) between icon, text blocks, and chip row.
3. **Between card and orbiter:** exactly `Spacer(chipGap)` = **12 dp**.
4. **Card is wrap-content** (`stretchCard = false`). Do not `weight(1f)` the card to fill the screen — that recreates the “huge empty glass” bug.
5. **Stack is vertically centered** in the safe band (`Arrangement.Center` on the column; MmmLayout uses `Alignment.Center` when `hasFilter == false`).
6. **Restore full chip content:** icons/emoji on chips (priority `!`, overdue `⚠️`, schedule icon, equip `⚙️`, status labels). Do not strip chips down to text-only unless the rail style explicitly says so.

### Tickets chips (inside card)

- Status chip (OPEN / PENDING / HOLD / …) with status color  
- Priority chip (NORMAL / HIGH / …) with `PriorityHigh` icon when high/critical  

### Work Order chips (inside card)

- Equipment chip (⚙️ + name)  
- Status chip (OVERDUE / SCHEDULED / …)  
- Date chip (schedule icon + date)  

### History

- Filter card: Events + Work Orders buttons **inside** the glass card  
- Event/WO cards: status chip (CLOSED / COMPLETED) **inside** card; then 12 dp; then orbiter  

---

## 4. Portrait item stack (Equipment / Toolings / Inventory)

These rails **have** a search strip.

```
[ shrinked category icons ]
           ↓
[ search strip — 56 dp height, top ~200 dp ]
           ↓  ~12 dp
[ glass CARD fills remaining band (stretch) ]
  content + chips centered inside card
           ↓  12 dp
[ orbiter ]
```

### Rules

1. `hasItemFilterBar` true for equipment / toolings / inventory only.
2. Search: `portraitSearchTop = 200.dp`, height = `WccTokens.searchBarHeight` (56 dp).
3. Item band top ≈ searchTop + 56 + 12.
4. Card **may** stretch (`stretchCard = true`, `weight(1f)`) so content fills under the search strip without a dead void under the card.
5. Chips stay **inside** the card; chip tokens come from that rail’s `*RailStyle` only.

---

## 5. Chip tokens (baseline values)

Tune only in `RailContainers.kt`. Do not hardcode one-off paddings in items unless matching these.

| Rail | chipPadH × V | chipFont | chipRadius | chipHeight | chipBorder | chipGap | stretchCard |
|------|--------------|----------|------------|------------|------------|---------|-------------|
| Tickets | 16 × 14 | 16 sp | 16 dp | **72 dp** | **3 dp** | **12 dp** | false |
| Work Orders | 16 × 12 | 14 sp | 16 dp | **56 dp** | **3 dp** | **12 dp** | false |
| Equipment | 14 × 8 | 12 sp | 12 dp | (content) | 2 dp | (internal) | true |
| Toolings | 14 × 8 | 12 sp | 12 dp | (content) | 2 dp | (internal) | true |
| Inventory | 16 × 9 | 13 sp | 12 dp | (content) | 2 dp | (internal) | true |
| History | 16 × 12 | 14 sp | 16 dp | **56 dp** | **3 dp** | **12 dp** | false |

Chip text: always `maxLines = 1` + `TextOverflow.Ellipsis` where length can grow.

**Chip borders fill the card width** (not hug-content pills floating in empty space):
- Single chip → `Modifier.fillMaxWidth()` (+ fixed `chipHeight` when set)
- Two chips in a row → each `Modifier.weight(1f).fillMaxHeight()` with `chipGap` between
- Border stroke **3 dp** on Tickets / WO / History (beefy chips); **2 dp** on equip rails
- Prefer showing the **initial info batch** in the chip (icons + label); only truncate with ellipsis when the full-width border still cannot fit
- Do **not** stretch empty glass on Tickets/WO/History to fake taller chips — use `chipHeight` instead

---

## 6. Global action / chrome tokens

| Token | Value | Use |
|-------|-------|-----|
| `WccTokens.touchMin` | 48 dp | Min interactive target |
| `WccTokens.orbiterButton` | 64 dp | Orbiter filled icon buttons |
| `WccTokens.orbiterIcon` | 28 dp | Icon inside orbiter |
| `WccTokens.searchBarHeight` | 56 dp | Rail filter strip |
| Orbiter row spacing | 12 dp | Between buttons |
| Card radius (portrait) | ~28 dp | Rail cards |
| Modal radius | `radiusXxl` (28 dp) | Detail sheets |

---

## 7. Detail / filter modals (sub-menus)

Use **`WccDetailModal`** for Equipment, Tooling, Part, History detail, and filter setup.

### Rules

1. **Content-sized sheet** — `wrapContentHeight` + `heightIn(max ≈ 86% screen)`.  
   **Never** force `fillMaxHeight(0.9f)` with sparse fields (causes empty black voids — rejected in QA).
2. **Full-bleed scrim** at ~**0.78** black alpha, covering status + nav; underlay orbiter must not look “live”.
3. Dismiss: **Back / tap scrim** — no header X on standard detail sheets.
4. Dense rows: **`WccDetailInfoRow`** with dividers; header via **`WccDetailHeader`**.
5. Sticky CTAs (takeover / closeout / WO complete): **`WccStickyActionBar`** with `navigationBarsPadding`; validation errors **above** the dock, never under it.
6. Forms: `imePadding()` + scroll so keyboard does not bury fields.

---

## 8. Performance (do not regress)

1. **Item virtualization:** compose only focus ±2 cards in `MmmLayout`.
2. **Category virtualization:** compose only focus ±2 category icons.
3. **One shared** `rememberInfiniteTransition` for category “breathe” — never one infinite transition per icon in a loop.
4. Prefer `key(index)` on virtualized item content.
5. Avoid N full-height expensive layouts when wrap-content is the rail standard (Tickets/WO/History).

---

## 9. Anti-patterns (explicitly rejected)

| Do not | Why |
|--------|-----|
| Stretch empty glass on Tickets/WO/History | Huge dead void under content |
| Float chips between card and orbiter as orphans | “Ugly”; loses glass grouping |
| Strip chip icons/emoji | Incomplete, low information density |
| One shared chip size for all rails | Breaks per-segment tuning |
| `fillMaxHeight(0.9)` detail modals with 5–7 fields | Empty modal body |
| Light scrim that leaves orbiter fully readable | Fake modal; mis-taps |
| Double `navigationBarsPadding` on orbiter + parent | Pushes content off-screen |
| Per-category infinite breathe animations | Perf jank |

---

## 10. Rollback checklist

If UI “feels wrong” again:

1. Open this file + `RailContainers.kt` — restore token values to the table in §5.  
2. Confirm Tickets/WO/History portrait stack matches §3 (chips **inside** card, 12 dp to orbiter).  
3. Confirm Equip/Toolings/Inv still use search band + stretch (§4).  
4. Confirm modals use `WccDetailModal` (§7).  
5. Confirm virtualization + single breathe (§8).  
6. Reference screenshots (optional): `qa_shots/drive/` from the approved session.

---

## 11. Change policy

- **Allowed without re-lock:** bug fixes that preserve these ratios; new fields on detail sheets; backend wiring.  
- **Requires user visual OK:** any change to chipGap, stretchCard, search top/height, orbiter size, or modal sizing model.  
- When locking a new baseline, update the **Captured** date at the top of this file and the token table in §5.

---

*End of locked styling baseline.*


---

**Sole author: Project owner**

