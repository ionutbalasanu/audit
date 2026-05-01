# Design Spec — Redesign Modern: audit-seo-gratuit

**Data:** 2026-04-15
**Abordare aprobată:** Abordarea 2 — Redesign Modern
**Status:** Aprobat de utilizator

---

## Decizii fundamentale (aprobate în brainstorming)

| Decizie | Valoare |
|---|---|
| Direcție vizuală | Warm Editorial — fond crem/alb, accent portocaliu `#ea782a`, IBM Plex Sans |
| Hero layout | Centrat, form card mare și proeminent (tip Vercel/Ahrefs) |
| Workspace navigare | Tab-uri sus cu underline activ — îmbunătățite față de actuale |
| Prioritate implementare | 1. Landing Hero + 2. Workspace Raport, apoi restul |
| Scope | Toate suprafețele — landing + workspace + componente |

---

## 1. Design System

### Fonturi
- **IBM Plex Sans** — corp, UI, labels (weights: 400, 500, 600, 700)
- **IBM Plex Mono** — valori numerice, URL-uri, cod (weights: 400, 500)
- Import Google Fonts, deja prezent în `assets/base.css`

### Tokeni de culoare (extindere minimă față de `tokens.css` existent)

```css
:root {
  /* Suprafețe */
  --bg-page: #faf8f4;
  --bg-surface: #ffffff;
  --bg-elevated: #fffdfa;
  --bg-sunken: #f5f3ef;
  --bg-dark: #0f1117;

  /* Text */
  --text-primary: #0f1117;
  --text-secondary: #4b5563;
  --text-tertiary: #6b7280;
  --text-muted: #9ca3af;

  /* Accent */
  --accent: #ea782a;
  --accent-hover: #d16820;
  --accent-muted: rgba(234, 120, 42, 0.1);
  --accent-border: rgba(234, 120, 42, 0.25);

  /* Linii */
  --line-subtle: rgba(15, 23, 42, 0.07);
  --line-strong: rgba(15, 23, 42, 0.13);

  /* Severitate issues */
  --critical: #dc2626;
  --critical-bg: #fef2f2;
  --high: #ea580c;
  --high-bg: #fff7ed;
  --medium: #ca8a04;
  --medium-bg: #fefce8;
  --low: #2563eb;
  --low-bg: #eff6ff;
  --success: #16a34a;
  --success-bg: #f0fdf4;

  /* Umbre */
  --shadow-card: 0 1px 3px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.06);
  --shadow-form: 0 2px 4px rgba(15,23,42,0.04), 0 12px 40px rgba(15,23,42,0.10);
  --shadow-modal: 0 8px 32px rgba(15,23,42,0.14), 0 2px 8px rgba(15,23,42,0.06);

  /* Border radius */
  --radius-sm: 8px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --radius-xl: 18px;
  --radius-2xl: 24px;

  /* Container */
  --container: 1160px;
  --container-pad: 32px;
  --header-height: 68px;
}
```

### Tipografie — scale

| Element | Size | Weight | Line-height | Letter-spacing |
|---|---|---|---|---|
| Hero H1 | `clamp(38px, 4.2vw, 58px)` | 700 | 1.10 | -0.03em |
| Section H2 | `clamp(26px, 2.6vw, 38px)` | 700 | 1.15 | -0.025em |
| Card H3 | 20px | 700 | 1.2 | -0.015em |
| Body large | 18px | 400 | 1.7 | 0 |
| Body default | 16px | 400 | 1.65 | 0 |
| Body small | 14px | 400 | 1.6 | 0 |
| Label | 13px | 600 | 1.4 | 0 |
| Eyebrow | 12px | 600 | 1 | 0.06em (uppercase) |
| Mono (URL, cod) | 14–15px | 400/500 | 1.5 | 0 |

### Butoane

