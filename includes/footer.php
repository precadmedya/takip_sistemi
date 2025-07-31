<?php
$fSettings = [];
foreach(['footer_text','footer_logo','footer_logo_width','footer_logo_height'] as $fk){
    $st=$pdo->prepare('SELECT value FROM settings WHERE `key`=?');
    $st->execute([$fk]);
    $fSettings[$fk]=$st->fetchColumn() ?: '';
}
?>
</div>
<footer class="bg-light py-3 mt-5">
  <div class="container d-flex justify-content-between align-items-center">
    <div><?= htmlspecialchars($fSettings['footer_text'] ?: 'Precad Medya 2025 Tüm Hakları Saklıdır.') ?></div>
    <?php if($fSettings['footer_logo']): ?>
      <img src="<?= htmlspecialchars($fSettings['footer_logo']) ?>" style="width:<?= (int)$fSettings['footer_logo_width'] ?>px;height:<?= (int)$fSettings['footer_logo_height'] ?>px;object-fit:contain;" alt="footer logo">
    <?php endif; ?>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
