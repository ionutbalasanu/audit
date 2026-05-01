# Landing Page Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign landing page to match the Lovable prototype — light theme, coral accent, Sora/Manrope fonts, with a rich report demo section (ReportHeader + ExecutiveSummary + AuditChecklist + ActionPlan + dark CTA).

**Architecture:** Pure PHP partials + vanilla CSS, no build step. CSS is split across `assets/{tokens,base,components,landing}.css`. Tokens and base styles already match the design system (coral `#f97316`, Sora + Manrope). Only `landing-example.php` needs a complete HTML rewrite; `landing.css` needs old `demo-*` CSS replaced with new component classes. Minor SVG/copy updates for header and HowItWorks.

**Tech Stack:** PHP 8.x partials, vanilla CSS (no Tailwind/npm), no JS changes required.

---

## File Map

| File | Change |
|---|---|
| `app/public/partials/landing-header.php` | Update brand SVG from hexagon to 3D box |
| `app/public/partials/landing-footer.php` | Same brand SVG update |
| `app/public/partials/landing-how.php` | Update step 3 icon + copy + add hover CSS class |
| `app/public/partials/landing-example.php` | **Complete rewrite** — 5-section report demo |
| `app/public/assets/landing.css` | Remove old `demo-*` CSS; add new component CSS + step hover + updated @media |

**Unchanged:** `tokens.css`, `base.css`, `components.css`, `report.css`, `states.css`, `seo-meta.php`, workspace partials, all JS.

---

## Task 1: Update brand SVG (header + footer)

**Files:**
- Modify: `app/public/partials/landing-header.php`
- Modify: `app/public/partials/landing-footer.php`

The current brand icon uses a hexagon path. The Lovable design uses a 3D isometric box (3 face paths with different opacities). The `.brand-icon svg` CSS rule sets `fill: currentColor`, so we override per-path using inline `style`.

- [ ] **Step 1: Update landing-header.php brand SVG**

Replace the `<span class="brand-icon">` block:

```php
<!-- OLD -->
<span class="brand-icon" aria-hidden="true">
  <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5L14 4.8V11.2L8 14.5L2 11.2V4.8L8 1.5Z"/></svg>
</span>
```

with:

```php
<!-- NEW -->
<span class="brand-icon" aria-hidden="true">
  <svg viewBox="0 0 16 16" width="16" height="16">
    <path d="M2 4L8 2L14 4L8 6L2 4Z" style="fill:white"/>
    <path d="M2 4V10L8 12V6L2 4Z" style="fill:white;opacity:0.8"/>
    <path d="M14 4V10L8 12V6L14 4Z" style="fill:white;opacity:0.6"/>
  </svg>
</span>
```

- [ ] **Step 2: Update landing-footer.php brand SVG**

Same replacement — find the identical `<span class="brand-icon">` block in `landing-footer.php` and apply the same change.

- [ ] **Step 3: Commit**

```bash
cd "d:/Work/Novaweb Projects/audit-seo-gratuit/audit-seo-gratuit"
git add app/public/partials/landing-header.php app/public/partials/landing-footer.php
git commit -m "feat: update brand SVG to 3D isometric box icon"
```

---

## Task 2: Update HowItWorks step 3 (landing-how.php)

**Files:**
- Modify: `app/public/partials/landing-how.php`

Step 3 currently shows a grid/table icon with text "Deblochezi sau delegi". The Lovable design uses a lightning/rocket icon with text "Acționezi".

- [ ] **Step 1: Replace step 3 article in landing-how.php**

Find the third `<article class="step-card">` block (Pasul 3) and replace entirely:

```php
<!-- OLD Pasul 3 -->
<article class="step-card">
  <span class="step-icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
      <path d="M8 12h8"></path>
      <path d="M12 8v8"></path>
      <path d="M4 6h16v12H4z"></path>
    </svg>
  </span>
  <span class="step-index">Pasul 3</span>
  <h3>Deblochezi sau delegi</h3>
  <p>Trimiti raportul pe email, il partajezi echipei sau lasi o cerere contextuala daca vrei implementare asistata.</p>
</article>
```

```php
<!-- NEW Pasul 3 -->
<article class="step-card">
  <span class="step-icon" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
      <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/>
    </svg>
  </span>
  <span class="step-index">Pasul 3</span>
  <h3>Acționezi</h3>
  <p>Urmezi planul de acțiune pas cu pas sau trimiți raportul unui specialist pentru implementare asistată.</p>
</article>
```

- [ ] **Step 2: Verify in browser** — open the page and confirm "Trei pași simpli" shows the lightning icon on step 3.

- [ ] **Step 3: Commit**

```bash
git add app/public/partials/landing-how.php
git commit -m "feat: update step 3 icon and copy in HowItWorks"
```

---

## Task 3: Rewrite landing-example.php

**Files:**
- Modify: `app/public/partials/landing-example.php`

