async function parseJson(response) {
  try {
    return await response.json();
  } catch {
    return {};
  }
}

async function request(url, options = {}) {
  const response = await fetch(url, options);
  const payload = await parseJson(response);
  return { response, payload };
}

const BLOCKED_HOST_SUFFIXES = [".local", ".localhost", ".internal", ".lan", ".test", ".invalid"];
const BLOCKED_HOST_EXACT = new Set(["localhost", "0.0.0.0"]);

function isBlockedHost(host) {
  if (!host) return true;
  const h = host.toLowerCase();
  if (BLOCKED_HOST_EXACT.has(h)) return true;
  if (BLOCKED_HOST_SUFFIXES.some((suffix) => h.endsWith(suffix))) return true;
  // Reject hosts without a dot (no TLD), unless it's a raw IP.
  if (!h.includes(".")) return true;
  // Reject obvious private IPv4 ranges.
  if (/^10\./.test(h)) return true;
  if (/^192\.168\./.test(h)) return true;
  if (/^172\.(1[6-9]|2\d|3[0-1])\./.test(h)) return true;
  if (/^127\./.test(h)) return true;
  if (/^169\.254\./.test(h)) return true;
  return false;
}

export function normalizeUrl(input) {
  const raw = String(input || "").trim();
  if (!raw) return null;

  const candidate = /^https?:\/\//i.test(raw) ? raw : `https://${raw.replace(/^\/+/, "")}`;
  let parsed;
  try {
    parsed = new URL(candidate);
  } catch {
    return null;
  }

  if (parsed.protocol !== "http:" && parsed.protocol !== "https:") return null;
  if (isBlockedHost(parsed.hostname)) return null;
  // Reject non-default ports.
  if (parsed.port && parsed.port !== "80" && parsed.port !== "443") return null;

  return parsed.toString();
}

export function requestAudit(app, payload, signal) {
  return request(app.scoreUrl, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
    signal,
  });
}

export function requestReport(app, token, signal) {
  return request(`${app.reportUrl}?token=${encodeURIComponent(token)}`, { signal });
}

export function sendReportEmail(app, payload) {
  return request(app.emailUrl, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export function sendLeadRequest(app, payload) {
  return request(app.leadUrl, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
}

export async function copyText(text) {
  const value = String(text || "");
  if (!value) return false;

  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(value);
    return true;
  }

  const helper = document.createElement("textarea");
  helper.value = value;
  helper.setAttribute("readonly", "");
  helper.style.position = "absolute";
  helper.style.left = "-9999px";
  document.body.appendChild(helper);
  helper.select();
  const ok = document.execCommand("copy");
  helper.remove();
  return ok;
}
