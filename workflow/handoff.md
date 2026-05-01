# Audit SEO Gratuit - Workflow / Handoff

## Status pre-launch 2026-04-23

- `view-home.php` ramane shell-ul HTML principal si defineste `window.NOVA_AUDIT`, dar markup-ul este impartit in partials, iar logica JS principala este in `app/public/assets/audit-app.js` si `app/public/assets/render-workspace.js`.
- `.htaccess` blocheaza public tot `app/`, cu allowlist doar pentru `app/public/assets/`. Root assets precum `favicon.ico`, `logo-audit.svg`, `raport-preview.png` si `raport-preview-og.png` raman servibile din root.
- `app/api/render.php` trebuie tratat ca legacy; in deploy Apache nu trebuie sa fie reachable, deoarece `^app/` este blocat inainte de regula pentru fisiere reale.
- Cloudflare Browser Rendering este dezactivat implicit. Se activeaza doar cu `ENABLE_CF_RENDER=1` plus `CF_ACCOUNT_ID` si `CF_API_TOKEN`.
- Fetch-ul HTML analizat foloseste `HTTP_MAX_BYTES` prin `HttpClient::fetchRawLimited()`, default `5242880`.
- API-urile folosesc JSON strict, `context` strict (`article` sau `local`) si raspuns `405 method_not_allowed` pentru metode gresite pe rutele cunoscute.
- In productie, `RECAPTCHA_SECRET_KEY` lipsa blocheaza `/api/lead-request`; skip-ul este permis doar cu `APP_ENV=local`.
- Opt-in-ul pentru sfaturi de remediere SEO este explicit: `wants_plan` absent sau fals inseamna ca userul primeste doar raportul/PDF tranzactional.
- Daca userul trimite raportul fara bifa de sfaturi, frontend-ul afiseaza un modal optional; `Da` seteaza doar `wants_plan=true`, iar `Nu` trimite doar raportul.
- Newsletter-ul ramane consimtamant separat prin `newsletter_optin`; nu se bifeaza implicit.
- Tracking-ul frontend este non-PII si trece prin CMP; evenimentele nu trimit email, nume, telefon, URL complet analizat sau `report_token`.
- reCAPTCHA ramane anti-spam pentru lead/contact si trebuie acoperit in politica publica de confidentialitate.

Document de preluare pentru orice developer sau orice LLM / AI care intră rapid în proiect și trebuie să înțeleagă starea reală a aplicației, nu doar planul de refactor.

## 1. Ce este proiectul acum

- Este un micro-app PHP proprietar NovaWeb pentru audit SEO on-page.
- Nu mai este doar un scorer tehnic 0-100. Acum are 3 straturi:
  - audit tehnic: `url + context -> score`
  - impact business: `score -> impact / service recomandat`
  - lead & nurture: email raport, sfaturi de remediere, lead request, newsletter opt-in
- Are două contexte de analiză:
  - `article` = pagină standard / articol / pagină generală
  - `local` = pagină locală, unde semnalele locale pot influența scorul final
- Rulează separat de WordPress, dar poate coexista pe același domeniu și poate trimite lead-uri spre ecosistemul WordPress Novaweb.

### 1.1 Ce oferă produsul concret

- Promisiunea principală pentru userul final este simplă: introduce un URL și primește rapid un audit SEO on-page ușor de înțeles, fără cont obligatoriu.
- În landing și în preview, produsul oferă:
  - scor total 0-100
  - breakdown pe categorii
  - număr de verificări OK / avertizări / probleme
  - rezumat executiv și framing business
  - recomandare de serviciu relevant
- După deblocare prin email sau lead flow, produsul oferă în plus:
  - raport complet în browser
  - lista completă de probleme și recomandări
  - plan de acțiune
  - PDF descărcabil
  - opțional, intrare în lista de sfaturi de remediere prin MailPoet
- Modul `local` nu este un simplu alias de UI; el adaugă verificări reale pentru semnale Google Maps / Local Pack / NAP / schema locală / oraș în pagină.
- Din perspectivă de business, aplicația nu este doar un tool de scoring; este un funnel de tip self-serve audit -> educare -> calificare lead -> cerere de serviciu.

