# UI Unification — Phase 0 Baseline (2026-07-17)

Captured via DOM probes (screenshot capture unavailable in preview pane), dark theme, 1280x720, logged in as admin.

| Metric | index.php | _maint/active_tickets.php | _rpt/statistics.php |
|---|---|---|---|
| `<label>` total / with `for=` | 0 / 0 | 2 / 0 | 2 / 0 |
| `<h1>` count | 1 | 0 | 0 |
| Elements with aria-label/role | 0 | 0 | 0 |
| Elements with inline `style=` | n/a | 221 | 460 |
| Elements with computed font < 12px | n/a | 19 | 131 |
| Console errors | 0 | 0 | 0 |

Notes:
- statistics.php double-loads global.css: `/css/global.css` AND `/css/global.css?v=<time()>`.
- body: Segoe UI, animated `gradientShift` background (infinite animation; caused screenshot stalls).
- Charts (chart.js) render OK; 2 canvases on statistics.
- Phase 0 changes: deleted `_rpt/statistics_backup.php`, `css/global - Copy.css`; archived `theme_css.php`, `add_css.php`; fixed dead `/css/style.css` link in `_maint/wo_takeover.php`.

Targets after unification: labels 100% associated, 1 h1/page, aria on modals/nav/icon buttons, inline styles near-0 (dynamic PHP values excepted), no computed font <12px, single CSS load with version cache-bust.
