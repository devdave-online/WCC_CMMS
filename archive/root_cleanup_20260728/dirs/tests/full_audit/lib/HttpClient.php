<?php
/**
 * Minimal cookie-jar HTTP client for CLI audit (curl).
 */
final class WccAuditHttpClient
{
    private string $base;
    private string $cookieFile;
    private int $timeout;
    private ?string $lastBody = null;
    private int $lastStatus = 0;
    /** @var array<string,string> */
    private array $lastHeaders = [];

    public function __construct(string $baseUrl, int $timeout = 20)
    {
        $this->base = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wcc_qa_cookies_' . getmypid() . '.txt';
        if (is_file($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function __destruct()
    {
        if (is_file($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function base(): string
    {
        return $this->base;
    }

    public function cookieFile(): string
    {
        return $this->cookieFile;
    }

    /**
     * Multipart POST (file upload). $fields may contain CURLFile values.
     * @param array<string, mixed> $fields
     */
    public function postMultipart(string $path, array $fields, ?string $csrf = null): array
    {
        $headers = [];
        if ($csrf) {
            $headers['X-CSRF-Token'] = $csrf;
            if (!isset($fields['csrf'])) {
                $fields['csrf'] = $csrf;
            }
        }
        $url = str_starts_with($path, 'http') ? $path : ($this->base . $path);
        $ch = curl_init($url);
        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_POSTFIELDS => $fields,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            $this->lastStatus = 0;
            $this->lastBody = 'CURL_ERROR: ' . $err;
            return ['status' => 0, 'body' => $this->lastBody, 'headers' => []];
        }
        $this->lastStatus = $status;
        $this->lastBody = (string)$raw;
        return ['status' => $status, 'body' => $this->lastBody, 'headers' => []];
    }

    public function lastStatus(): int
    {
        return $this->lastStatus;
    }

    public function lastBody(): string
    {
        return (string)$this->lastBody;
    }

    /**
     * @param array<string,string> $headers
     * @return array{status:int,body:string,headers:array<string,string>}
     */
    public function request(string $method, string $path, ?string $body = null, array $headers = [], bool $follow = true): array
    {
        $url = str_starts_with($path, 'http') ? $path : ($this->base . $path);
        $ch = curl_init($url);
        $headerLines = [];
        foreach ($headers as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }
        $respHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $follow,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_HEADERFUNCTION => static function ($ch, $line) use (&$respHeaders) {
                $len = strlen($line);
                if (str_contains($line, ':')) {
                    [$n, $v] = explode(':', $line, 2);
                    $respHeaders[strtolower(trim($n))] = trim($v);
                }
                return $len;
            },
            CURLOPT_HTTPHEADER     => $headerLines,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $this->lastStatus = 0;
            $this->lastBody = 'CURL_ERROR: ' . $err;
            $this->lastHeaders = [];
            return ['status' => 0, 'body' => $this->lastBody, 'headers' => []];
        }
        $this->lastStatus = $status;
        $this->lastBody = (string)$raw;
        $this->lastHeaders = $respHeaders;
        return ['status' => $status, 'body' => $this->lastBody, 'headers' => $respHeaders];
    }

    public function get(string $path, bool $follow = true): array
    {
        return $this->request('GET', $path, null, [], $follow);
    }

    /**
     * @param array<string,string> $fields
     */
    public function postForm(string $path, array $fields, bool $follow = true): array
    {
        return $this->request('POST', $path, http_build_query($fields), [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ], $follow);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function postJson(string $path, array $data, ?string $csrf = null): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($csrf) {
            $headers['X-CSRF-Token'] = $csrf;
            $data['csrf'] = $data['csrf'] ?? $csrf;
        }
        return $this->request('POST', $path, json_encode($data, JSON_UNESCAPED_UNICODE), $headers, true);
    }

    public function extractCsrf(string $html): ?string
    {
        if (preg_match('/window\.WCC_CSRF\s*=\s*"([^"]+)"/', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }
        if (preg_match("/window\.WCC_CSRF\s*=\s*'([^']+)'/", $html, $m)) {
            return $m[1];
        }
        if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }
        if (preg_match('/name="csrf"\s+value="([^"]+)"/', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }
        // JSON-encoded in head
        if (preg_match('/window\.WCC_CSRF\s*=\s*("(?:\\\\.|[^"\\\\])*")/', $html, $m)) {
            $j = json_decode($m[1]);
            if (is_string($j) && $j !== '') {
                return $j;
            }
        }
        return null;
    }

    public function login(string $user, string $pass): bool
    {
        // Fresh cookie jar
        if (is_file($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
        // Seed session cookie
        $this->get('/login.php', true);
        $r = $this->postForm('/login.php', [
            'username' => $user,
            'password' => $pass,
        ], true);
        if ($r['status'] === 0) {
            return false;
        }
        // Forced password change counts as authenticated for cookie purposes
        $probe = $this->get('/index.php', true);
        if ($probe['status'] >= 500 || $probe['status'] === 0) {
            return false;
        }
        $body = $probe['body'];
        // Login page also emits WCC_CSRF — do NOT treat CSRF alone as auth success.
        $looksLikeLogin = (str_contains($body, 'name="username"') && str_contains($body, 'name="password"'))
            || str_contains($body, 'id="loginPassword"')
            || (str_contains($body, 'login.php') && str_contains($body, 'auth-container'));
        if ($looksLikeLogin && !str_contains($body, 'wcc-sidebar') && !str_contains($body, 'id="wccSidebar"')) {
            return false;
        }
        if ($this->extractCsrf($body) && (str_contains($body, 'wcc-sidebar') || str_contains($body, 'id="wccSidebar"') || str_contains($body, 'wcc-nav-link'))) {
            return true;
        }
        // Redirected to change_password still has a session
        if (str_contains($body, 'must_change') || str_contains($body, 'pw.must_change') || str_contains($body, 'new_password')) {
            $cp = $this->get('/change_password.php', true);
            $cpBody = $cp['body'];
            return $cp['status'] === 200
                && (str_contains($cpBody, 'new_password') || str_contains($cpBody, 'confirm_password'))
                && !str_contains($cpBody, 'name="username"');
        }
        if (str_contains($body, 'Too many failed') || str_contains($body, 'inactive or pending')) {
            return false;
        }
        return $probe['status'] === 200
            && !str_contains($body, 'name="username"')
            && (str_contains($body, 'wcc-sidebar') || str_contains($body, 'id="wccSidebar"'));
    }

    /** True if session can load tooling ledger with CSRF (strict). */
    public function isAuthenticated(): bool
    {
        $probe = $this->get('/_eam/toolings.php', true);
        if ($probe['status'] !== 200) {
            return false;
        }
        if (str_contains($probe['body'], 'name="username"') && str_contains($probe['body'], 'name="password"')) {
            return false;
        }
        return $this->extractCsrf($probe['body']) !== null
            || str_contains($probe['body'], 'id="ledgerTable"')
            || str_contains($probe['body'], 'ledgerTable');
    }
}
