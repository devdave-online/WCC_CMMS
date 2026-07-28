# _eam — Enterprise Asset Management

**Purpose:** Asset registry, equipment hierarchy, tooling, and EAM functions.

## Pages

| Page | Permission | Role |
|------|------------|------|
| `equipment.php` / `equipment_list.php` | `view_equipment` | Equipment ledger (read) |
| `setup_vault_equipment.php` | `manage_equipment` | Equipment admin vault |
| `equipment_labels.php` + `label_lib.php` | `manage_equipment` | Label print / ZPL |
| `toolings.php` / `toolings_list.php` | `view_equipment` | Tooling registry (read) |
| `setup_vault_toolings.php` | `manage_equipment` | Tooling admin vault (CRUD, allocation) |

## Tooling

- Table: `toolings` (migration `0017_create_toolings.sql`)
- Optional allocation to equipment via `linked_equip_id`
- Companion: `GET /api/companion/toolings.php` (+ scan_lookup tooling branch)
- Status: Available · In Use · Maintenance · Calibration Due · Retired
- Soft-delete: `deleted_at` (ledger hides retired; vault can restore)

## Notes

- Paths use root-absolute URLs (`/_eam/...`).
- Auth: `auth.php` + `require_perm(...)`.
