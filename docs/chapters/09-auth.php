<p>
    Authentication answers "who is this?". Authorisation — covered in
    <a href="#rbac">Roles &amp; Permissions</a> — answers "what may they do?". This chapter
    is the first question only.
</p>

<h3 id="login-flow">The login flow</h3>

<ol>
    <li>
        <strong>Identify.</strong> A user signs in with either their username or their
        <strong>badge number</strong>. The badge is the shop-floor identifier printed on an
        ID card, which matters on a plant where people know each other by badge, and keeps
        personal names off shared terminals.
    </li>
    <li>
        <strong>Throttle check, before the password is examined.</strong> If this IP has
        already failed too many times, the attempt is rejected without testing the
        credential — so a locked-out attacker cannot keep trying candidates.
    </li>
    <li>
        <strong>Verify.</strong> <code>password_verify()</code> against a bcrypt hash. Plain
        passwords are never stored, logged, or written to a backup in recoverable form.
    </li>
    <li>
        <strong>Regenerate the session ID.</strong> A session that existed before login must
        never become an authenticated one — see below.
    </li>
    <li>
        <strong>Cache identity and rights.</strong> User ID, username, display name, badge,
        role level and the resolved permission set go into the session, so subsequent pages
        do not re-query on every permission check.
    </li>
</ol>

<div class="docs-note">
    <span class="t">Failure messages are deliberately identical</span>
    "Invalid username or password" is returned whether the account does not exist or the
    password was wrong. Distinguishing them would let an attacker enumerate valid usernames
    — turning one unknown into two, and halving the work of a targeted attack.
</div>

<h3 id="sessions">Session handling</h3>

<p>
    Every entry point starts its session through <code>inc/session.php</code>, never a raw
    <code>session_start()</code>. That file sets the cookie parameters <em>before</em> the
    session begins, which is the only point at which they can be set:
</p>

<div class="table-scroll">
<table>
    <thead><tr><th>Setting</th><th>Value</th><th>Why</th></tr></thead>
    <tbody>
        <tr><td><code>HttpOnly</code></td><td>on</td><td>JavaScript cannot read the cookie, so an XSS flaw cannot steal the session.</td></tr>
        <tr><td><code>SameSite</code></td><td><code>Lax</code></td><td>Blocks cross-site POST (CSRF) while leaving ordinary links working.</td></tr>
        <tr><td><code>Secure</code></td><td>only under TLS</td><td>Set automatically when the request is HTTPS. Not forced, because a shop-floor intranet frequently has no TLS and a hard flag would break login there entirely.</td></tr>
        <tr><td><code>use_strict_mode</code></td><td>on</td><td>Rejects a session ID the server never issued — the other half of fixation defence.</td></tr>
    </tbody>
</table>
</div>

<p>
    Configuring this in code rather than <code>php.ini</code> is deliberate: the hardening
    then ships with the application. A deploy to a different host, or a reinstalled PHP,
    cannot silently lose it.
</p>

<p>
    Idle timeout is configurable globally (<em>Admin Panel → System Settings</em>) and each
    user may set a shorter personal timeout in their profile.
</p>

<h3 id="brute-force">Brute-force defence</h3>

<p>
    Failed logins are counted per IP in a fixed window: <strong>10 failures in 15
    minutes</strong> locks further attempts from that address until the window rolls over. A
    successful login clears the counter, so someone who mistypes twice and then succeeds
    never accumulates toward a lockout.
</p>

<div class="docs-note">
    <span class="t">It fails open, on purpose</span>
    If the database is unreachable, the throttle allows the attempt rather than blocking it.
    This is a password-guessing deterrent, not an access control — and a technician locked
    out of a shop-floor terminal at 3am because a counter table was unavailable is a worse
    outcome than the attack it would have prevented.
</div>

<h3 id="passwords">Password policy</h3>

<ul>
    <li>Hashed with <code>PASSWORD_DEFAULT</code> (bcrypt), never stored reversibly.</li>
    <li>A change is forced when <code>must_change_password</code> is set on the account, or
        when the password is still the seeded default. The database flag is authoritative —
        it cannot be bypassed by manipulating the client.</li>
    <li>Administrators with <code>reset_passwords</code> can reset another user's password;
        they cannot read the existing one, because nobody can.</li>
    <li>Self-service change lives in My Profile and requires the current password.</li>
</ul>

<div class="docs-note">
    <span class="t">First-run convenience</span>
    On a brand-new installation with no users, the login page seeds a starter
    <code>admin</code> account so you can get straight in — and immediately requires you to
    set your own password before anything else. A clean, guided first login with no manual
    account creation needed.
</div>
