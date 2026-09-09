<?php
// ============================================================
// GEO-EXPLORER — Admin: Gerenciar Aulas (CRUD)
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
    $db->prepare('DELETE FROM aulas WHERE id = ?')->execute([$id]);
    setFlash('success', 'Aula excluída.');
    redirect(BASE_URL . '/admin/aulas.php');
}

// Salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Criar novo módulo
    if (isset($_POST['novo_modulo'])) {
        $cursoId = (int)$_POST['curso_id'];
        $titulo  = 'Novo Módulo';
        $ordem   = (int)$db->prepare('SELECT COUNT(*)+1 FROM modulos WHERE curso_id = ?')
                            ->execute([$cursoId]) ? 1 : 1;
        $maxOrdem = $db->prepare('SELECT COALESCE(MAX(ordem),0)+1 FROM modulos WHERE curso_id = ?');
        $maxOrdem->execute([$cursoId]);
        $ord = (int)$maxOrdem->fetchColumn();
        $db->prepare('INSERT INTO modulos (curso_id, titulo, ordem) VALUES (?,?,?)')->execute([$cursoId, $titulo, $ord]);
        setFlash('success','Módulo criado. Edite o título diretamente no banco ou via esta interface.');
        redirect(BASE_URL . '/admin/cursos.php?acao=editar&id=' . $cursoId);
    }

    $editId   = (int)($_POST['id'] ?? 0);
    $moduloId = (int)($_POST['modulo_id'] ?? 0);
    $titulo   = sanitize($_POST['titulo'] ?? '');
    $conteudo = $_POST['conteudo'] ?? '';
    $videoUrl = sanitize($_POST['video_url'] ?? '');
    $ordem    = (int)($_POST['ordem'] ?? 0);

    $erros = [];
    if (strlen($titulo) < 3) $erros[] = 'Título deve ter ao menos 3 caracteres.';
    if (!$moduloId)           $erros[] = 'Selecione um módulo.';

    if (empty($erros)) {
        if ($editId) {
            $db->prepare('UPDATE aulas SET modulo_id=?,titulo=?,conteudo=?,video_url=?,ordem=? WHERE id=?')
               ->execute([$moduloId, $titulo, $conteudo, $videoUrl ?: null, $ordem, $editId]);
            setFlash('success','Aula atualizada.');
        } else {
            $db->prepare('INSERT INTO aulas (modulo_id,titulo,conteudo,video_url,ordem) VALUES (?,?,?,?,?)')
               ->execute([$moduloId, $titulo, $conteudo, $videoUrl ?: null, $ordem]);
            setFlash('success','Aula criada.');
        }
        redirect(BASE_URL . '/admin/aulas.php');
    }
    $acao = $editId ? 'editar' : 'novo';
    $id   = $editId;
}

// Formulário
if ($acao === 'novo' || $acao === 'editar') {
    $aula = null;
    if ($acao === 'editar' && $id) {
        $stmt = $db->prepare('SELECT * FROM aulas WHERE id = ?');
        $stmt->execute([$id]);
        $aula = $stmt->fetch();
        if (!$aula) { setFlash('error','Aula não encontrada.'); redirect(BASE_URL.'/admin/aulas.php'); }
    }
    $modulos = $db->query('
        SELECT m.id, m.titulo AS modulo_titulo, c.titulo AS curso_titulo
        FROM modulos m JOIN cursos c ON c.id = m.curso_id
        ORDER BY c.titulo, m.ordem
    ')->fetchAll();

    $pageTitle = $acao === 'novo' ? 'Nova Aula' : 'Editar Aula';
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="max-width:780px;">
      <?php renderFlash(); ?>
      <div class="page-header">
        <h1 class="page-title"><?= $pageTitle ?></h1>
        <a href="<?= BASE_URL ?>/admin/aulas.php" class="btn btn-outline btn-sm">← Voltar</a>
      </div>
      <?php if (!empty($erros)): ?>
        <div class="alert alert-error"><ul style="margin:0;padding-left:1.25rem;">
          <?php foreach($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>
      <div class="card"><div class="card-body">
        <form method="POST" action="">
          <input type="hidden" name="id" value="<?= $aula['id'] ?? 0 ?>">
          <div class="form-row">
            <div class="form-group">
              <label>Módulo</label>
              <select name="modulo_id" required>
                <option value="">Selecione…</option>
                <?php foreach ($modulos as $m): ?>
                  <option value="<?= $m['id'] ?>" <?= ($aula['modulo_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>>
                    <?= e($m['curso_titulo']) ?> › <?= e($m['modulo_titulo']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Ordem</label>
              <input type="number" name="ordem" value="<?= $aula['ordem'] ?? 0 ?>" min="0">
            </div>
          </div>
          <div class="form-group">
            <label>Título da Aula</label>
            <input type="text" name="titulo" value="<?= e($aula['titulo'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Conteúdo (HTML permitido)</label>
            <textarea name="conteudo" rows="10"><?= htmlspecialchars($aula['conteudo'] ?? '', ENT_QUOTES) ?></textarea>
          </div>
          <div class="form-group">
            <label>URL do Vídeo <span style="color:var(--text-muted)">(opcional)</span></label>
            <input type="url" name="video_url" value="<?= e($aula['video_url'] ?? '') ?>" placeholder="https://youtube.com/…">
          </div>
          <div class="flex-between mt-3">
            <a href="<?= BASE_URL ?>/admin/aulas.php" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Aula</button>
          </div>
        </form>
      </div></div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Filtro por módulo/curso
$filtroCursoId = (int)($_GET['curso_id'] ?? 0);
$aulas = $db->query('
    SELECT a.*, m.titulo AS modulo, c.titulo AS curso
    FROM aulas a
    JOIN modulos m ON m.id = a.modulo_id
    JOIN cursos c  ON c.id = m.curso_id
    ORDER BY c.titulo, m.ordem, a.ordem
')->fetchAll();

$pageTitle = 'Gerenciar Aulas';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <h1 class="page-title">Aulas</h1>
    <a href="?acao=novo" class="btn btn-primary">+ Nova Aula</a>
  </div>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Título</th><th>Módulo</th><th>Curso</th><th>Ordem</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (empty($aulas)): ?>
            <tr><td colspan="6" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhuma aula cadastrada.</td></tr>
          <?php else: foreach ($aulas as $a): ?>
            <tr>
              <td><?= $a['id'] ?></td>
              <td><strong><?= e($a['titulo']) ?></strong></td>
              <td><?= e($a['modulo']) ?></td>
              <td><?= e($a['curso']) ?></td>
              <td><?= $a['ordem'] ?></td>
              <td style="white-space:nowrap;">
                <a href="?acao=editar&id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                <a href="?acao=deletar&id=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
                   data-confirm="Excluir a aula '<?= e($a['titulo']) ?>'?">Excluir</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
