<?php
/**
 * WCC CMMS — pitch-ready demo seeder.
 *
 * Wipes transactional + master data and rebuilds a believable mid-size plant with
 * ~9 months of operating history, so every screen in the app has something real to
 * show: KPIs with trend, tickets in every state, overdue work orders, POs sitting at
 * each stepper stage, parts under their reorder point, unread notifications.
 *
 * Everything is dated RELATIVE TO NOW, so the demo never looks stale — re-run it any
 * time (nightly cron, before a pitch) and "yesterday" is genuinely yesterday.
 *
 * CLI ONLY, on purpose. This is a destructive operation; exposing it over HTTP would
 * hand an anonymous visitor a database-wipe button.
 *
 *   php demo/demo_seed.php            # flush + seed
 *   php demo/demo_seed.php --seed=42  # different random draw, same shape
 *
 * Preserved (never touched): role_definitions, app_settings, schema_migrations.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden: demo_seed.php runs from the command line only.\n");
}

require_once __DIR__ . '/../inc/db.php';
$pdo = get_wcc_db_connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Deterministic by default: same command → same demo. Reproducible screenshots.
$opts = getopt('', ['seed::']);
mt_srand(isset($opts['seed']) ? (int)$opts['seed'] : 20260722);

const DEMO_PASSWORD = 'Demo2026!';

$t0 = microtime(true);
function say(string $m): void { echo $m . "\n"; }
function pick(array $a) { return $a[mt_rand(0, count($a) - 1)]; }

/** Random datetime between $daysAgoMax and $daysAgoMin days back, as 'Y-m-d H:i:s'. */
function backdate(int $daysAgoMax, int $daysAgoMin = 0, int $hFrom = 6, int $hTo = 21): string
{
    $day = mt_rand($daysAgoMin, $daysAgoMax);
    $ts  = strtotime("-{$day} days");
    return date('Y-m-d', $ts) . ' ' . sprintf('%02d:%02d:%02d', mt_rand($hFrom, $hTo), mt_rand(0, 59), mt_rand(0, 59));
}
function fwddate(int $daysFrom, int $daysTo): string { return date('Y-m-d', strtotime('+' . mt_rand($daysFrom, $daysTo) . ' days')); }

say("\n=== WCC DEMO SEEDER ===");
say('Target: ' . $pdo->query('SELECT DATABASE()')->fetchColumn());

// ---------------------------------------------------------------- flush
$wipe = [
    'ticket_attachments','ticket_comments','ticket_parts_consumed','ticket_actions','active_tickets',
    'po_documents','po_status_logs','po_items','purchase_orders',
    'department_budget_logs','inventory_ledger','notifications','notification_broadcast',
    'work_orders','pm_schedules','pm_checklist_items','pm_checklists',
    'equipment_bom','equipment_documents','equipment','production_lines','workshops',
    'inventory_parts','vendors_suppliers','departments','team_directory','eam_directory',
    'user_skills','audit_log','system_audit_logs','analytics_logs','scheduled_reports','rate_limit','users',
];
$live = $pdo->query("SELECT table_name FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$live = array_map('strtolower', $live);

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$cleared = 0;
foreach ($wipe as $t) {
    if (!in_array(strtolower($t), $live, true)) continue;
    $pdo->exec("TRUNCATE TABLE `$t`");
    $cleared++;
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
say("Flushed $cleared tables (role_definitions / app_settings / schema_migrations kept).");

// ---------------------------------------------------------------- users
// One account per role so the pitch can demo the RBAC story by logging in as each.
$hash  = password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT);
$users = [
    // username,       role, full name,          dept,            badge
    ['a.rivera',        4, 'Alex Rivera',        'Maintenance',   'IB-10001'],
    ['p.nair',          3, 'Priya Nair',         'Maintenance',   'IB-10002'],
    ['m.dubois',        3, 'Marc Dubois',        'Production',    'IB-10003'],
    ['j.okafor',        2, 'Jide Okafor',        'Maintenance',   'IB-10004'],
    ['s.lindqvist',     2, 'Sara Lindqvist',     'Maintenance',   'IB-10005'],
    ['t.yamamoto',      2, 'Taro Yamamoto',      'Maintenance',   'IB-10006'],
    ['k.novak',         2, 'Katerina Novak',     'Maintenance',   'IB-10007'],
    ['r.silva',         1, 'Rui Silva',          'Production',    'IB-10008'],
    ['e.moreau',        1, 'Elise Moreau',       'Production',    'IB-10009'],
    ['h.bakker',        6, 'Hendrik Bakker',     'Stores',        'IB-10010'],
    ['c.whitfield',     5, 'Claire Whitfield',   'Finance',       'IB-10011'],
];
$ins = $pdo->prepare(
    "INSERT INTO users (username, password_hash, role_level, full_name, email, phone, department,
                        status, badge_number, must_change_password, last_login, created_at)
     VALUES (?,?,?,?,?,?,?,'active',?,0,?,?)"
);
$UID = [];
foreach ($users as $i => $u) {
    $ins->execute([
        $u[0], $hash, $u[1], $u[2],
        strtolower(str_replace(' ', '.', $u[2])) . '@meridian-works.example',
        '+31 6 ' . mt_rand(10000000, 99999999),
        $u[3], $u[4],
        backdate(3, 0, 6, 20),
        backdate(400, 300),
    ]);
    $UID[$u[0]] = (int)$pdo->lastInsertId();
}
say('Users: ' . count($UID) . ' (one per role; password for all: ' . DEMO_PASSWORD . ')');

$ADMIN = $UID['a.rivera'];
$TECHS = [$UID['j.okafor'], $UID['s.lindqvist'], $UID['t.yamamoto'], $UID['k.novak']];
$TECHNAME = [
    $UID['j.okafor'] => 'Jide Okafor', $UID['s.lindqvist'] => 'Sara Lindqvist',
    $UID['t.yamamoto'] => 'Taro Yamamoto', $UID['k.novak'] => 'Katerina Novak',
];

// team_directory powers the "Person In Charge" picker on register.php and the
// "escalate to" picker on takeover.php, both of which call
// api/get_team.php?role=technical.
//
// role_type is a DOMAIN, not a job title: the app only ever queries the literal
// strings 'technical' and 'production'. Seeding job titles here ("Technician",
// "Supervisor", ...) matches nothing and silently empties the PIC dropdown, which
// blocks ticket registration entirely.
$td = $pdo->prepare("INSERT INTO team_directory (full_name, role_type, is_active, created_at) VALUES (?,?,1,?)");
foreach ($users as $u) {
    // Maintenance side (technician / supervisor / admin) can be put in charge of a
    // fault; operators, viewers and stores sit on the production side.
    $domain = in_array($u[1], [2, 3, 4], true) ? 'technical' : 'production';
    $td->execute([$u[2], $domain, backdate(400, 300)]);
}

