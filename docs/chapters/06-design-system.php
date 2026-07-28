<p>
    There is one stylesheet, <code>css/global.css</code>, and one behaviour script,
    <code>js/wcc-ui.js</code>. No CSS framework, no build step, no utility classes. Pages
    style themselves by using design tokens, so a theme change is a variable change rather
    than a search-and-replace across a hundred files.
</p>

<h3 id="tokens">Tokens</h3>

<p>
    Every colour, spacing step, radius and shadow is a CSS custom property defined once on
    <code>:root</code> and redefined under <code>.light-theme</code>. Components never name a
    literal colour.
</p>

<pre><code>--text-primary  --text-secondary  --text-muted  --text-accent
--panel-bg      --panel-border    --surface-1   --modal-bg
--danger        --warning         --success     --info
--space-1 … --space-8     --radius-sm/md/lg/xl     --shadow-1/2/3
--fs-xs --fs-sm ...</code></pre>

<p>
    Using a token instead of a literal is what makes light mode work at all. A panel written
    as <code>background: var(--panel-bg)</code> follows the theme; the same panel written as
    <code>background: #1e293b</code> becomes a dark slab on a white page. That exact bug has
    appeared more than once — most recently in a KPI drill-down popup that hardcoded a navy
    background and pale text.
</p>

<h3 id="components">Components</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Class</th><th>Use</th></tr></thead>
    <tbody>
        <tr><td><code>.dashboard-container</code></td><td>The main page panel. Centred, capped width, blurred backdrop.</td></tr>
        <tr><td><code>.data-table</code> + <code>.table-wrap</code></td><td>Tables. The wrapper carries <code>overflow-x</code> so wide content scrolls inside its own box rather than widening the page.</td></tr>
        <tr><td><code>.parent-row</code> / <code>.child-row</code></td><td>Expandable table rows — the master/detail pattern used across the app.</td></tr>
        <tr><td><code>.pill-btn</code></td><td>The standard button: pill-shaped, tinted background, coloured text. Variants <code>pill-success</code>, <code>pill-warning</code>, <code>pill-danger</code>, <code>pill-info</code>, <code>pill-sm</code>, <code>pill-block</code>.</td></tr>
        <tr><td><code>.modal</code> + <code>.modal-content</code></td><td>Overlays. See the warning below before setting a width.</td></tr>
        <tr><td><code>.wcc-empty</code></td><td>Empty states — an explicit "nothing here" rather than a blank region.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">Modal widths</span>
    <code>.modal-content</code> is now viewport-relative (<code>width: 94vw</code>) capped by
    <code>max-width</code>, which defaults to a comfortable <strong>460px</strong>. To make a
    modal wider, just raise its <code>max-width</code> — e.g. <code>style="max-width:760px"</code>
    gives an 760px modal that still shrinks on small screens. (Historically the base carried a
    fixed <code>width: 400px</code>, so <code>max-width</code> was ignored and modals crammed at
    400px; that trap is gone.) For a hard width you can still set
    <code>width: min(760px, 94vw)</code>.
</div>

<p>
    <code>js/wcc-ui.js</code> supplies the small amount of shared behaviour:
    <code>openWccModal()</code> / <code>closeWccModal()</code>, <code>showToast()</code>,
    <code>openWccConfirm()</code> for destructive confirmations, and
    <code>toggleTheme()</code>.
</p>

<h3 id="theming">Light and dark</h3>

<p>
    The theme is a single class, <code>light-theme</code>, on <code>&lt;html&gt;</code>. The
    choice persists in <code>localStorage</code> and is applied by a small inline script in
    <code>inc/head.php</code> <strong>before the stylesheet loads</strong> — without that,
    every page would flash the default theme before correcting itself. Switching dispatches
    a <code>wcc:themechange</code> event so components holding their own canvas or colours
    can react.
</p>

<div class="docs-note">
    <span class="t">Cache busting</span>
    CSS and JS are requested with <code>?v=</code> and the constant
    <code>WCC_UI_VERSION</code> from <code>inc/version.php</code>. <strong>Bump it whenever
    you change a shared asset</strong>, otherwise returning users keep the cached copy. It is
    unrelated to the application version in <code>version.json</code> — that one is the
    product's, this one is the browser's.
</div>

<h3 id="wave">The animated background</h3>

<p>
    <code>js/xmb-wave.js</code> draws three slow "silk ribbon" waves in the accent colours on
    a WebGL canvas behind all content. It is presentation only — nothing depends on it.
</p>

<p>Its constraints are worth knowing because they are the reason it is not a performance problem:</p>

<ul>
    <li>Renders at <strong>60% resolution</strong>, capped at <strong>24 fps</strong>.</li>
    <li><strong>Pauses entirely when the tab is hidden.</strong></li>
    <li>Honours <code>prefers-reduced-motion</code> by drawing one static frame.</li>
    <li>If WebGL is unavailable it simply never appears.</li>
    <li>Users can switch it off in <em>My Profile → Visual Preferences</em>; the preference
        is per-browser (<code>localStorage</code>) so someone on an old shop-floor PC can
        disable it without affecting anyone else.</li>
</ul>

<p>
    One subtlety worth recording: because WCC is a multi-page application, every navigation
    is a fresh document, and the animation clock would restart from zero on each one — the
    ribbon visibly snapping back to its opening shape on every menu click. The elapsed time
    is therefore carried in <code>sessionStorage</code> and resumed, so motion is continuous
    across pages. It is per-tab by design, so two open tabs each keep their own unbroken
    ribbon rather than fighting over one shared clock.
</p>
