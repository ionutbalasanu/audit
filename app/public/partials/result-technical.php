<?php
declare(strict_types=1);
?>
<section class="report-section" id="technical-stage" hidden>
  <details class="technical-accordion">
    <summary>Vezi raportul tehnic complet (pentru dezvoltatori)</summary>
    <div id="technicalSection" class="technical-shell"></div>
  </details>

  <div class="email-panel" id="emailPanel">
    <div class="email-panel-copy">
      <p class="section-eyebrow">Raport pe email</p>
      <h3>Pastreaza rezultatul si trimite-l mai departe in echipa</h3>
      <p>Raportul imediat vine pe email. Daca bifezi optiunea de sfaturi, primesti si recomandari practice de remediere SEO.</p>
    </div>

    <div class="email-panel-form">
      <input id="localEmail" type="email" placeholder="email@exemplu.ro">
      <input id="localFirstName" type="text" placeholder="Prenume (optional)">
      <label class="checkbox-row">
        <input id="wantsPlan" type="checkbox">
        <span>Vreau sfaturi de remediere SEO pe email</span>
      </label>
      <label class="checkbox-row">
        <input id="consentNewsletter" type="checkbox">
        <span>Vreau si sfaturi SEO ocazionale prin email</span>
      </label>
      <label class="checkbox-row">
        <input id="consentTerms" type="checkbox">
        <span>Am citit si accept <a href="https://novaweb.ro/termeni-si-conditii/" target="_blank" rel="noopener">Termenii</a> si <a href="https://novaweb.ro/politica-de-confidentialitate/" target="_blank" rel="noopener">Politica de confidentialitate</a>.</span>
      </label>
      <button id="localSend" type="button" class="btn-main">Trimite raportul pe email</button>
      <div id="localMsg" class="field-info" aria-live="polite"></div>
    </div>
  </div>
</section>
