<?php
declare(strict_types=1);

final class ReportPdfRenderer
{
    private const REPORT_CATEGORIES = [
        [
            'key' => 'content',
            'code' => 'CONTENT',
            'name' => 'Conținut & Media',
            'desc' => 'Lungime text, H1/H2/H3, liste, imagini și ALT-uri',
            'max' => 40,
            'score_source' => 'breakdown',
            'score_key' => 'content',
            'ids' => [
                'word_count_800',
                'intro_mentions_topic',
                'h1_single',
                'headings_hierarchy',
                'lists_tables',
                'images_in_body',
                'img_alt_ratio_80',
                'lazyload_images',
                'date_published',
                'date_modified',
                'author_visible_or_schema',
            ],
        ],
        [
            'key' => 'structure',
            'code' => 'STRUCTURE',
            'name' => 'Structură & Indexare',
            'desc' => 'Robots, canonical, URL, linkuri interne și externe',
            'max' => 25,
            'score_source' => 'breakdown',
            'score_key' => 'structure',
            'ids' => [
                'indexable',
                'canonical_present',
                'canonical_valid',
                'url_clean',
                'internal_links_present',
                'external_links_present',
                'meta_robots_ok',
                'html_valid',
                'image_dimensions_defined',
                'fonts_preload',
                'cls_risky_elements',
            ],
        ],
        [
            'key' => 'meta',
            'code' => 'META',
            'name' => 'Metadate & Rich Snippets',
            'desc' => 'Title, meta description, schema și Open Graph',
            'max' => 20,
            'score_source' => 'weighted',
            'score_key' => '',
            'ids' => [
                'title_length_ok',
                'meta_description_ok',
                'og_minimal',
                'schema_article_recommended',
                'faq_schema_present',
                'schema_breadcrumbs',
                'schema_image_required_fields',
            ],
        ],
        [
            'key' => 'local',
            'code' => 'LOCAL',
            'name' => 'Localizare RO',
            'desc' => 'Limbă, formate locale, hreflang și semnale locale de bază',
            'max' => 15,
            'score_source' => 'breakdown',
            'score_key' => 'locale',
            'ids' => [
                'lang_ro',
                'og_locale_or_inLanguage_ro',
                'date_format_ro',
                'hreflang_pairs',
            ],
        ],
        [
            'key' => 'seo-local',
            'code' => 'SEO-LOCAL',
            'name' => 'SEO local',
            'desc' => 'LocalBusiness schema, hartă, reviews și click-to-call',
            'max' => 30,
            'score_source' => 'local_points',
            'score_key' => 'points',
            'ids' => [
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
            ],
        ],
    ];

    private const IMPACT_BUCKETS = [
        ['key' => 'high', 'label' => 'Impact mare'],
        ['key' => 'med', 'label' => 'Impact mediu'],
        ['key' => 'low', 'label' => 'Impact redus'],
    ];

    private const PAGE_WIDTH = 841.89;
    private const PAGE_HEIGHT = 595.28;
    private const MARGIN_X = 30.0;
    private const MARGIN_Y = 28.0;
    private const CONTENT_WIDTH = 781.89;
    private const BOTTOM_LIMIT = 565.28;

    private const COLOR_TEXT = [0.09, 0.13, 0.21];
    private const COLOR_MUTED = [0.39, 0.45, 0.54];
    private const COLOR_BORDER = [0.88, 0.82, 0.73];
    private const COLOR_HEADER = [1.00, 0.95, 0.89];
    private const COLOR_ROW_ALT = [0.99, 0.98, 0.96];
    private const COLOR_ORANGE = [0.97, 0.45, 0.09];
    private const COLOR_ORANGE_DARK = [0.76, 0.25, 0.05];
    private const COLOR_GREEN = [0.10, 0.51, 0.22];
    private const COLOR_AMBER = [0.78, 0.45, 0.02];
    private const COLOR_RED = [0.82, 0.15, 0.15];

    private const ISSUE_COLUMNS = [
        ['key' => 'nr', 'label' => '#', 'w' => 22.0],
        ['key' => 'category', 'label' => 'Categorie', 'w' => 70.0],
        ['key' => 'status', 'label' => 'Status', 'w' => 48.0],
        ['key' => 'impact', 'label' => 'Impact', 'w' => 64.0],
        ['key' => 'label', 'label' => 'Verificare', 'w' => 145.0],
        ['key' => 'evidence', 'label' => 'Dovada detectata', 'w' => 190.0],
        ['key' => 'advice', 'label' => 'Sfat de rezolvare', 'w' => 242.89],
    ];

