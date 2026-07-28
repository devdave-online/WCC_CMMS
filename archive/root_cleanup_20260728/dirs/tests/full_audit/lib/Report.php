<?php
/**
 * Collect pass/fail/skip and write console + markdown report.
 */
final class WccAuditReport
{
    /** @var list<array{section:string,id:string,status:string,detail:string}> */
    private array $cases = [];
    private string $started;

    public function __construct()
    {
        $this->started = date('c');
    }

    public function ok(string $section, string $id, string $detail = ''): void
    {
        $this->cases[] = ['section' => $section, 'id' => $id, 'status' => 'OK', 'detail' => $detail];
        echo "  OK  [$section] $id" . ($detail !== '' ? " — $detail" : '') . "\n";
    }

    public function fail(string $section, string $id, string $detail = ''): void
    {
        $this->cases[] = ['section' => $section, 'id' => $id, 'status' => 'FAIL', 'detail' => $detail];
        echo " FAIL [$section] $id" . ($detail !== '' ? " — $detail" : '') . "\n";
    }

    public function skip(string $section, string $id, string $detail = ''): void
    {
        $this->cases[] = ['section' => $section, 'id' => $id, 'status' => 'SKIP', 'detail' => $detail];
        echo " SKIP [$section] $id" . ($detail !== '' ? " — $detail" : '') . "\n";
    }

    public function failCount(): int
    {
        return count(array_filter($this->cases, fn($c) => $c['status'] === 'FAIL'));
    }

    public function summaryBySection(): array
    {
        $out = [];
        foreach ($this->cases as $c) {
            $s = $c['section'];
            if (!isset($out[$s])) {
                $out[$s] = ['OK' => 0, 'FAIL' => 0, 'SKIP' => 0];
            }
            $out[$s][$c['status']]++;
        }
        return $out;
    }

    public function writeMarkdown(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $lines = [];
        $lines[] = '# WCC Full Audit Report';
        $lines[] = '';
        $lines[] = '- Started: ' . $this->started;
        $lines[] = '- Finished: ' . date('c');
        $lines[] = '- Failures: ' . $this->failCount();
        $lines[] = '';
        $lines[] = '## Summary by section';
        $lines[] = '';
        $lines[] = '| Section | OK | FAIL | SKIP |';
        $lines[] = '|---------|----:|-----:|-----:|';
        foreach ($this->summaryBySection() as $sec => $counts) {
            $lines[] = '| ' . $sec . ' | ' . $counts['OK'] . ' | ' . $counts['FAIL'] . ' | ' . $counts['SKIP'] . ' |';
        }
        $lines[] = '';
        $lines[] = '## Failures';
        $lines[] = '';
        $fails = array_filter($this->cases, fn($c) => $c['status'] === 'FAIL');
        if (!$fails) {
            $lines[] = '_None._';
        } else {
            foreach ($fails as $c) {
                $lines[] = '- **[' . $c['section'] . ']** `' . $c['id'] . '`: ' . $c['detail'];
            }
        }
        $lines[] = '';
        $lines[] = '## All cases';
        $lines[] = '';
        foreach ($this->cases as $c) {
            $lines[] = '- `' . $c['status'] . '` [' . $c['section'] . '] ' . $c['id']
                . ($c['detail'] !== '' ? ' — ' . str_replace("\n", ' ', $c['detail']) : '');
        }
        $lines[] = '';
        file_put_contents($path, implode("\n", $lines));
    }

    public function writeJson(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, json_encode([
            'started' => $this->started,
            'finished' => date('c'),
            'fail_count' => $this->failCount(),
            'cases' => $this->cases,
            'by_section' => $this->summaryBySection(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
