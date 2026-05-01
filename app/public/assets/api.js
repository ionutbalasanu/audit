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

export function normalizeUrl(input) {
  const raw = String(input || "").trim();
  if (!raw) return null;

  const candidate = /^https?:\/\//i.test(raw) ? raw : `https://${raw.replace(/^\/+/, "")}`;
  try {
    return new URL(candidate).toString();
  } catch {
    return null;
  }
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
