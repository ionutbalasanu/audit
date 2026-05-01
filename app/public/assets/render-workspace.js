import { hostnameFromUrl, recommendedAction, scoreTotal } from "./report-normalize.js";

const CATEGORY_CONFIG = [
  {
    key: "content",
    code: "CONTENT",
    name: "Conținut & Media",
    desc: "Lungime text, H1/H2/H3, liste, imagini și ALT-uri",
    max: 40,
    impact: "high",
    ids: [
      "word_count_800",
      "intro_mentions_topic",
      "h1_single",
      "headings_hierarchy",
      "lists_tables",
      "images_in_body",
      "img_alt_ratio_80",
      "lazyload_images",
      "date_published",
      "date_modified",
      "author_visible_or_schema",
    ],
    resolveScore(report, checks) {
      return Number.isFinite(Number(report?.score?.breakdown?.content))
        ? Number(report.score.breakdown.content)
        : weightedPoints(checks, 40);
    },
  },
  {
    key: "structure",
    code: "STRUCTURE",
    name: "Structură & Indexare",
    desc: "Robots, canonical, URL, linkuri interne și externe",
    max: 25,
    impact: "high",
    ids: [
      "indexable",
      "canonical_present",
      "canonical_valid",
      "url_clean",
      "internal_links_present",
      "external_links_present",
      "meta_robots_ok",
      "html_valid",
      "image_dimensions_defined",
      "fonts_preload",
      "cls_risky_elements",
    ],
    resolveScore(report, checks) {
      return Number.isFinite(Number(report?.score?.breakdown?.structure))
        ? Number(report.score.breakdown.structure)
        : weightedPoints(checks, 25);
    },
  },
  {
    key: "meta",
    code: "META",
    name: "Metadate & Rich Snippets",
    desc: "Title, meta description, schema, Open Graph",
    max: 20,
    impact: "med",
    ids: [
      "title_length_ok",
      "meta_description_ok",
      "og_minimal",
      "schema_article_recommended",
      "faq_schema_present",
      "schema_breadcrumbs",
      "schema_image_required_fields",
    ],
    resolveScore(report, checks) {
      return Number.isFinite(Number(report?.score?.breakdown?.signals))
        ? Number(report.score.breakdown.signals)
        : weightedPoints(checks, 20);
    },
  },
  {
    key: "local",
    code: "LOCAL",
    name: "Localizare RO",
    desc: "Limba, formate locale, hreflang, semnale locale",
    max: 15,
    impact: "med",
    ids: ["lang_ro", "og_locale_or_inLanguage_ro", "date_format_ro", "hreflang_pairs"],
    resolveScore(report, checks) {
      return Number.isFinite(Number(report?.score?.breakdown?.locale))
        ? Number(report.score.breakdown.locale)
        : weightedPoints(checks, 15);
    },
  },
  {
    key: "seo-local",
    code: "SEO-LOCAL",
    name: "SEO local",
    desc: "LocalBusiness schema, harta, reviews, click-to-call",
    max: 30,
    impact: "low",
    ids: [
      "local_tel_click",
      "local_tel_prefix_local",
      "local_address_visible",
      "local_directions_link",
      "local_opening_hours",
      "local_schema_localbusiness",
      "local_schema_postal",
      "local_schema_tel",
      "local_schema_geo",
      "local_schema_sameas",
      "local_schema_area",
      "local_schema_rating",
      "local_city_detected",
      "local_city_in_title",
      "local_city_in_h1",
      "local_city_in_slug",
      "local_city_in_intro",
      "local_map_embed",
      "local_alt_has_city",
      "local_locator",
      "local_whatsapp",
    ],
    resolveScore(report, checks) {
      const direct = Number(report?.score?.local?.points);
      return Number.isFinite(direct) ? direct : weightedPoints(checks, 30);
    },
  },
];

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/[\u2013\u2014]/g, "-")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function weightedPoints(checks, max) {
  if (!checks.length) return 0;
  const total = checks.reduce((sum, check) => {
    if (check?.sev === "pass") return sum + 1;
    if (check?.sev === "warn") return sum + 0.5;
    return sum;
  }, 0);

  return Math.round((total / checks.length) * max);
}

function pctTone(score, max) {
  const pct = max > 0 ? (Number(score || 0) / max) * 100 : 0;
  if (pct >= 85) return "ok";
  if (pct >= 55) return "warn";
  return "danger";
}

function impactBucket(value) {
  if (value === "high") return "high";
  if (value === "low") return "low";
  return "med";
}

function severityTone(value) {
  if (value === "pass") return "ok";
  if (value === "fail") return "danger";
  return "warn";
}

function impactChipLabel(value) {
  if (value === "high") return "Impact mare";
  if (value === "low") return "Impact redus";
  return "Impact mediu";
}

function categoryImpactLabel(value) {
  if (value === "high") return "Impact mare";
  if (value === "low") return "Impact redus";
  return "Impact mediu";
}

function categoryIcon(key, size = 18) {
  const iconSize = String(size);
  switch (key) {
    case "content":
      return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>`;
    case "structure":
      return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect></svg>`;
    case "meta":
      return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m20 7-8 8-4-4-4 4"></path><path d="M16 7h4v4"></path></svg>`;
    case "local":
      return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M2 12h20"></path><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>`;
    default:
      return `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 7-8 13-8 13S4 17 4 10a8 8 0 0 1 16 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;
  }
}

function checkStatusIcon(tone) {
  if (tone === "ok") {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>`;
  }
  if (tone === "danger") {
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>`;
  }
  return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18A2 2 0 0 0 3.53 21h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>`;
}

function smallCheckIcon(size = 11) {
  return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>`;
}

function statIcon(kind) {
  switch (kind) {
    case "ok":
      return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>`;
    case "warn":
      return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18A2 2 0 0 0 3.53 21h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"></path></svg>`;
    case "danger":
      return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>`;
    default:
      return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 7-8.5 8.5-5-5L2 17"></path><path d="M16 7h6v6"></path></svg>`;
  }
}

function chevronIcon(size = 16) {
  return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"></path></svg>`;
}

function mailIcon(size = 16) {
  return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 5L2 7"></path></svg>`;
}

function downloadIcon(size = 16) {
  return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="m7 10 5 5 5-5"></path><path d="M12 15V3"></path></svg>`;
}

function lockIcon(size = 12) {
  return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`;
}