// ---------------------------------------------------------------- manual skills
// user_skills holds certifications an admin grants by hand — the counterpart to the
// automatically-earned proficiencies. Left empty the Users Directory shows a bare
// "0" against everyone, which reads as a broken feature rather than an empty one.
// Expiry dates straddle today on purpose so renewal tracking has something to show.
$certs = [
    'a.rivera'    => [['LOTO Authorised Person', 400], ['Plant Safety Officer', 250], ['ISO 55001 Asset Mgmt', null]],
    'p.nair'      => [['LOTO Authorised Person', 300], ['Root Cause Analysis (8D)', null], ['Risk Assessment Lead', 180]],
    'm.dubois'    => [['Line Supervisor Cert', 220], ['Forklift Licence B', 45]],
    'j.okafor'    => [['LOTO Authorised Person', 365], ['KUKA Robot Programming', 120], ['Electrical LV Working', 30],
                      ['Welding: MIG/MAG', 260], ['Confined Space Entry', -20]],
    's.lindqvist' => [['LOTO Authorised Person', 340], ['Overhead Crane Operator', 90], ['Conveyor Systems L2', null],
                      ['Working at Height', 15]],
    't.yamamoto'  => [['LOTO Authorised Person', 410], ['CNC Maintenance L3', 200], ['Hydraulics & Pneumatics L2', 75],
                      ['Thermography Level 1', 150], ['Siemens S7 PLC', null]],
    'k.novak'     => [['LOTO Authorised Person', 320], ['Metrology & Calibration', 110], ['Vision Systems (Cognex)', null],
                      ['First Aid at Work', -5]],
    'r.silva'     => [['Machine Operator L2', null], ['Forklift Licence B', 200]],
    'e.moreau'    => [['Machine Operator L1', null]],
    'h.bakker'    => [['Warehouse Safety', 280], ['Dangerous Goods Handling', 60], ['Forklift Licence B', 150]],
    'c.whitfield' => [['Finance Systems Access', null]],
];
$us = $pdo->prepare("INSERT INTO user_skills (user_id, skill_name, expiry_date, created_at) VALUES (?,?,?,?)");
$nCerts = 0;
foreach ($certs as $uname => $list) {
    foreach ($list as [$skill, $daysToExpiry]) {
        // null = no expiry; negative = already lapsed (shows the renewal problem)
        $exp = $daysToExpiry === null ? null : date('Y-m-d', strtotime(($daysToExpiry >= 0 ? '+' : '-') . abs($daysToExpiry) . ' days'));
        $us->execute([$UID[$uname], $skill, $exp, backdate(500, 200)]);
        $nCerts++;
    }
}
say("Manual skills: {$nCerts} certifications across " . count($certs) . ' people (2 already lapsed, 3 expiring soon)');

// ---------------------------------------------------------------- skill mapping
// Gamified proficiencies are earned by logging repair hours against an equipment
// CATEGORY, but only categories present in skill_automation_config ever score.
// The stock config shipped two rows, one of which ("Conveyors") matches no asset
// category at all, so almost all real work went unrecognised. Every category the
// seeder actually creates is mapped here, exactly by name.
$skillMap = [
    ['Machining',   'Machining Specialist',  '⚙️'],
    ['Robotics',    'Robotics Technician',   '🤖'],
    ['Conveyance',  'Conveyor Master',       '📦'],
    ['Fabrication', 'Fabrication Expert',    '🔥'],
    ['Assembly',    'Assembly Specialist',   '🔩'],
    ['Quality',     'Quality Systems Tech',  '🎯'],
    ['Packaging',   'Packaging Technician',  '📦'],
    ['Utilities',   'Utilities Engineer',    '⚡'],
    ['Handling',    'Lifting & Handling',    '🏗️'],
    ['Auxiliary',   'Auxiliary Systems',     '🧰'],
];
$pdo->exec("DELETE FROM skill_automation_config");
$sm = $pdo->prepare("INSERT INTO skill_automation_config (equipment_category, skill_name, icon) VALUES (?,?,?)");
foreach ($skillMap as $s) { $sm->execute($s); }
say('Skill mappings: ' . count($skillMap) . ' equipment categories mapped to proficiencies');

// ---------------------------------------------------------------- departments
$depts = [
    ['Maintenance Operations', 250000, 0],
    ['Production Support',     180000, 0],
    ['Facilities & Utilities',  95000, 0],
    ['Tooling & Fixtures',      60000, 0],
    ['Health, Safety & Env.',   40000, 0],
];
$dp = $pdo->prepare("INSERT INTO departments (dept_name, budget_allocated, budget_consumed) VALUES (?,?,?)");
$DEPT = [];
foreach ($depts as $d) { $dp->execute($d); $DEPT[$d[0]] = (int)$pdo->lastInsertId(); }
say('Departments: ' . count($DEPT));

