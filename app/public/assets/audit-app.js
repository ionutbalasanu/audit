import { copyText, normalizeUrl, requestAudit, requestReport, sendLeadRequest, sendReportEmail } from "./api.js";
import { createDrawerController } from "./drawers.js";
import {
  buildLeadMessage,
  setFeedback,
  validateEmail,
  validatePhone,
} from "./forms.js";
import { executiveSummary, recommendedAction, scoreTotal, technicalSummary } from "./report-normalize.js";
import { renderContextToggle, syncSurfaceMode } from "./render-landing.js";
import { renderLoadingWorkspace, renderStateCard, renderWorkspace, updateTabState } from "./render-workspace.js";
import { createInitialState, PROGRESS_STAGES, TABS } from "./state.js";

const app = window.NOVA_AUDIT || {};
const state = createInitialState(app);
const drawers = createDrawerController(document);
const LAST_UNLOCK_EMAIL_STORAGE_KEY = "nova_audit_last_email";
const LAST_REPORT_REF_STORAGE_KEY = "nova_audit_last_report_ref";
const STICKY_CTA_CLOSED_KEY = "nova_audit_sticky_closed_until";
const EXIT_INTENT_SEEN_KEY = "nova_audit_exit_intent_seen_until";
const CTA_SUPPRESS_MS = 24 * 60 * 60 * 1000;
const STICKY_CTA_ENABLED = false;
const trackedReportGateTokens = new Set();
const trackedLowScoreBannerTokens = new Set();
let activePlanPrompt = null;
let recaptchaLoadPromise = null;
let landingRevealObserver = null;
const RECAPTCHA_READY_TIMEOUT_MS = 8000;
const LANDING_REVEAL_SELECTOR = [
  ".hero-left",
  ".audit-panel",
  ".trust-pill",
  "#landingEducation > section",
  ".info-card",
  ".step-card",
  ".range-row",
  ".checklist li",
  ".final-cta",
  ".report-contact-section",
  ".faq-item",
].join(",");

const dom = {
  root: document.getElementById("appRoot"),
  landing: document.getElementById("landingSurface"),
  landingEducation: document.getElementById("landingEducation"),
  workspace: document.getElementById("workspaceShell"),
  resultsToolbar: document.getElementById("resultsToolbar"),
  workspaceContent: document.getElementById("workspaceContent"),
  workspaceState: document.getElementById("workspaceState"),
  workspaceTitle: document.getElementById("workspaceTitle"),
  workspaceUrl: document.getElementById("workspaceUrl"),
  workspaceRunMeta: document.getElementById("workspaceRunMeta"),
  workspaceDocumentMeta: document.getElementById("workspaceDocumentMeta"),
  workspaceContextBadge: document.getElementById("workspaceContextBadge"),
  workspaceDate: document.getElementById("workspaceDate"),
  workspaceMetaNote: document.getElementById("workspaceMetaNote"),
  workspaceSource: document.getElementById("workspaceSource"),
  workspaceProgress: document.getElementById("workspaceProgress"),
  progressTitle: document.getElementById("progressTitle"),
  progressStep: document.getElementById("progressStep"),
  progressBar: document.getElementById("progressBar"),
  summaryStrip: document.getElementById("summaryStrip"),
  overviewPanel: document.getElementById("overviewPanel"),
  issuesToolbar: document.getElementById("issuesToolbar"),
  issuesPanel: document.getElementById("issuesPanel"),
  planPanel: document.getElementById("planPanel"),
  technicalPanel: document.getElementById("technicalPanel"),
  technicalSearch: document.getElementById("technicalSearch"),
  technicalProblemsOnly: document.getElementById("technicalProblemsOnly"),
  technicalCopyMessage: document.getElementById("technicalCopyMessage"),
  shareExecutiveSummary: document.getElementById("shareExecutiveSummary"),
  sharePrimaryTitle: document.getElementById("sharePrimaryTitle"),
  shareUnlockNotice: document.getElementById("shareUnlockNotice"),
  shareFormTitle: document.getElementById("shareFormTitle"),
  shareFormCopy: document.getElementById("shareFormCopy"),
  shareLinkInput: document.getElementById("shareLinkInput"),
  shareCopyMessage: document.getElementById("shareCopyMessage"),
  copyLinkButton: document.getElementById("copyLinkButton"),
  copySummaryButton: document.getElementById("copySummaryButton"),
  localEmail: document.getElementById("localEmail"),
  localFirstName: document.getElementById("localFirstName"),
  consentNewsletter: document.getElementById("consentNewsletter"),
  consentTerms: document.getElementById("consentTerms"),
  emailReportForm: document.getElementById("emailReportForm"),
  localMsg: document.getElementById("localMsg"),
  helpContextNote: document.getElementById("helpContextNote"),
  leadRequestForm: document.getElementById("leadRequestForm"),
  leadName: document.getElementById("leadName"),
  leadPhone: document.getElementById("leadPhone"),
  leadEmail: document.getElementById("leadEmail"),
  leadMessage: document.getElementById("leadMessage"),
  leadUrgent: document.getElementById("leadUrgent"),
  leadTerms: document.getElementById("leadTerms"),
  helpNewsletterOptin: document.getElementById("helpNewsletterOptin"),
  leadWebsiteTrap: document.getElementById("leadWebsiteTrap"),
  leadMsg: document.getElementById("leadMsg"),
  reportContactSection: document.getElementById("reportContactSection"),
  reportContactContext: document.getElementById("reportContactContext"),
  reportContactForm: document.getElementById("reportContactForm"),
  reportContactName: document.getElementById("reportContactName"),
  reportContactEmail: document.getElementById("reportContactEmail"),
  reportContactMessage: document.getElementById("reportContactMessage"),
  reportContactTerms: document.getElementById("reportContactTerms"),
  reportContactWebsiteTrap: document.getElementById("reportContactWebsiteTrap"),
  reportContactMsg: document.getElementById("reportContactMsg"),
  urlInput: document.getElementById("urlInput"),
  urlError: document.getElementById("urlError"),
  scoreForm: document.getElementById("scoreForm"),
  contextHint: document.getElementById("contextHint"),
  contextButtons: Array.from(document.querySelectorAll(".context-toggle[data-type]")),
  openSharePrimary: document.getElementById("openSharePrimary"),
  openSharePrimaryLabel: document.getElementById("openSharePrimaryLabel"),
  openHelpPrimary: document.getElementById("openHelpPrimary"),
  downloadPdfButton: document.getElementById("downloadPdfButton"),
  rerunAuditButton: document.getElementById("rerunAuditButton"),
  mobileActionBar: document.getElementById("mobileActionBar"),
  mobilePdfButton: document.getElementById("mobilePdfButton"),
  mobileShareButton: document.getElementById("mobileShareButton"),
  mobileHelpButton: document.getElementById("mobileHelpButton"),
  stickyCtaDesktop: document.getElementById("stickyCtaDesktop"),
  stickyCtaMobile: document.getElementById("stickyCtaMobile"),
  exitIntentModal: document.getElementById("exitIntentModal"),
  exitIntentEmail: document.getElementById("exitIntentEmail"),
  reportLimitModal: document.getElementById("reportLimitModal"),
  reportLimitTitle: document.getElementById("reportLimitTitle"),
  reportLimitCopy: document.getElementById("reportLimitCopy"),
  reportLimitMeta: document.getElementById("reportLimitMeta"),
  reportLimitHelp: document.getElementById("reportLimitHelp"),
  planPromptModal: document.getElementById("planPromptModal"),
  planPromptAccept: document.getElementById("planPromptAccept"),
  planPromptDecline: document.getElementById("planPromptDecline"),
  mobileNav: document.querySelector(".nav-mobile"),
  mobileNavLinks: Array.from(document.querySelectorAll(".nav-mobile-links a, .nav-mobile-cta")),
  globalToast: document.getElementById("globalToast"),
  tabButtons: Array.from(document.querySelectorAll(".tab-pill[data-tab]")),
  tabPanels: Array.from(document.querySelectorAll(".tab-panel")),
  technicalToolbar: document.querySelector(".technical-toolbar"),
};

state.lastUnlockEmail = readStoredUnlockEmail();