This is a complete rewrite. The new structure has 5 sections inside `.demo-report`:
1. `demo-rh` — ReportHeader (topbar + ScoreCircle + 4 ScoreCards)
2. `demo-summary` — ExecutiveSummary (highlighted text + 4 stat cards)
3. `demo-checklist` — AuditChecklist (3 categories with expandable `<details>` rows)
4. `demo-plan` — ActionPlan (8 numbered items, first 3 unlocked)
5. `demo-cta-dark` — ReportCTA (dark background, 2 buttons)

- [ ] **Step 1: Replace the entire content of landing-example.php**

```php
<?php
declare(strict_types=1);
?>
<section class="landing-section example-section" id="example-report">
  <div class="shell-container">

    <div class="section-heading section-heading-centered">
      <p class="section-kicker">Exemplu de raport</p>
      <h2 class="section-title">Ce vei primi după analiză</h2>
      <p class="section-copy">Iată un exemplu real de raport generat pentru un site din România. Scor, probleme, recomandări și plan de acțiune — totul într-un singur loc.</p>
    </div>

    <div class="demo-report">

      <!-- ── 1. REPORT HEADER ─────────────────────────────── -->
      <div class="demo-rh">

        <div class="demo-rh-topbar">
          <div class="demo-rh-brand">
            <div class="demo-rh-globe-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                <circle cx="12" cy="12" r="9"/>
                <path d="M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/>
              </svg>
            </div>
            <div>
              <p class="section-kicker" style="margin:0">Raport Audit SEO</p>
              <h3 class="demo-rh-url">novaweb.ro</h3>
            </div>
          </div>
          <div class="demo-rh-actions">
            <button type="button" class="button button-secondary button-small" disabled>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
              Rulează din nou
            </button>
            <button type="button" class="button button-secondary button-small" disabled>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Descarcă PDF
            </button>
            <button type="button" class="button button-primary button-small" onclick="document.getElementById('urlInput').focus(); document.getElementById('landingSurface').scrollIntoView({behavior:'smooth'});">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
              Distribuie
            </button>
          </div>
        </div>

        <div class="demo-rh-scores">
          <!-- ScoreCircle 72/100 — stroke color warning (50–79 range) -->
          <div class="demo-rh-circle-wrap">
            <div class="demo-rh-circle">
              <svg viewBox="0 0 118 118" width="128" height="128" style="transform:rotate(-90deg)">
                <circle cx="59" cy="59" r="54" fill="none" stroke="var(--line-subtle)" stroke-width="5"/>
                <circle cx="59" cy="59" r="54" fill="none" stroke="var(--warning)" stroke-width="5"
                  stroke-linecap="round" stroke-dasharray="339.3" stroke-dashoffset="95"/>
              </svg>
              <div class="demo-rh-circle-text">
                <strong>72</strong>
              </div>
            </div>
            <span class="demo-rh-circle-label">Scor SEO general</span>
          </div>

          <!-- 4 ScoreCards -->
          <div class="demo-rh-subcards">
            <div class="demo-rh-subcard">
              <p class="demo-rh-subcard-label">Performanță</p>
              <p class="demo-rh-subcard-value is-success">85</p>
              <div class="demo-rh-bar"><div class="demo-rh-bar-fill is-success" style="width:85%"></div></div>
            </div>
            <div class="demo-rh-subcard">
              <p class="demo-rh-subcard-label">SEO On-Page</p>
              <p class="demo-rh-subcard-value is-warning">68</p>
              <div class="demo-rh-bar"><div class="demo-rh-bar-fill is-warning" style="width:68%"></div></div>
            </div>
            <div class="demo-rh-subcard">
              <p class="demo-rh-subcard-label">SEO Tehnic</p>
              <p class="demo-rh-subcard-value is-warning">74</p>
              <div class="demo-rh-bar"><div class="demo-rh-bar-fill is-warning" style="width:74%"></div></div>
            </div>
            <div class="demo-rh-subcard">
              <p class="demo-rh-subcard-label">SEO Local</p>
              <p class="demo-rh-subcard-value is-danger">45</p>
              <div class="demo-rh-bar"><div class="demo-rh-bar-fill is-danger" style="width:45%"></div></div>
            </div>
          </div>
        </div>

      </div><!-- /demo-rh -->

      <!-- ── 2. EXECUTIVE SUMMARY ─────────────────────────── -->
      <div class="demo-summary surface-card">
        <h3 class="demo-summary-title">Sumar Executiv</h3>
        <p class="demo-summary-sub">O privire de ansamblu asupra stării site-ului tău.</p>

        <div class="demo-summary-highlight">
          <p style="margin:0;font-size:14px;line-height:1.65">Site-ul <strong>novaweb.ro</strong> are o fundație tehnică solidă, dar pierde vizibilitate din cauza lipsei optimizării locale și a unor probleme de conținut. Cu 3 acțiuni rapide poți crește traficul organic estimat cu <strong>40–60%</strong>.</p>
        </div>

        <div class="demo-summary-grid">
          <div class="demo-summary-stat">
            <div class="demo-summary-icon is-danger-bg" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div><strong class="demo-summary-val">5</strong><span class="demo-summary-lbl">Probleme critice</span></div>
          </div>
          <div class="demo-summary-stat">
            <div class="demo-summary-icon is-warning-bg" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg>
            </div>
            <div><strong class="demo-summary-val">8</strong><span class="demo-summary-lbl">Avertismente</span></div>
          </div>
          <div class="demo-summary-stat">
            <div class="demo-summary-icon is-success-bg" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div><strong class="demo-summary-val">23</strong><span class="demo-summary-lbl">Verificări OK</span></div>
          </div>
          <div class="demo-summary-stat">
            <div class="demo-summary-icon is-coral-bg" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
            <div><strong class="demo-summary-val">99–294€</strong><span class="demo-summary-lbl">Impact estimat/lună</span></div>
          </div>
        </div>
      </div><!-- /demo-summary -->

      <!-- ── 3. AUDIT CHECKLIST ────────────────────────────── -->
      <div class="demo-checklist">
        <div class="demo-checklist-intro">
          <h3 class="demo-checklist-title">Verificări detaliate</h3>
          <p class="demo-checklist-sub">Fiecare verificare are un status și recomandări concrete.</p>
        </div>

        <!-- Category: SEO On-Page -->
        <div class="demo-category">
          <div class="demo-category-head">
            <span class="demo-category-name">SEO On-Page</span>
            <div class="demo-category-badges">
              <span class="status-badge critical">2 eșuate</span>
              <span class="status-badge medium">1 atenționare</span>
              <span class="status-badge success-pill">3 OK</span>
            </div>
          </div>
          <div class="demo-checks">
            <div class="demo-check-row">
              <span class="demo-check-icon is-pass" aria-label="OK"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg></span>
              <div class="demo-check-body"><strong>Title tag prezent</strong><span>Pagina are un title tag unic de 58 caractere.</span></div>
              <span class="status-badge high">Impact mare</span>
            </div>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-fail" aria-label="Eșuat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>
                <div class="demo-check-body"><strong>Meta description lipsă</strong><span>Nu există meta description. Google va genera una automat.</span></div>
                <span class="status-badge high">Impact mare</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Adaugă o meta description de 150–160 caractere care include cuvintele cheie principale.</div>
            </details>
            <div class="demo-check-row">
              <span class="demo-check-icon is-pass" aria-label="OK"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg></span>
              <div class="demo-check-body"><strong>Heading H1 unic</strong><span>Pagina conține un singur H1, corect structurat.</span></div>
            </div>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-warn" aria-label="Atenție"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                <div class="demo-check-body"><strong>Imagini fără atribut alt</strong><span>3 din 12 imagini nu au atribut alt descriptiv.</span></div>
                <span class="status-badge medium">Impact mediu</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Adaugă text alternativ relevant pentru fiecare imagine.</div>
            </details>
            <div class="demo-check-row">
              <span class="demo-check-icon is-pass" aria-label="OK"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg></span>
              <div class="demo-check-body"><strong>URL-uri SEO-friendly</strong><span>Structura URL este curată și descriptivă.</span></div>
            </div>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-fail" aria-label="Eșuat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>
                <div class="demo-check-body"><strong>Conținut subțire</strong><span>Pagina principală are doar 180 cuvinte. Minim recomandat: 300.</span></div>
                <span class="status-badge high">Impact mare</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Adaugă conținut relevant care descrie serviciile oferite și zona de acoperire.</div>
            </details>
          </div>
        </div><!-- /SEO On-Page -->

        <!-- Category: SEO Tehnic -->
        <div class="demo-category">
          <div class="demo-category-head">
            <span class="demo-category-name">SEO Tehnic</span>
            <div class="demo-category-badges">
              <span class="status-badge critical">1 eșuat</span>
              <span class="status-badge medium">2 atenționări</span>
              <span class="status-badge success-pill">3 OK</span>
            </div>
          </div>
          <div class="demo-checks">
            <div class="demo-check-row">
              <span class="demo-check-icon is-pass" aria-label="OK"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg></span>
              <div class="demo-check-body"><strong>HTTPS activ</strong><span>Certificatul SSL este valid și redirecționarea HTTP→HTTPS funcționează.</span></div>
            </div>
            <div class="demo-check-row">
              <span class="demo-check-icon is-pass" aria-label="OK"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg></span>
              <div class="demo-check-body"><strong>Sitemap.xml prezent</strong><span>Sitemap-ul conține 12 URL-uri și este accesibil.</span></div>
            </div>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-warn" aria-label="Atenție"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                <div class="demo-check-body"><strong>Robots.txt incomplet</strong><span>Robots.txt există dar nu referențiază sitemap-ul.</span></div>
                <span class="status-badge low">Impact mic</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Adaugă directiva Sitemap: https://novaweb.ro/sitemap.xml</div>
            </details>
            <div class="demo-check-row">
              <span class="demo-check-icon is-pass" aria-label="OK"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="20 6 9 17 4 12"/></svg></span>
              <div class="demo-check-body"><strong>Mobile-friendly</strong><span>Pagina trece testul de compatibilitate mobilă.</span></div>
            </div>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-fail" aria-label="Eșuat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>
                <div class="demo-check-body"><strong>Core Web Vitals — LCP</strong><span>Largest Contentful Paint: 4.2s (limită: 2.5s)</span></div>
                <span class="status-badge high">Impact mare</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Optimizează imaginile hero, folosește format WebP și lazy loading.</div>
            </details>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-warn" aria-label="Atenție"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                <div class="demo-check-body"><strong>Core Web Vitals — CLS</strong><span>Cumulative Layout Shift: 0.18 (limită: 0.1)</span></div>
                <span class="status-badge medium">Impact mediu</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Setează dimensiuni explicite pentru imagini și fonturi.</div>
            </details>
          </div>
        </div><!-- /SEO Tehnic -->

        <!-- Category: SEO Local -->
        <div class="demo-category">
          <div class="demo-category-head">
            <span class="demo-category-name">SEO Local</span>
            <div class="demo-category-badges">
              <span class="status-badge critical">2 eșuate</span>
              <span class="status-badge medium">2 atenționări</span>
            </div>
          </div>
          <div class="demo-checks">
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-fail" aria-label="Eșuat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>
                <div class="demo-check-body"><strong>Adresă fizică lipsă</strong><span>Nu se găsește o adresă fizică pe site. Esențial pentru SEO local.</span></div>
                <span class="status-badge high">Impact mare</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Adaugă adresa completă în header/footer și pe pagina de contact.</div>
            </details>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-fail" aria-label="Eșuat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></span>
                <div class="demo-check-body"><strong>Google Business Profile</strong><span>Nu s-a detectat legătura cu un profil Google Business.</span></div>
                <span class="status-badge high">Impact mare</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Creează și verifică un profil Google Business cu aceleași date NAP.</div>
            </details>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-warn" aria-label="Atenție"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                <div class="demo-check-body"><strong>Schema LocalBusiness lipsă</strong><span>Nu s-a găsit markup structurat de tip LocalBusiness.</span></div>
                <span class="status-badge medium">Impact mediu</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Adaugă JSON-LD cu datele firmei: nume, adresă, telefon, program.</div>
            </details>
            <details class="demo-check-row is-expandable">
              <summary>
                <span class="demo-check-icon is-warn" aria-label="Atenție"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></span>
                <div class="demo-check-body"><strong>Link de direcții lipsă</strong><span>Nu există un link direct către Google Maps.</span></div>
                <span class="status-badge medium">Impact mediu</span>
                <span class="demo-check-chevron" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg></span>
              </summary>
              <div class="demo-check-detail">💡 <strong>Recomandare:</strong> Adaugă un buton „Găsește-ne pe Google Maps" pe pagina de contact.</div>
            </details>
          </div>
        </div><!-- /SEO Local -->

      </div><!-- /demo-checklist -->

      <!-- ── 4. ACTION PLAN ──────────────────────────────────── -->
      <div class="demo-plan surface-card">
        <div class="demo-plan-header">
          <h3 class="demo-plan-title">Plan de Acțiune</h3>
          <p class="demo-plan-sub">Pașii prioritizați pentru a îmbunătăți scorul SEO.</p>
        </div>
        <div class="demo-plan-list">
          <div class="demo-plan-row">
            <div class="demo-plan-num">1</div>
            <div class="demo-plan-body">
              <p>Adaugă meta description pe fiecare pagină</p>
              <div class="demo-plan-meta"><span class="demo-plan-effort">⏱ 15 min</span><span class="demo-plan-cat">On-Page</span></div>
            </div>
            <span class="status-badge critical">Impact mare</span>
          </div>
          <div class="demo-plan-row">
            <div class="demo-plan-num">2</div>
            <div class="demo-plan-body">
              <p>Optimizează LCP — comprimă imaginile hero</p>
              <div class="demo-plan-meta"><span class="demo-plan-effort">⏱ 30 min</span><span class="demo-plan-cat">Tehnic</span></div>
            </div>
            <span class="status-badge critical">Impact mare</span>
          </div>
          <div class="demo-plan-row">
            <div class="demo-plan-num">3</div>
            <div class="demo-plan-body">
              <p>Adaugă adresa fizică în header și footer</p>
              <div class="demo-plan-meta"><span class="demo-plan-effort">⏱ 10 min</span><span class="demo-plan-cat">Local</span></div>
            </div>
            <span class="status-badge critical">Impact mare</span>
          </div>
          <div class="demo-plan-row is-locked">
            <div class="demo-plan-num">4</div>
            <div class="demo-plan-body">
              <p>Creează profil Google Business verificat <span class="demo-plan-lock">🔒</span></p>
              <div class="demo-plan-meta"><span class="demo-plan-effort">⏱ 1–2 zile</span><span class="demo-plan-cat">Local</span></div>
            </div>
            <span class="status-badge critical">Impact mare</span>
          </div>
          <div class="demo-plan-row is-locked">
            <div class="demo-plan-num">5</div>
            <div class="demo-plan-body">
              <p>Adaugă schema LocalBusiness JSON-LD <span class="demo-plan-lock">🔒</span></p>
              <div class="demo-plan-meta"><span class="demo-plan-effort">⏱ 20 min</span><span class="demo-plan-cat">Local</span></div>
            </div>
            <span class="status-badge medium">Impact mediu</span>
          </div>
          <div class="demo-plan-row is-locked">
            <div class="demo-plan-num">6</div>
            <div class="demo-plan-body">
              <p>Extinde conținutul paginii principale la 500+ cuvinte <span class="demo-plan-lock">🔒</span></p>
              <div class="demo-plan-meta"><span class="demo-plan-effort">⏱ 1 oră</span><span class="demo-plan-cat">On-Page</span></div>
            </div>
            <span class="status-badge critical">Impact mare</span>
          </div>
          <div class="demo-plan-row is-locked">
            <div class="demo-plan-num">7</div>
            <div class="demo-plan-body">
              <p>Adaugă alt text la toate imaginile <span class="demo-plan-lock">🔒</span></p>
              <div class="demo-plan-meta"><span class="demo-plan-effort">⏱ 15 min</span><span class="demo-plan-cat">On-Page</span></div>
            </div>
            <span class="status-badge medium">Impact mediu</span>
          </div>
          <div class="demo-plan-row is-locked">
            <div class="demo-plan-num">8</div>
            <div class="demo-plan-body">
              <p>Corectează CLS — setează dimensiuni pentru imagini <span class="demo-plan-lock">🔒</span></p>
              <div class="demo-plan-meta"><span class="demo-plan-effort">⏱ 20 min</span><span class="demo-plan-cat">Tehnic</span></div>
            </div>
            <span class="status-badge medium">Impact mediu</span>
          </div>
        </div>
        <div class="demo-plan-footer">
          <p class="demo-plan-footer-text">🔒 5 acțiuni sunt blocate — deblochează raportul complet</p>
          <button type="button" class="button button-primary button-small" onclick="document.getElementById('urlInput').focus(); document.getElementById('landingSurface').scrollIntoView({behavior:'smooth'});">
            Deblochează tot
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12,5 19,12 12,19"/></svg>
          </button>
        </div>
      </div><!-- /demo-plan -->

      <!-- ── 5. REPORT CTA (dark) ───────────────────────────── -->
      <div class="demo-cta-dark">
        <div class="demo-cta-copy">
          <h3 class="demo-cta-title">Vrei implementarea completă?</h3>
          <p class="demo-cta-sub">Primești planul detaliat, raportul tehnic complet și suport pentru implementare. Sau discută cu un specialist SEO gratuit.</p>
        </div>
        <div class="demo-cta-actions">
          <button type="button" class="button button-primary" onclick="document.getElementById('urlInput').focus(); document.getElementById('landingSurface').scrollIntoView({behavior:'smooth'});">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Trimite raportul pe email
          </button>
          <a href="<?= htmlspecialchars($seoServiceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="button demo-cta-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Discută cu un specialist
          </a>
        </div>
      </div><!-- /demo-cta-dark -->

    </div><!-- /demo-report -->
  </div>
</section>
```

