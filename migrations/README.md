# WCC CMMS — Migrations

**Goal (Phase 5):** Replace ad-hoc `ALTER TABLE` at runtime and one-off scripts with a deliberate, versioned, reviewable migration system.

## Current State (as of 2026-07-12)
- Schema lives primarily in `schema.sql` (snapshot).
- Runtime schema mutation:
  - `api/submit_closeout.php`: auto-`ALTER TABLE active_tickets ADD COLUMN closed_by ...` on first use (zero-downtime QoL, but hidden).
- One-off dev scripts:
  - `db_migrate_equipment.php` (raw PDO + large ALTER + UUID backfill + equipment_bom table).
- No central tracking of applied migrations.
- `inc/db.php` is the single connection source (good foundation).

## Approach (Simple & Reversible)
1. Numbered files: `migrations/0001_*.sql`, `0002_*.sql` ...
   - Pure SQL (idempotent where possible with `IF NOT EXISTS`, `ADD COLUMN IF NOT EXISTS` patterns or guarded).
2. Optional lightweight runner (PHP) later: `migrations/migrate.php` that:
   - Tracks a `schema_migrations` table (id, filename, applied_at).
   - Applies pending in order.
   - Dry-run mode.
3. Every migration must be:
   - Small.
   - Documented (header comment).
   - Tested against a backup or the dev DB.
4. `schema.sql` becomes "baseline" + "current recommended after applying all migrations".

## Usage (once runner exists)
```bash
php migrations/migrate.php          # apply pending
php migrations/migrate.php --dry    # preview
```

## First Migrations (to be created / reviewed)
- 0001_*: baseline notes or initial critical columns if schema.sql is used for fresh installs.
- 0002_add_closed_by_to_active_tickets.sql : formalize the column that submit_closeout relies on.
- Later: indexes, new tables for audit_log, soft-delete flags, parts_ledger, etc.

## Safety Rules
- Never drop columns/tables in a migration without a prior soft-delete or dual-write period.
- All migrations must be forward-only in the numbered sequence.
- Update this README + the main PLAN.md when adding a migration.
- For production: always backup DB before running.

## Related
- See `CMMS_QA_AND_FUTURE_PLAN.md` → Phase 5 for full list.
- `schema.sql` for reference structure.
- `inc/db.php` for the canonical connection.

This folder and system were introduced as the first concrete step of Phase 5 after the UI icon/emoji unification fixes.
