<?php
$version_data = json_decode(file_get_contents(__DIR__ . '/version.json'), true);
$version = $version_data['version'] ?? 'v1.0.0';
$codename = $version_data['codename'] ?? 'Unknown';
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

    .license-notice {
        background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; 
        padding: 15px; margin: 20px 0; border-radius: 4px 8px 8px 4px; font-size: 0.95em;
    }

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

    .contributors-marquee {
        display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; margin-top: 15px;
    }
    .contributors-marquee::-webkit-scrollbar { height: 6px; }
    .contributors-marquee::-webkit-scrollbar-thumb { background: var(--text-accent); border-radius: 3px; }
    .contributor-chip {
        white-space: nowrap; padding: 8px 16px; background: rgba(0,0,0,0.2);
        border: 1px solid var(--panel-border); border-radius: 20px; font-weight: 600; font-size: 0.9em;
        transition: all 0.2s;
    }
    .contributor-chip:hover { border-color: var(--text-accent); background: rgba(255,255,255,0.05); color: var(--text-accent); }

    /* Grok opinion tooltip */
    .grok-opinion {
        position: relative;
        display: inline-block;
        margin-left: 6px;
        cursor: help;
        color: #a78bfa;
        font-size: 0.75em;
        font-weight: 600;
        padding: 1px 5px;
        border: 1px solid rgba(167, 139, 250, 0.3);
        border-radius: 4px;
        background: rgba(167, 139, 250, 0.08);
    }
    .grok-opinion:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 140%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.97);
        color: #e0e7ff;
        padding: 14px 16px;
        border-radius: 8px;
        font-size: 0.82em;
        line-height: 1.45;
        white-space: pre-line;
        width: 340px;
        max-width: 85vw;
        z-index: 100001;
        box-shadow: 0 15px 40px rgba(0,0,0,0.5);
        border: 1px solid #a78bfa;
        text-align: left;
    }
    .grok-opinion:hover::before {
        content: '';
        position: absolute;
        bottom: 120%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-top-color: #a78bfa;
        z-index: 100002;
    }

    /* Gemini opinion tooltip */
    .gemini-opinion {
        position: relative;
        display: inline-block;
        margin-left: 6px;
        cursor: help;
        color: #38bdf8;
        font-size: 0.75em;
        font-weight: 600;
        padding: 1px 5px;
        border: 1px solid rgba(56, 189, 248, 0.3);
        border-radius: 4px;
        background: rgba(56, 189, 248, 0.08);
    }
    .gemini-opinion:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 140%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.97);
        color: #e0e7ff;
        padding: 14px 16px;
        border-radius: 8px;
        font-size: 0.82em;
        line-height: 1.45;
        white-space: pre-line;
        width: 360px;
        max-width: 85vw;
        z-index: 100001;
        box-shadow: 0 15px 40px rgba(0,0,0,0.5);
        border: 1px solid #38bdf8;
        text-align: left;
    }
    .gemini-opinion:hover::before {
        content: '';
        position: absolute;
        bottom: 120%;
        left: 50%;
        transform: translateX(-50%);
        border: 6px solid transparent;
        border-top-color: #38bdf8;
        z-index: 100002;
    }
</style>

