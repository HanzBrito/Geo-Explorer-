<?php
// ============================================================
// GEO-EXPLORER — Aluno: Ver Aulas
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(3);

$db      = getDB();
$alunoId = (int)$_SESSION['usuario_id'];
$cursoId = (int)($_GET['curso_id'] ?? 0);
$aulaId  = (int)($_GET['aula_id'] ?? 0);

// Marcar aula como concluída
if (isset($_POST['concluir']) && $aulaId) {
    $stmt = $db->prepare('
        INSERT INTO progresso (aluno_id, aula_id, concluido, data_conclusao)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE concluido = 1, data_conclusao = NOW()
    ');
    $stmt->execute([$alunoId, $aulaId]);
    redirect(BASE_URL . '/aluno/aulas.php?curso_id=' . $cursoId . '&aula_id=' . $aulaId);
}

// Cursos disponíveis
$cursos = $db->query('SELECT id, titulo FROM cursos WHERE ativo = 1 ORDER BY titulo')->fetchAll();

if (!$cursoId && !empty($cursos)) {
    $cursoId = $cursos[0]['id'];
}

// Módulos e aulas do curso selecionado
$modulos = [];
if ($cursoId) {
    $stmtMod = $db->prepare('SELECT * FROM modulos WHERE curso_id = ? ORDER BY ordem');
    $stmtMod->execute([$cursoId]);
    $modulosRaw = $stmtMod->fetchAll();

    foreach ($modulosRaw as $mod) {
        $stmtAula = $db->prepare('
            SELECT a.*,
                   COALESCE(p.concluido, 0) AS concluido
            FROM aulas a
            LEFT JOIN progresso p ON p.aula_id = a.id AND p.aluno_id = ?
            WHERE a.modulo_id = ?
            ORDER BY a.ordem
        ');
        $stmtAula->execute([$alunoId, $mod['id']]);
        $mod['aulas'] = $stmtAula->fetchAll();
        $modulos[] = $mod;
    }
}

// Aula atual
$aulaAtual = null;
if ($aulaId) {
    $stmt = $db->prepare('SELECT * FROM aulas WHERE id = ?');
    $stmt->execute([$aulaId]);
    $aulaAtual = $stmt->fetch();
}
// Se nenhuma aula selecionada, pega a primeira do curso
if (!$aulaAtual && !empty($modulos)) {
    foreach ($modulos as $mod) {
        if (!empty($mod['aulas'])) {
            $aulaAtual = $mod['aulas'][0];
            $aulaId    = $aulaAtual['id'];
            break;
        }
    }
}

// Verificar se aula está concluída
$aulaConcluida = false;
if ($aulaId) {
    $chk = $db->prepare('SELECT concluido FROM progresso WHERE aluno_id = ? AND aula_id = ?');
    $chk->execute([$alunoId, $aulaId]);
    $prog = $chk->fetch();
    $aulaConcluida = (bool)($prog['concluido'] ?? false);
}

$pageTitle = 'Aulas';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <h1 class="page-title">Aulas</h1>
  </div>

  <!-- Seletor de curso -->
  <?php if (count($cursos) > 1): ?>
  <div style="margin-bottom:1.5rem;display:flex;gap:.5rem;flex-wrap:wrap;">
    <?php foreach ($cursos as $c): ?>
      <a href="?curso_id=<?= $c['id'] ?>"
         class="btn <?= $c['id'] == $cursoId ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <?= e($c['titulo']) ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (empty($modulos)): ?>
    <div class="card empty-state"><p>Nenhum conteúdo disponível neste curso ainda.</p></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;">

    <!-- Sidebar: lista de módulos/aulas -->
    <div class="card" style="position:sticky;top:80px;">
      <div class="card-header"><h3 style="font-size:.9rem;">Conteúdo do Curso</h3></div>
      <div style="overflow-y:auto;max-height:calc(100vh - 180px);">
        <?php foreach ($modulos as $mod): ?>
        <div style="padding:.75rem 1rem .25rem;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);">
          <?= $mod['ordem'] ?>. <?= e($mod['titulo']) ?>
        </div>
        <?php foreach ($mod['aulas'] as $a): ?>
          <a href="?curso_id=<?= $cursoId ?>&aula_id=<?= $a['id'] ?>"
             style="display:flex;align-items:center;gap:.6rem;padding:.6rem 1rem;font-size:.875rem;
                    color:<?= $a['id'] == $aulaId ? 'var(--color-primary)' : 'var(--text-primary)' ?>;
                    background:<?= $a['id'] == $aulaId ? 'var(--color-primary-light)' : 'transparent' ?>;
                    text-decoration:none;border-left:3px solid <?= $a['id'] == $aulaId ? 'var(--color-primary)' : 'transparent' ?>;">
            <span style="font-size:.75rem;"><?= $a['concluido'] ? '✅' : '⭕' ?></span>
            <span><?= e($a['titulo']) ?></span>
          </a>
        <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Conteúdo da aula -->
    <div>
      <?php if ($aulaAtual): ?>
      <div class="card">
        <div class="card-header">
          <h2 style="font-size:1.1rem;"><?= e($aulaAtual['titulo']) ?></h2>
          <?php if ($aulaConcluida): ?>
            <span class="badge badge-success">✅ Concluída</span>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <?php if ($aulaAtual['video_url']): ?>
          <div style="margin-bottom:1.5rem;">
            <iframe src="<?= e($aulaAtual['video_url']) ?>"
                    style="width:100%;aspect-ratio:16/9;border:none;border-radius:var(--radius);"
                    allowfullscreen></iframe>
          </div>
          <?php endif; ?>

          <div class="aula-conteudo" style="line-height:1.8;">
            <?= $aulaAtual['conteudo'] ?>
          </div>

          <?php if (!$aulaConcluida): ?>
          <form method="POST" action="" style="margin-top:2rem;">
            <input type="hidden" name="concluir" value="1">
            <button type="submit" class="btn btn-secondary">Marcar como Concluída ✓</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <?php else: ?>
        <div class="card empty-state"><p>Selecione uma aula no menu lateral.</p></div>
      <?php endif; ?>
    </div>

  </div>
  <?php endif; ?>
</div>

<style>
.aula-conteudo h2 { margin:1.5rem 0 .75rem; font-size:1.15rem; }
.aula-conteudo h3 { margin:1.25rem 0 .5rem; font-size:1rem; }
.aula-conteudo p  { margin-bottom:.85rem; }
.aula-conteudo pre{ background:var(--bg-surface2);padding:1rem;border-radius:var(--radius);overflow-x:auto;font-size:.875rem;margin-bottom:1rem; }
.aula-conteudo code{ font-family:'Roboto Mono',monospace;font-size:.875em;background:var(--bg-surface2);padding:.1em .35em;border-radius:3px; }
.aula-conteudo ul, .aula-conteudo ol { padding-left:1.5rem;margin-bottom:.85rem; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