function clockIcon(size = 12) {
  return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>`;
}

function relativeRunLabel(createdAt) {
  if (!createdAt) return "rulat recent";

  const parsed = new Date(createdAt);
  if (Number.isNaN(parsed.getTime())) return "rulat recent";

  const diffMs = Math.max(0, Date.now() - parsed.getTime());
  const diffMinutes = Math.round(diffMs / 60000);

  if (diffMinutes < 1) return "rulat acum câteva secunde";
  if (diffMinutes < 60) return `rulat acum ${diffMinutes} min`;

  const diffHours = Math.round(diffMinutes / 60);
  if (diffHours < 24) return `rulat acum ${diffHours} h`;

  const diffDays = Math.round(diffHours / 24);
  return `rulat acum ${diffDays} zile`;
}

function documentLabel(context) {
  return context === "local" ? "Document local" : "Document standard";
}

function buildCategoryModels(report) {
  const allChecks = Array.isArray(report?.score?.checks) ? report.score.checks : [];
  const includeSeoLocal = report?.context === "local" || report?.score?.context === "local";

  return CATEGORY_CONFIG.filter((config) => includeSeoLocal || config.key !== "seo-local").map((config) => {
    const checks = allChecks.filter((check) => config.ids.includes(check.id));
    const score = Math.max(0, Math.min(config.max, config.resolveScore(report, checks)));
    const groups = ["high", "med", "low"]
      .map((bucket) => ({
        key: bucket,
        label: bucket === "high" ? "Impact mare" : bucket === "med" ? "Impact mediu" : "Impact redus",
        items: checks.filter((check) => impactBucket(check.business_impact_magnitude) === bucket),
      }))
      .filter((group) => group.items.length > 0);

    return {
      ...config,
      score,
      groups,
      checks,
    };
  }).filter((category) => {
    if (category.checks.length > 0) return true;
    if (category.key === "seo-local") return Number.isFinite(Number(report?.score?.local?.points));
    return Number.isFinite(Number(category.score));
  });
}

function scoreHeroMarkup(report, categories, passCount, warnCount, failCount) {
  const total = Math.max(0, Math.min(100, scoreTotal(report)));
  const size = 228;
  const stroke = 12;
  const radius = (size - stroke) / 2;
  const circumference = 2 * Math.PI * radius;
  const rawOffset = circumference * (1 - total / 100);
  const minVisibleGapOffset = stroke * 0.9;
  const offset = total >= 100 ? 0 : Math.max(rawOffset, minVisibleGapOffset);

  return `
    <div class="score-hero fade-up">
      <div class="score-orb-wrap">
        <div class="score-orb" aria-label="Scor SEO ${total} din 100">
          <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
            <defs>
              <linearGradient id="score-orb-gradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#FB923C"></stop>
                <stop offset="100%" stop-color="#EA580C"></stop>
              </linearGradient>
            </defs>
            <circle cx="${size / 2}" cy="${size / 2}" r="${radius}" stroke="rgba(15, 28, 46, 0.06)" stroke-width="${stroke}" fill="none"></circle>
            <circle
              cx="${size / 2}"
              cy="${size / 2}"
              r="${radius}"
              stroke="url(#score-orb-gradient)"
              stroke-width="${stroke}"
              fill="none"
              stroke-linecap="round"
              stroke-dasharray="${circumference}"
              stroke-dashoffset="${circumference}"
              data-score-progress
              data-target-offset="${offset}"
            ></circle>
          </svg>
          <div class="score-orb-center">
            <div>
              <div class="score-val" data-score-value="${total}">${total}</div>
              <div class="score-max">/ 100</div>
            </div>
          </div>
        </div>
        <div class="score-legend">
          <span class="item"><span class="pip ok"></span><b>${passCount}</b> reușite</span>
          <span class="item"><span class="pip warn"></span><b>${warnCount}</b> avertizări</span>
          <span class="item"><span class="pip danger"></span><b>${failCount}</b> nereușite</span>
        </div>
      </div>

      <div class="lead-copy">
        <div class="breakdown-head">
          <h3>Distribuția scorului pe categorii</h3>
          <span>Vezi rapid unde pierzi puncte</span>
        </div>
        <div class="score-breakdown">
          ${categories.map((category) => {
            const tone = pctTone(category.score, category.max);
            const pct = Math.max(0, Math.min(100, (category.score / category.max) * 100));

            return `
              <div class="cat-row">
                <div class="cat-icon">${categoryIcon(category.key)}</div>
                <div class="cat-name">${escapeHtml(category.name)}<small>${escapeHtml(category.desc)}</small></div>
                <div class="cat-score">${escapeHtml(category.score)}/${escapeHtml(category.max)}</div>
                <div class="cat-bar"><div class="cat-bar-fill ${tone}" data-bar-width="${pct}" style="width:0%"></div></div>
              </div>
            `;
          }).join("")}
        </div>
      </div>
    </div>
  `;
}

function summaryStatsMarkup(report, checks) {
  const counts = report?.score?.checks_counts || {};
  const failCount = Number.isFinite(Number(counts.fail)) ? Number(counts.fail) : checks.filter((check) => check?.sev === "fail").length;
  const warnCount = Number.isFinite(Number(counts.warn)) ? Number(counts.warn) : checks.filter((check) => check?.sev === "warn").length;
  const passCount = Number.isFinite(Number(counts.pass)) ? Number(counts.pass) : checks.filter((check) => check?.sev === "pass").length;
  const priorityCount = Number.isFinite(Number(counts.priority)) ? Number(counts.priority) : checks.filter((check) => check?.sev !== "pass" && check?.business_impact_magnitude === "high").length;

  return `
    <div class="summary-stats fade-up">
      <div class="stat-card">
        <div class="stat-icon ok">${statIcon("ok")}</div>
        <div class="stat-copy">
          <div class="stat-val">${passCount}</div>
          <div class="stat-label">Puncte OK</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon warn">${statIcon("warn")}</div>
        <div class="stat-copy">
          <div class="stat-val">${warnCount}</div>
          <div class="stat-label">Avertizări</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon accent">${statIcon("accent")}</div>
        <div class="stat-copy">
          <div class="stat-val">${priorityCount}</div>
          <div class="stat-label">Priorități mari</div>
        </div>
      </div>
    </div>
  `;
}

function sectionHeadMarkup(kicker, title, copy = "") {
  return `
    <div class="report-section-headline">
      <p class="section-eyebrow">${escapeHtml(kicker)}</p>
      <h2>${escapeHtml(title)}</h2>
      ${copy ? `<p>${escapeHtml(copy)}</p>` : ""}
    </div>
  `;
}

function complexityLabel(value) {
  if (value === "easy") return "rapid";
  if (value === "hard") return "tehnic";
  return "mediu";
}

function serviceCtaMarkup(recommended, source) {
  const label = recommended?.label || "Vreau ajutor cu implementarea";
  return `
    <button type="button" class="btn btn-primary" data-open-help data-help-source="${escapeHtml(source)}">
      ${escapeHtml(label)}
    </button>
  `;
}

function lowScoreBannerMarkup(score) {
  if (score >= 70) return "";
  return `
    <div class="report-help-banner" data-track="report_help_banner_view">
      <div class="report-help-banner__content">
        <h3>Scorul t&#259;u arat&#259; c&#259; exist&#259; probleme reale</h3>
        <p>Un consultant NovaWeb &#238;&#539;i poate spune exact ce s&#259; prioritizezi, ce cost&#259; cel mai pu&#539;in de reparat &#537;i ce produce cele mai mari rezultate. R&#259;spuns &#238;n maxim 24h.</p>
      </div>
      <div class="report-help-banner__actions">
        <button type="button" class="btn btn-primary" data-open-help data-source="low_score_banner">
          Cere o revizuire gratuit&#259;
        </button>
      </div>
    </div>
  `;
}

function diagnosticMarkup(report, recommended, isUnlocked) {
  const business = report?.business || {};
  const issues = Array.isArray(business.top_business_issues) ? business.top_business_issues.slice(0, 3) : [];
  const opportunity = Number(business.opportunity_score ?? 0);

  return `
    <section class="report-section-block diagnostic-section fade-up">
      ${sectionHeadMarkup(
        "Diagnostic",
        "Ce blocheaza pagina acum",
        "Prioritățile sunt ordonate după impactul probabil asupra vizibilității și conversiei."
      )}
      <div class="diagnostic-layout">
        <div class="opportunity-panel">
          <span class="opportunity-label">Potential nefolosit</span>
          <strong>${Number.isFinite(opportunity) ? opportunity : 0}</strong>
          <p>Scor orientativ calculat din problemele rămase după auditul tehnic.</p>
          ${serviceCtaMarkup(recommended, "diagnostic_cta")}
        </div>
        <div class="business-issues">
          ${issues.length ? issues.map((issue, index) => `
            <article class="business-issue">
              <span class="issue-index">${index + 1}</span>
              <div>
                <div class="issue-meta">
                  <span>${escapeHtml(impactChipLabel(issue.business_impact_magnitude))}</span>
                  <span>${escapeHtml(complexityLabel(issue.fix_complexity))}</span>
                </div>
                <h3>${escapeHtml(issue.label || "Problemă prioritară")}</h3>
                <p>${escapeHtml(
                  isUnlocked && issue.impact_text
                    ? issue.impact_text
                    : "Explicația completă și recomandarea concretă se activează după deblocarea raportului."
                )}</p>
              </div>
            </article>
          `).join("") : `
            <article class="business-issue is-empty">
              <span class="issue-index">OK</span>
              <div>
                <h3>Nu avem probleme prioritare majore în preview.</h3>
                <p>Deblochează raportul pentru verificarea completă și pentru toate recomandările tehnice.</p>
              </div>
            </article>
          `}
        </div>
      </div>
    </section>
  `;
}

function actionPlanMarkup(report, isUnlocked) {
  const actions = Array.isArray(report?.business?.action_plan) ? report.business.action_plan : [];
  const visible = actions.slice(0, isUnlocked ? 5 : 3);

  return `
    <section class="report-section-block plan-section fade-up">
      ${sectionHeadMarkup(
        "Pași de remediere",
        "Ce merită reparat prima dată",
        "Recomandările separă acțiunile rapide de lucrurile care merită delegate."
      )}
      <div class="action-plan-list">
        ${visible.length ? visible.map((action, index) => `
          <article class="action-plan-row">
            <span class="plan-index">${index + 1}</span>
            <div class="plan-copy">
              <h3>${escapeHtml(action.title || "Acțiune recomandată")}</h3>
              ${isUnlocked && action.why ? `<p>${escapeHtml(action.why)}</p>` : `<p>Detaliile despre impact și implementare sunt disponibile în raportul complet.</p>`}
              ${isUnlocked && action.how ? `<div class="plan-how"><strong>Cum:</strong> ${escapeHtml(action.how)}</div>` : ""}
            </div>
            <div class="plan-meta">
              <span>${escapeHtml(action.duration || "prioritar")}</span>
              <span>${escapeHtml(action.owner || complexityLabel(action.fix_complexity))}</span>
              ${action.fix_complexity === "hard" ? `<b>recomandat asistat</b>` : ""}
            </div>
          </article>
        `).join("") : `
          <article class="action-plan-row">
            <span class="plan-index">1</span>
            <div class="plan-copy">
              <h3>Nu există acțiuni prioritare în preview.</h3>
              <p>Raportul complet poate include detalii tehnice suplimentare și pași de rafinare.</p>
            </div>
          </article>
        `}
      </div>
      ${!isUnlocked ? `<p class="locked-note">Recomandările complete și explicațiile fiecărei acțiuni se trimit pe email după deblocare.</p>` : ""}
    </section>
  `;
}

function checkRowMarkup(check, { open = false } = {}) {
  const tone = severityTone(check?.sev);
  const note = String(check?.note || check?.rule || "").trim();
  const isProblem = check?.sev === "warn" || check?.sev === "fail";
  const isLockedProblem = Boolean(check?.locked);
  const recommendation = String(
    check?.tip ||
    check?.rule ||
    "Verifică manual această zonă și corectează implementarea conform recomandării SEO."
  ).trim();
  const valueMarkup = note ? `<span class="value">${escapeHtml(note)}</span>` : "";

  if (isLockedProblem) {
    const lockedCopy = String(
      check?.locked_copy ||
      "Introdu email-ul pentru a vedea problema exactă și pașii de rezolvare."
    ).trim();
    const impactMarkup = `<span class="value is-locked">${escapeHtml(impactChipLabel(check?.business_impact_magnitude))}</span>`;

    return `
      <div class="check-row is-static is-locked-problem">
        <div class="check-head is-static">
          <span class="check-status locked">${lockIcon(12)}</span>
          <span class="check-title">
            <span class="label">${escapeHtml(check?.label || "Problemă ascunsă în preview")}</span>
            ${impactMarkup}
            <span class="locked-redacted" aria-hidden="true"><span></span><span></span></span>
            <span class="locked-row-copy">${escapeHtml(lockedCopy)}</span>
          </span>
        </div>
      </div>
    `;
  }

  if (!isProblem) {
    return `
      <div class="check-row is-static">
        <div class="check-head is-static">
          <span class="check-status ${tone}">${checkStatusIcon(tone)}</span>
          <span class="check-title">
            <span class="label">${escapeHtml(check?.label || check?.id || "Verificare")}</span>
            ${valueMarkup}
          </span>
        </div>
      </div>
    `;
  }

  return `
    <div class="check-row${open ? " open" : ""}">
      <button type="button" class="check-head" data-check-toggle aria-expanded="${open ? "true" : "false"}">
        <span class="check-status ${tone}">${checkStatusIcon(tone)}</span>
        <span class="check-title">
          <span class="label">${escapeHtml(check?.label || check?.id || "Verificare")}</span>
          ${valueMarkup}
        </span>
        <span class="check-chevron">${chevronIcon()}</span>
      </button>
      <div class="check-body"${open ? "" : " hidden"}>
        <div class="rec">
          <span>${categoryIcon("meta", 14)}</span>
          <div><strong>Recomandare:</strong> ${escapeHtml(recommendation)}</div>
        </div>
      </div>
    </div>
  `;
}

function checkGroupChipLabel(key) {
  if (key === "high") return "HIGH";
  if (key === "med") return "MED";
  if (key === "low") return "LOW";
  return "ASCUNS";
}

function checkGroupMarkup(group, previewLocked) {
  return `
    <div class="check-group">
      <div class="check-group-label ${group.key}">
        <span class="chip">${checkGroupChipLabel(group.key)}</span>
        <span>${escapeHtml(group.label)}</span>
      </div>
      ${group.items.map((check, index) => checkRowMarkup(check, { open: !previewLocked && group.key === "high" && index === 0 })).join("")}
    </div>
  `;
}

function lockedProblemsGroupMarkup(items) {
  if (!items.length) return "";

  return `
    <div class="check-group check-group-locked">
      <div class="check-group-label locked">
        <span class="chip">${checkGroupChipLabel("locked")}</span>
        <span>Probleme ascunse în preview</span>
      </div>
      <div class="locked-group-callout">
        <span>${lockIcon(13)} Detaliile sunt ascunse în preview. Introdu email-ul ca să vezi problema exactă și pașii de rezolvare.</span>
        <button type="button" class="locked-group-link" data-focus-email data-email-source="locked_problem_group">Trimite pe email</button>
      </div>
      ${items.map((check) => checkRowMarkup(check)).join("")}
    </div>
  `;
}

function categorySectionMarkup(category, { previewLocked = false } = {}) {
  const visibleGroups = previewLocked
    ? category.groups
      .map((group) => ({
        ...group,
        items: group.items.filter((check) => !check?.locked),
      }))
      .filter((group) => group.items.length > 0)
    : category.groups;
  const lockedItems = previewLocked
    ? category.groups.flatMap((group) => group.items.filter((check) => check?.locked))
    : [];

  return `
    <section class="cat-section fade-up">
      <div class="cat-section-head">
        <div class="title-group">
          <div class="big-icon">${categoryIcon(category.key, 22)}</div>
          <h2>
            <small>categoria · ${escapeHtml(category.code)}</small>
            ${escapeHtml(category.name)}
          </h2>
        </div>
        <div class="score-badge">
          <div class="val">${escapeHtml(category.score)}<span style="color:var(--text-mute);font-size:18px;font-weight:500">/${escapeHtml(category.max)}</span></div>
          <div class="impact ${escapeHtml(category.impact)}">${escapeHtml(categoryImpactLabel(category.impact))}</div>
        </div>
      </div>

      ${visibleGroups.map((group) => checkGroupMarkup(group, previewLocked)).join("")}
      ${lockedProblemsGroupMarkup(lockedItems)}
    </section>
  `;
}

function specialistButton(actionUrl, source) {
  return `<button type="button" class="btn btn-outline" data-open-help data-help-source="${escapeHtml(source)}">Vreau s&#259; discut cu un specialist</button>`;
}