```
.button-primary       → bg: #ea782a, color: white, radius: 10px, h: 48px, padding: 0 22px
.button-primary-hero  → identic cu primary dar h: 52px (în form card hero și workspace form)
.button-secondary     → bg: white, border: --line-strong, color: --text-primary, h: 48px
.button-ghost         → transparent, border: --line-subtle, color: --text-secondary, h: 36px
.button-small         → h: 36px, padding: 0 14px, font-size: 13px
```

Hover pe toate: `transform: translateY(-1px)`, tranziție 0.15s.

---

## 2. Landing — Header

**Structură:**
- Sticky, `backdrop-filter: blur(14px)`, fundal `rgba(250,248,244,0.92)`
- Înălțime: 68px
- Container max 1160px
- Stânga: logo NovaWeb (icon portocaliu 32×32 + text bold)
- Centru/dreapta: nav links (Cum funcționează, Ce verifică, FAQ) + buton ghost "novaweb.ro →"
- Mobile (≤768px): nav links ascunse, rămân logo + buton

---

## 3. Landing — Hero

**Layout:** centrat, full-width background, padding 96px top / 0 bottom (se termină cu preview produs)

**Background:**
```css
radial-gradient(ellipse 1400px 700px at 50% -80px, rgba(234,120,42,0.11), transparent 62%),
radial-gradient(ellipse 800px 500px at 10% 80%, rgba(234,120,42,0.05), transparent 55%),
linear-gradient(180deg, #fdfcf9 0%, #f8f3eb 55%, #f3ece1 100%)
```

**Structură verticală (de sus în jos):**

1. **Eyebrow badge** — pill cu dot animat portocaliu + text "Instrument gratuit de la NovaWeb"
   - `background: rgba(255,255,255,0.85)`, border portocaliu subtil, border-radius 999px

2. **H1** — `clamp(38px, 4.2vw, 58px)`, max-width 820px, centrat
   - Copy: "Află exact ce împiedică pagina ta să apară în **Google**." (keyword Google în accent color)

3. **Sub-headline** — 18px, max-width 580px, color `--text-secondary`, line-height 1.7
   - Copy: "Introdu URL-ul oricărei pagini. Primești în 30 de secunde un scor SEO, problemele prioritare și un plan de acțiune gata de trimis."

4. **Form card** — `width: min(720px, 100%)`, background white, border-radius 18px, shadow `--shadow-form`
   - Label: "URL-ul paginii de analizat" (13px, font-weight 600)
   - Input row: `grid-template-columns: 1fr auto`, gap 8px
   - Input: min-height 52px, IBM Plex Mono, placeholder color muted
   - Buton: min-height 52px, padding 0 28px, "Analizează →", icon săgeată
   - Context row: label "Tip audit:" + toggle pills "Standard" / "Local SEO"
   - Hint text sub toggle: descriere scurtă a contextului activ (12px, muted)

5. **Trust chips** — flex row, centrat, gap 10px + separatoare punct
   - ✓ 100% gratuit / ✓ Fără cont necesar / ✓ Rezultat în ~30 secunde / ✓ Link de share inclus

6. **Preview produs** — `width: min(960px, 100%)`, chrome browser mock (dots + URL bar)
   - Workspace mockup vizibil parțial — tabs + stat cards + top issues
   - `border-radius: 14px 14px 0 0`, se termină fără fundal cu fade-out gradient
   - Scopul: arată produsul înainte de conversie, umple lățimea paginii vizual

---

## 4. Landing — Secțiunile de jos

### 4.1 Cum funcționează
- Fundal: `#ffffff`
- Grid 3 coloane cu separator `1px` între ele, `border-radius: 16px`, overflow hidden
- Fiecare step: index mono (01/02/03) portocaliu, H3, paragraf
- Nu are imagini sau iconițe grele — textual, clar

### 4.2 Ce verifică (dark section)
- Fundal: `#0f1117` — contrast cu secțiunile albe vecine
- Grid 4 coloane (On-Page / Tehnic / Performanță / Local SEO)
- Fiecare coloană: heading cu icon mic portocaliu + listă cu bullet-uri subtile
- Separatoare `rgba(255,255,255,0.08)` între coloane

