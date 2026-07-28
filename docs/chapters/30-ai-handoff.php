<p>
    WCC is designed to be <em>handed to</em> an AI AGENT.
    A context layer at the project root lets any AI AGENT
    acquire the same understanding of the codebase in one read, rather than rediscovering it
    file by file and guessing at the conventions in between.
</p>

<p>
    This matters beyond novelty. Agents that infer conventions from whatever file they happen
    to open will invent a second way of doing everything. The context layer exists to make
    the project's actual decisions <strong>explicit and unmissable</strong>.
</p>

<h3 id="ai-bootstrap">The bootstrap file</h3>

<p>
    <code>ai_agent.ini</code> sits in the project root and is the entry point. It is plain
    INI — readable by a human, parseable by anything — and deliberately not specific to any
    one agent product.
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Section</th><th>Tells the agent</th></tr></thead>
    <tbody>
        <tr><td><code>[project]</code></td><td>Name, version, description, tech stack, creation date.</td></tr>
        <tr><td><code>[initialization]</code></td><td><strong>What to read first, and in what order.</strong> The single most important section.</td></tr>
        <tr><td><code>[context_layer]</code></td><td>Where the deep context lives and how to regenerate it.</td></tr>
        <tr><td><code>[architecture]</code></td><td>Folder conventions, module list, shared files.</td></tr>
        <tr><td><code>[rbac]</code></td><td>Permission count, role meanings, where authority lives, the procurement duty split.</td></tr>
        <tr><td><code>[data_model]</code></td><td>Schema sources, latest migration, <strong>and the known drift warnings</strong>.</td></tr>
        <tr><td><code>[styling]</code></td><td>Token tiers, theming, cache-busting rule, what was removed and must not be resurrected.</td></tr>
        <tr><td><code>[conventions]</code></td><td>Include style, path style, permission helpers, documentation duty.</td></tr>
        <tr><td><code>[rules_for_agents]</code></td><td>Six numbered standing instructions.</td></tr>
        <tr><td><code>[how_to_use_with_multiple_agents]</code></td><td>Running several agent sessions against one project without them diverging.</td></tr>
    </tbody>
</table>
</div>

<div class="docs-note">
    <span class="t">The most valuable entries are the warnings</span>
    <code>[data_model] caution</code> states that the live database is authoritative over
    <code>schema.sql</code> and names a migration that can never apply. <code>[styling]
    removed</code> records that Theme Lab was deleted and that a dormant column must not be
    revived without review. These stop an agent from confidently rebuilding something that
    was removed on purpose, or from trusting a file that has drifted — the two most expensive
    mistakes a fresh agent makes.
</div>

<h3 id="ai-context">The context folder</h3>

<p>
    <code>_ai_ctxt/</code> holds the depth that will not fit in an INI file. Each document
    has one job:
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>File</th><th>Contains</th></tr></thead>
    <tbody>
        <tr><td><code>AGENT_INSTRUCTIONS.md</code></td><td>Standing instructions — read before touching anything.</td></tr>
        <tr><td><code>OVERVIEW.md</code></td><td>What the product is and who uses it.</td></tr>
        <tr><td><code>ARCHITECTURE.md</code></td><td>Folder structure, shared infrastructure, dependency direction.</td></tr>
        <tr><td><code>DATA_MODEL.md</code></td><td>Tables and relationships. <strong>Generated</strong> — see below.</td></tr>
        <tr><td><code>KEY_FLOWS.md</code></td><td>Ticket lifecycle, work orders, procurement, inventory, notifications.</td></tr>
        <tr><td><code>CONVENTIONS.md</code></td><td>Code style, naming, the design-system rules.</td></tr>
        <tr><td><code>REST_API.md</code></td><td>API surface summary.</td></tr>
        <tr><td><code>context.json</code> · <code>manifest.json</code></td><td>Machine-readable equivalents for agents that prefer structured input.</td></tr>
    </tbody>
</table>
</div>

<p>
    <code>AGENTS.md</code> in the project root is the short front door — the file several
    agent tools look for by convention. It points at everything above.
</p>

<div class="docs-note warn">
    <span class="t">Not served over HTTP</span>
    <code>_ai_ctxt/</code> carries a deny-all <code>.htaccess</code>. Its contents are
    architecture notes for whoever is working on the code, not application pages, and a full
    description of your system's internals is exactly what you do not hand to an anonymous
    visitor. Agents read these from disk, not over the network.
</div>

<h3 id="ai-generator">Keeping context fresh</h3>

<p>Two scripts maintain the layer:</p>

<pre><code>php _ai_ctxt/generate-context.php          # refresh DATA_MODEL.md from the schema
php _ai_ctxt/generate-context.php --live   # include live row counts and samples
php _ai_ctxt/print-init-summary.php        # copy-pasteable briefing for a new session</code></pre>

<p>
    <strong>Run the generator after any schema change or significant refactor.</strong> Stale
    context is worse than none: an agent trusts it, acts on it, and produces work that
    matches a system you no longer have.
</p>

<p>
    For live figures without reading files at all, the REST API exposes the same material:
    <code>GET /api/v1/ai-context</code>, optionally with <code>?section=DATA_MODEL</code> or
    <code>?live=1</code>.
</p>

<h3 id="ai-handoff">Handing the project to a new agent</h3>

<ol>
    <li>Point the agent at the project root and have it read <code>ai_agent.ini</code>.</li>
    <li>Follow <code>[initialization] read_order</code> — <code>AGENTS.md</code>, then
        <code>AGENT_INSTRUCTIONS.md</code>, then the rest of <code>_ai_ctxt/</code>.</li>
    <li>Run <code>print-init-summary.php</code> for a condensed briefing to paste into a
        session.</li>
    <li>Re-run the generator first if the schema has moved since the files were last written.</li>
</ol>

<p>
    For several agents working the same project simultaneously, each loads the same
    <code>ai_agent.ini</code>, so all start from identical context. Machine-specific settings
    go in an untracked <code>ai_agent.local.ini</code> loaded after the main file.
</p>

<div class="docs-note">
    <span class="t">Working style for multiple agents</span>
    When more than one agent works the same project, keep implementation aligned as well as
    understanding: communicate changes, and prefer the shared helpers in <code>inc/</code>
    over writing a new copy of a rule that already exists. The context layer keeps everyone's
    <em>mental model</em> in sync; these habits keep the <em>code</em> in sync.
</div>

<div class="docs-note">
    <span class="t">Keep the facts current</span>
    <code>ai_agent.ini</code> holds a few hand-maintained facts — version number, latest
    migration, permission count — that no script regenerates. Update them in the same edit as
    the change they describe, along with the affected <code>_ai_ctxt/</code> documents, so the
    next agent starts from an accurate picture.
</div>