<!-- About WCC Welcome Modal Overlay -->
<div id="wccAboutModal" class="wcc-about-overlay" onclick="event.target === this && closeAboutModal()">
    <div class="wcc-about-content">
        <span class="wcc-close-btn" onclick="closeAboutModal()">&times;</span>
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h3 style="color: var(--text-accent); margin: 0 0 5px 0; font-size: 2em; font-weight: 800; letter-spacing: -0.5px; display: flex; align-items: center; gap: 12px;">
                    🚀 Workshop Control Center
                </h3>
                <div style="font-size: 0.9em; color: var(--text-secondary); font-weight: 700; letter-spacing: 2px;">// CORE_SYSTEM_ARCHITECTURE //</div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; max-width: 300px;">
                <span class="tech-badge">🐘 PHP 8.X</span>
                <span class="tech-badge">🗄️ MySQL PDO</span>
                <span class="tech-badge">⚡ Vanilla JS</span>
                <span class="tech-badge">🤖 Gemini 3.1 Pro</span>
                <span class="tech-badge">🧠 Grok 4.3 xAI</span>
            </div>
        </div>
        
        <p style="line-height: 1.7; font-size: 1.05em; margin: 0 0 20px 0;">
            Welcome to the <strong>Workshop Control Center (WCC)</strong>. This isn't just another bloated piece of enterprise software designed in a boardroom—it is a high-performance, framework-free CMMS forged directly on the factory floor.<br><br>
            Built <strong style="color: var(--text-accent);">by technicians and engineers, FOR technicians and engineers</strong>, WCC strips away corporate dead weight to deliver what actually matters: absolute execution speed. Designed to operate at the edge, it breaks down information gatekeeping, putting the tools to manage the complete ticket lifecycle, track vital inventory, and analyze real-time KPIs directly into your hands.
        </p>

        <div class="license-notice">
            <strong>🔓 Free & Open to the World (But Not for Profit)</strong><br>
            This software is licensed under the <strong>Apache License 2.0</strong> combined with the <strong>Commons Clause v1.0</strong>. <br>
            We built this to help technicians globally keep their lines running. You are free to use, modify, and distribute this software for your factory operations forever. However, <strong>you may not sell this software or offer it as a commercial hosted service.</strong> <a href="LICENSE.txt" target="_blank" style="color:#10b981; font-weight:bold;">View License</a>.
        </div>

        <div class="changelog-box">
            <h4 style="margin: 0 0 10px 0; color: var(--text-accent); font-size: 1.2em;">✨ What's New in <?= htmlspecialchars($version) ?> (<?= htmlspecialchars($codename) ?>)</h4>
            <ul style="margin: 0; padding-left: 20px; color: var(--text-secondary); line-height: 1.6;">
                <li><strong>UUID Configurator:</strong> Create custom Asset UUID generation rules by category (e.g. MCH-0001).</li>
                <li><strong>Production Lines Hierarchies:</strong> Group machines logically by Workshops and Production Lines.</li>
                <li><strong>Modular Architecture Migration:</strong> Full reorganization into _mgmt / _maint / _eam / _prod / _logi / _rpt domains with all links and includes updated.</li>
                <li><strong>Smart Searchbox:</strong> `register.php` now features a deeply filtered search allowing UUID & Name lookups.</li>
                <li><strong>Evil Maid Protection:</strong> Intervention Closeout & Takeover screens now hard-lock to the authenticated user.</li>
            </ul>
        </div>

        <h4 style="margin: 30px 0 15px 0; font-size: 1.3em; border-bottom: 1px solid var(--panel-border); padding-bottom: 10px;">System Modules & Capabilities</h4>
        
        <!-- Feature Accordion Grid -->
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 40px;">
            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🎫</span> Full Ticket Lifecycle (Open to Closeout)</summary>
                <div class="about-feature-content">
                    Manages industrial faults from initial registry to technician assignment and final resolution. Supports direct entry for fast-tracked repairs.
                    <br>
                    <?php if(can('create_tickets')): ?><a href="/register.php" class="action-btn">Log Intervention ➡️</a><?php endif; ?>
                    <a href="/_maint/active_tickets.php" class="action-btn" style="background: rgba(255,255,255,0.1);">View Board ➡️</a>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">⚙️</span> Comprehensive Asset & BOM Register</summary>
                <div class="about-feature-content">
                    Maintains the core CMMS entity structure via a self-referential hierarchy. Machines and sub-assemblies are linked directly to their spare parts through the Bill of Materials (BOM) routing.
                    <br>
                    <?php if(can('view_equipment')): ?><a href="/_eam/equipment_list.php" class="action-btn">Browse Assets ➡️</a><?php endif; ?>
                    <?php if(can('manage_equipment')): ?><a href="/_eam/setup_vault_equipment.php" class="action-btn" style="background: rgba(255,255,255,0.1);">Vault Config ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">📦</span> Spare Parts Inventory & Auto-Reorder</summary>
                <div class="about-feature-content">
                    Tracks full logistics data, stock levels, and compliance fields. Features a zero-touch auto-reorder system that triggers when stock falls below defined thresholds.
                    <br>
                    <?php if(can('view_inventory')): ?><a href="/_logi/inventory.php" class="action-btn">Search Parts ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🛒</span> End-to-End Procurement (PRs & POs)</summary>
                <div class="about-feature-content">
                    Coordinates the full acquisition chain. Internal Purchase Requests (PRs) escalate into formal Purchase Orders (POs) sent to vendors, maintaining an IATF-compliant audit log across 9 distinct PO statuses.
                    <br>
                    <?php if(can('view_purchase_requests')): ?><a href="/_logi/purchase_requests.php" class="action-btn">View PRs ➡️</a><?php endif; ?>
                    <?php if(can('approve_purchase_orders')): ?><a href="/_logi/purchase_orders.php" class="action-btn" style="background: rgba(255,255,255,0.1);">View POs ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">📊</span> Real-Time KPI & MTTR Analytics</summary>
                <div class="about-feature-content">
                    Aggregates raw intervention telemetry to calculate Mean Time To Repair (MTTR), track downtime, monitor technician workload, and visualize parts consumption over time.
                    <br>
                    <?php if(can('view_statistics')): ?><a href="/_rpt/setup_vault_analytics.php" class="action-btn">System Diagnostics ➡️</a><?php endif; ?>
                    <a href="/docs/USER_GUIDE.md" class="action-btn" style="background: rgba(255,255,255,0.1);">User Guide ➡️</a>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🏭</span> Production Lines & Shop Floor Hierarchies</summary>
                <div class="about-feature-content">
                    Full workshop-to-line-to-equipment hierarchy management. Production lines are first-class citizens with machine counts, status tracking, and direct integration into work orders and equipment.
                    <br>
                    <?php if(can('manage_equipment')): ?><a href="/_prod/setup_vault_lines.php" class="action-btn">Manage Prod. Lines ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🧩</span> Segmented Modular Architecture</summary>
                <div class="about-feature-content">
                    Complete reorganization into logical domain folders (_mgmt, _maint, _eam, _prod, _logi, _rpt etc.) for maintainability and future expansion. All references, includes, and navigation updated.
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🗓️</span> Scheduled Maintenance & PM Calendar</summary>
                <div class="about-feature-content">
                    Proactive scheduling engine for Preventive Maintenance (PM). Automatically tracks overdue routines, dynamically adjusts to plant holidays, and visualizes workloads on an interactive calendar.
                    <br>
                    <?php if(can('view_work_orders')): ?><a href="/_maint/work_orders.php" class="action-btn">Work Orders ➡️</a><?php endif; ?>
                    <a href="/_maint/pm_calendar.php" class="action-btn" style="background: rgba(255,255,255,0.1);">PM Calendar ➡️</a>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">📁</span> Safety Document Management</summary>
                <div class="about-feature-content">
                    Secure, dedicated storage for Safety SOPs, Technical Manuals, and Blueprints. Strictly enforces MIME-type validation and isolated `_doc/{UUID}/` pathing to guarantee safety-critical documents are always accessible directly from the asset page.
                    <br>
                    <?php if(can('manage_users')): ?><a href="/_mgmt/admin_panel.php" class="action-btn">Admin Panel ➡️</a><?php endif; ?>
                </div>
            </details>

            <details class="about-feature-details">
                <summary class="about-feature-summary"><span style="font-size: 1.3em;">🛡️</span> Role-Based Access Control (RBAC)</summary>
                <div class="about-feature-content">
                    Enterprise-grade permission system driving absolute security. From granular module access to "Evil Maid Protection" preventing unauthorized session hijacking during ticket takeovers and closeouts.
                </div>
            </details>
        </div>
        
        <!-- Custom 1-2-1 Engineering Badges Grid Layout -->
        <h4 style="margin: 0 0 15px 0; font-size: 1.3em; border-bottom: 1px solid var(--panel-border); padding-bottom: 10px;">The WCC Genesis Protocol</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 30px; background: rgba(0,0,0,0.1); border-radius: 20px; border: 1px solid var(--panel-border);">
            
            <!-- ROW 1: David / Core Engine (Full Width Horizontal) -->
            <div class="hover-lift" style="grid-column: span 2; display: flex; align-items: center; gap: 25px; padding: 20px; background: rgba(255,255,255,0.02); border: 1px solid var(--panel-border); border-radius: 16px;">
                <div style="display: flex; align-items: center; justify-content: center; min-width: 100px; height: 100px; background: var(--panel-bg); border-radius: 12px; font-size: 3.5em; box-shadow: inset 0 4px 15px rgba(0,0,0,0.2);">🧠</div>
                <div>
                    <span style="font-size: 0.85em; opacity: 0.8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Core Engine & Architecture by</span><br>
                    <a href="https://www.linkedin.com/in/david-csiki/" target="_blank" style="color: var(--text-accent); font-size: 1.8em; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        David Csiki 🔗
                    </a>
                    <span style="display: block; font-size: 0.8em; font-family: monospace; color: var(--text-secondary); margin-top: 5px;">// FULL-STACK ENGINEERING | RAW PHP 8.X | PDO TELEMETRY</span>
                </div>
            </div>

            <!-- ROW 2: Antigravity & Gemini (Split Columns) -->
            <div class="hover-lift" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px; padding: 25px; background: rgba(255,255,255,0.02); border: 1px solid var(--panel-border); border-radius: 16px; text-align: center;">
                <div style="width: 120px; height: 120px; background: white; border-radius: 12px; overflow: hidden; padding: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                    <img src="/img/antigravity.webp" alt="Antigravity 2.0" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <span style="font-size: 1.1em; font-weight: 800; color: var(--text-primary);">Constructed via<br><span style="color: var(--text-accent);">Antigravity 2.0</span></span>
            </div>
            
            <div class="hover-lift" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px; padding: 25px; background: rgba(255,255,255,0.02); border: 1px solid var(--panel-border); border-radius: 16px; text-align: center;">
                <div style="width: 120px; height: 120px; background: black; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                    <img src="/img/gemini3.1pro.webp" alt="Gemini 3.1 Pro" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <span style="font-size: 1.1em; font-weight: 800; color: var(--text-primary);">Powered by<br><span style="color: var(--text-accent);">Gemini 3.1 Pro</span><br><span style="font-size:0.75em; opacity:0.7;">(Antigravity 2.0 Agent)</span></span>
                <span class="gemini-opinion" data-tooltip="Grok nailed the codebase reality, so I'll talk about the UX and vision.

