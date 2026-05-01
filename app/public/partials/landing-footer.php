<?php
declare(strict_types=1);

$footerSeoUrl = $seoServiceUrl ?? (string)(Config::serviceCatalog()['seo_optimization']['url'] ?? 'https://novaweb.ro/optimizare-seo/');
$footerCookiesUrl = $cookiesUrl ?? 'https://novaweb.ro/politica-cookie/';
$footerTermsUrl = 'https://novaweb.ro/termeni-si-conditii/';
$footerPrivacyUrl = 'https://novaweb.ro/politica-de-confidentialitate/';
?>
<footer class="site-footer">
  <div class="container footer">
    <a class="footer-brand" href="<?= htmlspecialchars(Config::basePath() . '/', ENT_QUOTES, 'UTF-8') ?>" aria-label="Novaweb Audit SEO gratuit">
      <img
        class="footer-brand-logo"
        src="<?= htmlspecialchars(Config::assetUrl('logo_white.png'), ENT_QUOTES, 'UTF-8') ?>"
        alt=""
        aria-hidden="true"
        width="156"
        height="64"
      >
    </a>

    <nav class="footer-nav" aria-label="Linkuri footer">
      <a class="footer-nav-item" href="<?= htmlspecialchars($footerSeoUrl, ENT_QUOTES, 'UTF-8') ?>">Servicii SEO</a>
      <a class="footer-nav-item" href="#reportContactSection">Contact</a>
      <a class="footer-nav-item" href="<?= htmlspecialchars($footerTermsUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Termeni</a>
      <a class="footer-nav-item" href="<?= htmlspecialchars($footerPrivacyUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Confidențialitate</a>
      <a class="footer-nav-item" href="<?= htmlspecialchars($footerCookiesUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Politica cookies</a>
    </nav>

    <div class="footer-copy">&copy; <?= date('Y') ?> NovaWeb. Toate drepturile rezervate.</div>
  </div>
</footer>
