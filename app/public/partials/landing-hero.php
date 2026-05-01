<?php
declare(strict_types=1);
?>
<section class="hero" id="audit">
  <div class="container">
    <div class="hero-grid">

      <div class="hero-left">
        <div class="eyebrow">
          <span class="dot"></span>
          GRATUIT · FĂRĂ CONT · RAPORT ÎN ~60 DE SECUNDE
        </div>

        <h1>
          Vezi în 60 de secunde<br>
          ce blochează site-ul tău în
          <span class="underline"><span class="accent-word">Google</span></span>
        </h1>

        <p class="hero-lead">
          Audit SEO gratuit cu 90+ verificări on-page. Primești scor, listă cu probleme prioritare și pașii concreți de rezolvare. Fără cont, fără card.
        </p>
      </div>

      <div class="hero-right">
        <div class="audit-panel">
          <div class="panel-header">
            <div class="panel-title">Pornește auditul</div>
            <div class="panel-kicker">90+ verificări · gratuit</div>
          </div>

          <div class="context-switch-shell">
            <div class="tabs" role="radiogroup" aria-label="Tip audit" data-active="article">
              <button
                type="button"
                class="tab active context-toggle"
                data-type="article"
                role="radio"
                aria-checked="true"
              >Pagină standard</button>
              <button
                type="button"
                class="tab context-toggle"
                data-type="local"
                role="radio"
                aria-checked="false"
              >Pagină locală</button>
            </div>
          </div>

          <form id="scoreForm" novalidate>
            <div class="url-input-wrap">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
              </svg>
              <input
                id="urlInput"
                name="url"
                type="url"
                inputmode="url"
                autocomplete="url"
                autocapitalize="none"
                spellcheck="false"
                pattern="https?://.+|[A-Za-z0-9.-]+\.[A-Za-z]{2,}.*"
                placeholder="novaweb.ro sau https://..."
                required
                class="url-input"
              >
              <button class="btn btn-primary" type="submit">
                Vreau auditul gratuit
              </button>
            </div>

            <div id="urlError" class="inline-feedback" aria-live="polite"></div>

            <div class="panel-note">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/></svg>
              <span id="contextHint">Auditul standard analizează on-page, structură și indexare. Dacă pagina țintește un oraș, alege „Pagină locală" pentru verificări Google Maps / Local Pack.</span>
            </div>
          </form>

          <div class="panel-preview">
            <div class="mini-orb">
              <svg width="68" height="68" viewBox="0 0 68 68" style="transform:rotate(-90deg)">
                <circle cx="34" cy="34" r="28" stroke="rgba(15,28,46,0.08)" stroke-width="6" fill="none"/>
                <circle cx="34" cy="34" r="28" stroke="#F97316" stroke-width="6" fill="none"
                  stroke-linecap="round"
                  stroke-dasharray="175.93"
                  stroke-dashoffset="24.63"/>
              </svg>
              <div class="num">86</div>
            </div>
            <div class="lines">
              <div class="line">
                <span>Conținut &amp; media</span>
                <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-dim)">40/40</span>
              </div>
              <div class="line-bar"><span style="width:100%"></span></div>
              <div class="line">
                <span>Structură &amp; indexare</span>
                <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-dim)">25/25</span>
              </div>
              <div class="line-bar"><span style="width:100%"></span></div>
              <div class="line">
                <span>Metadate</span>
                <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-dim)">16/20</span>
              </div>
              <div class="line-bar"><span style="width:80%"></span></div>
            </div>
          </div>

          <div class="panel-stats">
            <div class="panel-stat">
              <div class="panel-stat-val ok">33</div>
              <div class="panel-stat-label">Reușite</div>
            </div>
            <div class="panel-stat">
              <div class="panel-stat-val warn">21</div>
              <div class="panel-stat-label">Avertizări</div>
            </div>
            <div class="panel-stat">
              <div class="panel-stat-val danger">0</div>
              <div class="panel-stat-label">Nereușite</div>
            </div>
          </div>
        </div>
      </div>

      <div class="trust-row">
        <span class="trust-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          Scor și priorități în ~60 secunde
        </span>
        <span class="trust-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg>
          Recomandări în limbaj clar, nu tehnic
        </span>
        <span class="trust-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
          PDF pentru developer sau agenția ta
        </span>
        <span class="trust-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 7-8 13-8 13S4 17 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
          Mod pentru pagini cu audiență locală
        </span>
      </div>

    </div>
  </div>
</section>
