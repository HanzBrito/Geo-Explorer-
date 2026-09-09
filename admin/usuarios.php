<?php
// ============================================================
// GEO-EXPLORER — Admin: Gerenciar Usuários (CRUD)
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(1);

$db    = getDB();
$acao  = $_GET['acao'] ?? 'listar';
$id    = (int)($_GET['id'] ?? 0);

// ── DELETAR ────────────────────────────────────────────────
if ($acao === 'deletar' && $id) {
    if ($id === (int)$_SESSION['usuario_id']) {
        setFlash('error', 'Não é possível deletar o próprio usuário.');
    } else {
        $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        setFlash('success', 'Usuário removido com sucesso.');
    }
    redirect(BASE_URL . '/admin/usuarios.php');
}

// ── SALVAR (criar ou editar) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editId  = (int)($_POST['id'] ?? 0);
    $nome    = sanitize($_POST['nome'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $nivel   = (int)($_POST['acesso_nivel'] ?? 3);
    $ativo   = isset($_POST['ativo']) ? 1 : 0;
    $dataNasc= sanitize($_POST['data_nascimento'] ?? '');
    $senha   = $_POST['senha'] ?? '';

    $erros = [];
    if (strlen($nome) < 2) $erros[] = 'Nome inválido.';
    if (!validarEmail($email)) $erros[] = 'E-mail inválido.';
    if (!$editId && strlen($senha) < 6) $erros[] = 'Senha deve ter ao menos 6 caracteres.';

    if (empty($erros)) {
        // Verifica email duplicado
        $chk = $db->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
        $chk->execute([$email, $editId]);
        if ($chk->fetch()) $erros[] = 'E-mail já em uso por outro usuário.';
    }

    if (empty($erros)) {
        if ($editId) {
            if ($senha) {
                $db->prepare('UPDATE usuarios SET nome=?,email=?,senha=?,acesso_nivel=?,ativo=?,data_nascimento=? WHERE id=?')
                   ->execute([$nome, $email, hashSenha($senha), $nivel, $ativo, $dataNasc ?: null, $editId]);
            } else {
                $db->prepare('UPDATE usuarios SET nome=?,email=?,acesso_nivel=?,ativo=?,data_nascimento=? WHERE id=?')
                   ->execute([$nome, $email, $nivel, $ativo, $dataNasc ?: null, $editId]);
            }
            setFlash('success', 'Usuário atualizado.');
        } else {
            $db->prepare('INSERT INTO usuarios (nome,email,senha,acesso_nivel,ativo,data_nascimento) VALUES (?,?,?,?,?,?)')
               ->execute([$nome, $email, hashSenha($senha), $nivel, $ativo, $dataNasc ?: null]);
            $novoId = (int)$db->lastInsertId();
            $db->prepare('INSERT IGNORE INTO configuracoes_usuario (usuario_id) VALUES (?)')->execute([$novoId]);
            setFlash('success', 'Usuário criado.');
        }
        redirect(BASE_URL . '/admin/usuarios.php');
    }
    // Volta ao formulário com erros
    $acao = $editId ? 'editar' : 'novo';
    $id   = $editId;
}

// ── FORMULÁRIO ─────────────────────────────────────────────
if ($acao === 'novo' || $acao === 'editar') {
    $usuario = null;
    if ($acao === 'editar' && $id) {
        $stmt = $db->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        if (!$usuario) { setFlash('error','Usuário não encontrado.'); redirect(BASE_URL.'/admin/usuarios.php'); }
    }

    $pageTitle = $acao === 'novo' ? 'Novo Usuário' : 'Editar Usuário';
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="max-width:600px;">
      <?php renderFlash(); ?>
      <div class="page-header">
        <h1 class="page-title"><?= $pageTitle ?></h1>
        <a href="<?= BASE_URL ?>/admin/usuarios.php" class="btn btn-outline btn-sm">← Voltar</a>
      </div>
      <?php if (!empty($erros)): ?>
        <div class="alert alert-error"><ul style="margin:0;padding-left:1.25rem;">
          <?php foreach($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>
      <div class="card">
        <div class="card-body">
          <form method="POST" action="">
            <input type="hidden" name="id" value="<?= $usuario['id'] ?? 0 ?>">
            <div class="form-group">
              <label>Nome</label>
              <input type="text" name="nome" value="<?= e($usuario['nome'] ?? ($_POST['nome'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
              <label>E-mail</label>
              <input type="email" name="email" value="<?= e($usuario['email'] ?? ($_POST['email'] ?? '')) ?>" required>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Nível de Acesso</label>
                <select name="acesso_nivel">
                  <?php foreach ([1=>'Admin',2=>'Professor',3=>'Aluno'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($usuario['acesso_nivel'] ?? 3) == $v ? 'selected' : '' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Data de Nascimento</label>
                <input type="date" name="data_nascimento" value="<?= e($usuario['data_nascimento'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label>Senha <?= $acao==='editar' ? '<span style="color:var(--text-muted)">(deixe em branco para manter)</span>' : '' ?></label>
              <input type="password" name="senha" <?= $acao==='novo' ? 'required' : '' ?> placeholder="Min. 6 caracteres">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:.5rem;">
              <input type="checkbox" id="ativo" name="ativo" value="1" <?= ($usuario['ativo'] ?? 1) ? 'checked' : '' ?>>
              <label for="ativo" style="margin:0;font-weight:400;">Usuário ativo</label>
            </div>
            <div class="flex-between mt-3">
              <a href="<?= BASE_URL ?>/admin/usuarios.php" class="btn btn-outline">Cancelar</a>
              <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// ── LISTAGEM ───────────────────────────────────────────────
$filtroNivel = isset($_GET['nivel']) ? (int)$_GET['nivel'] : 0;
$busca       = sanitize($_GET['busca'] ?? '');

$sql = 'SELECT * FROM usuarios WHERE 1=1';
$params = [];
if ($filtroNivel) { $sql .= ' AND acesso_nivel = ?'; $params[] = $filtroNivel; }
if ($busca)       { $sql .= ' AND (nome LIKE ? OR email LIKE ?)'; $params[] = "%$busca%"; $params[] = "%$busca%"; }
$sql .= ' ORDER BY criado_em DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

$pageTitle = 'Gerenciar Usuários';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <div>
      <h1 class="page-title">Usuários</h1>
      <p class="page-subtitle"><?= count($usuarios) ?> resultado(s)</p>
    </div>
    <a href="?acao=novo" class="btn btn-primary">+ Novo Usuário</a>
  </div>

  <!-- Filtros -->
  <form method="GET" action="" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <input type="text" name="busca" placeholder="Buscar por nome ou e-mail…"
           value="<?= e($busca) ?>" style="max-width:280px;">
    <select name="nivel">
      <option value="">Todos os níveis</option>
      <option value="1" <?= $filtroNivel===1?'selected':'' ?>>Admin</option>
      <option value="2" <?= $filtroNivel===2?'selected':'' ?>>Professor</option>
      <option value="3" <?= $filtroNivel===3?'selected':'' ?>>Aluno</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm">Filtrar</button>
    <a href="?" class="btn btn-outline btn-sm">Limpar</a>
  </form>

  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Nome</th><th>E-mail</th><th>Nível</th>
            <th>Status</th><th>Cadastro</th><th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usuarios)): ?>
            <tr><td colspan="7" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhum usuário encontrado.</td></tr>
          <?php else: ?>
            <?php foreach ($usuarios as $u): ?>
            <tr>
              <td><?= $u['id'] ?></td>
              <td><strong><?= e($u['nome']) ?></strong></td>
              <td><?= e($u['email']) ?></td>
              <td><?= badgeNivel((int)$u['acesso_nivel']) ?></td>
              <td>
                <?php if ($u['ativo']): ?>
                  <span class="badge badge-success">Ativo</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Inativo</span>
                <?php endif; ?>
              </td>
              <td><?= formatarData($u['criado_em']) ?></td>
              <td style="white-space:nowrap;">
                <a href="?acao=editar&id=<?= $u['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
                <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                  <a href="?acao=deletar&id=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                     data-confirm="Remover o usuário <?= e($u['nome']) ?>?">Excluir</a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
