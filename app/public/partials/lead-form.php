<?php
declare(strict_types=1);
?>
<section class="report-section" id="lead-stage" hidden>
  <div class="lead-shell">
    <div class="lead-copy">
      <p class="section-eyebrow">Cerere de implementare</p>
      <h2 class="section-title">Daca vrei sa implementam noi recomandarile, lasa-ne datele direct din contextul raportului.</h2>
      <p class="section-support">Formularul ramane inline, ca sa nu pierzi contextul rezultatului si al problemelor identificate.</p>
    </div>

    <form id="leadRequestForm" class="lead-form" novalidate>
      <div class="lead-grid">
        <label class="field-stack" for="leadName">
          <span>Nume</span>
          <input id="leadName" type="text" placeholder="Nume" autocomplete="name" required>
        </label>
        <label class="field-stack" for="leadPhone">
          <span>Telefon</span>
          <input id="leadPhone" type="tel" placeholder="Telefon" autocomplete="tel" required>
        </label>
        <label class="field-stack" for="leadEmail">
          <span>Email</span>
          <input id="leadEmail" type="email" placeholder="Email" autocomplete="email" required>
        </label>
        <label class="checkbox-row compact">
          <input id="leadUrgent" type="checkbox">
          <span>Sunati-ma in 24h</span>
        </label>
      </div>
      <label class="field-stack" for="leadMessage">
        <span>Mesaj</span>
        <textarea id="leadMessage" rows="4" placeholder="Mesaj" required></textarea>
      </label>
      <input id="leadWebsiteTrap" type="text" class="honeypot" tabindex="-1" autocomplete="off" placeholder="Leave empty">
      <button id="leadSend" type="button" class="btn-main">Trimite cererea</button>
      <div id="leadMsg" class="field-info" aria-live="polite"></div>
    </form>
  </div>
</section>