// ---------------------------------------------------------------- vendors
$vendors = [
    ['Nordwerk Industrial Supply', 'Ingrid Sørensen', 'orders@nordwerk.example',  '+47 22 55 10 40', 'Net 30', 'Oslo, NO',        '3-5 days',  'Distributor', 4.6],
    ['SKF Authorised Partner BV',  'Ruud van Dijk',   'sales@skf-partner.example','+31 20 441 9020', 'Net 45', 'Utrecht, NL',     '2-4 days',  'OEM',         4.8],
    ['Siemens Drive Services',     'Klaus Berger',    'service@siemens-ds.example','+49 911 895 220','Net 60', 'Nürnberg, DE',    '5-10 days', 'OEM',         4.4],
    ['Atlas Pneumatic Group',      'Marta Kowalska',  'support@atlaspg.example',  '+48 22 310 8800', 'Net 30', 'Warsaw, PL',      '4-7 days',  'OEM',         4.1],
    ['Baltic Bearing & Seal',      'Tomas Petrauskas','info@balticbs.example',    '+370 5 210 3344', 'Net 30', 'Vilnius, LT',     '2-3 days',  'Distributor', 3.9],
    ['Hydratech Fluid Power',      'Elena Rossi',     'orders@hydratech.example', '+39 02 8940 1177','Net 45', 'Milan, IT',       '6-9 days',  'Distributor', 4.2],
    ['Volt & Circuit Electricals', 'Peter Hoffmann',  'desk@voltcircuit.example', '+49 40 3070 5511','Net 30', 'Hamburg, DE',     '1-2 days',  'Local',       4.7],
    ['ToolCraft Precision Ltd',    'Sian Roberts',    'quotes@toolcraft.example', '+44 121 555 0188','Net 60', 'Birmingham, UK',  '7-14 days', 'OEM',         4.0],
];
$vp = $pdo->prepare("INSERT INTO vendors_suppliers
    (vendor_name, primary_contact_name, contact_email, contact_phone, payment_terms, vendor_address,
     shipping_time, vendor_type, rating, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
$VEND = [];
foreach ($vendors as $v) {
    $vp->execute([$v[0],$v[1],$v[2],$v[3],$v[4],$v[5],$v[6],$v[7],$v[8], backdate(400, 300)]);
    $VEND[$v[0]] = (int)$pdo->lastInsertId();
}
$VIDS = array_values($VEND);
say('Vendors: ' . count($VEND));

// ---------------------------------------------------------------- plant topology
$wp = $pdo->prepare("INSERT INTO workshops (name, location, status) VALUES (?,?,'Active')");
$wp->execute(['Plant A — Machining & Fabrication', 'Building 1, North Yard']);  $WS_A = (int)$pdo->lastInsertId();
$wp->execute(['Plant B — Assembly & Packaging',    'Building 2, South Yard']);  $WS_B = (int)$pdo->lastInsertId();

$lines = [
    [$WS_A, 'CNC Cell 1',        'Hydraulic manifolds, valve bodies'],
    [$WS_A, 'CNC Cell 2',        'Pump housings, flange sets'],
    [$WS_A, 'Fabrication Line',  'Weldments, frames, brackets'],
    [$WS_B, 'Assembly Line 1',   'Pump assemblies (series 400)'],
    [$WS_B, 'Assembly Line 2',   'Actuator modules'],
    [$WS_B, 'Packaging Line',    'Palletised finished goods'],
];
$lp = $pdo->prepare("INSERT INTO production_lines (workshop_id, name, products_built, status) VALUES (?,?,?,'Active')");
$LINE = [];
foreach ($lines as $l) { $lp->execute($l); $LINE[$l[1]] = ['id' => (int)$pdo->lastInsertId(), 'ws' => $l[0]]; }
say('Plant: 2 workshops, ' . count($LINE) . ' production lines');

// ---------------------------------------------------------------- equipment
// Realistic asset register: OEM data, criticality mix, PM intervals, warranty/EOL
// dates spread either side of today so the asset screens show live status variety.
$equipment = [
    // name,                           category,     crit, brand,           model,          line,               type
    ['DMG Mori NHX 5000 Machining Ctr','Machining',  'A', 'DMG Mori',      'NHX 5000',     'CNC Cell 1',       'CNC Machining Center'],
    ['Mazak VTC-800 Vertical Center',  'Machining',  'A', 'Yamazaki Mazak','VTC-800/30SR', 'CNC Cell 1',       'CNC Machining Center'],
    ['Okuma LB3000 EX II Lathe',       'Machining',  'A', 'Okuma',         'LB3000 EX II', 'CNC Cell 2',       'CNC Turning Center'],
    ['Haas VF-4SS Mill',               'Machining',  'B', 'Haas',          'VF-4SS',       'CNC Cell 2',       'CNC Machining Center'],
    ['Chip Conveyor & Coolant Unit 1', 'Auxiliary',  'C', 'Knoll',         'KTS-40',       'CNC Cell 1',       'Coolant System'],
    ['Chip Conveyor & Coolant Unit 2', 'Auxiliary',  'C', 'Knoll',         'KTS-40',       'CNC Cell 2',       'Coolant System'],
    ['Fronius TPS 400i Weld Station',  'Fabrication','B', 'Fronius',       'TPS 400i',     'Fabrication Line', 'Welding Station'],
    ['KUKA KR 60 Weld Robot',          'Robotics',   'A', 'KUKA',          'KR 60-3',      'Fabrication Line', 'Articulated Robot'],
    ['Amada HFE 1303 Press Brake',     'Fabrication','B', 'Amada',         'HFE 1303',     'Fabrication Line', 'Press Brake'],
    ['Trumpf TruLaser 3030 Cutter',    'Fabrication','A', 'TRUMPF',        'TruLaser 3030','Fabrication Line', 'Laser Cutter'],
    ['Assembly Conveyor A1 (Main)',    'Conveyance', 'B', 'Interroll',     'MCP-2000',     'Assembly Line 1',  'Belt Conveyor'],
    ['Atlas Copco Nutrunner Cell A1',  'Assembly',   'B', 'Atlas Copco',   'Tensor STR61', 'Assembly Line 1',  'Torque System'],
    ['Leak Test Bench A1',             'Quality',    'A', 'ATEQ',          'F620',         'Assembly Line 1',  'Leak Tester'],
    ['FANUC M-20iD Pick and Place',    'Robotics',   'B', 'FANUC',         'M-20iD/25',    'Assembly Line 2',  'Articulated Robot'],
    ['Assembly Conveyor A2',           'Conveyance', 'C', 'Interroll',     'MCP-1600',     'Assembly Line 2',  'Belt Conveyor'],
    ['Servo Press Station A2',         'Assembly',   'A', 'Promess',       'EMAP-100',     'Assembly Line 2',  'Servo Press'],
    ['Vision Inspection Rig A2',       'Quality',    'B', 'Cognex',        'In-Sight 9912','Assembly Line 2',  'Vision System'],
    ['Multivac R145 Thermoformer',     'Packaging',  'B', 'MULTIVAC',      'R 145',        'Packaging Line',   'Thermoformer'],
    ['Markem-Imaje 9450 Coder',        'Packaging',  'C', 'Markem-Imaje',  '9450',         'Packaging Line',   'Inkjet Coder'],
    ['Robopac Palletiser P1',          'Packaging',  'B', 'Robopac',       'Genesis HS',   'Packaging Line',   'Palletiser'],
    ['Atlas Copco GA 55 Compressor',   'Utilities',  'A', 'Atlas Copco',   'GA 55 VSD+',   null,               'Air Compressor'],
    ['Chiller Unit CH-1',              'Utilities',  'A', 'Trane',         'CGAM 070',     null,               'Process Chiller'],
    ['Dust Extraction Unit DE-2',      'Utilities',  'C', 'Nederman',      'NFPD 4000',    null,               'Dust Extractor'],
    ['Overhead Crane 5T Bay 1',        'Handling',   'B', 'Konecranes',    'CXT 5000',     null,               'Overhead Crane'],
];
$ep = $pdo->prepare(
    "INSERT INTO equipment (asset_uuid, equip_name, category, criticality, oem_brand, oem_model, oem_serial,
        equipment_type, plant_name, line_name, station_name, workshop_id, line_id, vendor_id, po_value,
        date_of_purchase, warranty_expiry, eol_date, lifecycle_years, pm_days_interval, last_pm_date,
        is_active, base_voltage, loto_protocol, technical_details)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?,?)"
);
$EQ = [];
foreach ($equipment as $i => $e) {
    $lineId = $e[5] ? $LINE[$e[5]]['id'] : null;
    $wsId   = $e[5] ? $LINE[$e[5]]['ws'] : $WS_A;
    $plant  = $wsId === $WS_A ? 'Plant A' : 'Plant B';
    $ageY   = mt_rand(2, 11);
    $bought = date('Y-m-d', strtotime("-{$ageY} years -" . mt_rand(0, 300) . ' days'));

    $ep->execute([
        sprintf('WCC-%s-%04d', strtoupper(substr(md5($e[0]), 0, 6)), $i + 1),
        $e[0], $e[1], $e[2], $e[3], $e[4],
        strtoupper(substr($e[3], 0, 3)) . '-' . mt_rand(100000, 999999),
        $e[6], $plant, $e[5], $e[5] ? 'ST-' . str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) : 'Utilities',
        $wsId, $lineId, pick($VIDS), mt_rand(18, 480) * 1000,
        $bought,
        date('Y-m-d', strtotime($bought . ' +' . mt_rand(24, 60) . ' months')),   // some already expired
        date('Y-m-d', strtotime($bought . ' +' . mt_rand(12, 20) . ' years')),    // end of life
        mt_rand(12, 20),
        pick([30, 60, 90, 180]),
        date('Y-m-d', strtotime('-' . mt_rand(5, 120) . ' days')),
        pick(['400V 3ph', '400V 3ph', '230V 1ph', '690V 3ph']),
        "1. Notify line supervisor.\n2. Cycle stop, wait for safe state.\n3. Isolate main disconnect, apply personal lock + tag.\n4. Bleed residual pneumatic/hydraulic pressure.\n5. Verify zero energy before opening guards.",
        json_encode(['control' => pick(['Siemens S7-1500', 'Fanuc 31i', 'Beckhoff CX2040', 'Allen-Bradley CompactLogix']),
                     'network' => 'PROFINET',
                     'hmi'     => pick(['Siemens Comfort 12in', 'Fanuc iHMI', 'Beijer X2'])]),
    ]);
    $EQ[$e[0]] = (int)$pdo->lastInsertId();
}
$EQIDS  = array_values($EQ);
$EQNAME = array_flip($EQ);
say('Equipment: ' . count($EQ) . ' assets (A/B/C criticality mix, PM intervals, warranty + EOL dates)');

