# Author signing analysis + anonymization

**Repomix file (originals not deleted):** `_repomix_WCC_OB1.0.0.txt`  
**Policy:**

1. Public product authorship is **David only**.  
2. Tooling/bootstrap docs refer only to **AI AGENT** — no vendor product names.

---

## Product author (UI / license)

| Location | Result |
|----------|--------|
| `_about_modal.php` | Sole author **David** |
| `LICENSE.txt` | Licensor: **David** |
| `lang/*` | `about.author_sole`; neutral labels |
| `migrations/0002_*.sql` | Author: **David** |

---

## AI AGENT docs (anonymized)

Vendor / product agent names removed from live bootstrap docs. Generic term only: **AI AGENT**.

Updated files include:

- `ai_agent.ini`
- `AGENTS.md`
- `_ai_ctxt/AGENT_INSTRUCTIONS.md`
- `_ai_ctxt/README.md`
- `_ai_ctxt/PRODUCT_STATUS.md`
- `docs/chapters/30-ai-handoff.php`

---

## Historical artifacts (not live product surface)

- `_repomix_WCC_OB1.0.0.txt` — snapshot taken before scrub; may still contain old strings  
- `backups/`, `archive/`, `_dev_artifacts/` — offline archives; not served as primary UI  

---

## Result

- **Author:** David  
- **Agents in docs:** AI AGENT only  
