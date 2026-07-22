<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Limpa todas as variáveis salvas na memória da sessão
$_SESSION = array();

// 2. Destrói o cookie de sessão ativo no navegador do usuário
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Elimina a sessão do servidor
session_destroy();

// 4. Redireciona imediatamente para a tela de login inicial
header("Location: index.php");
exit;