// ---------------------------------------------------------------- spare parts
// stock vs minimum_threshold is staged on purpose — healthy / at reorder point /
// below / stocked out — so the inventory screen and low-stock alerts have teeth.
$parts = [
    // name,                              code,         stock, min, cost,   max, moq, uom,   auto, vendor
    ['Deep Groove Ball Bearing 6205-2RS', 'BRG-6205',    42, 12,   14.50,  60,  10, 'pcs',  1, 1],
    ['Deep Groove Ball Bearing 6308-2RS', 'BRG-6308',     6, 10,   38.90,  40,  10, 'pcs',  1, 1],
    ['Spherical Roller Bearing 22215',    'BRG-22215',    3,  4,  187.00,  12,   2, 'pcs',  1, 1],
    ['Rotary Shaft Seal 45x62x8',         'SEA-4562',    88, 25,    6.20, 150,  50, 'pcs',  1, 4],
    ['O-Ring Kit NBR 70 (assorted)',      'SEA-ORK1',    14,  5,   42.00,  30,   5, 'kit',  0, 4],
    ['Hydraulic Filter Element HF-320',   'FLT-HF320',    9, 12,   64.75,  36,  12, 'pcs',  1, 5],
    ['Coolant Filter Bag 25 micron',      'FLT-CB25',    60, 20,    9.10, 120,  40, 'pcs',  1, 0],
    ['Compressor Air Filter GA55',        'FLT-GA55',     2,  3,  121.00,  12,   3, 'pcs',  1, 3],
    ['Pneumatic Cylinder 32x100 ISO',     'PNU-32100',    7,  4,   96.40,  16,   4, 'pcs',  0, 3],
    ['Solenoid Valve 5/2 24VDC',          'PNU-SV52',    18,  8,   73.20,  40,  10, 'pcs',  1, 3],
    ['FRL Unit 1/2in with Regulator',     'PNU-FRL12',    5,  3,  158.00,  10,   2, 'pcs',  0, 3],
    ['Servo Motor 1FK7 Replacement',      'ELC-1FK7',     1,  2, 1480.00,   4,   1, 'pcs',  1, 2],
    ['VFD 7.5kW 400V',                    'ELC-VFD75',    3,  2,  890.00,   8,   1, 'pcs',  1, 2],
    ['Contactor 3RT 25A',                 'ELC-3RT25',   22, 10,   44.30,  50,  10, 'pcs',  1, 6],
    ['Safety Relay PNOZ s5',              'ELC-PNOZ',     6,  4,  212.00,  12,   2, 'pcs',  1, 6],
    ['Proximity Sensor M12 PNP NO',       'ELC-PRX12',   35, 15,   28.60,  80,  20, 'pcs',  1, 6],
    ['Photoelectric Sensor Retro 10m',    'ELC-PHT10',    0,  6,   61.40,  24,   6, 'pcs',  1, 6],
    ['E-Stop Mushroom Button 22mm',       'ELC-ESTOP',   19,  8,   33.10,  40,  10, 'pcs',  0, 6],
    ['Timing Belt HTD 8M-1600-30',        'MEC-HTD16',   11,  6,   78.90,  24,   6, 'pcs',  1, 0],
    ['V-Belt SPZ 1600',                   'MEC-SPZ16',   26, 10,   17.40,  60,  20, 'pcs',  1, 0],
    ['Conveyor Belt PVC 600mm (per m)',   'MEC-CB600',   35, 15,   52.00,  90,  30, 'm',    1, 0],
    ['Drive Chain 12B-1 (per m)',         'MEC-DC12B',   12,  8,   24.80,  40,  10, 'm',    0, 0],
    ['Linear Guide Block HGH25',          'MEC-HGH25',    4,  4,  143.50,  12,   2, 'pcs',  1, 7],
    ['Ball Screw Support Unit BK15',      'MEC-BK15',     8,  3,  118.00,  16,   2, 'pcs',  0, 7],
    ['Carbide End Mill 12mm 4FL',         'TOL-EM12',    30, 12,   47.90,  80,  20, 'pcs',  1, 7],
    ['Carbide Insert CNMG 120408',        'TOL-CN120',   95, 40,   11.75, 200,  50, 'pcs',  1, 7],
    ['Collet ER32 12mm',                  'TOL-ER32',    16,  6,   38.00,  30,   6, 'pcs',  0, 7],
    ['Welding Torch Nozzle M8',           'TOL-WTN8',    24, 12,   15.30,  60,  20, 'pcs',  1, 3],
    ['Weld Contact Tip 1.0mm',            'TOL-WCT10',  150, 60,    2.85, 400, 100, 'pcs',  1, 3],
    ['Hydraulic Hose 3/8in R2 (per m)',   'HYD-H38',     18,  8,   21.60,  50,  10, 'm',    1, 5],
    ['Hydraulic Oil ISO VG46 (20L)',      'HYD-OIL46',    7,  4,   96.00,  20,   4, 'drum', 1, 5],
    ['Way Lube ISO VG68 (5L)',            'HYD-WL68',    13,  6,   41.20,  30,   6, 'can',  0, 5],
    ['Spindle Grease NLGI 2 (400g)',      'HYD-GRS2',    20,  8,   27.50,  40,  10, 'tube', 1, 1],
    ['Air Filter Panel G4 592x592',       'FAC-AFG4',    24, 12,   18.90,  60,  24, 'pcs',  1, 0],
    ['LED High Bay 150W IP65',            'FAC-LED150',   9,  4,   87.00,  20,   4, 'pcs',  0, 6],
];
$pp = $pdo->prepare(
    "INSERT INTO inventory_parts (part_name, internal_code, stock_level, minimum_threshold, cost_per_unit,
        maximum_stock, moq, uom, currency, auto_reorder, primary_vendor_id, oem_name, oem_part_number,
        standard_lead_time, expedited_lead_time, warehouse_id, aisle, rack, shelf, bin_code,
        part_condition, lifecycle_status, standardized_desc)
     VALUES (?,?,?,?,?,?,?,?,'EUR',?,?,?,?,?,?,1,?,?,?,?,'New',?,?)"
);
$PART = [];
foreach ($parts as $i => $p) {
    $vIdx = $p[9] % count($vendors);
    $vend = $VEND[$vendors[$vIdx][0]];
    $pp->execute([
        $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $vend,
        $vendors[$vIdx][0],
        strtoupper(substr($p[1], 0, 3)) . mt_rand(1000, 9999),
        mt_rand(3, 21), mt_rand(1, 5),
        chr(65 + intdiv($i, 8)), 'R' . (1 + $i % 6), 'S' . (1 + $i % 4),
        sprintf('%s-%02d-%02d', chr(65 + intdiv($i, 8)), 1 + $i % 6, 1 + $i % 4),
        mt_rand(1, 12) === 1 ? 'Phasing Out' : 'Active',
        $p[0],
    ]);
    $PART[$p[1]] = ['id' => (int)$pdo->lastInsertId(), 'name' => $p[0], 'cost' => $p[4], 'vendor' => $vend];
}
$PIDS = array_values($PART);
$low  = count(array_filter($parts, fn($p) => $p[2] <= $p[3]));
say('Inventory: ' . count($PART) . " parts ({$low} at/below reorder point, 1 stocked out)");

// ---------------------------------------------------------------- BOM + lifecycle
// Which parts fit which machines. Parts fitted to a Class-A machine become the
// "critical spares" flagged with a star on the inventory page; a couple of parts
// are marked phasing-out/obsolete to demonstrate the do-not-reorder status.
require_once __DIR__ . '/bom_map.php';
$bomRes = wcc_demo_apply_bom($pdo, true);   // fresh seed: rebuild the BOM cleanly
say("BOM: {$bomRes['bom_rows']} part-to-machine links; {$bomRes['lifecycle_set']} lifecycle overrides");

