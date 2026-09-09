<?php
// ============================================================
// GEO-EXPLORER — Admin: Ver Desafios
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(1);

$db   = getDB();
$acao = $_GET['acao'] ?? 'listar';
$id   = (int)($_GET['id'] ?? 0);

// Deletar
if ($acao === 'deletar' && $id) {
    $db->prepare('DELETE FROM desafios WHERE id = ?')->execute([$id]);
    setFlash('success', 'Desafio excluído.');
    redirect(BASE_URL . '/admin/desafios.php');
}

// Filtros
$filtroNivel = sanitize($_GET['nivel'] ?? '');
$busca       = sanitize($_GET['busca'] ?? '');

$sql = '
    SELECT d.*, u.nome AS professor, a.titulo AS aula
    FROM desafios d
    JOIN usuarios u ON u.id = d.professor_id
    LEFT JOIN aulas a ON a.id = d.aula_id
    WHERE 1=1
';
$params = [];
if ($filtroNivel) { $sql .= ' AND d.nivel = ?'; $params[] = $filtroNivel; }
if ($busca)       { $sql .= ' AND (d.titulo LIKE ? OR d.descricao LIKE ?)'; $params[] = "%$busca%"; $params[] = "%$busca%"; }
$sql .= ' ORDER BY d.criado_em DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$desafios = $stmt->fetchAll();

// Ver detalhes de um desafio
if ($acao === 'ver' && $id) {
    $stmt = $db->prepare('
        SELECT d.*, u.nome AS professor, a.titulo AS aula
        FROM desafios d
        JOIN usuarios u ON u.id = d.professor_id
        LEFT JOIN aulas a ON a.id = d.aula_id
        WHERE d.id = ?
    ');
    $stmt->execute([$id]);
    $desafio = $stmt->fetch();
    if (!$desafio) { setFlash('error','Desafio não encontrado.'); redirect(BASE_URL.'/admin/desafios.php'); }

    // Submissões (notas) deste desafio
    $submissoes = $db->prepare('
        SELECT n.*, al.nome AS aluno
        FROM notas n JOIN usuarios al ON al.id = n.aluno_id
        WHERE n.desafio_id = ? ORDER BY n.criado_em DESC
    ');
    $submissoes->execute([$id]);
    $subs = $submissoes->fetchAll();

    $pageTitle = 'Desafio: ' . $desafio['titulo'];
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container">
      <?php renderFlash(); ?>
      <div class="page-header">
        <h1 class="page-title"><?= e($desafio['titulo']) ?></h1>
        <a href="<?= BASE_URL ?>/admin/desafios.php" class="btn btn-outline btn-sm">← Voltar</a>
      </div>
      <div class="grid-2" style="gap:1.5rem;">
        <div class="card">
          <div class="card-header"><h3>Detalhes</h3></div>
          <div class="card-body">
            <p><strong>Nível:</strong> <span class="badge nivel-<?= e($desafio['nivel']) ?>"><?= ucfirst($desafio['nivel']) ?></span></p>
            <p style="margin-top:.75rem;"><strong>Professor:</strong> <?= e($desafio['professor']) ?></p>
            <?php if ($desafio['aula']): ?>
              <p><strong>Aula:</strong> <?= e($desafio['aula']) ?></p>
            <?php endif; ?>
            <p style="margin-top:.75rem;"><strong>Descrição:</strong></p>
            <p style="color:var(--text-secondary)"><?= e($desafio['descricao']) ?></p>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><h3>Código Inicial</h3></div>
          <div class="card-body">
            <pre style="background:var(--bg-surface2);padding:1rem;border-radius:var(--radius);overflow-x:auto;font-size:.85rem;"><?= e($desafio['codigo_inicial'] ?? '—') ?></pre>
          </div>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header"><h3>Notas / Submissões (<?= count($subs) ?>)</h3></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Aluno</th><th>Nota</th><th>Comentário</th><th>Data</th></tr></thead>
            <tbody>
              <?php if (empty($subs)): ?>
                <tr><td colspan="4" class="text-center" style="padding:1.5rem;color:var(--text-muted);">Nenhuma submissão ainda.</td></tr>
              <?php else: foreach ($subs as $s): ?>
                <tr>
                  <td><?= e($s['aluno']) ?></td>
                  <td><span class="nota-chip"><?= number_format($s['nota'],1) ?></span></td>
                  <td><?= e($s['comentario'] ?: '—') ?></td>
                  <td><?= formatarData($s['criado_em']) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = 'Desafios';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <div>
      <h1 class="page-title">Desafios</h1>
      <p class="page-subtitle"><?= count($desafios) ?> desafio(s)</p>
    </div>
  </div>

  <form method="GET" action="" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <input type="text" name="busca" placeholder="Buscar por título…"
           value="<?= e($busca) ?>" style="max-width:280px;">
    <select name="nivel">
      <option value="">Todos os níveis</option>
      <option value="basico"        <?= $filtroNivel==='basico'?'selected':'' ?>>Básico</option>
      <option value="intermediario" <?= $filtroNivel==='intermediario'?'selected':'' ?>>Intermediário</option>
      <option value="avancado"      <?= $filtroNivel==='avancado'?'selected':'' ?>>Avançado</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Filtrar</button>
    <a href="?" class="btn btn-outline btn-sm">Limpar</a>
  </form>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Título</th><th>Nível</th><th>Professor</th><th>Aula</th><th>Criado</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (empty($desafios)): ?>
            <tr><td colspan="7" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhum desafio encontrado.</td></tr>
          <?php else: foreach ($desafios as $d): ?>
            <tr>
              <td><?= $d['id'] ?></td>
              <td><strong><?= e($d['titulo']) ?></strong></td>
              <td><span class="badge nivel-<?= e($d['nivel']) ?>"><?= ucfirst($d['nivel']) ?></span></td>
              <td><?= e($d['professor']) ?></td>
              <td><?= $d['aula'] ? e($d['aula']) : '—' ?></td>
              <td><?= formatarData($d['criado_em']) ?></td>
              <td style="white-space:nowrap;">
                <a href="?acao=ver&id=<?= $d['id'] ?>" class="btn btn-outline btn-sm">Ver</a>
                <a href="?acao=deletar&id=<?= $d['id'] ?>" class="btn btn-danger btn-sm"
                   data-confirm="Excluir o desafio '<?= e($d['titulo']) ?>'?">Excluir</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
