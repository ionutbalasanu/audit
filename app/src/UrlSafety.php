<?php
declare(strict_types=1);

final class UrlSafety
{
    private const DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
    ];

    public static function normalize(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (!preg_match('~^https?://~i', $raw)) {
            $raw = 'https://' . ltrim($raw, '/');
        }

        if (!filter_var($raw, FILTER_VALIDATE_URL)) {
            return null;
        }

        try {
            self::assertAllowed($raw);
        } catch (\Throwable $e) {
            return null;
        }

        return $raw;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function assertAllowed(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            throw new \InvalidArgumentException('invalid_url');
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        if (!isset(self::DEFAULT_PORTS[$scheme])) {
            throw new \InvalidArgumentException('blocked_scheme');
        }

        $host = strtolower(trim((string)($parts['host'] ?? ''), "[] \t\n\r\0\x0B"));
        if ($host === '' || self::isBlockedHostName($host)) {
            throw new \InvalidArgumentException('blocked_host');
        }

        $port = isset($parts['port']) ? (int)$parts['port'] : self::DEFAULT_PORTS[$scheme];
        if ($port !== self::DEFAULT_PORTS[$scheme]) {
            throw new \InvalidArgumentException('blocked_port');
        }

        $ips = self::resolvePublicIps($host);
        if ($ips === []) {
            throw new \InvalidArgumentException('blocked_dns');
        }
    }

    /**
     * @return list<string>
     */
    public static function resolvePublicIps(string $host): array
    {
        $host = strtolower(trim($host, "[] \t\n\r\0\x0B"));
        if ($host === '' || self::isBlockedHostName($host)) {
            return [];
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host) ? [$host] : [];
        }

        $ips = [];
        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                $ip = (string)($record['ip'] ?? $record['ipv6'] ?? '');
                if ($ip !== '') {
                    $ips[] = $ip;
                }
            }
        }

        if ($ips === [] && function_exists('gethostbynamel')) {
            $fallback = @gethostbynamel($host);
            if (is_array($fallback)) {
                $ips = array_merge($ips, $fallback);
            }
        }

        $public = [];
        foreach (array_unique($ips) as $ip) {
            if (self::isPublicIp($ip)) {
                $public[] = $ip;
            }
        }

        return $public;
    }

    public static function firstPublicIp(string $host): ?string
    {
        $ips = self::resolvePublicIps($host);
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }

        return $ips[0] ?? null;
    }

    public static function resolveRedirectUrl(string $currentUrl, string $location): ?string
    {
        $location = trim($location);
        if ($location === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $location)) {
            return self::normalize($location);
        }

        $parts = parse_url($currentUrl);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = (string)($parts['host'] ?? '');
        if (!isset(self::DEFAULT_PORTS[$scheme]) || $host === '') {
            return null;
        }

        $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';
        $base = $scheme . '://' . $host . $port;

        if (str_starts_with($location, '//')) {
            return self::normalize($scheme . ':' . $location);
        }

        if (str_starts_with($location, '/')) {
            return self::normalize($base . $location);
        }

        $path = (string)($parts['path'] ?? '/');
        $dir = preg_replace('~/[^/]*$~', '/', $path) ?: '/';

        return self::normalize($base . $dir . $location);
    }

    private static function isBlockedHostName(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return true;
        }

        if (str_contains($host, "\0")) {
            return true;
        }

        return $host === '0' || str_ends_with($host, '.local') || str_ends_with($host, '.internal');
    }

    private static function isPublicIp(string $ip): bool
    {
        $validated = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($validated === false) {
            return false;
        }

        if (str_starts_with($ip, '0.') || str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.')) {
            return false;
        }

        return true;
    }
}
