# Audit SEO Gratuit - Documentatie completa pentru LLM

Acest document este scris ca un context autonom pentru un LLM, developer sau auditor care trebuie sa inteleaga aplicatia fara acces la cod. Nu contine secrete, date din storage sau valori reale din fisiere `.env`.

## 1. Rezumat executiv

Audit SEO Gratuit este un micro-app PHP NovaWeb care analizeaza un URL si genereaza un raport SEO on-page. Aplicatia este un funnel complet:

1. Userul introduce un URL si alege contextul de audit.
2. Backend-ul descarca sau randeaza HTML-ul paginii.
3. Motorul de scoring parseaza HTML-ul si calculeaza un scor 0-100.
4. Sistemul transforma rezultatul tehnic intr-un strat de impact business.
5. UI-ul afiseaza un preview public.
6. Raportul complet se deblocheaza prin email sau cerere de contact.
7. Dupa deblocare, userul poate primi raportul pe email, il poate retrimite si poate descarca PDF-ul.

Aplicatia nu este un PageSpeed clone. Nu masoara Core Web Vitals reali si nu ruleaza Lighthouse. Scorul acopera in principal SEO on-page, indexare, metadata, structura continutului, semnale detectabile in HTML si, optional, SEO local.

## 2. Utilizatori si promisiune produs

Userul tinta este un proprietar de site, marketer sau manager care vrea sa inteleaga rapid ce blocheaza pagina din perspectiva SEO on-page.

Produsul promite:

- audit gratuit fara cont obligatoriu;
- scor 0-100;
- categorii clare de probleme;
- recomandari prioritizate;
- raport complet pe email;
- PDF descarcabil dupa deblocare;
- optiune de a cere ajutor NovaWeb pentru implementare.

Produsul nu promite:

- masurare viteza reala / Core Web Vitals;
- analiza backlink-uri;
- analiza SERP live;
- crawl complet de site;
- audit juridic sau GDPR complet.

## 3. Stack tehnic

Backend:

- PHP 8.x, fara framework.
- Composer:
  - `vlucas/phpdotenv` pentru `.env`;
  - `phpmailer/phpmailer` pentru SMTP.
- Front controller in `app/public/index.php`.
- Servicii de domeniu in `app/src`.
- Stocare file-based JSON in `app/storage`.

Frontend:

- HTML server-rendered.
- JavaScript vanilla ES modules.
- CSS custom.
- Nu exista framework frontend.
- UI-ul principal este impartit intre `view-home.php`, partials PHP si module JS.

Runtime:

- Local se poate rula cu serverul built-in PHP.
- In productie, root `index.php` include front controller-ul din `app/public`.
- `.htaccess` blocheaza accesul public direct la `app/`, cu exceptia asset-urilor publice.

## 4. Concepte cheie

### 4.1 Context de audit

Exista doua contexte:

- `article`: audit standard pentru articol, pagina informationala sau pagina generala.
- `local`: audit pentru pagina care tinteste intentie locala, oras, locatie, Google Maps sau Local Pack.

Contextul nu este doar text de UI. In `local`, aplicatia include verificari locale extinse si scorul final combina scorul global cu scorul local.

### 4.2 Preview public vs raport complet

Fiecare raport are doua forme:

- `locked=true`: preview public, folosit imediat dupa audit;
- `locked=false`: raport complet, disponibil dupa deblocare.

Preview-ul poate arata:

- scor total;
- breakdown pe categorii;
- numar de verificari OK / warning / fail;
- probleme mascate generic;
- CTA pentru email sau contact.

Raportul complet arata:

- toate check-urile;
- label real pentru probleme;
- dovada detectata;
- regula;
- recomandarea de remediere;
- impact business;
- plan de actiune;
- PDF descarcabil.

Masca din preview este intentionata si face parte din funnel. Nu trebuie tratata ca bug.

### 4.3 Report token

Fiecare snapshot de raport este salvat cu un `report_token` hex de 32 caractere. Tokenul este folosit pentru:

- rehidratarea raportului prin URL;
- trimiterea raportului pe email;
- generarea linkului de share;
- deblocarea raportului;
- descarcarea PDF-ului;
- atasarea contextului la lead.

URL-ul de raport are forma:

```text
?report={report_token}#workspaceShell
```

Emailul/PDF-ul pot include si un access token semnat:

```text
?report={report_token}&access={hmac}#workspaceShell
```

## 5. Arhitectura la nivel inalt

Flux principal:

```text
Browser
  -> GET /
  -> view-home.php + partials + assets
  -> window.NOVA_AUDIT cu URL-uri API
  -> POST /api/score
  -> RenderService obtine HTML
  -> ArticleScorer calculeaza scorul
  -> BusinessImpactCalculator construieste impactul business
  -> ReportStore salveaza snapshot
  -> ReportPayload::public() mascheaza problemele
  -> UI randeaza workspace-ul
```

Flux unlock/email:

```text
Browser
  -> POST /api/email-report
  -> valideaza consent, email, reCAPTCHA si rate limit
  -> ReportStore recupereaza snapshot
  -> MailService trimite raportul
  -> LeadRepository salveaza lead tranzactional
  -> SiteAuditAccess creeaza grant pentru site/email
  -> ReportAccess seteaza cookie de unlock
  -> ReportPayload::full() intoarce raport complet
```

Flux lead/contact:

```text
Browser
  -> POST /api/lead-request
  -> honeypot + consent + reCAPTCHA + rate limit
  -> snapshot dupa token sau fallback url/context
  -> LeadRepository salveaza JSON
  -> optional MailService trimite notificare interna
  -> optional NewsletterService adauga lead in WordPress
  -> optional unlock pentru raportul atasat
```

