# Plan detaliat de implementare — Refactor tool audit-seo-gratuit

Documentul este structurat ca ghid de implementare în faze. Ordinea e gândită astfel încât fiecare fază se sprijină pe cea anterioară și fiecare poate fi livrată ca PR independent. Estimările de timp presupun un singur dev cu cunoaștere PHP solidă, lucrând part-time.

---

## Principii care ghidează tot planul

1. **Nu rupem activul SEO existent.** URL-ul `/tools/audit-seo-gratuit/`, title-ul principal, H1-ul de fundal și schema JSON-LD păstrează termenii „audit SEO gratuit” pentru poziționarea organică. Schimbările de copy se aplică pe sub-headline, propuneri de valoare și interior, nu pe ancorile SEO.
2. **Fiecare modificare de funcționalitate se reflectă consistent în 4 locuri** (cum spune handoff-ul): `ArticleScorer.php`, `Advice.php`, `EmailRenderer.php`, `view-home.php`.
3. **Backend rămâne sursă de adevăr.** Frontend-ul nu calculează niciodată scor sau segmentare. UI-ul afișează ce primește din `/api/score`.
4. **Refactorizarea se face fără rescriere totală.** Motorul de scoring e bun, îl păstrăm. Schimbăm pielea (copy, layout, output, CTA) și adăugăm stratul de business impact.
5. **Fricțiunea adăugată trebuie să fie justificată prin valoare adăugată.** Dacă cerem ceva utilizatorului, ce primește în schimb trebuie să fie mai bun decât ce primea fără cererea respectivă.

---

## Faza A — Curățenie tehnică și pregătire (1-3 zile)

Această fază nu schimbă comportamentul vizibil, dar elimină datorii tehnice care fac restul fazelor periculoase. Nu sări peste.

### A1. Eliminare și consolidare cod legacy
- **Șterge `app/api/render.php`** sau redenumește-l `_legacy_render.php` și marchează cu comentariu că nu e folosit. Ruta vie e în `app/public/index.php`.
- **Arhivează `app/storage/leads/`** într-un folder `_archive/` cu data export. Marchează folderul ca read-only. Nu mai e sursă funcțională.
- **Hotărăște și aplică un singur path canonical:** ori `/tools/audit-seo-gratuit/`, ori `/tools/tools/audit-seo-gratuit/`. Apoi search-and-replace în `view-home.php` pe toate aparițiile (canonical, hreflang, OG, JSON-LD, asset paths). Productivitatea SEO actuală sugerează că `/tools/audit-seo-gratuit/` e cel corect.

### A2. Hardening backend
- **`ArticleScorer::score()`**: înainte de `DOMDocument::loadHTML()`, verifică explicit că HTML-ul nu e gol și e valid UTF-8. Dacă e gol/invalid, returnează un response structurat cu `error: "render_failed"` și `partial_data: false`. UI-ul va afișa un fallback specific (vezi G3). Scopul: eliminăm fatal-urile istorice din error_log menționate în handoff.
- **Rate limit pe `/api/email-report`**: implementează folosind variabila `RATE_LIMIT_EMAIL` care există deja în env dar nu e folosită. Mecanismul există deja pentru `/api/score`, copiezi structura. Limită propusă: 3 emailuri/IP/zi. Previne abuz.
- **Validare server-side a `consent_terms`**: în handler-ul `/api/email-report`, dacă `consent_terms !== true`, returnează `400 invalid_consent`. Nu te baza pe frontend.

### A3. Refactor structural `view-home.php` (esențial)
Fișierul de 1735 linii e blocant pentru orice iterație rapidă. Spargere în partials:

```
app/public/
├── view-home.php (rămâne shell-ul + JS-ul de orchestrare)
└── partials/
    ├── seo-meta.php          (title, meta, JSON-LD, hreflang, OG)
    ├── landing-hero.php      (H1, sub-headline, formular URL)
    ├── landing-trust.php     (testimoniale, logos, indicatori)
    ├── landing-explainer.php (cum funcționează, ce primești)
    ├── landing-faq.php       (FAQ existent, util și SEO)
    ├── result-business.php   (Faza C — secțiunea diagnostic comercial)
    ├── result-action-plan.php (Faza C — plan acțiune)
    ├── result-technical.php  (Faza C — detalii tehnice, accordion)
    ├── cta-blocks.php        (Faza D — toate variantele de CTA)
    └── lead-form.php         (Faza D — form ofertă inline)
```

