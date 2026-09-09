</main><!-- /.main-content -->

<footer class="site-footer">
  <div class="footer-inner">
    <p>
      &copy; <?= date('Y') ?> <strong>Geo-Explorer</strong> — Plataforma de Cursos de Programação Online
    </p>
    <p class="footer-sub">
      Desenvolvido com PHP + MySQL + JavaScript puro
    </p>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/theme.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extraJS)): ?>
  <?php foreach ((array)$extraJS as $js): ?>
    <script src="<?= BASE_URL ?>/assets/js/<?= e($js) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