## 6. Puncte de intrare

### Root `index.php`

Este wrapper-ul public din radacina proiectului. Seteaza `DOCUMENT_ROOT` catre `app/public` si include front controller-ul real.

### `app/public/index.php`

Este front controller-ul principal. Face routing pentru:

- `GET /`;
- `GET /api/report`;
- `GET /api/report-pdf`;
- `POST /api/render`;
- `POST /api/score`;
- `POST /api/email-report`;
- `POST /api/lead-request`.

Rutele cunoscute au metoda stricta. O metoda gresita intoarce `405 method_not_allowed`.

### `app/public/view-home.php`

Construieste shell-ul HTML, include partials si publica obiectul `window.NOVA_AUDIT`.

Campuri importante in `window.NOVA_AUDIT`:

- `toolUrl`;
- `scoreUrl`;
- `emailUrl`;
- `reportUrl`;
- `leadUrl`;
- `pdfUrl`;
- `serviceCatalog`;
- `consultant`;
- `initialMode`;
- `initialReportToken`;
- `initialReportUnlocked`;
- `initialSessionUnlocked`;
- `servicesUrl`;
- `recaptchaSiteKey`;
- `cspNonce`.

### `router.local.php`

Router recomandat pentru rulare locala din radacina repo-ului:

```powershell
php -S 127.0.0.1:8080 -t . router.local.php
```

### `app/public/router.php`

Router pentru serverul PHP built-in cand document root este `app/public`. Este util pentru smoke tests, dar nu este varianta preferata pentru UI complet.

### `app/api/render.php`

Endpoint legacy dezactivat. Raspunde `410 legacy_endpoint_disabled`. Nu trebuie folosit de fluxul live.

## 7. Frontend: fisiere si responsabilitati

### `audit-app.js`

Este orchestratorul frontend principal. Responsabilitati:

- construieste starea initiala;
- colecteaza referintele DOM;
- leaga evenimentele;
- valideaza URL-ul;
- porneste auditul;
- gestioneaza progresul;
- restaureaza raportul din `?report=...`;
- gestioneaza unlock prin email;
- gestioneaza lead/contact forms;
- gestioneaza reCAPTCHA;
- controleaza modale, drawer, toast, tabs;
- sincronizeaza istoricul URL;
- gestioneaza limita de rapoarte pe site.

Stari importante:

- `mode`: `landing` sau `workspace`;
- `currentContext`: `article` sau `local`;
- `pendingUrl`;
- `currentReport`;
- `activeTab`;
- `reportUnlocked`;
- `siteAccess`;
- `lastUnlockEmail`;
- `issueFilter`;
- `technicalSearch`;
- `showOnlyProblems`;
- `emailSent`;
- `leadSent`;
- `leadSource`;
- `emailSourceOverride`;
- `rehydrated`.

### `api.js`

Wrapper mic pentru fetch:

- `normalizeUrl()`;
- `requestAudit()`;
- `requestReport()`;
- `sendReportEmail()`;
- `sendLeadRequest()`;
- `copyText()`.

### `state.js`

Defineste:

- taburile disponibile: `overview`, `issues`, `plan`, `technical`, `share`;
- mesajele progresului;
- grupurile de verificari tehnice;
- structura de state initial.

### `render-workspace.js`

Randeaza raportul in workspace:

- hero scor;
- breakdown pe categorii;
- preview blocat;
- raport complet;
- formular email;
- resend email;
- detalii tehnice;
- skeleton loading;
- carduri de eroare/stare;
- animatii de aparitie si bare de scor.

### `report-normalize.js`

Transforma raportul in modele utile pentru UI:

- scor total;
- verdict textual;
- categorii de display;
- top issues;
- quick wins;
- summary executiv;
- summary tehnic;
- categorii tehnice;
- actiuni grupate pe bucket.

### `forms.js`

Helperi pentru:

- validare email;
- validare telefon;
- feedback inline;
- link share;
- mesaj lead precompletat.

### `drawers.js`

Controleaza drawer-ul de ajutor/contact.

### `render-landing.js`

Controleaza toggle-ul de context si suprafetele `landing` / `workspace`.

### `cmp.js`

Controleaza consent management pentru cookies/tracking. GA4 si Meta Pixel sunt incarcate doar prin consimtamant.

## 8. Backend: clase si roluri

### `Config`

Centralizeaza:

- storage path;
- base URL / base path;
- asset URL cu cache busting;
- titlu si descriere SEO;
- catalog servicii;
- consultant;
- testimoniale;
- FAQ;
- chei reCAPTCHA;
- email notificare lead.

### `UrlSafety`

Normalizeaza si valideaza URL-urile analizate. Protectii:

- doar `http` si `https`;
- porturi standard 80/443;
- blocheaza localhost, `.local`, `.internal`, hosturi invalide;
- rezolva DNS si permite doar IP-uri publice;
- valideaza redirect-uri;
- previne SSRF catre IP-uri private sau rezervate.

### `RenderService`

Obtine HTML-ul paginii analizate.

Ordine:

1. Daca Cloudflare Browser Rendering este activ si configurat, incearca randare JS.
2. Daca Cloudflare esueaza sau este dezactivat, cade pe fetch HTTP simplu.

Returneaza:

- `html`;
- `source`: `cloudflare` sau `http`;
- `js`: `true` cand s-a folosit rendering JS.

### `CloudflareClient`

Client pentru Cloudflare Browser Rendering. Face pana la 3 incercari cu strategii diferite:

