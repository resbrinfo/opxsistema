<?php
// Inicia a sessão de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de Conexão PDO
$host    = 'localhost';
$dbname  = 'egis_saude_db';
$user    = 'root';
$password = ''; // Coloque a senha do seu banco local

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Recebe e sanitiza os dados do formulário
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? ''; // Mantém a string pura para comparar com o hash

    if (!$email || empty($senha)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos.']);
        exit;
    }

    // Consulta trazendo o nome do nível de acesso via INNER JOIN
    $stmt = $pdo->prepare("
        SELECT u.id, u.nome, u.senha, u.status, n.nome AS nivel 
        FROM usuarios u
        INNER JOIN niveis_acesso n ON u.nivel_acesso_id = n.id
        WHERE u.email = :email 
        LIMIT 1
    ");
    
    $stmt->execute(['email' => $email]);
    $usuario = $stmt->fetch();

    // Validação da senha utilizando password_verify (Padrão PHP 8.2+)
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        if ($usuario['status'] !== 'ativo') {
            echo json_encode(['status' => 'erro', 'mensagem' => 'Este usuário está inativo.']);
            exit;
        }

        // Salva as variáveis na sessão para controlar os menus da Dashboard
        $_SESSION['usuario_id']    = $usuario['id'];
        $_SESSION['usuario_nome']  = $usuario['nome'];
        $_SESSION['usuario_nivel'] = $usuario['nivel']; // ex: 'master', 'corretor'

        // Atualiza a hora do último login
        $update = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
        $update->execute(['id' => $usuario['id']]);

        echo json_encode(['status' => 'sucesso']);
    } else {
        echo json_encode(['status' => 'erro', 'mensagem' => 'E-mail ou senha incorretos.']);
    }

} catch (PDOException $e) {
    // Evita exibir erros brutos de banco para o usuário final por segurança
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro interno de conexão.']);
}