<?php
declare(strict_types=1);

final class LeadRepository
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/\\');
        if (!is_dir($this->dir) && !mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
            throw new \RuntimeException('Lead storage is not writable.');
        }
        $this->maybeCleanup();
    }

    public function save(array $payload): string
    {
        $name = gmdate('Y-m-d') . '-' . substr(sha1(json_encode($payload) . microtime(true)), 0, 16) . '.json';
        $path = $this->dir . '/' . $name;

        $written = file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
        if ($written === false) {
            throw new \RuntimeException('Lead storage is not writable.');
        }

        return $path;
    }

    public static function isValidPhone(string $phone): bool
    {
        $sanitized = preg_replace('/[^\d+]/', '', $phone);
        if (!is_string($sanitized) || $sanitized === '') {
            return false;
        }

        $ro = preg_match('/^(?:\+?40|0)?7\d{8}$/', $sanitized) === 1;
        $intl = preg_match('/^\+?[1-9]\d{7,14}$/', $sanitized) === 1;

        return $ro || $intl;
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

        $days = max(1, (int) envget('LEAD_RETENTION_DAYS', '180'));
        $cutoff = time() - ($days * 86400);
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            if (is_file($file) && (filemtime($file) ?: time()) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
