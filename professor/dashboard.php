<?php
// ============================================================
// GEO-EXPLORER — Professor: Dashboard
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(2);

$db    = getDB();
$profId = (int)$_SESSION['usuario_id'];

$totalDesafios    = $db->prepare('SELECT COUNT(*) FROM desafios WHERE professor_id = ?');
$totalDesafios->execute([$profId]);
$totalDesafios = (int)$totalDesafios->fetchColumn();

$totalNotasAplicadas = $db->prepare('SELECT COUNT(*) FROM notas WHERE professor_id = ?');
$totalNotasAplicadas->execute([$profId]);
$totalNotasAplicadas = (int)$totalNotasAplicadas->fetchColumn();

$mediaNotas = $db->prepare('SELECT ROUND(AVG(nota),2) FROM notas WHERE professor_id = ?');
$mediaNotas->execute([$profId]);
$mediaNotas = $mediaNotas->fetchColumn();

// Últimas notas aplicadas
$ultimasNotas = $db->prepare('
    SELECT n.nota, n.comentario, n.criado_em,
           a.nome AS aluno, d.titulo AS desafio
    FROM notas n
    JOIN usuarios a ON a.id = n.aluno_id
    JOIN desafios d ON d.id = n.desafio_id
    WHERE n.professor_id = ?
    ORDER BY n.criado_em DESC LIMIT 8
');
$ultimasNotas->execute([$profId]);
$ultimasNotas = $ultimasNotas->fetchAll();

// Desafios recentes
$meusDesafios = $db->prepare('
    SELECT d.id, d.titulo, d.nivel, d.criado_em,
           COUNT(n.id) AS total_notas
    FROM desafios d
    LEFT JOIN notas n ON n.desafio_id = d.id
    WHERE d.professor_id = ?
    GROUP BY d.id
    ORDER BY d.criado_em DESC LIMIT 6
');
$meusDesafios->execute([$profId]);
$meusDesafios = $meusDesafios->fetchAll();

$pageTitle = 'Dashboard Professor';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div class="page-header">
    <div>
      <h1 class="page-title">Painel do Professor</h1>
      <p class="page-subtitle">Olá, <?= e($_SESSION['nome']) ?>!</p>
    </div>
    <a href="<?= BASE_URL ?>/professor/desafios.php?acao=novo" class="btn btn-primary">+ Novo Desafio</a>
  </div>
  <?php renderFlash(); ?>

  <div class="stats-grid">
    <div class="stat-card">
      <span class="stat-label">Meus Desafios</span>
      <span class="stat-value"><?= $totalDesafios ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Notas Aplicadas</span>
      <span class="stat-value"><?= $totalNotasAplicadas ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Média das Notas</span>
      <span class="stat-value"><?= $mediaNotas ?: '—' ?></span>
    </div>
  </div>

  <div class="grid-2" style="gap:1.5rem;">
    <div class="card">
      <div class="card-header">
        <h3>Meus Desafios</h3>
        <a href="<?= BASE_URL ?>/professor/desafios.php" class="btn btn-outline btn-sm">Ver todos</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Título</th><th>Nível</th><th>Notas</th></tr></thead>
          <tbody>
            <?php if (empty($meusDesafios)): ?>
              <tr><td colspan="3" class="text-center" style="padding:1.5rem;color:var(--text-muted);">Nenhum desafio criado ainda.</td></tr>
            <?php else: foreach ($meusDesafios as $d): ?>
              <tr>
                <td><a href="<?= BASE_URL ?>/professor/desafios.php?acao=editar&id=<?= $d['id'] ?>"><?= e($d['titulo']) ?></a></td>
                <td><span class="badge nivel-<?= e($d['nivel']) ?>"><?= ucfirst($d['nivel']) ?></span></td>
                <td><?= $d['total_notas'] ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Últimas Notas Aplicadas</h3>
        <a href="<?= BASE_URL ?>/professor/notas.php" class="btn btn-outline btn-sm">Ver todas</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Aluno</th><th>Desafio</th><th>Nota</th></tr></thead>
          <tbody>
            <?php if (empty($ultimasNotas)): ?>
              <tr><td colspan="3" class="text-center" style="padding:1.5rem;color:var(--text-muted);">Nenhuma nota aplicada ainda.</td></tr>
            <?php else: foreach ($ultimasNotas as $n): ?>
              <tr>
                <td><?= e($n['aluno']) ?></td>
                <td style="font-size:.85rem"><?= e($n['desafio']) ?></td>
                <td><span class="nota-chip"><?= number_format($n['nota'],1) ?></span></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