function inlineUnlockFormMarkup(ctaLabel = "Deblocheaz&#259; raportul", prefilledEmail = "") {
  const safeEmail = escapeHtml(prefilledEmail || "");
  return `
    <form class="locked-form" data-report-email-form novalidate>
      <input type="hidden" name="first_name" value="">
      <input type="hidden" name="source" value="inline_unlock">
      <input type="hidden" name="newsletter_optin" value="0">
      <div class="locked-input-wrap" data-report-email-anchor>
        ${mailIcon(16)}
        <input type="email" class="locked-input" name="email" placeholder="nume@domeniu.ro" value="${safeEmail}" required>
        <button type="submit" class="btn btn-primary" data-loading-label="Se deblocheaz&#259;...">${ctaLabel}</button>
      </div>
      <label class="locked-consent">
        <input type="checkbox" name="wants_plan" value="1">
        <span>Vreau si sfaturi de remediere SEO pe email.</span>
      </label>
      <label class="locked-consent">
        <input type="checkbox" name="consent_terms" value="1" required>
        <span>Am citit &#537;i accept <a href="https://novaweb.ro/termeni-si-conditii/" target="_blank" rel="noopener">Termenii</a> &#537;i <a href="https://novaweb.ro/politica-de-confidentialitate/" target="_blank" rel="noopener">Politica de confiden&#539;ialitate</a>.</span>
      </label>
      <div class="report-email-feedback" data-email-feedback aria-live="polite"></div>
      <div class="locked-meta">
        <span class="locked-meta-item">${lockIcon(11)} F&#259;r&#259; newsletter implicit</span>
        <span class="locked-meta-item">${smallCheckIcon(11)} PDF trimis imediat</span>
        <span class="locked-meta-item">${smallCheckIcon(11)} Gratuit, f&#259;r&#259; card</span>
      </div>
    </form>
  `;
}

