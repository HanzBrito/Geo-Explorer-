<?php
// ============================================================
// GEO-EXPLORER — Header Comum
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/functions.php';
}

$cfg = function_exists('getConfiguracoes') ? getConfiguracoes() : ['tema'=>'claro','fonte'=>'Inter','tamanho_fonte'=>'medio'];
$tema        = $cfg['tema'] ?? 'claro';
$fonte       = $cfg['fonte'] ?? 'Inter';
$tamanhoFonte = $cfg['tamanho_fonte'] ?? 'medio';

$fontMap = [
    'Inter'  => 'Inter, sans-serif',
    'Roboto' => '"Roboto", sans-serif',
    'Mono'   => '"Roboto Mono", monospace',
];
$fontCSS = $fontMap[$fonte] ?? 'Inter, sans-serif';

$sizeMap = ['pequeno' => '13px', 'medio' => '15px', 'grande' => '17px'];
$sizeCSS = $sizeMap[$tamanhoFonte] ?? '15px';

$nomeUsuario  = $_SESSION['nome'] ?? '';
$nivelUsuario = (int)($_SESSION['acesso_nivel'] ?? 0);

$painelUrl = match($nivelUsuario) {
    1 => BASE_URL . '/admin/dashboard.php',
    2 => BASE_URL . '/professor/dashboard.php',
    3 => BASE_URL . '/aluno/dashboard.php',
    default => BASE_URL . '/index.php',
};
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= e($tema) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' — Geo-Explorer' : 'Geo-Explorer' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&family=Roboto+Mono&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dark-mode.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
  <style>
    :root {
      --font-family: <?= $fontCSS ?>;
      --font-size-base: <?= $sizeCSS ?>;
    }
  </style>
</head>
<body class="theme-<?= e($tema) ?>">

<header class="site-header">
  <div class="header-inner">
    <a href="<?= $painelUrl ?>" class="logo">
      <span class="logo-geo">Geo</span><span class="logo-explorer">-Explorer</span>
    </a>

    <nav class="main-nav" id="mainNav">
      <?php if ($nivelUsuario === 1): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/admin/usuarios.php">Usuários</a>
        <a href="<?= BASE_URL ?>/admin/cursos.php">Cursos</a>
        <a href="<?= BASE_URL ?>/admin/aulas.php">Aulas</a>
        <a href="<?= BASE_URL ?>/admin/desafios.php">Desafios</a>
        <a href="<?= BASE_URL ?>/admin/notas.php">Notas</a>
        <a href="<?= BASE_URL ?>/admin/certificados.php">Certificados</a>
      <?php elseif ($nivelUsuario === 2): ?>
        <a href="<?= BASE_URL ?>/professor/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/professor/desafios.php">Desafios</a>
        <a href="<?= BASE_URL ?>/professor/notas.php">Notas</a>
        <a href="<?= BASE_URL ?>/professor/assuntos.php">Assuntos</a>
      <?php elseif ($nivelUsuario === 3): ?>
        <a href="<?= BASE_URL ?>/aluno/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/aluno/aulas.php">Aulas</a>
        <a href="<?= BASE_URL ?>/aluno/desafios.php">Desafios</a>
        <a href="<?= BASE_URL ?>/aluno/notas.php">Notas</a>
        <a href="<?= BASE_URL ?>/aluno/certificado.php">Certificado</a>
      <?php endif; ?>
    </nav>

    <?php if ($nomeUsuario): ?>
    <div class="header-user">
      <button class="btn-icon" id="themeToggle" title="Alternar tema" aria-label="Alternar tema">
        <span class="icon-sun">☀️</span>
        <span class="icon-moon">🌙</span>
      </button>
      <div class="user-menu-wrapper">
        <button class="user-btn" id="userMenuBtn" aria-expanded="false">
          <img src="<?= isset($_SESSION['foto']) ? fotoUrl($_SESSION['foto']) : BASE_URL . '/assets/uploads/avatars/default.png' ?>"
               alt="Avatar" class="user-avatar">
          <span><?= e($nomeUsuario) ?></span>
          <span class="chevron">▾</span>
        </button>
        <div class="user-dropdown" id="userDropdown" hidden>
          <?php if ($nivelUsuario === 3): ?>
            <a href="<?= BASE_URL ?>/aluno/perfil.php">Meu Perfil</a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/logout.php">Sair</a>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="header-user">
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">Entrar</a>
      <a href="<?= BASE_URL ?>/cadastro.php" class="btn btn-primary">Cadastrar</a>
    </div>
    <?php endif; ?>

    <button class="hamburger" id="hamburger" aria-label="Menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main class="main-content">
