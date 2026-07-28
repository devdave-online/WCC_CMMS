<?php
/**
 * WCC CMMS — gamified proficiency ladder (single source of truth).
 *
 * Technicians earn a proficiency per EQUIPMENT CATEGORY from the hours logged on
 * closed interventions: ticket_actions.action_end - action_start, summed per
 * category. Only categories present in `skill_automation_config` are scored, so
 * mapping a category is what makes that work count.
 *
 * This ladder used to be copy-pasted into four places (_mgmt/users.php twice,
 * _mgmt/users_list.php, my_profile.php). They agreed on the hour thresholds but
 * drifted on icons and colours — Master rendered gold on one screen and red on
 * another, and "Novice" appeared as 🌱, 🔧 and 🥉 depending where you looked.
 * Everything now reads from here, so a tier looks identical everywhere and the
 * in-app help can be generated from the same data it documents.
 */

/**
 * Tiers, richest first. `min` is the inclusive hour threshold.
 * @return array<int, array{min:int,tier:string,icon:string,color:string,blurb:string}>
 */
function wcc_gamified_tiers(): array
{
    return [
        ['min' => 200, 'tier' => 'Master',     'icon' => '👑', 'color' => '#eab308',
         'blurb' => 'Deep specialist. The person you wake at 3am for this equipment.'],
        ['min' => 100, 'tier' => 'Expert',     'icon' => '💎', 'color' => '#a855f7',
         'blurb' => 'Handles the hard faults on this category unaided.'],
        ['min' => 40,  'tier' => 'Proficient', 'icon' => '🥇', 'color' => '#3b82f6',
         'blurb' => 'Comfortable across routine and most non-routine work.'],
        ['min' => 20,  'tier' => 'Competent',  'icon' => '🥈', 'color' => '#10b981',
         'blurb' => 'Works unsupervised on standard faults.'],
        ['min' => 10,  'tier' => 'Advanced',   'icon' => '🥉', 'color' => '#f97316',
         'blurb' => 'Past the basics, still building depth.'],
        ['min' => 0,   'tier' => 'Novice',     'icon' => '🌱', 'color' => '#94a3b8',
         'blurb' => 'Getting started on this equipment category.'],
    ];
}

/** Resolve logged hours to a tier. Always returns a tier (Novice at the floor). */
function wcc_gamified_level(float $hours): array
{
    foreach (wcc_gamified_tiers() as $t) {
        if ($hours >= $t['min']) return $t;
    }
    return end($t2 = wcc_gamified_tiers()); // unreachable: the last tier has min 0
}

/** Hours still needed for the next tier up, or null when already at the top. */
function wcc_gamified_next(float $hours): ?array
{
    $ladder = array_reverse(wcc_gamified_tiers()); // cheapest first
    foreach ($ladder as $t) {
        if ($hours < $t['min']) {
            return ['tier' => $t, 'remaining' => round($t['min'] - $hours, 1)];
        }
    }
    return null;
}

/**
 * The "?" help overlay: explains how proficiencies are earned and the exact hour
 * thresholds, rendered from the ladder above so it can never contradict the code.
 * Emit once per page, then trigger with wccShowGamifiedHelp().
 */
function wcc_gamified_help_modal(): string
{
    // A 4-column table with a prose column cannot fit a modal: the description gets
    // squeezed to a few characters and the row clips. Each tier is laid out as its own
    // block instead — icon and threshold on one line, meaning underneath — which reads
    // at any width and has nothing to scroll sideways.
    $rows = '';
    foreach (wcc_gamified_tiers() as $t) {
        $range = $t['min'] === 0 ? 'under 10 h' : $t['min'] . ' h or more';
        $rows .= '<li style="display:flex; gap:14px; align-items:flex-start; padding:12px 14px;'
              . ' border-left:3px solid ' . $t['color'] . '; background:var(--surface-1);'
              . ' border-radius:8px; margin-bottom:8px;">'
              . '<span aria-hidden="true" style="font-size:1.7em; line-height:1;">' . $t['icon'] . '</span>'
              . '<div style="min-width:0;">'
              .   '<div style="display:flex; flex-wrap:wrap; align-items:baseline; gap:10px;">'
              .     '<strong style="color:' . $t['color'] . '; font-size:1.05em;">' . htmlspecialchars($t['tier']) . '</strong>'
              .     '<span style="font-weight:700; color:var(--text-primary); font-size:0.9em;">' . $range . '</span>'
              .   '</div>'
              .   '<div style="color:var(--text-secondary); line-height:1.5; margin-top:3px; font-size:0.9em;">'
              .     htmlspecialchars($t['blurb']) . '</div>'
              . '</div>'
              . '</li>';
    }

    // width is set explicitly: .modal-content carries a fixed width:400px, which a
    // max-width alone can only cap, never widen — that is why this panel rendered
    // narrow enough to clip its own content.
    return <<<HTML
<div id="gamifiedHelpModal" class="modal" style="z-index:10002;" role="dialog" aria-modal="true" aria-labelledby="gamifiedHelpTitle">
  <div class="modal-content" style="width:min(720px, 92vw); max-width:none;">
    <span class="close" onclick="document.getElementById('gamifiedHelpModal').style.display='none'">&times;</span>
    <h2 id="gamifiedHelpTitle" style="margin:0 0 8px 0; font-size:1.2em; color:var(--text-primary);">
        🏆 How Gamified Proficiencies Work
    </h2>
    <p style="margin:0 0 18px 0; color:var(--text-secondary); line-height:1.65;">
        Proficiencies are <strong>earned automatically</strong> — nobody awards them. Every time a technician
        closes an intervention, the time between taking the job over and finishing it is added to the
        <strong>equipment category</strong> of the machine they worked on. Cross an hour threshold in a
        category and the tier for that category goes up on its own.
    </p>

    <ul style="list-style:none; margin:0; padding:0;">$rows</ul>

    <div style="margin-top:18px; padding:14px; background:var(--surface-1); border:1px solid var(--panel-border); border-radius:var(--radius-md, 8px);">
      <div style="font-weight:700; color:var(--text-accent); margin-bottom:8px;">Good to know</div>
      <ul style="margin:0; padding-left:20px; color:var(--text-secondary); line-height:1.7; font-size:0.9em;">
        <li>Hours come only from <strong>closed</strong> interventions that have both a start and an end time. Open jobs count for nothing until they are closed out.</li>
        <li>Tiers are counted <strong>per equipment category</strong>, not overall — someone can be Master on Machining and Novice on Packaging at the same time.</li>
        <li>A category only scores if it is mapped in the <strong>Skill Configurator</strong>. Unmapped categories earn nothing, however much work is done on them.</li>
        <li>These are earned proficiencies. The 🛠️ badge next to them is <strong>manual skills</strong> — certifications an administrator grants by hand, which can carry an expiry date.</li>
        <li>Nothing decays: hours accumulate for as long as the intervention history is kept.</li>
      </ul>
    </div>
  </div>
</div>
<script>
function wccShowGamifiedHelp() {
    var m = document.getElementById('gamifiedHelpModal');
    if (m) m.style.display = 'block';
}
</script>
HTML;
}