### 1.2 Ce trebuie să înțeleagă orice LLM în primele 5 minute

- Nu este un PageSpeed clone și nu măsoară Core Web Vitals; acoperă în principal SEO on-page, indexare și semnale locale detectabile în HTML.
- Valoarea produsului stă în combinația dintre:
  - audit tehnic
  - interpretare business
  - conversie spre email / PDF / cerere de ofertă
- Există două stări distincte de raport:
  - `preview public` = scorul și contextul sunt vizibile, dar problemele reale pot fi mascate
  - `full unlocked` = userul vede tot raportul și poate descărca PDF-ul
- Dacă testezi local fără SMTP, WordPress/MailPoet sau reCAPTCHA configurate, nu marca automat fluxurile respective drept defecte; unele depind explicit de configurație externă.
- Ordinea corectă de înțelegere pentru un LLM nou în proiect este:
  - landing și promisiunea produsului
  - `POST /api/score`
  - diferența dintre preview și unlock
  - abia apoi email / lead / newsletter

## 2. Stack și arhitectură

- Limbaj: PHP 8.x
- Fără framework
- Dependințe Composer:
  - `vlucas/phpdotenv`
  - `phpmailer/phpmailer`
- Frontend:
  - HTML server-rendered
  - CSS custom fara Bootstrap CDN activ
  - CSS custom în `app/public/style.css`
  - JS vanilla in `app/public/assets/audit-app.js` si `app/public/assets/render-workspace.js`
  - markup împărțit acum în partials PHP, nu mai este o singură pagină monolitică
- Backend:
  - front controller în `app/public/index.php`
  - fișiere helper / servicii în `app/src`
  - stocare file-based în `app/storage`
- Randare pentru pagina analizată:
  - HTTP simplu este default
  - Cloudflare Browser Rendering se foloseste doar cu `ENABLE_CF_RENDER=1` si credențiale valide

## 3. Straturile aplicației

### 3.1 Audit tehnic

- `ArticleScorer::score()` parsează HTML-ul și întoarce:
  - `total`
  - `total_global`
  - `total_local`
  - `breakdown`
  - `checks[]`
  - `meta`
  - `gatekeeper`
  - `context`
- Scorul principal rămâne 100 puncte:
  - Content & UX = 40
  - Structură & Indexare = 25
  - Metadate & Rich Snippets = 20
  - Localizare RO = 15
- Verificările locale extinse au un `LOCAL_MAX = 30`.
- În context `local`, scorul final este:
  - `overall = 0.8 * totalMain + 0.2 * localPercent`

### 3.2 Impact business

- `BusinessImpactCalculator` calculează:
  - `opportunity_score`
  - `estimated_monthly_loss`
  - `estimated_monthly_loss_detail`
  - `lead_segment`
  - `recommended_service`
  - `top_business_issues`
  - `action_plan`

### 3.3 Lead & nurture

- `POST /api/email-report` trimite Email A imediat ca flux tranzactional pentru raport/PDF.
- `wants_plan` ramane numele intern legacy pentru opt-in-ul de sfaturi de remediere; este nebifat implicit si doar `wants_plan=true` impreuna cu `consent_terms=true` inscrie userul in lista MailPoet dedicata.
- `POST /api/lead-request` salvează lead-ul în storage și poate trimite notificare internă pe email.
- Newsletter-ul merge separat, pe lista lui MailPoet, și doar dacă există opt-in explicit.

## 4. Punctele de intrare

- [index.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/index.php)
  - wrapper-ul de la rădăcina repo-ului
  - setează `DOCUMENT_ROOT` către `app/public`
  - încarcă `app/public/index.php`
- [.htaccess](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/.htaccess)
  - servește fișierele reale
  - altfel rescrie către `index.php`
- [app/public/index.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/public/index.php)
  - front controller-ul real
  - conține toată rutarea backend
- [app/public/router.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/public/router.php)
  - router pentru serverul built-in PHP
  - util mai ales pentru smoke test pe `app/public`
  - nu este varianta recomandată pentru testarea completă a UI-ului curent
