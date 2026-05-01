<?php
declare(strict_types=1);
?>
<div id="cmpBanner" class="cmpBanner" hidden aria-hidden="true">
  <div class="cmpCard">
    <div class="cmpText">
      <div class="cmpTitle">Preferinte cookies</div>
      <div class="cmpDesc">
        Folosim cookie-uri necesare pentru functionarea aplicatiei si, optional, cookie-uri pentru statistici si marketing.
        Poti citi detaliile in
        <a class="cmpInlineLink" href="<?= htmlspecialchars($cookiesUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">politica de cookie-uri</a>.
      </div>
    </div>

    <div class="cmpActions">
      <button type="button" class="cmpBtn ghost" data-cmp-action="reject">Respinge</button>
      <button type="button" class="cmpBtn" data-cmp-action="settings">Setari</button>
      <button type="button" class="cmpBtn primary" data-cmp-action="accept">Accepta</button>
    </div>
  </div>
</div>

<button id="cmpFab" class="cmpFab" type="button" hidden aria-label="Setari cookies" title="Setari cookies">
  <svg class="cookie-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <circle cx="12" cy="12" r="8.5" fill="#E1B08A"/>
    <circle cx="9" cy="8.75" r="1.15" fill="#6E4A3B"/>
    <circle cx="14.9" cy="9.4" r="1.1" fill="#6E4A3B"/>
    <circle cx="10.2" cy="14.7" r="1.35" fill="#6E4A3B"/>
    <circle cx="15.3" cy="14.5" r="1.1" fill="#6E4A3B"/>
    <circle cx="7.2" cy="12.1" r="1" fill="#6E4A3B"/>
    <path d="M17.8 11.1a4 4 0 0 1-3.9-3.9 2.9 2.9 0 0 1-2.9-2.9 8.5 8.5 0 1 0 8.5 8.5 3.1 3.1 0 0 1-1.7-1.7Z" fill="#F3D2B4" opacity=".75"/>
  </svg>
</button>

<div id="cmpModal" class="cmpModal" aria-hidden="true">
  <div class="cmpModalCard" role="dialog" aria-modal="true" aria-labelledby="cmpModalTitle">
    <div class="cmpModalHead">
      <div>
        <div id="cmpModalTitle" class="cmpModalTitle">Setari cookies</div>
        <div class="cmpModalSub">Poti decide separat ce categorii optionale permiti.</div>
      </div>
      <button type="button" class="cmpX" data-cmp-close aria-label="Inchide">x</button>
    </div>

    <div class="cmpModalBody">
      <div class="cmpRow">
        <div>
          <div class="cmpRowTitle">Necesare</div>
          <div class="cmpRowDesc">Necesare pentru functionarea corecta a aplicatiei. Nu pot fi dezactivate.</div>
        </div>
        <label class="cmpSwitch" aria-label="Cookies necesare active">
          <input type="checkbox" checked disabled>
          <span></span>
        </label>
      </div>

      <div class="cmpRow">
        <div>
          <div class="cmpRowTitle">Statistice</div>
          <div class="cmpRowDesc">Ne ajuta sa intelegem cum este folosita aplicatia si ce parti trebuie imbunatatite.</div>
        </div>
        <label class="cmpSwitch" for="cmpStats">
          <input id="cmpStats" type="checkbox">
          <span></span>
        </label>
      </div>

      <div class="cmpRow">
        <div>
          <div class="cmpRowTitle">Marketing</div>
          <div class="cmpRowDesc">Activeaza scripturile de remarketing si masurarea campaniilor.</div>
        </div>
        <label class="cmpSwitch" for="cmpMkt">
          <input id="cmpMkt" type="checkbox">
          <span></span>
        </label>
      </div>

      <div class="cmpModalFoot">
        <a class="cmpLink" href="<?= htmlspecialchars($cookiesUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Vezi politica de cookie-uri</a>
        <div class="cmpActions">
          <button type="button" class="cmpBtn ghost" data-cmp-action="reject">Respinge</button>
          <button type="button" class="cmpBtn" data-cmp-close>Inchide</button>
          <button type="button" class="cmpBtn primary" data-cmp-action="save">Salveaza</button>
        </div>
      </div>
    </div>
  </div>
</div>