/* ---------------------------------------------------------------------------
 * MANUAL SKILLS (certifications)
 *
 * The other half of the Skills column, and a different thing entirely from the
 * proficiencies above: `user_skills` rows are granted by an administrator and may
 * carry an expiry date. Nothing is earned and there is no tier.
 *
 * `expiry_date` shipped in the schema and is written by api/manage_skills.php, but
 * until now nothing ever read it — a lapsed LOTO authorisation looked identical to
 * a current one. For a maintenance system that is a safety signal, not cosmetics,
 * so the states are defined here once for every surface (web + REST + companion app).
 * ------------------------------------------------------------------------- */

/** Days before expiry at which a certification starts warning. */
const WCC_SKILL_EXPIRY_WARN_DAYS = 30;

/**
 * Classify a certification's expiry.
 *
 * @param string|null $expiry 'Y-m-d', or null for a certification that never expires
 * @return array{state:string,days:?int,label:string,color:string,icon:string}
 *         state: none | valid | expiring | expired
 *         days:  days until expiry (negative once lapsed), null when no expiry
 */
function wcc_skill_expiry(?string $expiry): array
{
    if ($expiry === null || $expiry === '' || $expiry === '0000-00-00') {
        return ['state' => 'none', 'days' => null, 'label' => 'No expiry',
                'color' => '#94a3b8', 'icon' => ''];
    }

    $ts = strtotime($expiry);
    if ($ts === false) {
        return ['state' => 'none', 'days' => null, 'label' => 'No expiry',
                'color' => '#94a3b8', 'icon' => ''];
    }

    // Compare at day granularity so "expires today" is not already lapsed.
    $today = strtotime(date('Y-m-d'));
    $days  = (int)floor(($ts - $today) / 86400);

    if ($days < 0) {
        return ['state' => 'expired', 'days' => $days,
                'label' => 'Expired ' . abs($days) . 'd ago', 'color' => '#ef4444', 'icon' => '⛔'];
    }
    if ($days <= WCC_SKILL_EXPIRY_WARN_DAYS) {
        return ['state' => 'expiring', 'days' => $days,
                'label' => $days === 0 ? 'Expires today' : 'Expires in ' . $days . 'd',
                'color' => '#f97316', 'icon' => '⚠️'];
    }
    return ['state' => 'valid', 'days' => $days,
            'label' => 'Valid until ' . date('j M Y', $ts), 'color' => '#10b981', 'icon' => ''];
}

/** Render a certification as a chip, expiry state included. */
function wcc_skill_chip(string $name, ?string $expiry): string
{
    $e = wcc_skill_expiry($expiry);
    $lapsed = $e['state'] === 'expired';
    return '<span style="background:rgba(255,255,255,0.06); border-radius:8px; padding:4px 10px;'
         . ' font-size:0.9em; display:inline-flex; align-items:center; gap:6px;'
         . ' border:1px solid ' . $e['color'] . '55;'
         . ($lapsed ? ' opacity:.75;' : '') . '">'
         . '<span aria-hidden="true">🛠️</span>'
         . '<span' . ($lapsed ? ' style="text-decoration:line-through;"' : '') . '>' . htmlspecialchars($name) . '</span>'
         . ($e['state'] === 'none' ? '' :
             '<small style="color:' . $e['color'] . '; font-weight:700; white-space:nowrap;">'
             . $e['icon'] . ' ' . htmlspecialchars($e['label']) . '</small>')
         . '</span>';
}

/** The small circled "?" trigger button. */
function wcc_gamified_help_button(string $title = 'How do proficiencies work?'): string
{
    $t = htmlspecialchars($title);
    return '<button type="button" onclick="wccShowGamifiedHelp(); event.stopPropagation();" title="' . $t . '"'
         . ' aria-label="' . $t . '"'
         . ' style="width:20px; height:20px; padding:0; border-radius:50%; cursor:pointer; line-height:1;'
         . ' border:1px solid var(--text-accent); background:transparent; color:var(--text-accent);'
         . ' font-size:0.78em; font-weight:700; display:inline-flex; align-items:center; justify-content:center;'
         . ' vertical-align:middle; margin-left:6px;">?</button>';
}