JS-ul rămâne deocamdată inline în shell, dar ID-urile DOM se păstrează identice cu cele actuale (`scoreForm`, `urlInput`, `result`, `localSend`, `consentNewsletter`, `consentTerms`, `scoreModal`) ca să nu spargi nimic. Modificările vin prin includere noi, nu prin rescriere de selectori.

### A4. Centralizare config în PHP
Creează `app/src/Config.php` care expune:
- `Config::baseUrl()` (citește `APP_BASE`)
- `Config::canonicalUrl(string $path = '')` 
- `Config::assetUrl(string $path)`

Înlocuiește hard-coding-urile din `view-home.php` cu apeluri la aceste metode. Mut cantitatea de `$_ENV` lookup în PHP runtime.

**Output Faza A:** cod curat, fără regresie funcțională, ușor de iterat în următoarele faze. Nimic vizibil pentru utilizator.

---

## Faza B — Audit direct (1-2 zile)

Auditul pornește direct după submitul URL-ului. `POST /api/score` acceptă `url`, `context` și `fresh` opțional.

---

## Faza C — Refactor scoring și output (5-8 zile)

Aici e munca cea mai grea: traducerea check-urilor tehnice în impact comercial. Are componentă de cod și componentă de copy.

### C1. Extindere `Advice.php`
Pentru fiecare check existent, adaugă patru câmpuri noi:

```php
'meta_description_missing' => [
  'label' => 'Meta description lipsă',                     // EXISTENT
  'severity_thresholds' => [...],                           // EXISTENT
  'recommendation' => 'Adaugă o descriere de 150-160 caractere...', // EXISTENT
  
  // NOI:
  'business_impact_text' => 'Pe Google, când cineva caută servicii ca ale tale, paragraful de sub link este gol. Asta scade considerabil șansa să dea click pe rezultatul tău față de competiție.',
  'business_impact_magnitude' => 'high', // high|medium|low
  'related_service' => 'seo_optimization', // seo_optimization|website_redesign|content|local_seo|technical
  'fix_complexity' => 'easy', // easy|medium|hard — semnal pentru dacă utilizatorul poate face singur
],
```

**Cele 4 magnitudini contează pentru ranjarea problemelor în UI.**

**Cele 5 servicii conectate** trimit utilizatorul către pagina relevantă din site:
- `seo_optimization` → `/servicii-seo/`
- `website_redesign` → pagina creare site
- `content` → ofertă conținut SEO
- `local_seo` → ofertă SEO local (de creat dacă nu există)
- `technical` → mentenanță / refactor tehnic

**Cele 3 niveluri `fix_complexity`** au rol critic: când e `hard` sau `medium`, CTA-ul lateral devine „Te ajutăm noi cu asta”. Când e `easy`, lași utilizatorul să simtă că poate. Asta evită percepția de „vinde-mi totul”.

Această muncă de copy e **cea mai consumatoare** din toată faza C. Estimativ 30-50 check-uri × 3-5 minute de gândire copy = 3-4 ore concentrate. **Făcută bine, asta singură schimbă tot tonul tool-ului.** Făcută prost, tool-ul devine spam de vânzare.

### C2. Modul nou: `BusinessImpactCalculator`

Creează `app/src/BusinessImpactCalculator.php`. Responsabilități:

**a) Calculează `opportunity_score` (0-100)** — cât potențial e nefolosit:
- Pornește de la `100 - score_total`.
- Aplică un multiplicator conservator, fără input suplimentar de la utilizator.

**b) Calculează `estimated_monthly_loss`** — afișat ca interval, nu valoare punctuală:
- Fără profil business declarat, returnează `null`; UI-ul ascunde secțiunea.

**c) Determină `lead_segment`** — output enum:
- fără input de calificare, segmentul rămâne `unknown`
- `mature`: scor > 75 + company_size 11+ → consultanță / audit avansat
- `educational`: site_role none + company_size 1-2 → newsletter, no follow-up activ
- `unknown`: profile.skipped == true → CTA generic

**d) Determină `recommended_service`** — care e oferta principală pe care o promovezi în CTA primar:
- Mapare directă din `lead_segment` + top 3 probleme `business_impact_magnitude=high`.
- Output: string + URL.

