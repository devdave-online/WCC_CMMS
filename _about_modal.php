<?php
$version_data = json_decode(file_get_contents(__DIR__ . '/version.json'), true);
$version = $version_data['version'] ?? 'v1.0.0';
$codename = $version_data['codename'] ?? 'Unknown';

// Public contact (bug → email toast+mailto; chat → LinkedIn toast+new tab).
$wcc_contact_linkedin = 'https://www.linkedin.com/in/david-csiki/';
$wcc_contact_bug_email = 'david.csiki@gmail.com';
$wcc_contact_author = 'David Zoltan Csiki';

// =============================================================================
// SUPPORT / DONATION LINKS  ←  PASTE YOUR LIVE URLs HERE
// File: C:\xampp\htdocs\_about_modal.php  (this block only)
//
// Used by the About → Contact → "Fuel the next late-night build?" accordion.
// Both links open in a NEW TAB. QR codes are generated client-side from these
// same URLs (bwip-js) so phones can open them without typing.
//
// Examples:
//   $wcc_support_revolut_url = 'https://revolut.me/yourname';
//   $wcc_support_kofi_url    = 'https://ko-fi.com/yourname';
// Leave empty to show a "not configured" hint instead of a broken link.
// =============================================================================
$wcc_support_revolut_url = 'https://revolut.me/cszd'; // Revolut.me
$wcc_support_kofi_url    = 'https://ko-fi.com/cszd';  // Ko-fi page
$wcc_support_snooze_days = 30; // days for button 2 — applied only AFTER a pay link is clicked

