<?php
/**
 * WCC CMMS — documentation chapter registry.
 *
 * Single source of truth for the manual: the sidebar, the scroll-spy targets and
 * the include order all derive from this array, so adding a chapter means adding
 * one entry plus one file in docs/chapters/. Nothing else needs touching.
 *
 * 'id'       anchor + scroll-spy key (must be unique, URL-safe)
 * 'file'     docs/chapters/<file>.php
 * 'sections' in-page subheadings; each must match an <h3 id="..."> in the chapter
 */

return [
    [
        'part' => 'Orientation',
        'chapters' => [
            ['id' => 'introduction', 'num' => '1', 'title' => 'What WCC Is', 'file' => '01-introduction',
             'sections' => [
                'why-wcc'        => 'Why this exists',
                'design-rules'   => 'The four design rules',
                'what-it-is-not' => 'What WCC deliberately is not',
             ]],
            ['id' => 'install', 'num' => '2', 'title' => 'Installation', 'file' => '02-install',
             'sections' => [
                'requirements'   => 'Requirements',
                'quick-install'  => 'Quick install',
                'manual-install' => 'Manual install',
                'first-login'    => 'First login',
             ]],
            ['id' => 'tour', 'num' => '3', 'title' => 'A Guided Tour', 'file' => '03-tour',
             'sections' => [
                'the-shell'      => 'The application shell',
                'first-ticket'   => 'Your first ticket, end to end',
                'demo-accounts'  => 'Demo accounts',
             ]],
        ],
    ],
    [
        'part' => 'Architecture',
        'chapters' => [
            ['id' => 'structure', 'num' => '4', 'title' => 'Code Structure', 'file' => '04-structure',
             'sections' => [
                'folder-map'     => 'Folder map',
                'module-domains' => 'Why underscore modules',
                'page-anatomy'   => 'Anatomy of a page',
             ]],
            ['id' => 'lifecycle', 'num' => '5', 'title' => 'Request Lifecycle', 'file' => '05-lifecycle',
             'sections' => [
                'request-path'   => 'From URL to HTML',
                'shared-infra'   => 'Shared infrastructure',
                'error-handling' => 'Errors and logging',
             ]],
            ['id' => 'design-system', 'num' => '6', 'title' => 'Design System', 'file' => '06-design-system',
             'sections' => [
                'tokens'         => 'Tokens',
                'components'     => 'Components',
                'theming'        => 'Light and dark',
                'wave'           => 'The animated background',
             ]],
        ],
    ],
    [
        'part' => 'Data',
        'chapters' => [
            ['id' => 'schema', 'num' => '7', 'title' => 'Database Schema', 'file' => '07-schema',
             'sections' => [
                'schema-overview' => 'The shape of the data',
                'schema-tickets'  => 'Tickets and actions',
                'schema-assets'   => 'Assets and plant',
                'schema-stock'    => 'Inventory and ledger',
                'schema-procure'  => 'Procurement',
                'schema-system'   => 'Users, RBAC and system',
             ]],
            ['id' => 'migrations', 'num' => '8', 'title' => 'Migrations', 'file' => '08-migrations',
             'sections' => [
                'how-migrations'  => 'How migrations run',
                'writing-one'     => 'Writing a migration',
                'schema-drift'    => 'Schema drift, honestly',
             ]],
        ],
    ],
    [
        'part' => 'Security',
        'chapters' => [
            ['id' => 'auth', 'num' => '9', 'title' => 'Authentication', 'file' => '09-auth',
             'sections' => [
                'login-flow'      => 'The login flow',
                'sessions'        => 'Session handling',
                'brute-force'     => 'Brute-force defence',
                'passwords'       => 'Password policy',
             ]],
            ['id' => 'rbac', 'num' => '10', 'title' => 'Roles & Permissions', 'file' => '10-rbac',
             'sections' => [
                'perm-model'      => 'The permission model',
                'perm-matrix'     => 'The full matrix',
                'custom-roles'    => 'Custom roles and overrides',
                'enforcement'     => 'Where it is enforced',
             ]],
            ['id' => 'hardening', 'num' => '11', 'title' => 'Hardening', 'file' => '11-hardening',
             'sections' => [
                'csrf'            => 'CSRF protection',
                'sql-injection'   => 'Query safety',
                'webroot'         => 'Webroot exposure',
                'uploads'         => 'Upload handling',
             ]],
        ],
    ],
    [
        'part' => 'Workflows',
        'chapters' => [
            ['id' => 'tickets', 'num' => '12', 'title' => 'Ticket Lifecycle', 'file' => '12-tickets',
             'sections' => [
                'ticket-states'   => 'The state machine',
                'registering'     => 'Registering an event',
                'takeover'        => 'Takeover and Evil Maid locking',
                'closeout'        => 'Closeout',
                'ticket-history'  => 'History and repeat detection',
             ]],
            ['id' => 'workorders', 'num' => '13', 'title' => 'Work Orders & PM', 'file' => '13-workorders',
             'sections' => [
                'wo-states'       => 'Work order states',
                'pm-schedules'    => 'Preventive schedules',
                'checklists'      => 'Checklists',
                'pm-calendar'     => 'The calendar',
             ]],
            ['id' => 'assets', 'num' => '14', 'title' => 'Assets & Labels', 'file' => '14-assets',
             'sections' => [
                'asset-register'  => 'The asset register',
                'hierarchy'       => 'Workshops, lines, stations',
                'tooling'         => 'Tooling registry and vault',
                'labels'          => 'QR and DataMatrix labels',
                'printing'        => 'Printing: Zebra and paper',
             ]],
            ['id' => 'inventory', 'num' => '15', 'title' => 'Inventory', 'file' => '15-inventory',
             'sections' => [
                'stock-status'    => 'The status column',
                'parts-master'    => 'The parts master',
                'ledger'          => 'The ledger is the truth',
                'consumption'     => 'How stock is consumed',
                'auto-reorder'    => 'Event-driven auto-reorder',
             ]],
            ['id' => 'procurement', 'num' => '16', 'title' => 'Procurement', 'file' => '16-procurement',
             'sections' => [
                'po-states'       => 'The nine stages',
                'approval'        => 'Approval routing',
                'fulfilment'      => 'Storekeeper fulfilment',
                'receipt'         => 'Goods receipt and budgets',
             ]],
            ['id' => 'notifications', 'num' => '17', 'title' => 'Notifications', 'file' => '17-notifications',
             'sections' => [
                'notif-model'     => 'How notifications work',
                'notif-triggers'  => 'Every trigger',
                'notif-expiry'    => 'Certification expiry',
             ]],
        ],
    ],
    [
        'part' => 'Features in Depth',
        'chapters' => [
            ['id' => 'tables', 'num' => '18', 'title' => 'Working with Tables', 'file' => '18-tables',
             'sections' => [
                'drag-filter'     => 'Drag-to-filter tokens',
                'accordions'      => 'Expandable rows',
                'sorting-search'  => 'Sorting and search',
                'table-exports'   => 'Exports',
             ]],
            ['id' => 'adminpanel', 'num' => '19', 'title' => 'The Admin Panel', 'file' => '19-adminpanel',
             'sections' => [
                'tile-board'      => 'The tile board',
                'tile-reorder'    => 'Rearranging your panel',
                'tile-inventory'  => 'What every tile does',
             ]],
            ['id' => 'skills', 'num' => '20', 'title' => 'Skills & Proficiencies', 'file' => '20-skills',
             'sections' => [
                'two-systems'     => 'Two separate systems',
                'proficiency-earn'=> 'How proficiencies are earned',
                'skill-config'    => 'The Skill Configurator',
                'manual-certs'    => 'Certifications and expiry',
             ]],
            ['id' => 'equipment-depth', 'num' => '21', 'title' => 'Equipment in Depth', 'file' => '21-equipment-depth',
             'sections' => [
                'eq-bom'          => 'Bill of materials',
                'eq-docs'         => 'Documents and manuals',
                'eq-uuid'         => 'UUID rules',
                'eq-labelsetup'   => 'Label & printer setup',
             ]],
            ['id' => 'configurators', 'num' => '22', 'title' => 'Configurators', 'file' => '22-configurators',
             'sections' => [
                'cfg-registration'=> 'Registration configurator',
                'cfg-roles'       => 'Role presets',
                'cfg-kpi'         => 'KPI targets',
                'cfg-calendar'    => 'Operational calendar',
                'cfg-procurement' => 'Procurement workflow',
                'cfg-other'       => 'Other configurators',
             ]],
            ['id' => 'selfservice', 'num' => '23', 'title' => 'Self-Service', 'file' => '23-selfservice',
             'sections' => [
                'my-stats'        => 'Your performance dashboard',
                'my-skills'       => 'Your skills',
                'my-prefs'        => 'Personal preferences',
                'my-language'     => 'Interface language',
             ]],
        ],
    ],
    [
        'part' => 'Analysis',
        'chapters' => [
            ['id' => 'kpis', 'num' => '24', 'title' => 'KPIs & Reporting', 'file' => '24-kpis',
             'sections' => [
                'kpi-definitions' => 'What each KPI means',
                'kpi-formulas'    => 'The actual formulas',
                'shift-model'     => 'Shift-adjusted downtime',
                'event-class'     => 'What counts as a failure',
                'kpi-targets'     => 'Targets: static and rolling',
                'exports'         => 'Exports and printing',
             ]],
        ],
    ],
    [
        'part' => 'Operations',
        'chapters' => [
            ['id' => 'dataadmin', 'num' => '25', 'title' => 'Data Administration', 'file' => '25-dataadmin',
             'sections' => [
                'backup'          => 'Backup',
                'restore'         => 'Restore',
                'flush'           => 'Flush',
                'safety'          => 'Safety rails',
             ]],
            ['id' => 'demodata', 'num' => '26', 'title' => 'Demo Data', 'file' => '26-demodata',
             'sections' => [
                'seeder'          => 'The seeder',
                'what-it-makes'   => 'What it creates',
                'reseeding'       => 'Re-seeding for a pitch',
             ]],
            ['id' => 'api', 'num' => '27', 'title' => 'REST API v1', 'file' => '27-api',
             'sections' => [
                'api-auth'        => 'Authentication',
                'api-resources'   => 'Resources',
                'api-examples'    => 'Worked examples',
             ]],
            ['id' => 'deployment', 'num' => '28', 'title' => 'Deployment', 'file' => '28-deployment',
             'sections' => [
                'local-deploy'    => 'Local deployment',
                'public-deploy'   => 'Public hosting',
                'backups-ops'     => 'Backups and cron',
             ]],
            ['id' => 'troubleshooting', 'num' => '29', 'title' => 'Troubleshooting', 'file' => '29-troubleshooting',
             'sections' => [
                'common-issues'   => 'Common issues',
                'where-logs'      => 'Where to look',
                'faq'             => 'FAQ',
             ]],
            ['id' => 'ai-handoff', 'num' => '30', 'title' => 'AI Agent Handoff', 'file' => '30-ai-handoff',
             'sections' => [
                'ai-bootstrap'    => 'The bootstrap file',
                'ai-context'      => 'The context folder',
                'ai-generator'    => 'Keeping context fresh',
                'ai-handoff'      => 'Handing over to a new agent',
             ]],
        ],
    ],
];