    private const ACTION_COLUMNS = [
        ['key' => 'nr', 'label' => '#', 'w' => 22.0],
        ['key' => 'title', 'label' => 'Actiune', 'w' => 165.0],
        ['key' => 'why', 'label' => 'De ce conteaza', 'w' => 205.0],
        ['key' => 'how', 'label' => 'Cum se rezolva', 'w' => 255.0],
        ['key' => 'meta', 'label' => 'Detalii', 'w' => 134.89],
    ];

    public static function render(string $url, array $snapshot): string
    {
        $model = self::buildReportModel($url, $snapshot);
        $pdf = new SimplePdfDocument(self::PAGE_WIDTH, self::PAGE_HEIGHT);
        $layout = [
            'pdf' => $pdf,
            'y' => self::MARGIN_Y,
            'page' => 0,
        ];

        self::startPage($layout, $model, true);
        self::renderHeader($layout, $model);
        self::renderScoreStrip($layout, $model);
        self::renderCategoryGrid($layout, $model);
        self::renderIssuesGrid($layout, $model);
        self::renderActionGrid($layout, $model);
        self::renderClosingNote($layout, $model);

        return $pdf->render();
    }

    private static function startPage(array &$layout, array $model, bool $firstPage): void
    {
        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        $pdf->addPage();
        $layout['page']++;
        $layout['y'] = self::MARGIN_Y;

        if ($firstPage) {
            return;
        }

        $pdf->addText(self::MARGIN_X, $layout['y'], 'Raport SEO complet', 9, true, self::COLOR_ORANGE_DARK);
        $pdf->addText(self::MARGIN_X + 104, $layout['y'], self::truncate($model['domain'], 48), 9, false, self::COLOR_MUTED);
        $pdf->addText(self::PAGE_WIDTH - self::MARGIN_X - 52, $layout['y'], 'Pag. ' . (string)$layout['page'], 9, false, self::COLOR_MUTED);
        $pdf->addLine(self::MARGIN_X, $layout['y'] + 13, self::PAGE_WIDTH - self::MARGIN_X, $layout['y'] + 13, 0.7, self::COLOR_BORDER);
        $layout['y'] += 24;
    }

    private static function willBreak(array $layout, float $height): bool
    {
        return ($layout['y'] + $height) > self::BOTTOM_LIMIT;
    }

    private static function ensureSpace(array &$layout, float $height, array $model): bool
    {
        if (!self::willBreak($layout, $height)) {
            return false;
        }

        self::startPage($layout, $model, false);
        return true;
    }

    private static function renderHeader(array &$layout, array $model): void
    {
        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        $top = $layout['y'];

        $pdf->addFilledRect(self::MARGIN_X, $top, 5, 88, self::COLOR_ORANGE);
        $pdf->addText(self::MARGIN_X + 16, $top + 4, 'NOVAWEB AUDIT SEO', 10, true, self::COLOR_ORANGE_DARK);
        $pdf->addText(self::MARGIN_X + 16, $top + 28, self::truncate($model['domain'], 42), 25, true, self::COLOR_TEXT);
        $pdf->addText(self::MARGIN_X + 16, $top + 49, self::truncate($model['title'], 104), 10, false, self::COLOR_MUTED);
        $pdf->addText(
            self::MARGIN_X + 16,
            $top + 66,
            self::normalizeText($model['context_label'] . ' | Generat la ' . $model['created_label']),
            9,
            false,
            self::COLOR_MUTED
        );

        $scoreX = self::PAGE_WIDTH - self::MARGIN_X - 140;
        $pdf->addText($scoreX, $top + 10, 'Scor SEO', 10, true, self::COLOR_MUTED);
        $pdf->addText($scoreX, $top + 45, (string)$model['total'], 36, true, self::COLOR_ORANGE_DARK);
        $pdf->addText($scoreX + 56, $top + 45, '/100', 13, true, self::COLOR_MUTED);
        $pdf->addText($scoreX, $top + 66, self::normalizeText($model['verdict']['label']), 11, true, self::COLOR_TEXT);

        $layout['y'] = $top + 104;
    }

