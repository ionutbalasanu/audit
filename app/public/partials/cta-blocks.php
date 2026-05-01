<?php
declare(strict_types=1);
?>
<aside id="stickyCtaDesktop" class="sticky-cta" hidden></aside>
<div id="stickyCtaMobile" class="sticky-cta-mobile" hidden></div>

<div id="exitIntentModal" class="modal-shell" aria-hidden="true">
  <div class="modal-backdrop" data-close="exitIntentModal"></div>
  <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="exitIntentTitle">
    <div class="modal-head">
      <div>
        <p class="modal-eyebrow">Inainte sa pleci</p>
        <h2 id="exitIntentTitle">Vrei sa-ti trimitem raportul si recomandarile pe email?</h2>
      </div>
      <button type="button" class="modal-close" data-close="exitIntentModal" aria-label="Inchide">&times;</button>
    </div>
    <p class="modal-copy">Pastrezi rezultatul si poti reveni direct in raport din linkul personalizat.</p>
    <div class="modal-actions">
      <button id="exitIntentEmail" type="button" class="btn-main">Merg la formularul de email</button>
      <button type="button" class="btn-secondary" data-close="exitIntentModal">Nu acum</button>
    </div>
  </div>
</div>

<div id="reportLimitModal" class="modal-shell" aria-hidden="true">
  <div class="modal-backdrop" data-close="reportLimitModal"></div>
  <div class="modal-panel limit-modal-panel" role="dialog" aria-modal="true" aria-labelledby="reportLimitTitle" aria-describedby="reportLimitCopy">
    <div class="modal-head">
      <div>
        <p class="modal-eyebrow">Limita atinsa</p>
        <h2 id="reportLimitTitle">Ai folosit cele 3 rapoarte complete pentru acest site.</h2>
      </div>
      <button type="button" class="modal-close" data-close="reportLimitModal" aria-label="Inchide">&times;</button>
    </div>
    <p id="reportLimitCopy" class="modal-copy">Ultimul raport ramane disponibil pe ecran. Limita se reseteaza dupa 24h; intre timp, trimite-ne cererea si il verificam manual.</p>
    <div id="reportLimitMeta" class="limit-modal-status" aria-live="polite"></div>
    <div class="modal-actions">
      <button id="reportLimitHelp" type="button" class="btn-main">Vreau verificare manuala</button>
      <button type="button" class="btn-secondary" data-close="reportLimitModal">Pastrez raportul</button>
    </div>
  </div>
</div>

<div id="planPromptModal" class="modal-shell" aria-hidden="true">
  <div class="modal-backdrop" data-close="planPromptModal"></div>
  <div class="modal-panel plan-prompt-panel" role="dialog" aria-modal="true" aria-labelledby="planPromptTitle" aria-describedby="planPromptCopy">
    <div class="modal-head">
      <div>
        <p class="modal-eyebrow">Optional</p>
        <h2 id="planPromptTitle">Vrei si sfaturi de remediere SEO?</h2>
      </div>
      <button type="button" class="modal-close" data-close="planPromptModal" aria-label="Trimite doar raportul">&times;</button>
    </div>
    <p id="planPromptCopy" class="modal-copy">Raportul complet se trimite oricum. Separat, iti putem trimite sfaturi practice despre cum sa rezolvi problemele detectate.</p>
    <div class="modal-actions">
      <button id="planPromptAccept" type="button" class="btn-main">Da, vreau sfaturile</button>
      <button id="planPromptDecline" type="button" class="btn-secondary">Nu, trimite doar raportul</button>
    </div>
  </div>
</div>