- [ ] **Step 2: Verify PHP syntax** — run PHP lint

```bash
php -l "d:/Work/Novaweb Projects/audit-seo-gratuit/audit-seo-gratuit/app/public/partials/landing-example.php"
```
Expected output: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/public/partials/landing-example.php
git commit -m "feat: rewrite landing-example.php with full report demo (5 sections)"
```

---

## Task 4: Update landing.css

**Files:**
- Modify: `app/public/assets/landing.css`

Replace everything from line 384 (`.example-section {`) to the end of the file with the new CSS block below. Lines 1–382 (header, hero, how-it-works styles) remain unchanged, except we insert the step hover effect right after the existing `.step-card p` rule.

- [ ] **Step 1: Add step hover effect**

After the `.step-card p` block (around line 382), insert:

```css
.step-card {
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.step-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-form);
}

.step-card:hover .step-icon {
  background: var(--accent);
  border-color: var(--accent);
  box-shadow: var(--shadow-coral);
  color: #ffffff;
}
```

- [ ] **Step 2: Replace everything from `.example-section` to end of file**

Delete from `.example-section {` to the end of the file, then append:

```css
/* ─────────────────────────────────────────────────────────
   REPORT DEMO — WRAPPER
───────────────────────────────────────────────────────── */
.example-section {
  background: linear-gradient(180deg, rgba(245, 247, 250, 0.72), rgba(248, 250, 252, 0));
}

.demo-report {
  display: grid;
  gap: 12px;
  overflow: hidden;
  border-radius: var(--radius-2xl);
  border: 1px solid var(--line-subtle);
  box-shadow: var(--shadow-soft);
  background: var(--bg-soft);
  padding: 12px;
}

/* ─────────────────────────────────────────────────────────
   REPORT HEADER (demo-rh)
───────────────────────────────────────────────────────── */
.demo-rh {
  padding: 24px;
  border-radius: var(--radius-xl);
  background: var(--bg-surface);
  border: 1px solid var(--line-subtle);
}

.demo-rh-topbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.demo-rh-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.demo-rh-globe-icon {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: var(--accent-muted);
  color: var(--accent);
  border: 1px solid var(--accent-border);
}

.demo-rh-url {
  margin: 4px 0 0;
  font-family: var(--font-display);
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.03em;
  color: var(--bg-dark);
}

.demo-rh-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.demo-rh-scores {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 24px;
  align-items: center;
}

.demo-rh-circle-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.demo-rh-circle {
  position: relative;
  width: 128px;
  height: 128px;
}

.demo-rh-circle svg {
  width: 128px;
  height: 128px;
  display: block;
}

.demo-rh-circle-text {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
}

.demo-rh-circle-text strong {
  font-family: var(--font-display);
  font-size: 38px;
  font-weight: 800;
  line-height: 1;
  color: var(--warning);
}

.demo-rh-circle-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--text-secondary);
  text-align: center;
}

