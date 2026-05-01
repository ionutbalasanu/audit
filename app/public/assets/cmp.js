/* NovaWeb mini CMP (no deps)
 * - Stores consent in first-party cookie (Path=/)
 * - Can activate <script type="text/plain" data-consent="statistics|marketing" data-src="..."> blocks
 * - If Complianz cookies exist (cmplz_statistics/cmplz_marketing), it mirrors them to avoid double prompts.
 */

(() => {
  const COOKIE_NAME = 'nw_cmp_v1';
  const COOKIE_DAYS = 180;

  const banner = document.getElementById('cmpBanner');
  const modal  = document.getElementById('cmpModal');
  const fab    = document.getElementById('cmpFab');
  const stats  = document.getElementById('cmpStats');
  const mkt    = document.getElementById('cmpMkt');
  const cspNonce = document.currentScript?.nonce
    || document.querySelector('meta[name="csp-nonce"]')?.getAttribute('content')
    || '';

  const openBtns = Array.from(document.querySelectorAll('[data-cmp-open]'));
  const closeBtns = Array.from(document.querySelectorAll('[data-cmp-close]'));
  const actionBtns = Array.from(document.querySelectorAll('[data-cmp-action]'));

  if (!banner || !modal || !fab || !stats || !mkt) {
    // Page might not include CMP markup
    return;
  }

  const isSecure = (() => {
    try { return window.location.protocol === 'https:'; } catch (_) { return false; }
  })();

  function getCookie(name) {
    const cookies = document.cookie ? document.cookie.split('; ') : [];
    for (const c of cookies) {
      const i = c.indexOf('=');
      if (i < 0) continue;
      const k = c.slice(0, i);
      if (k === name) return decodeURIComponent(c.slice(i + 1));
    }
    return null;
  }

  function setCookie(name, value, days) {
    const maxAge = days * 24 * 60 * 60;
    let cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
    if (isSecure) cookie += '; Secure';
    document.cookie = cookie;
  }

  function delCookie(name) {
    // Try multiple domain variants, because some tools set Domain=.example.ro
    const host = (window.location.hostname || '').trim();
    const domains = [];
    if (host) {
      domains.push(host);
      if (!host.startsWith('.')) domains.push(`.${host}`);
    }
    const variants = [''];
    for (const d of domains) variants.push(`; Domain=${d}`);

    for (const dom of variants) {
      let cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax${dom}`;
      if (isSecure) cookie += '; Secure';
      document.cookie = cookie;
    }
  }

  function listCookieNames() {
    const out = [];
    const cookies = document.cookie ? document.cookie.split('; ') : [];
    for (const c of cookies) {
      const i = c.indexOf('=');
      if (i < 0) continue;
      out.push(c.slice(0, i));
    }
    return out;
  }

  function purgeCookiePrefix(prefix) {
    for (const name of listCookieNames()) {
      if (name.startsWith(prefix)) delCookie(name);
    }
  }

  function readComplianz() {
    const s = getCookie('cmplz_statistics');
    const m = getCookie('cmplz_marketing');
    // Only trust if present (avoid forcing a choice)
    if (s === null && m === null) return null;
    return {
      necessary: true,
      statistics: s === 'allow',
      marketing: m === 'allow',
      source: 'complianz',
    };
  }

  function readState() {
    const raw = getCookie(COOKIE_NAME);
    if (!raw) return null;
    try {
      const obj = JSON.parse(raw);
      if (!obj || typeof obj !== 'object') return null;
      if (typeof obj.statistics !== 'boolean' || typeof obj.marketing !== 'boolean') return null;
      return {
        necessary: true,
        statistics: !!obj.statistics,
        marketing: !!obj.marketing,
        source: 'nw',
      };
    } catch (_) {
      return null;
    }
  }

  function saveState(state) {
    setCookie(COOKIE_NAME, JSON.stringify({
      v: 1,
      ts: Date.now(),
      statistics: !!state.statistics,
      marketing: !!state.marketing,
    }), COOKIE_DAYS);
  }

  function activateScripts(category) {
    const blocks = Array.from(document.querySelectorAll(`script[type="text/plain"][data-consent="${category}"]`));
    if (blocks.length === 0) return;

    blocks.forEach((node) => {
      const src = node.getAttribute('data-src');
      const code = (node.textContent || '').trim();

      const s = document.createElement('script');
      if (cspNonce) s.nonce = cspNonce;
      // Preserve async/defer if user added them on placeholder
      if (node.hasAttribute('async')) s.async = true;
      if (node.hasAttribute('defer')) s.defer = true;

      if (src) {
        s.src = src;
      }
      if (code) {
        s.text = code;
      }

      node.parentNode?.insertBefore(s, node);
      node.remove();
    });
  }

  function applyState(state) {
    // If user denies, try best-effort cleanup
    if (!state.statistics) {
      purgeCookiePrefix('_ga');
    }
    if (!state.marketing) {
      delCookie('_fbp');
    }

    if (state.statistics) activateScripts('statistics');
    if (state.marketing) activateScripts('marketing');

    // Show settings entry point after the first choice exists
    fab.hidden = false;
  }

  function showBanner() {
    banner.hidden = false;
    banner.setAttribute('aria-hidden', 'false');
  }
  function hideBanner() {
    banner.hidden = true;
    banner.setAttribute('aria-hidden', 'true');
  }

  function openModal() {
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('noScroll');

    // Sync toggles
    const st = currentState || { statistics: false, marketing: false };
    stats.checked = !!st.statistics;
    mkt.checked = !!st.marketing;

    // Focus first control
    setTimeout(() => {
      try { stats.focus(); } catch (_) {}
    }, 0);
  }

  function closeModal() {
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('noScroll');
  }

  function setAll(val) {
    currentState = { necessary: true, statistics: !!val, marketing: !!val, source: 'nw' };
    saveState(currentState);
    applyState(currentState);
    hideBanner();
    closeModal();
  }

  function saveCustom() {
    currentState = { necessary: true, statistics: !!stats.checked, marketing: !!mkt.checked, source: 'nw' };
    saveState(currentState);
    applyState(currentState);
    hideBanner();
    closeModal();
  }

  let currentState = readState();
  if (!currentState) {
    const cmplz = readComplianz();
    if (cmplz) {
      currentState = { ...cmplz, source: 'nw' };
      // Mirror to local state so tool doesn't ask again
      saveState(currentState);
      applyState(currentState);
      hideBanner();
    }
  }

  if (currentState) {
    // Already chosen
    applyState(currentState);
    hideBanner();
  } else {
    // Default: deny optional
    currentState = null;
    // Show after a short delay so it feels less intrusive
    setTimeout(showBanner, 550);
  }

  // Actions
  actionBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const action = btn.getAttribute('data-cmp-action');
      if (action === 'accept') return setAll(true);
      if (action === 'reject') return setAll(false);
      if (action === 'settings') return openModal();
      if (action === 'save') return saveCustom();
    });
  });

  openBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      openModal();
    });
  });

  closeBtns.forEach((btn) => {
    btn.addEventListener('click', closeModal);
  });

  fab.addEventListener('click', openModal);

  // Overlay click closes
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  // ESC closes
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
  });
})();