- `domcontentloaded` + timeout;
- `load` + timeout;
- `networkidle` + timeout.

### `HttpClient`

Client HTTP cu cURL:

- fetch raw;
- fetch raw limitat la `HTTP_MAX_BYTES`;
- redirect-uri controlate;
- DNS pinning prin `CURLOPT_RESOLVE`;
- User-Agent `Novaweb-SEO-Checker/1.0`;
- HEAD Last-Modified;
- heuristica pentru pagini JS-heavy.

### `ArticleScorer`

Motorul principal de analiza SEO. Parseaza HTML cu `DOMDocument` si `DOMXPath`.

Intoarce:

- `total`;
- `total_global`;
- `total_local`;
- `breakdown`;
- `local`;
- `checks`;
- `meta`;
- `gatekeeper`;
- `context`.

### `Advice`

Sursa de adevar pentru definitiile check-urilor. Pentru fiecare check defineste:

- `label`;
- `rule`;
- `tip`;
- `business_impact_text`;
- `business_impact_magnitude`;
- `related_service`;
- `fix_complexity`.

### `BusinessImpactCalculator`

Transforma scorul tehnic in business model:

- `opportunity_score`;
- `lead_segment`;
- `recommended_service`;
- `top_business_issues`;
- `action_plan`.

### `ReportStore`

Salveaza snapshot-uri JSON in `storage/reports`. Tokenul este hex de 32 caractere. Retentia default este 30 zile.

### `ReportPayload`

Construieste forma publica sau completa a raportului.

`full()`:

- imbogateste check-urile cu Advice;
- elimina check-urile locale daca raportul nu este `local`;
- seteaza `locked=false`;
- calculeaza counts.

`public()`:

- seteaza `locked=true`;
- pastreaza scorurile si breakdown-ul;
- mascheaza problemele;
- mascheaza top business issues si action plan;
- expune counts si numarul de probleme blocate.

### `ReportAccess`

Controleaza deblocarea pe raport:

- cookie semnat per `report_token`;
- access token HMAC pentru linkuri din email/PDF;
- TTL cookie de 30 zile;
- cookie HttpOnly, SameSite=Lax, Secure cand request-ul este HTTPS.

Deblocarea pe sesiune globala este intentionat dezactivata. Fiecare raport trebuie deblocat pe token.

### `SiteAuditAccess`

Controleaza grant-ul pe site/email si limita de rapoarte complete.

Scop:

- dupa ce un user introduce emailul, poate primi un numar limitat de rapoarte complete pentru acelasi site;
- limiteaza abuzul pe acelasi host;
- permite retrimiterea ultimului raport fara consumarea unei noi rulari.

Default:

- `SITE_AUDIT_RUN_LIMIT=3`;
- `SITE_AUDIT_WINDOW_SECONDS=86400`.

Status public expus:

- `active`;
- `site_key`;
- `runs_used`;
- `run_limit`;
- `remaining_runs`;
- `exhausted`;
- `last_report_token`;
- `email_mask`;
- `can_resend`;
- `resets_at`;
- `seconds_until_reset`;
- optional `auto_unlocked`, `counted`, `allowed`, `error`.

### `RateLimiter`

Rate limit file-based per zi UTC. Fiecare cheie devine un fisier `.cnt` in `storage/ratelimit`.

Este folosit pentru:

- scor pe IP;
- scor pe site;
- email pe IP/adresa/site;
- lead pe IP/adresa/site.

### `LeadRepository`

Salveaza lead-uri JSON in `storage/leads`. Valideaza telefon:

- mobil RO;
- fallback international E.164-ish.

Retentie default: 180 zile.

### `MailService`

Trimite email prin SMTP cu PHPMailer. Folosit pentru:

- raport pe email;
- notificare interna lead.

### `EmailRenderer`

Construieste HTML/text pentru:

- raport SEO complet;
- notificare interna lead.

Emailul raportului include:

- scor;
- verdict;
- categorii;
- probleme si recomandari;
- pasi de remediere;
- link raport complet;
- CTA service recomandat.

### `ReportPdfRenderer`

Construieste PDF-ul complet cu `SimplePdfDocument`.

PDF-ul include:

- header raport;
- scor si verdict;
- scoruri pe categorii;
- tabel probleme/recomandari;
- tabel pasi de remediere;
- link raport complet.

PDF-ul este disponibil doar dupa unlock.

### `NewsletterService` si `WordpressClient`

Integrare cu endpoint WordPress custom pentru subscribe.

Liste posibile:

- lead-uri generale;
- sfaturi de remediere SEO;
- newsletter.

Newsletter-ul si sfaturile de remediere necesita consimtamant explicit. Raportul tranzactional pe email nu inseamna abonare automata la marketing.

## 9. Endpoint-uri API

Toate raspunsurile JSON au:

- `Content-Type: application/json; charset=utf-8`;
- `Cache-Control: no-store`;
- `X-Robots-Tag: noindex`.

### 9.1 `GET /`

Returneaza HTML-ul aplicatiei.

### 9.2 `GET /api/report?token=...&access=...`

Recupereaza un raport existent.

Input:

- `token`: required, hex 32;
- `access`: optional, HMAC pentru unlock din email.

Raspunsuri:

- `200`: payload public sau full;
- `404`: `{"error":"report_not_found"}`.

Logica:

- daca tokenul nu exista, 404;
- daca raportul este deblocat prin cookie sau access token, intoarce full;
- altfel intoarce preview public.

### 9.3 `GET /api/report-pdf?token=...&access=...`

Returneaza PDF-ul raportului.

Raspunsuri:

