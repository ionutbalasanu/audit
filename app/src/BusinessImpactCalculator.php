<?php
declare(strict_types=1);

final class BusinessImpactCalculator
{
    private const DEFAULT_OPPORTUNITY_MULTIPLIER = 0.456;

    public static function calculate(array $score): array
    {
        $checks = Advice::enrichChecks((array)($score['checks'] ?? []));
        $totalGlobal = (int)($score['total_global'] ?? ($score['total'] ?? 0));

        $opportunityBase = max(0, 100 - $totalGlobal);

        $opportunityScore = (int) round(
            min(
                100,
                $opportunityBase * self::DEFAULT_OPPORTUNITY_MULTIPLIER
            )
        );

        $topBusinessIssues = self::topBusinessIssues($checks);
        $leadSegment = 'unknown';
        $recommendedService = self::recommendedService($leadSegment, $topBusinessIssues);
        $actionPlan = self::buildActionPlan($topBusinessIssues);

        return [
            'opportunity_score' => $opportunityScore,
            'estimated_monthly_loss' => null,
            'estimated_monthly_loss_detail' => [
                'formatted' => null,
                'min' => null,
                'max' => null,
                'disclaimer' => null,
            ],
            'lead_segment' => $leadSegment,
            'recommended_service' => $recommendedService,
            'top_business_issues' => $topBusinessIssues,
            'action_plan' => $actionPlan,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $checks
     * @return array<int,array<string,mixed>>
     */
    private static function topBusinessIssues(array $checks): array
    {
        $failed = array_values(array_filter(
            $checks,
            static fn(array $check): bool => ($check['sev'] ?? 'fail') !== 'pass'
        ));

        usort($failed, static function (array $left, array $right): int {
            $impactOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            $sevOrder = ['fail' => 0, 'warn' => 1, 'pass' => 2];

            $leftImpact = $impactOrder[$left['business_impact_magnitude'] ?? 'medium'] ?? 10;
            $rightImpact = $impactOrder[$right['business_impact_magnitude'] ?? 'medium'] ?? 10;
            if ($leftImpact !== $rightImpact) {
                return $leftImpact <=> $rightImpact;
            }

            $leftSev = $sevOrder[$left['sev'] ?? 'warn'] ?? 10;
            $rightSev = $sevOrder[$right['sev'] ?? 'warn'] ?? 10;
            if ($leftSev !== $rightSev) {
                return $leftSev <=> $rightSev;
            }

            return strcmp((string)($left['label'] ?? ''), (string)($right['label'] ?? ''));
        });

        return array_slice(array_map(static function (array $check): array {
            return [
                'check_id' => $check['id'],
                'label' => $check['label'],
                'impact_text' => $check['business_impact_text'],
                'business_impact_magnitude' => $check['business_impact_magnitude'],
                'fix_complexity' => $check['fix_complexity'],
                'related_service' => $check['related_service'],
                'tip' => $check['tip'],
                'sev' => $check['sev'],
                'note' => $check['note'] ?? '',
            ];
        }, $failed), 0, 5);
    }

    /**
     * @param array<int,array<string,mixed>> $topIssues
     * @return array<string,mixed>
     */
    private static function recommendedService(string $leadSegment, array $topIssues): array
    {
        $serviceKey = 'seo_optimization';
        if ($leadSegment === 'hot') {
            $serviceKey = 'website_redesign';
        } elseif ($leadSegment === 'mature') {
            $serviceKey = 'premium_audit';
        } elseif ($leadSegment === 'educational') {
            $serviceKey = 'content';
        } elseif (!empty($topIssues[0]['related_service'])) {
            $serviceKey = (string)$topIssues[0]['related_service'];
        }

        $catalog = Config::serviceCatalog();
        $service = $catalog[$serviceKey] ?? $catalog['seo_optimization'];
        $service['key'] = $serviceKey;
        $service['available'] = !empty($service['url']);

        return $service;
    }

    /**
     * @param array<int,array<string,mixed>> $topIssues
     * @return array<int,array<string,mixed>>
     */
    private static function buildActionPlan(array $topIssues): array
    {
        $actions = [];
        foreach ($topIssues as $issue) {
            $complexity = (string)($issue['fix_complexity'] ?? 'medium');
            $actions[] = [
                'title' => (string)$issue['label'],
                'why' => (string)$issue['impact_text'],
                'how' => (string)$issue['tip'],
                'duration' => self::durationFor($complexity),
                'owner' => self::ownerFor($complexity),
                'fix_complexity' => $complexity,
                'related_service' => (string)($issue['related_service'] ?? 'seo_optimization'),
            ];
        }

        return $actions;
    }

    private static function durationFor(string $complexity): string
    {
        return match ($complexity) {
            'easy' => 'aprox. 1 ora',
            'hard' => 'aprox. 1 saptamana',
            default => 'aprox. 1 zi',
        };
    }

    private static function ownerFor(string $complexity): string
    {
        return match ($complexity) {
            'easy' => 'tu sau editorul site-ului',
            'hard' => 'echipa tehnica / agentie',
            default => 'dezvoltator sau marketer',
        };
    }

}
