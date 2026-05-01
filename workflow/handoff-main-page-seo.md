# Handoff pentru Claude: Texte Pagina Principala + SEO

Scop: acest document este un shortcut pentru modificarea textelor din pagina principala si a metadatelor SEO fara sa atasezi toate partialele PHP separat.

Proiectul este un micro-app PHP fara framework pentru tool-ul "Audit SEO gratuit" NovaWeb. Pagina principala nu este intr-un singur fisier mare; este compusa dintr-un shell si partiale PHP.

## 1. Fisierul principal

Punctul principal pentru pagina este:

`app/public/view-home.php`

Rol:
- construieste documentul HTML;
- include `partials/seo-meta.php` in `<head>`;
- include partialele din landing;
- include partialele pentru raport/workspace;
- defineste configuratia JS globala `window.NOVA_AUDIT`;
- incarca JS-ul principal `app/public/assets/audit-app.js`.

Structura relevanta din `view-home.php`:

```php
<?php require __DIR__ . '/partials/seo-meta.php'; ?>
<?php require __DIR__ . '/partials/landing-header.php'; ?>
<?php require __DIR__ . '/partials/landing-hero.php'; ?>
<?php require __DIR__ . '/partials/landing-how.php'; ?>
<?php require __DIR__ . '/partials/landing-interpret.php'; ?>
<?php require __DIR__ . '/partials/landing-checklist.php'; ?>
<?php require __DIR__ . '/partials/landing-local-recs.php'; ?>
<?php require __DIR__ . '/partials/landing-cta-final.php'; ?>
<?php require __DIR__ . '/partials/report-contact.php'; ?>
<?php require __DIR__ . '/partials/landing-faq.php'; ?>
<?php require __DIR__ . '/partials/landing-footer.php'; ?>
```

## 2. Unde se modifica SEO-ul

Fisierul care genereaza tagurile SEO:

`app/public/partials/seo-meta.php`

Acest fisier outputeaza:
- `<title>`;
- `<meta name="description">`;
- `<meta name="robots">`;
- canonical;
- hreflang;
- favicon;
- CSS;
- Open Graph;
- Twitter Card;
- JSON-LD schema pentru landing.

Important: pentru landing, title si meta description NU sunt scrise direct in `seo-meta.php`. Ele vin din:

`app/src/Config.php`

Functiile importante:

```php
public static function toolTitle(): string
{
    return 'Audit SEO gratuit: scor 0-100 si raport | NovaWeb';
}

public static function toolDescription(): string
{
    return 'Introdu URL-ul si primesti instant un audit SEO gratuit: scor 0-100, prioritati clare, recomandari de business si raport pe email.';
}
```

Daca vrei sa schimbi title/meta description principal pentru Google, modifica aceste doua functii.

Unde sunt folosite:
- `<title>` foloseste `$pageTitle`;
- `<meta name="description">` foloseste `$pageDescription`;
- `og:title` foloseste `Config::toolTitle()`;
- `og:description` foloseste `Config::toolDescription()`;
- `twitter:title` foloseste `Config::toolTitle()`;
- `twitter:description` foloseste `Config::toolDescription()`;
- JSON-LD `WebPage.name` si `WebPage.description` folosesc aceleasi functii.

Observatie: exista doua moduri:
- `landing`: index/follow, title si description publice;
- `workspace`: raport privat, `noindex, nofollow`.

Pentru raport privat, valorile sunt hardcodate in `seo-meta.php`:

```php
$pageTitle = 'Raport privat | NovaWeb Audit SEO';
$pageDescription = 'Raportul tau privat de audit este afisat in NovaWeb Audit SEO.';
```

## 3. Open Graph si imagine social

Tot in:

`app/public/partials/seo-meta.php`

Imaginea OG folosita:

```php
Config::canonicalUrl('raport-preview-og.png')
```

Fisierul fizic este in root:

`raport-preview-og.png`

Alt text curent:

```html
Audit SEO gratuit - scor 0-100 si raport
```

