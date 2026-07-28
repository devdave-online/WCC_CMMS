<p>
    WCC has no framework, no router and no autoloader. A URL maps to a file on disk, that
    file includes what it needs, and it prints HTML. If you can read PHP you can read this
    codebase without learning anything else first — which is the point, because the people
    most likely to maintain a plant's CMMS are plant engineers, not full-time web developers.
</p>

<h3 id="folder-map">Folder map</h3>

<pre><code>htdocs/
├── index.php  login.php  register.php  my_profile.php   entry points
├── auth.php   rbac.php   nav.php                        shared shell
├── docs.php                                             this manual
│
├── inc/          shared logic — no HTML, no output
├── api/          JSON endpoints used by the pages
│   └── v1/       versioned REST API for machines and the companion app
│
├── _maint/       maintenance operations   (7 pages)  tickets, work orders, PM
├── _logi/        logistics & procurement  (9 pages)  inventory, POs, vendors
├── _mgmt/        management & admin       (6 pages)  users, settings, data admin
├── _eam/         asset management         (5 pages)  equipment, labels
├── _rpt/         reporting                (3 pages)  KPIs, history
├── _prod/        production topology      (1 page)   lines
├── _trck/        tracking                 (1 page)   PO stepper
│
├── css/  js/  img/  uploads/  backups/  migrations/  demo/
└── archive/      retired scripts, kept out of the served surface</code></pre>

<h3 id="module-domains">Why underscore modules</h3>

<p>
    The leading underscore is not decoration. It groups the domain folders together at the
    top of any directory listing, and — more usefully — it makes the domain visible in every
    URL and every include path. <code>/_logi/purchase_orders.php</code> tells you which part
    of the business you are in before you open anything.
</p>

<p>
    Modules are organised by <strong>business domain</strong>, not by technical layer. There
    is no <code>controllers/</code> or <code>models/</code>, because a maintenance engineer
    debugging the purchase-order screen wants every part of that screen in one place, not
    scattered across three layers by a convention they did not choose.
</p>

<p>
    <code>_qual/</code>, <code>_cmms/</code>, <code>_erp/</code> and <code>_mes/</code> exist
    but are empty — reserved for future domains so the naming does not have to be
    retrofitted later.
</p>

<div class="docs-note">
    <span class="t">Dependency direction</span>
    Modules depend on <code>inc/</code> and on each other's <em>data</em>, never on each
    other's pages. <code>_maint</code> reads equipment (<code>_eam</code>) and parts
    (<code>_logi</code>); <code>_rpt</code> reads from nearly everything. No module includes
    a page from another module, so there are no circular includes to untangle.
</div>

<h3 id="page-anatomy">Anatomy of a page</h3>

<p>Every protected page follows the same five-step shape. Once you have read one, you
    have read all of them:</p>

<pre><code>&lt;?php
include __DIR__ . '/../auth.php';            // 1. session + login gate
require_once __DIR__ . '/../rbac.php';
require_perm('view_work_orders');            // 2. permission gate — server-side

require_once __DIR__ . '/../inc/db.php';     // 3. dependencies
$pdo = get_wcc_db_connection();

try {                                        // 4. all queries up front
    $items = $pdo->query("SELECT ...")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { wcc_user_error("Unable to load work orders.", $e-&gt;getMessage()); }

$page_title = 'Work Orders';                 // 5. then render
require_once __DIR__ . '/../inc/head.php';
?&gt;
&lt;?php include __DIR__ . '/../nav.php'; ?&gt;
... HTML ...</code></pre>

<p>
    Data is fetched <strong>before</strong> any output. That ordering is deliberate: it means
    a database failure can still redirect or render an error page, because nothing has been
    sent to the browser yet.
</p>

<div class="docs-note warn">
    <span class="t">The permission gate is the security boundary</span>
    <code>require_perm()</code> at the top of the file is what actually protects the page.
    The sidebar hides links the user cannot use, but that is a courtesy — typing the URL
    directly hits the same gate. Any new page without a <code>require_perm()</code> call is
    public to every logged-in user, whatever the navigation shows.
</div>
