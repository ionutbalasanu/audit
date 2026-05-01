<?php
declare(strict_types=1);

final class RateLimiter {
  private string $dir;
  private int $limit;
  public function __construct(string $dir, int $limitPerDay = 200) {
    $this->dir = rtrim($dir, '/\\');
    $this->limit = $limitPerDay;
    if (!is_dir($this->dir) && !mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
      throw new \RuntimeException('Rate limit storage is not writable.');
    }
    $this->maybeCleanup();
  }
  private function keyPath(string $key): string {
    $day = gmdate('Ymd'); // reset la 00:00 UTC
    return "{$this->dir}/{$day}_" . sha1($key) . ".cnt";
  }
  public function hit(string $key): array {
    $f = $this->keyPath($key);
    $fh = fopen($f, 'c+');
    if (!$fh) {
      throw new \RuntimeException('Rate limit counter is not writable.');
    }

    try {
      if (!flock($fh, LOCK_EX)) {
        throw new \RuntimeException('Rate limit counter cannot be locked.');
      }
      rewind($fh);
      $raw = stream_get_contents($fh);
      $n = (int)($raw === false || trim($raw) === '' ? 0 : $raw);
      $n++;
      ftruncate($fh, 0);
      rewind($fh);
      fwrite($fh, (string)$n);
      fflush($fh);
      flock($fh, LOCK_UN);
    } finally {
      fclose($fh);
    }

    return ['count'=>$n, 'limit'=>$this->limit, 'allowed' => $n <= $this->limit];
  }

  private function maybeCleanup(): void {
    try {
      if (random_int(1, 200) !== 1) return;
    } catch (\Throwable $e) {
      return;
    }

    $days = max(1, (int) envget('RATE_LIMIT_RETENTION_DAYS', '2'));
    $cutoff = gmdate('Ymd', time() - ($days * 86400));
    foreach (glob($this->dir . '/*.cnt') ?: [] as $f) {
      $day = substr(basename($f), 0, 8);
      if (preg_match('/^\d{8}$/', $day) && $day < $cutoff) {
        @unlink($f);
      }
    }
  }
}
