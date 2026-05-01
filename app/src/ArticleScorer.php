<?php
declare(strict_types=1);

/* --- Polyfills pentru compatibilitate (nu se execută pe PHP 8+) --- */
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        if ($needle === '') return true;
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}
if (!function_exists('array_is_list')) {
    function array_is_list(array $arr): bool {
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i++) return false;
        }
        return true;
    }
}

final class ArticleScorer
{
    /**
     * ponderea principală rămâne 100 total.
     * NOU: twitter_card_large scos; +2 puncte mutate la schema_article_recommended.
     * NOILE teste moderne au pondere 0 (informative, nu afectează scorul).
     */
    private const W = [
        // Content & UX (40)
        'word_count_800' => 10,
        'intro_mentions_topic' => 4,
        'h1_single' => 6,
        'headings_hierarchy' => 6,
        'lists_tables' => 2,
        'images_in_body' => 3,
        'img_alt_ratio_80' => 3,
        'lazyload_images' => 2,
        'date_published' => 1,
        'date_modified' => 1,
        'author_visible_or_schema' => 4,

        // Structură & Indexare (25)
        'indexable' => 8,
        'canonical_present' => 5,
        'canonical_valid' => 5,
        'url_clean' => 4,
        'internal_links_present' => 2,
        'external_links_present' => 1,

        // Metadate & Rich Snippets (20)
        'title_length_ok' => 6,
        'meta_description_ok' => 4,
        'og_minimal' => 4,
        'schema_article_recommended' => 6, // era 4, preia +2 de la twitter_card_large

        // Localizare RO (15)
        'lang_ro' => 7,
        'og_locale_or_inLanguage_ro' => 4,
        'date_format_ro' => 3,
        'hreflang_pairs' => 1,

        // Verificări moderne adiționale (informative, weight = 0)
        'faq_schema_present'           => 0,
        'html_valid'                   => 0,
        'meta_robots_ok'               => 0,
        'image_dimensions_defined'     => 0,
        'fonts_preload'                => 0,
        'cls_risky_elements'           => 0,
        'schema_breadcrumbs'           => 0,
        'schema_image_required_fields' => 0,
    ];

    /**
     * Pondere pentru verificările de SEO local extinse (local_*),
     * folosite doar când contextul = "local".
     */
    private const W_LOCAL = [
        'local_tel_click'            => 2,
        'local_tel_prefix_local'     => 2,
        'local_address_visible'      => 2,
        'local_directions_link'      => 1,
        'local_opening_hours'        => 1,
        'local_schema_localbusiness' => 3,
        'local_schema_postal'        => 2,
        'local_schema_geo'           => 1,
        'local_schema_sameas'        => 1,
        'local_schema_rating'        => 1,
        'local_city_detected'        => 2,
        'local_city_in_title'        => 2,
        'local_city_in_h1'           => 2,
        'local_city_in_slug'         => 2,
        'local_city_in_intro'        => 2,
        'local_map_embed'            => 1,
        'local_alt_has_city'         => 1,
        'local_locator'              => 1,
        'local_whatsapp'             => 1,
    ];

    private const LOCAL_MAX = 30;
    private const EXTRA_CHECK_IDS = [
        'local_schema_tel',
        'local_schema_area',
    ];

    /**
     * @return array<int,string>
     */
    public static function allCheckIds(): array
    {
        return array_values(array_merge(array_keys(self::W), array_keys(self::W_LOCAL), self::EXTRA_CHECK_IDS));
    }

    /**
     * Verificări care, atunci când NU sunt îndeplinite, se afișează ca „Avertisment”
     * (nu ca „Nereușit”). Poți ajusta lista după preferințe.
     */
    private const WARN_FAIL_IDS = [
        'title_length_ok',
        'meta_description_ok',
        'date_published',
        'date_modified',
        'author_visible_or_schema',
        'schema_article_recommended',
        'faq_schema_present',
        'fonts_preload',
        'cls_risky_elements',
        'schema_breadcrumbs',
        'schema_image_required_fields',
        'html_valid',
        'hreflang_pairs'

    ];

    /**
     * Returnează severitatea pentru UI: pass | warn | fail
     * - local_* sunt WARN în context „article” (informativ), dar FAIL în context „local”
     * - unele teste sunt WARN by default (ex: meta description, schema, date)
     */
    private static function severity(string $id, bool $ok, string $context): string
    {
        if ($ok) return 'pass';

        if (str_starts_with($id, 'local_')) {
            return ($context === 'local') ? 'fail' : 'warn';
        }

        return in_array($id, self::WARN_FAIL_IDS, true) ? 'warn' : 'fail';
    }


    /** listă urbană minimală; poți extinde din config */
    private static function cityList(): array {
        return [
            'București',
            'Cluj-Napoca',
            'Timișoara',
            'Iași',
            'Constanța',
            'Brașov',
            'Sibiu',
            'Arad',
            'Oradea',
            'Ploiești',
            'Galați',
            'Pitești',
            'Târgu Mureș',
            'Craiova',
            'Baia Mare',
            'Suceava',
            'Buzău',
        ];
    }

private static function localityMap(): array {
        static $map = null;
        if (is_array($map)) {
            return $map;
        }

        $path = class_exists('Config') ? Config::storagePath('ro_localities.json') : __DIR__ . '/../storage/ro_localities.json';
        if (!is_file($path)) {
            $path = __DIR__ . '/../storage/ro_localities.json';
        }
        if (is_file($path)) {
            $raw = @file_get_contents($path);
            if (is_string($raw) && $raw !== '') {
                $data = json_decode($raw, true);
                if (is_array($data) && $data) {
                    $map = $data; // norm => pretty
                    return $map;
                }
            }
        }

        // fallback minimal (lista hardcodată)
        $map = [];
        foreach (self::cityList() as $c) {
            $map[self::roNorm($c)] = $c;
        }
        return $map;
    }


    private static function roNorm(string $s): string {
        $s = mb_strtolower($s, 'UTF-8');
        $s = strtr($s, [
            'ă' => 'a', 'â' => 'a', 'î' => 'i',
            'ș' => 's', 'ş' => 's',
            'ț' => 't', 'ţ' => 't',
        ]);
        $s = str_replace(['-', '_'], ' ', $s);
        return $s;
    }


    private static function tokenizeNormText(string $textNorm): array
    {
        if ($textNorm === '') {
            return [];
        }

        preg_match_all('/[a-z0-9]+/u', $textNorm, $m);
        return $m[0] ?? [];
    }

    /**
     * Returnează localități găsite în text (în forma normalizată folosită în ro_localities.json).
     * Potrivirea este pe secvențe de tokeni, nu substring, ca să evităm false-positive.
     */
    private static function extractCityCandidates(string $textNorm, array $localityMap, int $maxWords = 4): array
    {
        if ($textNorm === '') {
            return [];
        }

        $tokens = self::tokenizeNormText($textNorm);
        $count  = count($tokens);

        if ($count === 0) {
            return [];
        }

        $maxWords = max(1, min($maxWords, $count));
        $found = [];

        for ($n = $maxWords; $n >= 1; $n--) {
            for ($i = 0; $i <= $count - $n; $i++) {
                $phrase = implode(' ', array_slice($tokens, $i, $n));
                if (isset($localityMap[$phrase])) {
                    $found[$phrase] = true;
                }
            }
        }

        return array_keys($found);
    }

