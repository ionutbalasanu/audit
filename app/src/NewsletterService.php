<?php
declare(strict_types=1);

final class NewsletterService
{
    private WordpressClient $wp;
    private string $listName;

    public function __construct(WordpressClient $wp, string $listName)
    {
        $this->wp = $wp;
        $this->listName = $listName;
    }

    /**
     * Înscrie în listă DOAR dacă ambele consimțuri sunt true.
     * Returnează un payload diagnostic pentru front-end/loguri.
     */
    public function subscribeIfConsented(
        string $email,
        ?string $firstName,
        bool $consentNewsletter,
        bool $consentTerms,
        ?string $ip = null
    ): array {
        if (!$consentNewsletter || !$consentTerms) {
            return [
                'skipped' => true,
                'reason'  => 'missing_consents',
                'ok'      => false
            ];
        }

        $resp = $this->wp->subscribe($email, $firstName, $this->listName, $ip);
        return self::publicResponse($resp, false);
    }

    public function addLead(string $email, ?string $firstName, ?string $ip = null): array
    {
        $resp = $this->wp->subscribe($email, $firstName, $this->listName, $ip);
        return self::publicResponse($resp, false);
    }

    /**
     * @param array<string,mixed> $resp
     * @return array<string,mixed>
     */
    private static function publicResponse(array $resp, bool $skipped): array
    {
        return [
            'skipped' => $skipped,
            'ok' => (bool)($resp['ok'] ?? false),
            'status' => (int)($resp['status'] ?? 0),
        ];
    }
}
