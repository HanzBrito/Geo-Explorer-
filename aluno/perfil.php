<?php
// ============================================================
// GEO-EXPLORER — Aluno: Perfil + Foto + Configurações
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(3);

$db      = getDB();
$alunoId = (int)$_SESSION['usuario_id'];
$erros   = [];
$sucesso = '';

// Salvar configuração de tema via AJAX (theme.js)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_config') {
    $tema    = in_array($_POST['tema'] ?? '', ['claro','escuro']) ? $_POST['tema'] : 'claro';
    $fonte   = in_array($_POST['fonte'] ?? '', ['Inter','Roboto','Mono']) ? $_POST['fonte'] : 'Inter';
    $tamanho = in_array($_POST['tamanho'] ?? '', ['pequeno','medio','grande']) ? $_POST['tamanho'] : 'medio';
    $db->prepare('
        INSERT INTO configuracoes_usuario (usuario_id, tema, fonte, tamanho_fonte)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE tema=VALUES(tema), fonte=VALUES(fonte), tamanho_fonte=VALUES(tamanho_fonte)
    ')->execute([$alunoId, $tema, $fonte, $tamanho]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// Salvar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_perfil') {
    $nome        = sanitize($_POST['nome'] ?? '');
    $dataNasc    = sanitize($_POST['data_nascimento'] ?? '');
    $senhaAtual  = $_POST['senha_atual'] ?? '';
    $novaSenha   = $_POST['nova_senha'] ?? '';
    $confirmSenha= $_POST['confirmar_senha'] ?? '';

    if (strlen($nome) < 2) $erros[] = 'Nome deve ter ao menos 2 caracteres.';

    // Troca de senha
    if ($novaSenha) {
        $stmt = $db->prepare('SELECT senha FROM usuarios WHERE id = ?');
        $stmt->execute([$alunoId]);
        $hash = $stmt->fetchColumn();
        if (!verificarSenha($senhaAtual, $hash)) {
            $erros[] = 'Senha atual incorreta.';
        } elseif (strlen($novaSenha) < 6) {
            $erros[] = 'Nova senha deve ter ao menos 6 caracteres.';
        } elseif ($novaSenha !== $confirmSenha) {
            $erros[] = 'As novas senhas não coincidem.';
        }
    }

    // Upload de foto
    $novaFoto = null;
    if (!empty($_FILES['foto']['name'])) {
        $novaFoto = processarUploadFoto($_FILES['foto'], $alunoId);
        if ($novaFoto === null) $erros[] = 'Foto inválida. Use JPG, PNG ou WebP até 2 MB.';
    }

    if (empty($erros)) {
        if ($novaSenha) {
            $db->prepare('UPDATE usuarios SET nome=?,data_nascimento=?,senha=? WHERE id=?')
               ->execute([$nome, $dataNasc ?: null, hashSenha($novaSenha), $alunoId]);
        } else {
            $db->prepare('UPDATE usuarios SET nome=?,data_nascimento=? WHERE id=?')
               ->execute([$nome, $dataNasc ?: null, $alunoId]);
        }
        if ($novaFoto) {
            $db->prepare('UPDATE usuarios SET foto=? WHERE id=?')->execute([$novaFoto, $alunoId]);
            $_SESSION['foto'] = $novaFoto;
        }
        $_SESSION['nome'] = $nome;
        setFlash('success', 'Perfil atualizado com sucesso!');
        redirect(BASE_URL . '/aluno/perfil.php');
    }
}

// Salvar configurações visuais
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_config') {
    $tema    = in_array($_POST['tema'] ?? '', ['claro','escuro']) ? $_POST['tema'] : 'claro';
    $fonte   = in_array($_POST['fonte'] ?? '', ['Inter','Roboto','Mono']) ? $_POST['fonte'] : 'Inter';
    $tamanho = in_array($_POST['tamanho_fonte'] ?? '', ['pequeno','medio','grande']) ? $_POST['tamanho_fonte'] : 'medio';
    $db->prepare('
        INSERT INTO configuracoes_usuario (usuario_id, tema, fonte, tamanho_fonte)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE tema=VALUES(tema), fonte=VALUES(fonte), tamanho_fonte=VALUES(tamanho_fonte)
    ')->execute([$alunoId, $tema, $fonte, $tamanho]);
    setFlash('success', 'Configurações salvas.');
    redirect(BASE_URL . '/aluno/perfil.php');
}

// Dados atuais
$stmt = $db->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmt->execute([$alunoId]);
$usuario = $stmt->fetch();

$cfg = getConfiguracoes();