- `200 application/pdf`: PDF download;
- `403 text/plain`: raportul exista, dar nu este deblocat;
- `404 JSON`: raportul nu exista.

`403` inainte de unlock este comportament corect.

### 9.4 `POST /api/render`

Endpoint diagnostic pentru randarea HTML.

Input:

```json
{
  "url": "https://example.com/page"
}
```

Raspuns:

```json
{
  "html_source": "http|cloudflare",
  "bytes": 12345,
  "snippet": "text extras din pagina"
}
```

Nu este folosit de fluxul principal din UI.

### 9.5 `POST /api/score`

Endpoint principal pentru audit.

Input minim:

```json
{
  "url": "https://example.com/page",
  "context": "article"
}
```

Input optional:

```json
{
  "url": "https://example.com/page",
  "context": "local",
  "fresh": true
}
```

Validari:

- URL normalizat si permis de `UrlSafety`;
- context strict `article` sau `local`;
- site limit verificat inainte de audit;
- rate limit pe IP;
- rate limit pe site.

Raspuns succes, in mod normal preview:

```json
{
  "ok": true,
  "url_analyzed": "https://example.com/page",
  "source": "http",
  "js": false,
  "context": "article",
  "score": {
    "total": 78,
    "total_global": 78,
    "total_local": 0,
    "breakdown": {
      "content": 30,
      "structure": 22,
      "signals": 16,
      "locale": 10
    },
    "local": {
      "points": 0,
      "max": 30,
      "percent": 0
    },
    "checks": [],
    "report_token": "..."
  },
  "business": {},
  "created_at": "2026-04-24T...",
  "report_token": "...",
  "locked": true
}
```

Erori importante:

- `400 URL invalid`;
- `400 invalid_context`;
- `429 rate_limited`;
- `429 site_audit_limit_reached`;
- payload cu `error=render_failed` cand pagina nu poate fi analizata.

### 9.6 `POST /api/email-report`

Trimite raportul pe email si deblocheaza raportul.

Input tipic:

```json
{
  "report_token": "hex32",
  "url": "https://example.com/page",
  "context": "article",
  "email": "user@example.com",
  "first_name": "",
  "wants_plan": false,
  "newsletter_optin": false,
  "consent_terms": true,
  "recaptcha_token": "...",
  "recaptcha_action": "email_report",
  "source": "inline_unlock"
}
```

Reguli:

- `consent_terms=true` este obligatoriu;
- email valid este obligatoriu, cu exceptia cazului de resend unde poate folosi email salvat in grant;
- reCAPTCHA este obligatoriu in productie;
- `wants_plan` absent inseamna `false`;
- `newsletter_optin` absent inseamna `false`;
- raportul poate fi rezolvat din `report_token` sau reconstruit din `url` + `context`;
- daca limita pe site este atinsa pentru un raport nou, raspunde `site_audit_limit_reached`.

Raspuns succes:

```json
{
  "ok": true,
  "report_token": "hex32",
  "unlocked": true,
  "report": {},
  "site_access": {},
  "stored_email_used": false,
  "mailpoet_lead": {},
  "plan_subscribed": false,
  "plan": {},
  "newsletter": {},
  "lead_saved": true
}
```

Erori:

- `invalid_consent`;
- `rate_limited`;
- `captcha_failed`;
- `report_not_found`;
- `invalid_email`;
- `site_audit_limit_reached`;
- `email_failed`;
- `payload_too_large`;
- `invalid_json`.

### 9.7 `POST /api/lead-request`

Salveaza o cerere de ajutor/contact.

Input tipic:

```json
{
  "report_token": "hex32",
  "source": "workspace_help",
  "name": "Nume",
  "phone": "0712345678",
  "email": "user@example.com",
  "message": "Vreau ajutor cu raportul.",
  "urgent": false,
  "wants_plan": false,
  "newsletter_optin": false,
  "consent_terms": true,
  "recaptcha_token": "...",
  "recaptcha_action": "lead_request",
  "website": ""
}
```

Reguli:

- `website` este honeypot; daca nu este gol, raspunsul este `ok=true, spam=true`;
- `consent_terms=true` este obligatoriu;
- reCAPTCHA este obligatoriu in productie;
- `name`, `email`, `message` sunt obligatorii;
- telefonul, daca exista, trebuie sa treaca validarea;
- poate functiona cu `report_token` sau cu `url` + `context`.

Raspuns succes:

```json
{
  "ok": true,
  "message": "Multumim...",
  "unlocked": true,
  "mailpoet_lead": {},
  "report": {}
}
```

Erori:

- `invalid_consent`;
- `rate_limited`;
- `captcha_failed`;
- `invalid_contact`;
- `report_not_found` este evitat prin fallback la contact-only cand nu exista raport.

## 10. Scoring model

### 10.1 Scor global

Scorul global are maxim 100 puncte:

```text
Content & UX:                 40
Structura & Indexare:         25
Metadate & Rich Snippets:     20
Localizare RO:                15
Total global:                100
```

### 10.2 Content & UX

Check-uri si ponderi:

- `word_count_800`: 10;
- `intro_mentions_topic`: 4;
- `h1_single`: 6;
- `headings_hierarchy`: 6;
- `lists_tables`: 2;
- `images_in_body`: 3;
- `img_alt_ratio_80`: 3;
- `lazyload_images`: 2;
- `date_published`: 1;
- `date_modified`: 1;
- `author_visible_or_schema`: 4.

### 10.3 Structura & Indexare

- `indexable`: 8;
- `canonical_present`: 5;
- `canonical_valid`: 5;
- `url_clean`: 4;
- `internal_links_present`: 2;
- `external_links_present`: 1.

