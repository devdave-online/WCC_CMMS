<?php
/**
 * Page / API capability registry for full audit.
 * Capabilities drive the simulation loop — only listed actions run.
 *
 * Common capability keys:
 *   load, marker, search, accordion, require_login
 *   api_get (path with {id} placeholders)
 *   create_ticket, mutate_tooling (only with --mutate)
 */
return [
    // ---- Auth / public ----
    [
        'id' => 'login',
        'section' => 'Auth',
        'path' => '/login.php',
        'require_login' => false,
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'docs_public',
        'section' => 'Auth',
        'path' => '/docs.php',
        'require_login' => false,
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'my_profile',
        'section' => 'Auth',
        'path' => '/my_profile.php',
        'require_login' => true,
        'capabilities' => ['load' => true, 'marker' => 'Profile'],
    ],
    [
        'id' => 'change_password',
        'section' => 'Auth',
        'path' => '/change_password.php',
        'require_login' => true,
        // Redirects away when must_change_password is not set — load only
        'capabilities' => ['load' => true],
    ],

    // ---- Operations ----
    [
        'id' => 'tickets_hub',
        'section' => 'Operations',
        'path' => '/index.php',
        'require_login' => true,
        'perm' => 'view_tickets',
        'capabilities' => [
            'load' => true,
            'marker' => 'Ticket',
            'create_ticket' => true,
        ],
    ],
    [
        'id' => 'active_tickets',
        'section' => 'Operations',
        'path' => '/_maint/active_tickets.php',
        'require_login' => true,
        'perm' => 'view_tickets',
        'capabilities' => [
            'load' => true,
            'search' => true,
            'accordion' => true,
            'marker' => 'parent-row',
        ],
    ],
    [
        'id' => 'work_orders',
        'section' => 'Operations',
        'path' => '/_maint/work_orders.php',
        'require_login' => true,
        'perm' => 'view_work_orders',
        'capabilities' => ['load' => true, 'search' => true, 'accordion' => true],
    ],
    [
        'id' => 'pm_calendar',
        'section' => 'Operations',
        'path' => '/_maint/pm_calendar.php',
        'require_login' => true,
        'perm' => 'view_work_orders',
        'capabilities' => ['load' => true, 'marker' => 'PM'],
    ],
    [
        'id' => 'takeover',
        'section' => 'Operations',
        'path' => '/_maint/takeover.php',
        'require_login' => true,
        'capabilities' => ['load' => true], // may redirect without ticket id
    ],
    [
        'id' => 'closeout',
        'section' => 'Operations',
        'path' => '/_maint/closeout.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'quick_resolve',
        'section' => 'Operations',
        'path' => '/_maint/quick_resolve.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'wo_takeover',
        'section' => 'Operations',
        'path' => '/_maint/wo_takeover.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],

    // ---- Assets ----
    [
        'id' => 'equipment_ledger',
        'section' => 'Assets',
        'path' => '/_eam/equipment.php',
        'require_login' => true,
        'perm' => 'view_equipment',
        'capabilities' => [
            'load' => true,
            'search' => true,
            'search_per_column' => true,
            'accordion' => true,
            'marker' => 'ledgerTable',
            'api_bom' => '/api/get_equipment_bom.php?equip_id={equip_id}',
            'api_docs' => '/api/get_equipment_docs.php?equip_id={equip_id}',
        ],
    ],
    [
        'id' => 'equipment_list_redirect',
        'section' => 'Assets',
        'path' => '/_eam/equipment_list.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'equipment_vault',
        'section' => 'Assets',
        'path' => '/_eam/setup_vault_equipment.php',
        'require_login' => true,
        'perm' => 'manage_equipment',
        'capabilities' => [
            'load' => true,
            'search' => true,
            'accordion' => true,
            'marker' => 'vaultTable',
        ],
    ],
    [
        'id' => 'toolings_ledger',
        'section' => 'Assets',
        'path' => '/_eam/toolings.php',
        'require_login' => true,
        'perm' => 'view_equipment',
        'capabilities' => [
            'load' => true,
            'search' => true,
            'search_per_column' => true,
            'accordion' => true,
            'marker' => 'ledgerTable',
            'api_bom' => '/api/get_tooling_bom.php?tooling_id={tooling_id}',
            'api_docs' => '/api/get_tooling_docs.php?tooling_id={tooling_id}',
        ],
    ],
    [
        'id' => 'toolings_vault',
        'section' => 'Assets',
        'path' => '/_eam/setup_vault_toolings.php',
        'require_login' => true,
        'perm' => 'manage_equipment',
        'capabilities' => [
            'load' => true,
            'search' => true,
            'accordion' => true,
            'marker' => 'vaultTable',
            'marker_docs_modal' => 'toolingDocsModal',
            'marker_bom_modal' => 'bomModal',
        ],
    ],
    [
        'id' => 'prod_lines_vault',
        'section' => 'Assets',
        'path' => '/_prod/setup_vault_lines.php',
        'require_login' => true,
        'perm' => 'manage_equipment',
        'capabilities' => ['load' => true],
    ],

    // ---- Inventory / Logistics ----
    [
        'id' => 'inventory',
        'section' => 'Logistics',
        'path' => '/_logi/inventory.php',
        'require_login' => true,
        'perm' => 'view_inventory',
        'capabilities' => ['load' => true, 'search' => true],
    ],
    [
        'id' => 'inventory_audit',
        'section' => 'Logistics',
        'path' => '/_logi/inventory_audit.php',
        'require_login' => true,
        'perm' => 'view_inventory',
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'vendors_list',
        'section' => 'Logistics',
        'path' => '/_logi/vendors_list.php',
        'require_login' => true,
        'perm' => 'view_vendors',
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'vendors',
        'section' => 'Logistics',
        'path' => '/_logi/vendors.php',
        'require_login' => true,
        'perm' => 'view_vendors',
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'vendors_vault',
        'section' => 'Logistics',
        'path' => '/_logi/setup_vault_vendors.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'purchase_requests',
        'section' => 'Logistics',
        'path' => '/_logi/purchase_requests.php',
        'require_login' => true,
        'perm' => 'view_purchase_requests',
        'capabilities' => ['load' => true, 'search' => true],
    ],
    [
        'id' => 'purchase_orders',
        'section' => 'Logistics',
        'path' => '/_logi/purchase_orders.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],

    // ---- Records ----
    [
        'id' => 'history',
        'section' => 'Records',
        'path' => '/_rpt/history.php',
        'require_login' => true,
        'perm' => 'view_history',
        'capabilities' => ['load' => true, 'search' => true, 'accordion' => true],
    ],
    [
        'id' => 'tracking_stepper',
        'section' => 'Records',
        'path' => '/_trck/tracking_stepper.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],

    // ---- People / Admin ----
    [
        'id' => 'users_list',
        'section' => 'Admin',
        'path' => '/_mgmt/users_list.php',
        'require_login' => true,
        'perm' => 'manage_users',
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'users',
        'section' => 'Admin',
        'path' => '/_mgmt/users.php',
        'require_login' => true,
        'perm' => 'manage_users',
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'admin_panel',
        'section' => 'Admin',
        'path' => '/_mgmt/admin_panel.php',
        'require_login' => true,
        'perm' => 'manage_settings',
        'capabilities' => ['load' => true, 'marker' => 'Admin'],
    ],
    [
        'id' => 'app_settings',
        'section' => 'Admin',
        'path' => '/_mgmt/app_settings.php',
        'require_login' => true,
        'perm' => 'manage_settings',
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'departments_vault',
        'section' => 'Admin',
        'path' => '/_mgmt/setup_vault_departments.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'admin_backup',
        'section' => 'Admin',
        'path' => '/_mgmt/admin_backup.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],

    // ---- Insights ----
    [
        'id' => 'statistics',
        'section' => 'Insights',
        'path' => '/_rpt/statistics.php',
        'require_login' => true,
        'perm' => 'view_statistics',
        'capabilities' => ['load' => true],
    ],
    [
        'id' => 'analytics_vault',
        'section' => 'Insights',
        'path' => '/_rpt/setup_vault_analytics.php',
        'require_login' => true,
        'capabilities' => ['load' => true],
    ],

    // ---- JSON APIs (authenticated session) ----
    [
        'id' => 'api_get_equipment',
        'section' => 'API',
        'path' => '/api/get_equipment.php',
        'require_login' => true,
        'capabilities' => ['api_json' => true],
    ],
    [
        'id' => 'api_get_equipment_bom',
        'section' => 'API',
        'path' => '/api/get_equipment_bom.php?equip_id={equip_id}',
        'require_login' => true,
        'capabilities' => ['api_json' => true, 'expect_success' => true],
    ],
    [
        'id' => 'api_get_equipment_docs',
        'section' => 'API',
        'path' => '/api/get_equipment_docs.php?equip_id={equip_id}',
        'require_login' => true,
        'capabilities' => ['api_json' => true, 'expect_success' => true],
    ],
    [
        'id' => 'api_get_tooling_bom',
        'section' => 'API',
        'path' => '/api/get_tooling_bom.php?tooling_id={tooling_id}',
        'require_login' => true,
        'capabilities' => ['api_json' => true, 'expect_success' => true],
    ],
    [
        'id' => 'api_get_tooling_docs',
        'section' => 'API',
        'path' => '/api/get_tooling_docs.php?tooling_id={tooling_id}',
        'require_login' => true,
        'capabilities' => ['api_json' => true, 'expect_success' => true],
    ],
    [
        'id' => 'api_notifications',
        'section' => 'API',
        'path' => '/api/notifications.php',
        'require_login' => true,
        'capabilities' => ['api_json' => true],
    ],
    [
        'id' => 'api_get_team',
        'section' => 'API',
        'path' => '/api/get_team.php',
        'require_login' => true,
        'capabilities' => ['api_json' => true],
    ],
];
