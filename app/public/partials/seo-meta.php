<?php
declare(strict_types=1);

$isWorkspaceMode = ($pageMode ?? 'landing') === 'workspace';
$pageTitle = $isWorkspaceMode
    ? 'Raport privat | NovaWeb Audit SEO'
    : Config::toolTitle();
$pageDescription = $isWorkspaceMode
    ? 'Raportul tau privat de audit este afisat in NovaWeb Audit SEO.'
    : Config::toolDescription();
$robotsContent = $isWorkspaceMode
    ? 'noindex, nofollow'
    : 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';

if (!$isWorkspaceMode) {
    $faqGraph = array_map(static function (array $item): array {
        return [
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['answer'],
            ],
        ];
    }, Config::faqItems());

    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => Config::origin() . '/#organization',
                'name' => 'NovaWeb',
                'url' => Config::origin() . '/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => Config::canonicalUrl('logo-audit.svg'),
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => Config::origin() . '/#website',
                'url' => Config::origin() . '/',
                'name' => 'NovaWeb',
                'publisher' => ['@id' => Config::origin() . '/#organization'],
                'inLanguage' => 'ro-RO',
            ],
            [
                '@type' => 'WebPage',
                '@id' => Config::canonicalUrl() . '#webpage',
                'url' => Config::canonicalUrl(),
                'name' => Config::toolTitle(),
                'description' => Config::toolDescription(),
                'isPartOf' => ['@id' => Config::origin() . '/#website'],
                'about' => ['@id' => Config::origin() . '/#organization'],
                'inLanguage' => 'ro-RO',
            ],
            [
                '@type' => 'SoftwareApplication',
                '@id' => Config::canonicalUrl() . '#app',
                'name' => 'NovaWeb Audit SEO',
                'url' => Config::canonicalUrl(),
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'All',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'RON',
                ],
            ],
            [
                '@type' => 'FAQPage',
                '@id' => Config::canonicalUrl() . '#faq',
                'mainEntity' => $faqGraph,
            ],
            [
                '@type' => 'HowTo',
                '@id' => Config::canonicalUrl() . '#howto',
                'name' => 'Cum faci un audit SEO gratuit pentru site-ul tău',
                'description' => 'Pași simpli pentru a primi un raport SEO complet în 60 de secunde, fără cont și fără card.',
                'totalTime' => 'PT1M',
                'step' => [
                    [
                        '@type' => 'HowToStep',
                        'position' => 1,
                        'name' => 'Introdu URL-ul site-ului',
                        'text' => 'Copiază adresa paginii pe care vrei să o analizezi (ex: novaweb.ro sau https://site-ul-tau.ro/pagina).',
                    ],
                    [
                        '@type' => 'HowToStep',
                        'position' => 2,
                        'name' => 'Alege tipul paginii',
                        'text' => 'Selectează „Pagină standard" pentru articole sau pagini generale, sau „Pagină locală" dacă țintește o zonă geografică (oraș, județ).',
                    ],
                    [
                        '@type' => 'HowToStep',
                        'position' => 3,
                        'name' => 'Primești scorul și raportul',
                        'text' => 'În 60 de secunde primești scorul SEO 0-100, lista problemelor prioritare și pașii de rezolvare. Pentru raport complet pe email, introdu adresa.',
                    ],
                ],
            ],
        ],
    ];
}
?>
<meta charset="utf-8">
<meta name="csp-nonce" content="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#f8fafc">
<meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta name="robots" content="<?= htmlspecialchars($robotsContent, ENT_QUOTES, 'UTF-8') ?>">
<link rel="canonical" href="<?= htmlspecialchars(Config::canonicalUrl(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="alternate" hreflang="ro" href="<?= htmlspecialchars(Config::canonicalUrl(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars(Config::canonicalUrl(), ENT_QUOTES, 'UTF-8') ?>">
<link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(Config::assetUrl('favicon.ico'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="shortcut icon" href="<?= htmlspecialchars(Config::assetUrl('favicon.ico'), ENT_QUOTES, 'UTF-8') ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/tokens.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/base.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/components.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/landing.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/report.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/states.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/cmp.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/design.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(Config::assetUrl('app/public/assets/report-design.css'), ENT_QUOTES, 'UTF-8') ?>">

<meta property="og:locale" content="ro_RO">
<meta property="og:type" content="website">
<meta property="og:site_name" content="NovaWeb">
<meta property="og:url" content="<?= htmlspecialchars(Config::canonicalUrl(), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?= htmlspecialchars(Config::toolTitle(), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars(Config::toolDescription(), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="<?= htmlspecialchars(Config::canonicalUrl('raport-preview-og.png'), ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Audit SEO gratuit - scor 0-100 si raport">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars(Config::toolTitle(), ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars(Config::toolDescription(), ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:image" content="<?= htmlspecialchars(Config::canonicalUrl('raport-preview-og.png'), ENT_QUOTES, 'UTF-8') ?>">

<?php if ($gaId !== ''): ?>
<script nonce="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>" type="text/plain" data-consent="statistics" async data-src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($gaId, ENT_QUOTES, 'UTF-8') ?>"></script>
<script nonce="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>" type="text/plain" data-consent="statistics">
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= htmlspecialchars($gaId, ENT_QUOTES, 'UTF-8') ?>');
</script>
<?php endif; ?>

<?php if ($fbId !== ''): ?>
<script nonce="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>" type="text/plain" data-consent="marketing">
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
  n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,
  'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init','<?= htmlspecialchars($fbId, ENT_QUOTES, 'UTF-8') ?>');
  fbq('track','PageView');
</script>
<?php endif; ?>

<?php if (!$isWorkspaceMode): ?>
<script nonce="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>" type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php endif; ?>