    private static function containsTokenSequence(string $textNorm, string $phraseNorm): bool
    {
        if ($textNorm === '' || $phraseNorm === '') {
            return false;
        }

        $tokens = self::tokenizeNormText($textNorm);
        $needle = preg_split('/\s+/u', trim($phraseNorm));

        if (!$needle || count($needle) === 0) {
            return false;
        }

        $n     = count($needle);
        $count = count($tokens);

        if ($count < $n) {
            return false;
        }

        for ($i = 0; $i <= $count - $n; $i++) {
            if (array_slice($tokens, $i, $n) === $needle) {
                return true;
            }
        }

        return false;
    }

    public static function score(string $html, string $url, string $mode = 'deep', array $options = []): array
    {
        if (trim($html) === '' || !mb_check_encoding($html, 'UTF-8')) {
            return self::renderFailurePayload($url, $options, 'render_failed');
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $loaded = @$dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET);
        if ($loaded === false) {
            return self::renderFailurePayload($url, $options, 'render_failed');
        }

        // erori libxml ≈ proxy de „HTML valid”
        $libxmlErrors = libxml_get_errors();
        libxml_clear_errors();
        $htmlValid = count($libxmlErrors) < 10; // destul de tolerant

        $xp = new \DOMXPath($dom);

        $context        = (string)($options['context'] ?? 'article');   // 'article' sau 'local'
        if ($context !== 'local') {
            $context = 'article';
        }
        $isLocalContext = ($context === 'local');

        // --- extrase de bază
        $text = self::getVisibleText($dom);
        $wordCount = self::wordCount($text);
        $title = self::first($xp, '//title');
        $metaDesc = self::meta($xp, 'description') ?? '';
        $h1s = self::all($xp, '//h1');
        $h1 = $h1s[0] ?? '';
        $robots = self::meta($xp, 'robots') ?? '';
        $robotsLower = strtolower($robots);
        $canonical = self::attr($xp, '//link[@rel="canonical"]', 'href') ?? '';
        $lang = strtolower(self::attr($xp, '//html', 'lang') ?? '');
        $ogTitle = self::metaProp($xp, 'og:title');
        $ogDesc = self::metaProp($xp, 'og:description');
        $ogImage = self::metaProp($xp, 'og:image');
        $ogUrl = self::metaProp($xp, 'og:url');
        $ogLocale = strtolower(self::metaProp($xp, 'og:locale') ?? '');

        // --- JSON-LD
        $jsonld = self::jsonLd($xp);
        $articleSchemas = array_values(array_filter($jsonld, fn($j) => self::hasType($j, ['Article','BlogPosting','NewsArticle'])));
        $inLanguage = self::findInLanguage($articleSchemas);

        $datePub = self::articleDate($articleSchemas) ?: self::visibleDate($xp) ?: self::metaProp($xp, 'article:published_time');
        $dateMod = self::articleModified($articleSchemas) ?: self::metaProp($xp, 'article:modified_time');

        $authorVisible = self::hasAny($xp, [
            '//*[contains(@class,"author") or contains(@class,"byline")]',
            '//*[@itemprop="author"]',
            '//a[contains(translate(@href,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"/author/")]'
        ]);
        $metaAuthor = self::meta($xp, 'author');
        $schemaHasAuthor = false;
        foreach ($articleSchemas as $j) {
            if (!empty($j['author'])) { $schemaHasAuthor = true; break; }
        }
        $authorOk = ($authorVisible || $metaAuthor || $schemaHasAuthor);

        $mainTerm = self::mainTerm(($title . ' ' . $h1));
        $introMentions = self::termInFirstNWords($text, $mainTerm, 100);

        $hh = self::headingsHierarchy($xp);
        $primaryContentContext = "("
            . "//article | //main | //*[@itemprop='articleBody'] | //*[@role='main'] | "
            . "//*[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' post-content ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' article-content ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' post-body ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' article-body ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' content ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' entry ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' post ') "
            . "or contains(@id, 'content') or contains(@id, 'main') or contains(@id, 'article')]"
            . ")";

        $listTableXpath = "//*[self::ul or self::ol or self::table][not("
            . "ancestor::nav or ancestor::header or ancestor::footer or ancestor::aside "
            . "or ancestor::*[@role='navigation'] "
            . "or ancestor::*["
            . "contains(concat(' ', normalize-space(@class), ' '), ' menu ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' nav ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' navbar ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' breadcrumb ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' pagination ') "
            . "or contains(concat(' ', normalize-space(@class), ' '), ' sidebar ')"
            . "]"
            . ")]";

        $hasListOrTable = (
            self::count($xp, $primaryContentContext . $listTableXpath) > 0
        ) || (
            self::count($xp, "//body" . $listTableXpath) > 0
        );

        // --- imagini
        $bodyImgCount = self::countContentImages($xp);
        $imgsTotal = self::countAllRelevantImages($xp);
        $imgsAlt   = self::countImagesWithAlt($xp);
        $altRatio  = $imgsTotal ? $imgsAlt / $imgsTotal : 1.0;
        $lazyCount = self::countLazyImages($xp);
        $hasVideo  = (self::count($xp,
            '//article//video|//article//iframe|//main//video|//main//iframe|' .
            '//*[contains(@class,"entry-content")]//video|//*[contains(@class,"entry-content")]//iframe') > 0
        );

        // imagini cu width/height (CLS)
        $dimStats = self::imageDimensionsCoverage($xp);
        $imageDimensionsDefined = $dimStats['total'] > 0
            ? ($dimStats['with'] / max(1, $dimStats['total']) >= 0.8)
            : true; // fără imagini => nu penalizăm

        // fonts preload
        $fontsPreload = self::hasPreloadFonts($xp);

        // CLS „ok” dacă avem dimensiuni la imagini + (ideal) preload fonturi
        $clsRiskOk = $imageDimensionsDefined && $fontsPreload;

        // --- linkuri (profil sumar)
        $baseHost = self::normalizeHost(parse_url($canonical ?: $url, PHP_URL_HOST) ?? '');
        $linksContent = self::linksProfileContent($xp, $baseHost);
        $linksPage    = self::linksProfilePage($xp, $baseHost);

        // --- indexare/canonical/url
        $indexable = (stripos($robots, 'noindex') === false);
        $canonicalPresent = (bool)$canonical;
        $canonicalValid = false;
        if ($canonical) {
            $ch = parse_url($canonical);
            $uh = parse_url($url);
            $canonicalValid = !empty($ch['scheme']) && !empty($ch['host']);
            if ($canonicalValid && !empty($uh['host'])) {
                $canonicalValid = (self::normalizeHost((string)$ch['host']) === self::normalizeHost((string)$uh['host']));
            }
        }
        $urlClean = self::urlCleanliness($canonical ?: $url);

        // meta robots „ok” (fără noindex/none)
        $metaRobotsOk = ($robots === '' ||
            (strpos($robotsLower, 'noindex') === false && strpos($robotsLower, 'none') === false)
        );

        // --- metadate
        $titleLenOk = (mb_strlen($title) >= 35 && mb_strlen($title) <= 65);
        $descLenOk  = (mb_strlen($metaDesc) >= 120 && mb_strlen($metaDesc) <= 170);
        $ogMinimal  = (bool)($ogTitle && $ogDesc && $ogImage && ($ogUrl ?: $canonical ?: $url));

        $schemaRecommended = false;
        if ($articleSchemas) {
            foreach ($articleSchemas as $j) {
                $ok = !empty($j['headline']) && (!empty($j['author'])) && (!empty($j['datePublished'])) && (!empty($j['image']));
                $schemaRecommended = $schemaRecommended || $ok;
            }
        }

        // JSON-LD extra: FAQ, breadcrumbs, imagini cu width/height în schema
        $faqSchemaPresent = false;
        $breadcrumbsSchema = false;
        foreach ($jsonld as $j) {
            if (self::hasType($j, ['FAQPage'])) {
                $faqSchemaPresent = true;
            }
            if (self::hasType($j, ['BreadcrumbList'])) {
                $breadcrumbsSchema = true;
            }
        }
        $schemaImagesOk = self::schemaImagesHaveSize($articleSchemas);

        // --- Localizare clasică
        $isLangRo = (strncmp($lang, 'ro', 2) === 0);
        $ogOrInLangRo = ($ogLocale === 'ro_ro' || (is_string($inLanguage) && str_starts_with(strtolower($inLanguage), 'ro')));
        $dateFormatRo = self::hasRomanianMonth($dom);
        $hreflangOk   = self::hreflangPairs($xp);

        // --- SEO local extins (NU schimbă punctajul total direct)
        $jsonLocal = self::localBusinessSchemas($jsonld);
        $hasLocalBusiness = !empty($jsonLocal);

        $schemaPostal = false; $schemaTel = false; $schemaGeo = false; $schemaSameAs = false; $schemaArea = false; $schemaRating = false; $schemaHasMap = false;
        foreach ($jsonLocal as $s) {
            if (self::postalAddressOf($s)) $schemaPostal = true;
            if (!empty($s['telephone']))  $schemaTel = true;
            if (!empty($s['geo']['latitude']) && !empty($s['geo']['longitude'])) $schemaGeo = true;
            if (!empty($s['sameAs']))     $schemaSameAs = true;
            if (!empty($s['areaServed']) || !empty($s['serviceArea'])) $schemaArea = true;
            if (!empty($s['aggregateRating'])) $schemaRating = true;
            if (!empty($s['hasMap'])) $schemaHasMap = true;
        }

        $hasTelClick = ($xp->query('//a[starts-with(@href,"tel:")]')->length > 0);
        $telHref = '';
        if ($hasTelClick) $telHref = (string)$xp->query('//a[starts-with(@href,"tel:")]')->item(0)->getAttribute('href');
        $telDigits = preg_replace('~\D+~','', $telHref);
        $hasLocalPrefix = false;

        if ($telDigits !== '') {
            // Acceptă numere românești cu prefix 0 / 40 / 0040 și cod 2x, 3x sau 7x (mobil)
            if (preg_match('~^(?:0|40|0040)?(2\d|3\d|7\d)~', $telDigits)) {
                $hasLocalPrefix = true;
            }
        }

        $hasAddressVisible = $xp->query('//address|//*[@itemprop="address"]|//*[contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"address")]')->length > 0;
        $hasDirections = $xp->query('//a[contains(@href,"google.com/maps") or contains(@href,"g.page/") or contains(@href,"maps.app.goo.gl") or contains(@href,"waze.com/ul") or contains(@href,"apple.com/maps")]')->length > 0;
        $hasMapEmbed  = false;

        // Detect Google Maps embed even when the theme uses lazy-load (data-src, data-lazy-src etc.)
        $embedNodes = $xp->query('//iframe|//embed|//object');
        if ($embedNodes && $embedNodes->length > 0) {
            foreach ($embedNodes as $node) {
                /** @var \DOMElement $node */
                foreach (['src', 'data-src', 'data-lazy-src', 'data-original', 'data-url', 'srcdoc', 'data'] as $attr) {
                    if (!$node->hasAttribute($attr)) {
                        continue;
                    }
                    $val = trim((string) $node->getAttribute($attr));
                    if ($val === '') {
                        continue;
                    }

                    $v = strtolower($val);
                    $isGoogleMaps = (
                        strpos($v, 'google.com/maps') !== false ||
                        strpos($v, 'maps.google.') !== false ||
                        strpos($v, 'goo.gl/maps') !== false ||
                        strpos($v, 'maps.app.goo.gl') !== false
                    );
                    $isEmbed = (
                        strpos($v, 'output=embed') !== false ||
                        strpos($v, '/maps/embed') !== false ||
                        strpos($v, '/maps/d/embed') !== false
                    );

                    if ($isGoogleMaps && $isEmbed) {
                        $hasMapEmbed = true;
                        break 2;
                    }
                }

                if ($hasMapEmbed) {
                    break;
                }
            }
        }


        $hasOpeningSchema = false;
        foreach ($jsonLocal as $s) { if (!empty($s['openingHoursSpecification'])) { $hasOpeningSchema = true; break; } }
        $hasOpeningText = ($xp->query('//*[contains(translate(text(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"program")]')->length > 0);

        $whatsapp = $xp->query('//a[contains(@href,"wa.me/") or contains(@href,"api.whatsapp.com/send")]')->length > 0;

        // oraș detectat

        $localityMap = self::localityMap();

        $urlPath   = parse_url($url, PHP_URL_PATH) ?? '';
        $introRaw  = implode(' ', array_slice(preg_split('/\s+/u', $text), 0, 120));

        $uNorm     = self::roNorm($urlPath);
        $TNorm     = self::roNorm($title);
        $HNorm     = self::roNorm($h1);
        $introNorm = self::roNorm($introRaw);

        $cityDetected = null;   // forma „frumoasă” (ex. Iași)
        $cityNorm     = null;   // forma normalizată (iasi)

        // Detectăm orașul folosind potrivire pe tokeni (nu substring),
        // ca să evităm false-positive (ex. „var” în „vara”).
        $fieldsForCity = [
            'title' => $TNorm,
            'h1'    => $HNorm,
            'url'   => $uNorm,
            'intro' => $introNorm,
        ];

        $fieldWeight = [
            'title' => 40,
            'h1'    => 35,
            'url'   => 30,
            'intro' => 20,
        ];

        $bestNorm  = null;
        $bestScore = -INF;

        foreach ($fieldsForCity as $fieldKey => $fieldText) {
            $candidates = self::extractCityCandidates($fieldText, $localityMap, 4);
            foreach ($candidates as $cNorm) {
                $words = substr_count($cNorm, ' ') + 1;
                $score = ($fieldWeight[$fieldKey] ?? 0) + ($words * 3) + (min(strlen($cNorm), 30) / 30);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestNorm  = $cNorm;
                }
            }
        }

