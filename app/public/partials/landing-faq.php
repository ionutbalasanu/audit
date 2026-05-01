<?php
declare(strict_types=1);
?>
<section id="faq" style="background:var(--bg-1);border-top:1px solid var(--border)">
  <div class="container container-narrow">
    <div class="section-head center">
      <div class="section-kicker">FAQ</div>
      <h2>Întrebări frecvente</h2>
    </div>

    <?php foreach (Config::faqItems() as $i => $item): ?>
    <div class="faq-item<?= $i === 0 ? ' open' : '' ?>">
      <button class="faq-q" type="button" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
        <?= htmlspecialchars($item['question'], ENT_QUOTES, 'UTF-8') ?>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="faq-a"<?= $i === 0 ? '' : ' hidden' ?>>
        <p><?= htmlspecialchars($item['answer'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<script nonce="<?= htmlspecialchars(security_nonce(), ENT_QUOTES, 'UTF-8') ?>">
(function () {
  document.querySelectorAll('.faq-q').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item');
      var isOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(function (el) {
        el.classList.remove('open');
        el.querySelector('.faq-q').setAttribute('aria-expanded', 'false');
        el.querySelector('.faq-a').hidden = true;
      });
      if (!isOpen) {
        item.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        item.querySelector('.faq-a').hidden = false;
      }
    });
  });
}());
</script>
