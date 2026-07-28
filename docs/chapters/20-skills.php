<p>
    WCC tracks competence two ways, and they are frequently confused because they sit side
    by side in the same column. Understanding that they are separate systems is the whole
    chapter.
</p>

<h3 id="two-systems">Two separate systems</h3>

<div class="table-scroll">
<table>
    <thead><tr><th></th><th>🏆 Gamified Proficiencies</th><th>🛠️ Manual Skills</th></tr></thead>
    <tbody>
        <tr><td><strong>Origin</strong></td><td>Earned automatically from logged work</td><td>Granted by an administrator</td></tr>
        <tr><td><strong>Basis</strong></td><td>Hours on an equipment category</td><td>A certificate, licence or course</td></tr>
        <tr><td><strong>Has tiers</strong></td><td>Yes — six levels</td><td>No — you hold it or you do not</td></tr>
        <tr><td><strong>Expires</strong></td><td>Never</td><td>Optionally, with warnings</td></tr>
        <tr><td><strong>Answers</strong></td><td>"Who has actually worked on this kind of machine?"</td><td>"Who is <em>allowed</em> to do this?"</td></tr>
        <tr><td><strong>Stored in</strong></td><td>Computed live — nothing stored</td><td><code>user_skills</code></td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">Why both exist</span>
    They answer different questions and neither substitutes for the other. A technician with
    200 hours on machining is demonstrably experienced — but if their LOTO authorisation
    lapsed last week they must not isolate that machine. Conversely a valid certificate says
    nothing about whether someone has ever touched a thermoformer.
</div>

<h3 id="proficiency-earn">How proficiencies are earned</h3>

<p>
    Nobody awards a proficiency. When a technician closes an intervention, the time between
    taking the job over and finishing it is added to the <strong>equipment category</strong>
    of the machine worked on. Cross an hour threshold and the tier rises by itself.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Tier</th><th>Logged hours</th><th>Means</th></tr></thead>
    <tbody>
        <tr><td>👑 Master</td><td>200 h +</td><td>Deep specialist — the person you wake at 3am for this equipment.</td></tr>
        <tr><td>💎 Expert</td><td>100 h +</td><td>Handles the hard faults on this category unaided.</td></tr>
        <tr><td>🥇 Proficient</td><td>40 h +</td><td>Comfortable across routine and most non-routine work.</td></tr>
        <tr><td>🥈 Competent</td><td>20 h +</td><td>Works unsupervised on standard faults.</td></tr>
        <tr><td>🥉 Advanced</td><td>10 h +</td><td>Past the basics, still building depth.</td></tr>
        <tr><td>🌱 Novice</td><td>under 10 h</td><td>Getting started on this category.</td></tr>
    </tbody>
</table>
</div>

<p>The rules that decide what counts:</p>

<ul>
    <li>Only <strong>closed</strong> interventions with both a start and an end time. Open
        jobs count for nothing until closed out.</li>
    <li>Tiers are <strong>per equipment category</strong>, not overall — someone can be
        Master on Machining and Novice on Packaging simultaneously.</li>
    <li>A category only scores if it is <strong>mapped in the Skill Configurator</strong>.
        Unmapped categories earn nothing however much work is done on them.</li>
    <li>Nothing decays. Hours accumulate for as long as the history is kept.</li>
</ul>

<p>
    A chip shows the tier medal, the category icon, the proficiency name, the category, the
    hours, and how far to the next tier — <em>"27h to Expert 💎"</em>. The ❓ beside the
    heading opens the full threshold table, generated from the same values the code scores
    with, so the explanation cannot drift from the behaviour.
</p>

<p>Proficiencies appear in four places, rendered identically: your own profile, the Users
    Directory detail panel, the User Management detail panel, and the 🏆 badge popover.</p>

<h3 id="skill-config">The Skill Configurator</h3>

<figure class="docs-figure">
    <img src="/img/docs/skills_config.png" alt="Skills Configurator">
    <figcaption>Mapping equipment categories to gamified proficiencies.</figcaption>
</figure>

<p>
    Reached from User Management, the configurator maps an <strong>equipment category</strong>
    to a proficiency name and an icon — for example <code>Machining → ⚙️ Machining
    Specialist</code>.
</p>

<div class="docs-note warn">
    <span class="t">The configurator is an allow-list</span>
    A category that is not mapped here <strong>earns nothing</strong>, no matter how many
    hours are logged against it. The category name must also match the equipment record
    <strong>exactly</strong> — a mapping for "Conveyors" scores zero if your assets are
    categorised as "Conveyance". After adding an equipment category, add its mapping too, or
    that work becomes invisible.
</div>

<h3 id="manual-certs">Certifications and expiry</h3>

<p>
    Manual skills are free text — whatever your plant actually requires: <em>LOTO Authorised
    Person</em>, <em>Working at Height</em>, <em>KUKA Robot Programming</em>, <em>Forklift
    Licence B</em>. Each may carry an optional expiry date; leave it blank for something that
    does not expire.
</p>

<p>Two ways to add one:</p>

<ul>
    <li><strong>Self-service</strong> — My Profile → Skills &amp; Certifications. Name plus
        optional expiry date.</li>
    <li><strong>Administrator</strong> — User Management → expand the user → Manual Skills →
        <em>Add New Skill</em>, with its own date field per certification.</li>
</ul>

<p>Expiry state is shown wherever a certification appears:</p>

<div class="table-scroll">
<table>
    <thead><tr><th>State</th><th>Appearance</th></tr></thead>
    <tbody>
        <tr><td>No expiry</td><td>Plain chip.</td></tr>
        <tr><td>Valid</td><td>Green — "Valid until 7 Jun 2027".</td></tr>
        <tr><td>Expiring within 30 days</td><td>Amber ⚠️ — "Expires in 15d".</td></tr>
        <tr><td>Expired</td><td>Red ⛔, name struck through — "Expired 5d ago".</td></tr>
    </tbody>
</table>
</div>

<p>
    Warnings are also pushed as notifications at <strong>30, 20, 10, 5 and 3 days</strong>,
    and again on the day it lapses — at which point the holder's managers are told too. See
    <a href="#notif-expiry">Notifications</a> for the scheduled job that drives this.
</p>

<div class="docs-note">
    <span class="t">Renewing a certification</span>
    To renew, add the certification again with the new expiry date and remove the old entry —
    a quick two-step that also leaves the lapsed one visible until you clear it.
</div>