- [router.local.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/router.local.php)
  - router local nou
  - recomandat pentru rulare locală a aplicației complete din rădăcina repo-ului
- [app/api/render.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/api/render.php)
  - endpoint legacy dezactivat
  - răspunde cu `410 legacy_endpoint_disabled`

## 5. Structura actuală de frontend

- [app/public/view-home.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/public/view-home.php)
  - shell-ul principal al paginii
  - definește `window.NOVA_AUDIT`
  - incarca JS-ul principal din `app/public/assets/audit-app.js`
  - montează workspace-ul, landing-ul și shell-urile pentru drawer / modale prin partials
- Partials în [app/public/partials](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/public/partials):
  - loading live în `view-home.php`:
    - `seo-meta.php`
    - `landing-header.php`
    - `landing-hero.php`
    - `landing-how.php`
    - `landing-interpret.php`
    - `landing-checklist.php`
    - `landing-local-recs.php`
    - `landing-cta-final.php`
    - `landing-faq.php`
    - `landing-footer.php`
    - `report-header.php`
    - `report-overview.php`
    - `report-issues.php`
    - `report-plan.php`
    - `report-technical.php`
    - `report-share.php`
    - `report-contact.php`
    - `drawer-help.php`
    - `cta-blocks.php`
    - `cmp.php`
  - există și partials rămase în director care nu sunt neapărat conectate în fluxul live curent; nu presupune că orice fișier din `partials/` este deja montat în `view-home.php`
- `Config.php` este acum și sursă de copy/config pentru:
  - service catalog
  - consultant
  - testimoniale
  - logo strip
  - case studies
  - FAQ
  - micro trust

## 6. Fluxul real al aplicației

### 6.1 Flux UI -> scor

1. Userul intră pe `/`.
2. Pagina se construiește din `view-home.php` + partials.
3. Frontend-ul primește URL-urile API prin `window.NOVA_AUDIT`.
4. Userul introduce URL-ul.
5. `runAudit()` trimite direct `POST /api/score`.
6. UI-ul afișează loader full-screen cu mesaje în etape.
11. Dacă request-ul durează prea mult, frontend-ul are timeout de 30s și cade în fallback vizibil.
12. La succes, UI-ul randează:
  - secțiunea `Diagnosticul tău`
  - secțiunea `Ce merită reparat prima dată`
  - secțiunea `Detalii tehnice complete`
  - CTA sticky
  - email panel
  - lead form
13. Dacă există `report_token`, URL-ul devine:
  - `?report={token}`
14. La refresh, UI-ul încearcă `GET /api/report?token=...` pentru rehidratare.

### 6.2 Flux UI -> email raport

1. După ce userul vede raportul, poate cere trimiterea lui pe email.
2. Frontend-ul trimite `POST /api/email-report` cu:
  - `report_token`
  - `email`
  - `first_name`
  - `wants_plan` (optional, nume intern legacy pentru opt-in la sfaturi de remediere; absent = `false`)
  - `newsletter_optin` (optional, separat de plan; absent = `false`)
  - `consent_terms`
3. Backend-ul:
  - validează email-ul
  - validează `consent_terms`
  - dacă opt-in-ul de sfaturi este nebifat, afișează modalul opțional pentru sfaturi de remediere
  - dacă userul alege `Da, vreau sfaturile`, setează `wants_plan=true`
  - dacă userul alege `Nu, trimite doar raportul`, păstrează `wants_plan=false`
  - aplică rate limit
  - reîncarcă snapshot-ul din `report_token` sau îl reconstruiește dacă lipsește token-ul
  - trimite Email A
  - trateaza `wants_plan` absent ca `false`
  - înscrie lead-ul general în MailPoet
  - înscrie în lista MailPoet de sfaturi de remediere dacă `wants_plan=true`
  - înscrie opțional în newsletter dacă `newsletter_optin=true`
  - salvează lead JSON local cu `source`, scor, segment și intenții

### 6.3 Flux UI -> lead request

1. Userul poate deschide formularul de lead din CTA-uri sau din fallback.
2. Frontend-ul trimite `POST /api/lead-request` cu:
  - `report_token`
  - `source`
  - `name`
  - `phone`
  - `email`
  - `message`
  - `urgent`
  - `wants_plan`
  - `newsletter_optin`
  - `website` (honeypot)
