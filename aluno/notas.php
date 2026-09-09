<?php
// ============================================================
// GEO-EXPLORER — Aluno: Ver Notas
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(3);

$db      = getDB();
$alunoId = (int)$_SESSION['usuario_id'];

$notas = $db->prepare('
    SELECT n.id, n.nota, n.comentario, n.criado_em,
           d.titulo AS desafio, d.nivel,
           p.nome  AS professor
    FROM notas n
    JOIN desafios d ON d.id = n.desafio_id
    JOIN usuarios p ON p.id = n.professor_id
    WHERE n.aluno_id = ?
    ORDER BY n.criado_em DESC
');
$notas->execute([$alunoId]);
$notas = $notas->fetchAll();

$media = calcularMedia($alunoId);

// Distribuição por nível
$distNivel = ['basico' => 0, 'intermediario' => 0, 'avancado' => 0];
foreach ($notas as $n) {
    $distNivel[$n['nivel']] = ($distNivel[$n['nivel']] ?? 0) + 1;
}

$pageTitle = 'Minhas Notas';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="page-header">
    <h1 class="page-title">Minhas Notas</h1>
  </div>
  <?php renderFlash(); ?>

  <!-- Resumo -->
  <div class="stats-grid">
    <div class="stat-card">
      <span class="stat-label">Média Geral</span>
      <span class="stat-value" style="color:<?= $media >= 7 ? 'var(--success)' : ($media >= 5 ? 'var(--warning)' : 'var(--danger)') ?>">
        <?= $media > 0 ? number_format($media,1) : '—' ?>
      </span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Total de Notas</span>
      <span class="stat-value"><?= count($notas) ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Básico / Interm. / Avançado</span>
      <span class="stat-value" style="font-size:1.2rem;">
        <?= $distNivel['basico'] ?> / <?= $distNivel['intermediario'] ?> / <?= $distNivel['avancado'] ?>
      </span>
    </div>
  </div>

  <!-- Tabela de notas -->
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Desafio</th><th>Nível</th><th>Professor</th>
            <th>Nota</th><th>Comentário</th><th>Data</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($notas)): ?>
            <tr><td colspan="6" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhuma nota registrada ainda.</td></tr>
          <?php else: foreach ($notas as $n): ?>
            <tr>
              <td><strong><?= e($n['desafio']) ?></strong></td>
              <td><span class="badge nivel-<?= e($n['nivel']) ?>"><?= ucfirst($n['nivel']) ?></span></td>
              <td><?= e($n['professor']) ?></td>
              <td><span class="nota-chip"><?= number_format($n['nota'],1) ?></span></td>
              <td style="font-size:.85rem;max-width:250px;">
                <?= $n['comentario'] ? e(substr($n['comentario'],0,120)) . (strlen($n['comentario'])>120?'…':'') : '—' ?>
              </td>
              <td><?= formatarData($n['criado_em']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($notas)): ?>
    <div class="card-footer" style="text-align:right;">
      <strong>Média: <?= number_format($media,2) ?></strong>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
