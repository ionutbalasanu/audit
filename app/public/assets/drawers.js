const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function createDrawerController(doc) {
  let activeDrawer = null;
  let lastFocused = null;

  function trapFocus(event) {
    if (!activeDrawer || event.key !== "Tab") return;

    const focusable = Array.from(activeDrawer.querySelectorAll(FOCUSABLE)).filter(
      (element) => !element.hasAttribute("hidden")
    );
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && doc.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && doc.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function close(id = null) {
    const drawer = (id && doc.getElementById(id)) || activeDrawer;
    if (!drawer) return;

    drawer.classList.remove("active");
    drawer.setAttribute("aria-hidden", "true");

    if (activeDrawer === drawer) {
      activeDrawer = null;
      doc.body.classList.remove("body-locked");
      if (lastFocused && typeof lastFocused.focus === "function") {
        lastFocused.focus();
      }
      lastFocused = null;
    }
  }

  function open(id, focusSelector = null) {
    const drawer = doc.getElementById(id);
    if (!drawer) return;
    if (activeDrawer && activeDrawer !== drawer) close(activeDrawer.id);

    lastFocused = doc.activeElement;
    activeDrawer = drawer;
    drawer.classList.add("active");
    drawer.setAttribute("aria-hidden", "false");
    doc.body.classList.add("body-locked");

    const target =
      (focusSelector && drawer.querySelector(focusSelector)) ||
      drawer.querySelector(FOCUSABLE);
    if (target) {
      window.requestAnimationFrame(() => target.focus());
    }
  }

  doc.addEventListener("keydown", (event) => {
    if (!activeDrawer) return;
    if (event.key === "Escape") {
      close(activeDrawer.id);
      return;
    }
    trapFocus(event);
  });

  doc.addEventListener("click", (event) => {
    const closeTarget = event.target.closest("[data-close-drawer]");
    if (closeTarget) {
      close(closeTarget.getAttribute("data-close-drawer"));
    }
  });

  return {
    open,
    close,
    closeAll() {
      if (activeDrawer) close(activeDrawer.id);
    },
    isOpen(id) {
      return Boolean(activeDrawer && activeDrawer.id === id);
    },
  };
}