3. Backend-ul:
  - verifică honeypot
  - aplică rate limit
  - verifică reCAPTCHA ca mecanism anti-spam, cu skip doar în `APP_ENV=local`
  - validează snapshot-ul
  - validează email + telefon
  - salvează lead-ul JSON în storage
  - poate trimite notificare internă dacă există `LEAD_NOTIFY_EMAIL`

### 6.4 Exit intent

- Există modal de exit intent în `cta-blocks.php`.
- Se afișează doar după ce există un `currentReport`.
- Exit intent-ul împinge userul către formularul de email și setează `source=exit_intent`; lead-ul se salvează când userul trimite formularul.

### 6.5 Preview public vs raport deblocat

- `POST /api/score` întoarce, în mod normal, un payload de preview din `ReportPayload::public()` și setează `locked=true`.
- În preview, userul vede:
  - scorul total
  - breakdown-ul
  - numărul de verificări pe severități
  - check-urile OK sau neproblematice
  - indicii business de nivel înalt
- În preview, problemele reale și acțiunile concrete pot fi mascate intenționat:
  - label generic pentru problemă
  - action plan lock-uit
  - top business issues lock-uit
- `POST /api/email-report` deblochează raportul pentru token-ul curent și poate crea grant pe site/email.
- `GET /api/report?token=...` întoarce full doar dacă raportul este deblocat prin:
  - sesiune locală
  - `access` token
  - grant din `SiteAuditAccess`
- `GET /api/report-pdf?token=...` este disponibil doar după unlock; `403` pentru raport lock-uit este comportament corect, nu regresie.
- `SiteAuditAccess` pune limită pe rapoartele complete per site. Implicit, limita este `3` rapoarte complete / 24h pentru același host. Dacă un LLM retestează obsesiv același domeniu, poate lovi această limită și trebuie să interpreteze rezultatul corect.

Observatie limita:
- Cand apare o limita (`site_audit_limit_reached` sau `rate_limited`), frontend-ul trebuie sa pastreze sau sa rehidrateze ultimul raport disponibil, nu sa inlocuiasca workspace-ul cu un card gol de eroare. Raportul restaurat pastreaza formularul de retrimitere pe email.

## 7. Endpoint-uri backend

### `GET /`

- Returnează pagina publică din `view-home.php`.

### `GET /api/report?token=...`

- Returnează snapshot-ul raportului dacă token-ul există
- Dacă token-ul nu există:
  - `404 report_not_found`

### `GET /api/report-pdf?token=...`

- Returnează PDF-ul raportului doar dacă token-ul este deblocat
- Dacă token-ul nu există:
  - `404 report_not_found`
- Dacă raportul există, dar este încă lock-uit:
  - `403`
  - mesaj text simplu, nu JSON

### `POST /api/render`

- Endpoint de diagnostic
- Nu este folosit de fluxul principal din UI
- Primește:
  - `url`
- Întoarce:
  - `html_source`
  - `bytes`
  - `snippet`

### `POST /api/score`

- Endpoint-ul principal pentru audit
- Payload minim:
  - `url`
  - `context`
- Payload extins:
  - `url`
  - `context`
  - `fresh` (opțional)
- Returnează la succes:
  - `ok`
  - `url_analyzed`
  - `source`
  - `js`
  - `context`
  - `score`
  - `business`
  - `report_token`
- `score.checks[]` vine deja îmbogățit din backend cu:
  - `label`
  - `rule`
  - `tip`
  - `business_impact_text`
  - `business_impact_magnitude`
  - `related_service`
  - `fix_complexity`

### `POST /api/email-report`

- Trimite Email A cu raportul
- Este fluxul tranzactional pentru raport complet/PDF; sfaturile de remediere si newsletter-ul nu sunt implicite.
- Payload tipic:
  - `report_token`
  - `email`
  - `first_name`
  - `wants_plan` (absent = `false`)
  - `newsletter_optin` (absent = `false`)
  - `consent_terms`