### C3. Restructurare răspuns API
`/api/score` returnează acum în plus:
```
{
  "score": { ...existent... },
  "business": {
    "opportunity_score": 67,
    "estimated_monthly_loss": "800-2400 EUR",
    "lead_segment": "warm",
    "recommended_service": {
      "name": "Optimizare SEO",
      "url": "/servicii-seo/",
      "cta_primary": "Vreau planul personalizat",
      "cta_secondary": "Programează apel 15 min"
    },
    "top_business_issues": [
      {
        "check_id": "meta_description_missing",
        "impact_text": "...",
        "fix_complexity": "easy",
        "related_service": "seo_optimization"
      },
      ... (top 3-5)
    ]
  }
}
```

### C4. Restructurare layout raport în UI

În locul raportului tehnic monolitic actual, ai 3 secțiuni vizuale, în această ordine de sus în jos:

**Secțiunea 1 — „Diagnosticul tău”**
Vizibilă imediat când ajunge raportul, fără scroll.
- Donut score (existent, păstrat).
- Lângă donut: card mare „Estimat: X clienți / lună pierduți” cu valoarea calculată.
- Sub: 3 carduri cu top 3 probleme cu impact business înalt, format „[Problema în limbaj business]. Asta înseamnă că [consecință]. [CTA mic: Cum se rezolvă →]”.
- Buton primar mare: `cta_primary` din `recommended_service`.

**Secțiunea 2 — „Ce poți face în 30 zile”**
- Listă numerotată 3-5 acțiuni prioritizate (din top_business_issues + alte high-magnitude).
- Pentru fiecare: ce face, de ce contează, cât durează (estimat: 1h / 1 zi / 1 săptămână), cine o poate face (tu / dezvoltator / agenție).
- Cele cu `fix_complexity=hard` au badge „Recomandat să te ajutăm” cu link spre serviciu.

**Secțiunea 3 — „Detalii tehnice complete”**
- Accordion închis implicit, cu eticheta „Vezi raportul tehnic complet (pentru dezvoltatori)”.
- La deschidere: tot ce există acum în UI-ul actual — breakdown pe categorii, carduri pe content/structure/metadata/local, lista completă de check-uri pass/warn/fail.
- Aceasta e zona pentru audiența tehnică. O ascunzi inițial, dar nu o pierzi. Dezvoltatori sau marketers care chiar înțeleg vor să vadă detaliile, și asta crește credibilitatea — semnalează că nu vinzi vid.

### C5. Mod `local` integrat, nu separat
Modul `local` rămâne ca toggle explicit în interfață. Scorer-ul detectează singur semnalele locale din pagină.

---

## Faza D — CTA segmentat și captare lead (4-6 zile)

### D1. Tabel oficial al CTA-urilor pe segment

| Lead segment | CTA primar | CTA secundar | Ton copy |
|---|---|---|---|
| `hot` | „Solicită ofertă personalizată pentru refacere site” | „Programează apel 20 min cu un consultant” | Direct, urgență moderată |
| `warm` | „Vreau planul personalizat pe 30 zile” | „Solicită ofertă optimizare SEO” | Consultativ, valoare-first |
| `mature` | „Solicită audit avansat plătit” (199 EUR) | „Consultanță 1h: 350 EUR” | Profesionist, premium |
| `educational` | „Primește săptămânal sfaturi SEO” | (niciun secundar) | Educațional, blând |
| `unknown` | „Vorbește cu un consultant” | „Vezi serviciile noastre” | Neutru |

CTA-urile reale și prețurile se calibrează cu tine. Coloana e pentru a arăta logica.

### D2. Plasări CTA în pagină

- **Sticky right (desktop, latime ≥1024px):** card cu CTA primar + 1 testimonial scurt. Apare când utilizatorul a derulat secțiunea 1 a raportului. Persistent până când scrolează în jos de footer.
- **Sticky bottom (mobile):** bară fixă la bottom cu CTA primar singur. Cu posibilitate de close (cookie 24h).
- **Inline:** la finalul fiecărei secțiuni majore (1, 2, 3) — același CTA primar, dar cu copy variat ca să nu pară spam.
- **Exit intent (desktop):** dacă mouse-ul iese spre tab close, popup mic cu „Înainte să pleci, vrei să-ți trimitem planul pe 30 zile pe email?” — captare slabă pentru cei care nu erau gata să convertească.

### D3. Form ofertă inline (NU redirect spre alt URL)

