<?php
// ============================================================
// GEO-EXPLORER — Admin Dashboard
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(1);

$db = getDB();

$totalUsuarios  = $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalAlunos    = $db->query('SELECT COUNT(*) FROM usuarios WHERE acesso_nivel = 3')->fetchColumn();
$totalProfs     = $db->query('SELECT COUNT(*) FROM usuarios WHERE acesso_nivel = 2')->fetchColumn();
$totalCursos    = $db->query('SELECT COUNT(*) FROM cursos WHERE ativo = 1')->fetchColumn();
$totalAulas     = $db->query('SELECT COUNT(*) FROM aulas')->fetchColumn();
$totalDesafios  = $db->query('SELECT COUNT(*) FROM desafios')->fetchColumn();
$totalNotas     = $db->query('SELECT COUNT(*) FROM notas')->fetchColumn();
$totalCerts     = $db->query('SELECT COUNT(*) FROM certificados')->fetchColumn();

$ultimosUsuarios = $db->query('
    SELECT id, nome, email, acesso_nivel, criado_em
    FROM usuarios ORDER BY criado_em DESC LIMIT 8
')->fetchAll();

$ultimasNotas = $db->query('
    SELECT n.nota, n.criado_em,
           a.nome AS aluno, d.titulo AS desafio, p.nome AS professor
    FROM notas n
    JOIN usuarios a ON a.id = n.aluno_id
    JOIN desafios d ON d.id = n.desafio_id
    JOIN usuarios p ON p.id = n.professor_id
    ORDER BY n.criado_em DESC LIMIT 6
')->fetchAll();

$pageTitle = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">

  <div class="page-header">
    <div>
      <h1 class="page-title">Painel Administrativo</h1>
      <p class="page-subtitle">Bem-vindo(a), <?= e($_SESSION['nome']) ?>!</p>
    </div>
  </div>

  <?php renderFlash(); ?>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <span class="stat-label">Usuários</span>
      <span class="stat-value"><?= $totalUsuarios ?></span>
      <span class="stat-sub"><?= $totalAlunos ?> alunos · <?= $totalProfs ?> professores</span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Cursos Ativos</span>
      <span class="stat-value"><?= $totalCursos ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Aulas</span>
      <span class="stat-value"><?= $totalAulas ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Desafios</span>
      <span class="stat-value"><?= $totalDesafios ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Notas Aplicadas</span>
      <span class="stat-value"><?= $totalNotas ?></span>
    </div>
    <div class="stat-card">
      <span class="stat-label">Certificados</span>
      <span class="stat-value"><?= $totalCerts ?></span>
    </div>
  </div>

  <div class="grid-2" style="gap:1.5rem;">

    <!-- Últimos usuários -->
    <div class="card">
      <div class="card-header">
        <h3>Últimos Usuários</h3>
        <a href="<?= BASE_URL ?>/admin/usuarios.php" class="btn btn-outline btn-sm">Ver todos</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nome</th><th>Nível</th><th>Cadastro</th></tr></thead>
          <tbody>
            <?php foreach ($ultimosUsuarios as $u): ?>
            <tr>
              <td>
                <strong><?= e($u['nome']) ?></strong><br>
                <small style="color:var(--text-muted)"><?= e($u['email']) ?></small>
              </td>
              <td><?= badgeNivel((int)$u['acesso_nivel']) ?></td>
              <td style="white-space:nowrap"><?= formatarData($u['criado_em']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Últimas notas -->
    <div class="card">
      <div class="card-header">
        <h3>Últimas Notas</h3>
        <a href="<?= BASE_URL ?>/admin/notas.php" class="btn btn-outline btn-sm">Ver todas</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Aluno</th><th>Desafio</th><th>Nota</th></tr></thead>
          <tbody>
            <?php foreach ($ultimasNotas as $n): ?>
            <tr>
              <td><?= e($n['aluno']) ?></td>
              <td style="font-size:.85rem"><?= e($n['desafio']) ?></td>
              <td><span class="nota-chip"><?= number_format($n['nota'],1) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Atalhos rápidos -->
  <div class="card mt-4">
    <div class="card-header"><h3>Ações Rápidas</h3></div>
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:.75rem;">
      <a href="<?= BASE_URL ?>/admin/usuarios.php?acao=novo"   class="btn btn-primary">+ Novo Usuário</a>
      <a href="<?= BASE_URL ?>/admin/cursos.php?acao=novo"     class="btn btn-secondary">+ Novo Curso</a>
      <a href="<?= BASE_URL ?>/admin/aulas.php?acao=novo"      class="btn btn-outline">+ Nova Aula</a>
      <a href="<?= BASE_URL ?>/admin/certificados.php"         class="btn btn-outline">Certificados</a>
    </div>
  </div>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