        if ($bestNorm && isset($localityMap[$bestNorm])) {
            $cityNorm     = $bestNorm;
            $cityDetected = $localityMap[$cityNorm];
        }

        $cityInTitle = $cityNorm ? self::containsTokenSequence($TNorm, $cityNorm) : false;
        $cityInH1    = $cityNorm ? self::containsTokenSequence($HNorm, $cityNorm) : false;
        $cityInSlug  = $cityNorm ? self::containsTokenSequence($uNorm, $cityNorm) : false;
        $cityInIntro = $cityNorm ? self::containsTokenSequence($introNorm, $cityNorm) : false;


        // ALT cu numele orașului (tot diacritice-safe, și ignoră imaginile decorative)
        $altHasCity = false;
        if ($cityNorm) {
            $nodesAlt = $xp->query('//img[@alt]');
            foreach ($nodesAlt as $img) {
                if (!($img instanceof \DOMElement)) {
                    continue;
                }
                if (self::isDecorative($img) || !self::isRealImage($img)) {
                    continue;
                }

                $altNorm = self::roNorm((string)$img->getAttribute('alt'));
                if (str_contains($altNorm, $cityNorm)) {
                    $altHasCity = true;
                    break;
                }
            }
        }

        // locator/multi-locații
        $hasLocator = $xp->query('//*[contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"locations") or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"store")]//a[@href]')->length >= 2;