.demo-rh-subcards {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.demo-rh-subcard {
  padding: 14px;
  border-radius: var(--radius-lg);
  background: var(--bg-soft);
  border: 1px solid var(--line-subtle);
  text-align: center;
}

.demo-rh-subcard-label {
  margin: 0 0 6px;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary);
}

.demo-rh-subcard-value {
  margin: 0 0 8px;
  font-family: var(--font-display);
  font-size: 26px;
  font-weight: 800;
  line-height: 1;
}

.demo-rh-subcard-value.is-success { color: var(--success); }
.demo-rh-subcard-value.is-warning { color: var(--warning); }
.demo-rh-subcard-value.is-danger  { color: var(--critical); }

.demo-rh-bar {
  height: 6px;
  border-radius: 999px;
  background: var(--line-subtle);
  overflow: hidden;
}

.demo-rh-bar-fill {
  height: 100%;
  border-radius: 999px;
}

.demo-rh-bar-fill.is-success { background: var(--success); }
.demo-rh-bar-fill.is-warning { background: var(--warning); }
.demo-rh-bar-fill.is-danger  { background: var(--critical); }

/* ─────────────────────────────────────────────────────────
   EXECUTIVE SUMMARY (demo-summary)
───────────────────────────────────────────────────────── */
.demo-summary {
  padding: 24px;
}

