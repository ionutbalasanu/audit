<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/UrlSafety.php';
require_once __DIR__ . '/../src/Advice.php';
require_once __DIR__ . '/../src/ReportPayload.php';
require_once __DIR__ . '/../src/BusinessImpactCalculator.php';
require_once __DIR__ . '/../src/HttpClient.php';
require_once __DIR__ . '/../src/CloudflareClient.php';
require_once __DIR__ . '/../src/RenderService.php';
require_once __DIR__ . '/../src/ArticleScorer.php';
require_once __DIR__ . '/../src/Cache.php';
require_once __DIR__ . '/../src/RateLimiter.php';
require_once __DIR__ . '/../src/ReportStore.php';
require_once __DIR__ . '/../src/ReportAccess.php';
require_once __DIR__ . '/../src/SiteAuditAccess.php';
require_once __DIR__ . '/../src/SimplePdfDocument.php';
require_once __DIR__ . '/../src/ReportPdfRenderer.php';
require_once __DIR__ . '/../src/LeadRepository.php';
require_once __DIR__ . '/../src/MailService.php';
require_once __DIR__ . '/../src/EmailRenderer.php';
require_once __DIR__ . '/../src/WordpressClient.php';
require_once __DIR__ . '/../src/NewsletterService.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
if (!is_string($path) || $path === '') {
    $path = '/';
}
$base = rtrim((string) envget('APP_BASE', ''), '/');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
    if ($path === '') {
        $path = '/';
    }
}
if ($path === '') {
    $path = '/';
}

$allowedMethods = [
    '/' => ['GET'],
    '/api/report' => ['GET'],
    '/api/report-pdf' => ['GET'],
    '/api/render' => ['POST'],
    '/api/score' => ['POST'],
    '/api/email-report' => ['POST'],
    '/api/lead-request' => ['POST'],
];
if (isset($allowedMethods[$path]) && !in_array($method, $allowedMethods[$path], true)) {
    methodNotAllowed($allowedMethods[$path]);
}

$reportStore = new ReportStore(Config::storagePath('reports'));
$siteAuditAccess = new SiteAuditAccess(Config::storagePath('site-audit-access'));
$leadRepository = new LeadRepository(Config::storagePath('leads'));
$mailService = new MailService();

if ($method === 'GET' && $path === '/') {
    send_html_headers();
    require __DIR__ . '/view-home.php';
    exit;
}

if ($path === '/api/report' && $method === 'GET') {
    $token = trim((string)($_GET['token'] ?? ''));
    $accessToken = trim((string)($_GET['access'] ?? ''));
    $snapshot = $reportStore->get($token);
    if (!$snapshot) {
        json_out(['error' => 'report_not_found'], 404);
    }

    $unlocked = reportIsUnlocked($token, $accessToken, $siteAuditAccess);
    $payload = $unlocked ? ReportPayload::full($snapshot) : ReportPayload::public($snapshot);
    if ($unlocked) {
        $payload = attachSiteAccess($payload, $siteAuditAccess->statusForUrl((string)($snapshot['url_analyzed'] ?? '')));
    }

    json_out($payload);
}