        // --- scoruri binare
        $scores = [
            // Content
            'word_count_800' => ($wordCount >= 800),
            'intro_mentions_topic' => (bool)$introMentions,
            'h1_single' => (count($h1s) === 1 && $h1 !== ''),
            'headings_hierarchy' => $hh['ok'],
            'lists_tables' => $hasListOrTable,
            'images_in_body' => ($bodyImgCount >= 1),
            'img_alt_ratio_80' => ($altRatio >= 0.8),
            'lazyload_images' => ($lazyCount >= max(1, $imgsTotal > 2 ? 1 : 0)),
            'date_published' => (bool)$datePub,
            'date_modified' => (bool)$dateMod,
            'author_visible_or_schema' => $authorOk,

            // Structură & Indexare
            'indexable' => $indexable,
            'canonical_present' => $canonicalPresent,
            'canonical_valid' => $canonicalPresent && $canonicalValid,
            'url_clean' => $urlClean['ok'],
            'internal_links_present' => ($linksContent['internal'] >= 1 || $linksPage['internal'] >= 1),
            'external_links_present' => ($linksContent['external'] >= 1 || $linksPage['external'] >= 1),
            'meta_robots_ok' => $metaRobotsOk,
            'html_valid' => $htmlValid,
            'image_dimensions_defined' => $imageDimensionsDefined,
            'fonts_preload' => $fontsPreload,
            'cls_risky_elements' => $clsRiskOk,

            // Metadate
            'title_length_ok' => $titleLenOk,
            'meta_description_ok' => $descLenOk,
            'og_minimal' => $ogMinimal,
            'schema_article_recommended' => $schemaRecommended,
            'faq_schema_present' => $faqSchemaPresent,
            'schema_breadcrumbs' => $breadcrumbsSchema,
            'schema_image_required_fields' => $schemaImagesOk,

            // Localizare clasică (scor)
            'lang_ro' => $isLangRo,
            'og_locale_or_inLanguage_ro' => $ogOrInLangRo,
            'date_format_ro' => $dateFormatRo,
            'hreflang_pairs' => $hreflangOk,
        ];

        // --- acumulare scoruri + notes
        $checks = [];
        $content = $structure = $signals = $locale = 0.0;

        foreach (self::W as $id => $weight) {
            $ok = !empty($scores[$id]);
            $note = '';
            switch ($id) {
                case 'word_count_800': $note = $wordCount.' cuvinte'; break;
                case 'intro_mentions_topic': $note = $introMentions ? 'termen în primele 100 cuvinte' : ''; break;
                case 'h1_single': $note = count($h1s).' H1'; break;
                case 'headings_hierarchy': $note = $hh['note']; break;
                case 'lists_tables': $note = $hasListOrTable ? 'ok' : '-'; break;
                case 'images_in_body': $note = $bodyImgCount.' imagini în corp'; break;
                case 'img_alt_ratio_80': $note = sprintf('%d%%', (int)round($altRatio*100)); break;
                case 'lazyload_images': $note = $lazyCount.' imagini lazy'; break;
                case 'date_published': $note = $datePub ?: ''; break;
                case 'date_modified': $note = $dateMod ?: ''; break;
                case 'author_visible_or_schema': $note = $authorOk ? 'OK' : '-'; break;

                case 'indexable': $note = (stripos($robots,'noindex')!==false) ? 'noindex' : 'robots OK'; break;
                case 'canonical_present': $note = $canonical ?: '-'; break;
                case 'canonical_valid': $note = $canonicalValid ? 'self/host corect' : ($canonical?'alt domeniu?':'-'); break;
                case 'url_clean': $note = $urlClean['note']; break;
                case 'internal_links_present': $note = $linksContent['internal'].' interne în articol (total pagină '.$linksPage['internal'].')'; break;
                case 'external_links_present': $note = $linksContent['external'].' externe în articol (total pagină '.$linksPage['external'].')'; break;
                case 'meta_robots_ok': $note = $robots ?: '-'; break;
                case 'html_valid': $note = $htmlValid ? 'OK' : 'erori parser HTML'; break;
                case 'image_dimensions_defined': $note = $dimStats['total'] ? ($dimStats['with'].'/'.$dimStats['total'].' imagini cu dimensiuni') : 'fără imagini'; break;
                case 'fonts_preload': $note = $fontsPreload ? 'link preload font' : '-'; break;
                case 'cls_risky_elements': $note = $clsRiskOk ? 'dimensiuni imagini/fonturi critice OK' : 'posibil risc CLS'; break;

                case 'title_length_ok': $note = mb_strlen($title).' caractere'; break;
                case 'meta_description_ok': $note = mb_strlen($metaDesc).' caractere'; break;
                case 'og_minimal': $note = $ogMinimal ? 'og:title/desc/img/url' : 'incomplet'; break;
                case 'schema_article_recommended': $note = $schemaRecommended ? 'OK' : 'lipsește'; break;
                case 'faq_schema_present': $note = $faqSchemaPresent ? 'FAQPage' : '-'; break;
                case 'schema_breadcrumbs': $note = $breadcrumbsSchema ? 'BreadcrumbList' : '-'; break;
                case 'schema_image_required_fields': $note = $schemaImagesOk ? 'image width/height în schema' : '-'; break;

                case 'lang_ro': $note = $lang ?: '-'; break;
                case 'og_locale_or_inLanguage_ro': $note = $ogLocale ?: ($inLanguage ?: '-'); break;
                case 'date_format_ro': $note = $dateFormatRo ? 'ex. „12 noiembrie 2025”' : '-'; break;
                case 'hreflang_pairs': $note = $hreflangOk ? 'OK' : '-'; break;
            }

            $checks[] = ['id'=>$id,'ok'=>$ok,'note'=>$note];

            if (in_array($id,[
                'word_count_800','intro_mentions_topic','h1_single','headings_hierarchy',
                'lists_tables','images_in_body','img_alt_ratio_80','lazyload_images',
                'date_published','date_modified','author_visible_or_schema'
            ],true)) {
                $content += $ok ? $weight : 0;
            } elseif (in_array($id,[
                'indexable','canonical_present','canonical_valid','url_clean',
                'internal_links_present','external_links_present',
                'meta_robots_ok','html_valid','image_dimensions_defined',
                'fonts_preload','cls_risky_elements'
            ],true)) {
                $structure += $ok ? $weight : 0;
            } elseif (in_array($id,[
                'title_length_ok','meta_description_ok','og_minimal',
                'schema_article_recommended','faq_schema_present',
                'schema_breadcrumbs','schema_image_required_fields'
            ],true)) {
                $signals += $ok ? $weight : 0;
            } else {
                $locale += $ok ? $weight : 0;
            }
        }

