<?php
/**
 * AI Context Layer - REST Endpoint
 * 
 * Provides machine-readable context for AI agents.
 * 
 * Endpoints:
 *   GET /api/v1/ai-context                  -> Full context summary + links
 *   GET /api/v1/ai-context?section=overview -> Specific section (markdown)
 *   GET /api/v1/ai-context?live=1           -> Include live (safe) data
 */

function handle_ai_context($method, $id, $input) {
    global $pdo;

    if ($method !== 'GET') {
        api_error('Only GET supported for AI context', 405);
    }

    // No strict RBAC here - this is intentionally public-ish for agents
    // but we can still require a basic key in future if wanted.
    // For now, open so agents can bootstrap.

    $section = $_GET['section'] ?? null;
    $includeLive = isset($_GET['live']) && $_GET['live'] !== '0';

    $root = dirname(dirname(dirname(__DIR__)));
    $ctxtDir = $root . '/_ai_ctxt';

    $manifest = [];
    if (file_exists($ctxtDir . '/manifest.json')) {
        $manifest = json_decode(file_get_contents($ctxtDir . '/manifest.json'), true) ?: [];
    }

    if ($section) {
        $file = $ctxtDir . '/' . $section . '.md';
        if (!file_exists($file)) {
            // Try common names
            $file = $ctxtDir . '/' . strtoupper($section) . '.md';
        }
        if (!file_exists($file)) {
            api_error('Section not found', 404);
        }
        $content = file_get_contents($file);
        api_response(true, [
            'section' => $section,
            'content' => $content
        ]);
    }

    // Full context
    $response = [
        'project' => 'WCC CMMS',
        'version' => $manifest['version'] ?? 'unknown',
        'last_updated' => $manifest['last_updated'] ?? null,
        'instructions' => 'Read _ai_ctxt/AGENT_INSTRUCTIONS.md first. All context files are in _ai_ctxt/',
        'sections' => []
    ];

    $sections = ['OVERVIEW', 'ARCHITECTURE', 'DATA_MODEL', 'KEY_FLOWS', 'REST_API', 'CONVENTIONS'];
    foreach ($sections as $s) {
        $file = $ctxtDir . '/' . $s . '.md';
        if (file_exists($file)) {
            $response['sections'][$s] = [
                'path' => '_ai_ctxt/' . $s . '.md',
                'size' => filesize($file)
            ];
        }
    }

    // Always include machine-readable context
    $jsonFile = $ctxtDir . '/context.json';
    if (file_exists($jsonFile)) {
        $response['context_json'] = [
            'path' => '_ai_ctxt/context.json',
            'content' => json_decode(file_get_contents($jsonFile), true)
        ];
    }

    if ($includeLive) {
        $response['live_data'] = get_safe_live_context($pdo);
    }

    api_response(true, $response);
}

function get_safe_live_context($pdo) {
    $live = [];

    // Safe row counts (no sensitive data)
    $tables = [
        'users' => 'SELECT COUNT(*) FROM users',
        'equipment' => 'SELECT COUNT(*) FROM equipment',
        'active_tickets' => 'SELECT COUNT(*) FROM active_tickets WHERE status IN ("OPEN","PENDING")',
        'work_orders' => 'SELECT COUNT(*) FROM work_orders',
        'inventory_parts' => 'SELECT COUNT(*) FROM inventory_parts',
        'purchase_orders' => 'SELECT COUNT(*) FROM purchase_orders',
    ];

    foreach ($tables as $name => $sql) {
        try {
            $stmt = $pdo->query($sql);
            $live['counts'][$name] = (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            $live['counts'][$name] = 'error';
        }
    }

    // Very safe sample data (limited rows, no passwords, emails masked if needed)
    try {
        $stmt = $pdo->query("SELECT equip_id, equip_name, category, is_active FROM equipment ORDER BY equip_id DESC LIMIT 3");
        $live['sample_equipment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM active_tickets GROUP BY status");
        $live['ticket_status_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    $live['note'] = 'Live data is intentionally limited and anonymized for AI consumption.';

    return $live;
}