    private static function renderScoreStrip(array &$layout, array $model): void
    {
        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        $top = $layout['y'];

        $pdf->addLine(self::MARGIN_X, $top, self::PAGE_WIDTH - self::MARGIN_X, $top, 0.8, self::COLOR_BORDER);
        $pdf->addText(self::MARGIN_X, $top + 18, 'OK', 8, true, self::COLOR_GREEN);
        $pdf->addText(self::MARGIN_X + 24, $top + 18, (string)$model['counts']['pass'], 16, true, self::COLOR_GREEN);
        $pdf->addText(self::MARGIN_X + 92, $top + 18, 'Avertizări', 8, true, self::COLOR_AMBER);
        $pdf->addText(self::MARGIN_X + 154, $top + 18, (string)$model['counts']['warn'], 16, true, self::COLOR_AMBER);
        $pdf->addText(self::MARGIN_X + 220, $top + 18, 'Probleme', 8, true, self::COLOR_RED);
        $pdf->addText(self::MARGIN_X + 278, $top + 18, (string)$model['counts']['fail'], 16, true, self::COLOR_RED);

        self::writeLines(
            $pdf,
            self::MARGIN_X + 356,
            $top + 8,
            self::wrapText($model['verdict']['summary'], 424, 9),
            9,
            false,
            self::COLOR_MUTED,
            11
        );

        $pdf->addLine(self::MARGIN_X, $top + 38, self::PAGE_WIDTH - self::MARGIN_X, $top + 38, 0.8, self::COLOR_BORDER);
        $layout['y'] = $top + 56;
    }

    private static function renderCategoryGrid(array &$layout, array $model): void
    {
        if ($model['categories'] === []) {
            return;
        }

        self::ensureSpace($layout, 118, $model);
        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        $pdf->addText(self::MARGIN_X, $layout['y'], 'Scor pe categorii', 15, true, self::COLOR_TEXT);
        $layout['y'] += 18;

        $columns = [
            ['key' => 'name', 'label' => 'Categorie', 'w' => 190.0],
            ['key' => 'score', 'label' => 'Scor', 'w' => 48.0],
            ['key' => 'pass', 'label' => 'OK', 'w' => 42.0],
            ['key' => 'warn', 'label' => 'Warn', 'w' => 50.0],
            ['key' => 'fail', 'label' => 'Prob.', 'w' => 50.0],
            ['key' => 'desc', 'label' => 'Ce include', 'w' => 401.89],
        ];
        self::drawTableHeader($layout, $model, $columns, 17);

        foreach ($model['categories'] as $index => $category) {
            $row = [
                'name' => $category['name'],
                'score' => (string)$category['score'] . '/' . (string)$category['max'],
                'pass' => (string)$category['pass_count'],
                'warn' => (string)$category['warn_count'],
                'fail' => (string)$category['fail_count'],
                'desc' => $category['desc'],
            ];
            self::drawDataRow($layout, $model, $columns, $row, 9, $index % 2 === 1);
        }

        $layout['y'] += 14;
    }

    private static function renderIssuesGrid(array &$layout, array $model): void
    {
        self::ensureSpace($layout, 44, $model);
        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        $pdf->addText(self::MARGIN_X, $layout['y'], 'Probleme și recomandări', 15, true, self::COLOR_TEXT);
        $pdf->addText(self::MARGIN_X + 168, $layout['y'], 'Doar warning/error. Check-urile OK nu au recomandări în PDF.', 9, false, self::COLOR_MUTED);
        $layout['y'] += 18;

        if ($model['issues'] === []) {
            $pdf->addText(self::MARGIN_X, $layout['y'], 'Snapshotul curent nu conține probleme individuale de listat.', 10, false, self::COLOR_GREEN);
            $layout['y'] += 22;
            return;
        }

        self::drawTableHeader($layout, $model, self::ISSUE_COLUMNS, 18);

        foreach ($model['issues'] as $index => $issue) {
            $row = [
                'nr' => (string)($index + 1),
                'category' => $issue['category_code'],
                'status' => $issue['severity_label'],
                'impact' => $issue['impact_label'] . "\n" . $issue['complexity_label'],
                'label' => $issue['label'],
                'evidence' => $issue['evidence'],
                'advice' => $issue['advice'],
            ];
            self::drawDataRow($layout, $model, self::ISSUE_COLUMNS, $row, 8, $index % 2 === 1, [
                'status' => self::toneAccent($issue['severity']),
                'impact' => self::impactColor($issue['impact_key']),
            ]);
        }

        $layout['y'] += 14;
    }

