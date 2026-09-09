<?php
// ============================================================
// GEO-EXPLORER — Professor: Aplicar Notas
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(2);

$db     = getDB();
$profId = (int)$_SESSION['usuario_id'];

// Salvar nova nota (professor não pode editar nota existente)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alunoId   = (int)$_POST['aluno_id'];
    $desafioId = (int)$_POST['desafio_id'];
    $nota      = (float)str_replace(',', '.', $_POST['nota'] ?? '0');
    $comentario= sanitize($_POST['comentario'] ?? '');

    $erros = [];
    if (!$alunoId)                 $erros[] = 'Selecione um aluno.';
    if (!$desafioId)               $erros[] = 'Selecione um desafio.';
    if ($nota < 0 || $nota > 10)   $erros[] = 'Nota deve estar entre 0 e 10.';

    if (empty($erros)) {
        // Verifica se já foi avaliado (professor não pode sobrescrever)
        $chk = $db->prepare('SELECT id FROM notas WHERE aluno_id = ? AND desafio_id = ?');
        $chk->execute([$alunoId, $desafioId]);
        if ($chk->fetch()) {
            setFlash('warning', 'Já existe uma nota para este aluno neste desafio. Notas existentes não podem ser editadas.');
        } else {
            $db->prepare('INSERT INTO notas (aluno_id, desafio_id, professor_id, nota, comentario) VALUES (?,?,?,?,?)')
               ->execute([$alunoId, $desafioId, $profId, $nota, $comentario]);
            setFlash('success', 'Nota aplicada com sucesso!');
        }
        redirect(BASE_URL . '/professor/notas.php');
    }
}

// Dados para o formulário
$alunos   = $db->query('SELECT id, nome FROM usuarios WHERE acesso_nivel = 3 AND ativo = 1 ORDER BY nome')->fetchAll();
$desafios = $db->prepare('SELECT id, titulo, nivel FROM desafios WHERE professor_id = ? ORDER BY titulo');
$desafios->execute([$profId]);
$desafios = $desafios->fetchAll();

// Listagem das notas aplicadas pelo professor
$filtroAluno = sanitize($_GET['aluno'] ?? '');
$sql = '
    SELECT n.id, n.nota, n.comentario, n.criado_em,
           a.nome AS aluno, d.titulo AS desafio, d.nivel
    FROM notas n
    JOIN usuarios a ON a.id = n.aluno_id
    JOIN desafios d ON d.id = n.desafio_id
    WHERE n.professor_id = ?
';
$params = [$profId];
if ($filtroAluno) { $sql .= ' AND (a.nome LIKE ? OR a.email LIKE ?)'; $params[] = "%$filtroAluno%"; $params[] = "%$filtroAluno%"; }
$sql .= ' ORDER BY n.criado_em DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$notas = $stmt->fetchAll();

$pageTitle = 'Aplicar Notas';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <h1 class="page-title">Notas</h1>
  </div>

  <!-- Formulário aplicar nota -->
  <div class="card mb-4">
    <div class="card-header"><h3>Aplicar Nova Nota</h3></div>
    <div class="card-body">
      <?php if (!empty($erros)): ?>
        <div class="alert alert-error"><ul style="margin:0;padding-left:1.25rem;">
          <?php foreach($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>
      <?php if (empty($desafios)): ?>
        <p style="color:var(--text-muted);">Crie desafios primeiro para poder aplicar notas.</p>
      <?php else: ?>
      <form method="POST" action="">
        <div class="form-row">
          <div class="form-group">
            <label>Aluno</label>
            <select name="aluno_id" required>
              <option value="">Selecione…</option>
              <?php foreach ($alunos as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Desafio</label>
            <select name="desafio_id" required>
              <option value="">Selecione…</option>
              <?php foreach ($desafios as $d): ?>
                <option value="<?= $d['id'] ?>"><?= e($d['titulo']) ?> (<?= ucfirst($d['nivel']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Nota (0 – 10)</label>
            <input type="number" name="nota" min="0" max="10" step="0.1" placeholder="Ex: 8.5" required>
          </div>
        </div>
        <div class="form-group">
          <label>Comentário <span style="color:var(--text-muted)">(opcional)</span></label>
          <textarea name="comentario" rows="3" placeholder="Feedback para o aluno…"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Aplicar Nota</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Lista de notas -->
  <div class="card">
    <div class="card-header">
      <h3>Notas Aplicadas (<?= count($notas) ?>)</h3>
      <form method="GET" action="" style="display:flex;gap:.5rem;">
        <input type="text" name="aluno" placeholder="Filtrar por aluno…"
               value="<?= e($filtroAluno) ?>" style="max-width:220px;">
        <button type="submit" class="btn btn-outline btn-sm">Filtrar</button>
      </form>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Aluno</th><th>Desafio</th><th>Nível</th><th>Nota</th><th>Comentário</th><th>Data</th></tr></thead>
        <tbody>
          <?php if (empty($notas)): ?>
            <tr><td colspan="6" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhuma nota aplicada ainda.</td></tr>
          <?php else: foreach ($notas as $n): ?>
            <tr>
              <td><?= e($n['aluno']) ?></td>
              <td><?= e($n['desafio']) ?></td>
              <td><span class="badge nivel-<?= e($n['nivel']) ?>"><?= ucfirst($n['nivel']) ?></span></td>
              <td><span class="nota-chip"><?= number_format($n['nota'],1) ?></span></td>
              <td style="font-size:.85rem;max-width:180px;"><?= $n['comentario'] ? e(substr($n['comentario'],0,70)) : '—' ?></td>
              <td><?= formatarData($n['criado_em']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