function lockedProblemCount(report, categories = []) {
  const direct = Number(report?.score?.checks_locked_count);
  if (Number.isFinite(direct)) return Math.max(0, direct);

  return categories.reduce(
    (sum, category) => sum + category.checks.filter((check) => check?.locked || check?.sev === "warn" || check?.sev === "fail").length,
    0
  );
}

function visiblePreviewCount(report, categories = []) {
  const direct = Number(report?.score?.checks_preview_count);
  if (Number.isFinite(direct)) return Math.max(0, direct);

  return categories.reduce(
    (sum, category) => sum + category.checks.filter((check) => !check?.locked && check?.sev === "pass").length,
    0
  );
}

function lockedProblemGateMarkup(report, categories, actionUrl, prefilledEmail = "") {
  const lockedCount = lockedProblemCount(report, categories);
  const visibleCount = visiblePreviewCount(report, categories);

  if (!lockedCount) {
    return "";
  }

  return `
    <div class="locked-wrap fade-up">
      <div class="locked-overlay">
        <div class="locked-card">
          <div class="locked-badge"><span class="locked-dot"></span><span>Raport complet</span></div>
          <h3 class="locked-title">Deblochezi <span class="accent">${lockedCount} probleme</span> și pașii de rezolvare.</h3>
          <p class="locked-sub">Preview-ul îți arată scorurile, categoriile și ${visibleCount} verificări OK. Pentru problemele ascunse, introdu email-ul și primești explicațiile complete, recomandările concrete și PDF-ul prioritizat.</p>
          ${inlineUnlockFormMarkup("Vezi problemele și pașii de rezolvare", prefilledEmail)}
          <div class="locked-divider"><span>sau</span></div>
          <div class="locked-alt">
            ${specialistButton(actionUrl, "locked_report")}
          </div>
        </div>
      </div>
    </div>
  `;
}

