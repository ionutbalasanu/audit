import { TECHNICAL_CATEGORIES } from "./state.js";

const PRIORITY_ORDER = {
  critical: 0,
  high: 1,
  medium: 2,
  low: 3,
  insight: 4,
};

const IMPACT_ORDER = {
  high: 0,
  medium: 1,
  low: 2,
};

const DISPLAY_CATEGORIES = [
  {
    key: "performance",
    label: "Performanta",
    ids: ["lazyload_images", "image_dimensions_defined", "fonts_preload", "cls_risky_elements"],
    fallback(report) {
      const structure = Number(report?.score?.breakdown?.structure ?? 0);
      const signals = Number(report?.score?.breakdown?.signals ?? 0);
      return Math.round(((structure / 25) * 0.65 + (signals / 20) * 0.35) * 100);
    },
  },
  {
    key: "onpage",
    label: "SEO On-Page",
    ids: [
      "word_count_800",
      "intro_mentions_topic",
      "h1_single",
      "headings_hierarchy",
      "lists_tables",
      "images_in_body",
      "img_alt_ratio_80",
      "title_length_ok",
      "meta_description_ok",
      "internal_links_present",
      "external_links_present",
    ],
    fallback(report) {
      const content = Number(report?.score?.breakdown?.content ?? 0);
      const signals = Number(report?.score?.breakdown?.signals ?? 0);
      return Math.round(((content / 40) * 0.75 + (signals / 20) * 0.25) * 100);
    },
  },
  {
    key: "technical",
    label: "SEO Tehnic",
    ids: [
      "indexable",
      "canonical_present",
      "canonical_valid",
      "url_clean",
      "meta_robots_ok",
      "html_valid",
      "og_minimal",
      "schema_article_recommended",
      "faq_schema_present",
      "schema_breadcrumbs",
      "schema_image_required_fields",
    ],
    fallback(report) {
      const structure = Number(report?.score?.breakdown?.structure ?? 0);
      const signals = Number(report?.score?.breakdown?.signals ?? 0);
      return Math.round(((structure / 25) * 0.6 + (signals / 20) * 0.4) * 100);
    },
  },
  {
    key: "local",
    label: "SEO Local",
    ids: [
      "lang_ro",
      "og_locale_or_inLanguage_ro",
      "date_format_ro",
      "hreflang_pairs",
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
    fallback(report) {
      const localPercent = Number(report?.score?.local?.percent);
      if (Number.isFinite(localPercent)) return Math.round(localPercent);
      const locale = Number(report?.score?.breakdown?.locale ?? 0);
      return Math.round((locale / 15) * 100);
    },
  },
];

export function hostnameFromUrl(url) {
  try {
    return new URL(url).hostname;
  } catch {
    return url || "pagina analizata";
  }
}

export function scoreTotal(report) {
  return Number(report?.score?.total ?? 0);
}

export function verdictForScore(total) {
  if (total < 40) return { label: "Foarte slab", summary: "Pagina are blocaje majore care opresc atat vizibilitatea, cat si claritatea." };
  if (total < 60) return { label: "Fragil", summary: "Exista fundatie, dar sunt prea multe probleme ca raportul sa poata fi delegat imediat." };
  if (total < 75) return { label: "Decent", summary: "Ai baza functionala, insa sunt quick wins si blocaje care merita prioritizate." };
  if (total < 90) return { label: "Bun", summary: "Pagina este sanatoasa, iar castigurile vin din rafinare si prioritizare disciplinata." };
  return { label: "Foarte bun", summary: "Pagina este solida on-page; diferenta vine din consistenta si scalare." };
}

export function formatAnalysisDate(createdAt) {
  if (!createdAt) return "Analiza recenta";
  const parsed = new Date(createdAt);
  if (Number.isNaN(parsed.getTime())) return "Analiza recenta";
  return new Intl.DateTimeFormat("ro-RO", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(parsed);
}

export function priorityForCheck(check) {
  const severity = check?.sev || "warn";
  const impact = check?.business_impact_magnitude || "low";

  if (severity === "fail" && impact === "high") return "critical";
  if (severity === "fail" && impact === "medium") return "high";
  if (severity === "fail") return "medium";
  if (impact === "low") return "insight";
  return "low";
}

export function ownerForCheck(check) {
  switch (check?.related_service) {
    case "content":
      return "Content";
    case "local_seo":
      return "Local SEO";
    case "technical":
    case "website_redesign":
      return "Dev";
    default:
      return "SEO";
  }
}

export function complexityLabel(value) {
  switch (value) {
    case "easy":
      return "Quick";
    case "hard":
      return "Hard";
    default:
      return "Medium";
  }
}

export function severityLabel(value) {
  switch (value) {
    case "fail":
      return "Problem";
    case "warn":
      return "Warning";
    default:
      return "OK";
  }
}

function sortIssues(checks) {
  return [...checks].sort((left, right) => {
    const leftPriority = PRIORITY_ORDER[priorityForCheck(left)] ?? 99;
    const rightPriority = PRIORITY_ORDER[priorityForCheck(right)] ?? 99;
    if (leftPriority !== rightPriority) return leftPriority - rightPriority;

    const leftImpact = IMPACT_ORDER[left?.business_impact_magnitude || "low"] ?? 99;
    const rightImpact = IMPACT_ORDER[right?.business_impact_magnitude || "low"] ?? 99;
    if (leftImpact !== rightImpact) return leftImpact - rightImpact;

    return String(left?.label || left?.id || "").localeCompare(String(right?.label || right?.id || ""), "ro");
  });
}

function weightedScore(checks) {
  if (!checks.length) return null;

  const total = checks.reduce((sum, check) => {
    if (check.sev === "pass") return sum + 1;
    if (check.sev === "warn") return sum + 0.5;
    return sum;
  }, 0);

  return Math.round((total / checks.length) * 100);
}

export function getProblemChecks(report) {
  return (report?.score?.checks || []).filter((check) => check?.sev && check.sev !== "pass");
}

export function getTopIssues(report, limit = 3) {
  return sortIssues(getProblemChecks(report)).slice(0, limit);
}

export function quickWins(report, limit = 3) {
  const issues = getProblemChecks(report).filter((check) => (check?.fix_complexity || "medium") === "easy");
  return sortIssues(issues).slice(0, limit);
}

export function issueCounts(report) {
  const counts = { all: 0, critical: 0, high: 0, medium: 0, low: 0, insight: 0 };
  getProblemChecks(report).forEach((check) => {
    counts.all += 1;
    counts[priorityForCheck(check)] += 1;
  });
  return counts;
}

export function filterIssues(report, filter) {
  const issues = sortIssues(getProblemChecks(report));
  if (!filter || filter === "all") return issues;
  return issues.filter((issue) => priorityForCheck(issue) === filter);
}

export function recommendedAction(report) {
  const service = report?.business?.recommended_service || {};
  return {
    label: service?.cta_primary || service?.name || "Solicită ajutor",
    name: service?.name || "Ajutor contextual",
    url: service?.url || "",
  };
}

export function executiveSummary(report) {
  const verdict = verdictForScore(scoreTotal(report));
  const blockers = getTopIssues(report, 3).map((issue) => issue.label).join(", ");
  const nextAction = recommendedAction(report).name;

  return `Raport pentru ${hostnameFromUrl(report?.url_analyzed || "")}. Verdict: ${verdict.label}. Scor: ${scoreTotal(report)}/100. Blocaje prioritare: ${blockers || "nu au fost identificate probleme prioritare"}. Urmatorul pas recomandat: ${nextAction}.`;
}

export function technicalGroups(report, search, showOnlyProblems) {
  const query = String(search || "").trim().toLowerCase();
  let checks = [...(report?.score?.checks || [])];

  if (showOnlyProblems) {
    checks = checks.filter((check) => check.sev !== "pass");
  }

  if (query) {
    checks = checks.filter((check) =>
      `${check.id || ""} ${check.label || ""} ${check.rule || ""} ${check.note || ""} ${check.tip || ""}`
        .toLowerCase()
        .includes(query)
    );
  }

  const ids = new Set(checks.map((check) => check.id));
  const groups = TECHNICAL_CATEGORIES.map((group) => {
    const items = group.ids
      .map((id) => checks.find((candidate) => candidate.id === id))
      .filter(Boolean);

    items.forEach((item) => ids.delete(item.id));
    return { title: group.title, items };
  }).filter((group) => group.items.length > 0);

  const leftovers = checks.filter((check) => ids.has(check.id));
  if (leftovers.length > 0) {
    groups.push({ title: "Altele", items: sortIssues(leftovers) });
  }

  return groups;
}

export function categoryScores(report) {
  const checks = report?.score?.checks || [];
  return DISPLAY_CATEGORIES.map((cat) => {
    const catChecks = checks.filter((check) => cat.ids.includes(check.id));
    const fallback = typeof cat.fallback === "function" ? cat.fallback(report) : null;

    if (catChecks.length === 0) {
      return { key: cat.key, label: cat.label, score: fallback, checks: [] };
    }

    const passing = catChecks.filter((check) => check.sev === "pass").length;
    const failing = catChecks.filter((check) => check.sev === "fail").length;
    const warning = catChecks.filter((check) => check.sev === "warn").length;
    const score = weightedScore(catChecks) ?? fallback;

    return { key: cat.key, label: cat.label, score, checks: catChecks, passing, failing, warning };
  });
}

export function technicalSummary(report, search, showOnlyProblems) {
  return technicalGroups(report, search, showOnlyProblems)
    .map((group) => {
      const rows = group.items
        .map((item) => `- ${item.label || item.id}: ${severityLabel(item.sev)}. ${(item.note || item.rule || item.tip || "").trim()}`)
        .join("\n");
      return `${group.title}\n${rows}`;
    })
    .join("\n\n");
}

function dependencyForBucket(id) {
  switch (id) {
    case "quick":
      return "Niciuna";
    case "content":
      return "Dupa quick wins";
    case "technical":
      return "Poate cere acces dev";
    case "local":
      return "Necesita context local validat";
    default:
      return "Dupa primele remedieri";
  }
}

export function bucketActions(report) {
  const actions = report?.business?.action_plan || [];
  const issueMap = new Map((report?.business?.top_business_issues || []).map((issue) => [issue.label, issue]));
  const buckets = [
    { id: "quick", title: "Quick wins", items: [] },
    { id: "content", title: "Content & clarity", items: [] },
    { id: "technical", title: "Technical fixes", items: [] },
    { id: "local", title: "Local upgrades", items: [] },
    { id: "scale", title: "Scale next", items: [] },
  ];
  const lookup = new Map(buckets.map((bucket) => [bucket.id, bucket]));

  actions.forEach((action) => {
    const issue = issueMap.get(action.title) || {};
    const relatedService = action.related_service || issue.related_service || "seo_optimization";
    const complexity = action.fix_complexity || issue.fix_complexity || "medium";

    let bucketId = "scale";
    if (complexity === "easy") bucketId = "quick";
    else if (relatedService === "content") bucketId = "content";
    else if (relatedService === "technical" || relatedService === "website_redesign") bucketId = "technical";
    else if (relatedService === "local_seo") bucketId = "local";

    lookup.get(bucketId).items.push({
      title: action.title || "Actiune recomandata",
      why: action.why || issue.impact_text || "",
      how: action.how || issue.tip || "",
      impact: issue.business_impact_magnitude || "medium",
      effort: complexityLabel(complexity),
      owner: ownerForCheck({ related_service: relatedService }),
      duration: action.duration || "n/a",
      dependency: dependencyForBucket(bucketId),
      delegatable: complexity === "hard" || relatedService === "technical" || relatedService === "website_redesign",
    });
  });

  return buckets.filter((bucket) => bucket.items.length > 0);
}
