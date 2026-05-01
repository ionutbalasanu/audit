<?php
declare(strict_types=1);

final class SiteAuditAccess
{
    private const COOKIE_PREFIX = 'nova_audit_site_';
    private const DEFAULT_WINDOW_SECONDS = 86400;

    private string $dir;
    private string $indexDir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/\\');
        $this->indexDir = $this->dir . '/index';

        foreach ([$this->dir, $this->indexDir] as $dirPath) {
            if (!is_dir($dirPath) && !mkdir($dirPath, 0775, true) && !is_dir($dirPath)) {
                throw new \RuntimeException('Site audit access storage is not writable.');
            }
        }

        $this->maybeCleanup();
    }

    public function statusForUrl(string $url): ?array
    {
        $siteKey = self::siteKey($url);
        if ($siteKey === null) {
            return null;
        }

        $grant = $this->grantFromCookie($siteKey);
        return $grant !== null ? $this->publicStatus($grant) : null;
    }

    public function storedEmailForUrl(string $url): ?string
    {
        $siteKey = self::siteKey($url);
        if ($siteKey === null) {
            return null;
        }

        $grant = $this->grantFromCookie($siteKey);
        $email = trim((string)($grant['email'] ?? ''));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public function blockedForNewReport(string $url, string $reportToken, string $email = '', string $actorKey = ''): ?array
    {
        $siteKey = self::siteKey($url);
        if ($siteKey === null) {
            return null;
        }

        $grant = $this->resolveGrant($siteKey, $email, $actorKey);
        if ($grant === null) {
            return null;
        }

        if ($this->hasReportToken($grant, $reportToken)) {
            return null;
        }

        return $this->runsUsed($grant) >= $this->runLimit($grant)
            ? $this->limitStatus($grant)
            : null;
    }

    public function consumeScoreRun(string $url, string $reportToken): array
    {
        $siteKey = self::siteKey($url);
        if ($siteKey === null) {
            return ['ok' => false, 'reason' => 'invalid_site'];
        }

        $grant = $this->grantFromCookie($siteKey);
        if ($grant === null) {
            return ['ok' => false, 'reason' => 'missing_grant'];
        }

        return $this->updateGrant((string)$grant['grant_id'], function (array $stored) use ($siteKey, $reportToken): array {
            if ((string)($stored['site_key'] ?? '') !== $siteKey || $this->isExpired($stored)) {
                return ['ok' => false, 'reason' => 'invalid_grant'];
            }

            $stored = $this->normalizeGrant($stored);
            $recorded = $this->recordReportToken($stored, $reportToken);
            if (!$recorded['allowed']) {
                return [
                    'ok' => false,
                    'error' => 'site_audit_limit_reached',
                    'site_access' => $this->limitStatus($stored),
                ];
            }

            $updated = $recorded['grant'];
            return [
                'ok' => true,
                'grant' => $updated,
                'site_access' => array_merge($this->publicStatus($updated), [
                    'auto_unlocked' => true,
                    'counted' => (bool)$recorded['counted'],
                ]),
            ];
        });
    }

    public function grantReport(string $url, string $email, string $reportToken, string $actorKey = ''): array
    {
        $siteKey = self::siteKey($url);
        $normalizedEmail = self::normalizeEmail($email);
        if ($siteKey === null || $normalizedEmail === null) {
            return ['ok' => false, 'reason' => 'invalid_grant_input'];
        }

        $existing = $this->resolveGrant($siteKey, $normalizedEmail, $actorKey);
        $grantId = (string)($existing['grant_id'] ?? '');
        if ($grantId === '') {
            $grantId = $this->createGrant($siteKey, $normalizedEmail, $actorKey);
        }

        $result = $this->updateGrant($grantId, function (array $stored) use ($siteKey, $normalizedEmail, $actorKey, $reportToken): array {
            if ((string)($stored['site_key'] ?? '') !== $siteKey || $this->isExpired($stored)) {
                $stored = $this->baseGrant((string)($stored['grant_id'] ?? bin2hex(random_bytes(16))), $siteKey, $normalizedEmail, $actorKey);
            }

            $stored = $this->normalizeGrant($stored);
            $stored['site_key'] = $siteKey;
            $stored['email'] = $normalizedEmail;
            $stored['email_hash'] = self::emailHash($normalizedEmail);
            $stored['run_limit'] = $this->defaultRunLimit();
            $stored['window_seconds'] = $this->windowSeconds();
            $stored['expires_at'] = $this->resetTimestamp($stored);
            $stored['updated_at'] = gmdate(DATE_ATOM);

            $actorHash = $this->actorHash($actorKey);
            if ($actorHash !== null) {
                $actorHashes = (array)($stored['actor_hashes'] ?? []);
                $actorHashes[] = $actorHash;
                $stored['actor_hashes'] = array_values(array_unique(array_filter($actorHashes, 'is_string')));
            }

            $recorded = $this->recordReportToken($stored, $reportToken);
            if (!$recorded['allowed']) {
                return [
                    'ok' => false,
                    'error' => 'site_audit_limit_reached',
                    'site_access' => $this->limitStatus($stored),
                ];
            }

            $updated = $recorded['grant'];
            return [
                'ok' => true,
                'grant' => $updated,
                'site_access' => $this->publicStatus($updated),
            ];
        });

        if (!empty($result['ok']) && isset($result['grant']) && is_array($result['grant'])) {
            $this->indexGrant($result['grant']);
            $this->issueCookie($siteKey, $result['grant']);
        }

        return $result;
    }

    public static function siteKey(string $url): ?string
    {
        $host = strtolower(trim((string)(parse_url($url, PHP_URL_HOST) ?: '')));
        $host = preg_replace('/^www\./i', '', $host) ?? $host;
        $host = trim($host, ". \t\n\r\0\x0B");

        return $host !== '' ? $host : null;
    }

    private function resolveGrant(string $siteKey, string $email = '', string $actorKey = ''): ?array
    {
        $email = self::normalizeEmail($email) ?? '';
        if ($email !== '') {
            $grant = $this->scanGrant($siteKey, $email, null)
                ?? $this->grantFromIndex('email', $this->identityHash($siteKey, self::emailHash($email)), $siteKey);
            if ($grant !== null) {
                return $grant;
            }
        }

        $actorHash = $this->actorHash($actorKey);
        if ($actorHash !== null) {
            $grant = $this->scanGrant($siteKey, '', $actorHash)
                ?? $this->grantFromIndex('actor', $this->identityHash($siteKey, $actorHash), $siteKey);
            if ($grant !== null) {
                return $grant;
            }
        }

        return $this->grantFromCookie($siteKey);
    }

    private function grantFromIndex(string $type, string $identityHash, string $siteKey): ?array
    {
        $path = $this->indexPath($type, $identityHash);
        if (!is_file($path)) {
            return null;
        }

        $grantId = trim((string)@file_get_contents($path));
        if (!preg_match('/^[a-f0-9]{32}$/', $grantId)) {
            @unlink($path);
            return null;
        }

        $grant = $this->readGrant($grantId);
        if ($grant === null || (string)($grant['site_key'] ?? '') !== $siteKey || $this->isExpired($grant)) {
            @unlink($path);
            return null;
        }

        return $this->normalizeGrant($grant);
    }

    private function scanGrant(string $siteKey, string $email = '', ?string $actorHash = null): ?array
    {
        $emailHash = $email !== '' ? self::emailHash($email) : '';
        $bestGrant = null;
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $grantId = basename($file, '.json');
            if (!preg_match('/^[a-f0-9]{32}$/', $grantId)) {
                continue;
            }

            $grant = $this->readGrant($grantId);
            if ($grant === null || (string)($grant['site_key'] ?? '') !== $siteKey || $this->isExpired($grant)) {
                continue;
            }

            $grant = $this->normalizeGrant($grant);
            $matchesEmail = $emailHash !== '' && (string)($grant['email_hash'] ?? '') === $emailHash;
            $matchesActor = $actorHash !== null && in_array($actorHash, (array)($grant['actor_hashes'] ?? []), true);
            if ($matchesEmail || $matchesActor) {
                if (
                    $bestGrant === null
                    || $this->runsUsed($grant) > $this->runsUsed($bestGrant)
                    || (
                        $this->runsUsed($grant) === $this->runsUsed($bestGrant)
                        && strtotime((string)($grant['created_at'] ?? '')) > strtotime((string)($bestGrant['created_at'] ?? ''))
                    )
                ) {
                    $bestGrant = $grant;
                }
            }
        }

        if ($bestGrant !== null) {
            $this->indexGrant($bestGrant);
        }

        return $bestGrant;
    }

    private function createGrant(string $siteKey, string $email, string $actorKey = ''): string
    {
        for ($i = 0; $i < 5; $i++) {
            $grantId = bin2hex(random_bytes(16));
            $path = $this->pathFor($grantId);
            if (is_file($path)) {
                continue;
            }

            $grant = $this->baseGrant($grantId, $siteKey, $email, $actorKey);
            $written = file_put_contents(
                $path,
                json_encode($grant, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                LOCK_EX
            );
            if ($written !== false) {
                $this->indexGrant($grant);
                return $grantId;
            }
        }

        throw new \RuntimeException('Site audit access grant cannot be created.');
    }

    private function baseGrant(string $grantId, string $siteKey, string $email, string $actorKey = ''): array
    {
        $now = time();
        $normalizedEmail = self::normalizeEmail($email) ?? strtolower(trim($email));
        $actorHash = $this->actorHash($actorKey);

        return [
            'grant_id' => $grantId,
            'site_key' => $siteKey,
            'email' => $normalizedEmail,
            'email_hash' => self::emailHash($normalizedEmail),
            'actor_hashes' => $actorHash !== null ? [$actorHash] : [],
            'runs_used' => 0,
            'run_limit' => $this->defaultRunLimit(),
            'window_seconds' => $this->windowSeconds(),
            'report_tokens' => [],
            'created_at' => gmdate(DATE_ATOM, $now),
            'updated_at' => gmdate(DATE_ATOM, $now),
            'expires_at' => $now + $this->windowSeconds(),
        ];
    }

    private function updateGrant(string $grantId, callable $mutator): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $grantId)) {
            return ['ok' => false, 'reason' => 'invalid_grant_id'];
        }

        $path = $this->pathFor($grantId);
        $handle = fopen($path, 'c+');
        if (!$handle) {
            return ['ok' => false, 'reason' => 'grant_not_writable'];
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return ['ok' => false, 'reason' => 'grant_not_lockable'];
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $grant = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
            if (!is_array($grant)) {
                $grant = [];
            }
            $grant['grant_id'] = (string)($grant['grant_id'] ?? $grantId);

            $result = $mutator($grant);
            if (!empty($result['ok']) && isset($result['grant']) && is_array($result['grant'])) {
                ftruncate($handle, 0);
                rewind($handle);
                fwrite(
                    $handle,
                    json_encode($result['grant'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                );
                fflush($handle);
            }

            flock($handle, LOCK_UN);
            return $result;
        } finally {
            fclose($handle);
        }
    }

    private function recordReportToken(array $grant, string $reportToken): array
    {
        $tokens = $this->reportTokens($grant);
        $hasToken = $this->isValidReportToken($reportToken);

        if ($hasToken && in_array($reportToken, $tokens, true)) {
            $grant['report_tokens'] = $tokens;
            $grant['runs_used'] = max($this->runsUsed($grant), count($tokens));
            $grant['updated_at'] = gmdate(DATE_ATOM);

            return ['allowed' => true, 'counted' => false, 'grant' => $grant];
        }

        $used = $this->runsUsed($grant);
        $limit = $this->runLimit($grant);
        if ($used >= $limit) {
            return ['allowed' => false, 'counted' => false, 'grant' => $grant];
        }

        if ($hasToken) {
            $tokens[] = $reportToken;
        }

        $grant['report_tokens'] = array_values(array_unique($tokens));
        $grant['runs_used'] = $used + 1;
        $grant['last_report_token'] = $hasToken ? $reportToken : (string)($grant['last_report_token'] ?? '');
        $grant['updated_at'] = gmdate(DATE_ATOM);

        return ['allowed' => true, 'counted' => true, 'grant' => $grant];
    }

    private function grantFromCookie(string $siteKey): ?array
    {
        $raw = (string)($_COOKIE[$this->cookieName($siteKey)] ?? '');
        if ($raw === '') {
            return null;
        }

        $parts = explode('.', $raw, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$grantId, $signature] = $parts;
        if (!preg_match('/^[a-f0-9]{32}$/', $grantId)) {
            return null;
        }

        if (!hash_equals($this->signature($siteKey, $grantId), $signature)) {
            return null;
        }

        $grant = $this->readGrant($grantId);
        if ($grant === null || (string)($grant['site_key'] ?? '') !== $siteKey || $this->isExpired($grant)) {
            return null;
        }

        return $this->normalizeGrant($grant);
    }

    private function readGrant(string $grantId): ?array
    {
        $path = $this->pathFor($grantId);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        $grant = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : null;

        return is_array($grant) ? $grant : null;
    }

    private function normalizeGrant(array $grant): array
    {
        $email = self::normalizeEmail((string)($grant['email'] ?? '')) ?? strtolower(trim((string)($grant['email'] ?? '')));
        $grant['email'] = $email;
        $grant['email_hash'] = (string)($grant['email_hash'] ?? self::emailHash($email));
        $grant['actor_hashes'] = array_values(array_unique(array_filter((array)($grant['actor_hashes'] ?? []), 'is_string')));
        $grant['run_limit'] = max(1, (int)($grant['run_limit'] ?? $this->defaultRunLimit()));
        $grant['window_seconds'] = max(60, (int)($grant['window_seconds'] ?? $this->windowSeconds()));
        $grant['report_tokens'] = $this->reportTokens($grant);
        $grant['runs_used'] = $this->runsUsed($grant);
        $grant['expires_at'] = $this->resetTimestamp($grant);

        return $grant;
    }

    private function publicStatus(array $grant): array
    {
        $grant = $this->normalizeGrant($grant);
        $used = $this->runsUsed($grant);
        $limit = $this->runLimit($grant);
        $resetAt = $this->resetTimestamp($grant);

        return [
            'active' => true,
            'site_key' => (string)($grant['site_key'] ?? ''),
            'runs_used' => $used,
            'run_limit' => $limit,
            'remaining_runs' => max(0, $limit - $used),
            'exhausted' => $used >= $limit,
            'last_report_token' => $this->lastReportToken($grant),
            'email_mask' => self::maskEmail((string)($grant['email'] ?? '')),
            'can_resend' => filter_var((string)($grant['email'] ?? ''), FILTER_VALIDATE_EMAIL) !== false,
            'resets_at' => gmdate(DATE_ATOM, $resetAt),
            'seconds_until_reset' => max(0, $resetAt - time()),
        ];
    }

    private function limitStatus(array $grant): array
    {
        return array_merge($this->publicStatus($grant), [
            'allowed' => false,
            'error' => 'site_audit_limit_reached',
        ]);
    }

    private function indexGrant(array $grant): void
    {
        $grant = $this->normalizeGrant($grant);
        $siteKey = (string)($grant['site_key'] ?? '');
        $grantId = (string)($grant['grant_id'] ?? '');
        if ($siteKey === '' || !preg_match('/^[a-f0-9]{32}$/', $grantId)) {
            return;
        }

        $emailHash = (string)($grant['email_hash'] ?? '');
        if ($emailHash !== '') {
            $this->writeIndex('email', $this->identityHash($siteKey, $emailHash), $grantId);
        }

        foreach ((array)($grant['actor_hashes'] ?? []) as $actorHash) {
            if (is_string($actorHash) && $actorHash !== '') {
                $this->writeIndex('actor', $this->identityHash($siteKey, $actorHash), $grantId);
            }
        }
    }

    private function writeIndex(string $type, string $identityHash, string $grantId): void
    {
        @file_put_contents($this->indexPath($type, $identityHash), $grantId, LOCK_EX);
    }

    private function indexPath(string $type, string $identityHash): string
    {
        return $this->indexDir . '/' . $type . '_' . $identityHash . '.idx';
    }

    private function identityHash(string $siteKey, string $identity): string
    {
        return hash('sha256', $siteKey . '|' . $identity);
    }

    private function actorHash(string $actorKey): ?string
    {
        $actorKey = trim($actorKey);
        return $actorKey !== '' ? hash('sha256', $actorKey) : null;
    }

    private static function normalizeEmail(string $email): ?string
    {
        $email = strtolower(trim($email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private static function emailHash(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }

    private function hasReportToken(array $grant, string $reportToken): bool
    {
        return $this->isValidReportToken($reportToken)
            && in_array($reportToken, $this->reportTokens($grant), true);
    }

    private function reportTokens(array $grant): array
    {
        return array_values(array_filter(
            (array)($grant['report_tokens'] ?? []),
            fn($token): bool => is_string($token) && $this->isValidReportToken($token)
        ));
    }

    private function lastReportToken(array $grant): string
    {
        $last = (string)($grant['last_report_token'] ?? '');
        if ($this->isValidReportToken($last)) {
            return $last;
        }

        $tokens = $this->reportTokens($grant);
        $fallback = end($tokens);
        return is_string($fallback) && $this->isValidReportToken($fallback) ? $fallback : '';
    }

    private function runsUsed(array $grant): int
    {
        return max(0, (int)($grant['runs_used'] ?? count($this->reportTokens($grant))));
    }

    private function runLimit(array $grant): int
    {
        return max(1, (int)($grant['run_limit'] ?? $this->defaultRunLimit()));
    }

    private function defaultRunLimit(): int
    {
        return max(1, (int) envget('SITE_AUDIT_RUN_LIMIT', '3'));
    }

    private function windowSeconds(): int
    {
        return max(60, (int) envget('SITE_AUDIT_WINDOW_SECONDS', (string)self::DEFAULT_WINDOW_SECONDS));
    }

    private function resetTimestamp(array $grant): int
    {
        $created = strtotime((string)($grant['created_at'] ?? ''));
        if ($created !== false) {
            return $created + max(60, (int)($grant['window_seconds'] ?? $this->windowSeconds()));
        }

        $expiresAt = (int)($grant['expires_at'] ?? 0);
        return $expiresAt > 0 ? $expiresAt : time() + $this->windowSeconds();
    }

    private function isExpired(array $grant): bool
    {
        return $this->resetTimestamp($grant) <= time();
    }

    private function issueCookie(string $siteKey, array $grant): bool
    {
        $grantId = (string)($grant['grant_id'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}$/', $grantId) || $this->signingKey() === null) {
            return false;
        }

        $expiresAt = $this->resetTimestamp($grant);
        if ($expiresAt <= time()) {
            return false;
        }

        $options = $this->cookieOptions();
        $options['expires'] = $expiresAt;
        $value = $grantId . '.' . $this->signature($siteKey, $grantId);

        setcookie($this->cookieName($siteKey), $value, $options);
        $_COOKIE[$this->cookieName($siteKey)] = $value;

        return true;
    }

    private function cookieName(string $siteKey): string
    {
        return self::COOKIE_PREFIX . substr(sha1($siteKey), 0, 16);
    }

    private function signature(string $siteKey, string $grantId): string
    {
        $key = $this->signingKey();
        if ($key === null) {
            return '';
        }

        return hash_hmac('sha256', $siteKey . '|' . $grantId, $key);
    }

    private function signingKey(): ?string
    {
        $configured = class_exists('Config')
            ? Config::sensitiveEnv('SITE_AUDIT_ACCESS_KEY')
            : trim((string) envget('SITE_AUDIT_ACCESS_KEY', ''));
        if ($configured !== '') {
            if (strlen($configured) >= 32) {
                return $configured;
            }

            error_log('SITE_AUDIT_ACCESS_KEY is too short; falling back to storage key.');
        }

        $reportKey = class_exists('Config')
            ? Config::sensitiveEnv('REPORT_ACCESS_KEY')
            : trim((string) envget('REPORT_ACCESS_KEY', ''));
        if ($reportKey !== '') {
            if (strlen($reportKey) >= 32) {
                return $reportKey;
            }

            error_log('REPORT_ACCESS_KEY is too short for site audit access; falling back to storage key.');
        }

        return $this->storageSigningKey();
    }

    private function storageSigningKey(): ?string
    {
        $storageDir = dirname($this->dir);
        $path = $storageDir . '/site-audit-access.key';

        if (is_file($path)) {
            $key = trim((string) @file_get_contents($path));
            return strlen($key) >= 64 ? $key : null;
        }

        if (!is_dir($storageDir) && !mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            return null;
        }

        $key = bin2hex(random_bytes(32));
        if (file_put_contents($path, $key . PHP_EOL, LOCK_EX) === false) {
            return null;
        }

        @chmod($path, 0600);
        return $key;
    }

    private function cookieOptions(): array
    {
        return [
            'path' => Config::basePath() !== '' ? Config::basePath() : '/',
            'secure' => self::isSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    private static function isSecureRequest(): bool
    {
        $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
        if ($https === 'on' || $https === '1') {
            return true;
        }

        return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    private function pathFor(string $grantId): string
    {
        return $this->dir . '/' . $grantId . '.json';
    }

    private function isValidReportToken(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $token);
    }

    private static function maskEmail(string $email): string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        [$local, $domain] = explode('@', strtolower($email), 2);
        $localMask = substr($local, 0, 1) . str_repeat('*', max(2, min(5, strlen($local) - 1)));
        $domainParts = explode('.', $domain);
        $domainName = (string)array_shift($domainParts);
        $domainMask = substr($domainName, 0, 1) . str_repeat('*', max(2, min(5, strlen($domainName) - 1)));
        $suffix = $domainParts !== [] ? '.' . implode('.', $domainParts) : '';

        return $localMask . '@' . $domainMask . $suffix;
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

        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            if (!is_file($file)) {
                continue;
            }

            $raw = @file_get_contents($file);
            $grant = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
            if (!is_array($grant)) {
                @unlink($file);
                continue;
            }

            if ($this->isExpired($grant)) {
                @unlink($file);
            }
        }

        foreach (glob($this->indexDir . '/*.idx') ?: [] as $file) {
            $grantId = trim((string)@file_get_contents($file));
            if (!preg_match('/^[a-f0-9]{32}$/', $grantId) || !is_file($this->pathFor($grantId))) {
                @unlink($file);
            }
        }
    }
}
