<?php
// ============================================================
// GEO-EXPLORER — login.php
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Já logado → redireciona
if (estaLogado()) {
    redirecionarParaPainel();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!$email || !$senha) {
        $erro = 'Preencha e-mail e senha.';
    } elseif (!validarEmail($email)) {
        $erro = 'E-mail inválido.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1');
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && verificarSenha($senha, $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['nome']         = $usuario['nome'];
            $_SESSION['email']        = $usuario['email'];
            $_SESSION['acesso_nivel'] = (int)$usuario['acesso_nivel'];
            $_SESSION['foto']         = $usuario['foto'];

            redirecionarParaPainel();
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:440px;margin-top:4rem;">
  <div class="card">
    <div class="card-header" style="justify-content:center;">
      <h1 style="font-size:1.25rem;font-weight:700;">Entrar na plataforma</h1>
    </div>
    <div class="card-body">

      <?php if ($erro): ?>
        <div class="alert alert-error"><?= e($erro) ?></div>
      <?php endif; ?>
      <?php renderFlash(); ?>

      <form method="POST" action="" novalidate>
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" placeholder="seu@email.com"
                 value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Entrar</button>
      </form>

      <p style="text-align:center;margin-top:1.25rem;font-size:.9rem;color:var(--text-secondary);">
        Não tem conta?
        <a href="<?= BASE_URL ?>/cadastro.php">Cadastre-se</a>
      </p>

      <hr class="divider">
      <p style="font-size:.8rem;color:var(--text-muted);text-align:center;">
        Contas de teste:<br>
        <code>admin@geo-explorer.com</code> / <code>admin123</code><br>
        <code>professor@geo-explorer.com</code> / <code>prof123</code><br>
        <code>aluno@geo-explorer.com</code> / <code>aluno123</code>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