- `plan_subscribed=true` apare doar cand userul a bifat explicit sfaturile de remediere si `consent_terms=true`
- Poate reconstrui raportul și fără token dacă primește:
  - `url`
  - `context`
- Returnează:
  - `ok`
  - `report_token`
  - `plan_subscribed`
  - `plan`
  - `newsletter`
  - `lead_saved`

### `POST /api/lead-request`

- Salvează lead-ul și poate trimite notificare internă
- Folosește reCAPTCHA anti-spam pentru formularele de lead/contact; lipsa secretului blochează producția, iar skip-ul este permis doar local.
- Payload tipic:
  - `report_token`
  - `source`
  - `name`
  - `phone`
  - `email`
  - `message`
  - `urgent`
  - `wants_plan`
  - `newsletter_optin`
  - `website`
- Returnează:
  - `ok`
  - `message`

## 8. Clasele care contează cel mai mult

- [app/src/ArticleScorer.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/ArticleScorer.php)
  - motorul de scoring
  - parsează HTML cu `DOMDocument` + `DOMXPath`
  - expune `allCheckIds()` pentru verificarea consistenței
  - tratează acum și cazul de HTML gol / invalid fără fatal
- [app/src/Advice.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/Advice.php)
  - sursa unică de adevăr pentru definițiile check-urilor
  - include:
    - `label`
    - `rule`
    - `tip`
    - `business_impact_text`
    - `business_impact_magnitude`
    - `related_service`
    - `fix_complexity`
- [app/src/BusinessImpactCalculator.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/BusinessImpactCalculator.php)
  - transformă rezultatul tehnic într-un layer business
- [app/src/ReportStore.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/ReportStore.php)
  - salvează snapshot-uri de raport pe bază de `report_token`
- [app/src/LeadRepository.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/LeadRepository.php)
  - salvează lead-uri JSON
  - validează telefon RO și fallback internațional
- [app/src/EmailRenderer.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/EmailRenderer.php)
  - randare pentru:
    - Email A: raport
    - notificare lead internă
- [app/src/RenderService.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/RenderService.php)
  - foloseste HTTP simplu implicit; Cloudflare doar daca `ENABLE_CF_RENDER=1`
- [app/src/HttpClient.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/HttpClient.php)
  - fetch simplu, HEAD Last-Modified, detectare JS-heavy
- [app/src/RateLimiter.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/RateLimiter.php)
  - rate limit file-based
- [app/src/NewsletterService.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/NewsletterService.php)
  - subscribe în WordPress doar dacă există ambele consimțăminte
- [app/src/Config.php](/d:/Work/Novaweb%20Projects/audit-seo-gratuit/audit-seo-gratuit/app/src/Config.php)
  - helper de path-uri publice și config de landing / CTA / copy

## 9. Email-uri

### Email A

- Trimis imediat după `POST /api/email-report`
- Include:
  - scor total
  - rezumat executiv
  - top 3 probleme cu impact comercial
  - link către raportul complet
  - CTA către service recomandat sau raport

### Follow-up / MailPoet

- Nu se mai trimite din tool.
- Integrarea MailPoet foloseste o singura lista canonica, `WP_AUDIT_LIST_NAME`, pentru lead-uri, sfaturi si newsletter; `wants_plan` si `newsletter_optin` raman consimtaminte salvate separat in lead JSON.
- Orice follow-up se creează ca automation în WordPress/MailPoet; repo-ul nu mai promite public o secventa temporizata.
- Double opt-in, linkurile de unsubscribe si livrabilitatea SPF/DKIM/DMARC se verifica in WordPress/MailPoet si in configuratia domeniului, nu in acest repo.

### Notificare internă lead

- Se trimite doar dacă există `LEAD_NOTIFY_EMAIL`
- Include:
  - contact
  - URL
  - segment
  - urgent / normal
  - top probleme
  - mesaj

## 10. Storage și date sensibile

Folderul `app/storage` conține atât runtime artifacts, cât și date sensibile.

- `app/storage/cache`
  - cache pentru audituri
- `app/storage/reports`
  - snapshot-uri de raport cu `report_token`