function leadGateMarkup(prefilledEmail = "") {
  const safeEmail = escapeHtml(prefilledEmail || "");
  return `
    <div class="lead-gate fade-up" id="reportLeadGate">
      <div>
        <div class="gate-eyebrow">${mailIcon(14)} Raport complet pe email</div>
        <h2>Prime&#537;ti raport SEO complet, cu PDF detaliat direct &icirc;n inbox.</h2>
        <p>Toate verific&#259;rile (nu doar primele 5), recomand&#259;rile prioritizate &#537;i pa&#537;ii concre&#539;i de implementare. Gratuit, f&#259;r&#259; obliga&#539;ii.</p>
        <ul class="lead-benefits">
          <li><div class="ico">${checkStatusIcon("ok")}</div><div><b>Raport PDF complet</b> cu toate verific&#259;rile: structura, metadate, localizare.</div></li>
          <li><div class="ico">${checkStatusIcon("ok")}</div><div><b>Recomand&#259;ri prioritizate</b> pe Impact (mare/mediu/redus), ca s&#259; &#537;tii ce merit&#259; f&#259;cut prima dat&#259;.</div></li>
          <li><div class="ico">${checkStatusIcon("ok")}</div><div><b>Pași de remediere clari</b> cu recomandări concrete de implementare.</div></li>
          <li><div class="ico">${checkStatusIcon("ok")}</div><div><b>Gratuit &#537;i f&#259;r&#259; obliga&#539;ii.</b> F&#259;r&#259; spam, f&#259;r&#259; v&acirc;nz&#259;ri agresive.</div></li>
        </ul>
      </div>

      <form class="lead-form" data-report-email-form novalidate data-report-email-anchor>
        <div class="lead-form-title">Prime&#537;te raportul pe email</div>
        <div class="lead-form-sub">Trimitem raportul complet &icirc;n ~30 de secunde.</div>

        <input type="hidden" name="first_name" value="">
        <input type="hidden" name="source" value="inline_unlock">

        <label class="field-label">Email</label>
        <input class="input" type="email" name="email" placeholder="email@exemplu.ro" value="${safeEmail}" required>

        <label class="checkbox-row">
          <input type="checkbox" name="wants_plan" value="1">
          <span>Vreau sfaturi de remediere SEO pe email.</span>
        </label>

        <label class="checkbox-row">
          <input type="checkbox" name="newsletter_optin" value="1">
          <span>Sunt de acord s&#259; primesc newsletter cu resurse, oferte &#537;i nout&#259;&#539;i Novaweb.</span>
        </label>

        <label class="checkbox-row">
          <input type="checkbox" name="consent_terms" value="1" required>
          <span>Am citit &#537;i accept <a href="https://novaweb.ro/termeni-si-conditii/" target="_blank" rel="noopener">Termenii</a> &#537;i <a href="https://novaweb.ro/politica-de-confidentialitate/" target="_blank" rel="noopener">Politica de confiden&#539;ialitate</a>.</span>
        </label>

        <button type="submit" class="btn btn-primary btn-block">${mailIcon(16)} Trimite raportul pe email</button>
        <div class="report-email-feedback" data-email-feedback aria-live="polite"></div>
        <div class="report-email-note">${lockIcon(11)} Datele sunt securizate &middot; ${clockIcon(11)} Primul raport &icirc;n &lt; 30s</div>
      </form>
    </div>
  `;
}

function resendEmailMarkup(prefilledEmail = "") {
  const safeEmail = escapeHtml(prefilledEmail || "");
  return `
    <section class="report-section-block lead-gate fade-up" id="reportLeadGate">
      <div>
        <div class="gate-eyebrow">${mailIcon(14)} Retrimite raportul</div>
        <h2>Ai ultimul raport disponibil aici.</h2>
        <p>Îl poți retrimite pe email oricând, fără să rulezi un audit nou.</p>
      </div>

      <form class="lead-form" data-report-email-form novalidate data-report-email-anchor>
        <div class="lead-form-title">Trimite raportul din nou</div>
        <div class="lead-form-sub">Folosim raportul deja generat, fără să consumăm o rulare nouă.</div>

        <input type="hidden" name="first_name" value="">
        <input type="hidden" name="source" value="resend_report_form">
        <input type="hidden" name="newsletter_optin" value="0">

        <label class="field-label">Email</label>
        <input class="input" type="email" name="email" placeholder="email@exemplu.ro" value="${safeEmail}" required>

        <label class="checkbox-row">
          <input type="checkbox" name="wants_plan" value="1">
          <span>Vreau sfaturi de remediere SEO pe email.</span>
        </label>

        <label class="checkbox-row">
          <input type="checkbox" name="consent_terms" value="1" required>
          <span>Am citit &#537;i accept <a href="https://novaweb.ro/termeni-si-conditii/" target="_blank" rel="noopener">Termenii</a> &#537;i <a href="https://novaweb.ro/politica-de-confidentialitate/" target="_blank" rel="noopener">Politica de confiden&#539;ialitate</a>.</span>
        </label>

        <button type="submit" class="btn btn-primary btn-block">${mailIcon(16)} Retrimite raportul pe email</button>
        <div class="report-email-feedback" data-email-feedback aria-live="polite"></div>
      </form>
    </section>
  `;
}

