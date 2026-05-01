<?php
declare(strict_types=1);

final class Cache {
  private string $dir; private int $ttl;
  public function __construct(string $dir, int $ttlSeconds = 86400) {
    $this->dir = rtrim($dir, '/\\'); $this->ttl = $ttlSeconds;
    if (!is_dir($this->dir)) @mkdir($this->dir, 0775, true);
  }
  private function path(string $key): string { return $this->dir.'/'.sha1($key).'.json'; }
  public function get(string $key): ?array {
    $f = $this->path($key);
    if (!is_file($f)) return null;
    if (filemtime($f) + $this->ttl < time()) return null;
    $j = @file_get_contents($f); if ($j===false) return null;
    return json_decode($j, true) ?: null;
  }
  public function set(string $key, array $value): void {
    $json = json_encode($value, JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents($this->path($key), $json, LOCK_EX) === false) {
      error_log('Cache write failed.');
    }
  }
}
