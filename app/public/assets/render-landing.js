export function syncSurfaceMode(elements, mode) {
  const isWorkspace = mode === "workspace";
  elements.root.dataset.mode = mode;
  if (elements.landing) elements.landing.hidden = false;
  if (elements.workspace) elements.workspace.hidden = !isWorkspace;
  if (elements.education) elements.education.hidden = isWorkspace;
  if (!isWorkspace && elements.mobileActionBar) {
    elements.mobileActionBar.hidden = true;
  }
}

const CONTEXT_COPY = {
  article: {
    hint: "Pagina generală analizează on-page, structură, meta-taguri și indexare.",
    submit: "Pornește auditul paginii generale",
  },
  local: {
    hint: "Pagina locală include verificări on-page pentru oraș, telefon/adresă, link sau hartă Maps, rating în schema și LocalBusiness.",
    submit: "Pornește auditul paginii locale",
  },
};

export function renderContextToggle(buttons, hintElement, context, submitButton = null) {
  const activeContext = context === "local" ? "local" : "article";
  const switchGroup = buttons[0]?.closest(".audit-type-fieldset, .tabs");
  if (switchGroup) {
    switchGroup.dataset.active = activeContext;
  }

  buttons.forEach((button) => {
    const active = button.dataset.type === activeContext;
    const isNativeRadio = button.matches?.('input[type="radio"]');

    button.classList.toggle("active", active);
    button.closest(".audit-type-option")?.classList.toggle("active", active);

    if (isNativeRadio) {
      button.checked = active;
      button.removeAttribute("aria-checked");
    } else {
      button.setAttribute("aria-checked", active ? "true" : "false");
    }
  });

  const copy = CONTEXT_COPY[activeContext];
  if (hintElement) {
    hintElement.textContent = copy.hint;
  }
  if (submitButton) {
    submitButton.textContent = copy.submit;
  }
}