.demo-summary-title {
  margin: 0 0 4px;
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 700;
  color: var(--bg-dark);
}

.demo-summary-sub {
  margin: 0 0 20px;
  font-size: 14px;
  color: var(--text-secondary);
}

.demo-summary-highlight {
  padding: 16px 20px;
  border-radius: var(--radius-lg);
  background: rgba(249, 115, 22, 0.06);
  border: 1px solid rgba(249, 115, 22, 0.12);
  margin-bottom: 20px;
}

.demo-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.demo-summary-stat {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  border-radius: var(--radius-lg);
  background: var(--bg-soft);
  border: 1px solid var(--line-subtle);
}

.demo-summary-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
}

.demo-summary-icon.is-danger-bg  { background: var(--critical-bg); color: var(--critical); }
.demo-summary-icon.is-warning-bg { background: var(--warning-bg);  color: var(--warning); }
.demo-summary-icon.is-success-bg { background: var(--success-bg);  color: var(--success); }
.demo-summary-icon.is-coral-bg   { background: var(--accent-muted); color: var(--accent); }

.demo-summary-val {
  display: block;
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 800;
  line-height: 1.1;
  color: var(--bg-dark);
}

.demo-summary-lbl {
  display: block;
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 2px;
}

/* ─────────────────────────────────────────────────────────
   AUDIT CHECKLIST (demo-checklist / demo-category)
───────────────────────────────────────────────────────── */
.demo-checklist {
  display: grid;
  gap: 12px;
}

