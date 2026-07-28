# `_ai_ctxt` — AI Agent Context Layer

This folder exists so any **AI AGENT** reaches the **same** understanding of WCC CMMS: product intent, release state, architecture, and rules.

## Bootstrap (every session)

1. Load project root **`ai_agent.ini`**.  
2. Read **`AGENT_INSTRUCTIONS.md`** then **`PRODUCT_STATUS.md`**.  
3. Optional: `php _ai_ctxt/print-init-summary.php`  
4. Continue with OVERVIEW → ARCHITECTURE → DATA_MODEL → KEY_FLOWS → CONVENTIONS → REST_API.  
5. Machine index: **`context.json`** / **`manifest.json`**.  
6. Live counts: `GET /api/v1/ai-context?live=1` or `php generate-context.php --live`.

## Files

| File | Purpose |
|------|---------|
| `AGENT_INSTRUCTIONS.md` | Mandatory agent rules |
| `PRODUCT_STATUS.md` | **PM/PO status** — OB1.0.0, offline beta, gates, debt |
| `OVERVIEW.md` | What WCC is and who uses it |
| `ARCHITECTURE.md` | Folders, shared infra, hardening |
| `DATA_MODEL.md` | Tables and business rules |
| `KEY_FLOWS.md` | Ticket, PO, inventory, notifications |
| `CONVENTIONS.md` | PHP/UI standards |
| `REST_API.md` | REST v1 (incl. toolings) |
| `context.json` | Machine-readable snapshot |
| `manifest.json` | File index |
| `generate-context.php` | Refresh DATA_MODEL table list from schema (+ live) |
| `print-init-summary.php` | CLI briefing |

## Product facts agents must internalize

- Release **OB1.0.0** open beta  
- **Offline**, one install per site — **no hardlocks**  
- **24** RBAC permissions including **toolings**  
- **REST** `/api/v1/toolings` full; **companion** `/api/companion/*` separate  
- i18n: **34** locales; English authoritative  

## Maintenance rule

If you change product strategy, architecture, REST resources, or release version, **update this folder in the same PR/session** — especially `PRODUCT_STATUS.md`, `context.json`, and root `ai_agent.ini`.