function getReportToken(report = state.currentReport) {
  return report?.report_token || report?.score?.report_token || "";
}

function persistLastReportReference(report = state.currentReport) {
  const token = getReportToken(report);
  if (!token || !report?.url_analyzed) return;

  try {
    window.sessionStorage?.setItem(LAST_REPORT_REF_STORAGE_KEY, JSON.stringify({
      token,
      url: report.url_analyzed,
      site_key: siteKeyFromUrl(report.url_analyzed),
      context: report.context === "local" ? "local" : "article",
      saved_at: Date.now(),
    }));
  } catch {
    // Ignore storage failures; the current in-memory report is still usable.
  }
}

function readLastReportReference(preferredUrl = "") {
  try {
    const raw = window.sessionStorage?.getItem(LAST_REPORT_REF_STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    const token = String(parsed?.token || "");
    if (!/^[a-f0-9]{32}$/i.test(token)) return null;

    const preferredSite = siteKeyFromUrl(preferredUrl);
    const savedSite = String(parsed?.site_key || siteKeyFromUrl(parsed?.url || ""));
    if (preferredSite && savedSite && preferredSite !== savedSite) {
      return null;
    }

    return { ...parsed, token };
  } catch {
    return null;
  }
}

function readStoredUnlockEmail() {
  try {
    return String(window.sessionStorage?.getItem(LAST_UNLOCK_EMAIL_STORAGE_KEY) || "").trim();
  } catch {
    return "";
  }
}

function persistSessionUnlock(email = "") {
  const normalizedEmail = String(email || "").trim();
  if (!normalizedEmail) {
    return;
  }

  state.lastUnlockEmail = normalizedEmail;

  try {
    window.sessionStorage?.setItem(LAST_UNLOCK_EMAIL_STORAGE_KEY, normalizedEmail);
  } catch {
    // Ignore storage failures and keep the email in memory.
  }
}

function trackEvent(name, params = {}) {
  if (typeof window.gtag !== "function") return;

  window.gtag("event", name, {
    event_category: "audit_seo_gratuit",
    ...params,
  });
}

function safeFormSource(form, fallback = "unknown") {
  return String(form?.querySelector('[name="source"]')?.value || fallback).trim() || fallback;
}

function trackFormValidationError(form, reason, fallbackSource = "unknown") {
  trackEvent("form_validation_error", {
    context: state.currentContext,
    source: safeFormSource(form, fallbackSource),
    reason,
  });
}

function trackReportGateView(report) {
  if (!report || report.locked === false) return;
  const lockedCount = Number(report?.score?.checks_locked_count || 0);
  if (lockedCount <= 0) return;

  const key = getReportToken(report) || `${report.url_analyzed || "unknown"}:${report.created_at || ""}`;
  if (!key || trackedReportGateTokens.has(key)) return;

  trackedReportGateTokens.add(key);
  trackEvent("report_gate_view", {
    context: report.context === "local" ? "local" : "article",
    source: "locked_report",
    locked: true,
  });
}

function trackLowScoreBannerView(report, { rehydrated = false } = {}) {
  if (rehydrated || scoreTotal(report) >= 70) return;

  const key = getReportToken(report) || `${report?.url_analyzed || "unknown"}:${report?.created_at || ""}`;
  if (!key || trackedLowScoreBannerTokens.has(key)) return;

  trackedLowScoreBannerTokens.add(key);
  trackEvent("cta_view", {
    context: report?.context === "local" ? "local" : "article",
    source: "low_score_banner",
  });
}

function applyReturnedReport(report, { email = "", rehydrated = false } = {}) {
  if (!report) return false;

  state.currentReport = report;
  state.currentContext = report.context === "local" ? "local" : "article";
  state.pendingUrl = report.url_analyzed || state.pendingUrl;
  state.rehydrated = rehydrated;
  state.reportUnlocked = report.locked === false;
  state.siteAccess = report.site_access || null;

  if (email) {
    persistSessionUnlock(email);
  }

  persistLastReportReference(report);
  return true;
}

function syncKnownEmails() {
  const email = state.lastUnlockEmail || "";
  if (!email) return;

  if (dom.localEmail) dom.localEmail.value = email;
  if (dom.leadEmail && !dom.leadEmail.value.trim()) dom.leadEmail.value = email;
  if (dom.reportContactEmail && !dom.reportContactEmail.value.trim()) dom.reportContactEmail.value = email;
}

function setMode(mode) {
  state.mode = mode;
  syncSurfaceMode(
    {
      root: dom.root,
      landing: dom.landing,
      education: dom.landingEducation,
      workspace: dom.workspace,
      mobileActionBar: dom.mobileActionBar,
    },
    mode
  );
}

function scrollToElement(element, behavior = "smooth", block = "start") {
  if (!element) return;
  window.requestAnimationFrame(() => {
    element.scrollIntoView({ behavior, block });
  });
}

function scrollToWorkspace(behavior = "smooth") {
  scrollToElement(dom.workspace, behavior);
}

function goToLanding() {
  if (dom.urlInput) {
    setMode("landing");
    state.reportUnlocked = false;
    syncHistory("");
    dom.workspaceState.hidden = true;
    dom.workspaceContent.hidden = true;
    hideStickyCtas();
    dom.urlInput.focus();
    scrollToElement(dom.urlInput);
    return;
  }

  window.location.href = window.location.pathname;
}

function setContext(nextContext) {
  state.currentContext = nextContext === "local" ? "local" : "article";
  renderContextToggle(dom.contextButtons, dom.contextHint, state.currentContext);
}

function showToast(message) {
  if (!dom.globalToast) return;
  dom.globalToast.textContent = message;
  dom.globalToast.hidden = false;
  window.clearTimeout(state.timers.toast);
  state.timers.toast = window.setTimeout(() => {
    dom.globalToast.hidden = true;
  }, 2400);
}

function handleDownloadPdf() {
  if (!state.currentReport) return;

  if (!state.reportUnlocked) {
    showPdfUnlockPrompt();
    return;
  }

  const reportToken = getReportToken(state.currentReport);
  if (!reportToken || !app.pdfUrl) return;

  trackEvent("pdf_download_click", { context: state.currentContext });
  window.location.assign(`${app.pdfUrl}?token=${encodeURIComponent(reportToken)}`);
}

function reportEmailTarget() {
  const anchor = document.querySelector("[data-report-email-anchor]") || document.getElementById("reportLeadGate");
  const form = anchor?.closest?.("[data-report-email-form]") || document.querySelector("[data-report-email-form]");
  const feedback = form?.querySelector("[data-email-feedback]") || document.querySelector("[data-email-feedback]");
  const input = form?.querySelector?.('input[type="email"]');

  return { anchor, form, feedback, input };
}

function recaptchaSiteKey() {
  return String(app.recaptchaSiteKey || "").trim();
}

function loadRecaptcha() {
  const siteKey = recaptchaSiteKey();
  if (!siteKey) return Promise.resolve(false);
  if (window.grecaptcha?.execute) return Promise.resolve(true);
  if (recaptchaLoadPromise) return recaptchaLoadPromise;

  recaptchaLoadPromise = new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = `https://www.google.com/recaptcha/api.js?render=${encodeURIComponent(siteKey)}`;
    if (app.cspNonce) script.nonce = app.cspNonce;
    script.async = true;
    script.defer = true;
    script.onload = () => resolve(true);
    script.onerror = () => {
      recaptchaLoadPromise = null;
      reject(new Error("recaptcha_load_failed"));
    };
    document.head.appendChild(script);
  });

  return recaptchaLoadPromise;
}

function waitForRecaptchaApi(timeoutMs = RECAPTCHA_READY_TIMEOUT_MS) {
  return new Promise((resolve, reject) => {
    const startedAt = Date.now();

    const poll = () => {
      if (window.grecaptcha?.ready && window.grecaptcha?.execute) {
        resolve(window.grecaptcha);
        return;
      }

      if (Date.now() - startedAt >= timeoutMs) {
        reject(new Error("recaptcha_unavailable"));
        return;
      }

      window.setTimeout(poll, 60);
    };

    poll();
  });
}

