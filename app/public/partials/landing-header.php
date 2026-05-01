<?php
declare(strict_types=1);
?>
<nav class="nav">
  <div class="container nav-inner">
    <a href="<?= htmlspecialchars(Config::basePath() . '/', ENT_QUOTES, 'UTF-8') ?>" class="brand" aria-label="Novaweb Audit SEO">
      <img
        class="brand-logo"
        src="<?= htmlspecialchars(Config::assetUrl('logo-audit.svg'), ENT_QUOTES, 'UTF-8') ?>"
        alt=""
        aria-hidden="true"
        width="68"
        height="48"
      >
    </a>

    <div class="nav-links">
      <a href="#audit">Audit</a>
      <a href="#workspaceShell">Raport</a>
      <a href="#cum-functioneaza">Cum funcționează</a>
      <a href="#interpretare">Interpretare scor</a>
      <a href="#ce-verifica">Ce verific&#259;</a>
      <a href="#faq">FAQ</a>
    </div>

    <div class="nav-cta">
      <a href="#reportContactSection" class="btn btn-dark">Servicii optimizare SEO</a>
    </div>

    <details class="nav-mobile">
      <summary class="nav-mobile-toggle" aria-label="Deschide meniul">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="4" y1="7" x2="20" y2="7"></line>
          <line x1="4" y1="12" x2="20" y2="12"></line>
          <line x1="4" y1="17" x2="20" y2="17"></line>
        </svg>
      </summary>

      <div class="nav-mobile-panel">
        <div class="nav-mobile-links">
          <a href="#audit">Audit</a>
          <a href="#workspaceShell">Raport</a>
          <a href="#cum-functioneaza">Cum funcționează</a>
          <a href="#interpretare">Interpretare scor</a>
          <a href="#ce-verifica">Ce verific&#259;</a>
          <a href="#faq">FAQ</a>
        </div>

        <a href="#reportContactSection" class="btn btn-dark nav-mobile-cta">Servicii optimizare SEO</a>
      </div>
    </details>
  </div>
</nav>