function unlockedDeliveryMarkup(prefilledEmail = "") {
  const safeEmail = escapeHtml(prefilledEmail || "");
  return `
    <section class="report-section-block lead-gate report-delivery-gate fade-up" id="reportLeadGate">
      <div>
        <div class="gate-eyebrow">${smallCheckIcon(14)} Raport deblocat</div>
        <h2>PDF-ul complet este disponibil pentru descarcare.</h2>
        <p>Ai acces la raportul complet dupa email: toate verificarile, recomandarile prioritizate si pasii de implementare.</p>
        <div class="report-delivery-actions">
          <button type="button" class="btn btn-primary" data-download-pdf>${downloadIcon(16)} Descarca PDF</button>
          <button type="button" class="btn btn-outline" data-open-share>${mailIcon(16)} Retrimite pe email</button>
        </div>
        <div class="locked-meta">
          <span class="locked-meta-item">${smallCheckIcon(11)} Activat dupa email</span>
          <span class="locked-meta-item">${downloadIcon(11)} PDF complet</span>
          <span class="locked-meta-item">${clockIcon(11)} Disponibil imediat</span>
        </div>
      </div>

      <form class="lead-form" data-report-email-form novalidate data-report-email-anchor>
        <div class="lead-form-title">Trimite raportul din nou</div>
        <div class="lead-form-sub">Folosim raportul deja generat, fara sa consumam o rulare noua.</div>

        <input type="hidden" name="first_name" value="">
        <input type="hidden" name="source" value="resend_report_form">
        <input type="hidden" name="newsletter_optin" value="0">

        <label class="field-label">Email</label>
        <input class="input" type="email" name="email" placeholder="email@exemplu.ro" value="${safeEmail}" required>

        <label class="checkbox-row">
          <input type="checkbox" name="wants_plan" value="1">
          <span>Vreau sfaturi de remediere SEO pe email.</span>
        </label>

        <label class="checkbox-row">
          <input type="checkbox" name="consent_terms" value="1" required>
          <span>Am citit &#537;i accept <a href="https://novaweb.ro/termeni-si-conditii/" target="_blank" rel="noopener">Termenii</a> &#537;i <a href="https://novaweb.ro/politica-de-confidentialitate/" target="_blank" rel="noopener">Politica de confiden&#539;ialitate</a>.</span>
        </label>

        <button type="submit" class="btn btn-primary btn-block">${mailIcon(16)} Retrimite raportul pe email</button>
        <div class="report-email-feedback" data-email-feedback aria-live="polite"></div>
      </form>
    </section>
  `;
}

function categoriesMarkup(report, categories, isUnlocked, actionUrl, prefilledEmail = "") {
  const detailCategories = categories.filter((category) => category.checks.length > 0);

  if (!detailCategories.length) {
    return `
      <article class="cat-section">
        <div class="cat-section-head">
          <div class="title-group">
            <div class="big-icon">${categoryIcon("content", 22)}</div>
            <h2><small>categoria · REPORT</small>Nicio verificare disponibilă</h2>
          </div>
        </div>
        <p style="margin:0;color:var(--text-dim)">Raportul curent nu conține suficiente date pentru randarea detaliată.</p>
      </article>
    `;
  }

  if (isUnlocked) {
    return detailCategories.map((category) => categorySectionMarkup(category)).join("");
  }

  return `
    ${detailCategories.map((category) => categorySectionMarkup(category, { previewLocked: true })).join("")}
    ${lockedProblemGateMarkup(report, detailCategories, actionUrl, prefilledEmail)}
  `;
}

function technicalDetailsMarkup(report, categories, isUnlocked, actionUrl, prefilledEmail = "") {
  const detailCategories = categories.filter((category) => category.checks.length > 0);
  const totalChecks = Number(report?.score?.checks_total ?? report?.score?.checks?.length ?? 0);
  const previewCount = Number(report?.score?.checks_preview_count ?? detailCategories.reduce((sum, category) => sum + category.checks.length, 0));
  const lockedCount = Math.max(0, Number(report?.score?.checks_locked_count ?? (totalChecks - previewCount)));

  if (!detailCategories.length) {
    return `
      <section class="report-section-block technical-section fade-up">
        ${sectionHeadMarkup("Detalii tehnice", "Verificările complete", "Nu avem suficiente date tehnice pentru randarea detaliată.")}
        <article class="cat-section">
          <div class="cat-section-head">
            <div class="title-group">
              <div class="big-icon">${categoryIcon("content", 22)}</div>
              <h2><small>categoria REPORT</small>Nicio verificare disponibilă</h2>
            </div>
          </div>
          <p style="margin:0;color:var(--text-dim)">Raportul curent nu conține suficiente date pentru randarea detaliată.</p>
        </article>
      </section>
    `;
  }

  if (isUnlocked) {
    return `
      <section class="report-section-block technical-section fade-up">
        ${sectionHeadMarkup("Detalii tehnice", "Raportul complet pentru implementare", "Accordion-ul păstrează toate verificările într-un format ușor de trimis către echipă.")}
        <details class="technical-accordion" open>
          <summary>Vezi verificările tehnice complete</summary>
          <div class="technical-category-stack">
            ${detailCategories.map((category) => categorySectionMarkup(category)).join("")}
          </div>
        </details>
      </section>
      ${resendEmailMarkup(prefilledEmail)}
    `;
  }

  return `
    <section class="report-section-block technical-section fade-up">
      ${sectionHeadMarkup(
        "Detalii tehnice",
        "Preview tehnic",
        lockedCount > 0 ? `${lockedCount} probleme rămân ascunse până la email.` : "Raportul complet se activează după email."
      )}
      <div class="technical-category-stack">
        ${detailCategories.map((category) => categorySectionMarkup(category, { previewLocked: true })).join("")}
      </div>
      ${leadGateMarkup(prefilledEmail)}
    </section>
  `;
}