Daca se schimba pozitionarea/mesajul principal al tool-ului, verifica si:
- `og:title`;
- `og:description`;
- `twitter:title`;
- `twitter:description`;
- `og:image:alt`;
- imaginea `raport-preview-og.png`, daca promisiunea vizuala nu mai corespunde.

## 4. Schema JSON-LD

Schema este generata in:

`app/public/partials/seo-meta.php`

Doar pe landing, nu pe workspace.

Contine:
- `Organization`;
- `WebSite`;
- `WebPage`;
- `SoftwareApplication`;
- `FAQPage`.

FAQPage foloseste:

```php
Config::faqItems()
```

Deci, daca modifici intrebarile sau raspunsurile din FAQ, schema se actualizeaza automat, pentru ca foloseste aceeasi sursa ca sectiunea vizibila de FAQ.

## 5. Unde sunt textele principale din landing

### Header / meniu

Fisier:

`app/public/partials/landing-header.php`

Contine:
- logo;
- linkuri meniu desktop/mobile;
- CTA "Servicii optimizare SEO".

Texte relevante:
- `Audit`;
- `Raport`;
- `Interpretare scor`;
- `Ce verifica`;
- `FAQ`;
- `Servicii optimizare SEO`.

### Hero / primul ecran

Fisier:

`app/public/partials/landing-hero.php`

Este cel mai important fisier pentru copy-ul principal.

Contine:
- eyebrow: "GRATUIT · FARA CONT · RAPORT IN ~60 DE SECUNDE";
- H1;
- paragraful principal;
- formularul URL;
- taburi "Pagina standard" / "Pagina locala";
- hint despre context;
- preview scor;
- trust pills.

Zone importante de editat:
- H1:

```php
<h1>
  Afla exact ce impiedica<br>
  pagina ta sa apara in
  <span class="underline"><span class="accent-word">Google</span></span>
</h1>
```

- lead paragraph:

```php
Introdu URL-ul si primesti un raport structurat: scor 0-100,
lista problemelor in ordinea impactului si ce se poate face concret pentru fiecare.
```

- CTA button:

```php
Calculeaza scor
```

- input placeholder:

```php
novaweb.ro sau https://...
```

### Pasii de utilizare

Fisier:

`app/public/partials/landing-how.php`

Contine sectiunea:
- "Audit SEO in 3 pasi, fara cont";
- "Introdu URL-ul";
- "Alege tipul paginii";
- "Primesti scor + checklist".

### Interpretare scor

Fisier:

`app/public/partials/landing-interpret.php`

Contine:
- "Cum interpretezi scorul SEO (0-100)";
- explicatia ca scorul este orientat pe SEO on-page si indexare, nu PageSpeed;
- intervalele de scor;
- prioritati rapide.

### Checklist / ce verifica auditul

Fisier:

`app/public/partials/landing-checklist.php`

Contine:
- "Ce verifica auditul SEO";
- texte despre title, structura, indexare, continut;
- grupuri de verificari on-page si indexare/metadate.

### SEO local

Fisier:

`app/public/partials/landing-local-recs.php`

Contine:
- "Audit SEO local";
- semnale locale;
- recomandari pentru pagini locale.

### CTA final

Fisier:

`app/public/partials/landing-cta-final.php`

Contine:
- "Vrei sa ne uitam impreuna peste raportul tau?";
- explicatia despre revizuire umana;
- butonul "Cere revizuirea raportului in 24h".

### Formular contact / lead

Fisier:

`app/public/partials/report-contact.php`

Contine:
- "Ai intrebari pe raport sau vrei sa ne implicam?";
- textul formularului;
- campuri nume/email/telefon/mesaj;
- nota de confidentialitate;
- buton "Trimite".

### FAQ vizibil

Fisier:

`app/public/partials/landing-faq.php`

Important: acest fisier NU contine intrebarile si raspunsurile efective. El doar le afiseaza din:

`Config::faqItems()`

Sursa reala pentru FAQ:

`app/src/Config.php`

Functia:

```php
public static function faqItems(): array
```