Click pe CTA primar → expandează inline un mic form, NU redirect:
- Nume
- Telefon
- Email
- Mesaj prepopulat: „Site analizat: [URL]. Scor: [X]/100. Industrie: [Y]. Top problemă: [Z].”
- Bifa opțională: „Sunați-mă în 24h”
- Buton: „Trimite cererea”

**De ce inline, nu redirect:** redirect-ul rupe contextul vizual al raportului. Utilizatorul e în modul „uite ce probleme am”, redirect-ul îl duce în modul „acum trebuie să completez un alt formular”. Inline = continuitate.

Acest form e **diferit** de form-ul de email pentru raport. Astea sunt două intenții distincte:
- Email raport = vreau să păstrez raportul pentru mine
- Form ofertă = vreau să mă contactați

Le ții separate vizual și logic. Cele două se pot suprapune (un user poate face și una și alta), dar sunt CTA-uri diferite.

### D4. Backend pentru form ofertă
- Endpoint nou: `POST /api/lead-request`
- Payload: nume, telefon, email, url, profile, score, segment, top_issues, urgent (din bifa)
- Acțiuni:
  - Salvare în `app/storage/leads/YYYY-MM-DD-{hash}.json`
  - Email imediat către agenție (variabilă env nouă: `LEAD_NOTIFY_EMAIL`)
  - Subiect email diferențiat: dacă `urgent=true` → „[URGENT 24h] Lead [segment] din audit-seo-gratuit”, altfel → „[NORMAL] Lead [segment] din audit-seo-gratuit”
  - Răspuns către utilizator: confirmare in-page „Mulțumim, te contactăm în maxim 24h”
- **Rate limit pe IP** (3/zi) și protecție honeypot anti-bot (câmp ascuns „website” care, dacă e completat, marchează request-ul ca spam).

### D5. Validare numar de telefon românesc
Pentru a filtra spam-ul: regex pentru telefon RO (07XX XXX XXX format), cu opțiune internațional pentru cei din diaspora. Dacă invalid → mesaj clar „Te rugăm să introduci un număr valid”.

---

## Faza E — Email și nurture (3-5 zile)

### E1. Două emailuri în loc de unul

**Email A — „Confirmare + raport tehnic”** (trimis imediat la submit email)
- Subject: `Raport SEO pentru [domeniu] — scor [X]/100`
- Conținut: rezumatul executiv (scor + estimare pierderi + top 3 probleme business) + link înapoi la raport în UI cu un slug unic (`?report=abc123` care reîncarcă rezultatul cached) + raport tehnic complet
- CTA: „Vorbește cu un consultant” + „Vezi serviciile noastre”
- Footer: menționează planul de 30 zile doar dacă userul a ales `wants_plan`.

**Email B — „Plan personalizat pe 30 zile”** (trimis din MailPoet, nu din tool)
- Subject: `[Nume], am calculat ce poți face în 30 zile pentru [domeniu]`
- Conținut: 3-5 acțiuni concrete derivate din `top_business_issues`, fiecare cu:
  - Ce de făcut (1-2 propoziții)
  - De ce (impact așteptat)
  - Cine o face (tu / cu ajutor / cu agenție)
  - Estimare timp/cost
- CTA puternic: „Vrei să implementăm noi planul? Solicită ofertă →”
- Footer: dezabonare + link contact direct

### E2. Mecanism trimitere Email B la +24h
- Tool-ul nu mai construiește queue local și nu procesează Email B prin cron/lazy processor.
- La trimitere Email A, dacă `wants_plan=true`, userul este înscris în lista MailPoet configurată prin `WP_PLAN_LIST_NAME`.
- Default list name: `Audit SEO Gratuit - Plan 30 zile`.
- Automation-ul din WordPress/MailPoet trimite Email B după 24h și gestionează follow-up-urile viitoare, dezabonarea și tracking-ul.
- Newsletter-ul rămâne pe lista separată `WP_LIST_NAME`, iar lead-urile generale pe `WP_LEADS_LIST_NAME`.

### E3. Diferențiere checkbox-uri lead intent vs newsletter

În locul actualului „consimțământ newsletter” singur, două checkbox-uri SEPARATE pe form-ul de email-report:

```
☐ Vreau planul personalizat pe 30 zile (recomandat)
☐ Vreau și sfaturi SEO ocazionale prin email (max 2/lună)
```

- Primul checkbox înscrie userul în lista MailPoet de plan 30 zile și marchează lead-ul cu `wants_plan=true` în storage.
- Al doilea face ce face acum: înscriere MailPoet via WordpressClient.

