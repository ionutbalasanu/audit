<?php
declare(strict_types=1);

final class ReportPayload
{
    private const LOCAL_SEO_CHECK_IDS = [
        'local_tel_click',
        'local_tel_prefix_local',
        'local_address_visible',
        'local_directions_link',
        'local_opening_hours',
        'local_schema_localbusiness',
        'local_schema_postal',
        'local_schema_tel',
        'local_schema_geo',
        'local_schema_sameas',
        'local_schema_area',
        'local_schema_rating',
        'local_city_detected',
        'local_city_in_title',
        'local_city_in_h1',
        'local_city_in_slug',
        'local_city_in_intro',
        'local_map_embed',
        'local_alt_has_city',
        'local_locator',
        'local_whatsapp',
    ];

    public static function full(array $snapshot): array
    {
        if (isset($snapshot['score']) && is_array($snapshot['score'])) {
            $snapshot['score']['checks'] = Advice::enrichChecks((array)($snapshot['score']['checks'] ?? []));
            if (!self::isLocalContext($snapshot)) {
                $snapshot['score']['checks'] = self::withoutLocalSeoChecks($snapshot['score']['checks']);
            }
            $snapshot['score']['checks_total'] = count($snapshot['score']['checks']);
            $snapshot['score']['checks_preview_count'] = count($snapshot['score']['checks']);
            $snapshot['score']['checks_counts'] = self::checkCounts($snapshot['score']['checks']);
        }

        $snapshot['locked'] = false;
        return $snapshot;
    }

    public static function public(array $snapshot): array
    {
        $public = $snapshot;
        $public['locked'] = true;
        $public['score'] = self::publicScore(
            (array)($snapshot['score'] ?? []),
            (string)($snapshot['report_token'] ?? ''),
            self::isLocalContext($snapshot)
        );
        $public['business'] = self::publicBusiness((array)($snapshot['business'] ?? []));

        return $public;
    }

    private static function publicScore(array $score, string $token, bool $isLocalContext): array
    {
        $allowed = [
            'total',
            'total_global',
            'total_local',
            'breakdown',
            'local',
            'context',
            'gatekeeper',
        ];

        $public = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $score)) {
                $public[$key] = $score[$key];
            }
        }

        if ($token !== '') {
            $public['report_token'] = $token;
        }

        $allChecks = Advice::enrichChecks((array)($score['checks'] ?? []));
        if (!$isLocalContext) {
            $allChecks = self::withoutLocalSeoChecks($allChecks);
        }
        $previewChecks = array_map([self::class, 'publicCheck'], $allChecks);
        $problemCount = count(array_filter(
            $allChecks,
            static fn(array $check): bool => (string)($check['sev'] ?? 'warn') !== 'pass'
        ));

        $public['checks_total'] = count($allChecks);
        $public['checks_preview_count'] = max(0, count($allChecks) - $problemCount);
        $public['checks_locked_count'] = $problemCount;
        $public['checks_counts'] = self::checkCounts($allChecks);
        $public['checks'] = $previewChecks;

        return $public;
    }

    /**
     * @param array<string,mixed> $snapshot
     */
    private static function isLocalContext(array $snapshot): bool
    {
        $context = (string)($snapshot['context'] ?? ($snapshot['score']['context'] ?? 'article'));
        return $context === 'local';
    }

    /**
     * @param array<int,array<string,mixed>> $checks
     * @return array<int,array<string,mixed>>
     */
    private static function withoutLocalSeoChecks(array $checks): array
    {
        return array_values(array_filter(
            $checks,
            static fn(array $check): bool => !in_array((string)($check['id'] ?? ''), self::LOCAL_SEO_CHECK_IDS, true)
        ));
    }

    private static function publicBusiness(array $business): array
    {
        $public = [];
        foreach (['opportunity_score', 'lead_segment', 'recommended_service'] as $key) {
            if (array_key_exists($key, $business)) {
                $public[$key] = $business[$key];
            }
        }

        $public['top_business_issues'] = array_slice(array_map(
            static fn(array $issue): array => [
                'check_id' => $issue['check_id'] ?? null,
                'label' => 'Problemă ascunsă în preview',
                'business_impact_magnitude' => $issue['business_impact_magnitude'] ?? 'medium',
                'fix_complexity' => $issue['fix_complexity'] ?? 'medium',
                'related_service' => $issue['related_service'] ?? 'seo_optimization',
                'sev' => $issue['sev'] ?? 'warn',
                'locked' => true,
            ],
            (array)($business['top_business_issues'] ?? [])
        ), 0, 5);

        $public['action_plan'] = array_slice(array_map(
            static fn(array $action): array => [
                'title' => 'Acțiune de rezolvare ascunsă în preview',
                'duration' => $action['duration'] ?? '',
                'fix_complexity' => $action['fix_complexity'] ?? 'medium',
                'related_service' => $action['related_service'] ?? 'seo_optimization',
                'locked' => true,
            ],
            (array)($business['action_plan'] ?? [])
        ), 0, 5);

        return $public;
    }

    /**
     * @param array<string,mixed> $check
     * @return array<string,mixed>
     */
    private static function publicCheck(array $check): array
    {
        $severity = (string)($check['sev'] ?? 'warn');
        $isProblem = $severity !== 'pass';
        $public = [
            'id' => $check['id'] ?? '',
            'ok' => (bool)($check['ok'] ?? false),
            'sev' => $severity,
            'business_impact_magnitude' => $check['business_impact_magnitude'] ?? 'medium',
            'related_service' => $check['related_service'] ?? 'seo_optimization',
            'fix_complexity' => $check['fix_complexity'] ?? 'medium',
        ];

        if ($isProblem) {
            $public['label'] = 'Problemă ascunsă în preview';
            $public['note'] = '';
            $public['locked'] = true;
            $public['locked_copy'] = 'Introdu email-ul pentru a vedea problema exactă și pașii de rezolvare.';
            return $public;
        }

        $public['note'] = $check['note'] ?? '';
        $public['label'] = $check['label'] ?? ($check['id'] ?? 'Verificare');

        return $public;
    }

    /**
     * @param array<int,array<string,mixed>> $checks
     * @return array<string,int>
     */
    private static function checkCounts(array $checks): array
    {
        $counts = ['pass' => 0, 'warn' => 0, 'fail' => 0, 'priority' => 0];
        foreach ($checks as $check) {
            $severity = (string)($check['sev'] ?? 'warn');
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            }
            if ($severity !== 'pass' && ($check['business_impact_magnitude'] ?? '') === 'high') {
                $counts['priority']++;
            }
        }

        return $counts;
    }
}
