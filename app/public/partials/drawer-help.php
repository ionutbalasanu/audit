<?php
declare(strict_types=1);
$consultant = Config::consultant();
?>
<div id="helpDrawer" class="drawer-shell" aria-hidden="true">
  <div class="drawer-backdrop" data-close-drawer="helpDrawer"></div>
  <div class="drawer-panel" role="dialog" aria-modal="true" aria-labelledby="helpDrawerTitle">
    <div class="drawer-head">
      <div>
        <p class="section-kicker">Cerere contextuala</p>
        <h2 id="helpDrawerTitle" class="panel-title">Vrei sa implementam noi recomandarile?</h2>
      </div>
      <button type="button" class="icon-button" data-close-drawer="helpDrawer" aria-label="Închide">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <div class="context-callout strong">
      <strong><?= htmlspecialchars($consultant['name'], ENT_QUOTES, 'UTF-8') ?></strong>
      <p><?= htmlspecialchars($consultant['summary'], ENT_QUOTES, 'UTF-8') ?></p>
      <span id="helpContextNote">Vom prelua contextul raportului in mesaj.</span>
    </div>

    <form id="leadRequestForm" class="stack-form" novalidate>
      <label class="field-stack" for="leadName">
        <span>Nume</span>
        <input id="leadName" type="text" placeholder="Nume complet" autocomplete="name" required>
      </label>

      <label class="field-stack" for="leadPhone">
        <span>Telefon</span>
        <input id="leadPhone" type="tel" placeholder="Telefon" autocomplete="tel" required>
      </label>

      <label class="field-stack" for="leadEmail">
        <span>Email</span>
        <input id="leadEmail" type="email" placeholder="email@exemplu.ro" autocomplete="email" required>
      </label>

      <label class="field-stack" for="leadMessage">
        <span>Ce vrei sa rezolvam</span>
        <textarea id="leadMessage" rows="5" placeholder="Contextul raportului se va completa automat aici." required></textarea>
      </label>

      <label class="checkbox-inline">
        <input id="leadUrgent" type="checkbox">
        <span>Sunati-ma in 24h</span>
      </label>

      <label class="checkbox-inline">
        <input id="helpNewsletterOptin" type="checkbox">
        <span>Pot primi si update-uri SEO ocazionale. Cererea de contact poate fi folosita separat pentru raspuns punctual despre raport.</span>
      </label>

      <label class="checkbox-inline">
        <input id="leadTerms" type="checkbox" required>
        <span>Am citit și accept <a href="https://novaweb.ro/termeni-si-conditii/" target="_blank" rel="noopener">Termenii și condițiile</a> și <a href="https://novaweb.ro/politica-de-confidentialitate/" target="_blank" rel="noopener">Politica de confidențialitate</a>.</span>
      </label>
      <p class="form-privacy-note">
        Formular protejat anti-spam prin Google reCAPTCHA. Detalii in Politica de confidentialitate Novaweb si politicile Google.
      </p>

      <input id="leadWebsiteTrap" type="text" class="honeypot" tabindex="-1" autocomplete="off" placeholder="Leave empty">

      <div class="drawer-actions">
        <button type="button" class="button button-ghost" data-close-drawer="helpDrawer">Inchide</button>
        <button id="leadSend" type="submit" class="button button-primary">Trimite cererea</button>
      </div>

      <div id="leadMsg" class="inline-feedback" aria-live="polite"></div>
    </form>
  </div>
</div>
