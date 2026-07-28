<?php
/**
 * WCC CMMS — the manual.
 *
 * Deliberately PUBLIC (no auth.php, no require_perm). Documentation is the strongest
 * marketing surface a self-hosted product has: gating it behind a login means nobody
 * evaluating WCC can read it, and there is nothing sensitive here — it describes how
 * the system works, never what is in anyone's database.
 *
 * Structure comes entirely from docs/registry.php: the sidebar, the scroll-spy targets
 * and the include order all derive from that one array, so adding a chapter means
 * adding one entry plus one file in docs/chapters/. Nothing here needs editing.
 */

$parts = require __DIR__ . '/docs/registry.php';

// Flatten once — used for prev/next links and to count what we are showing.
$flat = [];
foreach ($parts as $p) {
    foreach ($p['chapters'] as $ch) {
        $ch['part'] = $p['part'];
        $flat[] = $ch;
    }
}

$page_title = 'Documentation';
require_once __DIR__ . '/inc/head.php';
?>
<style>
    /* The manual gets its own shell: no app sidebar, no page chrome. head.php reserves
       a left margin for the app nav, which is not rendered here — reclaim it. */
    body { margin-left: 0 !important; }

    .docs-layout {
        display: grid;
        grid-template-columns: 300px minmax(0, 1fr);
        gap: var(--space-8);
        max-width: 1500px;
        margin: 0 auto;
        padding: var(--space-6) var(--space-6) var(--space-8);
        align-items: start;
    }

    /* ── Sidebar ─────────────────────────────────────────────────────── */
    .docs-nav {
        position: sticky;
        top: var(--space-5);
        max-height: calc(100vh - var(--space-8));
        overflow-y: auto;
        overscroll-behavior: contain;
        background: var(--panel-bg);
        border: 1px solid var(--panel-border);
        border-radius: var(--radius-lg);
        padding: var(--space-5);
        backdrop-filter: blur(20px);
    }
    .docs-brand {
        display: flex; align-items: center; gap: 10px;
        font-size: 1.15em; font-weight: 800; color: var(--text-accent);
        margin-bottom: 4px; text-decoration: none;
    }
    .docs-version { font-size: var(--fs-xs); color: var(--text-muted); margin-bottom: var(--space-4); }

    .docs-search {
        width: 100%; padding: 8px 10px; margin-bottom: var(--space-4);
        background: var(--input-bg, rgba(0,0,0,.2)); color: var(--text-primary);
        border: 1px solid var(--input-border, var(--panel-border));
        border-radius: var(--radius-sm); font-size: var(--fs-sm);
    }

    .docs-part {
        font-size: var(--fs-xs); text-transform: uppercase; letter-spacing: 1.2px;
        color: var(--text-muted); font-weight: 700;
        margin: var(--space-4) 0 6px; padding-left: 4px;
    }
    .docs-part:first-of-type { margin-top: 0; }

    .docs-link {
        display: block; padding: 6px 10px; border-radius: var(--radius-sm);
        color: var(--text-secondary); text-decoration: none; font-size: var(--fs-sm);
        border-left: 2px solid transparent; line-height: 1.35;
    }
    .docs-link:hover { background: rgba(127,127,127,.10); color: var(--text-primary); }
    .docs-link.active {
        color: var(--text-accent); border-left-color: var(--text-accent);
        background: rgba(127,127,127,.12); font-weight: 700;
    }
    .docs-link .num { opacity: .55; margin-right: 6px; font-variant-numeric: tabular-nums; }

    .docs-sections { margin: 2px 0 6px 14px; }
    .docs-sections a {
        display: block; padding: 3px 10px; font-size: var(--fs-xs);
        color: var(--text-muted); text-decoration: none;
        border-left: 1px solid var(--panel-border);
    }
    .docs-sections a:hover { color: var(--text-primary); }
    .docs-sections a.active { color: var(--text-accent); border-left-color: var(--text-accent); }

    /* ── Content ─────────────────────────────────────────────────────── */
    .docs-body { min-width: 0; }
    .docs-chapter {
        background: var(--panel-bg);
        border: 1px solid var(--panel-border);
        border-radius: var(--radius-lg);
        padding: var(--space-7) var(--space-7);
        margin-bottom: var(--space-6);
        backdrop-filter: blur(20px);
    }
    /* Anchors must clear the sticky offset when jumped to. */
    .docs-chapter, .docs-chapter h3 { scroll-margin-top: var(--space-5); }

    .docs-chapter h2 {
        margin: 0 0 4px; font-size: 1.6em; color: var(--text-primary); line-height: 1.25;
    }
    .docs-chapter h2 .num { color: var(--text-accent); margin-right: 10px; }
    .docs-chapter > .part-tag {
        display: inline-block; font-size: var(--fs-xs); text-transform: uppercase;
        letter-spacing: 1px; color: var(--text-muted); margin-bottom: var(--space-4);
    }
    .docs-chapter h3 {
        margin: var(--space-6) 0 var(--space-3);
        font-size: 1.12em; color: var(--text-accent);
        padding-bottom: 6px; border-bottom: 1px solid var(--panel-border);
    }
    .docs-chapter p, .docs-chapter li { line-height: 1.75; color: var(--text-secondary); }
    .docs-chapter strong { color: var(--text-primary); }
    .docs-chapter ul, .docs-chapter ol { padding-left: 22px; }
    .docs-chapter li { margin-bottom: 6px; }
    .docs-chapter a { color: var(--text-accent); }

    .docs-chapter code {
        background: var(--surface-1); border: 1px solid var(--panel-border);
        padding: 1px 6px; border-radius: 4px;
        font-family: ui-monospace, "Cascadia Code", Consolas, monospace;
        font-size: .88em; color: var(--text-primary);
    }
    .docs-chapter pre {
        background: var(--surface-1); border: 1px solid var(--panel-border);
        border-radius: var(--radius-md); padding: var(--space-4);
        overflow-x: auto; line-height: 1.6;
    }
    .docs-chapter pre code { background: none; border: 0; padding: 0; }

    /* Wide content must scroll inside its own box, never widen the page. */
    .docs-chapter .table-scroll { overflow-x: auto; margin: var(--space-4) 0; }
    .docs-chapter table { width: 100%; border-collapse: collapse; font-size: var(--fs-sm); }
    .docs-chapter th, .docs-chapter td {
        text-align: left; padding: 9px 12px; border-bottom: 1px solid var(--panel-border);
        vertical-align: top;
    }
    .docs-chapter th { color: var(--text-muted); text-transform: uppercase; font-size: var(--fs-xs); letter-spacing: .6px; }
    .docs-chapter tbody tr:last-child td { border-bottom: 0; }

    .docs-note {
        border-left: 3px solid var(--text-accent);
        background: var(--surface-1);
        padding: var(--space-4); border-radius: 0 var(--radius-md) var(--radius-md) 0;
        margin: var(--space-4) 0;
    }
    .docs-note.warn   { border-left-color: var(--warning); }
    .docs-note.danger { border-left-color: var(--danger); }
    .docs-note .t { font-weight: 700; color: var(--text-primary); display: block; margin-bottom: 4px; }

    .docs-figure { margin: var(--space-5) 0; }
    .docs-figure img { max-width: 100%; border: 1px solid var(--panel-border); border-radius: var(--radius-md); display: block; }
    .docs-figure figcaption { font-size: var(--fs-xs); color: var(--text-muted); margin-top: 6px; }

    .docs-empty {
        border: 1px dashed var(--panel-border); border-radius: var(--radius-md);
        padding: var(--space-5); color: var(--text-muted); font-style: italic;
    }

    /* ── Responsive ──────────────────────────────────────────────────── */
    @media (max-width: 1000px) {
        .docs-layout { grid-template-columns: 1fr; padding: var(--space-4); gap: var(--space-5); }
        .docs-nav { position: static; max-height: none; }
        .docs-chapter { padding: var(--space-5); }
    }

    /* ── Print: drop the shell, keep the words ───────────────────────── */
    @media print {
        .docs-nav, #wccWaveBg, .docs-toolbar { display: none !important; }
        .docs-layout { display: block; max-width: none; padding: 0; }
        .docs-chapter {
            background: #fff; border: 0; padding: 0; margin: 0 0 28px;
            break-inside: avoid; backdrop-filter: none;
        }
        .docs-chapter h2 { break-after: avoid; }
        body { background: #fff !important; }
    }
</style>

<div class="docs-layout">

    <!-- ── Sidebar ─────────────────────────────────────────────────── -->
    <nav class="docs-nav" aria-label="Documentation contents">
        <a href="/index.php" class="docs-brand"><img src="/img/wcc-orb.png" alt="" class="wcc-docs-mark" width="22" height="22" decoding="async"> WCC Manual</a>
        <div class="docs-version">
            Version <?= htmlspecialchars(json_decode(@file_get_contents(__DIR__ . '/version.json'), true)['version'] ?? '1.00') ?>
            · <?= count($flat) ?> chapters
        </div>

        <input type="search" id="docsSearch" class="docs-search" placeholder="Filter chapters…"
               aria-label="Filter chapters" autocomplete="off">

        <div id="docsNavList">
        <?php foreach ($parts as $part): ?>
            <div class="docs-part" data-part><?= htmlspecialchars($part['part']) ?></div>
            <?php foreach ($part['chapters'] as $ch): ?>
                <a class="docs-link" href="#<?= htmlspecialchars($ch['id']) ?>"
                   data-target="<?= htmlspecialchars($ch['id']) ?>"
                   data-search="<?= htmlspecialchars(strtolower($ch['title'] . ' ' . implode(' ', $ch['sections'] ?? []))) ?>">
                    <span class="num"><?= htmlspecialchars($ch['num']) ?></span><?= htmlspecialchars($ch['title']) ?>
                </a>
                <?php if (!empty($ch['sections'])): ?>
                    <div class="docs-sections" data-sections-for="<?= htmlspecialchars($ch['id']) ?>">
                        <?php foreach ($ch['sections'] as $sid => $stitle): ?>
                            <a href="#<?= htmlspecialchars($sid) ?>" data-target="<?= htmlspecialchars($sid) ?>"><?= htmlspecialchars($stitle) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </div>

        <div id="docsNoMatch" class="text-muted" style="display:none; font-size:var(--fs-sm); padding:8px 4px;">
            No chapter matches that.
        </div>
    </nav>

    <!-- ── Chapters ────────────────────────────────────────────────── -->
    <main class="docs-body">
        <?php foreach ($parts as $part): ?>
            <?php foreach ($part['chapters'] as $ch): ?>
                <article class="docs-chapter" id="<?= htmlspecialchars($ch['id']) ?>">
                    <span class="part-tag"><?= htmlspecialchars($part['part']) ?></span>
                    <h2><span class="num"><?= htmlspecialchars($ch['num']) ?></span><?= htmlspecialchars($ch['title']) ?></h2>
                    <?php
                    $file = __DIR__ . '/docs/chapters/' . $ch['file'] . '.php';
                    if (is_file($file)) {
                        include $file;
                    } else {
                        // Registry entry with no file yet — say so plainly rather than
                        // rendering an empty chapter that looks finished.
                        echo '<div class="docs-empty">This chapter is not written yet.</div>';
                    }
                    ?>
                </article>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </main>
</div>

<script>
(function () {
    // ── Sidebar filter ────────────────────────────────────────────────
    var search = document.getElementById('docsSearch');
    var list   = document.getElementById('docsNavList');
    var noMatch= document.getElementById('docsNoMatch');

    search.addEventListener('input', function () {
        var q = this.value.trim().toLowerCase();
        var shown = 0;

        list.querySelectorAll('.docs-link').forEach(function (a) {
            var hit = !q || (a.dataset.search || '').indexOf(q) > -1;
            a.style.display = hit ? '' : 'none';
            var subs = list.querySelector('[data-sections-for="' + a.dataset.target + '"]');
            // Sub-sections stay collapsed while filtering — the chapter is the unit here.
            if (subs) subs.style.display = (hit && !q) ? '' : 'none';
            if (hit) shown++;
        });

        // A part heading with nothing under it is noise.
        list.querySelectorAll('[data-part]').forEach(function (h) {
            var any = false, el = h.nextElementSibling;
            while (el && !el.hasAttribute('data-part')) {
                if (el.classList.contains('docs-link') && el.style.display !== 'none') { any = true; break; }
                el = el.nextElementSibling;
            }
            h.style.display = any ? '' : 'none';
        });

        noMatch.style.display = shown ? 'none' : 'block';
    });

    // ── Scroll-spy ────────────────────────────────────────────────────
    // Deliberately a scroll-position calculation rather than IntersectionObserver:
    // IO callbacks are throttled while a document is hidden (background tab, or an
    // embedded preview pane), which leaves the sidebar highlighting nothing at all.
    // This recomputes from geometry, so it is correct whenever it is asked — and it
    // can be verified directly by dispatching a scroll event.
    //
    // Cost is kept to one rAF-coalesced pass per scroll burst, reading a cached list
    // of offsets, so it does not fight the scroll.
    var links = {};
    document.querySelectorAll('.docs-link, .docs-sections a').forEach(function (a) {
        (links[a.dataset.target] = links[a.dataset.target] || []).push(a);
    });

    var targets = [];
    document.querySelectorAll('.docs-chapter, .docs-chapter h3[id]').forEach(function (el) {
        if (el.id) targets.push(el);
    });

    var nav = document.querySelector('.docs-nav');
    var current = null, ticking = false;

    function spy() {
        ticking = false;
        if (!targets.length) return;

        // The heading nearest the top of the viewport that has not scrolled past it.
        // 120px of slack means a heading counts as "reached" just before it hits the edge.
        var line = 120, best = targets[0], bestTop = -Infinity;
        for (var i = 0; i < targets.length; i++) {
            var t = targets[i].getBoundingClientRect().top;
            if (t <= line && t > bestTop) { bestTop = t; best = targets[i]; }
        }
        // Past the end of the page, the last heading owns the view.
        if ((window.innerHeight + window.scrollY) >= document.body.scrollHeight - 2) {
            best = targets[targets.length - 1];
        }

        if (!best || best.id === current) return;
        current = best.id;

        Object.keys(links).forEach(function (id) {
            links[id].forEach(function (a) { a.classList.toggle('active', id === current); });
        });

        // Keep the active entry inside the sidebar's own scroll area.
        var a = (links[current] || [])[0];
        if (a && nav && nav.scrollHeight > nav.clientHeight) {
            var ar = a.getBoundingClientRect(), nr = nav.getBoundingClientRect();
            if (ar.top < nr.top || ar.bottom > nr.bottom) {
                nav.scrollTop += (ar.top - nr.top) - nr.height / 3;
            }
        }
    }

    function requestSpy() {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(spy);
    }

    window.addEventListener('scroll', requestSpy, { passive: true });
    window.addEventListener('resize', requestSpy);
    window.addEventListener('hashchange', requestSpy);
    // Clicking a link should highlight immediately, not a frame after the scroll lands.
    document.querySelectorAll('.docs-link, .docs-sections a').forEach(function (a) {
        a.addEventListener('click', function () { setTimeout(requestSpy, 0); });
    });

    spy();                       // establish the initial highlight
    window.wccDocsSpy = spy;     // exposed so the layout can be verified in a headless check
})();
</script>

</body>
</html>
