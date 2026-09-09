<?php
// ============================================================
// GEO-EXPLORER — Professor: Criar/Gerenciar Desafios
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(2);

$db     = getDB();
$profId = (int)$_SESSION['usuario_id'];
$acao   = $_GET['acao'] ?? 'listar';
$id     = (int)($_GET['id'] ?? 0);

// Deletar (somente os próprios)
if ($acao === 'deletar' && $id) {
    $db->prepare('DELETE FROM desafios WHERE id = ? AND professor_id = ?')->execute([$id, $profId]);
    setFlash('success', 'Desafio excluído.');
    redirect(BASE_URL . '/professor/desafios.php');
}

// Salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId    = (int)($_POST['id'] ?? 0);
    $titulo    = sanitize($_POST['titulo'] ?? '');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $nivel     = sanitize($_POST['nivel'] ?? 'basico');
    $aulaId    = (int)($_POST['aula_id'] ?? 0);
    $codInicial= $_POST['codigo_inicial'] ?? '';
    $respEsp   = $_POST['resposta_esperada'] ?? '';

    $erros = [];
    if (strlen($titulo) < 3)  $erros[] = 'Título deve ter ao menos 3 caracteres.';
    if (!$descricao)          $erros[] = 'Descrição é obrigatória.';
    if (!in_array($nivel, ['basico','intermediario','avancado'])) $erros[] = 'Nível inválido.';

    if (empty($erros)) {
        if ($editId) {
            // Verifica propriedade
            $chk = $db->prepare('SELECT id FROM desafios WHERE id = ? AND professor_id = ?');
            $chk->execute([$editId, $profId]);
            if (!$chk->fetch()) { setFlash('error','Sem permissão.'); redirect(BASE_URL.'/professor/desafios.php'); }

            $db->prepare('UPDATE desafios SET titulo=?,descricao=?,nivel=?,aula_id=?,codigo_inicial=?,resposta_esperada=? WHERE id=?')
               ->execute([$titulo, $descricao, $nivel, $aulaId ?: null, $codInicial, $respEsp, $editId]);
            setFlash('success', 'Desafio atualizado.');
        } else {
            $db->prepare('INSERT INTO desafios (professor_id,titulo,descricao,nivel,aula_id,codigo_inicial,resposta_esperada) VALUES (?,?,?,?,?,?,?)')
               ->execute([$profId, $titulo, $descricao, $nivel, $aulaId ?: null, $codInicial, $respEsp]);
            setFlash('success', 'Desafio criado com sucesso!');
        }
        redirect(BASE_URL . '/professor/desafios.php');
    }
    $acao = $editId ? 'editar' : 'novo';
    $id   = $editId;
}

