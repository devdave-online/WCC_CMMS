# AI Agent Instructions

**ALL AI AGENTS: READ THIS FILE FIRST**

This project also provides `ai_agent.ini` in the root. Load it at the start of your session for structured initialization (works with any AI AGENT).

Helpful scripts:
- `php _ai_ctxt/print-init-summary.php` → Quick briefing for new sessions
- `php _ai_ctxt/generate-context.php [--live]` → Refresh context after changes

This project has a dedicated, high-quality AI context layer located in `_ai_ctxt/`.

## Required Reading Order
1. `_ai_ctxt/AGENT_INSTRUCTIONS.md` ← Start here
2. `_ai_ctxt/OVERVIEW.md`
3. `_ai_ctxt/ARCHITECTURE.md`
4. `_ai_ctxt/DATA_MODEL.md`
5. `_ai_ctxt/KEY_FLOWS.md`
6. `_ai_ctxt/CONVENTIONS.md`
7. `_ai_ctxt/REST_API.md`

## Dynamic Context (Recommended)
Use the REST endpoint for fresh information:
- `GET /api/v1/ai-context`
- `GET /api/v1/ai-context?section=DATA_MODEL`
- `GET /api/v1/ai-context?live=1` (safe live counts + samples)

## Keeping Context Up to Date
```bash
php _ai_ctxt/generate-context.php
php _ai_ctxt/generate-context.php --live
```

## Why This Exists
The goal is to ensure that every **AI AGENT** can fully understand:
- What this application does
- How it is structured
- What the data means
- How the key flows work
- What the current conventions are

This dramatically improves the quality of code changes and answers.

**Do not skip these files.** Context is king.
