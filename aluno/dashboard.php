<?php
// ============================================================
// GEO-EXPLORER — Aluno: Dashboard
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(3);

$db      = getDB();
$alunoId = (int)$_SESSION['usuario_id'];

$media    = calcularMedia($alunoId);

// Cursos disponíveis
$cursos = $db->query('SELECT c.*, u.nome AS professor FROM cursos c JOIN usuarios u ON u.id = c.professor_id WHERE c.ativo = 1 ORDER BY c.criado_em')->fetchAll();

// Notas recentes
$notasRecentes = $db->prepare('
    SELECT n.nota, n.comentario, n.criado_em, d.titulo AS desafio
    FROM notas n JOIN desafios d ON d.id = n.desafio_id
    WHERE n.aluno_id = ? ORDER BY n.criado_em DESC LIMIT 5
');
$notasRecentes->execute([$alunoId]);
$notasRecentes = $notasRecentes->fetchAll();

// Desafios pendentes (sem nota ainda)
$desafiosPendentes = $db->prepare('
    SELECT COUNT(*) FROM desafios d
    WHERE NOT EXISTS (SELECT 1 FROM notas n WHERE n.desafio_id = d.id AND n.aluno_id = ?)
');
$desafiosPendentes->execute([$alunoId]);
$pendentes = (int)$desafiosPendentes->fetchColumn();

// Certificados
$certs = $db->prepare('SELECT COUNT(*) FROM certificados WHERE aluno_id = ?');
$certs->execute([$alunoId]);
$totalCerts = (int)$certs->fetchColumn();

$pageTitle = 'Meu Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="page-header">
    <div>
      <h1 class="page-title">Olá, <?= e($_SESSION['nome']) ?>! 👋</h1>
      <p class="page-subtitle">Bem-vindo(a) ao seu painel de aprendizado.</p>
    </div>
    <a href="<?= BASE_URL ?>/aluno/perfil.php" class="btn btn-outline">Meu Perfil</a>
  </div>
  <?php renderFlash(); ?>

  <div class="stats-grid">
    <div class="stat-card">
      <span class="stat-label">Minha Média</span>
      <span class="stat-value" style="color:<?= $media >= 7 ? 'var(--success)' : ($media >= 5 ? 'var(--warning)' : 'var(--danger)') ?>">
        <?= $media > 0 ? number_format($media,1) : '—' ?>
      </span>
      <span class="stat-sub">Média geral das notas</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Desafios Pendentes</span>
      <span class="stat-value"><?= $pendentes ?></span>
      <span class="stat-sub">Sem avaliação ainda</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Certificados</span>
      <span class="stat-value"><?= $totalCerts ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Cursos Disponíveis</span>
      <span class="stat-value"><?= count($cursos) ?></span>
    </div>
  </div>

  <!-- Cursos -->
  <h2 class="section-title">Cursos Disponíveis</h2>
  <?php if (empty($cursos)): ?>
    <div class="card empty-state"><p>Nenhum curso disponível ainda.</p></div>
  <?php else: ?>
  <div class="grid-2" style="gap:1rem;margin-bottom:2rem;">
    <?php foreach ($cursos as $curso): ?>
    <?php
      $concluidas = aulasConcluidas($alunoId, $curso['id']);
      $total      = totalAulasCurso($curso['id']);
      $pct        = $total > 0 ? round($concluidas / $total * 100) : 0;
    ?>
    <div class="card" style="padding:1.5rem;">
      <h3 style="font-size:1rem;font-weight:700;margin-bottom:.5rem;"><?= e($curso['titulo']) ?></h3>
      <p style="font-size:.875rem;color:var(--text-secondary);margin-bottom:1rem;"><?= e(substr($curso['descricao'] ?? '', 0, 100)) ?></p>
      <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:.5rem;">Prof. <?= e($curso['professor']) ?></p>
      <div class="progress-bar" style="margin-bottom:.5rem;">
        <div class="progress-bar-fill" data-pct="<?= $pct ?>"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:.8rem;color:var(--text-muted);">
        <span><?= $concluidas ?>/<?= $total ?> aulas concluídas</span>
        <span><?= $pct ?>%</span>
      </div>
      <div style="margin-top:1rem;">
        <a href="<?= BASE_URL ?>/aluno/aulas.php?curso_id=<?= $curso['id'] ?>" class="btn btn-primary btn-sm">Continuar</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Notas recentes -->
  <div class="card">
    <div class="card-header">
      <h3>Notas Recentes</h3>
      <a href="<?= BASE_URL ?>/aluno/notas.php" class="btn btn-outline btn-sm">Ver todas</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Desafio</th><th>Nota</th><th>Comentário</th><th>Data</th></tr></thead>
        <tbody>
          <?php if (empty($notasRecentes)): ?>
            <tr><td colspan="4" class="text-center" style="padding:1.5rem;color:var(--text-muted);">Nenhuma nota ainda.</td></tr>
          <?php else: foreach ($notasRecentes as $n): ?>
            <tr>
              <td><?= e($n['desafio']) ?></td>
              <td><span class="nota-chip"><?= number_format($n['nota'],1) ?></span></td>
              <td style="font-size:.85rem;"><?= $n['comentario'] ? e(substr($n['comentario'],0,80)) : '—' ?></td>
              <td><?= formatarData($n['criado_em']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