// ---------------------------------------------------------------- fault library
// Each entry: fault_desc, fault_type, root cause, action taken, typical repair minutes.
// Repair minutes drive MTTR, so they are plausible per fault class, not random noise.
$faults = [
    ['Spindle overheat alarm on cycle start',        'Mechanical', 'Spindle bearing grease degraded past service life',       'Regreased spindle, replaced front bearing set, ran warm-up cycle and verified temp curve', 180, 300, 'Machining'],
    ['Coolant pressure low, tool wear increasing',   'Hydraulic',  'Coolant filter blocked with fine swarf',                   'Replaced filter bag, flushed lines, restored 4.2 bar at nozzle',                          45, 90, 'Machining'],
    ['Axis servo fault F-0031 on rapid move',        'Electrical', 'Encoder cable chafed inside drag chain',                   'Replaced encoder cable, added protective sleeve, re-homed axis',                          120, 240, 'Machining'],
    ['Tool changer stalls mid-swap',                 'Mechanical', 'Cam follower worn, carousel indexing out of tolerance',    'Replaced cam follower, re-timed carousel, ran 50 change cycles clean',                    150, 260, 'Machining'],
    ['Conveyor belt tracking off to one side',       'Mechanical', 'Tail pulley bearing seized, belt pulling right',           'Replaced 6205 bearing, re-tensioned and re-tracked belt',                                 60, 120, 'Conveyance'],
    ['Robot in fault, brake release error',          'Electrical', 'Axis 3 brake contactor welded closed',                     'Replaced contactor, verified brake test, re-mastered axis 3',                             90, 180, 'Robotics'],
    ['Weld quality NOK, porosity in seam',           'Process',    'Contact tip worn and shielding gas flow low',              'Replaced contact tip and nozzle, corrected gas flow to 14 l/min, weld coupon passed',      40, 75, 'Fabrication'],
    ['Laser cutter head crash on nest start',        'Mechanical', 'Sheet not seated, height sensor calibration drifted',      'Replaced ceramic, recalibrated capacitive height sensor, re-ran nest',                    75, 150, 'Fabrication'],
    ['Press brake not holding pressure',             'Hydraulic',  'Main cylinder seal blown',                                 'Rebuilt cylinder with new seal kit, bled system, verified bend angle repeatability',      240, 420, 'Fabrication'],
    ['Leak test bench failing good parts',           'Process',    'Reference volume drifted, test fixture O-ring cracked',    'Replaced fixture O-ring, recalibrated against master part, verified Cg/Cgk',               90, 160, 'Quality'],
    ['Nutrunner torque out of window',               'Process',    'Transducer calibration expired',                           'Recalibrated transducer, re-ran capability on 30 joints, released cell',                   60, 110, 'Assembly'],
    ['Servo press position error at bottom dead ctr','Electrical', 'Load cell signal noise from unshielded run',               'Rerouted load cell cable away from VFD, added shield bonding, error cleared',              105, 200, 'Assembly'],
    ['Vision rig rejecting at 12 percent',           'Process',    'Ambient light change after lamp replacement',              'Re-taught vision model, fitted shroud, false reject back under 0.5 percent',               70, 130, 'Quality'],
    ['Thermoformer seal temperature unstable',       'Electrical', 'Heater band open circuit on zone 3',                       'Replaced heater band and thermocouple, re-tuned PID for zone 3',                          110, 190, 'Packaging'],
    ['Coder printing faint, missing characters',     'Process',    'Printhead partially clogged, solvent low',                 'Ran printhead clean cycle, topped solvent, replaced filter',                              35, 70, 'Packaging'],
    ['Palletiser stops, pattern incomplete',         'Electrical', 'Photoelectric sensor misaligned by pallet strike',         'Realigned and reinforced sensor bracket, verified pattern over 20 pallets',                50, 95, 'Packaging'],
    ['Compressor tripping on high discharge temp',   'Utilities',  'Cooler matrix fouled, ambient extraction restricted',      'Cleaned cooler matrix, replaced air filter, restored discharge temp to 78C',              130, 220, 'Utilities'],
    ['Chiller low delta-T, machines running warm',   'Utilities',  'Glycol concentration low and strainer partially blocked',  'Topped glycol to 30 percent, cleaned strainer, delta-T back to 5.5K',                     95, 170, 'Utilities'],
    ['Crane hoist limit switch intermittent',        'Electrical', 'Limit switch contact oxidised',                            'Replaced limit switch, tested overtravel stop, load tested at 5T',                        85, 150, 'Handling'],
    ['Dust extractor low suction at station 3',      'Mechanical', 'Filter cartridges loaded, pulse valve not firing',         'Replaced pulse valve diaphragm, cleaned cartridges, suction restored',                    65, 120, 'Utilities'],
    ['Hydraulic power pack noisy, foaming oil',      'Hydraulic',  'Oil level low, suction strainer drawing air',              'Topped VG46, replaced suction strainer, bled system',                                     55, 100, 'Auxiliary'],
    ['Guard door interlock will not reset',          'Safety',     'Safety relay channel fault after door impact',             'Replaced PNOZ s5 relay, verified dual-channel monitoring and stop category 1',            80, 140, 'Auxiliary'],
];
$prios = ['critical', 'high', 'normal', 'normal', 'normal', 'low'];

// ---------------------------------------------------------------- tickets + actions
// ~9 months of history, weighted toward recent months so the trend charts slope.
$tk  = $pdo->prepare("INSERT INTO active_tickets (ticket_id, equip_id, report_date, report_time, announced_by,
                        pic, fault_desc, priority, status, closed_by, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$ta  = $pdo->prepare("INSERT INTO ticket_actions (ticket_id, tech_name, action_start, action_end, fault_type,
                        root_cause, action_taken, parts_used, timestamp_logged) VALUES (?,?,?,?,?,?,?,?,?)");
$tc  = $pdo->prepare("INSERT INTO ticket_comments (ticket_id, user_name, comment_text, created_at) VALUES (?,?,?,?)");
$led = $pdo->prepare("INSERT INTO inventory_ledger (part_id, change_qty, reason, reference_type, reference_id,
                        notes, actor_user_id, created_at) VALUES (?,?,?,?,?,?,?,?)");

$reporters = ['Rui Silva', 'Elise Moreau', 'Marc Dubois', 'Priya Nair'];
$TICKETS   = [];
// A 24-asset plant running two shifts generates roughly this much corrective work
// over nine months. The volume also matters for the proficiency board: gamified
// tiers are earned in logged hours (10/20/40/100/200), so a thin ticket history
// leaves every technician sitting below the first tier with an empty-looking board.
$nTickets  = 420;
$openNow   = [];

// Equipment grouped by category, so a fault lands on a machine it could actually
// happen to — a spindle overheat should never be raised against a palletiser.
$EQBYCAT = [];
foreach ($equipment as $e) { $EQBYCAT[$e[1]][] = $EQ[$e[0]]; }

// Technicians specialise. Random assignment spreads hours evenly across ten
// categories so nobody ever crosses a tier threshold, which is precisely why the
// proficiency board read as all zeros. Each category has an owner who takes most
// of its work, with the rest spilling to colleagues (holidays, shifts, escalation).
$SPECIALIST = [
    'Machining'   => $UID['t.yamamoto'],
    'Utilities'   => $UID['t.yamamoto'],
    'Robotics'    => $UID['j.okafor'],
    'Fabrication' => $UID['j.okafor'],
    'Auxiliary'   => $UID['j.okafor'],
    'Conveyance'  => $UID['s.lindqvist'],
    'Packaging'   => $UID['s.lindqvist'],
    'Assembly'    => $UID['k.novak'],
    'Quality'     => $UID['k.novak'],
    'Handling'    => $UID['k.novak'],
];

for ($i = 0; $i < $nTickets; $i++) {
    $f    = $faults[$i % count($faults)];
    $cat  = $f[6];
    $pool = $EQBYCAT[$cat] ?? $EQIDS;
    $eq   = $pool[($i * 7 + 3) % count($pool)];
    // Weighted backdating: squaring the draw pulls most tickets toward the recent past.
    $days = (int)round(270 * pow(mt_rand(0, 1000) / 1000, 1.7));
    $open = strtotime(backdate($days, $days));
    $tid  = 'TK-' . date('ymd', $open) . '-' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT);
    $prio = $days < 5 ? pick(['critical', 'high', 'normal']) : pick($prios);

    // Newest handful stay live so the board is not an empty "all clear" screen.
    if ($i < 4)       { $status = 'OPEN';    }
    elseif ($i < 9)   { $status = 'PENDING'; }
    else              { $status = 'CLOSED';  }

    // 85% to the category owner, the rest to whoever else was on shift.
    $owner    = $SPECIALIST[$cat] ?? null;
    $tech     = ($owner && mt_rand(1, 100) <= 85) ? $owner : pick($TECHS);
    $techName = $TECHNAME[$tech];

    $tk->execute([
        $tid, $eq, date('Y-m-d', $open), date('H:i:s', $open),
        pick($reporters),
        $status === 'OPEN' ? null : $techName,
        $f[0], $prio, $status,
        $status === 'CLOSED' ? $techName : null,
        date('Y-m-d H:i:s', $open),
    ]);
    $TICKETS[] = ['id' => $tid, 'equip' => $eq, 'status' => $status, 'opened' => $open];
    if ($status !== 'CLOSED') $openNow[] = $tid;

    if ($status === 'CLOSED') {
        // Response delay then repair duration -> gives MTTD and MTTR something real to compute.
        $start = $open + mt_rand(4, 90) * 60;
        $end   = $start + mt_rand($f[4], $f[5]) * 60;

        // Roughly two in three repairs consume a part; log it to the ledger so the
        // parts-consumption history in inventory_audit.php is populated.
        $partsUsed = '';
        if (mt_rand(1, 3) > 1) {
            $pk  = $PIDS[($i * 5 + 2) % count($PIDS)];
            $qty = mt_rand(1, 3);
            $partsUsed = $pk['name'] . ' x' . $qty;
            $led->execute([$pk['id'], -$qty, 'ticket_consume', 'active_tickets', $tid,
                           'Consumed during repair of ' . $tid, $tech, date('Y-m-d H:i:s', $end)]);
        }

        $ta->execute([$tid, $techName, date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end),
                      $f[1], $f[2], $f[3], $partsUsed, date('Y-m-d H:i:s', $end)]);

        if (mt_rand(1, 4) === 1) {
            $tc->execute([$tid, pick($reporters), pick([
                'Line was held for this - please prioritise next time.',
                'Same symptom as last month, worth a PM interval review.',
                'Spare was on the shelf, good catch by stores.',
                'Operator briefed on the restart procedure afterwards.',
            ]), date('Y-m-d H:i:s', $end + 3600)]);
        }
    } elseif ($status === 'PENDING') {
        // In progress: started, not finished.
        $start = $open + mt_rand(10, 120) * 60;
        $ta->execute([$tid, $techName, date('Y-m-d H:i:s', $start), null, $f[1], $f[2],
                      'Diagnosis in progress - ' . lcfirst($f[2]) . '. Awaiting spare part.', '',
                      date('Y-m-d H:i:s', $start)]);
    }
}
say('Tickets: ' . $nTickets . ' over 9 months (4 open, 5 in progress, ' . ($nTickets - 9) . ' closed with full action logs)');