Check-uri informative moderne, weight 0:

- `html_valid`;
- `meta_robots_ok`;
- `image_dimensions_defined`;
- `fonts_preload`;
- `cls_risky_elements`.

### 10.4 Metadate & Rich Snippets

- `title_length_ok`: 6;
- `meta_description_ok`: 4;
- `og_minimal`: 4;
- `schema_article_recommended`: 6.

Check-uri informative, weight 0:

- `faq_schema_present`;
- `schema_breadcrumbs`;
- `schema_image_required_fields`.

### 10.5 Localizare RO

- `lang_ro`: 7;
- `og_locale_or_inLanguage_ro`: 4;
- `date_format_ro`: 3;
- `hreflang_pairs`: 1.

### 10.6 SEO local extins

Are `LOCAL_MAX=30`. Se foloseste doar ca influenta asupra scorului final cand contextul este `local`.

Check-uri locale ponderate:

- `local_tel_click`: 2;
- `local_tel_prefix_local`: 2;
- `local_address_visible`: 2;
- `local_directions_link`: 1;
- `local_opening_hours`: 1;
- `local_schema_localbusiness`: 3;
- `local_schema_postal`: 2;
- `local_schema_geo`: 1;
- `local_schema_sameas`: 1;
- `local_schema_rating`: 1;
- `local_city_detected`: 2;
- `local_city_in_title`: 2;
- `local_city_in_h1`: 2;
- `local_city_in_slug`: 2;
- `local_city_in_intro`: 2;
- `local_map_embed`: 1;
- `local_alt_has_city`: 1;
- `local_locator`: 1;
- `local_whatsapp`: 1.

Check-uri locale extra, prezente in raport, dar fara pondere separata in `W_LOCAL`:

- `local_schema_tel`;
- `local_schema_area`.

### 10.7 Formula scorului final

Pentru `article`:

```text
total = total_global
```

Pentru `local`:

```text
local_percent = round(local_points / 30 * 100)
total = round(0.8 * total_global + 0.2 * local_percent)
```

### 10.8 Severitati

Fiecare check are `sev`:

- `pass`: check reusit;
- `warn`: avertisment;
- `fail`: problema.

Reguli:

- un check OK este mereu `pass`;
- check-urile `local_*` esuate sunt `fail` in context `local`;
- check-urile `local_*` esuate sunt `warn` in context `article`;
- anumite check-uri non-critice esuate sunt `warn`, de exemplu title/meta/date/schema/fonts/CLS/breadcrumbs/html/hreflang;
- restul check-urilor esuate sunt `fail`.

### 10.9 Detectii principale

Motorul extrage:

- text vizibil;
- numar de cuvinte;
- title;
- meta description;
- H1;
- meta robots;
- canonical;
- `html lang`;
- Open Graph;
- JSON-LD;
- Article/BlogPosting/NewsArticle schema;
- date publicare/modificare;
- autor vizibil sau in schema;
- liste/tabele in continut;
- imagini relevante si ALT;
- lazy loading;
- dimensiuni imagini;
- font preload;
- linkuri interne/externe;
- hreflang;
- luni romanesti in continut;
- LocalBusiness schema;
- telefon click-to-call;
- adresa vizibila;
- link Google Maps/Waze/Apple Maps;
- program;
- harta embed;
- oras detectat din titlu, H1, URL si introducere;
- WhatsApp;
- locator/pagini de locatii.

## 11. Model de raport

Snapshot-ul complet contine:

```json
{
  "ok": true,
  "url_analyzed": "https://example.com/page",
  "source": "http|cloudflare",
  "js": false,
  "context": "article|local",
  "score": {},
  "business": {},
  "created_at": "ISO-8601",
  "report_token": "hex32",
  "locked": false
}
```

`score` contine:

```json
{
  "total": 0,
  "total_global": 0,
  "total_local": 0,
  "breakdown": {
    "content": 0,
    "structure": 0,
    "signals": 0,
    "locale": 0
  },
  "local": {
    "points": 0,
    "max": 30,
    "percent": 0
  },
  "checks": [],
  "meta": {
    "title": "",
    "description": "",
    "h1": ""
  },
  "context": "article|local",
  "report_token": "hex32",
  "checks_total": 0,
  "checks_preview_count": 0,
  "checks_locked_count": 0,
  "checks_counts": {
    "pass": 0,
    "warn": 0,
    "fail": 0,
    "priority": 0
  }
}
```

Un check complet contine:

```json
{
  "id": "title_length_ok",
  "ok": false,
  "note": "dovada detectata",
  "sev": "warn",
  "label": "Title optimizat",
  "rule": "Title intre aproximativ 35 si 65 de caractere.",
  "tip": "Rescrie title-ul...",
  "business_impact_text": "...",
  "business_impact_magnitude": "high|medium|low",
  "related_service": "seo_optimization|content|local_seo|technical|website_redesign|premium_audit",
  "fix_complexity": "easy|medium|hard"
}
```

Un check mascat in preview poate avea:

```json
{
  "id": "title_length_ok",
  "ok": false,
  "sev": "warn",
  "label": "Problema ascunsa in preview",
  "locked": true,
  "locked_copy": "Introdu email-ul pentru a vedea problema exacta si pasii de rezolvare."
}
```

`business` complet contine:

```json
{
  "opportunity_score": 0,
  "estimated_monthly_loss": null,
  "estimated_monthly_loss_detail": {
    "formatted": null,
    "min": null,
    "max": null,
    "disclaimer": null
  },
  "lead_segment": "unknown",
  "recommended_service": {},
  "top_business_issues": [],
  "action_plan": []
}
```