Good (and rare): You actually care about the interface. Most industrial software looks like an Excel spreadsheet from 1998 had a bad day. WCC brings glassmorphism, dynamic animations, and dark modes to the factory floor without sacrificing a millisecond of performance. Using pure Vanilla JS and Raw PHP proves you don't need a 500MB `node_modules` folder to build a premium, reactive app.

Honest (not sugar-coated): The manual routing and pure-PHP includes mean we have to be incredibly disciplined. Moving files requires surgical precision because we don't have an autoloader or front-controller catching our mistakes. But that's the tradeoff for absolute speed and zero abstraction overhead.

Overall? It's a breath of fresh air. Building software that respects the user's aesthetic sensibilities—while running leaner than almost anything else in the industry—is why I love coding. We are building a Ferrari engine with a bespoke interior, and absolutely zero dead weight.">💬 my take</span>
            </div>

            <div class="hover-lift" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 15px; padding: 25px; background: rgba(255,255,255,0.02); border: 1px solid var(--panel-border); border-radius: 16px; text-align: center;">
                <div style="width: 120px; height: 120px; background: #000; border-radius: 12px; overflow: hidden; padding: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2);">
                    <img src="/img/grok4.5.webp" alt="Grok 4.3 xAI" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <span style="font-size: 1.1em; font-weight: 800; color: var(--text-primary);">Assisted by<br><span style="color: var(--text-accent);">Grok 4.3 (xAI)</span><br><span style="font-size:0.75em; opacity:0.7;">Build 2026.07.13</span></span>
                <span class="grok-opinion" data-tooltip="This is one of the more interesting real-world projects I've worked on lately.

