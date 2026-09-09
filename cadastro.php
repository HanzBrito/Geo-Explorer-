<?php
// ============================================================
// GEO-EXPLORER — cadastro.php
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (estaLogado()) {
    redirecionarParaPainel();
}

$erros = [];
$dados = ['nome' => '', 'email' => '', 'data_nascimento' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome           = sanitize($_POST['nome'] ?? '');
    $email          = sanitize($_POST['email'] ?? '');
    $senha          = $_POST['senha'] ?? '';
    $senha_conf     = $_POST['senha_conf'] ?? '';
    $dataNasc       = sanitize($_POST['data_nascimento'] ?? '');
    $dados = compact('nome', 'email', 'data_nascimento');

    if (strlen($nome) < 3)           $erros[] = 'Nome deve ter ao menos 3 caracteres.';
    if (!validarEmail($email))       $erros[] = 'E-mail inválido.';
    if (strlen($senha) < 6)          $erros[] = 'Senha deve ter ao menos 6 caracteres.';
    if ($senha !== $senha_conf)      $erros[] = 'As senhas não coincidem.';

    if (empty($erros)) {
        $db   = getDB();
        $check = $db->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            $erros[] = 'E-mail já cadastrado.';
        } else {
            $hash = hashSenha($senha);
            $stmt = $db->prepare('
                INSERT INTO usuarios (nome, email, senha, acesso_nivel, data_nascimento, ativo)
                VALUES (?, ?, ?, 3, ?, 1)
            ');
            $stmt->execute([$nome, $email, $hash, $dataNasc ?: null]);
            $novoId = (int)$db->lastInsertId();

            // Config padrão
            $db->prepare('INSERT INTO configuracoes_usuario (usuario_id) VALUES (?)')->execute([$novoId]);

            // Loga automaticamente
            session_regenerate_id(true);
            $_SESSION['usuario_id']   = $novoId;
            $_SESSION['nome']         = $nome;
            $_SESSION['email']        = $email;
            $_SESSION['acesso_nivel'] = 3;
            $_SESSION['foto']         = null;

            setFlash('success', 'Bem-vindo(a), ' . $nome . '! Conta criada com sucesso.');
            redirect(BASE_URL . '/aluno/dashboard.php');
        }
    }
}

$pageTitle = 'Cadastro';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:480px;margin-top:3rem;">
  <div class="card">
    <div class="card-header" style="justify-content:center;">
      <h1 style="font-size:1.25rem;font-weight:700;">Criar conta</h1>
    </div>
    <div class="card-body">

      <?php if ($erros): ?>
        <div class="alert alert-error">
          <ul style="margin:0;padding-left:1.25rem;">
            <?php foreach ($erros as $e_): ?><li><?= e($e_) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <div class="form-group">
          <label for="nome">Nome completo</label>
          <input type="text" id="nome" name="nome" value="<?= e($dados['nome']) ?>"
                 placeholder="Seu nome" required autofocus>
        </div>
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" value="<?= e($dados['email']) ?>"
                 placeholder="seu@email.com" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Min. 6 caracteres" required>
          </div>
          <div class="form-group">
            <label for="senha_conf">Confirmar senha</label>
            <input type="password" id="senha_conf" name="senha_conf" placeholder="Repita a senha" required>
          </div>
        </div>
        <div class="form-group">
          <label for="data_nascimento">Data de nascimento <span style="color:var(--text-muted)">(opcional)</span></label>
          <input type="date" id="data_nascimento" name="data_nascimento"
                 value="<?= e($dados['data_nascimento'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Criar conta</button>
      </form>

      <p style="text-align:center;margin-top:1.25rem;font-size:.9rem;color:var(--text-secondary);">
        Já tem conta? <a href="<?= BASE_URL ?>/login.php">Entrar</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
