<p>
    <code>my_profile.php</code> is reachable by <strong>every</strong> logged-in user
    regardless of role — it carries no permission gate, deliberately, because everyone owns
    their own account. It is also the one screen a technician is likely to open by choice
    rather than because a job sent them there.
</p>

<h3 id="my-stats">Your performance dashboard</h3>

<figure class="docs-figure">
    <img src="/img/docs/self_service.png" alt="Self-Service Dashboard">
    <figcaption>Your personal performance stats and recent activity.</figcaption>
</figure>

<div class="table-scroll">
<table>
    <thead><tr><th>Figure</th><th>Counts</th></tr></thead>
    <tbody>
        <tr><td><strong>Interventions</strong></td><td>Closed intervention records filed under your name.</td></tr>
        <tr><td><strong>Avg Wrench Time</strong></td><td>Your mean hands-on repair duration, in minutes.</td></tr>
        <tr><td><strong>Tickets Reported</strong></td><td>Faults you raised.</td></tr>
        <tr><td><strong>Tickets Closed Out</strong></td><td>Faults you signed off.</td></tr>
    </tbody>
</table>
</div>

<p>
    Beneath these sit your <strong>active work orders</strong> — anything Scheduled or In
    Progress assigned to you, with its date — and a <strong>recent activity log</strong> of
    your last five interventions.
</p>

<div class="docs-note">
    <span class="t">Why these numbers can look wrong, and why they no longer are</span>
    Intervention records store a person's <em>name</em> rather than their user ID, and older
    records hold the username where newer ones hold the display name. Every figure here
    therefore matches <strong>both</strong> spellings. A profile that previously showed zero
    interventions for a technician whose work was plainly in the database was this exact
    problem — one spelling was being matched, and half the history was invisible.
</div>

<h3 id="my-skills">Your skills</h3>

<p>
    Both systems appear here, side by side — see <a href="#skills">Skills &amp;
    Proficiencies</a> for how they differ.
</p>

<p>
    <strong>Gamified Proficiencies</strong> shows a chip per mapped equipment category you
    have worked on: tier medal, category icon, proficiency name, hours, and the distance to
    the next tier (<em>"27h to Expert 💎"</em>). The ❓ opens the threshold table. If your
    hours are all on unmapped categories, it says so rather than showing an empty box — the
    fix is an administrator adding the mapping, and the message says that.
</p>

<p>
    <strong>Skills &amp; Certifications</strong> is self-service: add a certification with an
    optional expiry date, or remove one. Expiry state is colour-coded, and warnings arrive as
    notifications at 30, 20, 10, 5 and 3 days before the date.
</p>

<h3 id="my-prefs">Personal preferences</h3>

<div class="table-scroll">
<table>
    <thead><tr><th>Preference</th><th>Scope</th><th>Notes</th></tr></thead>
    <tbody>
        <tr><td><strong>Profile details</strong><br>name, email, phone, department</td><td>Account</td><td>Self-service. Role, status and badge are administrator-only.</td></tr>
        <tr><td><strong>Password</strong></td><td>Account</td><td>Requires the current password. Nobody, including administrators, can read the existing one.</td></tr>
        <tr><td><strong>Session timeout</strong></td><td>Account</td><td>Override the global default with a <em>shorter</em> personal value. Useful on a shared terminal.</td></tr>
        <tr><td><strong>Interface language</strong></td><td>Account</td><td>Stored as <code>users.locale</code>. Thirty-four packs ship with the product; groups are equal (no “high impact” tier). Applies after re-login / session rebuild.</td></tr>
        <tr><td><strong>Theme</strong></td><td>Browser</td><td>Light or dark, from the sidebar footer. Applied before first paint so there is no flash.</td></tr>
        <tr><td><strong>Animated background</strong></td><td>Browser</td><td>Switch the WebGL ribbon off. See below.</td></tr>
    </tbody>
</table>
</div>

<h3 id="my-language">Interface language</h3>

<p>
    Every user can pick their language on <strong>My Profile</strong>. The value is stored
    on the account (<code>users.locale</code>), not only in the browser, so a shared terminal
    still shows the right pack after the next login. Packs live under <code>lang/*.json</code>
    and share the same key set as English (747 keys at soft launch). Incomplete packs are not
    used for day-one UI groups — all shipped locales are full parity with <code>en</code>.
</p>

<p>
    Language groups in the picker are named by region only (for example
    <em>South &amp; Southeast Asia</em>, <em>Europe &amp; Americas</em>). There is no
    “high impact” ranking; every language is treated equally in the UI.
</p>

<div class="docs-note">
    <span class="t">Turning off the animated background</span>
    <em>My Profile → Visual Preferences.</em> The setting is stored per browser
    (<code>localStorage</code>), not on the account — deliberately, because it is about the
    machine you are sitting at, not who you are. An ageing shop-floor PC can run without it
    while the same user gets the full effect on their office desktop. With it off, no canvas
    is created at all, so it costs nothing.
    <br><br>
    It also disables itself when the tab is hidden, honours
    <code>prefers-reduced-motion</code>, and never appears on hardware without WebGL.
</div>

<div class="docs-note warn">
    <span class="t">Personal timeout only shortens</span>
    You can set a stricter idle timeout than the plant default, not a longer one. If your
    administrator sets 30 minutes, you cannot give yourself four hours — the shared-terminal
    risk is the reason the global setting exists.
</div>
