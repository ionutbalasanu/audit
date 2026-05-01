<?php
declare(strict_types=1);
?>
<section class="section trust-section" aria-labelledby="trust-title">
  <div class="nw-container">
    <div class="section-heading">
      <p class="section-eyebrow" id="trust-title">Trust</p>
      <h2 class="section-title">Auditul nu este doar o listă de tag-uri. Este un filtru rapid pentru problemele care îți blochează vizibilitatea și conversia.</h2>
    </div>

    <div class="trust-strip">
      <?php foreach (Config::logos() as $logo): ?>
        <span><?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?></span>
      <?php endforeach; ?>
    </div>

    <div class="trust-layout">
      <div class="trust-quote">
        <p class="quote-text">"<?= htmlspecialchars(Config::testimonials()[0]['quote'], ENT_QUOTES, 'UTF-8') ?>"</p>
        <p class="quote-author"><?= htmlspecialchars(Config::testimonials()[0]['author'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>

      <div class="trust-metrics">
        <div>
          <strong><?= number_format(max(1, Config::analysisCount()), 0, '.', '.') ?></strong>
          <span>pagini analizate până acum</span>
        </div>
        <div>
          <strong><?= count(Advice::definitions()) ?></strong>
          <span>verificări analizate în fiecare audit</span>
        </div>
        <div>
          <strong>24h</strong>
          <span>timp maxim de revenire pentru cererile de lead</span>
        </div>
      </div>
    </div>
  </div>
</section>