async function recaptchaToken(action) {
  const siteKey = recaptchaSiteKey();
  if (!siteKey) return "";

  await loadRecaptcha();
  const grecaptcha = await waitForRecaptchaApi();

  return new Promise((resolve, reject) => {
    grecaptcha.ready(() => {
      grecaptcha.execute(siteKey, { action })
        .then((token) => resolve(String(token || "")))
        .catch(reject);
    });
  });
}

async function recaptchaTokenWithFallback(primaryAction, fallbackAction) {
  try {
    return {
      token: await recaptchaToken(primaryAction),
      action: primaryAction,
    };
  } catch (error) {
    if (!fallbackAction || fallbackAction === primaryAction) {
      throw error;
    }

    return {
      token: await recaptchaToken(fallbackAction),
      action: fallbackAction,
    };
  }
}

function leadSubmitErrorMessage(result) {
  if (result?.error === "invalid_consent") {
    return "Trebuie să accepți termenii și politica de confidențialitate.";
  }

  if (result?.error === "captcha_failed") {
    return "Nu am putut valida reCAPTCHA. Reîncarcă pagina și încearcă din nou.";
  }

  return "Nu am putut trimite cererea. Verifică datele și încearcă din nou.";
}

function emailSubmitErrorMessage(result) {
  if (result?.message) {
    const debugId = result.debug_id ? ` Cod: ${result.debug_id}` : "";
    return `${result.message}${debugId}`;
  }

  if (result?.error === "site_audit_limit_reached") {
    return result.message || "Ai atins limita pentru acest site.";
  }
  if (result?.error === "rate_limited") {
    return "Ai ajuns la limita de emailuri pentru azi.";
  }
  if (result?.error === "captcha_failed") {
    return "Nu am putut valida reCAPTCHA. Reîncarcă pagina și încearcă din nou.";
  }
  if (result?.error === "payload_too_large") {
    return "Cererea este prea mare. Reîncarcă pagina și încearcă din nou.";
  }

  return "Nu am putut trimite raportul acum. Încearcă din nou.";
}



function initializeLandingReveals() {
  if (typeof window === "undefined" || !dom.root) return;
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

  const elements = Array.from(document.querySelectorAll(LANDING_REVEAL_SELECTOR))
    .filter((element) => !element.classList.contains("motion-reveal"));

  if (!elements.length) return;

  if (!landingRevealObserver && "IntersectionObserver" in window) {
    landingRevealObserver = new window.IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        landingRevealObserver.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -48px 0px" });
  }

  elements.forEach((element, index) => {
    element.classList.add("motion-reveal");
    element.style.setProperty("--reveal-delay", `${Math.min(index * 45, 240)}ms`);

    if (landingRevealObserver) {
      landingRevealObserver.observe(element);
      return;
    }

    element.classList.add("is-visible");
  });
}

function isSuppressed(key) {
  try {
    return Number(window.localStorage?.getItem(key) || 0) > Date.now();
  } catch {
    return false;
  }
}

function suppressForDay(key) {
  try {
    window.localStorage?.setItem(key, String(Date.now() + CTA_SUPPRESS_MS));
  } catch {
    // Ignore localStorage failures.
  }
}

function hideStickyCtas() {
  if (dom.stickyCtaDesktop) dom.stickyCtaDesktop.hidden = true;
  if (dom.stickyCtaMobile) dom.stickyCtaMobile.hidden = true;
}

function renderStickyCtas() {
  if (!STICKY_CTA_ENABLED || !state.currentReport || isSuppressed(STICKY_CTA_CLOSED_KEY)) {
    hideStickyCtas();
    return;
  }

  const recommended = recommendedAction(state.currentReport);
  const label = recommended.label || "Vreau ajutor cu implementarea";
  const serviceName = recommended.name || "implementare";

  if (dom.stickyCtaDesktop) {
    dom.stickyCtaDesktop.innerHTML = `
      <button type="button" class="sticky-close" data-close-sticky aria-label="Inchide CTA">&times;</button>
      <span>Urmatorul pas</span>
      <strong>${escapeHtml(serviceName)}</strong>
      <p>Trimitem contextul raportului împreună cu cererea ta.</p>
      <button type="button" class="btn btn-primary" data-open-help data-help-source="sticky_desktop">${escapeHtml(label)}</button>
    `;
  }

  if (dom.stickyCtaMobile) {
    dom.stickyCtaMobile.innerHTML = `
      <button type="button" class="btn btn-primary" data-open-help data-help-source="sticky_mobile">${escapeHtml(label)}</button>
      <button type="button" class="sticky-close" data-close-sticky aria-label="Inchide CTA">&times;</button>
    `;
  }

  refreshStickyVisibility();
}

function refreshStickyVisibility() {
  if (!STICKY_CTA_ENABLED || !state.currentReport || isSuppressed(STICKY_CTA_CLOSED_KEY)) {
    hideStickyCtas();
    return;
  }

  const summaryBottom = dom.summaryStrip
    ? dom.summaryStrip.getBoundingClientRect().bottom + window.scrollY
    : 0;
  const pastSummary = window.scrollY > Math.max(120, summaryBottom - 120);
  const desktop = window.matchMedia("(min-width: 1101px)").matches;
  const mobile = window.matchMedia("(max-width: 760px)").matches;

  if (dom.stickyCtaDesktop) dom.stickyCtaDesktop.hidden = !(desktop && pastSummary);
  if (dom.stickyCtaMobile) dom.stickyCtaMobile.hidden = !mobile;
}

function closeExitIntent(suppress = false) {
  if (!dom.exitIntentModal) return;
  dom.exitIntentModal.setAttribute("aria-hidden", "true");
  dom.exitIntentModal.classList.remove("active");
  if (suppress) suppressForDay(EXIT_INTENT_SEEN_KEY);
}

function maybeShowExitIntent(event) {
  if (!state.currentReport || state.reportUnlocked || isSuppressed(EXIT_INTENT_SEEN_KEY)) return;
  if (window.matchMedia("(max-width: 900px)").matches) return;
  if (event && event.clientY > 4) return;
  if (!dom.exitIntentModal) return;

  dom.exitIntentModal.setAttribute("aria-hidden", "false");
  dom.exitIntentModal.classList.add("active");
  suppressForDay(EXIT_INTENT_SEEN_KEY);
}

function focusEmailWithSource(source) {
  state.emailSourceOverride = source;
  closeExitIntent(false);
  setShareTab();

  window.setTimeout(() => {
    const target = reportEmailTarget();
    const sourceField = target.form?.querySelector('[name="source"]');
    if (sourceField) sourceField.value = source;
    target.input?.focus({ preventScroll: true });
  }, 220);
}

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function fieldValue(field) {
  return String(field?.value || "").trim();
}

function siteKeyFromUrl(url) {
  try {
    return new URL(String(url || "")).hostname.toLowerCase().replace(/^www\./, "");
  } catch {
    return "";
  }
}

function isKnownLimitForUrl(url) {
  const access = state.siteAccess || {};
  const siteKey = String(access.site_key || "").trim();
  return Boolean(access.exhausted && siteKey && siteKeyFromUrl(url) === siteKey);
}

function closeReportLimitModal() {
  if (!dom.reportLimitModal) return;
  dom.reportLimitModal.setAttribute("aria-hidden", "true");
  dom.reportLimitModal.classList.remove("active");
}

function closePlanPrompt(accepted = false, reason = accepted ? "accept" : "decline") {
  if (dom.planPromptModal) {
    dom.planPromptModal.setAttribute("aria-hidden", "true");
    dom.planPromptModal.classList.remove("active");
  }

  if (!activePlanPrompt) return;

  const prompt = activePlanPrompt;
  activePlanPrompt = null;
  trackEvent(accepted ? "plan_prompt_accept" : "plan_prompt_decline", {
    context: state.currentContext,
    source: prompt.source,
    reason,
  });
  prompt.resolve(Boolean(accepted));
}