    private static function renderActionGrid(array &$layout, array $model): void
    {
        if ($model['actions'] === []) {
            return;
        }

        self::ensureSpace($layout, 48, $model);
        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        $pdf->addText(self::MARGIN_X, $layout['y'], 'Pași de remediere prioritari', 15, true, self::COLOR_TEXT);
        $layout['y'] += 18;
        self::drawTableHeader($layout, $model, self::ACTION_COLUMNS, 18);

        foreach ($model['actions'] as $index => $action) {
            $row = [
                'nr' => (string)($index + 1),
                'title' => $action['title'],
                'why' => $action['why'],
                'how' => $action['how'],
                'meta' => 'Durata: ' . $action['duration'] . "\nOwner: " . $action['owner'],
            ];
            self::drawDataRow($layout, $model, self::ACTION_COLUMNS, $row, 8, $index % 2 === 1);
        }

        $layout['y'] += 14;
    }

    private static function renderClosingNote(array &$layout, array $model): void
    {
        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        self::ensureSpace($layout, 42, $model);

        $pdf->addLine(self::MARGIN_X, $layout['y'], self::PAGE_WIDTH - self::MARGIN_X, $layout['y'], 0.8, self::COLOR_BORDER);
        $layout['y'] += 14;
        $pdf->addText(self::MARGIN_X, $layout['y'], 'Link raport complet:', 10, true, self::COLOR_TEXT);
        self::writeLines(
            $pdf,
            self::MARGIN_X + 104,
            $layout['y'],
            self::wrapText($model['report_url'], self::CONTENT_WIDTH - 106, 9),
            9,
            false,
            self::COLOR_ORANGE_DARK,
            11
        );
        $layout['y'] += 24;
    }

    private static function drawTableHeader(array &$layout, array $model, array $columns, float $height): void
    {
        self::ensureSpace($layout, $height, $model);
        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        $x = self::MARGIN_X;
        $y = $layout['y'];

        $pdf->addFilledRect($x, $y, self::CONTENT_WIDTH, $height, self::COLOR_HEADER);
        self::drawGridLines($pdf, $x, $y, $columns, $height);

        foreach ($columns as $column) {
            $pdf->addText($x + 4, $y + 12, self::normalizeText((string)$column['label']), 7.8, true, self::COLOR_ORANGE_DARK);
            $x += (float)$column['w'];
        }

        $layout['y'] += $height;
    }

    private static function drawDataRow(
        array &$layout,
        array $model,
        array $columns,
        array $row,
        float $fontSize,
        bool $alt = false,
        array $cellColors = []
    ): void {
        $wrapped = [];
        $maxLines = 1;
        foreach ($columns as $column) {
            $key = (string)$column['key'];
            $lines = self::wrapText((string)($row[$key] ?? ''), max(10, (float)$column['w'] - 8), $fontSize);
            if ($lines === []) {
                $lines = [''];
            }
            $wrapped[$key] = $lines;
            $maxLines = max($maxLines, count($lines));
        }

        $lineHeight = $fontSize + 2.0;
        $height = max(24.0, 10.0 + ($maxLines * $lineHeight));

        if (self::willBreak($layout, $height)) {
            self::startPage($layout, $model, false);
            self::drawTableHeader($layout, $model, $columns, 18);
        }

        /** @var SimplePdfDocument $pdf */
        $pdf = $layout['pdf'];
        $x = self::MARGIN_X;
        $y = $layout['y'];

        if ($alt) {
            $pdf->addFilledRect($x, $y, self::CONTENT_WIDTH, $height, self::COLOR_ROW_ALT);
        }
        self::drawGridLines($pdf, $x, $y, $columns, $height);

        foreach ($columns as $column) {
            $key = (string)$column['key'];
            $color = $cellColors[$key] ?? self::COLOR_TEXT;
            self::writeLines($pdf, $x + 4, $y + 10, $wrapped[$key], $fontSize, false, $color, $lineHeight);
            $x += (float)$column['w'];
        }

        $layout['y'] += $height;
    }