.demo-checklist-intro {
  display: grid;
  gap: 4px;
}

.demo-checklist-title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 700;
  color: var(--bg-dark);
}

.demo-checklist-sub {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
}

.demo-category {
  overflow: hidden;
  border-radius: var(--radius-xl);
  border: 1px solid var(--line-subtle);
  background: var(--bg-surface);
}

.demo-category-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 20px;
  background: var(--bg-soft);
  border-bottom: 1px solid var(--line-subtle);
  flex-wrap: wrap;
}

.demo-category-name {
  font-family: var(--font-display);
  font-size: 14px;
  font-weight: 700;
  color: var(--bg-dark);
}

.demo-category-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.status-badge.success-pill {
  color: #15803d;
  background: var(--success-bg);
  border-color: rgba(34, 197, 94, 0.2);
}

.status-badge.low {
  color: var(--text-secondary);
  background: var(--low-bg);
  border-color: var(--line-subtle);
}

.demo-check-row,
details.demo-check-row > summary {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  border-bottom: 1px solid var(--line-subtle);
}

.demo-checks > .demo-check-row:last-child,
.demo-checks > details:last-child,
.demo-checks > details:last-child > summary {
  border-bottom: none;
}

details.demo-check-row > summary {
  cursor: pointer;
  list-style: none;
  width: 100%;
}

