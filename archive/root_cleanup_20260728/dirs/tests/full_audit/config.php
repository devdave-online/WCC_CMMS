<?php
/**
 * Full-audit configuration.
 * Override with env: WCC_QA_BASE, WCC_QA_USER, WCC_QA_PASS
 * Or create tests/full_audit/config.local.php (gitignored pattern optional).
 */
return [
    'base_url'   => rtrim(getenv('WCC_QA_BASE') ?: 'http://127.0.0.1', '/'),
    // Demo admin from KEY_FLOWS / demo seed; override via env
    'admin_user' => getenv('WCC_QA_USER') ?: 'a.rivera',
    'admin_pass' => getenv('WCC_QA_PASS') ?: 'Demo2026!',
    // Optional second role for 403 expectations
    'viewer_user' => getenv('WCC_QA_VIEWER_USER') ?: 'c.whitfield',
    'viewer_pass' => getenv('WCC_QA_VIEWER_PASS') ?: 'Demo2026!',
    'timeout'    => 20,
    'tag'        => '[QA-AUDIT]',
    // CLI flags override these defaults in run.php
    'mutate'     => false,
    'cleanup'    => true,
];