// Per-user visibility of the coffee accordion (donation_prompt_prefs table).
$wcc_support_show = true;
$__uid = (int)($_SESSION['user_id'] ?? 0);
if ($__uid > 0) {
    try {
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            if (!function_exists('get_wcc_db_connection')) {
                require_once __DIR__ . '/inc/db.php';
            }
            $__pdo_support = get_wcc_db_connection();
        } else {
            $__pdo_support = $pdo;
        }
        $__st = $__pdo_support->prepare(
            'SELECT status, snooze_until FROM donation_prompt_prefs WHERE user_id = ? LIMIT 1'
        );
        $__st->execute([$__uid]);
        $__row = $__st->fetch(PDO::FETCH_ASSOC);
        if ($__row) {
            if (($__row['status'] ?? '') === 'dismissed') {
                $wcc_support_show = false;
            } elseif (($__row['status'] ?? '') === 'snoozed' && !empty($__row['snooze_until'])) {
                $__ts = strtotime((string)$__row['snooze_until']);
                if ($__ts !== false && $__ts > time()) {
                    $wcc_support_show = false;
                }
            }
        }
    } catch (Throwable $e) {
        $wcc_support_show = true; // table missing → still show
    }
}
if (!function_exists('wcc_csrf_token')) {
    require_once __DIR__ . '/inc/csrf.php';
}
$wcc_support_csrf = wcc_csrf_token();
?>
<style>
    /* About Modal Animations & Styling */
    @keyframes sweepDown {
        0% { opacity: 0; transform: translateY(-10px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    
    .wcc-about-overlay {
        display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; 
        background-color: rgba(0,0,0,0.4); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
    }
    .wcc-about-content {
        position: relative; border: 1px solid rgba(255,255,255,0.1); 
        background: rgba(15, 23, 42, 0.85); /* Deep slate with opacity */
        backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px); 
        width: 1000px; max-width: 95%; max-height: 90vh; overflow-y: auto; 
        padding: 40px; margin: 5vh auto; border-radius: 16px; 
        box-shadow: 0 20px 60px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.1);
        color: var(--text-primary); text-align: left;
    }
    .light-theme .wcc-about-content {
        background: rgba(255, 255, 255, 0.85); border: 1px solid rgba(0,0,0,0.1);
        box-shadow: 0 20px 60px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.5);
    }
    
    .wcc-close-btn {
        position: absolute; right: 20px; top: 20px; font-size: 28px; font-weight: bold; 
        cursor: pointer; color: var(--text-secondary); transition: all 0.2s;
    }
    .wcc-close-btn:hover { color: #ef4444; transform: scale(1.2) rotate(90deg); }

    .tech-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px;
        background: rgba(0,0,0,0.2); border: 1px solid var(--panel-border); border-radius: 20px;
        font-family: monospace; font-size: 0.85em; font-weight: 600; color: var(--text-accent);
        transition: all 0.2s; cursor: default;
    }
    .tech-badge:hover { background: var(--text-accent); color: white; border-color: var(--text-accent); transform: translateY(-2px); }

    .privacy-notice {
        background: rgba(139, 92, 246, 0.14); border-left: 4px solid #8b5cf6;
        padding: 15px; margin: 16px 0 12px; border-radius: 4px 8px 8px 4px; font-size: 0.95em;
        color: var(--text-secondary); line-height: 1.55;
    }
    .privacy-notice strong { color: var(--text-primary); }
    .license-notice {
        background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; 
        padding: 15px; margin: 12px 0; border-radius: 4px 8px 8px 4px; font-size: 0.95em;
        color: var(--text-secondary); line-height: 1.55;
    }
    .license-notice strong { color: var(--text-primary); }
    .beta-notice {
        background: rgba(245, 158, 11, 0.12); border-left: 4px solid #f59e0b;
        padding: 15px; margin: 12px 0 20px; border-radius: 4px 8px 8px 4px; font-size: 0.95em;
        color: var(--text-secondary); line-height: 1.55;
    }
    .beta-notice strong { color: var(--text-primary); }

    .changelog-box {
        background: rgba(0,0,0,0.15); border: 1px solid var(--panel-border);
        border-radius: 12px; padding: 20px; margin-bottom: 25px;
    }
    
    .hover-lift { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; }
    .hover-lift:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 10px 25px rgba(0,0,0,0.3); border-color: var(--text-accent) !important; }

    /* Animated Accordions */
    .about-feature-details {
        background: rgba(0,0,0,0.2); border: 1px solid var(--panel-border); border-radius: 12px;
        margin-bottom: 12px; overflow: hidden; transition: border-color 0.2s;
    }
    .about-feature-details:hover { border-color: var(--text-accent); }
    .about-feature-summary {
        padding: 15px 20px; font-size: 1.1em; font-weight: 700; cursor: pointer; 
        list-style: none; display: flex; align-items: center; gap: 15px;
        color: var(--text-primary); transition: background 0.2s;
    }
    .about-feature-summary::-webkit-details-marker { display: none; }
    .about-feature-summary:hover { background: rgba(255,255,255,0.05); }
    .about-feature-content {
        padding: 0 20px 20px 20px; color: var(--text-secondary); font-size: 0.95em; line-height: 1.6;
    }
    .about-feature-details[open] .about-feature-content { animation: sweepDown 0.4s ease-in-out forwards; }

    .action-btn {
        display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px;
        background: var(--text-accent); color: white; text-decoration: none;
        border-radius: 8px; font-size: 0.9em; font-weight: 600; margin-top: 12px;
        transition: all 0.2s; border: none; cursor: pointer;
    }
    .action-btn:hover { filter: brightness(1.2); transform: translateX(5px); }

    /* Feedback / contact strip */
    .about-feedback-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        padding: 8px 0 0;
    }
    @media (max-width: 720px) {
        .about-feedback-grid { grid-template-columns: 1fr; }
    }
    .about-feedback-card {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 22px 20px;
        background: rgba(255,255,255,0.02);
        border: 1px solid var(--panel-border);
        border-radius: 16px;
        min-height: 150px;
    }
    .about-feedback-card .about-feedback-emoji {
        font-size: 1.8em;
        line-height: 1;
    }
    .about-feedback-card h5 {
        margin: 0;
        font-size: 1.15em;
        font-weight: 800;
        color: var(--text-primary);
    }
    .about-feedback-card p {
        margin: 0;
        flex: 1;
        font-size: 0.92em;
        line-height: 1.5;
        color: var(--text-secondary);
    }
    .about-feedback-card .action-btn {
        align-self: flex-start;
        margin-top: 4px;
        cursor: pointer;
        position: relative;
        z-index: 2;
        pointer-events: auto;
        text-decoration: none;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .about-email-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 4px 0 2px;
        padding: 10px 12px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--panel-border);
        border-radius: 10px;
    }
    .about-email-row img {
        width: 28px;
        height: 28px;
        flex-shrink: 0;
        border-radius: 4px;
    }
    .about-email-addr {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.95em;
        font-weight: 700;
        color: var(--text-accent);
        user-select: all;
        word-break: break-all;
        line-height: 1.35;
    }
    .about-email-hint {
        font-size: 0.8em;
        color: var(--text-muted);
        margin: 0 0 6px;
    }

    /* Support / coffee accordion + payment overlay */
    .about-support-wrap {
        margin-top: 22px;
    }
    .about-support-wrap .about-feature-details {
        border-radius: 16px;
    }
    .about-support-wrap .about-feature-summary {
        padding: 22px 26px;
        font-size: 1.35em;
        font-weight: 800;
        gap: 16px;
        letter-spacing: -0.02em;
        min-height: 3.25em;
    }
    .about-support-wrap .about-feature-summary .about-support-emoji {
        font-size: 1.45em;
        line-height: 1;
    }
    .about-support-wrap .about-feature-content {
        padding: 8px 26px 26px 26px;
        font-size: 1.05em;
        line-height: 1.65;
    }
    .about-support-wrap .about-support-body {
        margin: 0 0 8px;
        font-size: 1.05em;
        line-height: 1.6;
        color: var(--text-secondary);
    }
    .about-support-actions {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 20px;
        align-items: stretch;
    }
    .about-support-actions .about-support-btn {
        flex: 1 1 0;
        min-width: 0;
        margin-top: 0;
        justify-content: center;
        text-align: center;
        width: auto;
        box-sizing: border-box;
        white-space: normal;
        line-height: 1.4;
        padding: 18px 16px;
        min-height: 72px;
        font-size: 1.05em;
        border-radius: 14px;
        /* slight glassmorphism */
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.12);
        color: var(--text-primary);
        font-weight: 700;
    }
    .about-support-actions .about-support-btn:hover {
        background: rgba(255, 255, 255, 0.14);
        border-color: rgba(255, 255, 255, 0.28);
        filter: none;
        transform: translateY(-2px);
    }
    .about-support-actions .about-support-btn-coffee {
        background: rgba(167, 139, 250, 0.18);
        border-color: rgba(167, 139, 250, 0.35);
    }
    .about-support-actions .about-support-btn-snooze {
        background: rgba(16, 185, 129, 0.16);
        border-color: rgba(16, 185, 129, 0.32);
    }
    .about-support-actions .about-support-btn-dismiss {
        background: rgba(148, 163, 184, 0.14);
        border-color: rgba(148, 163, 184, 0.28);
    }
    .light-theme .about-support-actions .about-support-btn {
        background: rgba(255, 255, 255, 0.45);
        border-color: rgba(0, 0, 0, 0.08);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.7);
        color: var(--text-primary);
    }
    .light-theme .about-support-actions .about-support-btn-coffee {
        background: rgba(139, 92, 246, 0.12);
        border-color: rgba(139, 92, 246, 0.25);
    }
    .light-theme .about-support-actions .about-support-btn-snooze {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.25);
    }
    .light-theme .about-support-actions .about-support-btn-dismiss {
        background: rgba(100, 116, 139, 0.1);
        border-color: rgba(100, 116, 139, 0.2);
    }
    @media (max-width: 720px) {
        .about-support-wrap .about-feature-summary {
            padding: 18px 18px;
            font-size: 1.2em;
        }
        .about-support-wrap .about-feature-content {
            padding: 6px 18px 20px 18px;
        }
        .about-support-actions { flex-direction: column; gap: 12px; }
        .about-support-actions .about-support-btn {
            width: 100%;
            min-height: 64px;
            padding: 16px 14px;
        }
    }
    .wcc-support-overlay {
        display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.55); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
    }
    .wcc-support-overlay.open { display: block; }
    .wcc-support-panel {
        position: relative; width: 760px; max-width: 94%; max-height: 90vh; overflow-y: auto;
        margin: 5vh auto; padding: 32px 32px 26px;
        border-radius: 18px; border: 1px solid rgba(255,255,255,0.12);
        background: rgba(15, 23, 42, 0.92); color: var(--text-primary);
        box-shadow: 0 24px 60px rgba(0,0,0,0.55);
    }
    .light-theme .wcc-support-panel {
        background: rgba(255,255,255,0.95); border-color: rgba(0,0,0,0.1);
    }
    /* Twin columns: equal width, equal height, mirrored structure */
    .wcc-support-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin: 22px 0 16px;
        align-items: stretch;
    }
    @media (max-width: 640px) {
        .wcc-support-grid { grid-template-columns: 1fr; }
    }
    .wcc-support-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        height: 100%;
        min-height: 100%;
        padding: 22px 18px 20px;
        border-radius: 16px;
        border: 1px solid var(--panel-border);
        background: rgba(255,255,255,0.03);
        box-sizing: border-box;
    }
    .wcc-support-col h5 {
        margin: 0;
        font-size: 1.2em;
        font-weight: 800;
        line-height: 1.25;
        min-height: 1.5em;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .wcc-support-col .wcc-support-blurb {
        margin: 10px 0 0;
        font-size: 0.9em;
        color: var(--text-secondary);
        line-height: 1.45;
        min-height: 2.9em; /* equal copy blocks so QRs line up */
        max-width: 16em;
    }
    /* Fixed square “plate” so both QRs share the same visual weight */
    .wcc-support-qr-block {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin: 18px 0 16px;
        flex: 0 0 auto;
        width: 100%;
    }
    .wcc-support-qr-frame {
        width: 176px;
        height: 176px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow:
            0 1px 0 rgba(255,255,255,0.9) inset,
            0 10px 28px rgba(0,0,0,0.18);
    }
    .wcc-support-qr-frame canvas {
        display: block;
        width: 160px !important;
        height: 160px !important;
        max-width: 160px;
        max-height: 160px;
        margin: 0;
        image-rendering: pixelated;
        image-rendering: crisp-edges;
    }
    .wcc-support-qr-block .qr-cap {
        font-size: 0.72em;
        font-weight: 700;
        color: var(--text-muted, #94a3b8);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        line-height: 1;
    }
    .light-theme .wcc-support-qr-block .qr-cap { color: #64748b; }
    .wcc-support-col .wcc-support-pay-link,
    .wcc-support-col .wcc-support-missing {
        margin-top: auto; /* pin CTAs to same baseline across columns */
        width: 100%;
        max-width: 210px;
        justify-content: center;
        box-sizing: border-box;
    }
    .wcc-support-col .wcc-support-pay-link {
        padding: 10px 16px;
        border-radius: 10px;
    }
    .wcc-support-joke {
        margin: 8px 0 0; padding: 12px 14px; border-radius: 10px;
        background: rgba(245, 158, 11, 0.12); border-left: 4px solid #f59e0b;
        font-size: 0.9em; color: var(--text-secondary); line-height: 1.45; font-style: italic;
    }
    .wcc-support-missing {
        font-size: 0.82em; color: #f59e0b; margin: 0;
    }
    .wcc-support-snooze-hint {
        margin: 10px 0 0;
        padding: 9px 12px;
        border-radius: 10px;
        font-size: 0.82em;
        line-height: 1.3;
        color: var(--text-secondary);
        background: rgba(16, 185, 129, 0.12);
        border-left: 4px solid #10b981;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    @media (max-width: 640px) {
        .wcc-support-snooze-hint {
            white-space: normal; /* narrow screens may need two lines */
            font-size: 0.8em;
        }
    }

</style>
<script src="/js/bwip-js-min.js"></script>

<!-- About WCC Welcome Modal Overlay -->
<div id="wccAboutModal" class="wcc-about-overlay" onclick="event.target === this && closeAboutModal()">
    <div class="wcc-about-content">
        <span class="wcc-close-btn" onclick="closeAboutModal()">&times;</span>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h3 style="color: var(--text-accent); margin: 0 0 5px 0; font-size: 2em; font-weight: 800; letter-spacing: -0.5px; display: flex; align-items: center; gap: 12px;">
                    <img src="/img/wcc-orb.png" alt="" class="wcc-about-mark" width="44" height="44" decoding="async">
                    <?= __e('app.name') ?>
                </h3>
                <div style="font-size: 0.9em; color: var(--text-secondary); font-weight: 700; letter-spacing: 2px;"><?= __e('about.core_arch') ?></div>

                <!-- Entry point to the full manual. Opens in a new tab so the reader
                     keeps their place in the app behind the modal. -->
                <a href="/docs.php" target="_blank" rel="noopener"
                   class="pill-btn pill-info"
                   style="margin-top: 14px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                    📚 <?= __e('about.docs') ?>
                    <span aria-hidden="true" style="opacity:.7; font-size:.9em;">↗</span>
                </a>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; max-width: 300px;">
                <span class="tech-badge">🐘 PHP 8.X</span>
                <span class="tech-badge">🗄️ MySQL PDO</span>
                <span class="tech-badge">⚡ Vanilla JS</span>
            </div>
        </div>
        
        <p style="line-height: 1.7; font-size: 1.05em; margin: 0 0 20px 0;">
            <?= __e('about.welcome') ?>
        </p>

        <?php /* Order: privacy first (trust), then license, then beta — max impact for plant users */ ?>
        <div class="privacy-notice">
            <strong>🏠 <?= __e('about.privacy_title') ?></strong><br>
            <?= __e('about.privacy_body') ?>
        </div>

        <div class="license-notice">
            <strong>🔓 <?= __e('about.license_title') ?></strong><br>
            <?= __e('about.license_body') ?>
            <a href="LICENSE.txt" target="_blank" style="color:#10b981; font-weight:bold;"><?= __e('about.view_license') ?></a>.
        </div>

        <div class="beta-notice">
            <strong>🧪 <?= __e('about.beta_title') ?></strong><br>
            <?= __e('about.beta_body') ?>
        </div>

        <div class="changelog-box">
            <h4 style="margin: 0 0 10px 0; color: var(--text-accent); font-size: 1.2em;">🏁 <?= __e('about.whats_in', ['version' => $version, 'codename' => $codename]) ?></h4>
            <p style="margin: 0 0 12px 0; color: var(--text-secondary); line-height: 1.6;">
                <?= __e('about.release_intro') ?>
            </p>
            <ul style="margin: 0; padding-left: 20px; color: var(--text-secondary); line-height: 1.6;">
                <li><?= __e('about.changelog.tickets') ?></li>
                <li><?= __e('about.changelog.wo') ?></li>
                <li><?= __e('about.changelog.assets') ?></li>
                <li><?= __e('about.changelog.inventory') ?></li>
                <li><?= __e('about.changelog.procurement') ?></li>
                <li><?= __e('about.changelog.reorder') ?></li>
                <li><?= __e('about.changelog.rbac') ?></li>
                <li><?= __e('about.changelog.notif') ?></li>
                <li><?= __e('about.changelog.design') ?></li>
                <li><?= __e('about.changelog.ux') ?></li>
                <li><?= __e('about.changelog.security') ?></li>
                <li><?= __e('about.changelog.api') ?></li>
            </ul>
        </div>

        <h4 style="margin: 30px 0 15px 0; font-size: 1.3em; border-bottom: 1px solid var(--panel-border); padding-bottom: 10px;"><?= __e('about.modules') ?></h4>
        
        <!-- Feature Accordion Grid -->
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 40px;">
            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🎫</span> <?= __e('about.feat.tickets') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.tickets_body') ?>
                    <br>
                    <?php if(can('create_tickets')): ?><a href="/register.php" class="action-btn"><?= __e('about.btn.log_intervention') ?> ➡️</a><?php endif; ?>
                    <a href="/_maint/active_tickets.php" class="action-btn" style="background: rgba(255,255,255,0.1);"><?= __e('about.btn.view_board') ?> ➡️</a>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">⚙️</span> <?= __e('about.feat.assets') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.assets_body') ?>
                    <br>
                    <?php if(can('view_equipment')): ?><a href="/_eam/equipment_list.php" class="action-btn"><?= __e('about.btn.browse_assets') ?> ➡️</a><?php endif; ?>
                    <?php if(can('manage_equipment')): ?><a href="/_eam/setup_vault_equipment.php" class="action-btn" style="background: rgba(255,255,255,0.1);"><?= __e('about.btn.vault_config') ?> ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">📦</span> <?= __e('about.feat.inventory') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.inventory_body') ?>
                    <br>
                    <?php if(can('view_inventory')): ?><a href="/_logi/inventory.php" class="action-btn"><?= __e('about.btn.search_parts') ?> ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🛒</span> <?= __e('about.feat.procurement') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.procurement_body') ?>
                    <br>
                    <?php if(can('view_purchase_requests')): ?><a href="/_logi/purchase_requests.php" class="action-btn"><?= __e('about.btn.view_prs') ?> ➡️</a><?php endif; ?>
                    <?php if(can('approve_purchase_orders')): ?><a href="/_logi/purchase_orders.php" class="action-btn" style="background: rgba(255,255,255,0.1);"><?= __e('about.btn.view_pos') ?> ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">📊</span> <?= __e('about.feat.analytics') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.analytics_body') ?>
                    <br>
                    <?php if(can('view_statistics')): ?><a href="/_rpt/setup_vault_analytics.php" class="action-btn"><?= __e('about.btn.diagnostics') ?> ➡️</a><?php endif; ?>
                    <a href="/docs/USER_GUIDE.md" class="action-btn" style="background: rgba(255,255,255,0.1);"><?= __e('about.btn.user_guide') ?> ➡️</a>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🏭</span> <?= __e('about.feat.lines') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.lines_body') ?>
                    <br>
                    <?php if(can('manage_equipment')): ?><a href="/_prod/setup_vault_lines.php" class="action-btn"><?= __e('about.btn.manage_lines') ?> ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🧩</span> <?= __e('about.feat.modular') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.modular_body') ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🗓️</span> <?= __e('about.feat.pm') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.pm_body') ?>
                    <br>
                    <?php if(can('view_work_orders')): ?><a href="/_maint/work_orders.php" class="action-btn"><?= __e('about.btn.work_orders') ?> ➡️</a><?php endif; ?>
                    <a href="/_maint/pm_calendar.php" class="action-btn" style="background: rgba(255,255,255,0.1);"><?= __e('about.btn.pm_calendar') ?> ➡️</a>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">📁</span> <?= __e('about.feat.docs') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.docs_body') ?>
                    <br>
                    <?php if(can('manage_users')): ?><a href="/_mgmt/admin_panel.php" class="action-btn"><?= __e('about.btn.admin_panel') ?> ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🛡️</span> <?= __e('about.feat.rbac') ?></summary>
                <div class="about-feature-content">
                    <?= __e('about.feat.rbac_body') ?>
                </div>
            </details>
        </div>
        
        <!-- Feedback & contact — playful, service-first (not an ego plaque) -->
        <h4 style="margin: 0 0 15px 0; font-size: 1.3em; border-bottom: 1px solid var(--panel-border); padding-bottom: 10px;"><?= __e('about.feedback_title') ?></h4>
        <div style="padding: 22px 20px 24px; background: rgba(0,0,0,0.1); border-radius: 20px; border: 1px solid var(--panel-border);">
            <div class="about-feedback-grid">
                <div class="about-feedback-card hover-lift">
                    <span class="about-feedback-emoji" aria-hidden="true">🐛</span>
                    <h5><?= __e('about.bug_title') ?></h5>
                    <p><?= __e('about.bug_body') ?></p>
                    <p class="about-email-hint"><?= __e('about.email_visible_hint') ?></p>
                    <div class="about-email-row">
                        <img src="/img/gmail.svg" alt="Gmail" width="28" height="28">
                        <a class="about-email-addr"
                           href="mailto:<?= htmlspecialchars($wcc_contact_bug_email, ENT_QUOTES, 'UTF-8') ?>?subject=<?= rawurlencode('WCC bug report') ?>"
                           title="<?= htmlspecialchars(__('about.send_email'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($wcc_contact_bug_email, ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                    <?php
                    $gmailCompose = 'https://mail.google.com/mail/?view=cm&fs=1'
                        . '&to=' . rawurlencode($wcc_contact_bug_email)
                        . '&su=' . rawurlencode('WCC bug report')
                        . '&body=' . rawurlencode(
                            "What happened?\n\nSteps to reproduce:\n1.\n2.\n\nExpected:\n\nActual:\n\nWCC version: {$version}\n"
                        );
                    ?>
                    <a class="action-btn"
                       href="<?= htmlspecialchars($gmailCompose, ENT_QUOTES, 'UTF-8') ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       onclick="if(typeof showToast==='function'){showToast(<?= htmlspecialchars(json_encode(__('about.opening_gmail'), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>,'info',2200);}">
                        <img src="/img/gmail.svg" alt="" width="18" height="18" style="border-radius:3px;">
                        <?= __e('about.open_gmail') ?> ↗
                    </a>
                </div>
                <div class="about-feedback-card hover-lift">
                    <span class="about-feedback-emoji" aria-hidden="true">💬</span>
                    <h5><?= __e('about.chat_title') ?></h5>
                    <p><?= __e('about.chat_body') ?></p>
                    <a class="action-btn"
                       href="<?= htmlspecialchars($wcc_contact_linkedin, ENT_QUOTES, 'UTF-8') ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       onclick="if(typeof showToast==='function'){showToast(<?= htmlspecialchars(json_encode(__('about.opening_linkedin'), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>,'info',2200);}">
                        <?= __e('about.message_linkedin') ?> ↗
                    </a>
                </div>
            </div>

            <?php if ($wcc_support_show): ?>
            <!-- Coffee / support accordion (prefs: donation_prompt_prefs · API: /api/donation_prompt.php) -->
            <div class="about-support-wrap" id="wccSupportAccordionWrap">
                <details class="about-feature-details" id="wccSupportDetails">
                    <summary class="about-feature-summary">
                        <span class="about-support-emoji" aria-hidden="true">☕</span>
                        <?= __e('about.support.summary') ?>
                    </summary>
                    <div class="about-feature-content">
                        <p class="about-support-body"><?= __e('about.support.body') ?></p>
                        <div class="about-support-actions">
                            <button type="button" class="action-btn about-support-btn about-support-btn-coffee"
                                    id="wccSupportBtnCoffee"
                                    onclick="wccSupportChoose('coffee')">
                                🤩 <?= __e('about.support.btn_coffee') ?>
                            </button>
                            <button type="button" class="action-btn about-support-btn about-support-btn-snooze"
                                    id="wccSupportBtnSnooze"
                                    onclick="wccSupportChoose('coffee_snooze')">
                                😊 <?= __e('about.support.btn_coffee_snooze', ['days' => (int)$wcc_support_snooze_days]) ?>
                            </button>
                            <button type="button" class="action-btn about-support-btn about-support-btn-dismiss"
                                    id="wccSupportBtnDismiss"
                                    onclick="wccSupportChoose('no_coffee')">
                                😔 <?= __e('about.support.btn_no_coffee') ?>
                            </button>
                        </div>
                    </div>
                </details>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 40px; font-size: 0.8em; text-align: center; color: var(--text-secondary); font-weight: 700; font-family: monospace;">
            WCC <?= htmlspecialchars($codename) ?> <?= htmlspecialchars($version) ?> · <?= __e('about.made_by', ['name' => $wcc_contact_author]) ?>
        </div>
    </div>
</div>

<!-- Support payment overlay (Revolut | Ko-fi). Opened by buttons 1 & 2. Links: top of this file. -->
<div id="wccSupportModal" class="wcc-support-overlay" role="dialog" aria-modal="true"
     aria-labelledby="wccSupportModalTitle"
     onclick="if(event.target===this)closeWccSupportModal()">
    <div class="wcc-support-panel">
        <span class="wcc-close-btn" onclick="closeWccSupportModal()" title="<?= __e('about.support.close') ?>">&times;</span>
        <h3 id="wccSupportModalTitle" style="margin:0 0 8px; font-size:1.45em; font-weight:800; color:var(--text-accent);">
            ☕ <?= __e('about.support.modal_title') ?>
        </h3>
        <p style="margin:0; color:var(--text-secondary); font-size:0.95em; line-height:1.5;">
            <?= __e('about.support.modal_intro') ?>
        </p>
        <p id="wccSupportSnoozeHint" class="wcc-support-snooze-hint" hidden>
            <?= __e('about.support.snooze_pending_hint', ['days' => (int)$wcc_support_snooze_days]) ?>
        </p>

        <div class="wcc-support-grid">
            <div class="wcc-support-col">
                <h5><span aria-hidden="true">💳</span> <?= __e('about.support.revolut_title') ?></h5>
                <p class="wcc-support-blurb"><?= __e('about.support.revolut_blurb') ?></p>
                <div class="wcc-support-qr-block">
                    <div class="wcc-support-qr-frame">
                        <canvas id="wccSupportQrRevolut" width="160" height="160" aria-label="Revolut QR"></canvas>
                    </div>
                    <span class="qr-cap"><?= __e('about.support.qr_label') ?></span>
                </div>
                <?php if (trim((string)$wcc_support_revolut_url) !== ''): ?>
                <a class="action-btn wcc-support-pay-link"
                   id="wccSupportLinkRevolut"
                   href="<?= htmlspecialchars($wcc_support_revolut_url, ENT_QUOTES, 'UTF-8') ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-channel="revolut">
                    <?= __e('about.support.open_link') ?> ↗
                </a>
                <?php else: ?>
                <p class="wcc-support-missing"><?= __e('about.support.link_missing') ?></p>
                <?php endif; ?>
            </div>

            <div class="wcc-support-col">
                <h5><span aria-hidden="true">☕</span> <?= __e('about.support.kofi_title') ?></h5>
                <p class="wcc-support-blurb"><?= __e('about.support.kofi_blurb') ?></p>
                <div class="wcc-support-qr-block">
                    <div class="wcc-support-qr-frame">
                        <canvas id="wccSupportQrKofi" width="160" height="160" aria-label="Ko-fi QR"></canvas>
                    </div>
                    <span class="qr-cap"><?= __e('about.support.qr_label') ?></span>
                </div>
                <?php if (trim((string)$wcc_support_kofi_url) !== ''): ?>
                <a class="action-btn wcc-support-pay-link"
                   id="wccSupportLinkKofi"
                   href="<?= htmlspecialchars($wcc_support_kofi_url, ENT_QUOTES, 'UTF-8') ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-channel="kofi">
                    <?= __e('about.support.open_link') ?> ↗
                </a>
                <?php else: ?>
                <p class="wcc-support-missing"><?= __e('about.support.link_missing') ?></p>
                <?php endif; ?>
            </div>
        </div>

        <p class="wcc-support-joke"><?= __e('about.support.caffeine_joke') ?></p>
        <div style="margin-top:14px; text-align:right;">
            <button type="button" class="action-btn" style="background:rgba(255,255,255,0.12);"
                    onclick="closeWccSupportModal()"><?= __e('about.support.close') ?></button>
        </div>
    </div>
</div>

<script>
(function () {
    var SUPPORT = {
        csrf: <?= json_encode($wcc_support_csrf, JSON_UNESCAPED_UNICODE) ?>,
        snoozeDays: <?= (int)$wcc_support_snooze_days ?>,
        revolutUrl: <?= json_encode(trim((string)$wcc_support_revolut_url), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        kofiUrl: <?= json_encode(trim((string)$wcc_support_kofi_url), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        msgSnoozed: <?= json_encode(__('about.support.toast_snoozed', ['days' => (int)$wcc_support_snooze_days]), JSON_UNESCAPED_UNICODE) ?>,
        msgDismissed: <?= json_encode(__('about.support.no_coffee_note'), JSON_UNESCAPED_UNICODE) ?>,
        msgOpening: <?= json_encode(__('about.support.toast_opening'), JSON_UNESCAPED_UNICODE) ?>
    };

    // Button 2: snooze is PENDING until a Revolut/Ko-fi link is clicked.
    // Closing the modal without a link click cancels the pending snooze (accordion stays).
    var pendingSnooze = false;
    var snoozeCommitted = false;
    // Confetti + thanks toast wait until the user returns from the pay tab.
    var celebrateOnReturn = false;
    var sawPageHidden = false;
    var celebrateMsg = '';

    function toast(msg, type, ms, icon) {
        if (typeof showToast === 'function') showToast(msg, type || 'info', ms || 3200, icon);
    }

    function fireCelebrate() {
        if (!celebrateOnReturn) return;
        if (document.hidden) return;
        celebrateOnReturn = false;
        sawPageHidden = false;
        var msg = celebrateMsg || SUPPORT.msgSnoozed;
        celebrateMsg = '';
        if (typeof wccConfettiBurst === 'function') wccConfettiBurst({ count: 90, duration: 2400 });
        toast(msg, 'success', 8000, '🎉');
    }

    function armCelebrateOnReturn(msg) {
        celebrateOnReturn = true;
        sawPageHidden = !!document.hidden;
        celebrateMsg = msg || SUPPORT.msgSnoozed;
        // If they already left (rare), or leave soon — celebrate only after return
        if (!document.hidden) {
            // Mark that we need a real leave→return cycle
            sawPageHidden = false;
        }
    }

    function onPageMaybeBack() {
        if (!celebrateOnReturn) return;
        if (document.hidden) {
            sawPageHidden = true;
            return;
        }
        // Visible again after having been hidden (user came back)
        if (sawPageHidden) {
            fireCelebrate();
        }
    }

    document.addEventListener('visibilitychange', onPageMaybeBack);
    window.addEventListener('focus', function () {
        // Some browsers fire focus without visibilitychange order — only celebrate after a leave
        if (celebrateOnReturn && sawPageHidden && !document.hidden) {
            fireCelebrate();
        }
    });
    window.addEventListener('pageshow', function () {
        if (celebrateOnReturn && sawPageHidden && !document.hidden) {
            fireCelebrate();
        }
    });

    function setSnoozeHint(show) {
        var h = document.getElementById('wccSupportSnoozeHint');
        if (h) h.hidden = !show;
    }

    /**
     * Draw a QR into a FIXED square canvas.
     * bwip-js resizes canvases to module size — different URLs → different pixel
     * dimensions → visual "float". We render offscreen, then center-fit crisply.
     */
    function drawQr(canvasId, text) {
        var cv = document.getElementById(canvasId);
        if (!cv) return;
        var size = 160;
        cv.width = size;
        cv.height = size;
        var ctx = cv.getContext && cv.getContext('2d');
        if (!ctx) return;
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, size, size);
        if (!text || typeof bwipjs === 'undefined') return;
        try {
            var tmp = document.createElement('canvas');
            bwipjs.toCanvas(tmp, {
                bcid: 'qrcode',
                text: text,
                scale: 6,
                includetext: false,
                eclevel: 'M',
                paddingwidth: 2,
                paddingheight: 2
            });
            // Quiet zone + center: identical visual plate for both columns
            var margin = 10;
            var max = size - margin * 2;
            var scale = Math.min(max / tmp.width, max / tmp.height);
            var dw = Math.round(tmp.width * scale);
            var dh = Math.round(tmp.height * scale);
            // Force square draw box so neither code looks stretched
            var side = Math.min(dw, dh);
            var dx = Math.round((size - side) / 2);
            var dy = Math.round((size - side) / 2);
            ctx.imageSmoothingEnabled = false;
            ctx.drawImage(tmp, dx, dy, side, side);
        } catch (e) {
            console.warn('[WCC support QR]', e);
        }
    }

    window.openWccSupportModal = function openWccSupportModal(opts) {
        opts = opts || {};
        if (opts.pendingSnooze) {
            pendingSnooze = true;
            snoozeCommitted = false;
        } else if (!opts.keepPending) {
            pendingSnooze = false;
        }
        setSnoozeHint(!!pendingSnooze && !snoozeCommitted);
        var m = document.getElementById('wccSupportModal');
        if (!m) return;
        m.classList.add('open');
        drawQr('wccSupportQrRevolut', SUPPORT.revolutUrl || '');
        drawQr('wccSupportQrKofi', SUPPORT.kofiUrl || '');
    };

    window.closeWccSupportModal = function closeWccSupportModal() {
        var m = document.getElementById('wccSupportModal');
        if (m) m.classList.remove('open');
        // Went back without opening a pay link → do NOT hide / do NOT write snooze
        if (pendingSnooze && !snoozeCommitted) {
            pendingSnooze = false;
            setSnoozeHint(false);
        }
    };

    function hideAccordion() {
        var w = document.getElementById('wccSupportAccordionWrap');
        if (w) w.style.display = 'none';
    }

    function postPref(action) {
        var body = { action: action, csrf: SUPPORT.csrf };
        if (action === 'snooze') body.days = SUPPORT.snoozeDays;
        if (typeof wccCsrfToken === 'function') {
            try { body.csrf = wccCsrfToken() || body.csrf; } catch (e) {}
        }
        return fetch('/api/donation_prompt.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': body.csrf || '' },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function (r) { return r.json().catch(function () { return {}; }); });
    }

    /** Called when user clicks Revolut or Ko-fi (new tab). QR scan cannot be detected. */
    function commitSnoozeIfPending() {
        if (!pendingSnooze || snoozeCommitted) return;
        snoozeCommitted = true;
        pendingSnooze = false;
        setSnoozeHint(false);
        postPref('snooze').then(function (res) {
            if (res && res.status === 'success') {
                hideAccordion();
                // Confetti + "See you in 30 days" when they return to this tab — not on click
                armCelebrateOnReturn((res && res.message) || SUPPORT.msgSnoozed);
            } else {
                snoozeCommitted = false;
                pendingSnooze = true;
                setSnoozeHint(true);
                celebrateOnReturn = false;
                toast((res && res.message) || 'Error', 'error', 4000);
            }
        }).catch(function () {
            snoozeCommitted = false;
            pendingSnooze = true;
            setSnoozeHint(true);
            celebrateOnReturn = false;
        });
    }

    function onPayLinkClick(e) {
        // Let the browser open target=_blank; we only listen.
        // Brief "opening" toast only — party waits until focus returns.
        toast(SUPPORT.msgOpening, 'info', 1800, '↗');
        commitSnoozeIfPending();
    }

    document.querySelectorAll('.wcc-support-pay-link').forEach(function (a) {
        a.addEventListener('click', onPayLinkClick);
    });

    window.wccSupportChoose = function wccSupportChoose(kind) {
        if (kind === 'coffee') {
            // Keep showing next time — no snooze, just pay modal
            openWccSupportModal({ pendingSnooze: false });
            return;
        }
        if (kind === 'coffee_snooze') {
            // Do NOT hide yet. Snooze only after a pay link click.
            openWccSupportModal({ pendingSnooze: true });
            return;
        }
        if (kind === 'no_coffee') {
            postPref('dismiss').then(function (res) {
                if (res && res.status === 'success') {
                    toast(SUPPORT.msgDismissed, 'info', 10000, '💨');
                    hideAccordion();
                    var d = document.getElementById('wccSupportDetails');
                    if (d) d.open = false;
                } else {
                    toast((res && res.message) || 'Error', 'error', 4000);
                }
            });
        }
    };

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        var sm = document.getElementById('wccSupportModal');
        if (sm && sm.classList.contains('open')) {
            e.stopPropagation();
            closeWccSupportModal();
        }
    }, true);
})();
</script>
