# Landing Page Redesign — Design Spec
**Date:** 2026-04-15  
**Status:** Approved  
**Approach:** CSS Rewrite complet (Approach A)

---

## Context

Aplicația este un audit SEO gratuit bazat pe PHP cu partiale și un singur fișier `style.css`. Design-ul curent este dark-theme cu accent teal (#4dd0c8). Redesign-ul urmărește un design sistem light-theme cu accent coral, bazat pe un prototip Lovable (React + Tailwind), tradus în PHP + vanilla CSS fără dependințe noi.

**Scope:** Numai landing page-ul (Navbar, Hero, HowItWorks, ReportDemo static, FAQ, Footer). Workspace-ul raportului real (secțiunea interactivă post-analiză) rămâne neschimbat în această etapă.

---

## Design System

### Fonturi
Adăugate via Google Fonts în `seo-meta.php`:
- **Heading:** `Sora` weights 700, 800
- **Body:** `Manrope` weights 400, 500, 600

### CSS Custom Properties (`:root`)

```css
/* Layout */
--radius: 0.75rem;
--radius-sm: 0.5rem;
--radius-lg: 1rem;
--radius-xl: 1.25rem;
--radius-2xl: 1.5rem;
--radius-3xl: 1.75rem;

/* Colors */
--bg:           #f7f8fa;           /* page background */
--card:         #ffffff;           /* cards, panels */
--surface:      #f2f4f7;           /* secondary surfaces */
--border:       #e5e8ef;           /* borders */
--text:         #1a2030;           /* primary text */
--muted:        #6b7a99;           /* secondary text */

--coral:        oklch(0.7 0.18 45);        /* primary accent */
--coral-light:  oklch(0.95 0.04 45);       /* coral pastel bg */
--coral-fg:     #ffffff;                   /* text on coral */

--success:      oklch(0.65 0.18 155);      /* green */
--success-fg:   #ffffff;
--warning:      oklch(0.78 0.15 75);       /* amber */
--warning-fg:   oklch(0.3 0.05 75);
--danger:       oklch(0.577 0.245 27.325); /* red */
--danger-fg:    #ffffff;

/* Shadows */
--shadow-soft:     0 1px 3px oklch(0 0 0 / 0.04), 0 4px 12px oklch(0 0 0 / 0.03);
--shadow-elevated: 0 2px 8px oklch(0 0 0 / 0.04), 0 8px 24px oklch(0 0 0 / 0.06);
--shadow-coral:    0 4px 14px oklch(0.7 0.18 45 / 0.25);
```

### Utility classes
- `.text-gradient-coral` — gradient coral pe text (pentru "Google" în H1)
- `.font-heading` — forțează fontul Sora
- `.shadow-soft`, `.shadow-elevated`, `.shadow-coral`

---

## Componente

### 1. Navbar (`landing-header.php`)
- `position: sticky; top: 0; z-index: 50`
- `backdrop-filter: blur(20px)`, `background: rgba(247,248,250,0.85)`
- `border-bottom: 1px solid var(--border)`
- **Logo:** box 32×32 coral rounded-lg + SVG NovaWeb (3 fețe) + "NovaWeb" Sora bold
- **Nav links:** text-sm, color muted, hover → text
- **Dreapta:** "novaweb.ro →" link, text muted

### 2. Hero (`landing-hero.php`)
- Background `var(--bg)`, centered, `padding: 96px 0 112px`
- **Background decorativ:** 2 blob-uri coral/5% blur-3xl, `pointer-events: none`
- **Badge:** `display: inline-flex`, bg `var(--coral-light)`, text `var(--coral)`, border-radius pill, icon Zap + text
- **H1:** Sora 800, `clamp(40px, 5vw, 64px)`, tracking tight, "Google" cu `.text-gradient-coral`
- **Subtitle:** Manrope, muted, max-width 40ch, `font-size: 1.0625rem`
- **Input card:** `border: 1px solid var(--border)`, `border-radius: 1rem`, padding 8px, `box-shadow: var(--shadow-elevated)`
  - Label text-xs deasupra
  - Row: search icon + `<input>` + buton coral
  - Sub row: tabs Standard / Local SEO (butoane toggle, același mecanism JS existent)
- **Trust signals:** 3 spans inline cu icon + text, `gap: 24px`, culori: success/coral/blue-400

### 3. HowItWorks (`landing-how.php`)
- Section bg `var(--bg)`, `padding: 80px 0`
- Kicker coral uppercase + H2 Sora + subtitle muted
- **3 carduri** în grid 3 coloane, `text-align: center`
  - Icon box: 64×64, bg `var(--coral-light)`, border-radius `var(--radius-xl)`, icon `var(--coral)`
  - Hover: bg devine `var(--coral)`, icon alb, `box-shadow: var(--shadow-coral)`
  - "Pasul N" coral bold text-xs, H3 Sora, p muted

### 4. ReportDemo (`landing-example.php`)
Container: `border-radius: var(--radius-3xl)`, `border: 1px solid var(--border)`, `box-shadow: var(--shadow-elevated)`, `overflow: hidden`, bg `var(--card)`

#### 4a. ReportHeader
- Border-bottom, bg `var(--card)`
- Top bar: icon coral + "Raport Audit SEO" kicker coral + URL H1 | 3 butoane
  - Butoane: "Rulează din nou" (outline), "Descarcă PDF" (outline), "Distribuie" (coral)
- Score grid: ScoreCircle 72/100 (SVG cu stroke animat) + 4 ScoreCard-uri
  - ScoreCard: bg `var(--surface)`, border, text centrat, mini progress bar color-coded

#### 4b. ExecutiveSummary
- `border-radius: var(--radius-xl)`, border, bg `var(--card)`, padding 32px
- Rezumat text în box `var(--coral-light)/50` padding 20px, border-radius lg
- 4 stat carduri: icon box colored + valoare bold + label small

#### 4c. AuditChecklist
- 3 categorii (SEO On-Page, SEO Tehnic, SEO Local)
- Fiecare categorie: `border-radius: var(--radius-xl)`, border, overflow hidden
  - Header: bg `var(--surface)`, border-bottom, titlu + badge-uri (eșuate/avertismente/OK)
  - Rows: `<details>`/`<summary>` HTML nativ
    - Icon status (✓ verde / ✗ roșu / ⚠ amber) + titlu + descriere
    - Badge impact dreapta
    - Detalii (recomandare) în panel expandat, bg `var(--surface)/50`

#### 4d. ActionPlan
- Border, bg `var(--card)`, border-radius xl
- Header cu titlu + subtitle
- Lista: number badge coral + titlu + meta (timp + categorie badge) + impact badge
- Primele 3 vizibile complet, restul `opacity: 0.5` cu icon 🔒
- Footer: text "X acțiuni blocate" + buton coral "Deblochează tot"

#### 4e. ReportCTA
- `border-radius: var(--radius-xl)`, bg `var(--text)` (dark), overflow hidden
- H3 alb + subtitle alb/70%
- 2 butoane: "Trimite raportul pe email" (coral) + "Discută cu un specialist" (ghost pe dark)

### 5. FAQ (`landing-faq.php`)
- Section bg `var(--bg)`, padding 80px 0, max-width 672px centrat
- Kicker coral + H2 Sora
- Items: `<details>` cu styling custom
  - `border-radius: var(--radius-xl)`, bg `var(--card)`, padding `20px`, `box-shadow: var(--shadow-soft)`
  - `<summary>`: Sora semibold, `list-style: none`, chevron SVG rotit la deschidere
  - Conținut: Manrope, muted, padding-top 12px

### 6. Footer (`landing-footer.php`)
- `border-top: 1px solid var(--border)`, bg `var(--bg)`, padding 40px 0
- Flex row: logo stânga, copyright centru/dreapta
- Logo: același box coral 28×28 + "NovaWeb" Sora

---

## Fișiere modificate

| Fișier | Tipul modificării |
|---|---|
| `app/public/style.css` | Rescris complet |
| `app/public/partials/seo-meta.php` | Adăugat Google Fonts link |
| `app/public/partials/landing-header.php` | HTML restructurat |
| `app/public/partials/landing-hero.php` | HTML restructurat |
| `app/public/partials/landing-how.php` | HTML restructurat |
| `app/public/partials/landing-example.php` | Rescris complet (4 sub-secțiuni) |
| `app/public/partials/landing-faq.php` | HTML restructurat + CSS nou |
| `app/public/partials/landing-footer.php` | HTML restructurat |

**Neatins:** `view-home.php`, workspace partials (report-header, report-issues, etc.), JS assets, API, toate clasele CSS ale workspace-ului real.

---

## Constrângeri tehnice

- Fără dependențe noi (no Tailwind, no npm, no build step)
- `<details>`/`<summary>` pentru accordion FAQ și AuditChecklist expandable
- Mecanismul JS existent pentru tabs Standard/Local SEO rămâne neschimbat (`data-type` pe butoane)
- CSS-ul workspace-ului (clasele `.workspace-*`, `.report-*`, `.score-*` etc.) trebuie păstrat funcțional — se separă clar de clasele landing page
- `oklch()` este suportat în Chrome 111+, Firefox 113+, Safari 16.4+ — acceptabil pentru target audience
