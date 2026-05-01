<?php
declare(strict_types=1);
?>
<section id="reportContactSection" class="report-contact-section" aria-labelledby="reportContactTitle">
  <div class="report-contact-copy">
    <div class="gate-eyebrow">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      VORBEȘTE CU NOI
    </div>
    <h2 id="reportContactTitle">Ai întrebări pe raport sau vrei să ne implicăm?</h2>
    <p>Scrie-ne ce te interesează și îți răspundem concret. Dacă vrei să preluăm implementarea, îți facem și o estimare realistă - fără presiune să decizi imediat.</p>
  </div>

  <form id="reportContactForm" class="report-contact-form" novalidate>
    <div class="report-contact-head">
      <h3>Scrie-ne direct</h3>
      <p>Scrie-ne aici și îți răspundem pe email în maximum 24h.</p>
      <p id="reportContactContext" class="report-contact-context" hidden></p>
    </div>

    <label class="report-contact-field" for="reportContactName">
      <span>Nume</span>
      <input id="reportContactName" class="report-contact-input" type="text" placeholder="Nume complet" autocomplete="name" required>
    </label>

    <label class="report-contact-field" for="reportContactEmail">
      <span>Email</span>
      <input id="reportContactEmail" class="report-contact-input" type="email" placeholder="nume@domeniu.ro" autocomplete="email" required>
    </label>

    <label class="report-contact-field" for="reportContactMessage">
      <span>CE TE INTERESEAZĂ</span>
      <textarea id="reportContactMessage" class="report-contact-textarea" rows="5" placeholder="Scrie pe scurt ce ai vrea să clarifici sau să rezolvi. Poți atașa și linkul raportului dacă îl ai." required></textarea>
    </label>

    <label class="report-contact-check" for="reportContactTerms">
      <input id="reportContactTerms" type="checkbox" required>
      <span>Am citit și accept <a href="https://novaweb.ro/termeni-si-conditii/" target="_blank" rel="noopener">Termenii și condițiile</a> și <a href="https://novaweb.ro/politica-de-confidentialitate/" target="_blank" rel="noopener">Politica de confidențialitate</a>.</span>
    </label>
    <p class="form-privacy-note">
      Formular protejat anti-spam prin Google reCAPTCHA. Detalii in Politica de confidentialitate Novaweb si politicile Google.
    </p>

    <input id="reportContactWebsiteTrap" type="text" class="honeypot" tabindex="-1" autocomplete="off" placeholder="Leave empty">

    <button type="submit" class="btn btn-primary btn-block">Trimite</button>
    <div id="reportContactMsg" class="report-contact-feedback" aria-live="polite"></div>
  </form>
</section>