export function updateTabState(dom, activeTab = "overview") {
  const panelId = `tab-${activeTab || "overview"}`;
  const hasTarget = dom.tabPanels.some((panel) => panel.id === panelId);
  const targetId = hasTarget ? panelId : "tab-overview";

  dom.tabButtons.forEach((button) => {
    const isActive = `tab-${button.dataset.tab}` === targetId;
    button.classList.toggle("active", isActive);
    button.setAttribute("aria-selected", isActive ? "true" : "false");
  });

  dom.tabPanels.forEach((panel) => {
    panel.hidden = panel.id !== targetId;
  });
}

let fadeObserver;

function observeFadeUps(scope) {
  if (typeof window === "undefined" || !scope) return;

  if (!fadeObserver && "IntersectionObserver" in window) {
    fadeObserver = new window.IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("visible");
        fadeObserver.unobserve(entry.target);
      });
    }, { threshold: 0.12 });
  }

  scope.querySelectorAll(".fade-up").forEach((element, index) => {
    element.classList.remove("visible");
    element.style.transitionDelay = `${Math.min(index * 70, 280)}ms`;

    if (fadeObserver) {
      fadeObserver.observe(element);
    } else {
      element.classList.add("visible");
    }
  });
}

function animateNumber(element, target, duration = 1400) {
  if (!element || !Number.isFinite(target)) return;

  let startTime = null;

  const tick = (timestamp) => {
    if (startTime === null) startTime = timestamp;
    const progress = Math.min(1, (timestamp - startTime) / duration);
    const eased = 1 - Math.pow(1 - progress, 3);
    element.textContent = String(Math.round(target * eased));
    if (progress < 1) {
      window.requestAnimationFrame(tick);
    }
  };

  element.textContent = "0";
  window.requestAnimationFrame(tick);
}

function animateReportScene(dom) {
  if (typeof window === "undefined") return;

  const scoreValue = dom.summaryStrip.querySelector("[data-score-value]");
  const targetScore = Number(scoreValue?.dataset.scoreValue || "");
  if (scoreValue && Number.isFinite(targetScore)) {
    scoreValue.textContent = String(targetScore);
  }

  const scoreProgress = dom.summaryStrip.querySelector("[data-score-progress]");
  if (scoreProgress) {
    const targetOffset = scoreProgress.dataset.targetOffset || "0";
    window.requestAnimationFrame(() => {
      scoreProgress.style.strokeDashoffset = targetOffset;
    });
  }

  dom.summaryStrip.querySelectorAll("[data-bar-width]").forEach((bar, index) => {
    const width = bar.dataset.barWidth || "0";
    window.setTimeout(() => {
      bar.style.width = `${width}%`;
    }, 140 + index * 60);
  });

  observeFadeUps(dom.summaryStrip);
  observeFadeUps(dom.workspaceContent);
}

export function renderLoadingWorkspace(dom, state) {
  const pendingHost = hostnameFromUrl(state.pendingUrl || "");
  if (dom.resultsToolbar) dom.resultsToolbar.hidden = true;

  dom.workspaceTitle.textContent = pendingHost ? `Analizăm ${pendingHost}` : "Pregătim raportul";
  dom.workspaceUrl.textContent = pendingHost || "Așteptăm URL-ul";
  if (dom.workspaceRunMeta) dom.workspaceRunMeta.textContent = "analiză în curs";
  if (dom.workspaceDocumentMeta) dom.workspaceDocumentMeta.textContent = documentLabel(state.currentContext);
  dom.workspaceContextBadge.textContent = state.currentContext === "local" ? "Audit local" : "Audit standard";
  dom.workspaceDate.textContent = "Analiză în curs";
  dom.workspaceMetaNote.textContent = "";
  dom.workspaceSource.textContent = "Construim structura completă a raportului.";
  dom.workspaceState.hidden = true;
  dom.workspaceContent.hidden = false;

  dom.summaryStrip.innerHTML = `
    <div class="score-hero">
      <div class="skeleton-card" style="min-height:320px"></div>
      <div class="skeleton-card" style="min-height:320px"></div>
    </div>
  `;
  dom.overviewPanel.innerHTML = `
    <div class="summary-stats">
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
      <div class="skeleton-card"></div>
    </div>
    <div class="skeleton-card" style="min-height:440px"></div>
  `;
  dom.issuesToolbar.innerHTML = "";
  dom.issuesPanel.innerHTML = "";
  dom.planPanel.innerHTML = "";
  if (dom.technicalPanel) dom.technicalPanel.innerHTML = "";
  if (dom.downloadPdfButton) {
    dom.downloadPdfButton.disabled = true;
    dom.downloadPdfButton.classList.remove("is-locked");
    dom.downloadPdfButton.removeAttribute("aria-disabled");
    dom.downloadPdfButton.removeAttribute("title");
  }
  if (dom.mobilePdfButton) {
    dom.mobilePdfButton.hidden = true;
    dom.mobilePdfButton.disabled = true;
    dom.mobilePdfButton.classList.remove("button-primary");
    dom.mobilePdfButton.classList.add("button-secondary");
  }
  if (dom.openSharePrimaryLabel) dom.openSharePrimaryLabel.textContent = "Trimite pe email";
  if (dom.mobileShareButton) dom.mobileShareButton.textContent = "Trimite raportul";
  updateTabState(dom);
}

