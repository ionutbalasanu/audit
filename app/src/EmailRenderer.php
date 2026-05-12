<?php
declare(strict_types=1);

final class EmailRenderer
{
    private const REPORT_CATEGORIES = [
        [
            'key' => 'content',
            'code' => 'CONTENT',
            'name' => 'Conținut & Media',
            'desc' => 'Lungime text, H1/H2/H3, liste, imagini și ALT-uri',
            'max' => 40,
            'score_source' => 'breakdown',
            'score_key' => 'content',
            'ids' => [
                'word_count_800',
                'intro_mentions_topic',
                'h1_single',
                'headings_hierarchy',
                'lists_tables',
                'images_in_body',
                'img_alt_ratio_80',
                'lazyload_images',
                'date_published',
                'date_modified',
                'author_visible_or_schema',
            ],
        ],
        [
            'key' => 'structure',
            'code' => 'STRUCTURE',
            'name' => 'Structură & Indexare',
            'desc' => 'Robots, canonical, URL, linkuri interne și externe',
            'max' => 25,
            'score_source' => 'breakdown',
            'score_key' => 'structure',
            'ids' => [
                'indexable',
                'canonical_present',
                'canonical_valid',
                'url_clean',
                'internal_links_present',
                'external_links_present',
                'meta_robots_ok',
                'html_valid',
                'image_dimensions_defined',
                'fonts_preload',
                'cls_risky_elements',
            ],
        ],
        [
            'key' => 'meta',
            'code' => 'META',
            'name' => 'Metadate & Rich Snippets',
            'desc' => 'Title, meta description, schema și Open Graph',
            'max' => 20,
            'score_source' => 'weighted',
            'score_key' => '',
            'ids' => [
                'title_length_ok',
                'meta_description_ok',
                'og_minimal',
                'schema_article_recommended',
                'faq_schema_present',
                'schema_breadcrumbs',
                'schema_image_required_fields',
            ],
        ],
        [
            'key' => 'local',
            'code' => 'LOCAL',
            'name' => 'Localizare RO',
            'desc' => 'Limbă, formate locale, hreflang și semnale locale de bază',
            'max' => 15,
            'score_source' => 'breakdown',
            'score_key' => 'locale',
            'ids' => [
                'lang_ro',
                'og_locale_or_inLanguage_ro',
                'date_format_ro',
                'hreflang_pairs',
            ],
        ],
        [
            'key' => 'seo-local',
            'code' => 'SEO-LOCAL',
            'name' => 'SEO local',
            'desc' => 'LocalBusiness schema, hartă, reviews și click-to-call',
            'max' => 30,
            'score_source' => 'local_points',
            'score_key' => 'points',
            'ids' => [
                'local_tel_click',
                'local_tel_prefix_local',
                'local_address_visible',
                'local_directions_link',
                'local_opening_hours',
                'local_schema_localbusiness',
                'local_schema_postal',
                'local_schema_tel',
                'local_schema_geo',
                'local_schema_sameas',
                'local_schema_area',
                'local_schema_rating',
                'local_city_detected',
                'local_city_in_title',
                'local_city_in_h1',
                'local_city_in_slug',
                'local_city_in_intro',
                'local_map_embed',
                'local_alt_has_city',
                'local_locator',
                'local_whatsapp',
            ],
        ],
    ];

    private const IMPACT_BUCKETS = [
        ['key' => 'high', 'label' => 'Impact mare'],
        ['key' => 'med', 'label' => 'Impact mediu'],
        ['key' => 'low', 'label' => 'Impact redus'],
    ];