// ---------------------------------------------------------------- PM checklists
$checklists = [
    ['CNC Machining Centre - Monthly PM', 'Monthly preventive routine for machining centres.', [
        ['Check and top spindle lubrication reservoir', 10],
        ['Inspect way covers and wipers for damage', 10],
        ['Clean chip conveyor and coolant tank strainer', 25],
        ['Verify coolant concentration with refractometer', 10],
        ['Check air pressure and drain FRL bowl', 5],
        ['Inspect drag chains and cable routing', 15],
        ['Run axis backlash check and record values', 20],
        ['Verify E-stop and guard interlocks', 10],
    ]],
    ['Conveyor - Quarterly PM', 'Quarterly routine for belt conveyors.', [
        ['Inspect belt for wear, cuts and tracking', 15],
        ['Check and re-tension belt to spec', 15],
        ['Grease head and tail pulley bearings', 10],
        ['Inspect drive chain and sprocket wear', 10],
        ['Verify emergency pull-cord operation', 10],
    ]],
    ['Industrial Robot - Semi-annual PM', 'Semi-annual routine for articulated robots.', [
        ['Inspect and clean robot arm and cabling', 20],
        ['Check axis backlash against baseline', 30],
        ['Verify brake test on all axes', 20],
        ['Check gearbox oil level and condition', 25],
        ['Confirm mastering and re-master if required', 30],
        ['Test safety-rated monitored stop', 15],
    ]],
    ['Compressed Air System - Monthly PM', 'Monthly routine for compressor and air network.', [
        ['Check and record discharge temperature', 5],
        ['Inspect and replace air intake filter if loaded', 20],
        ['Drain receiver and check autodrain operation', 10],
        ['Check oil level and top if required', 10],
        ['Leak survey on main header', 30],
    ]],
    ['Safety Systems - Annual Verification', 'Annual verification of safety functions.', [
        ['Verify all E-stops halt motion within spec', 30],
        ['Test guard interlocks, dual channel', 30],
        ['Verify light curtain response time', 25],
        ['Confirm LOTO points match documented protocol', 20],
        ['Record results and sign off', 15],
    ]],
];
$cl  = $pdo->prepare("INSERT INTO pm_checklists (title, description, created_at) VALUES (?,?,?)");
$cli = $pdo->prepare("INSERT INTO pm_checklist_items (checklist_id, task_desc, expected_time_mins) VALUES (?,?,?)");
$CHK = [];
foreach ($checklists as $c) {
    $cl->execute([$c[0], $c[1], backdate(300, 200)]);
    $cid = (int)$pdo->lastInsertId();
    foreach ($c[2] as $item) $cli->execute([$cid, $item[0], $item[1]]);
    $CHK[$c[0]] = $cid;
}
$CHKIDS = array_values($CHK);
say('PM checklists: ' . count($CHK) . ' routines, ' . array_sum(array_map(fn($c) => count($c[2]), $checklists)) . ' tasks');

// ---------------------------------------------------------------- PM schedules
$pms = $pdo->prepare("INSERT INTO pm_schedules (title, description, equipment_id, assigned_to, checklist_id,
                        frequency_days, next_run_date, created_at) VALUES (?,?,?,?,?,?,?,?)");
$pmPlan = [
    ['DMG Mori NHX 5000 Machining Ctr', 'CNC Machining Centre - Monthly PM',  30],
    ['Mazak VTC-800 Vertical Center',   'CNC Machining Centre - Monthly PM',  30],
    ['Okuma LB3000 EX II Lathe',        'CNC Machining Centre - Monthly PM',  30],
    ['Haas VF-4SS Mill',                'CNC Machining Centre - Monthly PM',  30],
    ['Assembly Conveyor A1 (Main)',     'Conveyor - Quarterly PM',            90],
    ['Assembly Conveyor A2',            'Conveyor - Quarterly PM',            90],
    ['KUKA KR 60 Weld Robot',           'Industrial Robot - Semi-annual PM', 180],
    ['FANUC M-20iD Pick and Place',     'Industrial Robot - Semi-annual PM', 180],
    ['Atlas Copco GA 55 Compressor',    'Compressed Air System - Monthly PM', 30],
    ['Trumpf TruLaser 3030 Cutter',     'Safety Systems - Annual Verification', 365],
    ['Servo Press Station A2',          'Safety Systems - Annual Verification', 365],
];
foreach ($pmPlan as $i => $p) {
    // A couple of overdue schedules on purpose - a demo where nothing is late is not credible.
    $next = $i < 2 ? date('Y-m-d', strtotime('-' . mt_rand(2, 9) . ' days')) : fwddate(1, 60);
    $pms->execute([
        $p[1] . ' - ' . $p[0], 'Scheduled preventive maintenance per OEM interval.',
        $EQ[$p[0]], pick($TECHS), $CHK[$p[1]], $p[2], $next, backdate(280, 200),
    ]);
}
say('PM schedules: ' . count($pmPlan) . ' (2 overdue, rest upcoming)');