function promptForPlanOptin(form, fallbackSource = "email_report") {
  if (!dom.planPromptModal || !dom.planPromptAccept || !dom.planPromptDecline) {
    return Promise.resolve(false);
  }

  if (activePlanPrompt?.promise) {
    return activePlanPrompt.promise;
  }

  const source = safeFormSource(form, fallbackSource);
  closeExitIntent(false);
  closeReportLimitModal();
  trackEvent("plan_prompt_view", {
    context: state.currentContext,
    source,
  });

  let resolvePrompt = null;
  const promise = new Promise((resolve) => {
    resolvePrompt = resolve;
  });
  activePlanPrompt = { promise, resolve: resolvePrompt, source };

  dom.planPromptModal.setAttribute("aria-hidden", "false");
  dom.planPromptModal.classList.add("active");
  window.setTimeout(() => dom.planPromptAccept?.focus(), 80);

  return promise;
}

function manualAuditLimitMessage() {
  const site = state.siteAccess?.site_key || siteKeyFromUrl(state.currentReport?.url_analyzed || state.pendingUrl) || "site-ul analizat";
  const resetCopy = resetCopyFromAccess(state.siteAccess);
  const reportToken = getReportToken(state.currentReport);
  const reportUrl = reportToken
    ? `${window.location.origin}${window.location.pathname}?report=${encodeURIComponent(reportToken)}#workspaceShell`
    : window.location.href;

  return [
    `Am atins limita de rapoarte complete pentru ${site}.`,
    resetCopy ? `Resetare limită: ${resetCopy}.` : "Limita se resetează după 24h.",
    "Vreau o verificare manuală a ultimului raport și recomandări pentru următorii pași.",
    state.currentReport?.url_analyzed ? `URL analizat: ${state.currentReport.url_analyzed}` : "",
    reportToken ? `Link raport: ${reportUrl}` : "",
  ].filter(Boolean).join("\n");
}

function resetCopyFromAccess(access = {}) {
  const seconds = Number(access?.seconds_until_reset || 0);
  if (!Number.isFinite(seconds) || seconds <= 0) {
    return "";
  }

  const hours = Math.ceil(seconds / 3600);
  if (hours >= 2) {
    return `în aproximativ ${hours} ore`;
  }
  if (hours === 1) {
    return "în aproximativ o oră";
  }

  const minutes = Math.max(1, Math.ceil(seconds / 60));
  return `în aproximativ ${minutes} minute`;
}

function showReportLimitModal(payload = {}) {
  if (!dom.reportLimitModal) {
    showToast(payload.message || "Ai atins limita pentru acest site.");
    return;
  }

  const access = payload.site_access || state.siteAccess || {};
  const site = String(access.site_key || siteKeyFromUrl(state.pendingUrl) || "acest site");
  const limit = Number(access.run_limit || 3);
  const used = Number(access.runs_used || limit);
  const resetCopy = resetCopyFromAccess(access) || "după 24h";

  if (dom.reportLimitTitle) {
    dom.reportLimitTitle.textContent = `Ai folosit cele ${limit} rapoarte complete pentru ${site}.`;
  }
  if (dom.reportLimitCopy) {
    dom.reportLimitCopy.textContent =
      `Ultimul raport rămâne disponibil pe ecran. Limita se resetează ${resetCopy}; între timp, trimite-ne cererea și îl verificăm manual.`;
  }
  if (dom.reportLimitMeta) {
    const emailMask = String(access.email_mask || "").trim();
    dom.reportLimitMeta.innerHTML = `
      <span>${escapeHtml(used)}/${escapeHtml(limit)} rapoarte folosite</span>
      <span>${escapeHtml(site)}</span>
      <span>reset ${escapeHtml(resetCopy)}</span>
      ${emailMask ? `<span>${escapeHtml(emailMask)}</span>` : ""}
    `;
  }

  closeExitIntent(false);
  dom.reportLimitModal.setAttribute("aria-hidden", "false");
  dom.reportLimitModal.classList.add("active");
  window.setTimeout(() => dom.reportLimitHelp?.focus(), 80);
}

function showPdfUnlockPrompt() {
  setShareTab();
  showToast("Introdu emailul pentru a descărca PDF-ul.");

  window.setTimeout(() => {
    const target = reportEmailTarget();
    setFeedback(target.feedback, "PDF-ul se activează după ce deblochezi raportul cu emailul.", "warning");
    target.input?.focus({ preventScroll: true });
  }, 180);
}

async function resendReportEmail() {
  if (!state.currentReport) return;

  const email = String(state.lastUnlockEmail || "").trim();
  const useStoredEmail = !validateEmail(email) && Boolean(state.siteAccess?.can_resend);
  if (!validateEmail(email) && !useStoredEmail) {
    setShareTab();
    return;
  }

  const wantsPlan = await promptForPlanOptin(null, "resend_report");
  const buttons = [dom.openSharePrimary, dom.mobileShareButton].filter(Boolean);
  const buttonSnapshots = buttons.map((button) => ({
    button,
    disabled: button.disabled,
    html: button.innerHTML,
    text: button.textContent,
  }));

  buttonSnapshots.forEach(({ button }) => {
    button.disabled = true;
    if (button === dom.openSharePrimary) {
      button.innerHTML = `<span class="spinner" aria-hidden="true"></span> Trimitem...`;
    } else {
      button.textContent = "Trimitem...";
    }
  });

  let captchaToken = "";
  let captchaAction = "email_report";
  try {
    const captcha = await recaptchaTokenWithFallback("email_report", "lead_request");
    captchaToken = captcha.token;
    captchaAction = captcha.action;
  } catch {
    buttonSnapshots.forEach(({ button, disabled, html, text }) => {
      button.disabled = disabled;
      if (html !== undefined) {
        button.innerHTML = html;
      } else {
        button.textContent = text;
      }
    });
    showToast("Nu am putut valida reCAPTCHA. Reîncarcă pagina și încearcă din nou.");
    trackEvent("recaptcha_error", {
      context: state.currentContext,
      source: "resend_report",
      reason: "token_unavailable",
    });
    return;
  }

  const payload = {
    report_token: getReportToken(state.currentReport) || undefined,
    url: state.currentReport.url_analyzed,
    context: state.currentContext,
    email: validateEmail(email) ? email : undefined,
    first_name: "",
    wants_plan: wantsPlan,
    newsletter_optin: false,
    consent_terms: true,
    recaptcha_token: captchaToken,
    recaptcha_action: captchaAction,
    source: "resend_report",
  };

  let sent = false;

  try {
    const { response, payload: result } = await sendReportEmail(app, payload);
    if (!response.ok || !result.ok) {
      showToast(result.error === "site_audit_limit_reached"
        ? result.message || "Ai atins limita pentru acest site."
        : "Nu am putut trimite raportul pe email.");
      return;
    }

    applyReturnedReport(result.report, { email: validateEmail(email) ? email : "" });
    state.emailSent = true;
    renderWorkspace(app, state, dom);
    renderStickyCtas();
    sent = true;
    if (state.reportUnlocked) {
      trackEvent("report_unlock_success", { method: "resend", context: state.currentContext });
      scrollToWorkspace("smooth");
    }
    const destination = validateEmail(email)
      ? email
      : result.site_access?.email_mask || state.siteAccess?.email_mask || "emailul salvat";
    showToast(state.reportUnlocked ? `Raport trimis la ${destination}` : "Raport trimis, dar PDF-ul nu poate fi activat momentan.");
  } catch {
    showToast("A apărut o eroare de rețea.");
  } finally {
    if (sent) {
      return;
    }

    buttonSnapshots.forEach(({ button, disabled, html, text }) => {
      button.disabled = disabled;
      if (button === dom.openSharePrimary) {
        button.innerHTML = html;
      } else {
        button.textContent = text;
      }
    });
  }
}

function setShareTab() {
  const emailAnchor = reportEmailTarget().anchor;
  state.activeTab = "overview";
  updateTabState(dom, state.activeTab);
  scrollToElement(emailAnchor || dom.workspace, "smooth", emailAnchor ? "center" : "start");
}

function handleShareAction() {
  setShareTab();
}

function startProgress(title = "Analizăm pagina") {
  stopProgress();
  let index = 0;
  dom.workspaceProgress.hidden = false;
  dom.progressTitle.textContent = title;

  const updateProgress = () => {
    dom.progressStep.textContent = PROGRESS_STAGES[index];
    dom.progressBar.style.width = `${Math.min(92, 20 + index * 22)}%`;
  };

  updateProgress();
  state.timers.progress = window.setInterval(() => {
    index = Math.min(index + 1, PROGRESS_STAGES.length - 1);
    updateProgress();
  }, 900);
}

