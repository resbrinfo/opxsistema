<?php
// 1. Inclui a conexão segura
require_once __DIR__ . '/config/conexao.php';

// Configura o cabeçalho para responder como JSON puro (ótimo para interagir com o SweetAlert2)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Captura e sanitiza as entradas básicas de UX
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!$email || empty($senha)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Por favor, preencha todos os campos corretamente.']);
        exit;
    }

    try {
        // Usando prepared statement real do PDO contra SQL Injection
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome, u.senha, u.status, n.nome AS nivel 
            FROM usuarios u
            INNER JOIN niveis_acesso n ON u.nivel_acesso_id = n.id
            WHERE u.email = :email 
            LIMIT 1
        ");
        
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        // Verifica se o usuário existe e se a senha confere com o hash criptografado
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            
            // Verifica bloqueio de segurança por status
            if ($usuario['status'] !== 'ativo') {
                echo json_encode(['status' => 'erro', 'mensagem' => 'Este perfil está temporariamente inativo. Contate a diretoria.']);
                exit;
            }

            // Aloca variáveis limpas na sessão
            $_SESSION['usuario_id']    = $usuario['id'];
            $_SESSION['usuario_nome']  = $usuario['nome'];
            $_SESSION['usuario_nivel'] = $usuario['nivel'];

            // Registra auditoria simples de último login
            $update = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
            $update->execute(['id' => $usuario['id']]);

            echo json_encode(['status' => 'sucesso']);
            exit;
            
        } else {
            // Mensagem genérica por segurança (não diz se foi o email ou a senha que errou)
            echo json_encode(['status' => 'erro', 'mensagem' => 'Credenciais incorretas. Tente novamente.']);
            exit;
        }

    } catch (PDOException $e) {
        error_log("Erro na query de login: " . $e->getMessage());
        echo json_encode(['status' => 'erro', 'mensagem' => 'Falha interna ao processar requisição.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'erro', 'mensagem' => 'Método não permitido.']);
    exit;
}