        // --- Adăugăm verificările SEO local extinse (nu influențează direct scorul de bază; doar în context local)
        $checks = array_merge($checks, [
            ['id'=>'local_tel_click','ok'=>$hasTelClick,'note'=>$telHref ?: '-'],
            ['id'=>'local_tel_prefix_local','ok'=>$hasLocalPrefix,'note'=>$telDigits ?: '-'],
            ['id'=>'local_address_visible','ok'=>$hasAddressVisible,'note'=>''],
            ['id'=>'local_directions_link','ok'=>$hasDirections,'note'=>$hasDirections?'Link către hărți':'-'],
            ['id'=>'local_opening_hours','ok'=>($hasOpeningSchema||$hasOpeningText),'note'=>$hasOpeningSchema?'schema':'text/program'],
            ['id'=>'local_schema_localbusiness','ok'=>$hasLocalBusiness,'note'=>$hasLocalBusiness?'OK':'lipsește'],
            ['id'=>'local_schema_postal','ok'=>$schemaPostal,'note'=>$schemaPostal?'PostalAddress':'' ],
            ['id'=>'local_schema_tel','ok'=>$schemaTel,'note'=>$schemaTel?'telephone':'' ],
            ['id'=>'local_schema_geo','ok'=>$schemaGeo,'note'=>$schemaGeo?'lat/long':'' ],
            ['id'=>'local_schema_sameas','ok'=>($schemaSameAs||$schemaHasMap),'note'=>$schemaHasMap?'hasMap':'sameAs'],
            ['id'=>'local_schema_area','ok'=>$schemaArea,'note'=>$schemaArea?'areaServed/serviceArea':'' ],
            ['id'=>'local_schema_rating','ok'=>$schemaRating,'note'=>$schemaRating?'aggregateRating':'' ],
            ['id'=>'local_city_detected','ok'=> (bool)$cityDetected,'note'=>$cityDetected ?: '-'],
            ['id'=>'local_city_in_title','ok'=>$cityInTitle,'note'=>$cityDetected ?: '-'],
            ['id'=>'local_city_in_h1','ok'=>$cityInH1,'note'=>$cityDetected ?: '-'],
            ['id'=>'local_city_in_slug','ok'=>$cityInSlug,'note'=>$cityDetected ?: '-'],
            ['id'=>'local_city_in_intro','ok'=>$cityInIntro,'note'=>$cityDetected ?: '-'],
            ['id'=>'local_map_embed','ok'=>$hasMapEmbed,'note'=>$hasMapEmbed?'iframe Google Maps':'' ],
            ['id'=>'local_alt_has_city','ok'=>$altHasCity,'note'=>$altHasCity?'ALT cu oraș':'' ],
            ['id'=>'local_locator','ok'=>$hasLocator,'note'=>$hasLocator?'pagină/locator locații':'' ],
            ['id'=>'local_whatsapp','ok'=>$whatsapp,'note'=>$whatsapp?'link WhatsApp':'' ],
        ]);

        // atașăm severitatea (pass/warn/fail) pentru UI
        foreach ($checks as &$c) {
            $cid = (string)($c['id'] ?? '');
            $cok = (bool)($c['ok'] ?? false);
            $c['sev'] = self::severity($cid, $cok, $context);
        }
        unset($c);

        if (class_exists('Advice')) {
            $checks = Advice::enrichChecks($checks);
        }

        // --- calcul scoruri
        $content   = min($content, 40.0);
        $structure = min($structure, 25.0);
        $signals   = min($signals, 20.0);
        $locale    = min($locale, 15.0);
        $totalMain = (int)round($content + $structure + $signals + $locale);
        $totalMain = min($totalMain, 100);

        // scor SEO local (doar din local_*)
        $localPoints = 0.0;
        foreach (self::W_LOCAL as $id => $w) {
            foreach ($checks as $c) {
                if (($c['id'] ?? null) === $id) {
                    if (!empty($c['ok'])) {
                        $localPoints += $w;
                    }
                    break;
                }
            }
        }
        $localPoints = min($localPoints, self::LOCAL_MAX);
        $localPercent = self::LOCAL_MAX > 0
            ? (int)round($localPoints / self::LOCAL_MAX * 100)
            : 0;

        // combinăm scorul general cu localul doar dacă contextul e "local"
        $overall = $totalMain;
        if ($isLocalContext && self::LOCAL_MAX > 0) {
            $overall = (int)round(0.8 * $totalMain + 0.2 * $localPercent);
            if ($overall > 100) $overall = 100;
            if ($overall < 0)   $overall = 0;
        }