function stopProgress() {
  window.clearInterval(state.timers.progress);
  state.timers.progress = null;
  dom.workspaceProgress.hidden = true;
  dom.progressBar.style.width = "0%";
}

function syncHistory(reportToken = "") {
  const basePath = window.location.pathname;
  const nextUrl = reportToken
    ? `${basePath}?report=${encodeURIComponent(reportToken)}#workspaceShell`
    : basePath;
  window.history.replaceState({}, "", nextUrl);
}

function openHelpDrawer(options = {}) {
  const message =
    options.message ||
    buildLeadMessage(state.currentReport, app, options.focus || "") ||
    (state.pendingUrl ? `URL analizat: ${state.pendingUrl}.` : "") ||
    options.focus ||
    "Vreau ajutor cu auditul SEO si optimizarea paginii.";
  state.leadSource = options.source || "workspace_help";
  state.leadPrefill = message;
  const note =
    options.note ||
    (state.currentReport
      ? "Cererea ta va pleca împreună cu contextul raportului și blocajele prioritare."
      : "Poți trimite cererea direct. Dacă rulezi auditul înainte, atașăm automat contextul raportului.");

  if (dom.reportContactForm) {
    if (dom.reportContactContext) {
      dom.reportContactContext.textContent = note;
      dom.reportContactContext.hidden = false;
    }
    if (dom.reportContactMessage) {
      dom.reportContactMessage.value = message;
    }
    if (dom.reportContactEmail) {
      const knownEmail = fieldValue(dom.localEmail) || fieldValue(dom.leadEmail) || state.lastUnlockEmail || "";
      dom.reportContactEmail.value =
        knownEmail ||
        fieldValue(dom.reportContactEmail);
    }
    scrollToElement(dom.reportContactSection || dom.reportContactForm);
    const focusTarget =
      !fieldValue(dom.reportContactName) ? dom.reportContactName :
      !fieldValue(dom.reportContactEmail) ? dom.reportContactEmail :
      dom.reportContactMessage;
    window.setTimeout(() => focusTarget?.focus(), 260);
    return;
  }

  if (dom.helpContextNote) dom.helpContextNote.textContent = note;
  if (dom.leadMessage) dom.leadMessage.value = message;
  if (dom.leadEmail) {
    dom.leadEmail.value = fieldValue(dom.localEmail) || fieldValue(dom.leadEmail) || state.lastUnlockEmail;
  }
  drawers.open("helpDrawer", "#leadName");
}

function renderAuditState(payload) {
  stopProgress();
  setMode("workspace");
  dom.mobileActionBar.hidden = true;
  hideStickyCtas();
  dom.workspaceTitle.textContent = state.pendingUrl ? `Raport pentru ${state.pendingUrl}` : "Raport indisponibil";
  dom.workspaceUrl.textContent = state.pendingUrl || "Raport indisponibil";
  dom.workspaceDate.textContent = "Stare curentă";
  dom.workspaceMetaNote.textContent = "";
  dom.workspaceSource.textContent = payload.message || "Nu am putut finaliza analiza automată.";

  const isRateLimited = payload?.error === "rate_limited";
  const isSiteLimit = payload?.error === "site_audit_limit_reached";
  const isMissing = payload?.error === "report_not_found";
  renderStateCard(dom, {
    tone: isRateLimited || isSiteLimit ? "warning" : isMissing ? "neutral" : "danger",
    title: isSiteLimit
      ? "Ai atins limita pentru acest site."
      : isRateLimited
      ? "Ai ajuns la limita de audituri pentru azi."
      : isMissing
        ? "Raportul nu mai este disponibil."
        : "Analiza automată nu a putut fi finalizată.",
    body:
      payload?.message ||
      "Unele site-uri durează 10-30 secunde sau au protecții anti-bot. Poți relua auditul sau poți cere audit manual.",
    actions: isSiteLimit
      ? [
          { label: "Solicită audit manual", action: "help", kind: "button-primary", source: "site_limit" },
          { label: "Analizează alt site", action: "landing", kind: "button-ghost" },
        ]
      : isMissing
      ? [{ label: "Rulează un audit nou", action: "landing", kind: "button-primary" }]
      : isRateLimited
      ? [{ label: "Analizează alt URL mâine", action: "landing", kind: "button-ghost" }]
      : [
          { label: "Solicită audit manual", action: "help", kind: "button-primary", source: "manual_audit" },
          { label: "Analizează alt URL", action: "landing", kind: "button-ghost" },
        ],
  });
}

function renderLoadedWorkspace(report, { rehydrated = false } = {}) {
  const reportToken = getReportToken(report);
  state.currentReport = report;
  state.currentContext = report.context === "local" ? "local" : "article";
  state.pendingUrl = report.url_analyzed || state.pendingUrl;
  state.rehydrated = rehydrated;
  state.siteAccess = report.site_access || null;
  state.emailSent = false;
  state.leadSent = false;
  state.reportUnlocked = report.locked === false;
  state.issueFilter = "all";
  state.technicalSearch = "";
  state.showOnlyProblems = true;
  state.activeTab = "overview";
  dom.technicalSearch.value = "";
  dom.technicalProblemsOnly.checked = true;
  persistLastReportReference(report);
  renderWorkspace(app, state, dom);
  trackReportGateView(report);
  trackLowScoreBannerView(report, { rehydrated });
  dom.mobileActionBar.hidden = false;
  renderStickyCtas();
  syncHistory(reportToken);
  scrollToWorkspace(rehydrated ? "auto" : "smooth");
}

async function restoreLastReportAfterLimit(payload = {}) {
  const access = payload?.site_access || {};
  const tokenFromAccess = String(access.last_report_token || "").trim();
  const saved = readLastReportReference(state.pendingUrl);
  const token = /^[a-f0-9]{32}$/i.test(tokenFromAccess) ? tokenFromAccess : saved?.token || "";
  if (!token) return false;

  try {
    const { response, payload: report } = await requestReport(app, token);
    if (!response.ok || !report || report.error) {
      return false;
    }

    const restored = {
      ...report,
      site_access: access && Object.keys(access).length
        ? access
        : report.site_access || state.siteAccess || null,
    };
    renderLoadedWorkspace(restored, { rehydrated: true });
    return true;
  } catch {
    return false;
  }
}

async function runAudit() {
  if (state.currentReport && isKnownLimitForUrl(state.pendingUrl || state.currentReport.url_analyzed)) {
    setMode("workspace");
    scrollToWorkspace("smooth");
    showReportLimitModal({ site_access: state.siteAccess });
    return;
  }

  const previousReport = state.currentReport;
  const previousRehydrated = state.rehydrated;

  state.reportUnlocked = false;
  state.emailSent = false;
  state.leadSent = false;

  drawers.closeAll();
  setMode("workspace");
  renderLoadingWorkspace(dom, state);
  scrollToWorkspace();
  dom.mobileActionBar.hidden = true;
  hideStickyCtas();
  startProgress();
  const controller = new AbortController();
  state.timers.timeout = window.setTimeout(() => controller.abort(), 30000);

  try {
    const { response, payload } = await requestAudit(
      app,
      {
        url: state.pendingUrl,
        context: state.currentContext,
      },
      controller.signal
    );
    window.clearTimeout(state.timers.timeout);

    if (!response.ok || payload?.error) {
      trackEvent("audit_fail", { error: payload?.error || "request_failed", context: state.currentContext });
      const isLimitError = payload?.error === "site_audit_limit_reached" || payload?.error === "rate_limited";
      if (isLimitError && previousReport) {
        stopProgress();
        const restoredReport = {
          ...previousReport,
          site_access: payload.site_access || previousReport.site_access || state.siteAccess || null,
        };
        renderLoadedWorkspace(restoredReport, { rehydrated: previousRehydrated });
        if (payload?.error === "site_audit_limit_reached") {
          showReportLimitModal(payload || {});
        } else {
          showToast("Ai ajuns la limita pentru azi. Am pastrat ultimul raport disponibil.");
        }
        return;
      }
      if (isLimitError) {
        stopProgress();
        if (await restoreLastReportAfterLimit(payload || {})) {
          if (payload?.error === "site_audit_limit_reached") {
            showReportLimitModal(payload || {});
          } else {
            showToast("Ai ajuns la limita pentru azi. Am deschis ultimul raport disponibil.");
          }
          return;
        }
      }
      renderAuditState(payload || {});
      return;
    }

    stopProgress();
    renderLoadedWorkspace(payload, { rehydrated: false });
    trackEvent("audit_success", { context: state.currentContext, score: Number(payload?.score?.total || 0) });
    if (payload?.site_access?.auto_unlocked) {
      const remaining = Number(payload.site_access.remaining_runs ?? 0);
      showToast(
        remaining > 0
          ? `Raport complet deblocat automat. Mai ai ${remaining} rulare${remaining === 1 ? "" : "i"}.`
          : "Raport complet deblocat automat. Ai folosit ultima rulare pentru acest site."
      );
    }
  } catch (error) {
    window.clearTimeout(state.timers.timeout);
    trackEvent("audit_fail", { error: error?.name === "AbortError" ? "timeout" : "network", context: state.currentContext });
    renderAuditState({
      message:
        error?.name === "AbortError"
          ? "Analiza a depășit timpul de așteptare. Unele site-uri durează 10-30 secunde; poți relua auditul sau poți cere audit manual."
          : "A apărut o problemă de rețea în timpul analizei.",
    });
  }
}

