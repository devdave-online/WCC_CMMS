# Developer Conventions

**Sole author:** Project owner  

## Code Style
- **Declarative Compose:** All UI components must be declarative Jetpack Compose functions. Do NOT use standard Android XML or legacy View groups.
- **Glassmorphism:** Do not use `Card` with standard elevations or default `Surface` colors for primary elements. Use custom translucent surfaces, `BorderStroke` with alpha, and `Modifier.graphicsLayer` for blur.

## Deprecations & Limitations
- **`aspectRatio(16f/9f)` in carousels:** DO NOT USE. The `HorizontalPager` limits vertical height in landscape mode, which forces `aspectRatio` to squeeze the width violently. Instead, use a fixed width or dynamically calculated `itemSpacing` based on the screen's configuration width.
- **`weight(1f)` inside Pagers:** Extreme caution. Combining `weight(1f)` inside a parent that intrinsically measures its children (like a Pager or Scrollable Row) often leads to 0-width UI elements collapsing.

## Network Conventions
- Use `DynamicBaseUrl-Skip: true` header for any endpoints that bypass the standard `/api/v1/resources/` base path (like `/login.php`).
- Retrofit endpoints should return `Response<T>`. Always check `response.isSuccessful` before parsing bodies.

## Modals
- Prefer **`WccDetailModal`** for content-sized detail/filter sheets (see `STYLING_DESIGN_STANDARDS.md` §7). Do **not** force sparse forms into `fillMaxHeight(0.9f)`.
- Heavy form dialogs (takeover / closeout / WO exec) may use a tall sheet; still require:
  - dark full-bleed scrim (~0.78 alpha)
  - `imePadding()` + scroll for fields
  - sticky CTAs via `WccStickyActionBar` with errors **above** the dock
- Dismiss standard details via Back / scrim (no header X).

## Rail styling
- Six independent rails — tokens only in `ui/rails/RailContainers.kt`.
- Tickets / WO / History: chips **inside** the card; **12 dp** between card and orbiter; wrap-content card (no empty glass stretch).
- Full rules: `ai_ctxt/STYLING_DESIGN_STANDARDS.md`.

---

**Sole author: Project owner**