$pageTitle = 'Meu Perfil';
include __DIR__ . '/../includes/header.php';
?>
<div class="container" style="max-width:700px;">
  <?php renderFlash(); ?>
  <div class="page-header">
    <h1 class="page-title">Meu Perfil</h1>
  </div>

  <?php if (!empty($erros)): ?>
    <div class="alert alert-error"><ul style="margin:0;padding-left:1.25rem;">
      <?php foreach($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <!-- Foto e info -->
  <div class="card mb-4">
    <div class="card-header"><h3>Dados Pessoais</h3></div>
    <div class="card-body">
      <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="salvar_perfil">

        <div style="display:flex;gap:1.5rem;align-items:flex-start;margin-bottom:1.5rem;flex-wrap:wrap;">
          <div style="text-align:center;">
            <img id="fotoPreview"
                 src="<?= fotoUrl($usuario['foto']) ?>"
                 alt="Foto de perfil" class="avatar-upload-preview" style="width:100px;height:100px;">
            <div style="margin-top:.5rem;">
              <label for="fotoInput" class="btn btn-outline btn-sm" style="cursor:pointer;">Trocar foto</label>
              <input type="file" id="fotoInput" name="foto" accept="image/*" style="display:none;">
            </div>
          </div>
          <div style="flex:1;min-width:200px;">
            <div class="form-group">
              <label>Nome</label>
              <input type="text" name="nome" value="<?= e($usuario['nome']) ?>" required>
            </div>
            <div class="form-group">
              <label>E-mail <span style="color:var(--text-muted)">(não editável)</span></label>
              <input type="email" value="<?= e($usuario['email']) ?>" disabled style="opacity:.6;">
            </div>
            <div class="form-group">
              <label>Data de Nascimento</label>
              <input type="date" name="data_nascimento" value="<?= e($usuario['data_nascimento'] ?? '') ?>">
            </div>
          </div>
        </div>

        <hr class="divider">
        <h4 style="font-size:.95rem;font-weight:600;margin-bottom:1rem;">Trocar Senha <span style="color:var(--text-muted);font-weight:400">(deixe em branco para manter)</span></h4>
        <div class="form-row">
          <div class="form-group">
            <label>Senha Atual</label>
            <input type="password" name="senha_atual" placeholder="••••••••">
          </div>
          <div class="form-group">
            <label>Nova Senha</label>
            <input type="password" name="nova_senha" placeholder="Min. 6 caracteres">
          </div>
          <div class="form-group">
            <label>Confirmar Nova Senha</label>
            <input type="password" name="confirmar_senha" placeholder="Repita">
          </div>
        </div>

        <div style="text-align:right;">
          <button type="submit" class="btn btn-primary">Salvar Perfil</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Configurações visuais -->
  <div class="card">
    <div class="card-header"><h3>Preferências Visuais</h3></div>
    <div class="card-body">
      <form method="POST" action="">
        <input type="hidden" name="acao" value="salvar_config">
        <div class="form-row">
          <div class="form-group">
            <label>Tema</label>
            <select name="tema" id="selectTemaPerfil">
              <option value="claro"  <?= $cfg['tema']==='claro'  ? 'selected':'' ?>>☀️ Claro</option>
              <option value="escuro" <?= $cfg['tema']==='escuro' ? 'selected':'' ?>>🌙 Escuro</option>
            </select>
          </div>
          <div class="form-group">
            <label>Fonte</label>
            <select name="fonte" id="selectFonte">
              <option value="Inter"  <?= $cfg['fonte']==='Inter'  ? 'selected':'' ?>>Inter (padrão)</option>
              <option value="Roboto" <?= $cfg['fonte']==='Roboto' ? 'selected':'' ?>>Roboto</option>
              <option value="Mono"   <?= $cfg['fonte']==='Mono'   ? 'selected':'' ?>>Mono</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tamanho da Fonte</label>
            <select name="tamanho_fonte" id="selectTamanho">
              <option value="pequeno" <?= $cfg['tamanho_fonte']==='pequeno' ? 'selected':'' ?>>Pequeno</option>
              <option value="medio"   <?= $cfg['tamanho_fonte']==='medio'   ? 'selected':'' ?>>Médio</option>
              <option value="grande"  <?= $cfg['tamanho_fonte']==='grande'  ? 'selected':'' ?>>Grande</option>
            </select>
          </div>
        </div>
        <div style="text-align:right;">
          <button type="submit" class="btn btn-secondary">Salvar Preferências</button>
        </div>
      </form>
      <script>
      document.getElementById('selectTemaPerfil').addEventListener('change', function() {
        if (window.GeoTheme) window.GeoTheme.applyTheme(this.value);
      });
      </script>
    </div>
  </div>
</div>

<script>
window.GEO_BASE_URL = '<?= BASE_URL ?>';
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
