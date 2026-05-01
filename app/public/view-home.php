<?php
declare(strict_types=1);

$cookiesUrl = 'https://novaweb.ro/politica-cookie/';
$gaId = (string)(function_exists('envget') ? envget('GA4_ID', '') : '');
$fbId = (string)(function_exists('envget') ? envget('FB_PIXEL_ID', '') : '');
$initialReportToken = trim((string)($_GET['report'] ?? ''));
$initialReportAccess = trim((string)($_GET['access'] ?? ''));
$initialReportUnlocked = $initialReportToken !== ''
    ? (
        ReportAccess::isUnlocked($initialReportToken)
        || ReportAccess::unlockWithAccessToken($initialReportToken, $initialReportAccess)
    )
    : false;
$pageMode = $initialReportToken !== '' ? 'workspace' : 'landing';
$serviceCatalog = Config::serviceCatalog();
$seoServiceUrl = (string)($serviceCatalog['seo_optimization']['url'] ?? 'https://novaweb.ro/optimizare-seo/');

$frontendConfig = [
    'basePath' => Config::basePath(),
    'toolUrl' => Config::canonicalUrl(),
    'scoreUrl' => Config::basePath() . '/api/score',
    'emailUrl' => Config::basePath() . '/api/email-report',
    'reportUrl' => Config::basePath() . '/api/report',
    'leadUrl' => Config::basePath() . '/api/lead-request',
    'pdfUrl' => Config::basePath() . '/api/report-pdf',
    'serviceCatalog' => $serviceCatalog,
    'consultant' => Config::consultant(),
    'initialMode' => $pageMode,
    'initialReportToken' => $initialReportToken !== '' ? $initialReportToken : null,
    'initialReportUnlocked' => $initialReportUnlocked,
    'initialSessionUnlocked' => ReportAccess::hasSessionUnlock(),
    'servicesUrl' => $seoServiceUrl,
    'recaptchaSiteKey' => Config::recaptchaSiteKey(),
    'cspNonce' => security_nonce(),
];
?>
<!doctype html>
<html lang="ro">
<head>
<?php require __DIR__ . '/partials/seo-meta.php'; ?>
</head>
<body class="app-body app-mode-<?= htmlspecialchars($pageMode, ENT_QUOTES, 'UTF-8') ?>">
  <div id="appRoot" class="app-root" data-mode="<?= htmlspecialchars($pageMode, ENT_QUOTES, 'UTF-8') ?>">
    <div id="landingSurface" class="landing-surface">
      <?php require __DIR__ . '/partials/landing-header.php'; ?>
      <main>
        <?php require __DIR__ . '/partials/landing-hero.php'; ?>

        <section id="workspaceShell" class="workspace-shell"<?= $pageMode === 'landing' ? ' hidden' : '' ?>>
          <div class="shell-container workspace-frame">
            <?php require __DIR__ . '/partials/report-header.php'; ?>

            <section id="workspaceState" class="workspace-state-panel" hidden aria-live="polite"></section>

            <div id="workspaceContent" class="workspace-content" hidden>
              <?php require __DIR__ . '/partials/report-overview.php'; ?>
              <?php require __DIR__ . '/partials/report-issues.php'; ?>
              <?php require __DIR__ . '/partials/report-plan.php'; ?>
              <?php require __DIR__ . '/partials/report-technical.php'; ?>
              <?php require __DIR__ . '/partials/report-share.php'; ?>
            </div>
          </div>
        </section>

        <div id="landingSecondary" class="landing-secondary">
          <div id="landingEducation" class="landing-education"<?= $pageMode === 'workspace' ? ' hidden' : '' ?>>
            <?php require __DIR__ . '/partials/landing-how.php'; ?>
            <?php require __DIR__ . '/partials/landing-interpret.php'; ?>
            <?php require __DIR__ . '/partials/landing-checklist.php'; ?>
            <?php require __DIR__ . '/partials/landing-local-recs.php'; ?>
          </div>
          <section id="contactShell" class="contact-shell">
            <div class="shell-container contact-frame">
              <?php require __DIR__ . '/partials/report-contact.php'; ?>
            </div>
          </section>
          <?php require __DIR__ . '/partials/landing-cta-final.php'; ?>
          <?php require __DIR__ . '/partials/landing-faq.php'; ?>
          <?php require __DIR__ . '/partials/landing-footer.php'; ?>
        </div>
      </main>
    </div>

    <?php require __DIR__ . '/partials/drawer-help.php'; ?>
    <?php require __DIR__ . '/partials/cta-blocks.php'; ?>
    <?php require __DIR__ . '/partials/cmp.php'; ?>

    <div id="globalToast" class="global-toast" hidden aria-live="polite"></div>

    <div id="mobileActionBar" class="mobile-action-bar" hidden>
      <button id="mobilePdfButton" type="button" class="button button-primary" hidden>PDF</button>
      <button id="mobileShareButton" type="button" class="button button-primary">Trimite raportul</button>
      <button id="mobileHelpButton" type="button" class="button button-secondary">Ajutor</button>
    </div>
  </div>

  <script nonce="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>" defer src="<?= htmlspecialchars(Config::assetUrl('app/public/assets/cmp.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
  <script nonce="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>">
    window.NOVA_AUDIT = <?= json_encode($frontendConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  </script>
  <script nonce="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>" type="module" src="<?= htmlspecialchars(Config::assetUrl('app/public/assets/audit-app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