async function restoreReport(token) {
  setMode("workspace");
  renderLoadingWorkspace(dom, state);
  scrollToWorkspace("auto");
  startProgress("Rehidrătăm raportul");
  try {
    const { response, payload } = await requestReport(app, token);
    if (!response.ok) {
      renderAuditState({
        error: "report_not_found",
        message: "Report token-ul nu mai este disponibil. Poți rula un audit nou din landing.",
      });
      return;
    }

    stopProgress();
    renderLoadedWorkspace(payload, { rehydrated: true });
  } catch {
    renderAuditState({
      message: "Nu am putut rehidrata raportul din linkul primit.",
    });
  }
}

function handleScoreSubmit(event) {
  event.preventDefault();
  const normalized = normalizeUrl(dom.urlInput.value);
  if (!normalized) {
    setFeedback(dom.urlError, "URL-ul nu pare valid. Introdu domeniul complet sau URL-ul paginii.", "error");
    dom.urlInput.focus();
    return;
  }

  setFeedback(dom.urlError, "");
  state.pendingUrl = normalized;
  trackEvent("audit_submit", { context: state.currentContext });
  runAudit();
}

async function handleEmailSubmit(event) {
  event.preventDefault();
  const form = event.target.closest("[data-report-email-form]");
  const feedback = form?.querySelector("[data-email-feedback]");
  const emailField = form?.querySelector('[name="email"]');
  const firstNameField = form?.querySelector('[name="first_name"]');
  const sourceField = form?.querySelector('[name="source"]');
  const wantsPlanField = form?.querySelector('[name="wants_plan"]');
  const newsletterField = form?.querySelector('[name="newsletter_optin"]');
  const consentField = form?.querySelector('[name="consent_terms"]');
  const submitButton = form?.querySelector('button[type="submit"]');
  const initialButtonHtml = submitButton?.innerHTML || "";
  const loadingLabel = submitButton?.dataset.loadingLabel || "Trimitem raportul...";

  const setSubmitState = (isLoading, label = loadingLabel) => {
    if (!submitButton) return;
    submitButton.disabled = isLoading;
    if (isLoading) {
      submitButton.innerHTML = `<span class="spinner" aria-hidden="true"></span> ${label}`;
      submitButton.dataset.loading = "true";
      return;
    }

    submitButton.innerHTML = initialButtonHtml;
    delete submitButton.dataset.loading;
  };

  if (!state.currentReport) {
    setFeedback(feedback, "Raportul nu este pregătit pentru trimitere.", "error");
    trackFormValidationError(form, "report_missing", "email_report");
    return;
  }

  const email = String(emailField?.value || "").trim();
  if (!validateEmail(email)) {
    setFeedback(feedback, "Introdu un email valid.", "error");
    trackFormValidationError(form, "invalid_email", "email_report");
    return;
  }

  const consentAccepted = consentField
    ? consentField.type === "checkbox"
      ? consentField.checked
      : String(consentField.value || "").trim() !== ""
    : true;

  if (!consentAccepted) {
    setFeedback(feedback, "Trebuie să accepți termenii și politica de confidențialitate.", "error");
    trackFormValidationError(form, "missing_terms", "email_report");
    return;
  }

  let wantsPlan = wantsPlanField
    ? wantsPlanField.type === "checkbox"
      ? wantsPlanField.checked
      : String(wantsPlanField.value || "") === "1"
    : false;
  const newsletterOptin = newsletterField
    ? newsletterField.type === "checkbox"
      ? newsletterField.checked
      : String(newsletterField.value || "") === "1"
    : false;

  if (!wantsPlan) {
    if (form?.dataset.planPromptPending === "1") {
      return;
    }

    if (form) form.dataset.planPromptPending = "1";
    const acceptedPlan = await promptForPlanOptin(form);
    if (form) delete form.dataset.planPromptPending;

    wantsPlan = acceptedPlan;
    if (acceptedPlan && wantsPlanField?.type === "checkbox") {
      wantsPlanField.checked = true;
      trackEvent("plan_optin_checked", {
        context: state.currentContext,
        source: safeFormSource(form, "email_report"),
        checked: true,
      });
    }
  }

  setSubmitState(true);
  setFeedback(feedback, "Trimitem raportul...", "warning");

  let captchaToken = "";
  let captchaAction = "email_report";
  try {
    const captcha = await recaptchaTokenWithFallback("email_report", "lead_request");
    captchaToken = captcha.token;
    captchaAction = captcha.action;
  } catch {
    setSubmitState(false);
    setFeedback(feedback, "Nu am putut valida reCAPTCHA. Reîncarcă pagina și încearcă din nou.", "error");
    trackEvent("recaptcha_error", {
      context: state.currentContext,
      source: safeFormSource(form, "email_report"),
      reason: "token_unavailable",
    });
    return;
  }

  const payload = {
    report_token: state.currentReport.report_token || state.currentReport.score?.report_token || undefined,
    url: state.currentReport.url_analyzed,
    context: state.currentContext,
    email,
    first_name: String(firstNameField?.value || "").trim(),
    wants_plan: wantsPlan,
    newsletter_optin: newsletterOptin,
    consent_terms: consentAccepted,
    recaptcha_token: captchaToken,
    recaptcha_action: captchaAction,
    source: state.emailSourceOverride || String(sourceField?.value || "email_report"),
  };

  trackEvent("email_report_submit", { context: state.currentContext, source: payload.source });

  try {
    const { response, payload: result } = await sendReportEmail(app, payload);
    if (!response.ok || !result.ok) {
      setSubmitState(false);
      setFeedback(
        feedback,
        emailSubmitErrorMessage(result),
        "error"
      );
      return;
    }

    applyReturnedReport(result.report, { email });
    state.emailSourceOverride = "";
    state.emailSent = true;
    renderWorkspace(app, state, dom);
    renderStickyCtas();
    if (state.reportUnlocked) {
      trackEvent("report_unlock_success", { method: "email", context: state.currentContext });
      scrollToWorkspace("smooth");
    }
    trackEvent("email_report_success", { context: state.currentContext, source: payload.source });
    showToast(state.reportUnlocked ? "Raport deblocat" : "Raport trimis, dar PDF-ul nu poate fi activat momentan.");
  } catch {
    setSubmitState(false);
    setFeedback(feedback, "A apărut o eroare de rețea.", "error");
  }
}