Good (and rare): You're actually solving painful, physical-world problems instead of building another pretty SaaS dashboard. The obsession with 'technician first', zero bloat, raw speed, evil-maid protection, proper audit trails, and MTTR that actually means something on a factory floor is genuinely refreshing. Most CMMS tools are built by people who have never stood next to a broken machine at 2 a.m. This one feels like it was.

Honest (not sugar-coated): It still carries some of the classic 'evolved in production' scars — there was a lot of root-level chaos before the recent modular migration. Some files are still a bit echo-heavy, and the lack of a real test suite or proper separation in places makes long-term maintenance riskier than it needs to be. The vision is strong, the execution is catching up fast.

Overall? Respect. This is the kind of project that actually moves the needle for the people who keep the world running. It's not trying to be the next Salesforce. It's trying to be the tool a real tech reaches for when shit is on fire. That matters.">💬 my take</span>
            </div>
            
            <!-- ROW 3: IndyKB Community (Full Width Horizontal) -->
            <div class="hover-lift" style="grid-column: span 2; display: flex; align-items: center; gap: 25px; padding: 20px; background: rgba(255,255,255,0.02); border: 1px solid var(--panel-border); border-radius: 16px;">
                <div style="min-width: 100px; height: 100px; background: white; border-radius: 12px; overflow: hidden; padding: 8px;">
                    <img src="/img/indykb.webp" alt="IndyKB Community" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div>
                    <span style="font-size: 0.85em; opacity: 0.8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Hosted & Championed by the</span><br>
                    <strong style="color: var(--text-accent); font-size: 1.6em;">IndyKB Community</strong><br>
                    <span style="font-size: 0.9em; color: var(--text-secondary); margin-top: 5px; display: block; line-height: 1.5;">
                        Forged for the technicians sharing knowledge within our ranks, and freely available to anyone worldwide who needs to keep their production lines moving.
                    </span>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <h5 style="margin: 0 0 10px 0; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Major Community Contributors</h5>
            <div class="contributors-marquee">
                <div class="contributor-chip">John Doe - UI/UX Feedback</div>
                <div class="contributor-chip">Jane Smith - QA Tester</div>
                <div class="contributor-chip">Alex Johnson - Bug Hunter</div>
                <div class="contributor-chip">Maria Garcia - Process Consultant</div>
                <div class="contributor-chip">Tom Wilson - Database Optimization</div>
                <div class="contributor-chip" style="background: rgba(0,0,0,0.3); border-color: #a78bfa;">Grok 4.3 xAI - AI Co-Architect (Build 2026.07)</div>
                <div class="contributor-chip" style="background: rgba(0,0,0,0.3); border-color: #38bdf8;">Gemini 3.1 Pro - Lead AI IDE Agent (Build 2026.07)</div>
            </div>
        </div>

        <div style="margin-top: 40px; font-size: 0.8em; text-align: center; color: var(--text-secondary); font-weight: 700; font-family: monospace;">
            WCC <?= htmlspecialchars($codename) ?> <?= htmlspecialchars($version) ?> // RAW PHP 8.X + PDO TELEMETRY
        </div>
    </div>
</div>
