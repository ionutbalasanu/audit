<?php
declare(strict_types=1);

final class RenderService
{
    /** @var HttpClient */
    private $http;
    /** @var CloudflareClient|null */
    private $cf;

    public function __construct($http, ?CloudflareClient $cf = null)
    {
        $this->http = $http;
        $this->cf = $cf;
    }

    /**
     * @return array{html:string, source:string, js:bool}
     */
    public function getHtml(string $url, array $options = []): array
    {
        UrlSafety::assertAllowed($url);
        $waitFor = is_array($options['waitFor'] ?? null) ? $options['waitFor'] : [];

        if ($this->cf) {
            try {
                $html = $this->cf->fetchRenderedHtml($url, $waitFor);
                if (!is_string($html) || $html === '') {
                    throw new \RuntimeException('Cloudflare returned empty HTML');
                }

                return [
                    'html' => $html,
                    'source' => 'cloudflare',
                    'js' => true,
                ];
            } catch (\Throwable $e) {
                // Fall back to the local HTTP fetcher.
            }
        }

        return [
            'html' => $this->httpGet($url),
            'source' => 'http',
            'js' => false,
        ];
    }

    private function httpGet(string $url, int $redirects = 0): string
    {
        if ($redirects > 5) {
            throw new \RuntimeException('Too many redirects');
        }

        $safeUrl = UrlSafety::normalize($url);
        if ($safeUrl === null) {
            throw new \RuntimeException('Blocked or invalid URL');
        }

        $maxBytes = max(1, (int) envget('HTTP_MAX_BYTES', '5242880'));

        if (method_exists($this->http, 'fetchRawLimited')) {
            $resp = $this->http->fetchRawLimited($safeUrl, $maxBytes);
            if (is_array($resp) && isset($resp['html'])) {
                return (string)$resp['html'];
            }
            if (is_string($resp)) {
                return $resp;
            }
        }

        if (method_exists($this->http, 'fetchRaw')) {
            $resp = $this->http->fetchRaw($safeUrl);
            if (is_array($resp) && isset($resp['html'])) {
                return (string)$resp['html'];
            }
            if (is_string($resp)) {
                return $resp;
            }
        }

        if (method_exists($this->http, 'get')) {
            try {
                $resp = $this->http->get($safeUrl);
                if (is_array($resp) && isset($resp['html'])) {
                    return (string)$resp['html'];
                }
                if (is_string($resp)) {
                    return $resp;
                }
            } catch (\Throwable $e) {
                // Continue with the cURL fallback.
            }
        }

        if (function_exists('curl_init')) {
            $parts = parse_url($safeUrl);
            $scheme = strtolower((string)($parts['scheme'] ?? 'https'));
            $host = strtolower(trim((string)($parts['host'] ?? ''), '[]'));
            $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'http' ? 80 : 443);
            $ip = UrlSafety::firstPublicIp($host);
            if ($ip === null) {
                throw new \RuntimeException('Blocked DNS target');
            }

            $ch = curl_init($safeUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => 'Novaweb-SEO-Checker/1.0 (+https://novaweb.ro)',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HEADER => true,
                CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $ip],
                CURLOPT_HTTPHEADER => ['Accept: text/html,*/*;q=0.8'],
            ]);

            $body = curl_exec($ch);
            $err = curl_error($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            if ($body === false) {
                throw new \RuntimeException('HTTP GET (cURL) failed: ' . $err);
            }

            $headers = substr((string)$body, 0, $headerSize);
            $html = substr((string)$body, $headerSize);
            if (strlen($html) > $maxBytes) {
                throw new \RuntimeException('HTTP response too large');
            }
            if ($code >= 300 && $code < 400 && preg_match('~^Location:\s*(.+)$~im', $headers, $m)) {
                $next = UrlSafety::resolveRedirectUrl($safeUrl, trim($m[1]));
                if ($next === null) {
                    throw new \RuntimeException('Blocked redirect target');
                }
                return $this->httpGet($next, $redirects + 1);
            }

            if ($code >= 400) {
                throw new \RuntimeException('HTTP error ' . $code);
            }

            return (string)$html;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'header' => "User-Agent: Novaweb-SEO-Checker/1.0\r\nAccept: text/html,*/*;q=0.8\r\n",
            ],
        ]);
        $body = @file_get_contents($safeUrl, false, $ctx);
        if ($body === false) {
            throw new \RuntimeException('HTTP GET failed (file_get_contents)');
        }
        if (strlen((string)$body) > $maxBytes) {
            throw new \RuntimeException('HTTP response too large');
        }

        return (string)$body;
    }
}