### Footer

Fisier:

`app/public/partials/landing-footer.php`

Contine:
- brand/footer;
- link catre servicii SEO;
- linkuri de footer.

## 6. Texte/config centralizate in Config.php

Fisier:

`app/src/Config.php`

Contine mai multe texte folosite in landing si funnel:

```php
Config::toolTitle()
Config::toolDescription()
Config::serviceCatalog()
Config::consultant()
Config::testimonials()
Config::logos()
Config::caseStudies()
Config::faqItems()
Config::microTrust()
```

Cele mai importante pentru aceasta cerere:
- `toolTitle()` = title SEO principal;
- `toolDescription()` = meta description principala;
- `faqItems()` = intrebari si raspunsuri FAQ, folosite si in schema;
- `serviceCatalog()` = nume servicii si CTA-uri pentru recomandari;
- `consultant()` = texte despre consultant;
- `testimonials()` = testimoniale;
- `logos()` = industria/logo strip;
- `caseStudies()` = studii de caz;
- `microTrust()` = trust pills/argumente scurte.

## 7. Partialele care exista, dar nu toate sunt active in pagina curenta

In `app/public/partials/` exista mai multe fisiere `landing-*`, dar nu toate sunt incluse in `view-home.php`.

Active in pagina principala curenta:
- `landing-header.php`;
- `landing-hero.php`;
- `landing-how.php`;
- `landing-interpret.php`;
- `landing-checklist.php`;
- `landing-local-recs.php`;
- `landing-cta-final.php`;
- `landing-faq.php`;
- `landing-footer.php`.

Exista si partiale care pot fi vechi/neincluse direct in fluxul curent, de exemplu:
- `landing-checks.php`;
- `landing-cta-band.php`;
- `landing-example.php`;
- `landing-explainer.php`;
- `landing-proof.php`;
- `landing-trust.php`.

Nu modifica aceste fisiere presupunand ca sunt live fara sa verifici intai daca sunt incluse in `view-home.php`.

## 8. CSS si JS relevante

Pentru copy si SEO nu ar trebui sa fie nevoie de CSS/JS, dar daca se schimba lungimi mari de texte si se strica layoutul:

CSS principal landing:

`app/public/assets/landing.css`

CSS baza/componente:

`app/public/assets/base.css`
`app/public/assets/components.css`
`app/public/assets/design.css`

JS principal:

`app/public/assets/audit-app.js`

Atentie: textele dinamice din flow-ul raportului pot fi si in JS, nu doar in partiale PHP.

## 9. Recomandare pentru Claude

Pentru modificari de copy/SEO, ordinea buna este:

1. Modifica `Config::toolTitle()` si `Config::toolDescription()` in `app/src/Config.php`.
2. Modifica H1, lead, CTA si trust pills in `app/public/partials/landing-hero.php`.
3. Modifica sectiunile secundare active:
   - `landing-how.php`;
   - `landing-interpret.php`;
   - `landing-checklist.php`;
   - `landing-local-recs.php`;
   - `landing-cta-final.php`;
   - `report-contact.php`.
4. Modifica FAQ in `Config::faqItems()`, nu in `landing-faq.php`.
5. Verifica `seo-meta.php` doar daca trebuie schimbate:
   - robots;
   - canonical/hreflang;
   - Open Graph image;
   - schema JSON-LD;
   - title/description pentru workspace privat.
6. Nu modifica partiale neincluse fara sa verifici `view-home.php`.

## 10. Observatie despre diacritice/encoding

Fisierul proiectului foloseste texte romanesti. Pastreaza fisierele in UTF-8. Daca vezi caractere de tip mojibake in terminal, verifica editorul/encodingul inainte sa faci inlocuiri globale.

## 11. Comanda utila de verificare

Pentru a vedea rapid partialele active:

```powershell
Select-String -Path app\public\view-home.php -Pattern "landing-|seo-meta|report-contact"
```

Pentru a gasi unde apare un text:

```powershell
rg -n "textul cautat" app\public app\src
```

