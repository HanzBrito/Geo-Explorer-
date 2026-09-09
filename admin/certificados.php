<?php
// ============================================================
// GEO-EXPLORER — Admin: Gerar Certificados
// ============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireNivel(1);

$db   = getDB();
$acao = $_GET['acao'] ?? 'listar';

// Gerar certificado para um aluno
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar'])) {
    $alunoId  = (int)$_POST['aluno_id'];
    $cursoId  = (int)$_POST['curso_id'];

    // Verifica se já existe
    $chk = $db->prepare('SELECT id FROM certificados WHERE aluno_id = ? AND curso_id = ?');
    $chk->execute([$alunoId, $cursoId]);
    if ($chk->fetch()) {
        setFlash('warning', 'Certificado já emitido para este aluno neste curso.');
    } else {
        // Calcula nota final (média das notas do aluno nos desafios do curso)
        $stmt = $db->prepare('
            SELECT ROUND(AVG(n.nota),2) AS media
            FROM notas n
            JOIN desafios d ON d.id = n.desafio_id
            JOIN aulas a    ON a.id = d.aula_id
            JOIN modulos m  ON m.id = a.modulo_id
            WHERE n.aluno_id = ? AND m.curso_id = ?
        ');
        $stmt->execute([$alunoId, $cursoId]);
        $notaFinal = (float)($stmt->fetchColumn() ?: 0);
        $codigo = gerarCodigo();

        $db->prepare('INSERT INTO certificados (aluno_id, curso_id, nota_final, codigo_verificacao) VALUES (?,?,?,?)')
           ->execute([$alunoId, $cursoId, $notaFinal, $codigo]);
        setFlash('success', 'Certificado emitido com sucesso!');
    }
    redirect(BASE_URL . '/admin/certificados.php');
}

// Deletar certificado
if ($acao === 'deletar') {
    $id = (int)$_GET['id'];
    $db->prepare('DELETE FROM certificados WHERE id = ?')->execute([$id]);
    setFlash('success', 'Certificado excluído.');
    redirect(BASE_URL . '/admin/certificados.php');
}

// Ver certificado
if ($acao === 'ver') {
    $id   = (int)$_GET['id'];
    $stmt = $db->prepare('
        SELECT c.*, u.nome AS aluno, cur.titulo AS curso,
               (SELECT nome FROM usuarios WHERE acesso_nivel = 1 LIMIT 1) AS admin_nome,
               (SELECT nome FROM usuarios WHERE acesso_nivel = 2 AND id = cur.professor_id LIMIT 1) AS prof_nome
        FROM certificados c
        JOIN usuarios u   ON u.id = c.aluno_id
        JOIN cursos cur   ON cur.id = c.curso_id
        WHERE c.id = ?
    ');
    $stmt->execute([$id]);
    $cert = $stmt->fetch();
    if (!$cert) { setFlash('error','Certificado não encontrado.'); redirect(BASE_URL.'/admin/certificados.php'); }

    $pageTitle = 'Certificado — ' . $cert['aluno'];
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container">
      <?php renderFlash(); ?>
      <div style="text-align:right;margin-bottom:1rem;" class="no-print">
        <button onclick="window.print()" class="btn btn-primary">Imprimir / PDF</button>
        <a href="<?= BASE_URL ?>/admin/certificados.php" class="btn btn-outline">← Voltar</a>
      </div>

      <div class="certificado-wrapper">
        <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:1.5rem;letter-spacing:.1em;text-transform:uppercase;">
          Geo-Explorer — Plataforma de Cursos de Programação
        </div>
        <div class="cert-title">Certificado de Conclusão</div>
        <p class="cert-body">
          Certificamos que
        </p>
        <div class="cert-name"><?= e($cert['aluno']) ?></div>
        <p class="cert-body">
          concluiu com êxito o curso<br>
          <strong><?= e($cert['curso']) ?></strong><br>
          com nota final de <strong><?= number_format($cert['nota_final'],1) ?></strong>,
          obtendo a formação de <strong>Desenvolvedor Web</strong>.
        </p>
        <p style="margin-top:1rem;font-size:.9rem;color:var(--text-secondary);">
          Emitido em: <?= formatarData($cert['emitido_em']) ?><br>
          Código de verificação: <code><?= e(substr($cert['codigo_verificacao'],0,16)) ?>…</code>
        </p>
        <div class="cert-assinaturas">
          <div class="cert-assinatura">
            <div class="linha"></div>
            <p><strong><?= e($cert['admin_nome'] ?? 'Administrador') ?></strong></p>
            <p>Direção Acadêmica</p>
          </div>
          <div class="cert-assinatura">
            <div class="linha"></div>
            <p><strong><?= e($cert['prof_nome'] ?? 'Professor(a)') ?></strong></p>
            <p>Docente Responsável</p>
          </div>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Listagem
$certs = $db->query('
    SELECT c.*, u.nome AS aluno, cur.titulo AS curso
    FROM certificados c
    JOIN usuarios u  ON u.id = c.aluno_id
    JOIN cursos cur  ON cur.id = c.curso_id
    ORDER BY c.emitido_em DESC
')->fetchAll();

$alunos = $db->query('SELECT id, nome FROM usuarios WHERE acesso_nivel = 3 AND ativo = 1 ORDER BY nome')->fetchAll();
$cursos = $db->query('SELECT id, titulo FROM cursos WHERE ativo = 1 ORDER BY titulo')->fetchAll();

$pageTitle = 'Certificados';
include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php renderFlash(); ?>
  <div class="page-header">
    <h1 class="page-title">Certificados</h1>
  </div>

  <!-- Emitir novo -->
  <div class="card mb-4">
    <div class="card-header"><h3>Emitir Novo Certificado</h3></div>
    <div class="card-body">
      <form method="POST" action="" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;min-width:220px;">
          <label>Aluno</label>
          <select name="aluno_id" required>
            <option value="">Selecione…</option>
            <?php foreach ($alunos as $a): ?>
              <option value="<?= $a['id'] ?>"><?= e($a['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;min-width:220px;">
          <label>Curso</label>
          <select name="curso_id" required>
            <option value="">Selecione…</option>
            <?php foreach ($cursos as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['titulo']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" name="gerar" class="btn btn-primary">Emitir Certificado</button>
      </form>
    </div>
  </div>

  <!-- Lista -->
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Aluno</th><th>Curso</th><th>Nota Final</th><th>Emitido</th><th>Código</th><th>Ações</th></tr></thead>
        <tbody>
          <?php if (empty($certs)): ?>
            <tr><td colspan="7" class="text-center" style="padding:2rem;color:var(--text-muted);">Nenhum certificado emitido.</td></tr>
          <?php else: foreach ($certs as $c): ?>
            <tr>
              <td><?= $c['id'] ?></td>
              <td><?= e($c['aluno']) ?></td>
              <td><?= e($c['curso']) ?></td>
              <td><span class="nota-chip"><?= number_format($c['nota_final'],1) ?></span></td>
              <td><?= formatarData($c['emitido_em']) ?></td>
              <td><code style="font-size:.75rem;"><?= e(substr($c['codigo_verificacao'],0,12)) ?>…</code></td>
              <td style="white-space:nowrap;">
                <a href="?acao=ver&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Ver</a>
                <a href="?acao=deletar&id=<?= $c['id'] ?>" class="btn btn-danger btn-sm"
                   data-confirm="Excluir este certificado?">Excluir</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