**Ambele bifate = lead serios.** Niciuna bifate = utilizator care vrea doar raportul, marchează ca `passive`. Asta e segmentare automată suplimentară.

### E4. Lead handoff structurat
Toate fișierele lead salvate în `app/storage/leads/` ar trebui să respecte schema unică:

```json
{
  "captured_at": "2026-04-15T14:23:00Z",
  "source": "email_report|lead_request|exit_intent",
  "url_analyzed": "https://...",
  "score": { ...full... },
  "lead_segment": "warm",
  "contact": { "name": "...", "email": "...", "phone": "..." },
  "intent_signals": {
    "wants_plan": true,
    "wants_call_24h": false,
    "newsletter_optin": true
  },
  "ip": "...", "user_agent": "..."
}
```

Asta îți permite ulterior să exporți ușor către orice CRM (chiar și manual în Excel pentru început).

---

## Faza F — Trust și autoritate (2-3 zile)

Schimbări de copy/asset, nu de logică. Dar critică pentru conversie.

### F1. Bloc dovadă socială deasupra formularului URL
Înainte ca utilizatorul să introducă URL-ul, vede:
- 3-5 logo-uri clienți reali
- 1 quote scurt cu rezultat cuantificabil („+340% trafic organic în 6 luni — [nume client]”)
- Indicator volum: „[N] firme analizate până acum” (nr real, actualizat lunar manual sau automat din `storage/leads` count)

### F2. Mini case studies în pagina rezultatului
La finalul Secțiunii 2 (plan acțiune), un slider/carusel cu 3 case studies scurte:
- Imagine before/after sau screenshot scor
- 1-2 propoziții situație
- Rezultatul în cifre
- Link „Vezi studiul complet” (link spre articolul de blog respectiv din site — există deja: „Poți garanta un site care aduce clienți?”, „Cum am dublat traficul pentru X”, etc.)

### F3. Bio scurt al consultantului
Lângă CTA primar, un mic bloc cu:
- Foto profesională
- Nume + rol („Consultant Senior SEO, 8 ani experiență”)
- 1 propoziție: „Eu personal mă uit pe fiecare cerere de ofertă în maxim 24h.”

Personalizarea umană crește conversia pe lead capture cu mult mai mult decât orice optimizare de buton.

### F4. Indicatori micro-trust
În subsolul tool-ului, bandă cu:
- „[N] check-uri tehnice analizate”
- „Audit gratuit, fără înregistrare obligatorie”
- „Datele tale rămân ale tale — politică de confidențialitate”
- „GDPR compliant”

---

## Faza G — UX hygiene (2-3 zile)

### G1. URL input îmbunătățit
- Auto-prepend `https://` dacă lipsește.
- Validare format înainte de submit (regex URL simplu).
- Opțional: ping HEAD rapid client-side pentru a detecta URL inexistent înainte de a porni audit-ul (poate fi blocat de CORS, se acceptă fail silently).
- Mesaje de eroare specifice:
  - „URL-ul nu pare valid. Asigură-te că include domeniul complet.”
  - „Site-ul pare offline. Vrei să încerci alt URL?”
  - „Site-ul blochează analiza automată. Programează un audit manual →” (acesta e CTA mascat)

### G2. Indicator de progres în timpul audit-ului
În loc de spinner generic:
- „Verificăm conținutul paginii…” (0-30%)
- „Analizăm structura tehnică…” (30-60%)
- „Calculăm scorul SEO…” (60-85%)
- „Pregătim recomandările…” (85-100%)

Folosește timestamps progresive bazate pe estimarea medie de timp (handoff-ul nu menționează, dar în practică e 5-15s). Nu trebuie să fie progres real — perceput ca progres real e suficient. Ține utilizatorul angajat.

### G3. Recovery elegant pe eșec
Dacă render eșuează (Cloudflare timeout, HTTP 403, blocare robots, etc.):
- Mesaj clar: „Nu am putut analiza automat acest site. De obicei, asta înseamnă că site-ul folosește protecție anti-bot, are probleme de configurare, sau e prea complex pentru analiza rapidă.”
- CTA: „Solicită un audit manual gratuit” → deschide form-ul de lead capture cu mesaj prepopulat „URL [X] nu a putut fi analizat automat. Vă rog să-mi trimiteți un audit manual.”

