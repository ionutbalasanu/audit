<?php
declare(strict_types=1);
?>
<header id="resultsToolbar" class="results-toolbar" hidden aria-live="polite">
  <div class="url-badge">
    <div class="ico" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
      </svg>
    </div>
    <div>
      <div><b>Raport &middot; Audit</b></div>
      <div id="workspaceUrl" class="path">Asteptam URL-ul</div>
    </div>
  </div>

  <div class="toolbar-meta">
    <span class="item">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
      <span id="workspaceRunMeta">rulat recent</span>
    </span>
    <span class="item">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
      <span id="workspaceDocumentMeta">Document standard</span>
    </span>
  </div>

  <div class="toolbar-actions">
    <button id="downloadPdfButton" type="button" class="btn btn-outline" disabled>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
      <span>PDF</span>
    </button>
    <button id="openSharePrimary" type="button" class="btn btn-primary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-10 5L2 7"></path></svg>
      <span id="openSharePrimaryLabel">Trimite pe email</span>
    </button>
    <button id="rerunAuditButton" type="button" class="toolbar-rerun" aria-label="Ruleaza din nou auditul">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
    </button>
  </div>
</header>

<!-- Elements kept hidden for JS compatibility -->
<div hidden aria-hidden="true">
  <div id="workspaceTitle">Rezumat pentru pagina analizata</div>
  <p id="workspaceSource"></p>
  <span id="workspaceMetaNote"></span>
  <div id="workspaceUrlPill"></div>
  <span id="workspaceContextBadge" class="status-badge neutral">Standard</span>
  <span id="workspaceDate"></span>
  <button id="openHelpPrimary" type="button">Cere oferta</button>
</div>

<section id="workspaceProgress" class="workspace-progress" hidden aria-live="polite">
  <div class="workspace-progress-inner">
    <div class="workspace-progress-head">
      <strong id="progressTitle">Analizam pagina</strong>
      <span id="progressStep">Pregatim workspace-ul...</span>
    </div>
    <div class="progress-track">
      <span id="progressBar" class="progress-fill"></span>
    </div>
  </div>
</section>

<section id="summaryStrip" class="summary-strip" aria-live="polite"></section>
