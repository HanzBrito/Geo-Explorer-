<?php
// ============================================================
// GEO-EXPLORER — index.php
// Redireciona usuário logado ao painel; exibe landing page
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (estaLogado()) {
    redirecionarParaPainel();
}

$pageTitle = 'Início';
include __DIR__ . '/includes/header.php';
?>

<div class="container">

  <!-- Hero -->
  <section style="text-align:center;padding:4rem 1rem 3rem;">
    <h1 style="font-size:2.8rem;font-weight:800;line-height:1.2;margin-bottom:1rem;">
      Aprenda a programar com<br>
      <span style="color:var(--color-primary)">Geo</span><span style="color:var(--color-secondary)">-Explorer</span>
    </h1>
    <p style="font-size:1.15rem;color:var(--text-secondary);max-width:560px;margin:0 auto 2rem;">
      Plataforma de cursos online de programação — HTML, CSS, JavaScript, PHP e MySQL —
      com desafios práticos, notas e certificados.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-primary btn-lg">Começar gratuitamente</a>
      <a href="<?= BASE_URL ?>/login.php"    class="btn btn-outline btn-lg">Já tenho conta</a>
    </div>
  </section>

  <!-- Features -->
  <section style="margin:2rem 0 4rem;">
    <h2 class="section-title" style="text-align:center;border:none;margin-bottom:2rem;font-size:1.4rem;">
      O que você vai aprender
    </h2>
    <div class="grid-3">
      <?php
      $modulos = [
        ['🌐','HTML','Estrutura de páginas web — tags, formulários, tabelas e semântica.'],
        ['🎨','CSS','Estilização, Flexbox, Grid, animações e design responsivo.'],
        ['⚡','JavaScript','Variáveis, funções, DOM, eventos e programação assíncrona.'],
        ['🐘','PHP','Backend robusto — sessões, funções, orientação a objetos.'],
        ['🗄️','MySQL','Banco de dados relacional — CRUD, joins e procedures.'],
        ['🚀','Projeto Final','Construa um sistema completo do zero ao deploy.'],
      ];
      foreach ($modulos as $m): ?>
      <div class="card" style="padding:1.5rem;text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:.75rem;"><?= $m[0] ?></div>
        <h3 style="font-weight:700;margin-bottom:.5rem;"><?= e($m[1]) ?></h3>
        <p style="font-size:.9rem;color:var(--text-secondary);"><?= e($m[2]) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- CTA -->
  <section style="background:var(--color-primary);color:#fff;border-radius:var(--radius-lg);padding:3rem 2rem;text-align:center;margin-bottom:4rem;">
    <h2 style="font-size:1.8rem;font-weight:700;margin-bottom:1rem;">Pronto para começar?</h2>
    <p style="opacity:.9;margin-bottom:1.5rem;">Cadastre-se gratuitamente e acesse todos os módulos.</p>
    <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-lg" style="background:#fff;color:var(--color-primary);border-color:#fff;">
      Criar minha conta
    </a>
  </section>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