### 4.3 Exemplu de raport
- Fundal: `#ffffff`
- Layout split: stânga copy (eyebrow + H2 + lista cu 4 beneficii cu iconițe), dreapta preview workspace live
- Preview: chrome mock + score ring + top 3 issues reale

### 4.4 FAQ
- Fundal: `#faf8f4`
- Layout 2 coloane: stânga eyebrow + titlu + contact, dreapta accordeon
- FAQ items: background alb, border subtil, border-radius 12px, `+` icon se rotește la deschidere
- Minimum 5 întrebări relevante

### 4.5 CTA Band final
- Fundal: `#0f1117`
- H2 alb centrat, sub-headline muted, form inline (input + buton în același container dark)
- Repetă mecanismul de conversie din hero pentru utilizatorii care scrollează tot

---

## 5. Workspace — Header

Sticky, `z-index: 20`, fundal alb, `border-bottom: 1px solid --line-subtle`.

**Elemente (stânga → dreapta):**
1. Brand mark (logo mic + "NovaWeb") — 28×28px icon
2. URL pill — background `--bg-sunken`, IBM Plex Mono, icon search, URL analizat trunchiat
3. Score chip — ring mic (28px) + text "Scor SEO: 72/100", background accent-muted
4. Actions (margin-left: auto) — buton ghost "Share" (cu icon) + buton primary "Cere ofertă"

**Mobile:** URL pill dispare, scorul rămâne, acțiunile se mută în mobile action bar (jos).

---

## 6. Workspace — Tab Navigation

Linie separată sub header, fundal alb, `border-bottom: 1px solid --line-subtle`.

**Tab-uri:** Overview | Issues `(12)` | Plan 30 zile | Tehnic | Share & Email

- Stare activă: `color: --accent`, `border-bottom: 2px solid --accent`, `font-weight: 600`
- Badge count: pill mic `background: accent-muted`, color accent, 11px
- Inactive: color `--text-tertiary`, hover → `--text-secondary`
- Fără fundal pe tab-uri — doar underline activ

---

## 7. Workspace — Tab: Overview

**Stat cards row** — 4 carduri egale, gap 12px:
1. Scor SEO: ring vizual `conic-gradient`, număr + verdict text (ex: "Mediu — necesită atenție") + badge-uri severitate
2. Probleme totale: număr mare mono + "din 47 verificări"
3. Impact estimat: "Mare/Mic/Mediu" + explicație sursă
4. Efort rezolvare: estimare ore + calificativ

**Top probleme prioritare** — titlu secțiune uppercase 11px + lista issue rows:
- Fiecare row: badge severitate | titlu | descriere scurtă | impact estimat (portocaliu, dreapta)
- Maxim 5 issues în overview, link "Vezi toate (12) →" sub

---

## 8. Workspace — Tab: Issues

**Toolbar filtru:** pills "Toate (N)" / "Critice (N)" / "High (N)" / "Medium (N)" / "Low (N)"
- Activ: `background: #0f1117`, color white
- Inactiv: background white, border subtil

**Issue cards** — fiecare card:
- `border-left: 3px solid` cu culoarea severității (roșu/portocaliu/galben/albastru)
- Header: badge severitate + titlu H4
- Body: paragraf descriptiv (ce e, de ce contează)
- Fix sugerat: text verde cu prefix "→", acțiune concretă

---

## 9. Workspace — Tab: Plan 30 zile

**Plan cards** — grid single column, gap 12px:
- Layout: `grid-template-columns: 48px 1fr` — număr mare portocaliu (box 48×48) + conținut
- Număr: IBM Plex Mono, 18px, background `accent-muted`
- Conținut: H4 + paragraf + meta row (tags: Efort, Impact, Săptămâna)
- Tags: color-coded — efort mic=verde, efort mediu=galben, impact mare=portocaliu

---

## 10. Workspace — Tab: Tehnic

