<?php
// ============================================================
// GEO-EXPLORER — Admin: Ver/Excluir Notas
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(1);

$db   = getDB();
$acao = $_GET['acao'] ?? 'listar';
$id   = (int)($_GET['id'] ?? 0);

if ($acao === 'deletar' && $id) {
    $db->prepare('DELETE FROM notas WHERE id = ?')->execute([$id]);
    setFlash('success', 'Nota removida.');
    redirect(BASE_URL . '/admin/notas.php');
}

// Filtros
$filtroAluno = sanitize($_GET['aluno'] ?? '');
$sql = '
    SELECT n.id, n.nota, n.comentario, n.criado_em,
           a.nome AS aluno, a.id AS aluno_id,
           p.nome AS professor,
           d.titulo AS desafio, d.nivel
    FROM notas n
    JOIN usuarios a ON a.id = n.aluno_id
    JOIN usuarios p ON p.id = n.professor_id
    JOIN desafios d ON d.id = n.desafio_id
    WHERE 1=1
';
$params = [];
if ($filtroAluno) {
    $sql .= ' AND (a.nome LIKE ? OR a.email LIKE ?)';
    $params[] = "%$filtroAluno%";
    $params[] = "%$filtroAluno%";
}
$sql .= ' ORDER BY n.criado_em DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$notas = $stmt->fetchAll();

// Média geral
$mediaGeral = $db->query('SELECT ROUND(AVG(nota),2) FROM notas')->fetchColumn();

$pageTitle = 'Gerenciar Notas';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <div>
      <h1 class="page-title">Notas</h1>
      <p class="page-subtitle">Média geral da plataforma: <strong><?= $mediaGeral ?: '—' ?></strong></p>
    </div>
  </div>

  <form method="GET" action="" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <input type="text" name="aluno" placeholder="Buscar por aluno…"
           value="<?= e($filtroAluno) ?>" style="max-width:280px;">
    <button type="submit" class="btn btn-outline btn-sm">Filtrar</button>
    <a href="?" class="btn btn-outline btn-sm">Limpar</a>
  </form>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Aluno</th><th>Desafio</th><th>Nível</th>
            <th>Professor</th><th>Nota</th><th>Comentário</th><th>Data</th><th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($notas)): ?>
            <tr><td colspan="9" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhuma nota encontrada.</td></tr>
          <?php else: foreach ($notas as $n): ?>
            <tr>
              <td><?= $n['id'] ?></td>
              <td><?= e($n['aluno']) ?></td>
              <td><?= e($n['desafio']) ?></td>
              <td><span class="badge nivel-<?= e($n['nivel']) ?>"><?= ucfirst($n['nivel']) ?></span></td>
              <td><?= e($n['professor']) ?></td>
              <td><span class="nota-chip"><?= number_format($n['nota'],1) ?></span></td>
              <td style="font-size:.85rem;max-width:200px;"><?= $n['comentario'] ? e(substr($n['comentario'],0,80)) . (strlen($n['comentario'])>80?'…':'') : '—' ?></td>
              <td><?= formatarData($n['criado_em']) ?></td>
              <td>
                <a href="?acao=deletar&id=<?= $n['id'] ?>" class="btn btn-danger btn-sm"
                   data-confirm="Remover esta nota?">Excluir</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
