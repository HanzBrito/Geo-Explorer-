<?php
// ============================================================
// GEO-EXPLORER — Aluno: Ver Certificado
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(3);

$db      = getDB();
$alunoId = (int)$_SESSION['usuario_id'];

$certId = (int)($_GET['id'] ?? 0);

// Busca certificados do aluno
$stmt = $db->prepare('
    SELECT c.*, cur.titulo AS curso,
           (SELECT nome FROM usuarios WHERE acesso_nivel = 1 LIMIT 1) AS admin_nome,
           (SELECT nome FROM usuarios WHERE acesso_nivel = 2 AND id = cur.professor_id LIMIT 1) AS prof_nome
    FROM certificados c
    JOIN cursos cur ON cur.id = c.curso_id
    WHERE c.aluno_id = ?
    ORDER BY c.emitido_em DESC
');
$stmt->execute([$alunoId]);
$certificados = $stmt->fetchAll();

$certAtual = null;
if ($certId) {
    foreach ($certificados as $c) {
        if ($c['id'] === $certId) { $certAtual = $c; break; }
    }
}
if (!$certAtual && !empty($certificados)) {
    $certAtual = $certificados[0];
}

$pageTitle = 'Meu Certificado';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="page-header">
    <h1 class="page-title">Meus Certificados</h1>
    <?php if ($certAtual): ?>
    <div style="display:flex;gap:.5rem;">
      <button onclick="window.print()" class="btn btn-primary no-print">Imprimir / PDF</button>
    </div>
    <?php endif; ?>
  </div>
  <?php renderFlash(); ?>

  <?php if (empty($certificados)): ?>
    <div class="card empty-state">
      <p style="font-size:2rem;">🎓</p>
      <p>Você ainda não possui certificados.</p>
      <p>Continue estudando e conclua um curso para receber seu certificado!</p>
      <a href="<?= BASE_URL ?>/aluno/aulas.php" class="btn btn-primary" style="margin-top:1rem;">Ver Aulas</a>
    </div>

  <?php else: ?>
    <!-- Seletor de certificados -->
    <?php if (count($certificados) > 1): ?>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;" class="no-print">
      <?php foreach ($certificados as $c): ?>
        <a href="?id=<?= $c['id'] ?>"
           class="btn <?= $c['id'] == ($certAtual['id'] ?? 0) ? 'btn-primary' : 'btn-outline' ?> btn-sm">
          <?= e($c['curso']) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($certAtual): ?>
    <!-- Certificado imprimível -->
    <div class="certificado-wrapper">
      <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:1.5rem;letter-spacing:.1em;text-transform:uppercase;">
        Geo-Explorer — Plataforma de Cursos de Programação Online
      </div>

      <div class="cert-title">Certificado de Conclusão</div>

      <p class="cert-body">Certificamos que</p>

      <div class="cert-name"><?= e($_SESSION['nome']) ?></div>

      <p class="cert-body" style="margin-top:1rem;">
        concluiu com êxito o curso<br>
        <strong style="font-size:1.2rem;"><?= e($certAtual['curso']) ?></strong><br><br>
        Formação obtida: <strong>Desenvolvedor Web</strong><br>
        Nota Final: <strong><?= number_format($certAtual['nota_final'],1) ?></strong>
      </p>

      <p style="margin-top:1.25rem;font-size:.875rem;color:var(--text-secondary);">
        Data de Emissão: <?= formatarData($certAtual['emitido_em']) ?><br>
        Código de Verificação: <code><?= e($certAtual['codigo_verificacao']) ?></code>
      </p>

      <div class="cert-assinaturas">
        <div class="cert-assinatura">
          <div class="linha"></div>
          <p><strong><?= e($certAtual['admin_nome'] ?? 'Administrador') ?></strong></p>
          <p>Direção Acadêmica — Geo-Explorer</p>
        </div>
        <div class="cert-assinatura">
          <div class="linha"></div>
          <p><strong><?= e($certAtual['prof_nome'] ?? 'Professor(a)') ?></strong></p>
          <p>Docente Responsável</p>
        </div>
      </div>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