`opportunity_score` este calculat aproximativ:

```text
round(min(100, (100 - total_global) * 0.456))
```

Top issues sunt primele 5 probleme ordonate dupa:

1. impact business (`high`, `medium`, `low`);
2. severitate (`fail`, `warn`, `pass`);
3. label.

Action plan-ul este derivat din top issues. Durata estimata:

- `easy`: aproximativ 1 ora;
- `medium`: aproximativ 1 zi;
- `hard`: aproximativ 1 saptamana.

Owner estimat:

- `easy`: user/editor;
- `medium`: developer sau marketer;
- `hard`: echipa tehnica/agentie.

## 12. Fluxuri UI detaliate

### 12.1 Landing

La `GET /`, aplicatia porneste in `landing`, cu:

- header;
- hero;
- toggle context `article` / `local`;
- formular URL;
- sectiuni educative;
- FAQ;
- contact/report section;
- CMP;
- modale pregatite dar ascunse.

### 12.2 Submit audit

1. Userul introduce URL.
2. Frontend-ul normalizeaza URL-ul. Daca lipseste schema, adauga `https://`.
3. Daca URL-ul nu este valid, arata feedback inline.
4. Seteaza `pendingUrl`.
5. Trimite `POST /api/score`.
6. Activeaza workspace si loader.
7. Timeout frontend: 30 secunde.
8. La succes, salveaza `currentReport`, context, site access si token.
9. Randeaza raportul.
10. Sincronizeaza URL-ul cu `?report=token#workspaceShell`.

### 12.3 Restaurare raport

Daca pagina se incarca cu `?report=token`, frontend-ul:

1. porneste in workspace;
2. arata loading;
3. trimite `GET /api/report?token=...`;
4. randeaza public sau full in functie de unlock;
5. seteaza `rehydrated=true`.

### 12.4 Trimitere raport pe email

Frontend-ul:

1. valideaza raport existent;
2. valideaza email;
3. valideaza acceptare termeni;
4. cere reCAPTCHA token;
5. daca `wants_plan` nu este bifat, afiseaza modal optional pentru sfaturi de remediere;
6. trimite `POST /api/email-report`;
7. aplica raportul returnat;
8. marcheaza `reportUnlocked=true` daca backend-ul a deblocat;
9. rerandeaza workspace;
10. arata toast.

### 12.5 Retrimitere raport

Daca raportul este deblocat si exista email cunoscut sau `siteAccess.can_resend`, butonul principal trimite din nou raportul fara audit nou.

### 12.6 Download PDF

1. Daca raportul nu este deblocat, UI duce userul la formularul de email.
2. Daca raportul este deblocat, browserul navigheaza la `/api/report-pdf?token=...`.

### 12.7 Lead/help drawer

CTA-urile `data-open-help` deschid formularul de contact sau deruleaza la formularul inline. Mesajul este precompletat cu:

- URL analizat;
- context;
- scor;
- problema principala sau focusul CTA.

### 12.8 Limita pe site

Daca backend-ul raspunde `site_audit_limit_reached`, frontend-ul:

- incearca sa pastreze raportul anterior daca exista;
- altfel incearca sa restaureze ultimul raport din `site_access.last_report_token` sau din sessionStorage;
- arata modalul de limita;
- ofera CTA de verificare manuala.

## 13. Formulare si consimtamant

### Email raport

Campuri:

- email;
- first_name optional;
- wants_plan optional;
- newsletter_optin optional;
- consent_terms obligatoriu;
- source.

Semantica:

- trimiterea raportului este tranzactionala;
- `wants_plan=true` inseamna opt-in pentru sfaturi de remediere SEO;
- `newsletter_optin=true` inseamna opt-in separat pentru newsletter;
- niciun opt-in de marketing nu este implicit.

### Lead request

Campuri:

- name;
- phone;
- email;
- message;
- urgent;
- newsletter_optin;
- consent_terms;
- honeypot `website`;
- source.

### Report contact inline

Este formular de contact mai simplu:

- name;
- email;
- message;
- consent_terms;
- honeypot.

Trimite tot catre `/api/lead-request`.

## 14. reCAPTCHA

Frontend-ul incarca Google reCAPTCHA v3 daca exista `recaptchaSiteKey`.

Actiuni suportate:

- `email_report`;
- `lead_request`;
- `report_contact`.

Backend-ul verifica:

- secret configurat;
- token prezent;
- request catre Google siteverify;
- score minim `RECAPTCHA_MIN_SCORE`, default 0.5;
- action match.

Exceptie:

- daca `RECAPTCHA_SECRET_KEY` lipseste si `APP_ENV=local`, verificarea este sarita.
- in productie, lipsa secretului inseamna `captcha_failed`.

## 15. Analytics si CMP

Aplicatia poate folosi:

- GA4 prin `GA4_ID`;
- Meta Pixel prin `FB_PIXEL_ID`.

Tracking-ul este controlat de CMP. Evenimentele frontend sunt non-PII si nu trebuie sa includa:

- email;
- nume;
- telefon;
- URL complet analizat;
- `report_token`;
- date sensibile din raport.

Evenimente cunoscute:

- `tool_view`;
- `audit_submit`;
- `audit_success`;
- `audit_fail`;
- `report_gate_view`;
- `email_report_submit`;
- `email_report_success`;
- `report_unlock_success`;
- `pdf_download_click`;
- `cta_service_click`;
- `lead_submit_success`;
- `contact_form_submit`;
- `form_validation_error`;
- `recaptcha_error`;
- `plan_prompt_view`;
- `plan_prompt_accept`;
- `plan_prompt_decline`;
- `plan_optin_checked`;
- `newsletter_optin_checked`.

