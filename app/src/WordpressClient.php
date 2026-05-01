<?php
declare(strict_types=1);

final class WordpressClient
{
    private string $endpoint;
    private string $token;
    private bool   $insecure;
    private ?string $caPath;

    public function __construct(string $endpoint, string $token, bool $insecure = false, ?string $caPath = null)
    {
        $this->endpoint = rtrim($endpoint, '/');
        $this->token    = $token;
        $this->insecure = $insecure;
        $this->caPath   = $caPath;
    }

    /**
     * Subscribe via WP plugin endpoint.
     * Returns:
     *   ['ok'=>bool, 'status'=>int, 'body'=>string, 'json'=>mixed|null]
     */
    public function subscribe(string $email, ?string $firstName, string $listName, ?string $ip = null): array
    {
        $payload = [
            'email'      => $email,
            'first_name' => $firstName ?: '',
            'list_name'  => $listName,
            'ip'         => $ip ?: '',
        ];
        return $this->requestJson('POST', $this->endpoint, $payload);
    }

    private function requestJson(string $method, string $url, array $payload): array
    {
        $ch = curl_init();
        $headers = [
            'Content-Type: application/json',
            'X-Novaweb-Token: ' . $this->token,
        ];
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HEADER         => false,
        ];

        if ($this->insecure) {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        } else {
            $opts[CURLOPT_SSL_VERIFYPEER] = true;
            $opts[CURLOPT_SSL_VERIFYHOST] = 2;
            if ($this->caPath && is_file($this->caPath)) {
                $opts[CURLOPT_CAINFO] = $this->caPath;
            }
        }

        curl_setopt_array($ch, $opts);
        $respBody = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $payloadKeys = implode(',', array_keys($payload));
        $bodyBytes = is_string($respBody) ? strlen($respBody) : 0;
        $safeError = $error !== '' ? preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[redacted-email]', $error) : '';
        $this->log("[WP] POST $url status=$status errno=$errno err=$safeError body_bytes=$bodyBytes payload_keys=$payloadKeys");

        if ($errno !== 0) {
            return ['ok' => false, 'status' => 0, 'body' => $error, 'json' => null];
        }

        $json = null;
        if (is_string($respBody) && $respBody !== '') {
            try { $json = json_decode($respBody, true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable $e) { /* ignore */ }
        }

        $ok = ($status >= 200 && $status < 300) && (is_array($json) ? !empty($json['ok']) : true);
        return ['ok' => $ok, 'status' => $status, 'body' => (string)$respBody, 'json' => $json];
    }

    private function log(string $line): void
    {
        $dir = class_exists('Config') ? Config::storagePath('logs') : __DIR__ . '/../storage/logs';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $this->cleanupLogs($dir);
        $file = $dir . '/newsletter.log';
        @file_put_contents($file, '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function cleanupLogs(string $dir): void
    {
        try {
            if (random_int(1, 200) !== 1) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $days = max(1, (int) envget('LOG_RETENTION_DAYS', '14'));
        $cutoff = time() - ($days * 86400);
        foreach (glob($dir . '/*.log') ?: [] as $file) {
            if (is_file($file) && (filemtime($file) ?: time()) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
