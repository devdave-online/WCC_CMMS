<?php
/**
 * AI Agent Initialization Summary Printer
 * 
 * Run: php _ai_ctxt/print-init-summary.php
 * 
 * This script outputs a clean, copy-paste friendly summary of the project
 * initialization. Useful when starting a new agent session or when
 * multiple agents/windows need to sync their understanding.
 * 
 * It reads ai_agent.ini and the context layer to produce a concise briefing.
 */

$root = dirname(__DIR__);
$iniFile = $root . '/ai_agent.ini';
$ctxtDir = $root . '/_ai_ctxt';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  WCC CMMS - AI AGENT INITIALIZATION SUMMARY\n";
echo "  (Generated: " . date('Y-m-d H:i:s') . ")\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (!file_exists($iniFile)) {
    echo "ERROR: ai_agent.ini not found at project root.\n";
    exit(1);
}

// RAW scanner: values are prose (parentheses, arrows, quotes) — typed mode
// treats those as reserved chars and breaks parsing.
$ini = parse_ini_file($iniFile, true, INI_SCANNER_RAW);

echo "PROJECT\n";
echo "-------\n";
echo "Name:        " . ($ini['project']['name'] ?? 'WCC CMMS') . "\n";
echo "Version:     " . ($ini['project']['version'] ?? 'OB1.0.0') . "\n";
echo "Release:     " . ($ini['project']['release_type'] ?? 'Open Beta') . "\n";
echo "Deploy:      " . ($ini['project']['deployment_model'] ?? 'offline one-site') . "\n";
echo "Hardlocks:   " . ($ini['project']['hardlocks'] ?? 'NONE') . "\n";
echo "Description: " . ($ini['project']['description'] ?? '') . "\n\n";

echo "MANDATORY READING (in this exact order)\n";
echo "---------------------------------------\n";
if (!empty($ini['initialization']['must_read_first'])) {
    $files = explode(',', $ini['initialization']['must_read_first']);
    foreach ($files as $i => $f) {
        echo "  " . ($i+1) . ". " . trim($f) . "\n";
    }
}
echo "\n";

echo "CONTEXT LAYER\n";
echo "-------------\n";
echo "Folder:      _ai_ctxt/\n";
echo "PO status:   _ai_ctxt/PRODUCT_STATUS.md\n";
echo "Machine-readable: _ai_ctxt/context.json\n";
echo "Generator:   php _ai_ctxt/generate-context.php [--live]\n";
echo "\n";

echo "DYNAMIC CONTEXT (via REST API)\n";
echo "------------------------------\n";
echo "Base:        /api/v1/\n";
echo "Endpoint:    /ai-context\n";
echo "Toolings:    /api/v1/toolings (+ /bom, /documents)\n";
echo "Companion:   /api/companion/* (separate hive — do not break)\n";
echo "Examples:\n";
echo "  GET /api/v1/ai-context\n";
echo "  GET /api/v1/ai-context?section=PRODUCT_STATUS\n";
echo "  GET /api/v1/ai-context?live=1\n";
echo "  GET /api/v1/toolings?search=die\n";
echo "\n";

echo "KEY RULES FOR ALL AGENTS\n";
echo "------------------------\n";
echo "• Always load ai_agent.ini + PRODUCT_STATUS.md at session start.\n";
echo "• Offline open beta: one install per site; NO hardlocks / license kills.\n";
echo "• Use root-absolute paths for links and assets (/_maint/, /_eam/, etc.).\n";
echo "• Respect the _module/ folder structure; raw PHP only.\n";
echo "• Tooling RBAC is independent of equipment (view_toolings / manage_toolings).\n";
echo "• Companion /api/companion/* is a separate package contract.\n";
echo "• English is the i18n reference; no high-impact language tiers.\n";
echo "• Version stamps: version.json + WCC_UI_VERSION = OB1.0.0.\n";
echo "• Run generator after schema changes; re-run REST tests after API changes.\n";
echo "\n";

echo "MULTI-AGENT / MULTI-WINDOW SYNC\n";
echo "--------------------------------\n";
echo "1. Every agent window loads the same ai_agent.ini from the project root.\n";
echo "2. When one window makes changes, remind others to re-read:\n";
echo "   - Updated _ai_ctxt/ files\n";
echo "   - Run generator if schema touched\n";
echo "   - Re-load ai_agent.ini\n";
echo "\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "  END OF INITIALIZATION SUMMARY\n";
echo "  Full details: _ai_ctxt/AGENT_INSTRUCTIONS.md\n";
echo "═══════════════════════════════════════════════════════════════\n";
