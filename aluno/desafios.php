<?php
// ============================================================
// GEO-EXPLORER — Aluno: Desafios por Nível
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(3);

$db      = getDB();
$alunoId = (int)$_SESSION['usuario_id'];

// Submissão de desafio via AJAX (retorna JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desafio_id'])) {
    header('Content-Type: application/json');
    $desafioId = (int)$_POST['desafio_id'];
    $codigo    = trim($_POST['codigo'] ?? '');

    // Verifica se já tem nota
    $chk = $db->prepare('SELECT id FROM notas WHERE aluno_id = ? AND desafio_id = ?');
    $chk->execute([$alunoId, $desafioId]);
    if ($chk->fetch()) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você já submeteu este desafio. Aguarde a avaliação do professor.']);
    } elseif (!$codigo) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'O código não pode estar vazio.']);
    } else {
        // Armazenamos a submissão como comentário temporário em notas com nota 0
        // (professor precisará avaliar e sobrescrever — mas na lógica do sistema,
        //  não existe tabela separada de submissões, então sinalizamos nota=0 + comentário especial)
        $db->prepare('INSERT INTO notas (aluno_id, desafio_id, professor_id, nota, comentario) VALUES (?, ?, ?, 0, ?)')
           ->execute([$alunoId, $desafioId, 1, '⏳ Aguardando avaliação. Código enviado: ' . substr($codigo, 0, 500)]);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Código enviado! Aguarde a avaliação do professor.']);
    }
    exit;
}

$nivelFiltro = sanitize($_GET['nivel'] ?? '');
$desafioSel  = (int)($_GET['desafio'] ?? 0);

// Busca desafios com informação de nota do aluno
$sql = '
    SELECT d.*,
           n.nota, n.comentario,
           a.titulo AS aula_titulo
    FROM desafios d
    LEFT JOIN notas n     ON n.desafio_id = d.id AND n.aluno_id = ?
    LEFT JOIN aulas a     ON a.id = d.aula_id
    WHERE 1=1
';
$params = [$alunoId];
if ($nivelFiltro && in_array($nivelFiltro, ['basico','intermediario','avancado'])) {
    $sql .= ' AND d.nivel = ?';
    $params[] = $nivelFiltro;
}
$sql .= ' ORDER BY FIELD(d.nivel,"basico","intermediario","avancado"), d.criado_em';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$desafios = $stmt->fetchAll();

// Desafio selecionado para visualização
$desafioAtual = null;
if ($desafioSel) {
    foreach ($desafios as $d) {
        if ($d['id'] === $desafioSel) { $desafioAtual = $d; break; }
    }
}
if (!$desafioAtual && !empty($desafios)) {
    $desafioAtual = $desafios[0];
    $desafioSel   = $desafioAtual['id'];
}

$pageTitle = 'Desafios';
$extraJS   = ['challenges.js'];
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <h1 class="page-title">Desafios de Código</h1>
  </div>

  <!-- Filtros de nível -->
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <a href="?nivel=&desafio=<?= $desafioSel ?>"
       class="btn <?= !$nivelFiltro ? 'btn-primary' : 'btn-outline' ?> btn-sm">Todos</a>
    <a href="?nivel=basico"
       class="btn <?= $nivelFiltro==='basico' ? 'btn-primary' : 'btn-outline' ?> btn-sm">🟢 Básico</a>
    <a href="?nivel=intermediario"
       class="btn <?= $nivelFiltro==='intermediario' ? 'btn-primary' : 'btn-outline' ?> btn-sm">🟡 Intermediário</a>
    <a href="?nivel=avancado"
       class="btn <?= $nivelFiltro==='avancado' ? 'btn-primary' : 'btn-outline' ?> btn-sm">🔴 Avançado</a>
  </div>

  <?php if (empty($desafios)): ?>
    <div class="card empty-state"><p>Nenhum desafio disponível neste nível.</p></div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:260px 1fr;gap:1.5rem;align-items:start;">

    <!-- Lista de desafios -->
    <div class="card" style="position:sticky;top:80px;">
      <div class="card-header"><h3 style="font-size:.9rem;">Desafios (<?= count($desafios) ?>)</h3></div>
      <div style="overflow-y:auto;max-height:calc(100vh - 200px);">
        <?php foreach ($desafios as $d): ?>
          <?php $done = $d['nota'] !== null; ?>
          <a href="?nivel=<?= e($nivelFiltro) ?>&desafio=<?= $d['id'] ?>"
             style="display:flex;align-items:center;justify-content:space-between;
                    padding:.65rem 1rem;font-size:.875rem;
                    color:<?= $d['id'] == $desafioSel ? 'var(--color-primary)' : 'var(--text-primary)' ?>;
                    background:<?= $d['id'] == $desafioSel ? 'var(--color-primary-light)' : 'transparent' ?>;
                    border-left:3px solid <?= $d['id'] == $desafioSel ? 'var(--color-primary)' : 'transparent' ?>;
                    text-decoration:none;border-bottom:1px solid var(--border);">
            <span><?= e($d['titulo']) ?></span>
            <?php if ($done): ?>
              <span class="nota-chip" style="font-size:.75rem;"><?= number_format($d['nota'],1) ?></span>
            <?php else: ?>
              <span class="badge nivel-<?= e($d['nivel']) ?>" style="font-size:.7rem;"><?= ucfirst($d['nivel'][0]) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Editor de desafio -->
    <div>
      <?php if ($desafioAtual): ?>
      <div class="card">
        <div class="card-header">
          <div>
            <h2 style="font-size:1.05rem;"><?= e($desafioAtual['titulo']) ?></h2>
            <span class="badge nivel-<?= e($desafioAtual['nivel']) ?>"><?= ucfirst($desafioAtual['nivel']) ?></span>
          </div>
          <?php if ($desafioAtual['nota'] !== null): ?>
            <span class="nota-chip"><?= number_format($desafioAtual['nota'],1) ?></span>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <p style="margin-bottom:1.25rem;color:var(--text-secondary);">
            <?= e($desafioAtual['descricao']) ?>
          </p>

          <?php if ($desafioAtual['nota'] !== null): ?>
            <!-- Já avaliado -->
            <div class="alert alert-info">
              <strong>Avaliação recebida!</strong> Nota: <?= number_format($desafioAtual['nota'],1) ?>
              <?php if ($desafioAtual['comentario']): ?>
                <br><em>Comentário: <?= e($desafioAtual['comentario']) ?></em>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <!-- Editor -->
            <input type="hidden" id="codigoInicial" value="<?= e($desafioAtual['codigo_inicial'] ?? '') ?>">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
              <label style="font-weight:600;font-size:.875rem;">Seu Código</label>
              <span id="charCounter" style="font-size:.75rem;color:var(--text-muted);"></span>
            </div>
            <textarea id="codeEditor" class="code-editor"
                      data-desafio-id="<?= $desafioAtual['id'] ?>"
                      data-tipo="html"
                      rows="12"><?= e($desafioAtual['codigo_inicial'] ?? '') ?></textarea>
            <div style="display:flex;gap:.75rem;margin-top:1rem;flex-wrap:wrap;">
              <button id="btnRunCode"    class="btn btn-outline">▶ Visualizar</button>
              <button id="btnResetCode"  class="btn btn-outline">↩ Reset</button>
              <button id="btnSubmitCode" class="btn btn-primary">Enviar Resposta</button>
            </div>
            <div id="codeOutput" style="margin-top:1rem;"></div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div>
  <?php endif; ?>
</div>

<script>
window.GEO_BASE_URL = '<?= BASE_URL ?>';
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