- `app/storage/leads`
  - lead-uri active salvate de `LeadRepository`
- `app/storage/_archive`
  - arhivă de lead-uri istorice migrate din implementarea veche
- `app/storage/ratelimit`
  - contoare rate limit per zi
- `app/storage/logs/newsletter.log`
  - log de integrare WordPress / newsletter
  - tratează-l ca potențial sensibil
- `app/storage/ro_localities.json`
  - baza folosită de `ArticleScorer` pentru detectarea orașelor în pagină

## 11. Variabile de mediu folosite efectiv

### Public path / origin

- `APP_BASE`
- `APP_ORIGIN`
- `APP_URL`

Observație:
- `Config::origin()` încearcă mai întâi `APP_ORIGIN`
- dacă lipsește, cade pe host-ul extras din `APP_URL`

### Render / cache / rate limit

- `ENABLE_CF_RENDER`
- `CF_ACCOUNT_ID`
- `CF_API_TOKEN`
- `HTTP_MAX_BYTES`
- `CACHE_TTL`
- `RATE_LIMIT_SCORE`
- `RATE_LIMIT_EMAIL`
- `RATE_LIMIT_LEAD`

### Site audit access / unlock

- `SITE_AUDIT_RUN_LIMIT`
- `SITE_AUDIT_WINDOW_SECONDS`

### SMTP

- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_SECURE`
- `SMTP_USER`
- `SMTP_PASS`
- `MAIL_FROM`
- `MAIL_FROM_NAME`

### WordPress / newsletter

- `WP_SUBSCRIBE_URL`
- `WP_SUBSCRIBE_TOKEN`
- `WP_AUDIT_LIST_NAME`
- `WP_LIST_NAME`
- `WP_PLAN_LIST_NAME`
- `WP_LEADS_LIST_NAME`
- `WP_INSECURE`

### IP / proxy

- `TRUST_CF_CONNECTING_IP`

### Tracking / UI

- `GA4_ID`
- `FB_PIXEL_ID`
- `RECAPTCHA_SITE_KEY`
- `RECAPTCHA_SECRET_KEY`
- `RECAPTCHA_MIN_SCORE`
- `APP_ENV`

Observatii tracking / CMP:
- GA4 si Meta Pixel sunt incarcate prin CMP (`type="text/plain"` cu `data-consent`), nu direct.
- Evenimentele frontend noi sunt non-PII: `report_gate_view`, `form_validation_error`, `recaptcha_error`, `plan_optin_checked`, `newsletter_optin_checked`.
- Modalul optional pentru sfaturi de remediere trimite tot non-PII; evenimentele pastreaza numele interne legacy `plan_prompt_view`, `plan_prompt_accept`, `plan_prompt_decline`.
- Evenimentele pot trimite doar `context`, `source`, valori booleene si motiv generic de eroare.
- Nu trimite in analytics email, nume, telefon, URL complet analizat sau `report_token`.

### Lead notify

- `LEAD_NOTIFY_EMAIL`

### Fișiere `.env`

- `app/.env`
  - baza principală
- `app/.env.local`
  - override local nou, încărcat peste `.env`
- `app/.env.local.example`
  - exemplu minim pentru local

Important:
- nu expune valori reale din `.env*`
- documentează doar numele variabilelor, nu conținutul lor

## 12. Rulare locală

### Varianta recomandată pentru UI complet

Rulează din rădăcina proiectului:

```powershell
php -S 127.0.0.1:8080 -t . router.local.php
```

Și deschide:

```text
http://127.0.0.1:8080/
```

### Config local minim

În `app/.env.local`:

```dotenv
APP_ORIGIN=http://127.0.0.1:8080
APP_BASE=/
APP_ENV=local
ENABLE_CF_RENDER=0
CF_ACCOUNT_ID=
CF_API_TOKEN=
HTTP_MAX_BYTES=5242880
```

Observații:
- local, e recomandat ca `CF_*` să fie goale pentru predictibilitate
- altfel poți aștepta mai mult până la fallback

### 12.1 Protocol minim de test pentru orice LLM

1. Pornește aplicația cu `router.local.php` din rădăcina repo-ului.
2. Deschide `/` și verifică promisiunea produsului: audit gratuit, fără cont, două contexte (`article` și `local`).
3. Rulează un audit prin UI sau direct prin API:

```powershell
Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8080/api/score -ContentType 'application/json' -Body '{"url":"https://exemplu.ro/servicii-seo/","context":"article","fresh":true}'
```

4. Salvează `report_token` și verifică snapshot-ul:

```powershell
Invoke-RestMethod -Method Get -Uri "http://127.0.0.1:8080/api/report?token=TOKEN_AICI"
```

5. Observă explicit dacă răspunsul vine cu `locked=true` sau `locked=false`; asta face parte din funnel-ul produsului și nu este un comportament accidental.
6. Repetă testul pentru `context=local` pe o pagină cu intenție locală, altfel nu evalua sever semnalele locale.
7. Testează `GET /api/report-pdf` doar după unlock; `403` înainte de unlock este expected.
8. Testează `POST /api/lead-request` doar în unul dintre cazurile de mai jos:
  - `APP_ENV=local`, unde `verifyRecaptcha()` permite skip
  - sau există configurare reCAPTCHA validă
9. Nu declara fluxurile de email / MailPoet / newsletter „stricate” dacă mediul local nu are SMTP sau integrarea WordPress configurată.
10. Dacă lovești `site_audit_limit_reached`, schimbă domeniul testat sau așteaptă resetarea ferestrei; nu trata acel răspuns ca bug fără context.

### Varianta veche

```powershell
php -S 127.0.0.1:8080 -t app/public app/public/router.php
```

Aceasta este utilă mai ales pentru backend smoke tests, dar nu este varianta recomandată pentru UI-ul curent.

## 13. Particularități de frontend

- `view-home.php` nu mai este monolit complet; JS-ul principal este in fisierele din `app/public/assets/`.
- Multe interacțiuni depind în continuare de ID-uri fixe:
  - `scoreForm`
  - `urlInput`
  - `report-stage`
  - `action-plan-stage`
  - `technical-stage`
  - `lead-stage`
  - `emailPanel`
  - `stickyCtaDesktop`
  - `stickyCtaMobile`
  - `exitIntentModal`
  - `planPromptModal`
- Dacă muți markup-ul între partials fără să urmărești și JS-ul, poți rupe flow-ul imediat.
- Există loader full-screen, help drawer și modale pentru exit intent / limită raport / plan optional / CMP.
- S-a lucrat recent la stacking-ul modalelor și la loader; dacă apar regresii vizuale, verifică mai întâi:
  - `.modal-shell`
  - `.modal-backdrop`
  - `.modal-panel`
  - `.page-loader`

## 14. Particularități de path / montaj pe domeniu

- Aplicația folosește acum helperi centralizați în `Config`:
  - `basePath()`
  - `origin()`
  - `baseUrl()`
  - `canonicalUrl()`
  - `assetUrl()`
- Target-ul public dorit în producție este:
  - `/tools/audit-seo-gratuit/`
- Local se poate rula la:
  - `/`
- Backend-ul știe să scoată prefixul `APP_BASE` din request path.
- SEO meta și asset path-urile nu mai folosesc vechea inconsistență `/tools/tools/...`.

## 15. WordPress și integrarea externă

- Proiectul nu conține WordPress.
- Integrarea există doar prin:
  - `WordpressClient`
  - endpoint-ul custom de subscribe
- Lista MailPoet este controlata prin `WP_AUDIT_LIST_NAME`; fallback-ul codului este `Audit SEO Gratuit - Sfaturi de remediere`.
- `WP_LIST_NAME`, `WP_PLAN_LIST_NAME` si `WP_LEADS_LIST_NAME` raman doar variabile legacy/example si trebuie tinute la aceeasi valoare daca sunt prezente in env.
- `wants_plan` si `newsletter_optin` raman consimtaminte separate in lead JSON, dar nu declanseaza abonari MailPoet in liste separate.
- Double opt-in, unsubscribe si livrabilitatea SPF/DKIM/DMARC se verifica in WordPress/MailPoet si la nivel de domeniu.
- Dacă apare o problemă de listă / MailPoet / REST, verificarea trebuie făcută în site-ul WordPress, nu în acest repo.

## 16. Dacă modifici check-urile, sincronizează corect

Deși `Advice` este acum sursa unică pentru label / rule / tip / business fields, tot trebuie să verifici minim:

- `app/src/ArticleScorer.php`
- `app/src/Advice.php`
- `app/src/BusinessImpactCalculator.php`
- `app/src/EmailRenderer.php`
- `app/public/view-home.php`

În plus, rulează:

```powershell
php tools/check-consistency.php
```

Tool-ul verifică:
- definiții lipsă în `Advice`
- câmpuri incomplete
- definiții în plus față de `ArticleScorer::allCheckIds()`

## 17. Limitări și puncte rămase deschise

Acestea sunt limitările reale din implementarea actuală.

- Email A funcționează ca raport imediat, dar copy-ul poate fi rafinat în continuare.
- Fluxul de sfaturi de remediere depinde de automation-ul MailPoet configurat în WordPress, nu de codul din acest repo.
- Validarea juridica finala pentru privacy policy, reCAPTCHA si consimtamant ramane externa implementarii tehnice.
- `view-home.php` contine inca shell HTML si markup de modal intern, dar JS-ul principal este externalizat
- există în continuare copy + logică amestecate, chiar dacă o parte din landing a fost mutată în partials și `Config`
- proiectul nu are testare automată
- unele texte UI au fost curățate recent, dar copy-ul trebuie tratat cu atenție; nu presupune că orice text din repo este deja copy final de marketing
- trust metric-ul `analysisCount()` se bazează pe numărul de fișiere din cache, nu pe un counter business validat
- anumite detalii de polish din planul mare încă nu sunt complet închise:
  - copy și timing pentru automation-ul MailPoet
  - unele elemente de trust / case studies încă nu sunt expuse complet în UI

## 18. Cum să abordezi modificările fără să strici proiectul

### Dacă taskul este de scoring

- pornește din `ArticleScorer.php`
- verifică apoi `Advice.php`
- verifică `BusinessImpactCalculator.php`
- confirmă în UI că `checks[]` apar corect și în raport, și în email

### Dacă taskul este de email / lead capture

- verifică:
  - `app/public/index.php`
  - `EmailRenderer.php`
  - `LeadRepository.php`
  - `NewsletterService.php`
  - `WordpressClient.php`
- nu expune date din `storage/logs`, `storage/leads`, `storage/reports`

### Dacă taskul este de frontend / UX

- verifică mai întâi:
  - `view-home.php`
  - `style.css`
  - partial-ul relevant
- dacă umbli la modale, verifică stacking și backdrop
- dacă umbli la flow-ul de scor, verifică și:
  - `report token`
  - `?report=...`

### Dacă taskul este de path / deploy

- verifică:
  - `Config::origin()`
  - `Config::basePath()`
  - `Config::assetUrl()`
  - `Config::canonicalUrl()`
- local și producție au setup-uri diferite; nu valida deploy-ul doar din rularea locală

## 19. Rezumat ultra-scurt

- Proiectul este acum un funnel PHP complet: audit tehnic + strat business + lead capture.
- Backend-ul real este în `app/public/index.php`.
- UI-ul este în `view-home.php` + partials.
- `Advice` este sursa unică pentru definițiile check-urilor.
- `report_token` și `ReportStore` sunt baza pentru rehidratare, email și lead form.
- MailPoet foloseste o singura lista, `WP_AUDIT_LIST_NAME`, pentru toate inscrierile din tool.
- Modalul de confirmare pentru sfaturi nu aboneaza la newsletter; seteaza doar `wants_plan=true` sau continua raportul cu `false`.
- Newsletter-ul este consimtamant separat; tracking-ul frontend ramane non-PII si controlat prin CMP.
- reCAPTCHA este folosit pe lead/contact ca anti-spam si trebuie documentat in politica publica de confidentialitate.
- Rularea locală corectă se face cu `router.local.php` și `APP_BASE=/`.
- Nu expune niciodată secretele din `.env*` sau datele din `storage`.