**Tech groups** — carduri colapsabile, unul per categorie (Indexare & Crawl, Performanță, On-Page, Social & Schema):
- Header grup: titlu + scor "N/M checks OK" + arrow icon
- Body: tabel de rows — label + status pill
- Status pills: `s-pass` (verde), `s-warn` (galben), `s-fail` (roșu), height 24px

---

## 11. Workspace — Tab: Share & Email

**Grid 2 coloane**, gap 16px:

**Card 1 — Link de share:**
- Titlu + descriere scurtă
- URL display row: `font-family: mono`, background sunken + buton "Copiază"
- Buton principal: "Copiază link"

**Card 2 — Email report:**
- Titlu + descriere
- Input email + buton primary full-width "Trimite raportul pe email"

---

## 12. Loading State

Centrat, padding 80px, afișat între submit form și apariția workspace-ului.

**Elemente:**
- Spinner ring: 56px, `border-top-color: --accent`, animație spin 0.85s linear
- Titlu: "Se analizează pagina..." 18px, font-weight 600
- Sub-titlu: "Verificăm 47 de semnale SEO. Durează ~30 de secunde." muted
- Steps progress: 4 pași (Descărcat / On-page / Tehnic / Plan) cu dot animat pe cel activ

---

## 13. Componente transversale

### Toast notifications
- Poziție: bottom-center, z-index 50
- Variante: success (verde), error (roșu), info (neutral)
- Auto-dismiss 4s, tranziție slide-up

### Mobile Action Bar
- Ascuns pe desktop, vizibil ≤768px
- Fixed bottom, padding safe-area
- 2 butoane full-width: "Trimite raportul" (primary) + "Ajutor" (secondary)

---

## 14. Responsivitate

| Breakpoint | Modificări principale |
|---|---|
| ≤1200px | Container se îngustează, preview hero devine 100% lărgime |
| ≤1024px | Workspace stat cards: 2×2, checks grid: 2 coloane |
| ≤768px | Nav header ascuns, hero form full-width, workspace tabs scroll horizontal, share grid stivuit |
| ≤480px | Hero H1 la min (38px), form buton full-width pe rând nou |

---

## 15. Fișiere de modificat

### CSS — înlocuire și extindere
- `app/public/style.css` → **de eliminat complet** după migrare
- `app/public/assets/tokens.css` → actualizat cu tokenii din această specificație
- `app/public/assets/base.css` → actualizat (body background, fonturi, reset)
- `app/public/assets/components.css` → butoane, inputs, pills, badges, status pills
- `app/public/assets/landing.css` → header, hero, toate secțiunile landing
- `app/public/assets/report.css` → workspace header, tabs, toate tab-urile
- `app/public/assets/states.css` → loading state, error state, empty state

### HTML Partials (fără modificări structurale mari)
- `partials/landing-header.php` — actualizat markup header
- `partials/landing-hero.php` — form card nou, eyebrow, trust chips, preview section
- `partials/landing-how.php` — how-grid 3 coloane
- `partials/landing-checks.php` — dark section 4 coloane
- `partials/landing-example.php` — split layout cu preview
- `partials/landing-faq.php` — 2 coloane + accordeon
- `partials/report-header.php` — header workspace nou

### JS (modificări minore)
- `assets/render-workspace.js` — actualizare clase CSS pentru noile componente
- `assets/render-landing.js` — animație loading steps, toggle tabs
- Fără modificări în `state.js`, `api.js`, `forms.js`

---

## 16. Principii de implementare

1. **`style.css` nu se atinge** până toate componentele sunt migrate în `assets/*.css` și testate
2. **Tokenii sunt sursa de adevăr** — nicio culoare sau dimensiune hard-coded în componentele noi
3. **HTML-ul existent se modifică minim** — schimbăm clase CSS, nu structuri DOM, pentru a nu rupe JS-ul
4. **Mobile-first** pe toate componentele noi
5. **Fiecare fișier CSS din assets/** are o responsabilitate clară (tokens / base / components / landing / report / states)
