export function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || "").trim());
}

export function validatePhone(phone) {
  return String(phone || "").replace(/\D/g, "").length >= 9;
}

export function setFeedback(element, text = "", tone = "neutral") {
  if (!element) return;
  element.textContent = text;
  if (!text || tone === "neutral") {
    delete element.dataset.tone;
    return;
  }
  element.dataset.tone = tone;
}

export function buildShareLink(toolUrl, token) {
  if (!token) return "";
  const separator = toolUrl.includes("?") ? "&" : "?";
  return `${toolUrl}${separator}report=${encodeURIComponent(token)}#workspaceShell`;
}

export function buildLeadMessage(report, app, focus = "") {
  if (!report) return focus || "";

  const topIssue =
    focus ||
    report?.business?.top_business_issues?.[0]?.label ||
    "blocajele prioritare din raport";
  const contextLabel = report.context === "local" ? "local" : "standard";

  return `Site analizat: ${report.url_analyzed}. Context: ${contextLabel}. Scor: ${report?.score?.total || 0}/100. Focus: ${topIssue}.`;
}
