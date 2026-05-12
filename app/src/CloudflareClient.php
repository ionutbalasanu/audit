<?php
declare(strict_types=1);

final class CloudflareClient
{
    private string $accountId;
    private string $token;
    private string $endpoint;

    public function __construct(string $accountId, string $token)
    {
        $this->accountId = $accountId;
        $this->token     = $token;
        // Endpointul „render” (one-shot) al Browser Rendering
        $this->endpoint  = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/browser_rendering/render";
    }

    /**
     * Returnează HTML-ul randat de Cloudflare.
     * Face până la 3 încercări cu strategii diferite de așteptare.
     * Aruncă RuntimeException DOAR dacă toate încercările eșuează.
     */
    public function fetchRenderedHtml(string $url, array $hints = []): string
    {
        UrlSafety::assertAllowed($url);
        $strategies = [
            // 1) Safe & rapid: domcontentloaded + mic timeout suplimentar
            [
                'gotoOptions' => ['waitUntil' => 'domcontentloaded', 'timeout' => 30000],
                'waitFor'     => ['type' => 'timeout', 'ms' => 1500],
            ],
            // 2) load (unele site-uri nu își pun niciun selector util)
            [
                'gotoOptions' => ['waitUntil' => 'load', 'timeout' => 35000],
                'waitFor'     => ['type' => 'timeout', 'ms' => 1000],
            ],
            // 3) networkidle (ultimul fallback)
            [
                'gotoOptions' => ['waitUntil' => 'networkidle', 'timeout' => 40000],
                'waitFor'     => ['type' => 'timeout', 'ms' => 500],
            ],
        ];

        $lastErr = null;

        foreach ($strategies as $i => $opt) {
            try {
                return $this->renderOnce($url, $opt);
            } catch (\Throwable $e) {
                $lastErr = $e;
                // 422 / „execution context destroyed” - probabil o re-navigare;
                // retry cu altă strategie după un mic backoff.
                if (strpos($e->getMessage(), 'execution context was destroyed') !== false || $this->is422($e)) {
                    usleep(200_000); // 200ms
                    continue;
                }
                // alte erori - mai încercăm totuși următoarea strategie
                usleep(150_000);
            }
        }

        throw new \RuntimeException('Cloudflare render failed: ' . ($lastErr?->getMessage() ?? 'unknown'));
    }

    private function is422(\Throwable $e): bool
    {
        return (strpos($e->getMessage(), '422') !== false);
    }

    /**
     * Apel minim către endpointul de render.
     * Body-ul e ținut „generic” ca să nu depindem de un selector anume.
     */
    private function renderOnce(string $url, array $opts): string
    {
        $payload = [
            'url'         => $url,
            'gotoOptions' => $opts['gotoOptions'] ?? ['waitUntil' => 'domcontentloaded', 'timeout' => 20000],
            // „waitFor” suportă mai multe forme; aici folosim un simplu timeout suplimentar
            'waitFor'     => $opts['waitFor']     ?? ['type' => 'timeout', 'ms' => 800],
            // pentru siguranță, cerem doar „content”
            'response'    => ['format' => 'html'],
            // mic „safety” împotriva navigărilor în buclă
            'navigation'  => ['maxNavigations' => 3],
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 45,
        ]);

        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException('Cloudflare request failed: ' . $err);
        }

        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Cloudflare invalid response: HTTP ' . $http . ' - ' . substr($raw, 0, 200));
        }

        if (empty($json['success'])) {
            $msg = $json['errors'][0]['message'] ?? ($json['messages'][0] ?? 'unknown');
            throw new \RuntimeException("Cloudflare API error ({$http}): " . $msg);
        }

        $html = $json['result']['content'] ?? null;
        if (!is_string($html) || $html === '') {
            throw new \RuntimeException("Cloudflare result empty ({$http})");
        }

        return $html;
    }
}