// Formulário
if ($acao === 'novo' || $acao === 'editar') {
    $desafio = null;
    if ($acao === 'editar' && $id) {
        $stmt = $db->prepare('SELECT * FROM desafios WHERE id = ? AND professor_id = ?');
        $stmt->execute([$id, $profId]);
        $desafio = $stmt->fetch();
        if (!$desafio) { setFlash('error','Desafio não encontrado.'); redirect(BASE_URL.'/professor/desafios.php'); }
    }

    $aulas = $db->query('
        SELECT a.id, a.titulo AS aula_titulo, m.titulo AS modulo, c.titulo AS curso
        FROM aulas a
        JOIN modulos m ON m.id = a.modulo_id
        JOIN cursos c  ON c.id = m.curso_id
        ORDER BY c.titulo, m.ordem, a.ordem
    ')->fetchAll();

    $pageTitle = $acao === 'novo' ? 'Novo Desafio' : 'Editar Desafio';
    $extraJS   = ['challenges.js'];
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="max-width:840px;">
      <?php renderFlash(); ?>
      <div class="page-header">
        <h1 class="page-title"><?= $pageTitle ?></h1>
        <a href="<?= BASE_URL ?>/professor/desafios.php" class="btn btn-outline btn-sm">← Voltar</a>
      </div>
      <?php if (!empty($erros)): ?>
        <div class="alert alert-error"><ul style="margin:0;padding-left:1.25rem;">
          <?php foreach($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>
      <div class="card"><div class="card-body">
        <form method="POST" action="">
          <input type="hidden" name="id" value="<?= $desafio['id'] ?? 0 ?>">
          <div class="form-row">
            <div class="form-group">
              <label>Título do Desafio</label>
              <input type="text" name="titulo" value="<?= e($desafio['titulo'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Nível de Dificuldade</label>
              <select name="nivel">
                <option value="basico"        <?= ($desafio['nivel'] ?? '') === 'basico'        ? 'selected' : '' ?>>Básico</option>
                <option value="intermediario" <?= ($desafio['nivel'] ?? '') === 'intermediario' ? 'selected' : '' ?>>Intermediário</option>
                <option value="avancado"      <?= ($desafio['nivel'] ?? '') === 'avancado'      ? 'selected' : '' ?>>Avançado</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Aula Relacionada <span style="color:var(--text-muted)">(opcional)</span></label>
            <select name="aula_id">
              <option value="">Nenhuma</option>
              <?php foreach ($aulas as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ($desafio['aula_id'] ?? 0) == $a['id'] ? 'selected' : '' ?>>
                  <?= e($a['curso']) ?> › <?= e($a['modulo']) ?> › <?= e($a['aula_titulo']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Descrição / Enunciado</label>
            <textarea name="descricao" rows="4"><?= e($desafio['descricao'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Código Inicial <span style="color:var(--text-muted)">(ponto de partida para o aluno)</span></label>
            <textarea class="code-editor" name="codigo_inicial" rows="8"><?= e($desafio['codigo_inicial'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Resposta Esperada <span style="color:var(--text-muted)">(gabarito — não exibido para alunos)</span></label>
            <textarea class="code-editor" name="resposta_esperada" rows="8"><?= e($desafio['resposta_esperada'] ?? '') ?></textarea>
          </div>
          <div class="flex-between mt-3">
            <a href="<?= BASE_URL ?>/professor/desafios.php" class="btn btn-outline">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar Desafio</button>
          </div>
        </form>
      </div></div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Listagem
$filtroNivel = sanitize($_GET['nivel'] ?? '');
$sql = 'SELECT d.*, a.titulo AS aula FROM desafios d LEFT JOIN aulas a ON a.id = d.aula_id WHERE d.professor_id = ?';
$params = [$profId];
if ($filtroNivel) { $sql .= ' AND d.nivel = ?'; $params[] = $filtroNivel; }
$sql .= ' ORDER BY d.criado_em DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$desafios = $stmt->fetchAll();

$pageTitle = 'Meus Desafios';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <div>
      <h1 class="page-title">Meus Desafios</h1>
      <p class="page-subtitle"><?= count($desafios) ?> desafio(s)</p>
    </div>
    <a href="?acao=novo" class="btn btn-primary">+ Novo Desafio</a>
  </div>

  <form method="GET" action="" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;">
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
        <thead><tr><th>#</th><th>Título</th><th>Nível</th><th>Aula</th><th>Criado</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (empty($desafios)): ?>
            <tr><td colspan="6" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhum desafio criado ainda.</td></tr>
          <?php else: foreach ($desafios as $d): ?>
            <tr>
              <td><?= $d['id'] ?></td>
              <td><strong><?= e($d['titulo']) ?></strong></td>
              <td><span class="badge nivel-<?= e($d['nivel']) ?>"><?= ucfirst($d['nivel']) ?></span></td>
              <td><?= $d['aula'] ? e($d['aula']) : '—' ?></td>
              <td><?= formatarData($d['criado_em']) ?></td>
              <td style="white-space:nowrap;">
                <a href="?acao=editar&id=<?= $d['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                <a href="?acao=deletar&id=<?= $d['id'] ?>" class="btn btn-danger btn-sm"
                   data-confirm="Excluir '<?= e($d['titulo']) ?>'?">Excluir</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
