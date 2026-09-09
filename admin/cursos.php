<?php
// ============================================================
// GEO-EXPLORER — Admin: Gerenciar Cursos (CRUD)
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
    $db->prepare('DELETE FROM cursos WHERE id = ?')->execute([$id]);
    setFlash('success', 'Curso excluído.');
    redirect(BASE_URL . '/admin/cursos.php');
}

// Toggle ativo
if ($acao === 'toggle' && $id) {
    $db->prepare('UPDATE cursos SET ativo = 1 - ativo WHERE id = ?')->execute([$id]);
    redirect(BASE_URL . '/admin/cursos.php');
}

// Salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId  = (int)($_POST['id'] ?? 0);
    $titulo  = sanitize($_POST['titulo'] ?? '');
    $desc    = sanitize($_POST['descricao'] ?? '');
    $profId  = (int)($_POST['professor_id'] ?? 0);
    $ativo   = isset($_POST['ativo']) ? 1 : 0;

    $erros = [];
    if (strlen($titulo) < 3) $erros[] = 'Título deve ter ao menos 3 caracteres.';
    if (!$profId)             $erros[] = 'Selecione um professor.';

    if (empty($erros)) {
        if ($editId) {
            $db->prepare('UPDATE cursos SET titulo=?,descricao=?,professor_id=?,ativo=? WHERE id=?')
               ->execute([$titulo, $desc, $profId, $ativo, $editId]);
            setFlash('success', 'Curso atualizado.');
        } else {
            $db->prepare('INSERT INTO cursos (titulo,descricao,professor_id,ativo) VALUES (?,?,?,?)')
               ->execute([$titulo, $desc, $profId, $ativo]);
            setFlash('success', 'Curso criado.');
        }
        redirect(BASE_URL . '/admin/cursos.php');
    }
    $acao = $editId ? 'editar' : 'novo';
    $id   = $editId;
}

// Formulário
if ($acao === 'novo' || $acao === 'editar') {
    $curso = null;
    if ($acao === 'editar' && $id) {
        $stmt = $db->prepare('SELECT * FROM cursos WHERE id = ?');
        $stmt->execute([$id]);
        $curso = $stmt->fetch();
        if (!$curso) { setFlash('error','Curso não encontrado.'); redirect(BASE_URL.'/admin/cursos.php'); }
    }
    $professores = $db->query('SELECT id, nome FROM usuarios WHERE acesso_nivel = 2 AND ativo = 1 ORDER BY nome')->fetchAll();
    $pageTitle = $acao === 'novo' ? 'Novo Curso' : 'Editar Curso';
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="max-width:600px;">
      <?php renderFlash(); ?>
      <div class="page-header">
        <h1 class="page-title"><?= $pageTitle ?></h1>
        <a href="<?= BASE_URL ?>/admin/cursos.php" class="btn btn-outline btn-sm">← Voltar</a>
      </div>
      <?php if (!empty($erros)): ?>
        <div class="alert alert-error"><ul style="margin:0;padding-left:1.25rem;">
          <?php foreach($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>
      <div class="card"><div class="card-body">
        <form method="POST" action="">
          <input type="hidden" name="id" value="<?= $curso['id'] ?? 0 ?>">
          <div class="form-group">
            <label>Título do Curso</label>
            <input type="text" name="titulo" value="<?= e($curso['titulo'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Descrição</label>
            <textarea name="descricao"><?= e($curso['descricao'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Professor Responsável</label>
            <select name="professor_id" required>
              <option value="">Selecione…</option>
              <?php foreach ($professores as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($curso['professor_id'] ?? 0) == $p['id'] ? 'selected' : '' ?>>
                  <?= e($p['nome']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="display:flex;align-items:center;gap:.5rem;">
            <input type="checkbox" id="ativo" name="ativo" value="1" <?= ($curso['ativo'] ?? 1) ? 'checked' : '' ?>>
            <label for="ativo" style="margin:0;font-weight:400;">Curso ativo</label>
          </div>
          <div class="flex-between mt-3">
            <a href="<?= BASE_URL ?>/admin/cursos.php" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
          </div>
        </form>
      </div></div>

      <!-- Módulos do curso (somente em edição) -->
      <?php if ($curso): ?>
      <div class="card mt-4">
        <div class="card-header">
          <h3>Módulos</h3>
          <form method="POST" action="<?= BASE_URL ?>/admin/aulas.php" style="display:inline;">
            <input type="hidden" name="novo_modulo" value="1">
            <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
            <button type="submit" class="btn btn-secondary btn-sm">+ Módulo</button>
          </form>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Título</th><th>Ordem</th></tr></thead>
            <tbody>
              <?php
              $mods = $db->prepare('SELECT * FROM modulos WHERE curso_id = ? ORDER BY ordem');
              $mods->execute([$curso['id']]);
              $modulos = $mods->fetchAll();
              if (empty($modulos)): ?>
                <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:1rem;">Nenhum módulo ainda.</td></tr>
              <?php else: foreach($modulos as $mod): ?>
                <tr>
                  <td><?= $mod['id'] ?></td>
                  <td><?= e($mod['titulo']) ?></td>
                  <td><?= $mod['ordem'] ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Listagem
$cursos = $db->query('
    SELECT c.*, u.nome AS professor
    FROM cursos c
    JOIN usuarios u ON u.id = c.professor_id
    ORDER BY c.criado_em DESC
')->fetchAll();

$pageTitle = 'Gerenciar Cursos';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <h1 class="page-title">Cursos</h1>
    <a href="?acao=novo" class="btn btn-primary">+ Novo Curso</a>
  </div>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Título</th><th>Professor</th><th>Status</th><th>Criado</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (empty($cursos)): ?>
            <tr><td colspan="6" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhum curso cadastrado.</td></tr>
          <?php else: foreach ($cursos as $c): ?>
            <tr>
              <td><?= $c['id'] ?></td>
              <td><strong><?= e($c['titulo']) ?></strong></td>
              <td><?= e($c['professor']) ?></td>
              <td>
                <?php if ($c['ativo']): ?>
                  <span class="badge badge-success">Ativo</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Inativo</span>
                <?php endif; ?>
              </td>
              <td><?= formatarData($c['criado_em']) ?></td>
              <td style="white-space:nowrap;">
                <a href="?acao=editar&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                <a href="?acao=toggle&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm"><?= $c['ativo'] ? 'Desativar' : 'Ativar' ?></a>
                <a href="?acao=deletar&id=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                   data-confirm="Excluir o curso '<?= e($c['titulo']) ?>'? Todos os módulos e aulas serão removidos.">Excluir</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
