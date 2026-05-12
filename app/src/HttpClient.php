<?php
declare(strict_types=1);

final class HttpClient
{
    public function fetchRaw(string $url): array {
        return $this->request('GET', $url, [
            'timeout' => 15,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Ca fetchRaw, dar limitează transferul (range) la max $maxBytes (default ~256KB).
     */
    public function fetchRawLimited(string $url, int $maxBytes = 262144): array {
        return $this->request('GET', $url, [
            'timeout' => 12,
            'connect_timeout' => 8,
            'range' => '0-' . max(1, $maxBytes - 1),
            'max_bytes' => max(1, $maxBytes),
        ]);
    }

    /**
     * HEAD - încearcă să întoarcă Last-Modified ca timestamp.
     */
    public function headLastModified(string $url): ?int {
        try {
            $response = $this->request('HEAD', $url, [
                'timeout' => 8,
                'connect_timeout' => 5,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if ((int)($response['status'] ?? 0) < 200) return null;

        if (preg_match('~^Last-Modified:\s*(.+)$~im', (string)($response['headers'] ?? ''), $m)) {
            $t = strtotime(trim($m[1]));
            return $t ?: null;
        }
        return null;
    }

    private function request(string $method, string $url, array $options = []): array
    {
        $current = UrlSafety::normalize($url);
        if ($current === null) {
            throw new \RuntimeException('Blocked or invalid URL.');
        }

        $maxRedirects = 5;
        for ($redirects = 0; $redirects <= $maxRedirects; $redirects++) {
            UrlSafety::assertAllowed($current);
            $parts = parse_url($current);
            if (!is_array($parts)) {
                throw new \RuntimeException('Invalid URL.');
            }

            $scheme = strtolower((string)($parts['scheme'] ?? 'https'));
            $host = strtolower(trim((string)($parts['host'] ?? ''), '[]'));
            $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'http' ? 80 : 443);
            $ip = UrlSafety::firstPublicIp($host);
            if ($ip === null) {
                throw new \RuntimeException('Blocked DNS target.');
            }

            $ch = curl_init($current);
            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => (int)($options['timeout'] ?? 15),
                CURLOPT_CONNECTTIMEOUT => (int)($options['connect_timeout'] ?? 10),
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; NovawebAuditBot/1.0; +https://novaweb.ro/audit-bot)',
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $ip],
            ];

            if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
                $curlOptions[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            }
            if (defined('CURLOPT_REDIR_PROTOCOLS') && defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
                $curlOptions[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            }

            if ($method === 'HEAD') {
                $curlOptions[CURLOPT_NOBODY] = true;
            }
            if (!empty($options['range'])) {
                $curlOptions[CURLOPT_RANGE] = (string)$options['range'];
            }

            curl_setopt_array($ch, $curlOptions);
            $out = curl_exec($ch);
            $errNo = curl_errno($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            if ($out === false || $errNo !== 0) {
                throw new \RuntimeException('HTTP request failed: ' . $error);
            }

            $headerSize = (int)($info['header_size'] ?? 0);
            $headersRaw = substr((string)$out, 0, $headerSize);
            $body = substr((string)$out, $headerSize);
            $status = (int)($info['http_code'] ?? 0);
            $maxBytes = (int)($options['max_bytes'] ?? 0);
            if ($maxBytes > 0 && strlen($body) > $maxBytes) {
                throw new \RuntimeException('HTTP response too large.');
            }

            if ($status >= 300 && $status < 400) {
                $location = $this->headerValue($headersRaw, 'Location');
                if ($location === null) {
                    return [
                        'status' => $status,
                        'bytes' => strlen((string)$out),
                        'headers' => $headersRaw,
                        'html' => $body,
                        'effective_url' => $current,
                    ];
                }

                $next = UrlSafety::resolveRedirectUrl($current, $location);
                if ($next === null) {
                    throw new \RuntimeException('Blocked redirect target.');
                }
                $current = $next;
                continue;
            }

            return [
                'status' => $status,
                'bytes' => strlen((string)$out),
                'headers' => $headersRaw,
                'html' => $body,
                'effective_url' => $current,
            ];
        }

        throw new \RuntimeException('Too many redirects.');
    }

    private function headerValue(string $headers, string $name): ?string
    {
        if (preg_match('~^' . preg_quote($name, '~') . ':\s*(.+)$~im', $headers, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /** Heuristic JS-heavy */
    public static function detectJsHeavy(string $html): array {
        $scripts  = substr_count($html, '<script');
        $textLen  = strlen(strip_tags($html));
        $totalLen = strlen($html);

        $heavy = ($scripts > 40 && $textLen < 10000);
        $textRatio = $totalLen > 0 ? $textLen / $totalLen : 0.0;

        return [
            'heavy'   => $heavy,
            'metrics' => [
                'scriptCount' => $scripts,
                'textLen'     => $textLen,
                'textRatio'   => $textRatio,
            ],
        ];
    }
}