details.demo-check-row > summary::-webkit-details-marker {
  display: none;
}

details.demo-check-row.is-expandable > summary:hover {
  background: var(--bg-soft);
}

.demo-check-icon {
  width: 22px;
  height: 22px;
  border-radius: 999px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
}

.demo-check-icon.is-pass {
  background: var(--success-bg);
  color: var(--success);
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.demo-check-icon.is-fail {
  background: var(--critical-bg);
  color: var(--critical);
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.demo-check-icon.is-warn {
  background: var(--warning-bg);
  color: var(--warning);
  border: 1px solid rgba(234, 179, 8, 0.22);
}

.demo-check-body {
  flex: 1;
  min-width: 0;
}

.demo-check-body strong {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: var(--bg-dark);
}

.demo-check-body span {
  display: block;
  font-size: 12px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.demo-check-chevron {
  flex-shrink: 0;
  color: var(--text-secondary);
  transition: transform 0.2s ease;
}

details[open].demo-check-row .demo-check-chevron {
  transform: rotate(180deg);
}

.demo-check-detail {
  padding: 14px 20px 14px 54px;
  background: rgba(248, 250, 252, 0.8);
  border-top: 1px solid var(--line-subtle);
  font-size: 13px;
  line-height: 1.65;
  color: var(--text-primary);
}

/* ─────────────────────────────────────────────────────────
   ACTION PLAN (demo-plan)
───────────────────────────────────────────────────────── */
.demo-plan {
  overflow: hidden;
  padding: 0;
}

.demo-plan-header {
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--line-subtle);
}

.demo-plan-title {
  margin: 0 0 4px;
  font-family: var(--font-display);
  font-size: 16px;
  font-weight: 700;
  color: var(--bg-dark);
}

.demo-plan-sub {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
}

.demo-plan-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 24px;
  border-bottom: 1px solid var(--line-subtle);
  transition: background 0.15s ease;
}

.demo-plan-row:hover:not(.is-locked) {
  background: var(--bg-soft);
}

.demo-plan-row.is-locked {
  opacity: 0.5;
}

.demo-plan-num {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--accent-muted);
  color: var(--accent);
  border: 1px solid var(--accent-border);
  font-family: var(--font-display);
  font-size: 12px;
  font-weight: 800;
}

.demo-plan-body {
  flex: 1;
  min-width: 0;
}

.demo-plan-body > p {
  margin: 0 0 6px;
  font-size: 14px;
  font-weight: 600;
  color: var(--bg-dark);
}

.demo-plan-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.demo-plan-effort {
  font-size: 11px;
  color: var(--text-secondary);
}

.demo-plan-cat {
  display: inline-flex;
  align-items: center;
  min-height: 20px;
  padding: 0 8px;
  border-radius: 999px;
  background: var(--accent-muted);
  color: var(--accent);
  border: 1px solid var(--accent-border);
  font-size: 11px;
  font-weight: 700;
}

.demo-plan-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 24px;
  background: var(--bg-soft);
  border-top: 1px solid var(--line-subtle);
  flex-wrap: wrap;
}

.demo-plan-footer-text {
  margin: 0;
  font-size: 13px;
  color: var(--text-secondary);
}

/* ─────────────────────────────────────────────────────────
   REPORT CTA DARK (demo-cta-dark)
───────────────────────────────────────────────────────── */
.demo-cta-dark {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 28px 32px;
  border-radius: var(--radius-xl);
  background: linear-gradient(135deg, #1f2937 0%, #334155 100%);
  flex-wrap: wrap;
}

.demo-cta-title {
  margin: 0 0 8px;
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.03em;
  color: #ffffff;
}

.demo-cta-sub {
  margin: 0;
  max-width: 52ch;
  font-size: 14px;
  line-height: 1.65;
  color: rgba(255, 255, 255, 0.72);
}

.demo-cta-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.demo-cta-ghost {
  color: rgba(255, 255, 255, 0.85);
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.18);
}