        return [
            'total'        => $overall,
            'total_global' => $totalMain,
            'total_local'  => (int)round($localPoints),
            'breakdown' => [
                'content' => (int)round($content),
                'structure' => (int)round($structure),
                'signals' => (int)round($signals),
                'locale' => (int)round($locale),
            ],
            'local' => [
                'points'  => (int)round($localPoints),
                'max'     => self::LOCAL_MAX,
                'percent' => $localPercent,
            ],
            'checks' => $checks,
            'meta' => [
                'title' => $title,
                'description' => $metaDesc,
                'h1' => $h1,
                'canonical' => $canonical,
                'datePublished' => $datePub ?: null,
                'dateModified' => $dateMod ?: null,
                'og' => ['title'=>$ogTitle,'description'=>$ogDesc,'image'=>$ogImage,'url'=>$ogUrl],
                'twitter' => ['card'=>null,'title'=>null,'description'=>null,'image'=>null], // lăsat neutru
                'links' => [
                    'content' => [
                        'internal' => array_slice($linksContent['list_internal'], 0, 50),
                        'external' => array_slice($linksContent['list_external'], 0, 50),
                    ],
                    'page' => [
                        'internal' => array_slice($linksPage['list_internal'], 0, 50),
                        'external' => array_slice($linksPage['list_external'], 0, 50),
                    ],
                ],
            ],
            'gatekeeper' => ['pass'=>true,'fails'=>[]],
            'context'    => $context,
        ];
    }

    private static function renderFailurePayload(string $url, array $options, string $error): array
    {
        $context = (string)($options['context'] ?? 'article');
        if ($context !== 'local') {
            $context = 'article';
        }

        return [
            'total' => 0,
            'total_global' => 0,
            'total_local' => 0,
            'breakdown' => [
                'content' => 0,
                'structure' => 0,
                'signals' => 0,
                'locale' => 0,
            ],
            'local' => [
                'points' => 0,
                'max' => self::LOCAL_MAX,
                'percent' => 0,
            ],
            'checks' => [],
            'meta' => [
                'title' => $url,
                'description' => '',
                'h1' => '',
                'canonical' => '',
                'datePublished' => null,
                'dateModified' => null,
                'og' => ['title' => null, 'description' => null, 'image' => null, 'url' => null],
                'twitter' => ['card' => null, 'title' => null, 'description' => null, 'image' => null],
                'links' => [
                    'content' => ['internal' => [], 'external' => []],
                    'page' => ['internal' => [], 'external' => []],
                ],
            ],
            'gatekeeper' => ['pass' => false, 'fails' => [$error]],
            'context' => $context,
            'error' => $error,
            'partial_data' => false,
        ];
    }

    /* ================= helpers generale ================= */
    private static function getVisibleText(\DOMDocument $dom): string {
        // NU mai ștergem <script> și <style>, ca să rămână JSON-LD în DOM.
        $xp = new \DOMXPath($dom);

        // Luăm toate nodurile text care NU sunt în <script> sau <style>
        $nodes = $xp->query('//text()[not(ancestor::script) and not(ancestor::style)]');
        if ($nodes === false) {
            return '';
        }

        $chunks = [];
        foreach ($nodes as $n) {
            $chunks[] = $n->nodeValue;
        }

        $text = implode(' ', $chunks);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    private static function wordCount(string $t): int {
        $t = trim($t); return $t === '' ? 0 : count(preg_split('/\s+/u',$t));
    }
    private static function first(\DOMXPath $xp, string $q): string {
        $n = $xp->query($q)->item(0); return $n ? trim($n->textContent) : '';
    }
    private static function all(\DOMXPath $xp, string $q): array {
        $o=[]; foreach ($xp->query($q) as $n) $o[] = trim($n->textContent); return $o;
    }
    private static function count(\DOMXPath $xp, string $q): int {
        return (int)$xp->evaluate('count('.$q.')');
    }
    private static function meta(\DOMXPath $xp, string $name): ?string {
        return self::attr($xp,'//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="'.$name.'"]','content');
    }
    private static function metaProp(\DOMXPath $xp, string $prop): ?string {
        return self::attr($xp,'//meta[@property="'.$prop.'"]','content') ?? self::attr($xp,'//meta[@name="'.$prop.'"]','content');
    }
    private static function attr(\DOMXPath $xp, string $q, string $attr): ?string {
        $n = $xp->query($q)->item(0); return $n ? trim((string)$n->getAttribute($attr)) : null;
    }
    private static function jsonLd(\DOMXPath $xp): array {
        $list = [];
        $nodes = $xp->query(
            '//script[contains(translate(@type,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"ld+json")]'
        );

        foreach ($nodes as $s) {
            $txt = trim($s->textContent ?? '');
            if ($txt === '') continue;

            $j = json_decode($txt, true);
            if (!$j) continue;

            $objs = (is_array($j) && array_is_list($j)) ? $j : [$j];

            foreach ($objs as $obj) {
                if (!is_array($obj)) continue;

                if (isset($obj['@graph']) && is_array($obj['@graph'])) {
                    foreach ($obj['@graph'] as $g) {
                        if (is_array($g)) {
                            $list[] = $g;
                        }
                    }
                } else {
                    $list[] = $obj;
                }
            }
        }

        return $list;
    }
    private static function hasType(array $j, array $types): bool {
        $t = $j['@type'] ?? null; $arr = is_array($t) ? $t : [$t];
        $low = array_map(fn($x)=>strtolower((string)$x), $arr);
        foreach ($types as $x) if (in_array(strtolower($x),$low,true)) return true;
        return false;
    }
    private static function findInLanguage(array $schemas): ?string {
        foreach ($schemas as $j) if (!empty($j['inLanguage']))
            return is_array($j['inLanguage']) ? ($j['inLanguage'][0] ?? null) : $j['inLanguage'];
        return null;
    }
    private static function articleDate(array $schemas): ?string {
        foreach ($schemas as $j) if (!empty($j['datePublished'])) return (string)$j['datePublished'];
        return null;
    }
    private static function articleModified(array $schemas): ?string {
        foreach ($schemas as $j) if (!empty($j['dateModified'])) return (string)$j['dateModified'];
        return null;
    }

    // Extrage data vizibilă din markup (preferă <time datetime>, apoi textul <time>)
    private static function visibleDate(\DOMXPath $xp): ?string
    {
        $q1 = '(//article//time[@datetime] | //main//time[@datetime] | //time[@datetime])[1]';
        $n1 = $xp->query($q1)->item(0);
        if ($n1) {
            $dt = trim((string)$n1->getAttribute('datetime'));
            if ($dt !== '') return $dt;
        }

        $q2 = '(//article//time | //main//time | //time)[1]';
        $n2 = $xp->query($q2)->item(0);
        if ($n2) {
            $txt = trim($n2->textContent ?? '');
            if ($txt !== '') return $txt;
        }

        return null;
    }

    /* ============== LOCAL helpers ============== */
    /** orice obiect JSON-LD care are @type LocalBusiness (sau rude) */
    private static function localBusinessSchemas(array $jsonld): array
    {
        $out = [];

        $localTypes = [
            'localbusiness',
            'store',
            'medicalbusiness',
            'automotivebusiness',
            'restaurant',
            'hotel',
            'healthandbeautybusiness',
            'professionalservice',
            'dentist',
            'autorepair',
        ];

        foreach ($jsonld as $j) {
            if (!is_array($j)) {
                continue;
            }

            // @type poate fi string sau array
            if (isset($j['@type'])) {
                $types = (array)$j['@type'];
                $types = array_map(
                    fn($t) => strtolower(trim((string)$t)),
                    $types
                );

                if (count(array_intersect($types, $localTypes)) > 0) {
                    $out[] = $j;
                    continue;
                }
            }

            // fallback: uneori LocalBusiness e "îngropat" într-un @graph intern
            if (isset($j['@graph']) && is_array($j['@graph'])) {
                foreach ($j['@graph'] as $g) {
                    if (!is_array($g) || empty($g['@type'])) {
                        continue;
                    }
                    $types = (array)$g['@type'];
                    $types = array_map(
                        fn($t) => strtolower(trim((string)$t)),
                        $types
                    );
                    if (count(array_intersect($types, $localTypes)) > 0) {
                        $out[] = $g;
                    }
                }
            }
        }

        return $out;
    }

    private static function postalAddressOf(array $schema): ?array {
        $addr = $schema['address'] ?? null;
        if (is_array($addr) && (isset($addr['streetAddress']) || isset($addr['addressLocality']))) return $addr;
        return null;
    }

    /* ============== altele ============== */
    private static function termInFirstNWords(string $t, ?string $term, int $n): bool {
        if (!$term) return false;
        $w = preg_split('/\s+/u', trim($t));
        $first = array_slice($w, 0, $n);
        $chunk = mb_strtolower(implode(' ', $first), 'UTF-8');
        $term = mb_strtolower($term, 'UTF-8');
        return (mb_strpos($chunk, $term, 0, 'UTF-8') !== false);
    }
    private static function headingsHierarchy(\DOMXPath $xp): array {
        $nodes = $xp->query('//h1|//h2|//h3|//h4');
        $prev = 0; $ok = true; $h2h3 = 0;
        foreach ($nodes as $n) {
            $lvl = (int)substr(strtolower($n->nodeName),1);
            if ($lvl===2 || $lvl===3) $h2h3++;
            if ($prev && ($lvl - $prev) > 1) $ok = false;
            $prev = $lvl;
        }
        $ok = $ok && ($h2h3 >= 3);
        return ['ok'=>$ok,'note'=>$ok?'ordine ok':'sărituri/puține subheadings'];
    }
    private static function hasAny(\DOMXPath $xp, array $queries): bool {
        foreach ($queries as $q) if ($xp->query($q)->length > 0) return true;
        return false;
    }

    /* ===== Link helpers ===== */
    private static function normalizeHost(string $h): string {
        $h = strtolower(trim($h)); if ($h==='') return '';
        if (str_starts_with($h,'www.')) $h = substr($h,4); return $h;
    }
    private static function sameSite(string $hrefHost, string $baseHost): bool {
        if ($baseHost==='') return false;
        $a = self::normalizeHost($hrefHost); $b = self::normalizeHost($baseHost);
        if ($a===$b) return true;
        return (bool)(strlen($a) > strlen($b) && str_ends_with($a,'.'.$b));
    }
    private static function contentContainers(): array {
        return [
            '//article','//main','//*[@itemprop="articleBody"]',
            '//*[contains(@class,"entry-content")]','//*[contains(@class,"post-content")]',
            '//*[contains(@class,"single-content")]','//*[contains(@class,"article-content")]',
            '//*[contains(@class,"content-inner")]','//*[contains(@class,"post-entry")]',
            '//*[contains(@class,"the-content")]','//*[contains(@class,"wp-block-post-content")]',
            '//*[contains(@class,"wp-block")]','//*[contains(@class,"elementor")]',
            '//*[@id="content"]','//*[@id="primary"]','//*[@id="main"]',
        ];
    }
    private static function isExcludedAncestor(\DOMElement $el): bool {
        for ($n=$el; $n && $n instanceof \DOMElement; $n=$n->parentNode) {
            $tag = strtolower($n->tagName ?? '');
            if (in_array($tag,['header','nav','footer','aside'])) return true;
            $cls = strtolower($n->getAttribute('class') ?? '');
            if ($cls && preg_match('~(menu|sidebar|widget|breadcrumbs|breadcrumb|related|comments|comment|share|social|pagination|site-footer|site-header|toc|ez-toc)~',$cls)) return true;
            $id = strtolower($n->getAttribute('id') ?? '');
            if ($id && preg_match('~(menu|sidebar|widget|breadcrumbs|breadcrumb|related|comments|comment|share|social|pagination|toc)~',$id)) return true;
        }
        return false;
    }
    private static function filterAnchorHref(string $href): ?string {
        $href = trim($href);
        if ($href==='' || $href[0]==='#') return null;
        $low = strtolower($href);
        if (str_starts_with($low,'javascript:') || str_starts_with($low,'mailto:') || str_starts_with($low,'tel:')) return null;
        return $href;
    }
    private static function linksProfileContent(\DOMXPath $xp, string $baseHost): array {
        $internal=0; $external=0; $listInt=[]; $listExt=[]; $seen=[];
        $nodes=[];
        foreach (self::contentContainers() as $q) {
            foreach ($xp->query($q.'//a[@href]') as $a) {
                if (!($a instanceof \DOMElement)) continue;
                if (self::isExcludedAncestor($a)) continue;
                $nodes[]=$a;
            }
        }
        if (!$nodes) {
            foreach ($xp->query('//p//a[@href] | //li//a[@href] | //h2//a[@href] | //h3//a[@href]') as $a) {
                if (!($a instanceof \DOMElement)) continue;
                if (self::isExcludedAncestor($a)) continue;
                $nodes[]=$a;
            }
        }
        foreach ($nodes as $a) {
            $href = self::filterAnchorHref($a->getAttribute('href')); if (!$href) continue;
            if (isset($seen[$href])) continue; $seen[$href]=true;
            $host = parse_url($href, PHP_URL_HOST);
            if (!$host) { $internal++; $listInt[]=['href'=>$href,'text'=>trim($a->textContent)]; }
            else {
                if (self::sameSite($host,$baseHost)) { $internal++; $listInt[]=['href'=>$href,'text'=>trim($a->textContent)]; }
                else { $external++; $listExt[]=['href'=>$href,'text'=>trim($a->textContent)]; }
            }
        }
        return ['internal'=>$internal,'external'=>$external,'list_internal'=>$listInt,'list_external'=>$listExt];
    }
    private static function linksProfilePage(\DOMXPath $xp, string $baseHost): array {
        $internal=0; $external=0; $listInt=[]; $listExt=[]; $seen=[];
        foreach ($xp->query('//a[@href]') as $a) {
            if (!($a instanceof \DOMElement)) continue;
            $href = self::filterAnchorHref($a->getAttribute('href')); if (!$href) continue;
            if (isset($seen[$href])) continue; $seen[$href]=true;
            $host = parse_url($href, PHP_URL_HOST);
            if (!$host) { $internal++; $listInt[]=['href'=>$href,'text'=>trim($a->textContent)]; }
            else {
                if (self::sameSite($host,$baseHost)) { $internal++; $listInt[]=['href'=>$href,'text'=>trim($a->textContent)]; }
                else { $external++; $listExt[]=['href'=>$href,'text'=>trim($a->textContent)]; }
            }
        }
        return ['internal'=>$internal,'external'=>$external,'list_internal'=>$listInt,'list_external'=>$listExt];
    }

    /* ===== imagini ===== */
    private static function isDecorative(\DOMElement $img): bool {
        $cls = strtolower($img->getAttribute('class') ?? '');
        $src = strtolower($img->getAttribute('src') ?? '');
        if (strpos($cls,'logo')!==false || strpos($cls,'avatar')!==false || strpos($cls,'icon')!==false) return true;
        if ($src && (strpos($src,'/logo')!==false || strpos($src,'avatar')!==false || strpos($src,'/icons')!==false)) return true;
        for ($n=$img; $n && $n instanceof \DOMElement; $n=$n->parentNode) {
            $tag = strtolower($n->tagName ?? ''); if (in_array($tag,['header','nav','footer'])) return true;
        }
        return false;
    }
    private static function isRealImage(\DOMElement $img): bool {
        if ($img->hasAttribute('src') && trim((string)$img->getAttribute('src'))!=='') return true;
        if ($img->hasAttribute('srcset') && trim((string)$img->getAttribute('srcset'))!=='') return true;
        foreach (['data-src','data-lazy-src','data-srcset','data-original','data-lazy-original'] as $a) {
            if ($img->hasAttribute($a) && trim((string)$img->getAttribute($a))!=='') return true;
        }
        $cls = strtolower($img->getAttribute('class') ?? '');
        if (strpos($cls,'lazy')!==false) return true;
        return false;
    }
    private static function countContentImages(\DOMXPath $xp): int {
        $seen=[]; $count=0;
        foreach (self::contentContainers() as $qCont) {
            $nodes = $xp->query($qCont.'//img | '.$qCont.'//picture//img | '.$qCont.'//figure//img');
            foreach ($nodes as $img) {
                if (!($img instanceof \DOMElement)) continue;
                $id = spl_object_hash($img); if (isset($seen[$id])) continue;
                if (self::isDecorative($img)) continue;
                if (!self::isRealImage($img)) continue;
                $seen[$id]=true; $count++;
            }
        }
        if ($count===0) {
            $nodes = $xp->query('//img | //picture//img | //figure//img');
            foreach ($nodes as $img) {
                if (!($img instanceof \DOMElement)) continue;
                if (self::isDecorative($img)) continue;
                if (!self::isRealImage($img)) continue;
                $count++;
            }
        }
        return $count;
    }
    private static function countAllRelevantImages(\DOMXPath $xp): int {
        $nodes=$xp->query('//img'); $c=0;
        foreach ($nodes as $img) {
            if (!($img instanceof \DOMElement)) continue;
            if (self::isDecorative($img)) continue;
            if (!self::isRealImage($img)) continue;
            $c++;
        }
        return $c;
    }
    private static function countImagesWithAlt(\DOMXPath $xp): int {
        $nodes=$xp->query('//img[@alt and normalize-space(@alt)!=""]'); $c=0;
        foreach ($nodes as $img) {
            if (!($img instanceof \DOMElement)) continue;
            if (self::isDecorative($img)) continue;
            if (!self::isRealImage($img)) continue;
            $c++;
        }
        return $c;
    }
    private static function countLazyImages(\DOMXPath $xp): int {
        $nodes=$xp->query('//img[@loading="lazy" or @data-lazy-src or @data-src or contains(translate(@class,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"lazy")]'); $c=0;
        foreach ($nodes as $img) {
            if (!($img instanceof \DOMElement)) continue;
            if (self::isDecorative($img)) continue;
            if (!self::isRealImage($img)) continue;
            $c++;
        }
        return $c;
    }

    /**
     * Statistici imagini cu dimensiuni definite (width+height)
     * @return array{total:int,with:int}
     */
    private static function imageDimensionsCoverage(\DOMXPath $xp): array {
        $nodes = $xp->query('//img');
        $total = 0;
        $with  = 0;
        foreach ($nodes as $img) {
            if (!($img instanceof \DOMElement)) continue;
            if (self::isDecorative($img) || !self::isRealImage($img)) continue;
            $total++;
            $w = trim((string)$img->getAttribute('width'));
            $h = trim((string)$img->getAttribute('height'));
            if ($w !== '' && $h !== '') {
                $with++;
            }
        }
        return ['total'=>$total,'with'=>$with];
    }

    /* ===== locale/url ===== */
    private static function hreflangPairs(\DOMXPath $xp): bool {
        $nodes=$xp->query('//link[@rel="alternate" and @hreflang and @href]');
        return ($nodes && $nodes->length >= 1);
    }
    private static function hasRomanianMonth(\DOMDocument $dom): bool {
        $t = mb_strtolower($dom->textContent ?? '', 'UTF-8');
        $months = ['ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'];
        foreach ($months as $m) if (mb_strpos($t,$m,0,'UTF-8')!==false) return true;
        return false;
    }
    private static function urlCleanliness(string $u): array {
        $note=''; $ok=true; $parts=parse_url($u);
        if (!$parts) {
            $note = 'URL invalid';
            return ['ok'=>false,'note'=>$note];
        }

        // homepage / root - îl considerăm OK (nu penalizăm lipsa slug-ului)
        if (!isset($parts['path']) || $parts['path'] === '' || $parts['path'] === '/') {
            return ['ok'=>true,'note'=>'/'];
        }

        $slug = trim($parts['path'],'/');
        if (mb_strlen($slug) > 75) { $ok=false; $note='slug lung'; }
        if (preg_match('~%20|[\s]~',$u)) { $ok=false; $note=trim($note.' spații'); }
        if (stripos($u,'replytocom=')!==false) { $ok=false; $note=trim($note.' parametru replytocom'); }
        if ($ok) $note='/'.$slug.'/';
        return ['ok'=>$ok,'note'=>$note];
    }

    /**
     * Detectează link rel="preload" as="font"
     */
    private static function hasPreloadFonts(\DOMXPath $xp): bool {
        $nodes = $xp->query('//link[@rel="preload" and translate(@as,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="font"]');
        return $nodes !== false && $nodes->length > 0;
    }

    /**
     * Verifică dacă imaginile din schema Article/BlogPosting au width/height.
     */
    private static function schemaImagesHaveSize(array $articleSchemas): bool {
        foreach ($articleSchemas as $j) {
            if (empty($j['image'])) {
                continue;
            }
            $imgField = $j['image'];
            $imgs = [];
            if (is_string($imgField)) {
                // nu avem width/height aici
                continue;
            } elseif (is_array($imgField)) {
                if (array_is_list($imgField)) {
                    $imgs = $imgField;
                } else {
                    $imgs = [$imgField];
                }
            }
            foreach ($imgs as $img) {
                if (!is_array($img)) continue;
                $w = $img['width']  ?? null;
                $h = $img['height'] ?? null;
                if ($w && $h) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function mainTerm(string $s): ?string {
        $s = mb_strtolower($s,'UTF-8');
        $s = preg_replace('/[^a-z0-9ăâîșşșțţ ]/iu',' ',$s);
        $tokens = preg_split('/\s+/u',$s);
        $stop = ['și','în','este','cu','pentru','care','sau','la','un','o','din','pe','mai','de','că','ce','nu','cum','între','ghid','complet','articol','2025','2024'];
        $freq=[];
        foreach ($tokens as $t) {
            if (!$t || mb_strlen($t)<3 || in_array($t,$stop,true)) continue;
            $freq[$t] = ($freq[$t] ?? 0) + 1;
        }
        arsort($freq);
        return array_key_first($freq) ?: null;
    }
}