    public static function renderReportHtml(string $url, array $snapshot): string
    {
        $model = self::buildReportModel($url, $snapshot);

        ob_start();
        ?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no">
  <title>Raport SEO complet</title>
  <style>
    a[x-apple-data-detectors],
    #MessageViewBody a,
    u + .email-body a {
      color: inherit !important;
      text-decoration: none !important;
      font: inherit !important;
    }

    @media screen and (max-width: 640px) {
      .email-shell {
        width: 100% !important;
      }

      .hero-score-cell,
      .hero-categories-cell,
      .cta-copy-cell,
      .cta-actions-cell {
        display: block !important;
        width: 100% !important;
      }

      .category-grid-cell {
        display: block !important;
        width: 100% !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
      }

      .hero-score-cell {
        padding-right: 18px !important;
        padding-bottom: 12px !important;
      }

      .score-orb {
        width: 152px !important;
        height: 152px !important;
      }

      .hero-domain {
        font-size: 30px !important;
        line-height: 36px !important;
      }

      .score-value {
        font-size: 60px !important;
        line-height: 62px !important;
      }

      .cta-copy-cell {
        padding-right: 0 !important;
        padding-bottom: 18px !important;
      }

      .cta-actions-cell {
        padding-left: 0 !important;
      }

      .cta-actions-wrap {
        text-align: left !important;
      }

      .cta-button-cell {
        display: block !important;
        width: 100% !important;
        padding-bottom: 10px !important;
      }

      .cta-action-link {
        width: 100% !important;
        box-sizing: border-box !important;
      }

      .cta-action-table {
        width: 100% !important;
      }
    }
  </style>
</head>
<body class="email-body" style="margin:0;padding:0;background-color:#f6efe6;font-family:Arial,sans-serif;color:#1f2937;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;visibility:hidden;font-size:1px;line-height:1px;color:#f6efe6;">
    <?php echo self::e($model['preheader']); ?>
  </div>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;background-color:#f6efe6;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" class="email-shell" style="width:100%;max-width:720px;background-color:#ffffff;border:1px solid #eadfce;border-radius:24px;">
          <tr>
            <td style="padding:28px 28px 0 28px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #eadfce;background-color:#fffaf5;border-radius:24px;">
                <tr>
                  <td class="hero-score-cell" width="216" valign="top" style="padding:20px 12px 20px 20px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                      <tr>
                        <td align="center">
                          <div class="score-orb" style="width:172px;height:172px;border:12px solid #f97316;border-radius:999px;background-color:#ffffff;text-align:center;">
                            <div class="score-value" style="padding-top:34px;font-size:66px;line-height:68px;font-weight:800;color:#f97316;"><?php echo self::e((string)$model['total']); ?></div>
                            <div style="margin-top:4px;font-size:16px;line-height:20px;color:#64748b;font-weight:700;">/ 100</div>
                            <div style="margin-top:14px;font-size:11px;line-height:15px;letter-spacing:0.22em;text-transform:uppercase;color:#64748b;font-weight:700;">Scor SEO</div>
                          </div>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding-top:14px;">
                          <table role="presentation" cellspacing="0" cellpadding="0" align="center" style="border:1px solid #eadfce;background-color:#ffffff;border-radius:999px;">
                            <tr>
                              <td style="padding:8px 12px;font-size:11px;line-height:16px;color:#475569;">
                                <span style="display:inline-block;width:8px;height:8px;border-radius:999px;background-color:#16a34a;vertical-align:middle;"></span>
                                <span style="margin-left:6px;"><?php echo self::e((string)$model['counts']['pass']); ?> reușite</span>
                                <span style="display:inline-block;width:8px;height:8px;border-radius:999px;background-color:#f59e0b;vertical-align:middle;margin-left:14px;"></span>
                                <span style="margin-left:6px;"><?php echo self::e((string)$model['counts']['warn']); ?> avertizări</span>
                                <span style="display:inline-block;width:8px;height:8px;border-radius:999px;background-color:#dc2626;vertical-align:middle;margin-left:14px;"></span>
                                <span style="margin-left:6px;"><?php echo self::e((string)$model['counts']['fail']); ?> nereușite</span>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td class="hero-categories-cell" valign="top" style="padding:20px 20px 20px 12px;">
                    <div style="font-size:12px;line-height:18px;letter-spacing:0.14em;text-transform:uppercase;color:#f97316;font-weight:700;">NovaWeb Audit SEO</div>
                    <div class="hero-domain" style="margin-top:8px;font-size:34px;line-height:38px;font-weight:800;color:#182235;">
                      <a href="<?php echo self::e($model['url']); ?>" style="color:#182235;text-decoration:none;"><?php echo self::e($model['domain']); ?></a>
                    </div>
                    <?php if ($model['title'] !== $model['domain']): ?>
                      <div style="margin-top:6px;font-size:15px;line-height:22px;color:#334155;"><?php echo self::e($model['title']); ?></div>
                    <?php endif; ?>
                    <div style="margin-top:8px;font-size:13px;line-height:20px;color:#64748b;">
                      <?php echo self::e($model['context_label']); ?> &middot; Generat la <?php echo self::e($model['created_label']); ?>
                    </div>
                    <div style="margin-top:4px;font-size:13px;line-height:20px;">
                      <a href="<?php echo self::e($model['url']); ?>" style="color:#ea580c;text-decoration:none;"><?php echo self::e($model['url']); ?></a>
                    </div>
                    <div style="margin-top:18px;font-size:15px;line-height:24px;color:#334155;">
                      <?php echo self::e($model['verdict']['summary']); ?>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td colspan="2" style="padding:0 20px 20px 20px;">
                    <div style="padding-top:6px;font-size:18px;line-height:24px;font-weight:700;color:#182235;">Distribuția scorului pe categorii</div>
                    <div style="margin-top:4px;font-size:13px;line-height:20px;color:#64748b;">Vezi rapid unde pierzi puncte</div>
                    <div style="margin-top:12px;">
                      <?php echo self::renderCategorySummaryGridHtml($model['categories']); ?>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:22px 28px 0 28px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="33.333%" valign="top" style="padding:0 8px 0 0;">
                    <?php echo self::renderMetricCardHtml((string)$model['counts']['pass'], 'Verificări OK', 'Verificări care nu necesită intervenție.', 'ok'); ?>
                  </td>
                  <td width="33.333%" valign="top" style="padding:0 4px 0 4px;">
                    <?php echo self::renderMetricCardHtml((string)$model['counts']['warn'], 'Avertizări', 'Verificări cu risc sau informație incompletă.', 'warn'); ?>
                  </td>
                  <td width="33.333%" valign="top" style="padding:0 0 0 8px;">
                    <?php echo self::renderMetricCardHtml((string)$model['counts']['fail'], 'Probleme', 'Blocaje și lipsuri care afectează raportul.', 'danger'); ?>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 28px 0 28px;">
              <div style="font-size:22px;line-height:28px;font-weight:700;color:#0f1c2e;">Probleme și recomandări</div>
              <div style="margin-top:6px;font-size:14px;line-height:21px;color:#64748b;">Fiecare problemă din snapshot are și un sfat de rezolvare, în ordinea impactului.</div>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 28px 0 28px;">
              <?php if ($model['problem_categories'] === []): ?>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d9f3e6;background-color:#effaf4;border-radius:18px;">
                  <tr>
                    <td style="padding:16px 18px;font-size:14px;line-height:22px;color:#166534;">
                      Nu există probleme individuale de listat în snapshotul curent. Punctele trecute cu succes rămân sumarizate mai sus.
                    </td>
                  </tr>
                </table>
              <?php else: ?>
                <?php foreach ($model['problem_categories'] as $category): ?>
                  <?php echo self::renderCategorySectionHtml($category); ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 28px 0 28px;">
              <div style="font-size:22px;line-height:28px;font-weight:700;color:#0f1c2e;">Pași de remediere prioritari</div>
              <div style="margin-top:6px;font-size:14px;line-height:21px;color:#64748b;">Primele acțiuni recomandate pe baza problemelor cu impact comercial.</div>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 28px 0 28px;">
              <?php foreach ($model['actions'] as $index => $action): ?>
                <?php echo self::renderActionCardHtml($action, $index + 1); ?>
              <?php endforeach; ?>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 28px 28px 28px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:linear-gradient(135deg,#0f1c2e 0%,#1a2540 100%);border-radius:22px;">
                <tr>
                  <td style="padding:30px 30px 28px 30px;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                      <tr>
                        <td class="cta-copy-cell" valign="top" style="padding:0 18px 0 0;">
                          <div style="display:inline-block;padding:6px 12px;border-radius:999px;background-color:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);font-size:11px;line-height:16px;letter-spacing:0.12em;text-transform:uppercase;color:#fdba74;font-weight:700;">Următorul pas</div>
                          <div style="margin-top:16px;font-size:20px;line-height:25px;font-weight:700;color:#ffffff;max-width:400px;">Vezi raportul complet</div>
                          <div style="margin-top:10px;font-size:13px;line-height:21px;color:rgba(255,255,255,0.76);max-width:390px;">
                            Vezi toate problemele detectate, scorurile pe categorii și recomandările de implementare într-un singur loc.
                          </div>
                        </td>
                        <td class="cta-actions-cell" width="260" valign="middle" style="padding:0 0 0 18px;">
                          <div class="cta-actions-wrap" style="text-align:right;">
                            <table role="presentation" cellspacing="0" cellpadding="0" align="right" class="cta-action-table" style="width:220px;">
                              <tr>
                                <td class="cta-button-cell" style="padding:0 0 10px 0;">
                                  <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                      <td bgcolor="#ffffff" style="border-radius:18px;color:#182235;font-size:14px;line-height:20px;font-weight:700;text-align:center;">
                                        <a class="cta-action-link" href="<?php echo self::e($model['report_url']); ?>" style="display:block;padding:13px 18px;color:#182235;text-decoration:none;text-align:center;">
                                          <span style="color:#182235;font-size:14px;line-height:20px;font-weight:700;">Deschide raportul</span>
                                        </a>
                                      </td>
                                    </tr>
                                  </table>
                                </td>
                              </tr>
                              <?php if ($model['service']['secondary_available']): ?>
                                <tr>
                                  <td class="cta-button-cell" style="padding:0 0 10px 0;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                      <tr>
                                        <td bgcolor="#2f3b56" style="border:1px solid #4a5671;border-radius:18px;color:#ffffff;font-size:14px;line-height:20px;font-weight:700;text-align:center;">
                                          <a class="cta-action-link" href="<?php echo self::e($model['service']['secondary_url']); ?>" style="display:block;padding:13px 18px;color:#ffffff;text-decoration:none;text-align:center;">
                                            <span style="color:#ffffff;font-size:14px;line-height:20px;font-weight:700;"><?php echo self::e($model['service']['secondary_label']); ?></span>
                                          </a>
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                              <?php endif; ?>
                            </table>
                            <div style="clear:both;"></div>
                          </div>
                          <?php if (!$model['service']['secondary_available']): ?>
                            <div style="margin-top:8px;font-size:13px;line-height:20px;color:#cdd7e3;">
                              Dacă vrei implementarea, răspunde direct la acest email.
                            </div>
                          <?php endif; ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:0 28px 28px 28px;">
              <div style="border-top:1px solid #eadfce;padding-top:16px;font-size:11px;line-height:18px;color:#64748b;">
                Acest mesaj conține raportul SEO solicitat. Pentru ștergerea datelor, dezabonare sau preferințe de comunicare,
                folosește pagina de confidențialitate:
                <a href="<?php echo self::e($model['privacy_url']); ?>" style="color:#ea580c;text-decoration:underline;"><?php echo self::e($model['privacy_url']); ?></a>.
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
<?php
        return (string) ob_get_clean();
    }

    public static function renderReportText(string $url, array $snapshot): string
    {
        $model = self::buildReportModel($url, $snapshot);

        $lines = [
            'Raport complet NovaWeb Audit SEO',
            'Domeniu: ' . $model['domain'],
            'URL: ' . $url,
            'Tip audit: ' . $model['context_label'],
            'Generat la: ' . $model['created_label'],
            'Scor total: ' . $model['total'] . '/100',
            'Verdict: ' . $model['verdict']['label'] . ' - ' . $model['verdict']['summary'],
            'Verificari OK: ' . $model['counts']['pass'],
            'Avertizari: ' . $model['counts']['warn'],
            'Probleme: ' . $model['counts']['fail'],
        ];

        $lines[] = 'Raport in browser: ' . $model['report_url'];
        $lines[] = 'Serviciu recomandat: ' . $model['service']['name'];
        if ($model['service']['available']) {
            $lines[] = 'Link serviciu: ' . $model['service']['url'];
        }

        $lines[] = '';
        $lines[] = 'Scor pe categorii:';
        foreach ($model['categories'] as $category) {
            $lines[] = sprintf(
                '- %s: %d/%d | probleme %d | OK %d',
                $category['name'],
                $category['score'],
                $category['max'],
                $category['problem_count'],
                $category['pass_count']
            );
        }

        $lines[] = '';
        $lines[] = 'Probleme și recomandări:';
        if ($model['problem_categories'] === []) {
            $lines[] = '- Nu există probleme individuale de listat în snapshotul curent.';
        } else {
            foreach ($model['problem_categories'] as $category) {
                $lines[] = '';
                $lines[] = '[' . $category['code'] . '] ' . $category['name'] . ' - ' . $category['score'] . '/' . $category['max'];
                foreach ($category['groups'] as $group) {
                    $lines[] = '  ' . $group['label'] . ':';
                    foreach ($group['items'] as $issue) {
                        $lines[] = '  - ' . $issue['label'] . ' [' . $issue['severity_label'] . ' | ' . $issue['impact_label'] . ']';
                        $lines[] = '    Dovada: ' . $issue['evidence'];
                        $lines[] = '    Impact: ' . $issue['impact_text'];
                        $lines[] = '    Sfat: ' . $issue['advice'];
                    }
                }
            }
        }

        $lines[] = '';
        $lines[] = 'Pași de remediere:';
        foreach ($model['actions'] as $index => $action) {
            $lines[] = ($index + 1) . '. ' . $action['title'];
            $lines[] = '   De ce: ' . $action['why'];
            $lines[] = '   Cum: ' . $action['how'];
            $lines[] = '   Durata: ' . $action['duration'] . ' | Owner: ' . $action['owner'];
        }

        $lines[] = '';
        $lines[] = 'Raport complet in browser: ' . $model['report_url'];
        $lines[] = 'Preferinte, dezabonare si stergerea datelor: ' . $model['privacy_url'];

        return implode(PHP_EOL, $lines);
    }

    public static function renderPlanHtml(string $url, array $snapshot, string $firstName = ''): string
    {
        $business = (array)($snapshot['business'] ?? []);
        $actions = array_slice((array)($business['action_plan'] ?? []), 0, 5);
        $token = (string)($snapshot['report_token'] ?? '');
        $reportUrl = $token !== '' ? self::reportLink($token) : Config::canonicalUrl('#workspaceShell');
        $greeting = trim($firstName) !== '' ? 'Salut, ' . trim($firstName) . '!' : 'Salut!';

        ob_start();
        ?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <title>Sfaturi de remediere SEO</title>
</head>
<body style="margin:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:720px;background:#ffffff;border-radius:18px;overflow:hidden;">
          <tr>
            <td style="padding:28px;background:#082f49;color:#ecfeff;">
              <div style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#67e8f9;">Sfaturi de remediere SEO</div>
              <h1 style="margin:12px 0 8px;font-size:30px;"><?php echo self::e($greeting); ?></h1>
              <p style="margin:0;color:#bae6fd;">Am pregătit recomandările prioritare pentru <?php echo self::e($url); ?>.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 28px;">
              <h2 style="margin:0 0 16px;font-size:20px;">Pași de remediere prioritari</h2>
              <ol style="margin:0;padding-left:20px;">
                <?php foreach ($actions as $action): ?>
                  <li style="margin-bottom:16px;line-height:1.6;">
                    <strong><?php echo self::e((string)($action['title'] ?? '')); ?></strong><br>
                    <?php echo self::e((string)($action['why'] ?? '')); ?><br>
                    <span style="color:#475569;">Cum: <?php echo self::e((string)($action['how'] ?? '')); ?></span><br>
                    <span style="color:#475569;">Durata: <?php echo self::e((string)($action['duration'] ?? '')); ?> | Cine: <?php echo self::e((string)($action['owner'] ?? '')); ?></span>
                  </li>
                <?php endforeach; ?>
              </ol>

              <div style="margin-top:24px;padding:18px;border-radius:16px;background:#0f172a;color:#f8fafc;">
                <p style="margin:0 0 12px;">Dacă vrei să implementăm noi aceste recomandări, răspunde la acest email sau folosește cererea din raport.</p>
                <a href="<?php echo self::e($reportUrl); ?>" style="display:inline-block;padding:12px 18px;border-radius:999px;background:#22c55e;color:#052e16;font-weight:700;text-decoration:none;">Deschide raportul</a>
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
<?php
        return (string) ob_get_clean();
    }

    public static function renderPlanText(string $url, array $snapshot, string $firstName = ''): string
    {
        $business = (array)($snapshot['business'] ?? []);
        $actions = array_slice((array)($business['action_plan'] ?? []), 0, 5);
        $token = (string)($snapshot['report_token'] ?? '');
        $reportUrl = $token !== '' ? self::reportLink($token) : Config::canonicalUrl('#workspaceShell');
        $lines = [$firstName !== '' ? ('Salut, ' . $firstName . '!') : 'Salut!'];
        $lines[] = 'Sfaturi de remediere SEO pentru ' . $url;
        $lines[] = '';

        foreach ($actions as $index => $action) {
            $lines[] = ($index + 1) . '. ' . (string)($action['title'] ?? '');
            $lines[] = '   De ce: ' . (string)($action['why'] ?? '');
            $lines[] = '   Cum: ' . (string)($action['how'] ?? '');
            $lines[] = '   Durata: ' . (string)($action['duration'] ?? '') . ' | Cine: ' . (string)($action['owner'] ?? '');
        }

        $lines[] = '';
        $lines[] = 'Raport complet: ' . $reportUrl;
        return implode(PHP_EOL, $lines);
    }

    public static function renderLeadNotificationHtml(array $lead): string
    {
        $contact = (array)($lead['contact'] ?? []);
        $business = (array)($lead['business'] ?? []);
        $issues = array_slice((array)($business['top_business_issues'] ?? []), 0, 3);
        $phone = trim((string)($contact['phone'] ?? ''));
        $url = trim((string)($lead['url_analyzed'] ?? ''));
        $source = trim((string)($lead['source'] ?? 'lead_request'));

        ob_start();
        ?>
<h1>Lead nou din audit-seo-gratuit</h1>
<p><strong>Nume:</strong> <?php echo self::e((string)($contact['name'] ?? '')); ?></p>
<p><strong>Email:</strong> <?php echo self::e((string)($contact['email'] ?? '')); ?></p>
<p><strong>Telefon:</strong> <?php echo self::e($phone !== '' ? $phone : '-'); ?></p>
<p><strong>Sursa:</strong> <?php echo self::e($source); ?></p>
<p><strong>URL:</strong> <?php echo self::e($url !== '' ? $url : '-'); ?></p>
<p><strong>Segment:</strong> <?php echo self::e((string)($lead['lead_segment'] ?? '')); ?></p>
<p><strong>Urgent:</strong> <?php echo !empty($lead['intent_signals']['wants_call_24h']) ? 'Da' : 'Nu'; ?></p>
<?php if ($issues !== []): ?>
<h2>Top probleme</h2>
<ul>
  <?php foreach ($issues as $issue): ?>
    <li><strong><?php echo self::e((string)($issue['label'] ?? '')); ?>:</strong> <?php echo self::e((string)($issue['impact_text'] ?? '')); ?></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<p><strong>Mesaj:</strong><br><?php echo nl2br(self::e((string)($contact['message'] ?? ''))); ?></p>
<?php
        return (string) ob_get_clean();
    }

    public static function renderLeadNotificationText(array $lead): string
    {
        $contact = (array)($lead['contact'] ?? []);
        $business = (array)($lead['business'] ?? []);
        $issues = array_slice((array)($business['top_business_issues'] ?? []), 0, 3);
        $phone = trim((string)($contact['phone'] ?? ''));
        $url = trim((string)($lead['url_analyzed'] ?? ''));
        $source = trim((string)($lead['source'] ?? 'lead_request'));
        $lines = [
            'Lead nou din audit-seo-gratuit',
            'Nume: ' . (string)($contact['name'] ?? ''),
            'Email: ' . (string)($contact['email'] ?? ''),
            'Telefon: ' . ($phone !== '' ? $phone : '-'),
            'Sursa: ' . $source,
            'URL: ' . ($url !== '' ? $url : '-'),
            'Segment: ' . (string)($lead['lead_segment'] ?? ''),
            'Urgent: ' . (!empty($lead['intent_signals']['wants_call_24h']) ? 'Da' : 'Nu'),
        ];
        if ($issues !== []) {
            $lines[] = '';
            $lines[] = 'Top probleme:';
            foreach ($issues as $issue) {
                $lines[] = '- ' . (string)($issue['label'] ?? '') . ': ' . (string)($issue['impact_text'] ?? '');
            }
        }
        $lines[] = '';
        $lines[] = 'Mesaj: ' . (string)($contact['message'] ?? '');
        return implode(PHP_EOL, $lines);
    }

    private static function buildReportModel(string $url, array $snapshot): array
    {
        $score = (array)($snapshot['score'] ?? []);
        $business = (array)($snapshot['business'] ?? []);
        $checks = array_values(array_filter(
            (array)($score['checks'] ?? []),
            static fn(mixed $check): bool => is_array($check)
        ));

        $total = max(0, min(100, (int)($score['total'] ?? 0)));
        $counts = self::buildSeverityCounts($checks);
        $domain = self::domainFromUrl($url);
        $title = self::meaningfulText((string)($score['meta']['title'] ?? ''), $domain);
        $token = trim((string)($snapshot['report_token'] ?? ''));
        $reportUrl = $token !== '' ? self::reportLink($token) : Config::canonicalUrl('#workspaceShell');
        $categories = self::buildCategories($snapshot, $checks);
        $problemCategories = array_values(array_filter(
            $categories,
            static fn(array $category): bool => ($category['problem_count'] ?? 0) > 0
        ));

        $service = self::buildServiceModel($business);
        $actions = self::buildEmailActions($snapshot, $checks);
        $problemTotal = $counts['warn'] + $counts['fail'];

        return [
            'url' => $url,
            'domain' => $domain,
            'title' => $title,
            'context_label' => self::contextLabel((string)($snapshot['context'] ?? 'article')),
            'created_label' => self::formatAnalysisDate((string)($snapshot['created_at'] ?? '')),
            'total' => $total,
            'counts' => $counts,
            'verdict' => self::verdictForScore($total),
            'categories' => $categories,
            'problem_categories' => $problemCategories,
            'actions' => $actions,
            'service' => $service,
            'report_url' => $reportUrl,
            'privacy_url' => Config::origin() . '/politica-de-confidentialitate/',
            'preheader' => sprintf(
                'Raport complet pentru %s: scor %d/100, %d probleme și recomandări concrete.',
                $domain,
                $total,
                $problemTotal
            ),
        ];
    }

    private static function reportLink(string $token): string
    {
        $query = '?report=' . urlencode($token);
        if (class_exists('ReportAccess')) {
            $accessToken = ReportAccess::accessToken($token);
            if ($accessToken !== '') {
                $query .= '&access=' . urlencode($accessToken);
            }
        }

        return Config::canonicalUrl($query) . '#workspaceShell';
    }

    private static function buildCategories(array $snapshot, array $checks): array
    {
        $categories = [];

        foreach (self::REPORT_CATEGORIES as $config) {
            $categoryChecks = array_values(array_filter(
                $checks,
                static fn(array $check): bool => in_array((string)($check['id'] ?? ''), $config['ids'], true)
            ));

            if ($categoryChecks === []) {
                continue;
            }

            $problemChecks = array_values(array_filter(
                $categoryChecks,
                static fn(array $check): bool => (string)($check['sev'] ?? 'pass') !== 'pass'
            ));
            usort($problemChecks, [self::class, 'compareIssueChecks']);

            $groups = [];
            foreach (self::IMPACT_BUCKETS as $bucket) {
                $items = [];
                foreach ($problemChecks as $check) {
                    if (self::impactBucket((string)($check['business_impact_magnitude'] ?? 'medium')) !== $bucket['key']) {
                        continue;
                    }

                    $items[] = self::buildIssueModel($check);
                }

                if ($items !== []) {
                    $groups[] = [
                        'key' => $bucket['key'],
                        'label' => $bucket['label'],
                        'items' => $items,
                    ];
                }
            }

            $passCount = count(array_filter(
                $categoryChecks,
                static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'pass'
            ));
            $warnCount = count(array_filter(
                $categoryChecks,
                static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'warn'
            ));
            $failCount = count(array_filter(
                $categoryChecks,
                static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'fail'
            ));
            $scoreValue = self::resolveCategoryScore($snapshot, $config, $categoryChecks);

            $categories[] = [
                'key' => $config['key'],
                'code' => $config['code'],
                'name' => $config['name'],
                'desc' => $config['desc'],
                'max' => (int)$config['max'],
                'score' => $scoreValue,
                'tone' => self::categoryTone($scoreValue, (int)$config['max']),
                'total_count' => count($categoryChecks),
                'problem_count' => count($problemChecks),
                'pass_count' => $passCount,
                'warn_count' => $warnCount,
                'fail_count' => $failCount,
                'groups' => $groups,
            ];
        }

        return $categories;
    }

    private static function buildIssueModel(array $check): array
    {
        $severity = (string)($check['sev'] ?? 'warn');
        $rule = self::meaningfulText((string)($check['rule'] ?? ''));
        $note = self::meaningfulText((string)($check['note'] ?? ''), $rule);
        $advice = self::meaningfulText(
            (string)($check['tip'] ?? ''),
            self::meaningfulText(
                (string)($check['rule'] ?? ''),
                'Verifică manual această zonă și corectează implementarea conform recomandării SEO.'
            )
        );

        return [
            'label' => self::meaningfulText((string)($check['label'] ?? ''), (string)($check['id'] ?? 'Verificare SEO')),
            'severity' => $severity,
            'severity_label' => self::severityLabel($severity),
            'impact_label' => self::impactLabel((string)($check['business_impact_magnitude'] ?? 'medium')),
            'impact_text' => self::meaningfulText(
                (string)($check['business_impact_text'] ?? ''),
                'Impactul comercial estimat nu este disponibil în snapshotul curent.'
            ),
            'evidence' => $note !== '' ? $note : 'Snapshotul curent nu include o dovadă suplimentară pentru această verificare.',
            'advice' => $advice,
            'complexity_label' => self::complexityLabel((string)($check['fix_complexity'] ?? 'medium')),
        ];
    }

    private static function buildServiceModel(array $business): array
    {
        $service = (array)($business['recommended_service'] ?? []);
        $url = trim((string)($service['url'] ?? ''));
        $contactEmail = Config::leadNotifyEmail();
        $secondaryUrl = $url !== '' ? $url : ($contactEmail !== null ? 'mailto:' . $contactEmail : '');

        return [
            'name' => self::meaningfulText((string)($service['name'] ?? ''), 'Ajutor contextual'),
            'label' => self::meaningfulText((string)($service['cta_primary'] ?? ''), 'Cere ajutor pentru implementare'),
            'url' => $url,
            'available' => $url !== '',
            'secondary_label' => self::meaningfulText((string)($service['cta_secondary'] ?? ''), 'Discută cu un specialist'),
            'secondary_url' => $secondaryUrl,
            'secondary_available' => $secondaryUrl !== '',
        ];
    }

    private static function buildEmailActions(array $snapshot, array $checks): array
    {
        $business = (array)($snapshot['business'] ?? []);
        $rawActions = array_values(array_filter(
            array_slice((array)($business['action_plan'] ?? []), 0, 5),
            static fn(mixed $action): bool => is_array($action)
        ));

        if ($rawActions === []) {
            $fallbackIssues = array_values(array_filter(
                (array)($business['top_business_issues'] ?? []),
                static fn(mixed $issue): bool => is_array($issue)
            ));

            if ($fallbackIssues === []) {
                $fallbackIssues = array_slice(array_values(array_filter(
                    $checks,
                    static fn(array $check): bool => (string)($check['sev'] ?? 'pass') !== 'pass'
                )), 0, 5);
            }

            foreach ($fallbackIssues as $issue) {
                $rawActions[] = [
                    'title' => (string)($issue['label'] ?? $issue['title'] ?? 'Acțiune recomandată'),
                    'why' => (string)($issue['impact_text'] ?? $issue['business_impact_text'] ?? ''),
                    'how' => (string)($issue['how'] ?? $issue['tip'] ?? $issue['rule'] ?? ''),
                    'duration' => (string)($issue['duration'] ?? self::durationForComplexity((string)($issue['fix_complexity'] ?? 'medium'))),
                    'owner' => (string)($issue['owner'] ?? self::ownerForComplexity((string)($issue['fix_complexity'] ?? 'medium'))),
                    'fix_complexity' => (string)($issue['fix_complexity'] ?? 'medium'),
                ];
            }
        }

        $actions = [];
        foreach (array_slice($rawActions, 0, 5) as $action) {
            $actions[] = [
                'title' => self::meaningfulText((string)($action['title'] ?? ''), 'Acțiune recomandată'),
                'why' => self::meaningfulText(
                    (string)($action['why'] ?? ''),
                    'Această acțiune are impact direct asupra clarității și vizibilității SEO.'
                ),
                'how' => self::meaningfulText(
                    (string)($action['how'] ?? ''),
                    'Pornește de la recomandarea din problema asociată și validează implementarea după schimbare.'
                ),
                'duration' => self::meaningfulText(
                    (string)($action['duration'] ?? ''),
                    self::durationForComplexity((string)($action['fix_complexity'] ?? 'medium'))
                ),
                'owner' => self::meaningfulText(
                    (string)($action['owner'] ?? ''),
                    self::ownerForComplexity((string)($action['fix_complexity'] ?? 'medium'))
                ),
            ];
        }

        return $actions;
    }

    private static function resolveCategoryScore(array $snapshot, array $config, array $checks): int
    {
        $max = (int)$config['max'];
        $scoreSource = (string)($config['score_source'] ?? 'weighted');
        $scoreKey = (string)($config['score_key'] ?? '');

        $value = null;
        if ($scoreSource === 'breakdown') {
            $candidate = ($snapshot['score']['breakdown'][$scoreKey] ?? null);
            if (is_numeric($candidate)) {
                $value = (int)round((float)$candidate);
            }
        } elseif ($scoreSource === 'local_points') {
            $candidate = ($snapshot['score']['local'][$scoreKey] ?? null);
            if (is_numeric($candidate)) {
                $value = (int)round((float)$candidate);
            }
        }

        if ($scoreSource === 'weighted' || $value === null) {
            $value = self::weightedPoints($checks, $max);
        }

        return max(0, min($max, $value));
    }

    private static function weightedPoints(array $checks, int $max): int
    {
        if ($checks === []) {
            return 0;
        }

        $total = 0.0;
        foreach ($checks as $check) {
            $severity = (string)($check['sev'] ?? 'pass');
            if ($severity === 'pass') {
                $total += 1.0;
            } elseif ($severity === 'warn') {
                $total += 0.5;
            }
        }

        return (int)round(($total / count($checks)) * $max);
    }

    private static function buildSeverityCounts(array $checks): array
    {
        return [
            'pass' => count(array_filter($checks, static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'pass')),
            'warn' => count(array_filter($checks, static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'warn')),
            'fail' => count(array_filter($checks, static fn(array $check): bool => (string)($check['sev'] ?? 'pass') === 'fail')),
        ];
    }

    private static function compareIssueChecks(array $left, array $right): int
    {
        $impactOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        $severityOrder = ['fail' => 0, 'warn' => 1, 'pass' => 2];

        $leftImpact = $impactOrder[(string)($left['business_impact_magnitude'] ?? 'medium')] ?? 10;
        $rightImpact = $impactOrder[(string)($right['business_impact_magnitude'] ?? 'medium')] ?? 10;
        if ($leftImpact !== $rightImpact) {
            return $leftImpact <=> $rightImpact;
        }

        $leftSeverity = $severityOrder[(string)($left['sev'] ?? 'warn')] ?? 10;
        $rightSeverity = $severityOrder[(string)($right['sev'] ?? 'warn')] ?? 10;
        if ($leftSeverity !== $rightSeverity) {
            return $leftSeverity <=> $rightSeverity;
        }

        return strcmp(
            self::meaningfulText((string)($left['label'] ?? ''), (string)($left['id'] ?? '')),
            self::meaningfulText((string)($right['label'] ?? ''), (string)($right['id'] ?? ''))
        );
    }

    private static function renderMetricCardHtml(string $value, string $label, string $detail, string $tone): string
    {
        $bg = self::toneBackground($tone);
        $border = self::toneBorder($tone);
        $accent = self::toneAccent($tone);
        $muted = $tone === 'danger' ? '#7c2d12' : '#526074';

        return sprintf(
            '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="border:1px solid %s;background-color:%s;border-radius:16px;"><tr><td style="padding:13px 14px 12px 14px;"><div style="font-size:22px;line-height:26px;font-weight:700;color:%s;">%s</div><div style="margin-top:4px;font-size:13px;line-height:18px;font-weight:700;color:#0f1c2e;">%s</div><div style="margin-top:4px;font-size:12px;line-height:18px;color:%s;">%s</div></td></tr></table>',
            self::e($border),
            self::e($bg),
            self::e($accent),
            self::e($value),
            self::e($label),
            self::e($muted),
            self::e($detail)
        );
    }

    private static function renderCategorySummaryRowHtml(array $category): string
    {
        $pct = $category['max'] > 0 ? max(0, min(100, (int)round(($category['score'] / $category['max']) * 100))) : 0;
        $bar = self::toneAccent((string)$category['tone']);
        $barTrack = '#ece3d7';

        return sprintf(
            '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="margin-bottom:8px;border:1px solid #eadfce;border-radius:14px;background-color:#fffaf5;"><tr><td style="padding:10px 12px 8px 12px;"><table role="presentation" width="100%%" cellspacing="0" cellpadding="0"><tr><td valign="top"><div style="font-size:15px;line-height:20px;font-weight:700;color:#0f1c2e;">%s</div><div style="margin-top:3px;font-size:12px;line-height:17px;color:#64748b;">%s</div></td><td width="66" align="right" valign="top"><div style="font-size:18px;line-height:22px;font-weight:700;color:#0f1c2e;">%d<span style="font-size:12px;color:#64748b;">/%d</span></div></td></tr></table></td></tr><tr><td style="padding:0 12px 10px 12px;"><div style="height:5px;background-color:%s;border-radius:999px;"><div style="width:%d%%;max-width:100%%;height:5px;background-color:%s;border-radius:999px;"></div></div></td></tr></table>',
            self::e($category['name']),
            self::e($category['desc']),
            (int)$category['score'],
            (int)$category['max'],
            self::e($barTrack),
            $pct,
            self::e($bar)
        );
    }

    private static function renderCategorySummaryGridHtml(array $categories): string
    {
        if ($categories === []) {
            return '';
        }

        ob_start();
        ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
  <?php foreach (array_chunk($categories, 2) as $row): ?>
    <tr>
      <?php if (count($row) === 1): ?>
        <td colspan="2" valign="top" style="padding:0 0 10px 0;">
          <?php echo self::renderCategorySummaryRowHtml($row[0]); ?>
        </td>
      <?php else: ?>
        <td class="category-grid-cell" width="50%" valign="top" style="padding:0 8px 10px 0;">
          <?php echo self::renderCategorySummaryRowHtml($row[0]); ?>
        </td>
        <td class="category-grid-cell" width="50%" valign="top" style="padding:0 0 10px 8px;">
          <?php echo self::renderCategorySummaryRowHtml($row[1]); ?>
        </td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
</table>
<?php
        return (string)ob_get_clean();
    }

    private static function renderCategorySectionHtml(array $category): string
    {
        ob_start();
        ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:14px;border:1px solid #eadfce;background-color:#fffaf5;border-radius:18px;">
  <tr>
    <td style="padding:14px 14px 4px 14px;">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
          <td valign="top">
            <div style="font-size:11px;line-height:16px;letter-spacing:0.12em;text-transform:uppercase;color:#ea580c;font-weight:700;"><?php echo self::e($category['code']); ?></div>
            <div style="margin-top:3px;font-size:19px;line-height:24px;font-weight:700;color:#0f1c2e;"><?php echo self::e($category['name']); ?></div>
            <div style="margin-top:3px;font-size:12px;line-height:18px;color:#64748b;"><?php echo self::e($category['desc']); ?></div>
          </td>
          <td width="88" align="right" valign="top">
            <div style="font-size:21px;line-height:26px;font-weight:700;color:#0f1c2e;"><?php echo self::e((string)$category['score']); ?><span style="font-size:12px;color:#64748b;">/<?php echo self::e((string)$category['max']); ?></span></div>
            <div style="margin-top:4px;font-size:11px;line-height:16px;color:#64748b;"><?php echo self::e((string)$category['problem_count']); ?> probleme</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <?php foreach ($category['groups'] as $group): ?>
    <tr>
      <td style="padding:8px 14px 0 14px;">
        <div style="display:inline-block;padding:4px 9px;background-color:#fff1e5;border:1px solid #f7d4b2;border-radius:999px;font-size:11px;line-height:16px;color:#9a3412;font-weight:700;"><?php echo self::e($group['label']); ?></div>
      </td>
    </tr>
    <tr>
      <td style="padding:8px 14px 2px 14px;">
        <?php foreach ($group['items'] as $issue): ?>
          <?php echo self::renderIssueCardHtml($issue); ?>
        <?php endforeach; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<?php
        return (string)ob_get_clean();
    }

    private static function renderIssueCardHtml(array $issue): string
    {
        $tone = (string)$issue['severity'];
        $accent = self::toneAccent($tone);
        $bg = self::toneBackground($tone);
        $border = self::toneBorder($tone);

        return sprintf(
            '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="margin-bottom:10px;border:1px solid %s;background-color:#ffffff;border-radius:16px;"><tr><td style="padding:13px 13px 11px 13px;"><div style="font-size:15px;line-height:20px;font-weight:700;color:#0f1c2e;">%s</div><div style="margin-top:6px;"><span style="display:inline-block;padding:3px 8px;background-color:%s;border:1px solid %s;border-radius:999px;font-size:11px;line-height:16px;color:%s;font-weight:700;">%s</span><span style="display:inline-block;margin-left:5px;padding:3px 8px;background-color:#fff7ed;border:1px solid #fed7aa;border-radius:999px;font-size:11px;line-height:16px;color:#9a3412;font-weight:700;">%s</span><span style="display:inline-block;margin-left:5px;padding:3px 8px;background-color:#f8fafc;border:1px solid #dbe4ef;border-radius:999px;font-size:11px;line-height:16px;color:#475569;font-weight:700;">%s</span></div><div style="margin-top:10px;font-size:12px;line-height:18px;color:#526074;"><strong style="color:#0f1c2e;">Dovada detectata:</strong> %s</div><div style="margin-top:6px;font-size:12px;line-height:18px;color:#526074;"><strong style="color:#0f1c2e;">Impact:</strong> %s</div><div style="margin-top:8px;padding:10px 11px;border:1px solid #eadfce;background-color:#fffaf5;border-radius:12px;font-size:12px;line-height:18px;color:#374151;"><strong style="color:#0f1c2e;">Sfat de rezolvare:</strong> %s</div></td></tr></table>',
            self::e($border),
            self::e($issue['label']),
            self::e($bg),
            self::e($border),
            self::e($accent),
            self::e($issue['severity_label']),
            self::e($issue['impact_label']),
            self::e($issue['complexity_label']),
            self::e($issue['evidence']),
            self::e($issue['impact_text']),
            self::e($issue['advice'])
        );
    }

    private static function renderActionCardHtml(array $action, int $position): string
    {
        return sprintf(
            '<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="margin-bottom:10px;border:1px solid #eadfce;background-color:#fffaf5;border-radius:16px;"><tr><td style="padding:13px 13px 12px 13px;"><div style="font-size:11px;line-height:16px;letter-spacing:0.12em;text-transform:uppercase;color:#ea580c;font-weight:700;">Acțiunea %d</div><div style="margin-top:3px;font-size:16px;line-height:21px;font-weight:700;color:#0f1c2e;">%s</div><div style="margin-top:8px;font-size:12px;line-height:18px;color:#526074;"><strong style="color:#0f1c2e;">De ce:</strong> %s</div><div style="margin-top:6px;font-size:12px;line-height:18px;color:#526074;"><strong style="color:#0f1c2e;">Cum:</strong> %s</div><div style="margin-top:8px;font-size:11px;line-height:16px;color:#64748b;">Durata: %s &middot; Owner: %s</div></td></tr></table>',
            $position,
            self::e($action['title']),
            self::e($action['why']),
            self::e($action['how']),
            self::e($action['duration']),
            self::e($action['owner'])
        );
    }

    private static function verdictForScore(int $total): array
    {
        if ($total < 40) {
            return [
                'label' => 'Foarte slab',
                'summary' => 'Pagina are blocaje majore care opresc atât vizibilitatea, cât și claritatea.',
            ];
        }

        if ($total < 60) {
            return [
                'label' => 'Fragil',
                'summary' => 'Există fundație, dar sunt prea multe probleme ca raportul să poată fi delegat imediat.',
            ];
        }

        if ($total < 75) {
            return [
                'label' => 'Decent',
                'summary' => 'Ai baza funcțională, însă sunt quick wins și blocaje care merită prioritizate.',
            ];
        }

        if ($total < 90) {
            return [
                'label' => 'Bun',
                'summary' => 'Pagina este sănătoasă, iar câștigurile vin din rafinare și prioritizare disciplinată.',
            ];
        }

        return [
            'label' => 'Foarte bun',
            'summary' => 'Pagina este solidă on-page; diferența vine din consistență și scalare.',
        ];
    }

    private static function categoryTone(int $score, int $max): string
    {
        $pct = $max > 0 ? ($score / $max) * 100 : 0;
        if ($pct >= 85) {
            return 'ok';
        }
        if ($pct >= 55) {
            return 'warn';
        }
        return 'danger';
    }

    private static function severityLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'fail' => 'Problem',
            'warn' => 'Warning',
            default => 'OK',
        };
    }

    private static function impactLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'high' => 'Impact mare',
            'low' => 'Impact redus',
            default => 'Impact mediu',
        };
    }

    private static function impactBucket(string $value): string
    {
        return match (strtolower(trim($value))) {
            'high' => 'high',
            'low' => 'low',
            default => 'med',
        };
    }

    private static function complexityLabel(string $value): string
    {
        return match (strtolower(trim($value))) {
            'easy' => 'Quick win',
            'hard' => 'Hard',
            default => 'Medium',
        };
    }

    private static function durationForComplexity(string $value): string
    {
        return match (strtolower(trim($value))) {
            'easy' => 'aprox. 1 oră',
            'hard' => 'aprox. 1 săptămână',
            default => 'aprox. 1 zi',
        };
    }

    private static function ownerForComplexity(string $value): string
    {
        return match (strtolower(trim($value))) {
            'easy' => 'tu sau editorul site-ului',
            'hard' => 'echipa tehnica / agentie',
            default => 'dezvoltator sau marketer',
        };
    }

    private static function toneBackground(string $tone): string
    {
        return match ($tone) {
            'ok' => '#effaf4',
            'danger' => '#fff3eb',
            default => '#fff7ed',
        };
    }

    private static function toneBorder(string $tone): string
    {
        return match ($tone) {
            'ok' => '#cfead9',
            'danger' => '#f8d3bd',
            default => '#f7d8b0',
        };
    }

    private static function toneAccent(string $tone): string
    {
        return match ($tone) {
            'ok' => '#166534',
            'danger' => '#c2410c',
            default => '#b45309',
        };
    }

    private static function contextLabel(string $context): string
    {
        return strtolower(trim($context)) === 'local' ? 'Audit local' : 'Audit standard';
    }

    private static function formatAnalysisDate(string $date): string
    {
        if ($date === '') {
            return 'analiza recenta';
        }

        try {
            return (new DateTimeImmutable($date))
                ->setTimezone(new DateTimeZone('Europe/Bucharest'))
                ->format('d.m.Y H:i');
        } catch (\Throwable) {
            return 'analiza recenta';
        }
    }

    private static function domainFromUrl(string $url): string
    {
        $host = (string)(parse_url($url, PHP_URL_HOST) ?: '');
        if ($host === '') {
            return $url;
        }

        return preg_replace('/^www\./i', '', $host) ?: $host;
    }

    private static function meaningfulText(string $value, string $fallback = ''): string
    {
        $text = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $normalized = trim(str_replace(['â€”', '-', 'â€“', '-'], '-', $text));
        if ($normalized === '' || $normalized === '-' || strtolower($normalized) === 'n/a') {
            return $fallback;
        }

        return $text;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
