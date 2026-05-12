# Plan: Stocare Rapoarte si Analytics SEO Pentru Audit SEO Gratuit

## Summary

Tool-ul ramane o micro-aplicatie PHP separata de WordPress, dar va primi o baza de date MySQL/MariaDB dedicata in cPanel, separata de baza WordPress. Rapoartele complete raman salvate ca snapshot-uri JSON pentru afisare, PDF si debug, iar baza noua devine sursa pentru utilizatori, istoricul rapoartelor, statistici, conversii si date agregate pentru articole/studii.

Decizia finala: **MySQL dedicat pentru analytics + snapshot JSON temporar pentru raport complet + date publice doar agregate/anonymizate**.

## Key Changes

- Adaugam conexiune PDO MySQL configurata prin `.env`:
  - `ANALYTICS_ENABLED=1`
  - `ANALYTICS_DB_HOST=localhost`
  - `ANALYTICS_DB_NAME=cpaneluser_auditseo`
  - `ANALYTICS_DB_USER=cpaneluser_auditseo_user`
  - `ANALYTICS_DB_PASS=...`
  - `ANALYTICS_DB_CHARSET=utf8mb4`
  - `ANALYTICS_HASH_SALT=secret-lung-random`
- Cream `AnalyticsRepository` care salveaza:
  - raportul sumarizat dupa `/api/score`;
  - check-urile individuale;
  - userul/lead-ul dupa `/api/email-report` si `/api/lead-request`;
  - evenimentele funnel: audit generat, email trimis, raport deblocat, PDF descarcat, cerere lead.
- Nu incarcam WordPress in tool si nu folosim tabele WordPress. WordPress ramane doar site principal si, eventual, destinatie pentru publicarea articolelor.

## Database Model

Tabele noi in baza dedicata:

### `audit_users`

- `id`
- `created_at`
- `updated_at`
- `email_hash`
- `email_encrypted` optional, doar daca vrem identificare interna
- `first_name`
- `last_seen_at`
- `reports_count`
- `leads_count`

### `audit_reports`

- `id`
- `report_token`
- `user_id`
- `created_at`
- `url_analyzed`
- `url_hash`
- `domain`
- `domain_hash`
- `context`
- `source`
- `js_rendered`
- `score_total`
- `score_global`
- `score_local_points`
- `score_local_percent`
- `score_content`
- `score_structure`
- `score_signals`
- `score_locale`
- `checks_total`
- `checks_pass`
- `checks_warn`
- `checks_fail`
- `checks_priority`
- `opportunity_score`
- `recommended_service_key`
- `lead_segment`
- `schema_version`

### `audit_report_checks`

- `id`
- `report_id`
- `check_id`
- `ok`
- `severity`
- `business_impact_magnitude`
- `fix_complexity`
- `related_service`
- `note_sample` limitat la 255 caractere pentru debug intern

### `audit_events`

- `id`
- `report_id`
- `user_id`
- `created_at`
- `event_type`
- `source`
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `ip_hash`
- `user_agent_hash`

### `audit_daily_stats`

- `stat_date`
- `context`
- `check_id`
- `reports_count`
- `fail_count`
- `warn_count`
- `pass_count`
- `high_impact_count`
- `easy_fix_count`

### `audit_monthly_stats`

Aceeasi logica precum `audit_daily_stats`, dar agregata lunar pentru articole si studii.

Indexuri obligatorii:

- `report_token` unic;
- `created_at`;
- `domain_hash`;
- `user_id`;
- `check_id + severity`;
- `stat_date + context + check_id`.

## Data Flow

- La finalul `/api/score`, imediat dupa `ReportStore->create($snapshot)`, salvam sumarul in `audit_reports` si toate check-urile in `audit_report_checks`.
- La `/api/email-report`, cautam/cream `audit_users` dupa `email_hash`, legam raportul de user si inregistram evenimentele `email_submitted` si `report_unlocked`.
- La `/api/lead-request`, legam lead-ul de user/raport si inregistram `lead_request`.
- La `/api/report-pdf`, dupa validarea unlock-ului, inregistram `pdf_download`.
- Adaugam script CLI `tools/rebuild-analytics-stats.php` care regenereaza `audit_daily_stats` si `audit_monthly_stats` din tabelele brute.
- Adaugam script CLI `tools/export-editorial-stats.php` care exporta JSON/CSV cu:
  - top probleme SEO;
  - top probleme high-impact;
  - top quick wins;
  - diferente `article` vs `local`;
  - scor mediu pe luna;
  - rata audit -> email -> lead.

## Privacy And Retention

- Rapoartele JSON complete raman cu retentie limitata: `REPORT_RETENTION_DAYS=90`.
- Lead-urile/contactele raman cu retentie: `LEAD_RETENTION_DAYS=180` sau conform politicii publice.
- Analytics brut in MySQL se pastreaza 24 luni.
- Agregarile lunare pot fi pastrate nelimitat.
- Pentru articole publice folosim doar agregari: nici email, nici URL complet, nici domeniu fara acord explicit.
- `ip_hash`, `user_agent_hash`, `email_hash`, `url_hash`, `domain_hash` se calculeaza cu `ANALYTICS_HASH_SALT`, nu cu hash simplu nesarat.

## Implementation Notes

- Storage-ul file-based existent nu se elimina. Ramane necesar pentru raport complet, PDF, access token si rehidratare.
- `STORAGE_DIR` trebuie mutat in afara `public_html`, de exemplu `/home/cpaneluser/audit-seo-storage`.
- Daca hostingul nu permite asta, se pune `.htaccess` cu deny total in orice folder de storage.
- Baza de date se creeaza din cPanel, separat de WordPress: `cpaneluser_auditseo`.
- Implementarea trebuie sa fie fail-soft: daca analytics DB cade, auditul si lead-ul continua sa functioneze, iar eroarea se logheaza.

## Test Plan

- Ruleaza `php tools/check-consistency.php` si confirma ca scoring-ul existent nu s-a rupt.
- Test `/api/score`:
  - creeaza JSON report;
  - insereaza un rand in `audit_reports`;
  - insereaza check-urile in `audit_report_checks`.
- Test `/api/email-report`:
  - creeaza/actualizeaza `audit_users`;
  - leaga `user_id` de raport;
  - inregistreaza evenimentele corecte.
- Test `/api/lead-request`:
  - salveaza lead-ul existent;
  - inregistreaza conversia in `audit_events`.
- Test `/api/report-pdf`:
  - fara unlock ramane `403`;
  - cu unlock descarca PDF si logheaza `pdf_download`.
- Test script stats:
  - regenereaza daily/monthly stats;
  - exportul editorial contine top probleme si rate de conversie.
- Test failure:
  - dezactivezi temporar DB credentials;
  - auditul trebuie sa raspunda in continuare, doar analytics sa fie logat ca esuat.

## Assumptions

- Folosim o baza MySQL separata in cPanel, nu baza WordPress.
- Tool-ul ramane accesibil la `novaweb.ro/tools/audit-seo-gratuit`.
- Nu construim conturi de utilizator in v1; "user" inseamna identitate dedusa din email dupa unlock/contact.
- Nu publicam domenii sau exemple nominale in articole fara acord explicit.
- Prima versiune prioritizeaza statistici SEO si funnel lead, nu dashboard vizual in admin.
