# Root cleanup — 2026-07-28

Everything here was moved out of the webapp root because it is **not loaded or served by the live WCC CMMS runtime**. Nothing was deleted.

Restore any path with:

```powershell
Move-Item -LiteralPath "C:\xampp\htdocs\archive\root_cleanup_20260728\dirs\<name>" -Destination "C:\xampp\htdocs\<name>"
# or for files:
Move-Item -LiteralPath "C:\xampp\htdocs\archive\root_cleanup_20260728\root_files\<file>" -Destination "C:\xampp\htdocs\<file>"
```

## dirs/

| Item | Why archived |
|------|----------------|
| `_ai_ctxt/` | AI agent context docs / generators |
| `_dev_artifacts/` | Phase backups, QA dumps, feat backups |
| `_docs/` | Internal project-structure notes (not in-app docs) |
| `_cmms/`, `_erp/`, `_mes/`, `_qual/` | Empty module placeholders (README only) |
| `demo/` | Demo seed / ghost-event scripts |
| `tests/` | Audit suite, gates, factory-reset tooling |
| `backups/` | SQL dumps and code snapshot backups |
| `cron/` | Empty directory (cron entrypoints stay at root) |
| `.agents/` | Editor/agent tooling |

## root_files/

| Item | Why archived |
|------|----------------|
| `admin_out.html`, `admin_test.html` | Empty leftover HTML |
| `AGENTS.md`, `ai_agent.ini`, `ai_agent.local.ini.example` | AI agent config (not PHP runtime) |
| `CMMS_QA_AND_FUTURE_PLAN.md` | Planning doc |
| `rest_api_core.md` | Dev API notes (product docs live under `docs/`) |
| `TASK.md` | Dev task notes |
| `_AUTHOR_SIGNING_ANALYSIS.md` | Dev analysis |
| `_repomix_WCC_OB1.0.0.txt` | Large code dump for AI context |
| `schema.sql.backup_20260713_014706` | Old schema backup |
| `update_search_icon.ps1` | One-off PowerShell script |

## Left at root (webapp / install surface)

- **Entry / shell:** `index.php`, `login.php`, `register.php`, `change_password.php`, `my_profile.php`, `auth.php`, `nav.php`, `rbac.php`, `docs.php`, `_about_modal.php`, `_confirm_modal.php`
- **Cron entrypoints:** `cron_requisition.php`, `cron_skill_expiry.php`
- **Modules:** `_maint/`, `_eam/`, `_logi/`, `_mgmt/`, `_rpt/`, `_trck/`, `_prod/`, `_doc/`
- **Runtime assets:** `api/`, `css/`, `js/`, `img/`, `inc/`, `lang/`, `uploads/`, `timer.js`
- **Product docs:** `docs/` (served via `docs.php`)
- **Install / ops:** `schema.sql`, `migrations/`, `LICENSE.txt`, `NOTICE`, `README.md`, `version.json`, `.htaccess`
- **Prior archive:** `archive/` (this folder + older dead_code / data_dumps)
- **Editor:** `.vscode/` (left in place)