export function renderStateCard(dom, model) {
  if (dom.resultsToolbar) dom.resultsToolbar.hidden = true;
  dom.workspaceContent.hidden = true;
  dom.workspaceState.hidden = false;
  dom.summaryStrip.innerHTML = "";
  const actions = Array.isArray(model.actions) ? model.actions : [];
  const tone = model.tone || "danger";
  const iconMarkup = tone === "warning"
    ? `
      <svg viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="11" cy="11" r="9" stroke="#f07523" stroke-width="1.8"/>
        <path d="M11 6.6V11.8" stroke="#f07523" stroke-width="2" stroke-linecap="round"/>
        <circle cx="11" cy="15.3" r="1.05" fill="#f07523"/>
      </svg>
    `
    : tone === "neutral"
      ? `
      <svg viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="11" cy="11" r="9" stroke="#6b7280" stroke-width="1.8"/>
        <path d="M8.9 8.7a2.4 2.4 0 0 1 4.7.76c0 1.64-1.63 2.08-2.4 3.07-.28.36-.43.76-.43 1.37" stroke="#6b7280" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="11" cy="15.9" r="1.05" fill="#6b7280"/>
      </svg>
    `
      : `
      <svg viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="11" cy="11" r="9" stroke="#e5322d" stroke-width="1.8"/>
        <path d="M7.5 7.5L14.5 14.5M14.5 7.5L7.5 14.5" stroke="#e5322d" stroke-width="2" stroke-linecap="round"/>
      </svg>
    `;
  dom.workspaceState.innerHTML = `
    <article class="state-card" data-tone="${escapeHtml(tone)}">
      <div class="state-icon-wrap">
        ${iconMarkup}
      </div>
      <div class="state-text">
        <h2>${escapeHtml(model.title)}</h2>
        <p>${escapeHtml(model.body)}</p>
      </div>
      <div class="state-divider" aria-hidden="true"></div>
      <div class="state-actions">
          <div class="state-actions-inner">
            ${actions.map((action) => `
              <button
                type="button"
                class="state-action-button ${escapeHtml(action.kind || "button-secondary") === "button-primary" ? "state-action-button-primary" : "state-action-button-secondary"}"
                data-state-action="${escapeHtml(action.action)}"
                data-state-source="${escapeHtml(action.source || "")}"
              >${escapeHtml(action.label)}</button>
            `).join("")}
          </div>
      </div>
    </article>
  `;
}

export function renderWorkspace(app, state, dom) {
  const report = state.currentReport;
  if (!report) return;

  const host = hostnameFromUrl(report.url_analyzed || "");
  const checks = Array.isArray(report?.score?.checks) ? report.score.checks : [];
  const counts = report?.score?.checks_counts || {};
  const passCount = Number.isFinite(Number(counts.pass)) ? Number(counts.pass) : checks.filter((check) => check?.sev === "pass").length;
  const warnCount = Number.isFinite(Number(counts.warn)) ? Number(counts.warn) : checks.filter((check) => check?.sev === "warn").length;
  const failCount = Number.isFinite(Number(counts.fail)) ? Number(counts.fail) : checks.filter((check) => check?.sev === "fail").length;
  const categories = buildCategoryModels(report);
  const recommended = recommendedAction(report);
  const actionUrl = recommended.url || app.servicesUrl || "";
  if (dom.resultsToolbar) dom.resultsToolbar.hidden = false;
  if (dom.mobileActionBar) dom.mobileActionBar.hidden = false;

  dom.workspaceState.hidden = true;
  dom.workspaceContent.hidden = false;
  dom.workspaceTitle.textContent = `Raport pentru ${host}`;
  dom.workspaceUrl.textContent = host || "Pagina analizată";
  if (dom.workspaceRunMeta) dom.workspaceRunMeta.textContent = relativeRunLabel(report.created_at);
  if (dom.workspaceDocumentMeta) dom.workspaceDocumentMeta.textContent = documentLabel(report.context);
  dom.workspaceContextBadge.textContent = report.context === "local" ? "Audit local" : "Audit standard";
  dom.workspaceDate.textContent = report.created_at || "";
  dom.workspaceMetaNote.textContent = state.rehydrated ? "Rehidratat din report token." : "Raport proaspăt generat.";
  dom.workspaceSource.textContent = report.url_analyzed || "";

  dom.summaryStrip.innerHTML = scoreHeroMarkup(report, categories, passCount, warnCount, failCount);
  dom.overviewPanel.innerHTML = `
    ${summaryStatsMarkup(report, checks)}
    ${lowScoreBannerMarkup(scoreTotal(report))}
    ${categoriesMarkup(report, categories, Boolean(state.reportUnlocked), actionUrl, state.lastUnlockEmail || "")}
    ${state.reportUnlocked ? unlockedDeliveryMarkup(state.lastUnlockEmail || "") : ""}
  `;
  dom.issuesToolbar.innerHTML = "";
  dom.issuesPanel.innerHTML = "";
  dom.planPanel.innerHTML = "";
  if (dom.technicalPanel) dom.technicalPanel.innerHTML = "";
  if (dom.downloadPdfButton) {
    dom.downloadPdfButton.disabled = false;
    dom.downloadPdfButton.classList.toggle("is-locked", !state.reportUnlocked);
    dom.downloadPdfButton.setAttribute("aria-disabled", state.reportUnlocked ? "false" : "true");
    dom.downloadPdfButton.title = state.reportUnlocked
      ? "Descarcă raportul PDF"
      : "Introdu emailul pentru a activa PDF-ul";
  }
  if (dom.mobilePdfButton) {
    dom.mobilePdfButton.hidden = !state.reportUnlocked;
    dom.mobilePdfButton.disabled = !state.reportUnlocked;
    dom.mobilePdfButton.textContent = "PDF";
    dom.mobilePdfButton.classList.toggle("button-primary", state.reportUnlocked);
    dom.mobilePdfButton.classList.toggle("button-secondary", !state.reportUnlocked);
    dom.mobilePdfButton.setAttribute("aria-disabled", state.reportUnlocked ? "false" : "true");
    dom.mobilePdfButton.title = state.reportUnlocked
      ? "Descarca raportul PDF"
      : "Introdu emailul pentru a activa PDF-ul";
  }
  if (dom.openSharePrimaryLabel) {
    const canResend = state.reportUnlocked && (state.lastUnlockEmail || state.siteAccess?.can_resend);
    dom.openSharePrimaryLabel.textContent = canResend ? "Retrimite pe email" : "Trimite pe email";
  }
  if (dom.mobileShareButton) {
    const canResend = state.reportUnlocked && (state.lastUnlockEmail || state.siteAccess?.can_resend);
    dom.mobileShareButton.textContent = canResend ? "Email" : "Trimite raportul";
    dom.mobileShareButton.classList.toggle("button-primary", !state.reportUnlocked);
    dom.mobileShareButton.classList.toggle("button-secondary", state.reportUnlocked);
  }
  updateTabState(dom);
  animateReportScene(dom);
}
