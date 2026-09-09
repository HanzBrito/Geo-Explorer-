<?php
// ============================================================
// GEO-EXPLORER — Verificação de Sessão e Permissões
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Garante que o usuário esteja logado.
 * Se não estiver, redireciona para login.
 */
function requireLogin(): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Garante que o usuário logado tenha o nível de acesso exigido.
 * Aceita um único nível (int) ou array de níveis.
 *
 * @param int|int[] $nivel
 */
function requireNivel($nivel): void {
    requireLogin();
    $niveis = is_array($nivel) ? $nivel : [$nivel];
    if (!in_array($_SESSION['acesso_nivel'], $niveis, true)) {
        http_response_code(403);
        include __DIR__ . '/../includes/header.php';
        echo '<div class="container" style="padding:4rem 2rem;text-align:center;">
                <h2 style="color:var(--danger)">Acesso Negado</h2>
                <p>Você não tem permissão para acessar esta página.</p>
                <a href="' . BASE_URL . '/" class="btn btn-primary">Voltar ao início</a>
              </div>';
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}

/**
 * Verifica se o usuário está logado (booleano).
 */
function estaLogado(): bool {
    return !empty($_SESSION['usuario_id']);
}

/**
 * Retorna o nível de acesso do usuário logado (ou 0 se não logado).
 */
function nivelAtual(): int {
    return (int)($_SESSION['acesso_nivel'] ?? 0);
}

/**
 * Redireciona o usuário para o painel correto conforme seu nível.
 */
function redirecionarParaPainel(): void {
    $nivel = nivelAtual();
    if ($nivel === 1) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($nivel === 2) {
        header('Location: ' . BASE_URL . '/professor/dashboard.php');
    } elseif ($nivel === 3) {
        header('Location: ' . BASE_URL . '/aluno/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/login.php');
    }
    exit;
}