async function handleLeadSubmit(event) {
  event.preventDefault();

  if (!dom.leadName.value.trim() || !validatePhone(dom.leadPhone.value) || !validateEmail(dom.leadEmail.value)) {
    setFeedback(dom.leadMsg, "Completează nume, telefon și un email valid.", "error");
    trackEvent("form_validation_error", {
      context: state.currentContext,
      source: state.leadSource || "workspace_help",
      reason: "invalid_lead_contact",
    });
    return;
  }

  if (!dom.leadTerms?.checked) {
    setFeedback(dom.leadMsg, "Trebuie să accepți termenii și politica de confidențialitate.", "error");
    trackEvent("form_validation_error", {
      context: state.currentContext,
      source: state.leadSource || "workspace_help",
      reason: "missing_terms",
    });
    return;
  }

  setFeedback(dom.leadMsg, "Trimitem cererea...", "warning");
  let captchaToken = "";
  try {
    captchaToken = await recaptchaToken("lead_request");
  } catch {
    setFeedback(dom.leadMsg, "Nu am putut valida reCAPTCHA. Reîncarcă pagina și încearcă din nou.", "error");
    trackEvent("recaptcha_error", {
      context: state.currentContext,
      source: state.leadSource || "workspace_help",
      reason: "token_unavailable",
    });
    return;
  }

  const payload = {
    source: state.leadSource || "workspace_help",
    name: String(dom.leadName.value || "").trim(),
    phone: String(dom.leadPhone.value || "").trim(),
    email: String(dom.leadEmail.value || "").trim(),
    message: String(dom.leadMessage.value || "").trim(),
    urgent: dom.leadUrgent.checked,
    wants_plan: false,
    newsletter_optin: dom.helpNewsletterOptin.checked,
    consent_terms: true,
    recaptcha_token: captchaToken,
    recaptcha_action: "lead_request",
    website: String(dom.leadWebsiteTrap.value || ""),
  };

  if (state.currentReport?.report_token || state.currentReport?.score?.report_token) {
    payload.report_token = state.currentReport.report_token || state.currentReport.score?.report_token;
  } else {
    payload.url = state.pendingUrl;
    payload.context = state.currentContext;
  }

  try {
    const { response, payload: result } = await sendLeadRequest(app, payload);
    if (!response.ok || !result.ok) {
      if (result?.error === "captcha_failed") {
        trackEvent("recaptcha_error", {
          context: state.currentContext,
          source: payload.source || "workspace_help",
          reason: "server_rejected",
        });
      }
      setFeedback(
        dom.leadMsg,
        leadSubmitErrorMessage(result),
        "error"
      );
      return;
    }

    applyReturnedReport(result.report, { email: payload.email });
    state.leadSent = true;
    renderWorkspace(app, state, dom);
    renderStickyCtas();
    drawers.closeAll();
    setFeedback(dom.leadMsg, result.message || "Mulțumim. Revenim în maximum 24h. Raportul complet este acum deblocat.", "success");
    trackEvent("lead_submit_success", { source: payload.source || "workspace_help", context: state.currentContext });
    trackEvent("contact_form_submit", { source: payload.source || "workspace_help", context: state.currentContext });
    showToast(state.reportUnlocked ? "Raport deblocat" : "Cerere trimisă");
  } catch {
    setFeedback(dom.leadMsg, "A apărut o eroare de rețea.", "error");
  }
}

async function handleReportContactSubmit(event) {
  event.preventDefault();

  const name = String(dom.reportContactName?.value || "").trim();
  const email = String(dom.reportContactEmail?.value || "").trim();
  const message = String(dom.reportContactMessage?.value || "").trim();
  const consentTerms = Boolean(dom.reportContactTerms?.checked);

  if (!name || !validateEmail(email) || !message) {
    setFeedback(dom.reportContactMsg, "Completează nume, email și mesajul.", "error");
    trackEvent("form_validation_error", {
      context: state.currentContext,
      source: state.mode === "workspace" ? "workspace_inline_help" : "landing_contact",
      reason: "invalid_contact_form",
    });
    return;
  }

  if (!consentTerms) {
    setFeedback(dom.reportContactMsg, "Trebuie să accepți termenii și politica de confidențialitate.", "error");
    trackEvent("form_validation_error", {
      context: state.currentContext,
      source: state.mode === "workspace" ? "workspace_inline_help" : "landing_contact",
      reason: "missing_terms",
    });
    return;
  }

  setFeedback(dom.reportContactMsg, "Trimitem cererea...", "warning");
  let captchaToken = "";
  try {
    captchaToken = await recaptchaToken("report_contact");
  } catch {
    setFeedback(dom.reportContactMsg, "Nu am putut valida reCAPTCHA. Reîncarcă pagina și încearcă din nou.", "error");
    trackEvent("recaptcha_error", {
      context: state.currentContext,
      source: state.mode === "workspace" ? "workspace_inline_help" : "landing_contact",
      reason: "token_unavailable",
    });
    return;
  }

  const payload = {
    source: state.leadSource || (state.mode === "workspace" ? "workspace_inline_help" : "landing_contact"),
    name,
    email,
    message,
    phone: "",
    urgent: false,
    wants_plan: false,
    newsletter_optin: false,
    consent_terms: true,
    recaptcha_token: captchaToken,
    recaptcha_action: "report_contact",
    website: String(dom.reportContactWebsiteTrap?.value || ""),
  };

  if (state.currentReport?.report_token || state.currentReport?.score?.report_token) {
    payload.report_token = state.currentReport.report_token || state.currentReport.score?.report_token;
  } else if (state.pendingUrl) {
    payload.url = state.pendingUrl;
    payload.context = state.currentContext;
  }

  try {
    const { response, payload: result } = await sendLeadRequest(app, payload);
    if (!response.ok || !result.ok) {
      if (result?.error === "captcha_failed") {
        trackEvent("recaptcha_error", {
          context: state.currentContext,
          source: payload.source || "contact",
          reason: "server_rejected",
        });
      }
      setFeedback(
        dom.reportContactMsg,
        leadSubmitErrorMessage(result),
        "error"
      );
      return;
    }

    const hadReport = applyReturnedReport(result.report, { email });
    if (hadReport) {
      state.leadSent = true;
      renderWorkspace(app, state, dom);
      renderStickyCtas();
    }

    setFeedback(
      dom.reportContactMsg,
      result.message || "Mulțumim. Revenim pe email în maximum 24h.",
      "success"
    );
    trackEvent("lead_submit_success", { source: payload.source || "contact", context: state.currentContext });
    trackEvent("contact_form_submit", { source: payload.source || "contact", context: state.currentContext });
    showToast("Cerere trimisă");
  } catch {
    setFeedback(dom.reportContactMsg, "A apărut o eroare de rețea.", "error");
  }
}

async function handleCopy(action) {
  if (!state.currentReport) return;
  if (!state.reportUnlocked) {
    setFeedback(dom.shareCopyMessage, "Deblochează raportul complet prin email sau cerere contextuală.", "warning");
    return;
  }

  try {
    if (action === "link") {
      await copyText(dom.shareLinkInput.value);
      setFeedback(dom.shareCopyMessage, "Linkul raportului a fost copiat.", "success");
      showToast("Link copiat");
    } else if (action === "summary") {
      await copyText(executiveSummary(state.currentReport));
      setFeedback(dom.shareCopyMessage, "Rezumatul executiv a fost copiat.", "success");
      showToast("Rezumat copiat");
    } else if (action === "technical") {
      await copyText(technicalSummary(state.currentReport, state.technicalSearch, state.showOnlyProblems));
      setFeedback(dom.technicalCopyMessage, "Summary-ul tehnic a fost copiat.", "success");
      showToast("Summary tehnic copiat");
    }
  } catch {
    if (action === "technical") {
      setFeedback(dom.technicalCopyMessage, "Nu am putut copia summary-ul tehnic.", "error");
    } else {
      setFeedback(dom.shareCopyMessage, "Nu am putut copia conținutul.", "error");
    }
  }
}

function rerunCurrentAudit() {
  if (!state.currentReport && !state.pendingUrl) {
    goToLanding();
    return;
  }

  state.pendingUrl = state.currentReport?.url_analyzed || state.pendingUrl;
  state.currentContext = state.currentReport?.context || state.currentContext;
  setContext(state.currentContext);
  runAudit();
}

