<?php
declare(strict_types=1);

final class ReportAccess
{
    private const COOKIE_PREFIX = 'nova_audit_unlock_';
    private const COOKIE_TTL = 2592000;
    private const SESSION_COOKIE = 'nova_audit_unlock_session';

    public static function isUnlocked(string $token): bool
    {
        if (!self::isValidToken($token)) {
            return false;
        }

        return self::hasTokenUnlock($token);
    }

    public static function accessToken(string $token): string
    {
        if (!self::isValidToken($token) || self::signingKey() === null) {
            return '';
        }

        return self::accessSignature($token);
    }

    public static function unlockWithAccessToken(string $token, string $accessToken): bool
    {
        if (!self::isValidToken($token)) {
            return false;
        }

        $provided = strtolower(trim($accessToken));
        if (!preg_match('/^[a-f0-9]{64}$/', $provided)) {
            return false;
        }

        if (!hash_equals(self::accessSignature($token), $provided)) {
            return false;
        }

        return self::unlock($token);
    }

    public static function hasSessionUnlock(): bool
    {
        return false;
    }

    public static function unlockSession(): void
    {
        // Session-wide unlocks are intentionally disabled; each report token must
        // have its own signed cookie.
    }

    public static function unlock(string $token): bool
    {
        if (!self::isValidToken($token)) {
            return false;
        }

        if (self::signingKey() === null) {
            error_log('REPORT_ACCESS_KEY missing; report unlock cookie was not issued.');
            return false;
        }

        $cookieName = self::cookieName($token);
        $cookieValue = self::signature($token);
        $options = self::cookieOptions();
        $options['expires'] = time() + self::COOKIE_TTL;

        setcookie($cookieName, $cookieValue, $options);

        $_COOKIE[$cookieName] = $cookieValue;
        return true;
    }

    private static function hasTokenUnlock(string $token): bool
    {
        $cookieName = self::cookieName($token);
        $provided = (string)($_COOKIE[$cookieName] ?? '');
        if ($provided === '') {
            return false;
        }

        return hash_equals(self::signature($token), $provided);
    }

    private static function cookieName(string $token): string
    {
        return self::COOKIE_PREFIX . $token;
    }

    private static function signature(string $token): string
    {
        $key = self::signingKey();
        if ($key === null) {
            return '';
        }

        return hash_hmac('sha256', $token, $key);
    }

    private static function accessSignature(string $token): string
    {
        $key = self::signingKey();
        if ($key === null) {
            return '';
        }

        return hash_hmac('sha256', 'email-access:' . $token, $key);
    }

    private static function sessionSignature(): string
    {
        $key = self::signingKey();
        if ($key === null) {
            return '';
        }

        return hash_hmac('sha256', 'session', $key);
    }

    private static function cookieOptions(): array
    {
        return [
            'path' => Config::basePath() !== '' ? Config::basePath() : '/',
            'secure' => self::isSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    private static function signingKey(): ?string
    {
        $configured = class_exists('Config')
            ? Config::sensitiveEnv('REPORT_ACCESS_KEY')
            : trim((string) envget('REPORT_ACCESS_KEY', ''));
        if ($configured !== '') {
            if (strlen($configured) >= 32) {
                return $configured;
            }

            error_log('REPORT_ACCESS_KEY is too short; falling back to storage key.');
        }

        return self::storageSigningKey();
    }

    private static function storageSigningKey(): ?string
    {
        $dir = class_exists('Config') ? Config::storagePath() : __DIR__ . '/../storage';
        $path = $dir . '/report-access.key';

        if (is_file($path)) {
            $key = trim((string) @file_get_contents($path));
            return strlen($key) >= 64 ? $key : null;
        }

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            error_log('REPORT_ACCESS_KEY missing and storage key directory cannot be created.');
            return null;
        }

        try {
            $key = bin2hex(random_bytes(32));
        } catch (\Throwable $e) {
            error_log('REPORT_ACCESS_KEY missing and storage key cannot be generated.');
            return null;
        }

        if (file_put_contents($path, $key . PHP_EOL, LOCK_EX) === false) {
            error_log('REPORT_ACCESS_KEY missing and storage key cannot be written.');
            return null;
        }

        @chmod($path, 0600);
        return $key;
    }

    private static function isSecureRequest(): bool
    {
        $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
        if ($https === 'on' || $https === '1') {
            return true;
        }

        return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    private static function isValidToken(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $token);
    }
}