    private static function drawGridLines(SimplePdfDocument $pdf, float $x, float $y, array $columns, float $height): void
    {
        $pdf->addLine($x, $y, $x + self::CONTENT_WIDTH, $y, 0.5, self::COLOR_BORDER);
        $pdf->addLine($x, $y + $height, $x + self::CONTENT_WIDTH, $y + $height, 0.5, self::COLOR_BORDER);

        $cursor = $x;
        $pdf->addLine($cursor, $y, $cursor, $y + $height, 0.5, self::COLOR_BORDER);
        foreach ($columns as $column) {
            $cursor += (float)$column['w'];
            $pdf->addLine($cursor, $y, $cursor, $y + $height, 0.5, self::COLOR_BORDER);
        }
    }

    private static function writeLines(
        SimplePdfDocument $pdf,
        float $x,
        float $y,
        array $lines,
        float $fontSize,
        bool $bold,
        array $color,
        float $lineHeight
    ): void {
        foreach ($lines as $line) {
            $pdf->addText($x, $y, $line, $fontSize, $bold, $color);
            $y += $lineHeight;
        }
    }

    private static function wrapText(string $text, float $maxWidth, float $fontSize): array
    {
        $normalized = self::normalizeText($text);
        if ($normalized === '') {
            return [];
        }

        $maxChars = max(8, (int)floor($maxWidth / max(3.8, $fontSize * 0.50)));
        $paragraphs = preg_split('/\n+/u', $normalized) ?: [$normalized];
        $lines = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            $current = '';
            $words = preg_split('/\s+/u', $paragraph) ?: [];
            foreach ($words as $word) {
                $candidate = $current === '' ? $word : $current . ' ' . $word;
                if (mb_strlen($candidate, 'UTF-8') <= $maxChars) {
                    $current = $candidate;
                    continue;
                }

                if ($current !== '') {
                    $lines[] = $current;
                }

                while (mb_strlen($word, 'UTF-8') > $maxChars) {
                    $lines[] = mb_substr($word, 0, $maxChars - 1, 'UTF-8') . '-';
                    $word = mb_substr($word, $maxChars - 1, null, 'UTF-8');
                }

                $current = $word;
            }

            if ($current !== '') {
                $lines[] = $current;
            }
        }