.demo-cta-ghost:hover {
  background: rgba(255, 255, 255, 0.14);
  color: #ffffff;
}

/* ─────────────────────────────────────────────────────────
   FAQ + FOOTER
───────────────────────────────────────────────────────── */
.faq-section {
  padding-top: 72px;
}

.faq-list {
  display: grid;
  gap: 12px;
}

.faq-item {
  padding: 0 20px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.88);
  border: 1px solid var(--line-subtle);
  box-shadow: var(--shadow-card);
}

.faq-item summary {
  list-style: none;
  cursor: pointer;
  padding: 18px 0;
  font-size: 15px;
  font-weight: 800;
  color: var(--bg-dark);
}

.faq-item summary::-webkit-details-marker {
  display: none;
}

.faq-item p {
  margin: 0;
  padding: 0 0 18px;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.65;
}

.faq-link-row {
  display: flex;
  justify-content: center;
  margin-top: 24px;
}

.site-footer {
  padding: 28px 0 42px;
}

.footer-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-top: 22px;
  border-top: 1px solid rgba(226, 232, 240, 0.88);
}

.footer-copy {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.65;
}

/* ─────────────────────────────────────────────────────────
   ANIMATIONS
───────────────────────────────────────────────────────── */
@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.55; transform: scale(0.85); }
}

/* ─────────────────────────────────────────────────────────
   RESPONSIVE
───────────────────────────────────────────────────────── */
@media (max-width: 980px) {
  .hero-trust-row,
  .step-grid {
    grid-template-columns: 1fr;
  }

  .demo-rh-scores {
    grid-template-columns: 1fr;
  }

  .demo-rh-circle-wrap {
    justify-self: start;
  }

  .demo-rh-subcards {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .demo-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .demo-cta-dark {
    flex-direction: column;
    align-items: flex-start;
  }

  .footer-row {
    flex-direction: column;
    align-items: flex-start;
  }
}

@media (max-width: 760px) {
  .header-nav,
  .header-link {
    display: none;
  }

  .landing-hero {
    padding-top: 10px;
  }

  .hero-stage {
    padding-top: 32px;
  }

  .hero-h1 {
    max-width: 10ch;
    font-size: clamp(34px, 12vw, 48px);
  }

  .hero-sub {
    font-size: 15px;
  }

  .hero-form {
    padding: 14px;
    border-radius: 22px;
  }

  .hero-input-row {
    grid-template-columns: 1fr;
  }

  .demo-rh-subcards {
    grid-template-columns: 1fr 1fr;
  }

  .demo-summary-grid {
    grid-template-columns: 1fr 1fr;
  }

  .demo-rh-topbar {
    flex-direction: column;
  }

  .demo-plan-row {
    padding: 14px 16px;
  }

  .demo-plan-footer {
    flex-direction: column;
    align-items: flex-start;
  }

  .demo-cta-dark {
    padding: 22px 20px;
  }

  .demo-check-detail {
    padding-left: 20px;
  }

  .faq-item {
    padding: 0 16px;
  }
}
```

- [ ] **Step 3: Verify in browser** — open landing page and confirm:
  - Section "Ce vei primi după analiză" shows ReportHeader with score circle (72) + 4 score subcards
  - ExecutiveSummary shows 4 stat cards with colored icons
  - AuditChecklist shows 3 categories; clicking rows with chevron expands details
  - ActionPlan shows 8 rows, last 5 at 50% opacity with 🔒
  - Dark CTA at the bottom with 2 buttons
  - Step cards in HowItWorks have hover effect (icon bg becomes coral)

- [ ] **Step 4: Commit**

```bash
git add app/public/assets/landing.css
git commit -m "feat: replace demo CSS with new report demo component styles"
```

---

## Spec Coverage Check

| Spec requirement | Covered by |
|---|---|
| Light theme, coral accent | Already in tokens.css — no change needed |
| Sora + Manrope fonts | Already in seo-meta.php + tokens.css — no change needed |
| Navbar with 3D box logo | Task 1 |
| Hero section (existing) | Already done — no change |
| HowItWorks step 3 update | Task 2 |
| Step card hover effect | Task 4 (step 1 CSS) |
| ReportDemo — ReportHeader | Task 3 (section 1) + Task 4 (CSS) |
| ReportDemo — ExecutiveSummary | Task 3 (section 2) + Task 4 (CSS) |
| ReportDemo — AuditChecklist | Task 3 (section 3) + Task 4 (CSS) |
| ReportDemo — ActionPlan | Task 3 (section 4) + Task 4 (CSS) |
| ReportDemo — dark CTA | Task 3 (section 5) + Task 4 (CSS) |
| FAQ (existing) | Already done — no change |
| Footer logo update | Task 1 |
| Workspace/report CSS unchanged | Not touched |