## 16. Storage

Directorul default este `app/storage`, sau `STORAGE_DIR` daca este configurat.

Subdirectoare importante:

- `cache`: cache audituri;
- `reports`: snapshot-uri JSON cu rapoarte;
- `leads`: lead-uri JSON;
- `ratelimit`: contoare rate limit;
- `site-audit-access`: grant-uri pe site/email;
- `site-audit-access/index`: indexuri pentru grant-uri;
- `logs`: loguri de integrare newsletter;
- `_archive`: arhive istorice;
- `ro_localities.json`: baza pentru detectie de localitati romanesti.

Fisiere de chei generate daca lipsesc:

- `report-access.key`;
- `site-audit-access.key`.

Nu se expun niciodata continutul real din storage, rapoarte, lead-uri sau chei.

## 17. Variabile de mediu

### App / URL

- `APP_BASE`: prefix public, default `/tools/audit-seo-gratuit`;
- `APP_ORIGIN`: origin public;
- `APP_URL`: fallback pentru origin;
- `APP_ENV`: `local` permite skip reCAPTCHA daca lipseste secretul;
- `APP_ENV_FILE`: poate forta `.env.dev`.

### Storage / body

- `STORAGE_DIR`;
- `MAX_JSON_BODY_BYTES`, default 65536.

### Render / audit

- `ENABLE_CF_RENDER`, default 0;
- `CF_ACCOUNT_ID`;
- `CF_API_TOKEN`;
- `HTTP_MAX_BYTES`, default 5242880;
- `CACHE_TTL`, default 900.

### Rate limit

- `RATE_LIMIT_SCORE`, default 200;
- `RATE_LIMIT_EMAIL`, default 3;
- `RATE_LIMIT_LEAD`, default 3;
- `RATE_LIMIT_RETENTION_DAYS`, default 2.

### Site audit access

- `SITE_AUDIT_RUN_LIMIT`, default 3;
- `SITE_AUDIT_WINDOW_SECONDS`, default 86400;
- `SITE_AUDIT_ACCESS_KEY`;
- `REPORT_ACCESS_KEY`.

### Retentie

- `REPORT_RETENTION_DAYS`, default 30;
- `LEAD_RETENTION_DAYS`, default 180;
- `LOG_RETENTION_DAYS`, default 14.

### SMTP

- `SMTP_HOST`;
- `SMTP_PORT`;
- `SMTP_SECURE`;
- `SMTP_USER`;
- `SMTP_PASS`;
- `MAIL_FROM`;
- `MAIL_FROM_NAME`;
- `MAIL_REPLY_TO`;
- `MAIL_REPLY_TO_NAME`.

### WordPress / MailPoet

- `WP_SUBSCRIBE_URL`;
- `WP_SUBSCRIBE_TOKEN`;
- `WP_AUDIT_LIST_NAME`;
- `WP_LIST_NAME`;
- `WP_PLAN_LIST_NAME`;
- `WP_LEADS_LIST_NAME`;
- `WP_INSECURE`.

### reCAPTCHA

- `RECAPTCHA_SITE_KEY`;
- `RECAPTCHA_SECRET_KEY`;
- `RECAPTCHA_MIN_SCORE`.

### Proxy / IP

- `TRUST_CF_CONNECTING_IP`: daca 1, foloseste `CF_CONNECTING_IP`.

### Analytics

- `GA4_ID`;
- `FB_PIXEL_ID`.

### Lead notify

- `LEAD_NOTIFY_EMAIL`.

## 18. Securitate

Protectii relevante:

- CSP cu nonce pentru scripturi;
- `X-Content-Type-Options: nosniff`;
- `Referrer-Policy: strict-origin-when-cross-origin`;
- `Permissions-Policy` restrictiv;
- HSTS cand request-ul este HTTPS;
- JSON API no-store si noindex;
- `.htaccess` blocheaza fisiere dot, env, app intern, docs, workflow, tools, logs;
- URL safety anti-SSRF;
- reCAPTCHA pentru email/lead/contact;
- honeypot pentru lead;
- rate limit file-based;
- unlock cookie semnat HMAC;
- site access cookie semnat HMAC;
- tokenuri de raport validate strict hex32;
- max JSON body size;
- SMTP prin PHPMailer;
- WordPress subscribe cu token custom.

Riscuri si atentie:

- storage contine date personale si trebuie protejat;
- logurile newsletter pot contine metadata operationala;
- rapoartele pot contine URL-uri analizate si continut derivat;
- nu trebuie trimise date PII in analytics;
- nu trebuie hardcodate secrete.

## 19. Rulare locala

Comanda recomandata:

```powershell
php -S 127.0.0.1:8080 -t . router.local.php
```

URL:

```text
http://127.0.0.1:8080/
```

Config local minim in `app/.env.local`:

```dotenv
APP_ORIGIN=http://127.0.0.1:8080
APP_BASE=/
APP_ENV=local
ENABLE_CF_RENDER=0
HTTP_MAX_BYTES=5242880
```

Pentru email real trebuie configurat SMTP. Pentru WordPress/MailPoet trebuie configurat endpointul WordPress. Pentru reCAPTCHA real trebuie site key si secret.

## 20. Protocol de test pentru LLM

### 20.1 Consistenta check-uri

Ruleaza:

```powershell
php tools/check-consistency.php
```

Expected:

```text
Consistency check passed.
```

Acest tool verifica:

- check-uri din `ArticleScorer` fara definitie in `Advice`;
- campuri incomplete in Advice;
- definitii extra fata de check-urile reale.

