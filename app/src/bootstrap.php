<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$explicitEnvFile = (string)(getenv('APP_ENV_FILE') ?: '');
$shouldLoadDevEnv = $explicitEnvFile === '.env.dev' || PHP_SAPI === 'cli-server';

if ($shouldLoadDevEnv && is_file(dirname(__DIR__) . '/.env.dev')) {
  $dotenvDev = Dotenv::createMutable(dirname(__DIR__), '.env.dev');
  $dotenvDev->safeLoad();
}

$dotenvLocalPath = dirname(__DIR__) . '/.env.local';
if (is_file($dotenvLocalPath)) {
  $dotenvLocal = Dotenv::createMutable(dirname(__DIR__), '.env.local');
  $dotenvLocal->safeLoad();
}

function envget(string $key, ?string $default=null): ?string {
  if (array_key_exists($key, $_ENV)) return $_ENV[$key];
  if (array_key_exists($key, $_SERVER)) return $_SERVER[$key];
  $value = getenv($key);
  return $value !== false ? $value : $default;
}

function security_nonce(): string {
  static $nonce = null;
  if ($nonce === null) {
    $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
  }

  return $nonce;
}

function request_is_https(): bool {
  $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
  if ($https === 'on' || $https === '1') {
    return true;
  }

  return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function send_common_security_headers(): void {
  if (function_exists('header_remove')) {
    header_remove('X-Powered-By');
  }

  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
  if (request_is_https()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
  }
}

function send_html_headers(): void {
  send_common_security_headers();
  header('Content-Type: text/html; charset=utf-8');
  $nonce = security_nonce();
  header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com data:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com https://connect.facebook.net https://www.google.com https://www.gstatic.com; connect-src 'self' https://www.google-analytics.com https://region1.google-analytics.com https://www.google.com https://www.gstatic.com https://connect.facebook.net; frame-src https://www.google.com; form-action 'self'");
}

function send_json_headers(): void {
  send_common_security_headers();
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  header('X-Robots-Tag: noindex');
}

function json_out($data, int $status=200): void {
  http_response_code($status);
  send_json_headers();
  $json = json_encode($data, JSON_UNESCAPED_UNICODE);
  if ($json === false) {
    http_response_code(500);
    echo '{"error":"json_encode_failed"}';
    exit;
  }
  echo $json;
  exit;
}
