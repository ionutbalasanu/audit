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

export function renderContextToggle(buttons, hintElement, context) {
  const switchGroup = buttons[0]?.closest(".tabs");
  if (switchGroup) {
    switchGroup.dataset.active = context === "local" ? "local" : "article";
  }

  buttons.forEach((button) => {
    const active = button.dataset.type === context;
    button.classList.toggle("active", active);
    button.setAttribute("aria-checked", active ? "true" : "false");
  });

  if (!hintElement) return;
  hintElement.textContent =
    context === "local"
      ? "Auditul local include verificari pentru oras, NAP, schema LocalBusiness si semnale geografice."
      : "Auditul standard analizează on-page, structură și indexare. Dacă pagina țintește un oraș, alege „Pagină locală\" pentru verificări Google Maps / Local Pack.";
}