if ($path === '/api/report-pdf' && $method === 'GET') {
    $token = trim((string)($_GET['token'] ?? ''));
    $accessToken = trim((string)($_GET['access'] ?? ''));
    $snapshot = $reportStore->get($token);
    if (!$snapshot) {
        json_out(['error' => 'report_not_found'], 404);
    }

    if (!reportIsUnlocked($token, $accessToken, $siteAuditAccess)) {
        http_response_code(403);
        send_common_security_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        echo 'Raportul PDF este disponibil doar dupa deblocarea raportului complet.';
        exit;
    }

    $pdf = ReportPdfRenderer::render((string)($snapshot['url_analyzed'] ?? ''), $snapshot);
    $filename = reportPdfFilename((string)($snapshot['url_analyzed'] ?? 'raport'));

    send_common_security_headers();
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

if ($path === '/api/render' && $method === 'POST') {
    $in = readJsonBody();
    $url = normalizeUrlInput((string)($in['url'] ?? ''));
    if ($url === null) {
        json_out(['error' => 'invalid_url'], 400);
    }

    try {
        $render = buildRenderer()->getHtml($url, ['force_render' => true]);
        json_out([
            'html_source' => $render['source'],
            'bytes' => strlen((string)$render['html']),
            'snippet' => mb_substr(strip_tags((string)$render['html']), 0, 180),
        ]);
    } catch (\Throwable $e) {
        error_log('Render diagnostic failed: ' . $e->getMessage());
        json_out(['error' => 'render_failed', 'message' => 'Nu am putut randa acest URL.'], 502);
    }
}

if ($path === '/api/score' && $method === 'POST') {
    $in = readJsonBody();
    $url = normalizeUrlInput((string)($in['url'] ?? ''));
    if ($url === null) {
        json_out(['error' => 'invalid_url'], 400);
    }

    $context = normalizeContext((string)($in['context'] ?? 'article'));
    $siteAccessStatus = $siteAuditAccess->statusForUrl($url);
    if ($siteAccessStatus !== null && !empty($siteAccessStatus['exhausted'])) {
        json_out(siteAuditLimitPayload($siteAccessStatus), 429);
    }

    $ipLimiter = new RateLimiter(Config::storagePath('ratelimit'), (int) envget('RATE_LIMIT_SCORE', '200'));
    $siteLimiter = new RateLimiter(Config::storagePath('ratelimit'), (int) envget('SITE_AUDIT_RUN_LIMIT', '3'));
    $ip = clientIp();
    enforceRateLimit($ipLimiter, [
        'score:ip:' . $ip,
    ], ['error' => 'rate_limited', 'message' => 'Prea multe cereri astazi.']);
    enforceRateLimit($siteLimiter, [
        'score:site:' . $ip . ':' . (SiteAuditAccess::siteKey($url) ?? hash('sha256', $url)),
    ], ['error' => 'rate_limited', 'message' => 'Prea multe cereri astazi.']);

    $baseAudit = analyzeUrl($url, $context, !empty($in['fresh']));
    if (!empty($baseAudit['error'])) {
        json_out(array_merge($baseAudit, [
            'business' => null,
        ]));
    }

    $score = (array)$baseAudit['score'];
    $business = BusinessImpactCalculator::calculate($score);
    $snapshot = [
        'ok' => true,
        'url_analyzed' => $url,
        'source' => $baseAudit['source'] ?? null,
        'js' => $baseAudit['js'] ?? null,
        'context' => $context,
        'score' => $score,
        'business' => $business,
        'created_at' => gmdate(DATE_ATOM),
    ];
    $token = $reportStore->create($snapshot);
    $snapshot['report_token'] = $token;
    $snapshot['score']['report_token'] = $token;

    if ($siteAccessStatus !== null) {
        $accessResult = $siteAuditAccess->consumeScoreRun($url, $token);
        if (empty($accessResult['ok'])) {
            if (($accessResult['error'] ?? '') === 'site_audit_limit_reached') {
                json_out(siteAuditLimitPayload((array)($accessResult['site_access'] ?? [])), 429);
            }

            json_out(ReportPayload::public($snapshot));
        }

        $unlocked = ReportAccess::unlock($token);
        $payload = $unlocked ? ReportPayload::full($snapshot) : ReportPayload::public($snapshot);
        json_out(attachSiteAccess($payload, (array)($accessResult['site_access'] ?? [])));
    }

    json_out(ReportPayload::public($snapshot));
}

if ($path === '/api/email-report' && $method === 'POST') {
    $in = readJsonBody();

    $consentTerms = (bool)($in['consent_terms'] ?? false);
    if (!$consentTerms) {
        json_out(['ok' => false, 'error' => 'invalid_consent'], 400);
    }

    $emailLimiter = new RateLimiter(Config::storagePath('ratelimit'), (int) envget('RATE_LIMIT_EMAIL', '3'));
    enforceRateLimit($emailLimiter, [
        'email:ip:' . clientIp(),
    ], ['ok' => false, 'error' => 'rate_limited']);

    $recaptchaAction = normalizeRecaptchaAction((string)($in['recaptcha_action'] ?? 'email_report'));
    $recaptcha = verifyRecaptcha((string)($in['recaptcha_token'] ?? ''), $recaptchaAction);
    if (empty($recaptcha['ok'])) {
        error_log('Email report reCAPTCHA failed: ' . json_encode([
            'expected_action' => $recaptchaAction,
            'actual_action' => $recaptcha['action'] ?? null,
            'score' => $recaptcha['score'] ?? null,
            'error' => $recaptcha['error'] ?? null,
        ], JSON_UNESCAPED_SLASHES));
        json_out(['ok' => false, 'error' => 'captcha_failed'], 400);
    }

    $snapshot = resolveSnapshotFromRequest($in, $reportStore);
    if ($snapshot === null) {
        json_out(['ok' => false, 'error' => 'report_not_found'], 404);
    }

    $email = trim((string)($in['email'] ?? ''));
    $usedStoredEmail = false;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $storedEmail = $siteAuditAccess->storedEmailForUrl((string)($snapshot['url_analyzed'] ?? ''));
        if ($storedEmail !== null) {
            $email = $storedEmail;
            $usedStoredEmail = true;
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_out(['ok' => false, 'error' => 'invalid_email'], 400);
    }

    $token = (string)($snapshot['report_token'] ?? '');
    $actorKey = siteAuditActorKey();
    $limitBlock = $siteAuditAccess->blockedForNewReport((string)($snapshot['url_analyzed'] ?? ''), $token, $email, $actorKey);
    if ($limitBlock !== null) {
        json_out(siteAuditLimitPayload($limitBlock), 429);
    }

    enforceRateLimit($emailLimiter, [
        'email:addr:' . strtolower($email),
        'email:site:' . (SiteAuditAccess::siteKey((string)($snapshot['url_analyzed'] ?? '')) ?? hash('sha256', (string)($snapshot['url_analyzed'] ?? ''))),
    ], ['ok' => false, 'error' => 'rate_limited']);

    $firstName = trim((string)($in['first_name'] ?? ''));
    $newsletterOptin = (bool)($in['newsletter_optin'] ?? ($in['consent_newsletter'] ?? false));
    $wantsPlan = array_key_exists('wants_plan', $in) ? (bool)$in['wants_plan'] : false;
    $source = normalizeLeadSource((string)($in['source'] ?? 'email_report'), 'email_report');
    $subject = sprintf(
        'Raport SEO pentru %s - scor %d/100',
        parse_url((string)$snapshot['url_analyzed'], PHP_URL_HOST) ?: 'pagina analizata',
        (int)($snapshot['score']['total'] ?? 0)
    );

    $mailDebugId = 'mail_' . substr(hash('sha256', $token . '|' . $email . '|' . microtime(true)), 0, 12);
    $mailInfo = null;
    try {
        $mailInfo = $mailService->send(
            $email,
            $firstName,
            $subject,
            EmailRenderer::renderReportHtml((string)$snapshot['url_analyzed'], $snapshot),
            EmailRenderer::renderReportText((string)$snapshot['url_analyzed'], $snapshot),
            ['debug_id' => $mailDebugId]
        );
    } catch (\Throwable $e) {
        error_log('Email report send failed: ' . json_encode([
            'debug_id' => $mailDebugId,
            'to' => preg_replace('/(^.).*(@.*$)/', '$1***$2', strtolower($email)),
            'report_token' => $token,
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_SLASHES));
        json_out([
            'ok' => false,
            'error' => 'email_failed',
            'message' => 'Serverul SMTP a refuzat trimiterea raportului.',
            'debug_id' => $mailDebugId,
        ], 500);
    }

    $mailpoetLeadInfo = subscribeAuditList($email, $firstName);
    $planInfo = unifiedMailpoetListInfo($mailpoetLeadInfo, $wantsPlan);
    $newsletterInfo = unifiedMailpoetListInfo($mailpoetLeadInfo, $newsletterOptin);
    $leadSaved = saveEmailReportLead($leadRepository, $snapshot, $email, $firstName, $wantsPlan, $newsletterOptin, $source);

    $siteGrant = $siteAuditAccess->grantReport((string)($snapshot['url_analyzed'] ?? ''), $email, $token, $actorKey);
    $siteAccessInfo = !empty($siteGrant['site_access']) && is_array($siteGrant['site_access'])
        ? (array)$siteGrant['site_access']
        : $siteAuditAccess->statusForUrl((string)($snapshot['url_analyzed'] ?? ''));
    $unlocked = ReportAccess::unlock($token);
    $reportPayload = $unlocked ? ReportPayload::full($snapshot) : ReportPayload::public($snapshot);
    $reportPayload = attachSiteAccess($reportPayload, $siteAccessInfo);

    json_out([
        'ok' => true,
        'report_token' => $token !== '' ? $token : null,
        'unlocked' => $unlocked,
        'report' => $reportPayload,
        'site_access' => sanitizeSiteAccess($siteAccessInfo),
        'stored_email_used' => $usedStoredEmail,
        'email_delivery' => [
            'transport' => $mailInfo['transport'] ?? null,
            'accepted_by_transport' => !empty($mailInfo['accepted_by_transport']),
            'accepted_by_smtp' => !empty($mailInfo['accepted_by_smtp']),
            'message_id' => $mailInfo['message_id'] ?? null,
            'smtp_transaction_id' => $mailInfo['smtp_transaction_id'] ?? null,
            'to' => $mailInfo['to'] ?? null,
        ],
        'mailpoet_lead' => $mailpoetLeadInfo,
        'plan_subscribed' => !empty($planInfo['ok']),
        'plan' => $planInfo,
        'newsletter' => $newsletterInfo,
        'lead_saved' => $leadSaved,
    ]);
}

if ($path === '/api/lead-request' && $method === 'POST') {
    $in = readJsonBody();
    $honeypot = trim((string)($in['website'] ?? ''));
    if ($honeypot !== '') {
        json_out(['ok' => true, 'spam' => true]);
    }

    $consentTerms = (bool)($in['consent_terms'] ?? false);
    if (!$consentTerms) {
        json_out(['ok' => false, 'error' => 'invalid_consent'], 400);
    }

    $leadLimiter = new RateLimiter(Config::storagePath('ratelimit'), (int) envget('RATE_LIMIT_LEAD', '3'));
    enforceRateLimit($leadLimiter, [
        'lead:ip:' . clientIp(),
    ], ['ok' => false, 'error' => 'rate_limited']);

    $recaptchaAction = normalizeRecaptchaAction((string)($in['recaptcha_action'] ?? 'lead_request'));
    $recaptcha = verifyRecaptcha((string)($in['recaptcha_token'] ?? ''), $recaptchaAction);
    if (empty($recaptcha['ok'])) {
        json_out(['ok' => false, 'error' => 'captcha_failed'], 400);
    }

    $snapshot = resolveSnapshotFromRequest($in, $reportStore);
    if ($snapshot === null) {
        $snapshot = [
            'url_analyzed' => normalizeUrlInput((string)($in['url'] ?? '')) ?? '',
            'context' => normalizeContext((string)($in['context'] ?? 'article')),
            'score' => [],
            'business' => [],
            'report_token' => null,
        ];
    }

    $name = trim((string)($in['name'] ?? ''));
    $phone = trim((string)($in['phone'] ?? ''));
    $email = trim((string)($in['email'] ?? ''));
    $message = trim((string)($in['message'] ?? ''));
    $urgent = (bool)($in['urgent'] ?? false);
    $wantsPlan = (bool)($in['wants_plan'] ?? false);
    $newsletterOptin = (bool)($in['newsletter_optin'] ?? false);
    $source = normalizeLeadSource((string)($in['source'] ?? 'lead_request'), 'lead_request');

    if (
        $name === ''
        || $message === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || ($phone !== '' && !LeadRepository::isValidPhone($phone))
    ) {
        json_out(['ok' => false, 'error' => 'invalid_contact'], 400);
    }

    $leadRateKeys = [
        'lead:addr:' . strtolower($email),
    ];
    $leadSiteKey = SiteAuditAccess::siteKey((string)($snapshot['url_analyzed'] ?? ''));
    if ($leadSiteKey !== null) {
        $leadRateKeys[] = 'lead:site:' . $leadSiteKey;
    }

    enforceRateLimit($leadLimiter, $leadRateKeys, ['ok' => false, 'error' => 'rate_limited']);

    $lead = [
        'captured_at' => gmdate(DATE_ATOM),
        'source' => $source,
        'url_analyzed' => $snapshot['url_analyzed'],
        'score' => $snapshot['score'],
        'lead_segment' => $snapshot['business']['lead_segment'] ?? ($snapshot['url_analyzed'] !== '' ? 'unknown' : 'contact_only'),
        'business' => $snapshot['business'],
        'contact' => [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
        ],
        'intent_signals' => [
            'wants_plan' => $wantsPlan,
            'wants_call_24h' => $urgent,
            'newsletter_optin' => $newsletterOptin,
            'consent_terms' => $consentTerms,
        ],
        'captcha' => [
            'provider' => 'recaptcha_v3',
            'action' => $recaptchaAction,
            'score' => $recaptcha['score'] ?? null,
            'skipped' => !empty($recaptcha['skipped']),
        ],
        'ip' => clientIp(),
        'user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
    ];

    $leadRepository->save($lead);

    $notifyEmail = Config::leadNotifyEmail();
    if ($notifyEmail) {
        $subject = sprintf(
            '[%s] Lead %s din audit-seo-gratuit',
            $urgent ? 'URGENT 24h' : 'NORMAL',
            strtoupper((string)($lead['lead_segment'] ?? 'unknown'))
        );
        try {
            $mailService->send(
                $notifyEmail,
                'NovaWeb',
                $subject,
                EmailRenderer::renderLeadNotificationHtml($lead),
                EmailRenderer::renderLeadNotificationText($lead),
                [
                    'reply_to_email' => $email,
                    'reply_to_name' => $name,
                ]
            );
        } catch (\Throwable $e) {
            // Lead-ul a fost salvat deja; nu blocam raspunsul catre utilizator.
        }
    }

    $mailpoetLeadInfo = subscribeAuditList($email, $name);

    $unlocked = !empty($snapshot['report_token']) && ReportAccess::unlock((string)$snapshot['report_token']);

    $messageText = !empty($snapshot['report_token'])
        ? ($unlocked
            ? 'Multumim, te contactam in maximum 24h. Raportul complet este acum deblocat.'
            : 'Multumim, te contactam in maximum 24h. Cererea a fost trimisa.')
        : 'Multumim, revenim pe email in maximum 24h.';

    $response = ['ok' => true, 'message' => $messageText, 'unlocked' => $unlocked, 'mailpoet_lead' => $mailpoetLeadInfo];
    if (!empty($snapshot['report_token'])) {
        $response['report'] = $unlocked ? ReportPayload::full($snapshot) : ReportPayload::public($snapshot);
    }

    json_out($response);
}

json_out(['error' => 'Not Found', 'path' => $path], 404);

function readJsonBody(): array
{
    $maxBytes = Config::maxJsonBodyBytes();
    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > $maxBytes) {
        json_out(['error' => 'payload_too_large'], 413);
    }

    $raw = file_get_contents('php://input') ?: '';
    if (strlen($raw) > $maxBytes) {
        json_out(['error' => 'payload_too_large'], 413);
    }

    if (trim($raw) === '') {
        return [];
    }

    try {
        $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {
        json_out(['error' => 'invalid_json'], 400);
    }

    if (!is_array($json)) {
        json_out(['error' => 'invalid_json'], 400);
    }

    return $json;
}

function methodNotAllowed(array $allowedMethods): void
{
    header('Allow: ' . implode(', ', $allowedMethods));
    json_out(['error' => 'method_not_allowed'], 405);
}

function reportPdfFilename(string $url): string
{
    $host = (string)(parse_url($url, PHP_URL_HOST) ?: 'pagina');
    $host = strtolower((string) preg_replace('/^www\./i', '', $host));
    $host = preg_replace('/[^a-z0-9.-]+/', '-', $host) ?? 'pagina';
    $host = trim($host, '-.');
    if ($host === '') {
        $host = 'pagina';
    }

    return 'raport-seo-' . $host . '.pdf';
}

function attachSiteAccess(array $payload, ?array $siteAccess): array
{
    $sanitized = sanitizeSiteAccess($siteAccess);
    if ($sanitized !== null) {
        $payload['site_access'] = $sanitized;
    }

    return $payload;
}

function reportIsUnlocked(string $token, string $accessToken, SiteAuditAccess $siteAuditAccess): bool
{
    if (ReportAccess::isUnlocked($token)) {
        return true;
    }

    if (ReportAccess::unlockWithAccessToken($token, $accessToken)) {
        return true;
    }

    return false;
}

function enforceRateLimit(RateLimiter $limiter, array $keys, array $payload, int $status = 429): void
{
    foreach (array_values(array_unique(array_filter($keys, 'is_string'))) as $key) {
        if ($key === '') {
            continue;
        }

        $rate = $limiter->hit($key);
        if (!$rate['allowed']) {
            json_out($payload, $status);
        }
    }
}

function sanitizeSiteAccess(?array $siteAccess): ?array
{
    if ($siteAccess === null || $siteAccess === []) {
        return null;
    }

    $allowed = [
        'active',
        'site_key',
        'runs_used',
        'run_limit',
        'remaining_runs',
        'exhausted',
        'last_report_token',
        'email_mask',
        'can_resend',
        'resets_at',
        'seconds_until_reset',
        'auto_unlocked',
        'counted',
        'allowed',
        'error',
    ];
    $safe = [];
    foreach ($allowed as $key) {
        if (array_key_exists($key, $siteAccess)) {
            $safe[$key] = $siteAccess[$key];
        }
    }

    return $safe !== [] ? $safe : null;
}

function siteAuditLimitPayload(array $siteAccess): array
{
    $safe = sanitizeSiteAccess($siteAccess) ?? [];
    $limit = max(1, (int)($safe['run_limit'] ?? envget('SITE_AUDIT_RUN_LIMIT', '3')));
    $site = trim((string)($safe['site_key'] ?? 'acest site'));
    $secondsUntilReset = max(0, (int)($safe['seconds_until_reset'] ?? 0));
    $resetCopy = $secondsUntilReset > 0
        ? ' Limita se reseteaza in aproximativ ' . humanDurationRo($secondsUntilReset) . '.'
        : ' Limita se reseteaza dupa 24h de la primul raport complet.';

    return [
        'ok' => false,
        'error' => 'site_audit_limit_reached',
        'message' => sprintf(
            'Ai atins limita de %d rapoarte complete pentru %s.%s Poti analiza un alt site sau ne poti trimite cererea pentru o verificare manuala.',
            $limit,
            $site !== '' ? $site : 'acest site',
            $resetCopy
        ),
        'site_access' => $safe,
    ];
}

function humanDurationRo(int $seconds): string
{
    $seconds = max(0, $seconds);
    $hours = intdiv($seconds + 3599, 3600);
    if ($hours >= 2) {
        return $hours . ' ore';
    }

    if ($hours === 1) {
        return 'o ora';
    }

    $minutes = max(1, intdiv($seconds + 59, 60));
    return $minutes . ' minute';
}

function normalizeContext(string $context): string
{
    $context = trim($context);
    if ($context === '') {
        $context = 'article';
    }

    if (!in_array($context, ['article', 'local'], true)) {
        json_out(['error' => 'invalid_context'], 400);
    }

    return $context;
}

function normalizeRecaptchaAction(string $action): string
{
    return in_array($action, ['email_report', 'lead_request', 'report_contact'], true) ? $action : 'lead_request';
}

function verifyRecaptcha(string $token, string $expectedAction): array
{
    $secret = Config::recaptchaSecretKey();
    if ($secret === '') {
        if (strtolower((string) envget('APP_ENV', 'production')) === 'local') {
            return ['ok' => true, 'skipped' => true];
        }

        return ['ok' => false, 'error' => 'missing_config'];
    }

    if (trim($token) === '') {
        return ['ok' => false, 'error' => 'missing_token'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'curl_missing'];
    }

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => $secret,
            'response' => $token,
            'remoteip' => clientIp(),
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $raw = curl_exec($ch);
    $errNo = curl_errno($ch);
    curl_close($ch);

    if ($raw === false || $errNo !== 0) {
        return ['ok' => false, 'error' => 'request_failed'];
    }

    $data = json_decode((string)$raw, true);
    if (!is_array($data) || empty($data['success'])) {
        return ['ok' => false, 'error' => 'verification_failed'];
    }

    $score = isset($data['score']) ? (float)$data['score'] : null;
    $action = trim((string)($data['action'] ?? ''));
    $scoreOk = $score === null || $score >= Config::recaptchaMinScore();
    $actionOk = $action === '' || hash_equals($expectedAction, $action);

    return [
        'ok' => $scoreOk && $actionOk,
        'score' => $score,
        'action' => $action,
    ];
}

function normalizeUrlInput(string $raw): ?string
{
    return UrlSafety::normalize($raw);
}

function clientIp(): string
{
    $remote = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $cfIp = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if (
        (int) envget('TRUST_CF_CONNECTING_IP', '0') === 1
        && $cfIp !== ''
        && filter_var($cfIp, FILTER_VALIDATE_IP)
    ) {
        return $cfIp;
    }

    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
}

function siteAuditActorKey(): string
{
    $ua = strtolower(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')));
    $ua = preg_replace('/\s+/', ' ', $ua) ?? $ua;

    return clientIp() . '|' . substr($ua, 0, 220);
}

function buildRenderer(): RenderService
{
    $cfId = envget('CF_ACCOUNT_ID');
    $cfTk = envget('CF_API_TOKEN');
    $cfEnabled = (int) envget('ENABLE_CF_RENDER', '0') === 1;
    $cf = ($cfEnabled && $cfId && $cfTk) ? new CloudflareClient($cfId, $cfTk) : null;
    return new RenderService(new HttpClient(), $cf);
}

function analyzeUrl(string $url, string $context, bool $fresh = false): array
{
    $ttl = max(0, (int) envget('CACHE_TTL', '900'));
    $cache = $ttl > 0 ? new Cache(Config::storagePath('cache'), $ttl) : null;
    $cacheKey = 'score:v8:' . $context . ':deep:' . $url;

    if (!$fresh && $cache && ($cached = $cache->get($cacheKey))) {
        return $cached;
    }

    try {
        $render = buildRenderer()->getHtml($url, ['force_render' => true]);
        $score = ArticleScorer::score((string)$render['html'], $url, 'deep', ['context' => $context]);

        if (!empty($score['error'])) {
            return [
                'ok' => false,
                'error' => (string)$score['error'],
                'message' => 'Nu am putut analiza automat acest site.',
                'source' => $render['source'] ?? null,
                'js' => $render['js'] ?? null,
                'score' => $score,
                'context' => $context,
            ];
        }

        $response = [
            'ok' => true,
            'source' => $render['source'] ?? null,
            'js' => $render['js'] ?? null,
            'score' => $score,
            'context' => $context,
            'cached_at' => time(),
        ];

        if ($cache) {
            $cache->set($cacheKey, $response);
        }

        return $response;
    } catch (\Throwable $e) {
        error_log(sprintf(
            'Analyze URL failed: url=%s ctx=%s ip=%s err=%s',
            $url,
            $context,
            clientIp(),
            $e->getMessage()
        ));
        return [
            'ok' => false,
            'error' => 'render_failed',
            'message' => 'Nu am putut analiza automat acest site.',
            'context' => $context,
            'score' => ArticleScorer::score('', $url, 'deep', ['context' => $context]),
        ];
    }
}

function resolveSnapshotFromRequest(array $input, ReportStore $reportStore): ?array
{
    $token = trim((string)($input['report_token'] ?? ''));
    if ($token !== '') {
        return $reportStore->get($token);
    }

    $url = normalizeUrlInput((string)($input['url'] ?? ''));
    if ($url === null) {
        return null;
    }

    $context = normalizeContext((string)($input['context'] ?? 'article'));
    $baseAudit = analyzeUrl($url, $context, false);
    if (!empty($baseAudit['error'])) {
        return null;
    }

    $score = (array)$baseAudit['score'];
    $business = BusinessImpactCalculator::calculate($score);
    $snapshot = [
        'ok' => true,
        'url_analyzed' => $url,
        'source' => $baseAudit['source'] ?? null,
        'js' => $baseAudit['js'] ?? null,
        'context' => $context,
        'score' => $score,
        'business' => $business,
        'created_at' => gmdate(DATE_ATOM),
    ];
    $snapshot['report_token'] = $reportStore->create($snapshot);
    return $snapshot;
}

function normalizeLeadSource(string $source, string $fallback): string
{
    $source = strtolower(trim($source));
    $source = preg_replace('/[^a-z0-9_\-]/', '_', $source) ?? '';
    $source = trim($source, '_-');

    return $source !== '' ? substr($source, 0, 64) : $fallback;
}

function saveEmailReportLead(
    LeadRepository $leadRepository,
    array $snapshot,
    string $email,
    string $firstName,
    bool $wantsPlan,
    bool $newsletterOptin,
    string $source
): bool {
    $lead = [
        'captured_at' => gmdate(DATE_ATOM),
        'source' => $source,
        'url_analyzed' => (string)($snapshot['url_analyzed'] ?? ''),
        'score' => (array)($snapshot['score'] ?? []),
        'lead_segment' => $snapshot['business']['lead_segment'] ?? 'unknown',
        'business' => (array)($snapshot['business'] ?? []),
        'contact' => [
            'name' => $firstName,
            'email' => $email,
            'phone' => '',
            'message' => 'Raport trimis pe email.',
        ],
        'intent_signals' => [
            'wants_plan' => $wantsPlan,
            'wants_call_24h' => false,
            'newsletter_optin' => $newsletterOptin,
        ],
        'ip' => clientIp(),
        'user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
    ];

    try {
        $leadRepository->save($lead);
        return true;
    } catch (\Throwable $e) {
        error_log('Email report lead save failed.');
        return false;
    }
}

function subscribeAuditList(string $email, string $firstName): array
{
    $wpUrl = (string) envget('WP_SUBSCRIBE_URL', '');
    $wpTok = Config::sensitiveEnv('WP_SUBSCRIBE_TOKEN');
    $wpList = auditMailpoetListName();
    $wpInsecure = ((int) envget('WP_INSECURE', '0') === 1);

    if ($wpUrl === '' || $wpTok === '') {
        return ['skipped' => true, 'ok' => false, 'reason' => 'missing_config'];
    }

    try {
        $wpClient = new WordpressClient($wpUrl, $wpTok, $wpInsecure);
        $newsletter = new NewsletterService($wpClient, $wpList);

        return $newsletter->addLead(
            email: $email,
            firstName: $firstName !== '' ? $firstName : null,
            ip: clientIp()
        );
    } catch (\Throwable $e) {
        return ['skipped' => false, 'ok' => false, 'reason' => 'request_failed'];
    }
}

function auditMailpoetListName(): string
{
    $listName = trim((string) envget('WP_AUDIT_LIST_NAME', 'Audit SEO Gratuit - Sfaturi de remediere'));
    return $listName !== '' ? $listName : 'Audit SEO Gratuit - Sfaturi de remediere';
}

function unifiedMailpoetListInfo(array $subscriptionInfo, bool $requested): array
{
    if (!$requested) {
        return ['skipped' => true, 'ok' => false, 'reason' => 'not_requested'];
    }

    return [
        'skipped' => (bool)($subscriptionInfo['skipped'] ?? false),
        'ok' => (bool)($subscriptionInfo['ok'] ?? false),
        'status' => (int)($subscriptionInfo['status'] ?? 0),
        'reason' => 'covered_by_unified_list',
    ];
}