### 20.2 Smoke test API score

```powershell
Invoke-RestMethod `
  -Method Post `
  -Uri http://127.0.0.1:8080/api/score `
  -ContentType 'application/json' `
  -Body '{"url":"https://example.com/","context":"article","fresh":true}'
```

Verifica:

- `ok=true`;
- `report_token` hex32;
- `score.total` intre 0 si 100;
- `locked=true` in preview;
- `score.checks_counts` exista sau poate fi derivat.

### 20.3 Restore report

```powershell
Invoke-RestMethod -Method Get -Uri "http://127.0.0.1:8080/api/report?token=TOKEN"
```

Expected:

- raport public daca nu exista unlock;
- raport full daca exista cookie/access token.

### 20.4 PDF inainte de unlock

```powershell
Invoke-WebRequest -Uri "http://127.0.0.1:8080/api/report-pdf?token=TOKEN"
```

Expected fara unlock:

- status 403.

Acesta este comportament corect.

### 20.5 Lead/contact local

Testabil fara reCAPTCHA doar daca `APP_ENV=local`. In productie lipsa secretului trebuie sa blocheze request-ul.

### 20.6 UI

Verifica manual:

- landing se incarca;
- toggle `article/local` schimba contextul;
- submit URL arata loader;
- workspace se randeaza;
- URL-ul devine `?report=...#workspaceShell`;
- refresh-ul rehidrateaza raportul;
- email form cere termeni;
- PDF cere unlock inainte de download;
- limita pe site pastreaza ultimul raport daca exista.

## 21. Reguli de modificare pentru viitoare LLM-uri

### Cand modifici scoring

Sincronizeaza obligatoriu:

- motorul de scoring;
- definitiile Advice;
- grupele din frontend;
- EmailRenderer;
- ReportPdfRenderer;
- testul `tools/check-consistency.php`.

Nu adauga un check nou fara:

- `id`;
- regula clara;
- mesaj de remediere;
- impact business;
- serviciu asociat;
- complexitate.

### Cand modifici unlock/email/PDF

Nu confunda:

- raport tranzactional pe email;
- opt-in sfaturi de remediere;
- newsletter marketing;
- lead request.

Sunt fluxuri diferite si consimtaminte diferite.

### Cand modifici frontend

Pastreaza ID-urile DOM folosite de `audit-app.js`, sau actualizeaza JS-ul in acelasi timp. ID-uri critice:

- `appRoot`;
- `landingSurface`;
- `landingEducation`;
- `workspaceShell`;
- `workspaceContent`;
- `workspaceState`;
- `scoreForm`;
- `urlInput`;
- `urlError`;
- `summaryStrip`;
- `overviewPanel`;
- `issuesPanel`;
- `planPanel`;
- `technicalPanel`;
- `emailReportForm`;
- `leadRequestForm`;
- `reportContactForm`;
- `downloadPdfButton`;
- `openSharePrimary`;
- `openHelpPrimary`;
- `reportLimitModal`;
- `planPromptModal`;
- `globalToast`.

### Cand modifici deploy/path

Verifica:

- `APP_BASE`;
- `APP_ORIGIN`;
- `Config::assetUrl`;
- `.htaccess`;
- server root;
- router local vs productie.

### Cand modifici privacy/tracking

Nu trimite PII in analytics. Respecta CMP. Actualizeaza politica publica daca se schimba:

- reCAPTCHA;
- categorii cookie;
- scopuri marketing;
- retentie date.

## 22. Limitari cunoscute

- Nu exista test suite completa automatizata.
- Scorul este heuristic, bazat pe HTML detectabil.
- Paginile foarte JS-heavy pot necesita Cloudflare Browser Rendering.
- `lead_segment` este in prezent `unknown` in calculul business.
- `estimated_monthly_loss` este null.
- `analysisCount()` este un proxy bazat pe numarul de fisiere din cache, nu o metrica business stricta.
- Email/MailPoet/reCAPTCHA depind de configurari externe.
- Limita pe site poate afecta testele repetitive pe acelasi domeniu.

## 23. Checklist rapid pentru intelegere fara cod

Un LLM a inteles aplicatia daca poate explica corect:

- diferenta dintre `article` si `local`;
- diferenta dintre preview blocat si raport complet;
- de ce PDF-ul cere unlock;
- ce face `report_token`;
- ce face `SiteAuditAccess`;
- cum se calculeaza scorul global;
- cum influenteaza local SEO scorul final;
- ce date cere email-report;
- diferenta dintre `wants_plan` si `newsletter_optin`;
- unde sunt salvate rapoartele si lead-urile;
- ce inseamna `site_audit_limit_reached`;
- de ce reCAPTCHA poate fi sarita doar local;
- de ce tracking-ul nu trebuie sa contina PII;
- cum se ruleaza local si cum se face smoke test.

## 24. Rezumat ultra-scurt

Audit SEO Gratuit este o aplicatie PHP fara framework care transforma un URL intr-un raport SEO on-page. Auditul produce un preview public blocat si un raport complet deblocat prin email/contact. Motorul de scoring verifica HTML-ul pentru continut, structura, indexare, metadata, localizare si, in context local, semnale LocalBusiness/Maps/oras. Backend-ul salveaza rapoarte JSON cu token, aplica rate limit si granturi pe site/email, trimite email prin SMTP, poate inscrie contacte in WordPress/MailPoet si genereaza PDF dupa unlock. Frontend-ul este vanilla JS si gestioneaza landing, workspace, rehidratare raport, email unlock, lead forms, modale, tabs, toast, reCAPTCHA si CMP.