// ---------------------------------------------------------------- work orders
$wo = $pdo->prepare("INSERT INTO work_orders (title, description, equipment_id, assigned_to, checklist_data,
                       status, scheduled_date, started_at, completed_date, completed_by, parts_list)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$woTitles = [
    'Monthly PM - lubrication and inspection',
    'Quarterly PM - belt and drive inspection',
    'Replace worn tool changer cam follower',
    'Rebuild main hydraulic cylinder',
    'Recalibrate torque transducer',
    'Replace conveyor tail bearing',
    'Safety interlock verification',
    'Annual thermographic survey of panels',
    'Coolant system deep clean and refill',
    'Robot gearbox oil change',
    'Replace heater band, thermoformer zone 3',
    'Vision system re-teach after lamp change',
    'Compressor cooler matrix clean',
    'Chiller glycol top-up and strainer clean',
    'Crane annual load test and inspection',
];
$woCounts = ['Completed' => 34, 'In Progress' => 4, 'Scheduled' => 9, 'Missed' => 3, 'Cancelled' => 2];
$nWo = 0;
foreach ($woCounts as $status => $count) {
    for ($i = 0; $i < $count; $i++) {
        $eq   = $EQIDS[($nWo * 5 + 1) % count($EQIDS)];
        $tech = pick($TECHS);
        $parts = json_encode([['part_id' => $PIDS[($nWo * 3) % count($PIDS)]['id'], 'qty' => mt_rand(1, 4)]]);

        if ($status === 'Completed') {
            $sched = date('Y-m-d', strtotime('-' . mt_rand(10, 240) . ' days'));
            $start = $sched . ' ' . sprintf('%02d:%02d:00', mt_rand(7, 14), mt_rand(0, 59));
            $done  = date('Y-m-d H:i:s', strtotime($start) + mt_rand(45, 300) * 60);
            $wo->execute([pick($woTitles), 'Planned maintenance task raised from the PM programme.',
                          $eq, $tech, null, $status, $sched, $start, $done, $tech, $parts]);
        } elseif ($status === 'In Progress') {
            $sched = date('Y-m-d', strtotime('-' . mt_rand(0, 2) . ' days'));
            $wo->execute([pick($woTitles), 'Work started, awaiting completion sign-off.',
                          $eq, $tech, null, $status, $sched,
                          $sched . ' ' . sprintf('%02d:%02d:00', mt_rand(7, 12), mt_rand(0, 59)), null, null, $parts]);
        } elseif ($status === 'Scheduled') {
            $wo->execute([pick($woTitles), 'Upcoming planned task.', $eq, $tech, null, $status,
                          fwddate(1, 45), null, null, null, $parts]);
        } elseif ($status === 'Missed') {
            // Overdue: scheduled in the past, never started. Shows the escalation story.
            $wo->execute([pick($woTitles), 'Scheduled task not executed on the planned date.',
                          $eq, $tech, null, $status,
                          date('Y-m-d', strtotime('-' . mt_rand(4, 30) . ' days')), null, null, null, $parts]);
        } else {
            $wo->execute([pick($woTitles), 'Cancelled - asset taken off line for a project.',
                          $eq, $tech, null, $status,
                          date('Y-m-d', strtotime('-' . mt_rand(20, 120) . ' days')), null, null, null, $parts]);
        }
        $nWo++;
    }
}
// Ledger entries for the completed work-order consumption, so parts history covers both paths.
$woRows = $pdo->query("SELECT wo_id, equipment_id, assigned_to, completed_date FROM work_orders
                       WHERE status='Completed' ORDER BY wo_id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($woRows as $k => $w) {
    if ($k % 2) continue;
    $pk = $PIDS[($k * 4 + 5) % count($PIDS)];
    $q  = mt_rand(1, 3);
    $led->execute([$pk['id'], -$q, 'wo_consume', 'work_orders', (string)$w['wo_id'],
                   'Consumed on work order #' . $w['wo_id'], $w['assigned_to'], $w['completed_date']]);
}
say("Work orders: {$nWo} (" . implode(', ', array_map(fn($k, $v) => "$v $k", array_keys($woCounts), $woCounts)) . ')');

// ---------------------------------------------------------------- procurement
// One or more POs parked at EVERY stepper stage, so the tracking stepper demo can
// walk an order forward from any state without first having to create one.
$poIns  = $pdo->prepare("INSERT INTO purchase_orders (po_number, vendor_id, created_by, dept_id, total_amount,
                          status, approval_level, is_emergency_bypass, created_at) VALUES (?,?,?,?,?,?,?,?,?)");
$poItem = $pdo->prepare("INSERT INTO po_items (po_id, part_id, ordered_qty, received_qty, unit_price, currency, status)
                          VALUES (?,?,?,?,?,'EUR',?)");
$poLog  = $pdo->prepare("INSERT INTO po_status_logs (po_id, action_type, status_from, status_to, note, changed_by, created_at)
                          VALUES (?,?,?,?,?,?,?)");

// status => [how many, how many days back it was raised]
$poPlan = [
    'Draft'              => [2,  3],
    'Pending Approval'   => [4,  6],
    'Issued'             => [4, 14],
    'Shipped'            => [3, 22],
    'In Transit'         => [3, 28],
    'Partially Received' => [2, 35],
    'Fully Received'     => [5, 55],
    'Closed'             => [8, 110],
    'Cancelled'          => [2, 70],
];
// The full forward path; each PO gets the slice of history up to where it now sits.
$flow = ['Draft', 'Pending Approval', 'Issued', 'Shipped', 'In Transit', 'Partially Received', 'Fully Received', 'Closed'];
$deptIds = array_values($DEPT);
$nPo = 0; $poSeq = 1;

foreach ($poPlan as $status => $cfg) {
    [$count, $daysBack] = $cfg;
    for ($i = 0; $i < $count; $i++) {
        $created = strtotime(backdate($daysBack + 10, max(1, $daysBack - 8)));
        $vendor  = pick($VIDS);
        $dept    = $deptIds[$nPo % count($deptIds)];
        $nLines  = mt_rand(1, 4);

        // Price the order from its real lines so the total is not a made-up number.
        $lines = []; $total = 0.0;
        for ($l = 0; $l < $nLines; $l++) {
            $pk  = $PIDS[($nPo * 6 + $l * 3) % count($PIDS)];
            $qty = mt_rand(2, 25);
            $lines[] = ['part' => $pk, 'qty' => $qty];
            $total  += $qty * $pk['cost'];
        }
        $total = round($total, 2);

        // Mirrors inc/procurement.php routing so the data is consistent with the live rules.
        $approval = $total > 5000 ? 'Plant Director' : ($total > 1500 ? 'Maintenance Manager' : 'Auto-Approved');
        $emergency = ($status === 'Issued' && $i === 0) ? 1 : 0;

        $poIns->execute([
            sprintf('PR-%s-%04d', date('Ymd', $created), 1000 + $poSeq++),
            $vendor, pick([$ADMIN, $UID['p.nair'], $UID['h.bakker']]), $dept, $total,
            $status, $approval, $emergency, date('Y-m-d H:i:s', $created),
        ]);
        $poId = (int)$pdo->lastInsertId();

        foreach ($lines as $ln) {
            $recv = in_array($status, ['Fully Received', 'Closed'], true) ? $ln['qty']
                  : ($status === 'Partially Received' ? (int)floor($ln['qty'] / 2) : 0);
            $poItem->execute([$poId, $ln['part']['id'], $ln['qty'], $recv, $ln['part']['cost'],
                              $recv >= $ln['qty'] ? 'Received' : 'Pending']);

            // Receipts move stock, so the ledger tells the whole story, not just consumption.
            if ($recv > 0) {
                $led->execute([$ln['part']['id'], $recv, 'po_receipt', 'purchase_orders', (string)$poId,
                               'Goods receipt against PO #' . $poId, $UID['h.bakker'],
                               date('Y-m-d H:i:s', $created + 86400 * mt_rand(3, 12))]);
            }
        }

        // Replay the status history up to the current stage.
        $stop = array_search($status, $flow, true);
        if ($status === 'Cancelled') $stop = 1;                       // cancelled after being raised
        $ts   = $created;
        $prev = null;
        for ($s = 0; $s <= ($stop === false ? 0 : $stop); $s++) {
            $to = $flow[$s];
            $note = $s === 0 ? 'Purchase request raised.' : null;
            if ($to === 'Issued') {
                $note = $approval === 'Auto-Approved'
                      ? 'Auto-approved: total within the configured approval limit.'
                      : 'Approved by ' . $approval . '.';
            }
            if ($to === 'Partially Received') $note = 'Part shipment received, balance outstanding.';
            if ($to === 'Fully Received')     $note = 'All lines received and checked into stores.';
            if ($to === 'Closed')             $note = 'Invoice matched, order closed.';
            $poLog->execute([$poId, $s === 0 ? 'created' : 'status_change', $prev, $to, $note,
                             pick([$ADMIN, $UID['p.nair'], $UID['h.bakker']]), date('Y-m-d H:i:s', $ts)]);
            $prev = $to;
            $ts  += 86400 * mt_rand(1, 5);
        }
        if ($status === 'Cancelled') {
            $poLog->execute([$poId, 'status_change', $prev, 'Cancelled', 'Cancelled - requirement covered from stock.',
                             $ADMIN, date('Y-m-d H:i:s', $ts)]);
        }
        $nPo++;
    }
}
say("Purchase orders: {$nPo} across all 9 stepper stages, with line items and full status history");

// ---------------------------------------------------------------- budgets
// Consumed budget derived from the POs that actually landed, so the figures reconcile.
$pdo->exec("UPDATE departments d SET budget_consumed = COALESCE((
                SELECT SUM(p.total_amount) FROM purchase_orders p
                 WHERE p.dept_id = d.dept_id AND p.status IN ('Fully Received','Closed','Partially Received')), 0)");
$dbl = $pdo->prepare("INSERT INTO department_budget_logs (dept_id, action_type, amount, notes, changed_by, created_at)
                      VALUES (?,?,?,?,?,?)");
foreach ($DEPT as $name => $id) {
    $dbl->execute([$id, 'allocation', $depts[array_search($name, array_column($depts, 0), true)][1],
                   'Annual budget allocated for the current financial year.', $ADMIN, backdate(300, 280)]);
}
$spend = $pdo->query("SELECT dept_id, dept_name, budget_allocated, budget_consumed FROM departments")->fetchAll(PDO::FETCH_ASSOC);
foreach ($spend as $s) {
    if ($s['budget_consumed'] > 0) {
        $dbl->execute([$s['dept_id'], 'consumption', $s['budget_consumed'],
                       'Cumulative spend against received purchase orders.', $UID['h.bakker'], backdate(30, 1)]);
    }
}
say('Budgets: ' . count($spend) . ' departments with allocation and reconciled spend');

// ---------------------------------------------------------------- notifications
// Unread items for the manager account so the nav bell shows a live badge on login.
$nt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link, severity, is_read, created_at)
                     VALUES (?,?,?,?,?,?,?)");
$lowParts = $pdo->query("SELECT part_name, stock_level, minimum_threshold FROM inventory_parts
                         WHERE stock_level <= minimum_threshold ORDER BY stock_level ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$pendingPo = (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders WHERE status='Pending Approval'")->fetchColumn();
$missedWo  = (int)$pdo->query("SELECT COUNT(*) FROM work_orders WHERE status='Missed'")->fetchColumn();

$feed = [];
foreach ($lowParts as $p) {
    $feed[] = ['inventory', "Low stock: {$p['part_name']} at {$p['stock_level']} (minimum {$p['minimum_threshold']})",
               '/_logi/inventory.php', $p['stock_level'] == 0 ? 'danger' : 'warning', 0];
}
$feed[] = ['procurement', "{$pendingPo} purchase requests are waiting for your approval", '/_logi/purchase_orders.php', 'warning', 0];
$feed[] = ['work_order',  "{$missedWo} work orders are past their scheduled date",         '/_maint/work_orders.php',   'danger',  0];
$feed[] = ['ticket',      'New critical event registered on Okuma LB3000 EX II Lathe',     '/_maint/active_tickets.php','danger',  0];
$feed[] = ['pm',          '2 PM schedules are overdue and need rescheduling',              '/_maint/pm_calendar.php',   'warning', 0];
$feed[] = ['procurement', 'PO fully received and checked into stores',                     '/_logi/purchase_orders.php','info',    1];
$feed[] = ['ticket',      'Event closed: coolant pressure restored on CNC Cell 1',         '/_rpt/history.php',         'info',    1];
$feed[] = ['system',      'Nightly backup completed successfully',                         '/_mgmt/admin_backup.php',   'info',    1];

$nNotif = 0;
foreach ([$ADMIN, $UID['p.nair'], $UID['h.bakker']] as $recipient) {
    foreach ($feed as $k => $f) {
        // Storekeeper only cares about stock and procurement.
        if ($recipient === $UID['h.bakker'] && !in_array($f[0], ['inventory', 'procurement'], true)) continue;
        $nt->execute([$recipient, $f[0], $f[1], $f[2], $f[3], $f[4], backdate(6, 0)]);
        $nNotif++;
    }
}
$unread = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id={$ADMIN} AND is_read=0")->fetchColumn();
say("Notifications: {$nNotif} seeded ({$unread} unread for the manager account)");

// ---------------------------------------------------------------- audit trail
// Column names mirror inc/audit.php exactly (actor_user_id, not user_id).
$aud = $pdo->prepare("INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, notes, created_at)
                      VALUES (?,?,?,?,?,?)");
$auditable = [
    ['equipment.create', 'equipment', 'Added asset to the register'],
    ['equipment.update', 'equipment', 'Updated PM interval'],
    ['user.create',      'users',     'Created technician account'],
    ['po.approve',       'purchase_orders', 'Approved purchase request'],
    ['po.receive',       'purchase_orders', 'Goods receipt recorded'],
    ['settings.update',  'app_settings',    'Changed SLA target'],
    ['inventory.adjust', 'inventory_parts', 'Stock count correction'],
];
$hasAudit = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.tables
                               WHERE table_schema=DATABASE() AND table_name='audit_log'")->fetchColumn();
$nAudit = 0;
if ($hasAudit) {
    try {
        for ($i = 0; $i < 40; $i++) {
            $a = $auditable[$i % count($auditable)];
            $aud->execute([pick([$ADMIN, $UID['p.nair'], $UID['h.bakker']]), $a[0], $a[1],
                           (string)mt_rand(1, 30), $a[2], backdate(180, 0)]);
            $nAudit++;
        }
    } catch (Throwable $e) {
        say('  (audit_log columns differ - skipped: ' . $e->getMessage() . ')');
    }
}
say("Audit log: {$nAudit} entries");

// ---------------------------------------------------------------- KPI showcase
// Hold-then-resume closed tickets (so Ghost/On-Hold time is non-zero) and a few
// non-failure event classes (so MTBF-by-failure and the population toggle differ).
// Runs last: it reclassifies some of the tickets seeded above.
require_once __DIR__ . '/ghost_events.php';
$ghostRes = wcc_demo_apply_ghost_events($pdo);
say("KPI showcase: {$ghostRes['ghost_tickets']} hold/resume tickets, {$ghostRes['reclassified']} reclassified (inspection/no-fault/request)");

// ---------------------------------------------------------------- summary
say("\n--- DEMO READY -------------------------------------------");
foreach (['users','equipment','inventory_parts','active_tickets','ticket_actions','work_orders',
          'pm_schedules','pm_checklists','purchase_orders','po_items','po_status_logs',
          'inventory_ledger','notifications','vendors_suppliers','departments'] as $t) {
    printf("  %-22s %5d\n", $t, (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn());
}
say('');
say('  Sign in with any of these (all share the same password):');
say('    a.rivera     Admin          Alex Rivera        - full access, notification bell loaded');
say('    p.nair       Supervisor     Priya Nair         - approvals, closeout, statistics');
say('    j.okafor     Technician     Jide Okafor        - takeover, work orders');
say('    r.silva      Operator       Rui Silva          - register events only');
say('    h.bakker     Storekeeper    Hendrik Bakker     - PO fulfilment, stores');
say('    c.whitfield  Custom Viewer  Claire Whitfield   - read-only');
say('    password:    ' . DEMO_PASSWORD);
say('');
say(sprintf('  Completed in %.1fs', microtime(true) - $t0));
say('----------------------------------------------------------');