        return $lines;
    }

    private static function buildReportModel(string $url, array $snapshot): array
    {
        $score = (array)($snapshot['score'] ?? []);
        $checks = array_values(array_filter(
            (array)($score['checks'] ?? []),
            static fn(mixed $check): bool => is_array($check)
        ));

        $total = max(0, min(100, (int)($score['total'] ?? 0)));
        $counts = self::buildSeverityCounts($checks);
        $domain = self::domainFromUrl($url);
        $title = self::meaningfulText((string)($score['meta']['title'] ?? ''), $domain);
        $categories = self::buildCategories($snapshot, $checks);
        $problemCategories = array_values(array_filter(
            $categories,
            static fn(array $category): bool => ($category['problem_count'] ?? 0) > 0
        ));

        return [
            'url' => $url,
            'domain' => $domain,
            'title' => $title,
            'context_label' => self::contextLabel((string)($snapshot['context'] ?? 'article')),
            'created_label' => self::formatAnalysisDate((string)($snapshot['created_at'] ?? '')),
            'report_url' => self::reportLink((string)($snapshot['report_token'] ?? '')),
            'total' => $total,
            'counts' => $counts,
            'verdict' => self::verdictForScore($total),
            'categories' => $categories,
            'problem_categories' => $problemCategories,
            'issues' => self::flattenIssues($problemCategories),
            'actions' => self::buildActions($snapshot, $checks),
        ];
    }

    private static function buildCategories(array $snapshot, array $checks): array
    {
        $categories = [];

        foreach (self::REPORT_CATEGORIES as $config) {
            $categoryChecks = array_values(array_filter(
                $checks,
                static fn(array $check): bool => in_array((string)($check['id'] ?? ''), $config['ids'], true)
            ));

            if ($categoryChecks === []) {
                continue;
            }

            $problemChecks = array_values(array_filter(
                $categoryChecks,
                static fn(array $check): bool => (string)($check['sev'] ?? 'pass') !== 'pass'
            ));
            usort($problemChecks, [self::class, 'compareIssueChecks']);

            $groups = [];
            foreach (self::IMPACT_BUCKETS as $bucket) {
                $items = [];
                foreach ($problemChecks as $check) {
                    if (self::impactBucket((string)($check['business_impact_magnitude'] ?? 'medium')) !== $bucket['key']) {
                        continue;
                    }

                    $items[] = self::buildIssueModel($check);
                }

                if ($items !== []) {
                    $groups[] = [
                        'key' => $bucket['key'],
                        'label' => $bucket['label'],
                        'items' => $items,
                    ];
                }
            }

            $passCount = count(array_filter(
                $categoryChecks,
                static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'pass'
            ));
            $warnCount = count(array_filter(
                $categoryChecks,
                static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'warn'
            ));
            $failCount = count(array_filter(
                $categoryChecks,
                static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'fail'
            ));
            $scoreValue = self::resolveCategoryScore($snapshot, $config, $categoryChecks);

            $categories[] = [
                'key' => $config['key'],
                'code' => $config['code'],
                'name' => $config['name'],
                'desc' => $config['desc'],
                'max' => (int)$config['max'],
                'score' => $scoreValue,
                'tone' => self::categoryTone($scoreValue, (int)$config['max']),
                'problem_count' => count($problemChecks),
                'pass_count' => $passCount,
                'warn_count' => $warnCount,
                'fail_count' => $failCount,
                'groups' => $groups,
            ];
        }

        return $categories;
    }

    private static function flattenIssues(array $problemCategories): array
    {
        $rows = [];

        foreach ($problemCategories as $category) {
            foreach ($category['groups'] as $group) {
                foreach ($group['items'] as $issue) {
                    $rows[] = array_merge($issue, [
                        'category_code' => (string)$category['code'],
                        'category_name' => (string)$category['name'],
                        'impact_key' => (string)$group['key'],
                    ]);
                }
            }
        }

        return $rows;
    }

    private static function buildIssueModel(array $check): array
    {
        $severity = (string)($check['sev'] ?? 'warn');
        $rule = self::meaningfulText((string)($check['rule'] ?? ''));
        $note = self::meaningfulText((string)($check['note'] ?? ''), $rule);
        $advice = self::meaningfulText(
            (string)($check['tip'] ?? ''),
            self::meaningfulText(
                (string)($check['rule'] ?? ''),
                'Verifica manual aceasta zona si corecteaza implementarea conform recomandarii SEO.'
            )
        );

        return [
            'label' => self::meaningfulText((string)($check['label'] ?? ''), (string)($check['id'] ?? 'Verificare SEO')),
            'severity' => $severity,
            'severity_label' => self::severityLabel($severity),
            'impact_label' => self::impactLabel((string)($check['business_impact_magnitude'] ?? 'medium')),
            'impact_text' => self::meaningfulText(
                (string)($check['business_impact_text'] ?? ''),
                'Impactul comercial estimat nu este disponibil in snapshotul curent.'
            ),
            'evidence' => $note !== '' ? $note : 'Snapshotul curent nu include o dovada suplimentara pentru aceasta verificare.',
            'advice' => $advice,
            'complexity_label' => self::complexityLabel((string)($check['fix_complexity'] ?? 'medium')),
        ];
    }

    private static function buildActions(array $snapshot, array $checks): array
    {
        $business = (array)($snapshot['business'] ?? []);
        $rawActions = array_values(array_filter(
            array_slice((array)($business['action_plan'] ?? []), 0, 5),
            static fn(mixed $action): bool => is_array($action)
        ));

        if ($rawActions === []) {
            $fallbackIssues = array_values(array_filter(
                (array)($business['top_business_issues'] ?? []),
                static fn(mixed $issue): bool => is_array($issue)
            ));

            if ($fallbackIssues === []) {
                $fallbackIssues = array_slice(array_values(array_filter(
                    $checks,
                    static fn(array $check): bool => (string)($check['sev'] ?? 'pass') !== 'pass'
                )), 0, 5);
            }

            foreach ($fallbackIssues as $issue) {
                $rawActions[] = [
                    'title' => (string)($issue['label'] ?? $issue['title'] ?? 'Acțiune recomandată'),
                    'why' => (string)($issue['impact_text'] ?? $issue['business_impact_text'] ?? ''),
                    'how' => (string)($issue['how'] ?? $issue['tip'] ?? $issue['rule'] ?? ''),
                    'duration' => (string)($issue['duration'] ?? self::durationForComplexity((string)($issue['fix_complexity'] ?? 'medium'))),
                    'owner' => (string)($issue['owner'] ?? self::ownerForComplexity((string)($issue['fix_complexity'] ?? 'medium'))),
                    'fix_complexity' => (string)($issue['fix_complexity'] ?? 'medium'),
                ];
            }
        }

        $actions = [];
        foreach (array_slice($rawActions, 0, 5) as $action) {
            $actions[] = [
                'title' => self::meaningfulText((string)($action['title'] ?? ''), 'Acțiune recomandată'),
                'why' => self::meaningfulText(
                    (string)($action['why'] ?? ''),
                    'Această acțiune are impact direct asupra clarității și vizibilității SEO.'
                ),
                'how' => self::meaningfulText(
                    (string)($action['how'] ?? ''),
                    'Pornește de la recomandarea din problema asociată și validează implementarea după schimbare.'
                ),
                'duration' => self::meaningfulText(
                    (string)($action['duration'] ?? ''),
                    self::durationForComplexity((string)($action['fix_complexity'] ?? 'medium'))
                ),
                'owner' => self::meaningfulText(
                    (string)($action['owner'] ?? ''),
                    self::ownerForComplexity((string)($action['fix_complexity'] ?? 'medium'))
                ),
            ];
        }

        return $actions;
    }

    private static function resolveCategoryScore(array $snapshot, array $config, array $checks): int
    {
        $max = (int)$config['max'];
        $scoreSource = (string)($config['score_source'] ?? 'weighted');
        $scoreKey = (string)($config['score_key'] ?? '');

        $value = null;
        if ($scoreSource === 'breakdown') {
            $candidate = ($snapshot['score']['breakdown'][$scoreKey] ?? null);
            if (is_numeric($candidate)) {
                $value = (int)round((float)$candidate);
            }
        } elseif ($scoreSource === 'local_points') {
            $candidate = ($snapshot['score']['local'][$scoreKey] ?? null);
            if (is_numeric($candidate)) {
                $value = (int)round((float)$candidate);
            }
        }

        if ($scoreSource === 'weighted' || $value === null) {
            $value = self::weightedPoints($checks, $max);
        }

        return max(0, min($max, $value));
    }

    private static function weightedPoints(array $checks, int $max): int
    {
        if ($checks === []) {
            return 0;
        }

        $total = 0.0;
        foreach ($checks as $check) {
            $severity = (string)($check['sev'] ?? 'pass');
            if ($severity === 'pass') {
                $total += 1.0;
            } elseif ($severity === 'warn') {
                $total += 0.5;
            }
        }

        return (int)round(($total / count($checks)) * $max);
    }

    private static function buildSeverityCounts(array $checks): array
    {
        return [
            'pass' => count(array_filter($checks, static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'pass')),
            'warn' => count(array_filter($checks, static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'warn')),
            'fail' => count(array_filter($checks, static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'fail')),
        ];
    }

    private static function compareIssueChecks(array $left, array $right): int
    {
        $impactOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        $severityOrder = ['fail' => 0, 'warn' => 1, 'pass' => 2];

        $leftImpact = $impactOrder[(string)($left['business_impact_magnitude'] ?? 'medium')] ?? 10;
        $rightImpact = $impactOrder[(string)($right['business_impact_magnitude'] ?? 'medium')] ?? 10;
        if ($leftImpact !== $rightImpact) {
            return $leftImpact <=> $rightImpact;
        }

        $leftSeverity = $severityOrder[(string)($left['sev'] ?? 'warn')] ?? 10;
        $rightSeverity = $severityOrder[(string)($right['sev'] ?? 'warn')] ?? 10;
        if ($leftSeverity !== $rightSeverity) {
            return $leftSeverity <=> $rightSeverity;
        }

        return strcmp(
            self::meaningfulText((string)($left['label'] ?? ''), (string)($left['id'] ?? '')),
            self::meaningfulText((string)($right['label'] ?? ''), (string)($right['id'] ?? ''))
        );
    }

    private static function verdictForScore(int $total): array
    {
        if ($total < 40) {
            return [
                'label' => 'Foarte slab',
                'summary' => 'Pagina are blocaje majore care opresc atât vizibilitatea, cât și claritatea.',
            ];
        }

        if ($total < 60) {
            return [
                'label' => 'Fragil',
                'summary' => 'Există fundație, dar sunt prea multe probleme ca raportul să poată fi delegat imediat.',
            ];
        }

        if ($total < 75) {
            return [
                'label' => 'Decent',
                'summary' => 'Ai baza funcțională, însă sunt quick wins și blocaje care merită prioritizate.',
            ];
        }

        if ($total < 90) {
            return [
                'label' => 'Bun',
                'summary' => 'Pagina este sănătoasă, iar câștigurile vin din rafinare și prioritizare disciplinată.',
            ];
        }

        return [
            'label' => 'Foarte bun',
            'summary' => 'Pagina este solidă on-page; diferența vine din consistență și scalare.',
        ];
    }

    private static function categoryTone(int $score, int $max): string
    {
        $pct = $max > 0 ? ($score / $max) * 100 : 0;
        if ($pct >= 85) {
            return 'ok';
        }
        if ($pct >= 55) {
            return 'warn';
        }
        return 'danger';
    }

    private static function severityLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'fail' => 'Problem',
            'warn' => 'Warning',
            default => 'OK',
        };
    }

    private static function impactLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'high' => 'Impact mare',
            'low' => 'Impact redus',
            default => 'Impact mediu',
        };
    }

    private static function impactBucket(string $value): string
    {
        return match (strtolower(trim($value))) {
            'high' => 'high',
            'low' => 'low',
            default => 'med',
        };
    }

    private static function complexityLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'easy' => 'Quick win',
            'hard' => 'Hard',
            default => 'Medium',
        };
    }

    private static function durationForComplexity(string $value): string
    {
        return match (strtolower(trim($value))) {
            'easy' => 'aprox. 1 oră',
            'hard' => 'aprox. 1 săptămână',
            default => 'aprox. 1 zi',
        };
    }

    private static function ownerForComplexity(string $value): string
    {
        return match (strtolower(trim($value))) {
            'easy' => 'tu sau editorul site-ului',
            'hard' => 'echipa tehnica / agentie',
            default => 'dezvoltator sau marketer',
        };
    }

    private static function toneAccent(string $tone): array
    {
        return match ($tone) {
            'ok' => self::COLOR_GREEN,
            'danger' => self::COLOR_RED,
            default => self::COLOR_AMBER,
        };
    }

    private static function impactColor(string $tone): array
    {
        return match ($tone) {
            'high' => self::COLOR_RED,
            'low' => self::COLOR_GREEN,
            default => self::COLOR_AMBER,
        };
    }

    private static function contextLabel(string $context): string
    {
        return strtolower(trim($context)) === 'local' ? 'Audit local' : 'Audit standard';
    }

    private static function formatAnalysisDate(string $date): string
    {
        if ($date === '') {
            return 'analiza recenta';
        }

        try {
            return (new DateTimeImmutable($date))
                ->setTimezone(new DateTimeZone('Europe/Bucharest'))
                ->format('d.m.Y H:i');
        } catch (\Throwable) {
            return 'analiza recenta';
        }
    }

    private static function domainFromUrl(string $url): string
    {
        $host = (string)(parse_url($url, PHP_URL_HOST) ?: '');
        if ($host === '') {
            return $url;
        }

        return preg_replace('/^www\./i', '', $host) ?: $host;
    }

    private static function reportLink(string $token): string
    {
        if ($token !== '' && class_exists('Config') && method_exists('Config', 'canonicalUrl')) {
            $query = '?report=' . urlencode($token);
            if (class_exists('ReportAccess')) {
                $accessToken = ReportAccess::accessToken($token);
                if ($accessToken !== '') {
                    $query .= '&access=' . urlencode($accessToken);
                }
            }

            return (string)Config::canonicalUrl($query) . '#workspaceShell';
        }

        if (class_exists('Config') && method_exists('Config', 'canonicalUrl')) {
            return (string)Config::canonicalUrl('#workspaceShell');
        }

        return '';
    }

    private static function meaningfulText(string $value, string $fallback = ''): string
    {
        $text = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $normalized = trim(str_replace(['-', '-', 'Ã¢â‚¬â€', 'Ã¢â‚¬â€œ'], '-', $text));
        if ($normalized === '' || $normalized === '-' || strtolower($normalized) === 'n/a') {
            return $fallback;
        }

        return $text;
    }

    private static function normalizeText(string $value): string
    {
        $text = self::meaningfulText($value);
        $text = preg_replace('/[^\P{C}\n\t]+/u', '', $text) ?? $text;
        $text = trim(preg_replace('/[ \t]+/u', ' ', $text) ?? $text);
        return $text;
    }

    private static function truncate(string $value, int $maxLength): string
    {
        $text = self::normalizeText($value);
        if (mb_strlen($text, 'UTF-8') <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $maxLength - 3, 'UTF-8')) . '...';
    }
}
