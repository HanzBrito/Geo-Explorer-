<?php
// ============================================================
// GEO-EXPLORER — Professor: Gerenciar Assuntos
// Lista os conteúdos/módulos para referência e gerenciamento
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(2);

$db     = getDB();
$profId = (int)$_SESSION['usuario_id'];

// Cursos nos quais este professor é responsável
$meusCursos = $db->prepare('SELECT * FROM cursos WHERE professor_id = ? AND ativo = 1 ORDER BY titulo');
$meusCursos->execute([$profId]);
$meusCursos = $meusCursos->fetchAll();

// Módulos e aulas de cada curso
$cursosComModulos = [];
foreach ($meusCursos as $curso) {
    $stmtMod = $db->prepare('SELECT * FROM modulos WHERE curso_id = ? ORDER BY ordem');
    $stmtMod->execute([$curso['id']]);
    $modulos = $stmtMod->fetchAll();

    foreach ($modulos as &$mod) {
        $stmtAula = $db->prepare('SELECT * FROM aulas WHERE modulo_id = ? ORDER BY ordem');
        $stmtAula->execute([$mod['id']]);
        $mod['aulas'] = $stmtAula->fetchAll();
    }
    unset($mod);

    $cursosComModulos[] = array_merge($curso, ['modulos' => $modulos]);
}

// Alunos com progresso nos cursos do professor
$alunosProgresso = $db->prepare('
    SELECT u.id, u.nome, u.email,
           COUNT(DISTINCT p.aula_id) AS aulas_vistas,
           SUM(p.concluido) AS aulas_concluidas
    FROM usuarios u
    LEFT JOIN progresso p ON p.aluno_id = u.id
    WHERE u.acesso_nivel = 3 AND u.ativo = 1
    GROUP BY u.id
    ORDER BY u.nome
');
$alunosProgresso->execute();
$alunos = $alunosProgresso->fetchAll();

$pageTitle = 'Assuntos / Conteúdo';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <h1 class="page-title">Assuntos & Conteúdo</h1>
  </div>

  <?php if (empty($meusCursos)): ?>
    <div class="card">
      <div class="card-body empty-state">
        <p>Você não está vinculado a nenhum curso ativo ainda.</p>
        <p>Peça ao administrador para associar você a um curso.</p>
      </div>
    </div>
  <?php else: ?>

  <!-- Cursos e módulos -->
  <?php foreach ($cursosComModulos as $curso): ?>
  <div class="card mb-4">
    <div class="card-header">
      <h3><?= e($curso['titulo']) ?></h3>
      <span class="badge badge-success">Ativo</span>
    </div>
    <div class="card-body">
      <?php if ($curso['descricao']): ?>
        <p style="color:var(--text-secondary);margin-bottom:1rem;"><?= e($curso['descricao']) ?></p>
      <?php endif; ?>

      <?php if (empty($curso['modulos'])): ?>
        <p style="color:var(--text-muted);">Nenhum módulo neste curso.</p>
      <?php else: ?>
        <?php foreach ($curso['modulos'] as $mod): ?>
        <div style="margin-bottom:1.25rem;">
          <h4 style="font-size:.95rem;font-weight:600;margin-bottom:.5rem;color:var(--color-primary);">
            <?= $mod['ordem'] ?>. <?= e($mod['titulo']) ?>
          </h4>
          <?php if (empty($mod['aulas'])): ?>
            <p style="font-size:.875rem;color:var(--text-muted);padding-left:1rem;">Nenhuma aula neste módulo.</p>
          <?php else: ?>
            <ul style="padding-left:1.5rem;list-style:disc;">
              <?php foreach ($mod['aulas'] as $aula): ?>
                <li style="margin-bottom:.35rem;">
                  <span><?= e($aula['titulo']) ?></span>
                  <?php if ($aula['video_url']): ?>
                    <a href="<?= e($aula['video_url']) ?>" target="_blank" rel="noopener"
                       style="font-size:.8rem;margin-left:.5rem;">▶ Vídeo</a>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Progresso dos alunos -->
  <div class="card mt-4">
    <div class="card-header"><h3>Progresso dos Alunos</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Aluno</th><th>E-mail</th><th>Aulas Vistas</th><th>Aulas Concluídas</th><th>Média</th></tr></thead>
        <tbody>
          <?php foreach ($alunos as $a): ?>
          <tr>
            <td><?= e($a['nome']) ?></td>
            <td><?= e($a['email']) ?></td>
            <td><?= $a['aulas_vistas'] ?></td>
            <td><?= $a['aulas_concluidas'] ?></td>
            <td>
              <?php
              $media = calcularMedia($a['id']);
              echo $media > 0
                ? '<span class="nota-chip">' . number_format($media,1) . '</span>'
                : '<span style="color:var(--text-muted)">—</span>';
              ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
