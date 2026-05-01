<?php
declare(strict_types=1);

final class ReportStore
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/\\');
        if (!is_dir($this->dir) && !mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
            throw new \RuntimeException('Report storage is not writable.');
        }
        $this->maybeCleanup();
    }

    public function create(array $snapshot): string
    {
        $token = (string)($snapshot['report_token'] ?? bin2hex(random_bytes(16)));
        $snapshot['report_token'] = $token;
        $snapshot['created_at'] = (string)($snapshot['created_at'] ?? gmdate(DATE_ATOM));
        if (isset($snapshot['score']) && is_array($snapshot['score'])) {
            $snapshot['score']['report_token'] = $token;
        }

        $written = file_put_contents(
            $this->pathFor($token),
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
        if ($written === false) {
            throw new \RuntimeException('Report storage is not writable.');
        }

        return $token;
    }

    public function get(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        $path = $this->pathFor($token);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $json = json_decode($raw, true);
        return is_array($json) ? $json : null;
    }

    private function pathFor(string $token): string
    {
        return $this->dir . '/' . $token . '.json';
    }

    private function maybeCleanup(): void
    {
        try {
            if (random_int(1, 200) !== 1) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $days = max(1, (int) envget('REPORT_RETENTION_DAYS', '30'));
        $cutoff = time() - ($days * 86400);
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            if (is_file($file) && (filemtime($file) ?: time()) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