Astfel un eșec tehnic devine oportunitate de lead. **Acesta e probabil unul dintre cele mai valoroase lead-uri** — utilizatorul a venit cu intenție și a fost frustrat, e maxim de motivat să fie ajutat.

### G4. Mobile UX
- Donut score pe mobile: variantă compactă (orizontal cu metric mare lângă, nu donut full)
- Sticky CTA bottom bar (vezi D2)
- Test cross-browser pe mobile (iOS Safari și Chrome Android obligatoriu)

---

## Sincronizare check-uri — disciplina constantă

Pentru orice check nou adăugat sau modificat, lista oficială de fișiere de sincronizat se extinde la **5 locuri** după acest refactor:

1. `app/src/ArticleScorer.php` — logica scor
2. `app/src/Advice.php` — label, recomandare, **business_impact_text**, **business_impact_magnitude**, **related_service**, **fix_complexity**
3. `app/src/BusinessImpactCalculator.php` — dacă afectează mapping-ul către segment
4. `app/src/EmailRenderer.php` — cum apare în Email A și notificările interne
5. `app/public/partials/result-business.php` + `result-action-plan.php` + `result-technical.php`

Recomandare: scrie un test simplu în CLI (`php tools/check-consistency.php`) care iterează prin lista de check IDs din `ArticleScorer.php` și verifică prezența lor în celelalte fișiere. Te scapă de bug-urile silent menționate în handoff (label-uri inconsistente, severități neacordate).

---

## Ordinea recomandată de execuție

Pentru cel mai mic risc și cea mai rapidă livrare incrementală:

| Săpt. | Ce livrezi | Vizibil pentru user? |
|---|---|---|
| 1 | Faza A completă | Nu |
| 2 | Faza B (audit direct) + Faza F (trust) | Da, parțial |
| 3 | Faza C1+C2+C3 (backend business impact) | Nu |
| 4 | Faza C4 (UI nou raport) + Faza C5 (local integrat) | Da, total |
| 5 | Faza D (CTA + lead form) | Da |
| 6 | Faza E (email A+B) | Da, după 24h |
| 7 | Faza G (UX hygiene) + bug fixes acumulate | Da |
| 8 | Buffer / polish / copy review | — |

**Total: 6-8 săptămâni de muncă concentrată part-time.** Cu un dev full-time, jumătate.

---

## Riscuri tehnice și mitigation

| Risc | Probabilitate | Impact | Mitigation |
|---|---|---|---|
| Refactor-ul `view-home.php` rupe JS-ul existent | Mediu | Mare | Păstrează ID-uri DOM identice, testează după fiecare partial extras |
| Estimările financiare par lipsite de credibilitate | Mediu | Mare | Cifrele se calibrează pe research real; afișezi interval, nu valoare; adaugă disclaimer clar |
| Automation-ul MailPoet pentru Email B nu pornește | Mic | Mediu | Verificare manuală în WordPress că userul ajunge în lista `WP_PLAN_LIST_NAME`; logging pe răspunsul de subscribe |
| Lead form spam (boți) | Mare | Mic | Honeypot + rate limit + validare telefon RO |
| GDPR — profile + lead capture | Mic | Mare | Privacy policy update; menționezi explicit ce face cu datele; menține opțiune skip |
| CTA-uri prea agresive percepute ca spam | Mediu | Mare | Copy review după primele 2 săpt; A/B testing pe ton dacă e posibil |

---

## Ce NU e inclus în acest plan (intenționat)

- **Tracking și analytics** — am rezervat-o pentru discuție separată, după implementare. Tot ce e nevoie pentru tracking se va adăuga la sfârșit ca strat separat (data attributes pe CTA, event triggers la momente cheie). Nu intersectează arhitectura.
- **Optimizări SEO ale paginii** (title rewrite, meta description, schema FAQ) — astea vin după ce vezi conversia tool-ului, ca să poți măsura impactul lor.
- **Conținut adițional** (FAQ extins, case studies noi, articol de blog complementar) — sunt pârghii ulterioare, nu sunt blocante pentru funcționarea tool-ului.
- **Integrare CRM** — schema JSON din Faza E permite export manual către orice CRM. Integrarea automată este următorul pas, după ce ai volum care o justifică.
- **Refactor agresiv backend (microservicii, queue local pentru nurture, etc.)** — nu e nevoie. PHP procedural cu structură clară + MailPoet pentru follow-up este suficient pentru volumul așteptat.