function handleDynamicAction(event) {
  const focusUrlButton = event.target.closest("[data-focus-url-input]");
  if (focusUrlButton) {
    dom.landing?.scrollIntoView({ behavior: "smooth" });
    window.setTimeout(() => dom.urlInput?.focus({ preventScroll: true }), 160);
    return true;
  }

  const closeSticky = event.target.closest("[data-close-sticky]");
  if (closeSticky) {
    suppressForDay(STICKY_CTA_CLOSED_KEY);
    hideStickyCtas();
    return true;
  }

  const closeModal = event.target.closest('[data-close="exitIntentModal"]');
  if (closeModal) {
    closeExitIntent(true);
    return true;
  }

  const closeLimitModal = event.target.closest('[data-close="reportLimitModal"]');
  if (closeLimitModal) {
    closeReportLimitModal();
    return true;
  }

  const closePlanPromptModal = event.target.closest('[data-close="planPromptModal"]');
  if (closePlanPromptModal) {
    closePlanPrompt(false, "dismiss");
    return true;
  }

  const helpButton = event.target.closest("[data-open-help]");
  if (helpButton) {
    const source = helpButton.dataset.helpSource || helpButton.dataset.source || "workspace_help";
    trackEvent("cta_service_click", { source, context: state.currentContext });
    trackEvent("cta_help_click", { context: state.currentContext, source });
    openHelpDrawer({
      source,
      focus: helpButton.dataset.helpFocus || "",
    });
    return true;
  }

  const shareButton = event.target.closest("[data-open-share]");
  if (shareButton) {
    setShareTab();
    return true;
  }

  const downloadPdfButton = event.target.closest("[data-download-pdf]");
  if (downloadPdfButton) {
    handleDownloadPdf();
    return true;
  }

  const emailFocusButton = event.target.closest("[data-focus-email]");
  if (emailFocusButton) {
    focusEmailWithSource(emailFocusButton.dataset.emailSource || "locked_problem_group");
    return true;
  }

  const checkToggle = event.target.closest("[data-check-toggle]");
  if (checkToggle) {
    const row = checkToggle.closest(".check-row");
    const body = row?.querySelector(".check-body");
    const isOpen = row?.classList.toggle("open");
    checkToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    if (body) body.hidden = !isOpen;
    return true;
  }

  const filterButton = event.target.closest("[data-issue-filter]");
  if (filterButton) {
    state.issueFilter = filterButton.dataset.issueFilter || "all";
    renderWorkspace(app, state, dom);
    return true;
  }

  const stateActionButton = event.target.closest("[data-state-action]");
  if (stateActionButton) {
    const action = stateActionButton.dataset.stateAction;
    if (action === "landing") {
      state.currentReport = null;
      goToLanding();
    } else if (action === "help") {
      openHelpDrawer({ source: stateActionButton.dataset.stateSource || "manual_audit" });
    }
    return true;
  }

  return false;
}

function handleOptinChange(event) {
  const field = event.target;
  if (!(field instanceof HTMLInputElement) || field.type !== "checkbox") return;
  if (!field.matches('[name="wants_plan"], [name="newsletter_optin"]')) return;

  const form = field.closest("form");
  trackEvent(field.name === "wants_plan" ? "plan_optin_checked" : "newsletter_optin_checked", {
    context: state.currentContext,
    source: safeFormSource(form, form?.id || "unknown"),
    checked: field.checked,
  });
}

function initializeTabs() {
  dom.tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
      state.activeTab = button.dataset.tab || "overview";
      updateTabState(dom, state.activeTab);
    });

    button.addEventListener("keydown", (event) => {
      const currentIndex = TABS.indexOf(button.dataset.tab);
      if (currentIndex === -1) return;

      if (event.key === "ArrowRight" || event.key === "ArrowLeft") {
        event.preventDefault();
        const direction = event.key === "ArrowRight" ? 1 : -1;
        const nextIndex = (currentIndex + direction + TABS.length) % TABS.length;
        dom.tabButtons[nextIndex]?.focus();
        state.activeTab = TABS[nextIndex];
        updateTabState(dom, state.activeTab);
      }
    });
  });
}

function bindEvents() {
  dom.scoreForm?.addEventListener("submit", handleScoreSubmit);
  dom.leadRequestForm?.addEventListener("submit", handleLeadSubmit);
  dom.reportContactForm?.addEventListener("submit", handleReportContactSubmit);

  dom.contextButtons.forEach((button) => {
    button.addEventListener("click", () => setContext(button.dataset.type));
  });

  dom.openSharePrimary?.addEventListener("click", handleShareAction);
  dom.mobilePdfButton?.addEventListener("click", handleDownloadPdf);
  dom.mobileShareButton?.addEventListener("click", handleShareAction);
  dom.openHelpPrimary?.addEventListener("click", () => {
    trackEvent("cta_service_click", { source: "workspace_help", context: state.currentContext });
    openHelpDrawer({ source: "workspace_help" });
  });
  dom.downloadPdfButton?.addEventListener("click", handleDownloadPdf);
  dom.mobileHelpButton?.addEventListener("click", () => {
    trackEvent("cta_service_click", { source: "workspace_help", context: state.currentContext });
    openHelpDrawer({ source: "workspace_help" });
  });
  dom.rerunAuditButton?.addEventListener("click", rerunCurrentAudit);
  dom.exitIntentEmail?.addEventListener("click", () => focusEmailWithSource("exit_intent"));
  dom.reportLimitHelp?.addEventListener("click", () => {
    closeReportLimitModal();
    showToast("Completează formularul și îți trimitem cererea.");
    trackEvent("cta_service_click", { source: "site_limit", context: state.currentContext });
    openHelpDrawer({
      source: "site_limit",
      message: manualAuditLimitMessage(),
      note: "Cererea va include linkul ultimului raport si motivul: limita pentru acest site a fost atinsa.",
    });
  });
  dom.planPromptAccept?.addEventListener("click", () => closePlanPrompt(true, "accept"));
  dom.planPromptDecline?.addEventListener("click", () => closePlanPrompt(false, "decline"));
  window.addEventListener("scroll", refreshStickyVisibility, { passive: true });
  window.addEventListener("resize", refreshStickyVisibility);
  document.addEventListener("mouseleave", maybeShowExitIntent);
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      if (dom.planPromptModal?.classList.contains("active")) {
        closePlanPrompt(false, "escape");
        return;
      }
      closeReportLimitModal();
    }
  });

  dom.technicalSearch?.addEventListener("input", () => {
    state.technicalSearch = dom.technicalSearch.value || "";
    if (state.currentReport) renderWorkspace(app, state, dom);
  });

  dom.technicalProblemsOnly?.addEventListener("change", () => {
    state.showOnlyProblems = dom.technicalProblemsOnly.checked;
    if (state.currentReport) renderWorkspace(app, state, dom);
  });

  dom.copyLinkButton?.addEventListener("click", () => handleCopy("link"));
  dom.copySummaryButton?.addEventListener("click", () => handleCopy("summary"));
  document.getElementById("copyTechnicalButton")?.addEventListener("click", () => handleCopy("technical"));

  dom.mobileNavLinks.forEach((link) => {
    link.addEventListener("click", () => {
      if (dom.mobileNav) dom.mobileNav.open = false;
    });
  });

  document.addEventListener("click", (event) => {
    if (dom.mobileNav?.open && !dom.mobileNav.contains(event.target)) {
      dom.mobileNav.open = false;
    }
    handleDynamicAction(event);
  });

  document.addEventListener("change", handleOptinChange);

  document.addEventListener("submit", (event) => {
    if (event.target.matches("[data-report-email-form]")) {
      handleEmailSubmit(event);
    }
  });
}

function initialize() {
  syncKnownEmails();
  setContext("article");
  trackEvent("tool_view", { context: state.currentContext });
  initializeTabs();
  bindEvents();
  loadRecaptcha().catch(() => {
    // If preload fails, submit handlers will surface the error and allow retry.
  });
  syncSurfaceMode(
    {
      root: dom.root,
      landing: dom.landing,
      education: dom.landingEducation,
      workspace: dom.workspace,
      mobileActionBar: dom.mobileActionBar,
    },
    state.mode
  );
  initializeLandingReveals();

  if (app.initialReportToken) {
    restoreReport(app.initialReportToken);
  }
}

initialize();
