<?php
// ============================================================
// GEO-EXPLORER — Funções Auxiliares
// ============================================================

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Detecta subdiretório automaticamente
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // Sobe até a raiz do projeto (geo-explorer)
    define('BASE_URL', $protocol . '://' . $host . str_replace(['/admin','/professor','/aluno','/includes'], '', $scriptDir));
}

/**
 * Escapa string para saída HTML segura.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redireciona e termina o script.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Armazena uma mensagem de flash na sessão.
 * @param string $tipo  'success' | 'error' | 'info' | 'warning'
 */
function setFlash(string $tipo, string $mensagem): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

/**
 * Retorna e limpa a mensagem de flash.
 */
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Renderiza o banner de flash se existir.
 */
function renderFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $tipo = e($flash['tipo']);
        $msg  = e($flash['mensagem']);
        echo "<div class=\"alert alert-{$tipo}\" role=\"alert\">{$msg}</div>";
    }
}

/**
 * Hash de senha usando PASSWORD_BCRYPT.
 */
function hashSenha(string $senha): string {
    return password_hash($senha, PASSWORD_BCRYPT);
}

/**
 * Verifica senha contra hash.
 */
function verificarSenha(string $senha, string $hash): bool {
    return password_verify($senha, $hash);
}

/**
 * Sanitiza entrada de texto simples.
 */
function sanitize(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Valida e-mail.
 */
function validarEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gera código UUID v4 simples.
 */
function gerarCodigo(): string {
    return bin2hex(random_bytes(16));
}

/**
 * Formata data para exibição no padrão brasileiro.
 */
function formatarData(string $data): string {
    if (!$data) return '—';
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $data)
        ?: DateTime::createFromFormat('Y-m-d', $data);
    return $dt ? $dt->format('d/m/Y') : $data;
}

/**
 * Calcula média de notas de um aluno.
 */
function calcularMedia(int $alunoId): float {
    $db = getDB();
    $stmt = $db->prepare('SELECT AVG(nota) AS media FROM notas WHERE aluno_id = ?');
    $stmt->execute([$alunoId]);
    $row = $stmt->fetch();
    return round((float)($row['media'] ?? 0), 2);
}

/**
 * Retorna a foto de perfil do usuário (URL relativa).
 */
function fotoUrl(?string $foto): string {
    if ($foto && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $foto)) {
        return BASE_URL . '/assets/uploads/avatars/' . rawurlencode($foto);
    }
    return BASE_URL . '/assets/uploads/avatars/default.png';
}

/**
 * Processa o upload de foto de perfil e retorna o nome do arquivo
 * ou null em caso de erro.
 */
function processarUploadFoto(array $file, int $usuarioId): ?string {
    $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $permitidos, true)) return null;
    if ($file['size'] > 2 * 1024 * 1024) return null; // máx 2 MB

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nome = 'avatar_' . $usuarioId . '_' . time() . '.' . strtolower($ext);
    $destino = __DIR__ . '/../assets/uploads/avatars/' . $nome;

    if (move_uploaded_file($file['tmp_name'], $destino)) {
        return $nome;
    }
    return null;
}

/**
 * Retorna a label do nível de acesso.
 */
function labelNivel(int $nivel): string {
    return match($nivel) {
        1 => 'Admin',
        2 => 'Professor',
        3 => 'Aluno',
        default => 'Desconhecido',
    };
}

/**
 * Retorna badge HTML colorido para o nível.
 */
function badgeNivel(int $nivel): string {
    $classes = [1 => 'badge-danger', 2 => 'badge-warning', 3 => 'badge-info'];
    $cls = $classes[$nivel] ?? 'badge-secondary';
    return '<span class="badge ' . $cls . '">' . labelNivel($nivel) . '</span>';
}

/**
 * Retorna as configurações visuais do usuário logado.
 */
function getConfiguracoes(): array {
    if (empty($_SESSION['usuario_id'])) {
        return ['tema' => 'claro', 'fonte' => 'Inter', 'tamanho_fonte' => 'medio'];
    }
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM configuracoes_usuario WHERE usuario_id = ?');
    $stmt->execute([$_SESSION['usuario_id']]);
    $cfg = $stmt->fetch();
    return $cfg ?: ['tema' => 'claro', 'fonte' => 'Inter', 'tamanho_fonte' => 'medio'];
}

/**
 * Conta aulas concluídas por um aluno em um curso.
 */
function aulasConcluidas(int $alunoId, int $cursoId): int {
    $db = getDB();
    $stmt = $db->prepare('
        SELECT COUNT(*) AS total
        FROM progresso p
        JOIN aulas a ON a.id = p.aula_id
        JOIN modulos m ON m.id = a.modulo_id
        WHERE p.aluno_id = ? AND m.curso_id = ? AND p.concluido = 1
    ');
    $stmt->execute([$alunoId, $cursoId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Conta total de aulas de um curso.
 */
function totalAulasCurso(int $cursoId): int {
    $db = getDB();
    $stmt = $db->prepare('
        SELECT COUNT(*) FROM aulas a
        JOIN modulos m ON m.id = a.modulo_id
        WHERE m.curso_id = ?
    ');
    $stmt->execute([$cursoId]);
    return (int)$stmt->fetchColumn();
}
