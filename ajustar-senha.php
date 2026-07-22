<?php
// 1. Importa a conexão com o banco de dados
require_once __DIR__ . '/config/conexao.php';

try {
    // 2. Gera o hash nativo e limpo para a senha desejada
    $senhaPura = 'admin123';
    $senhaHash = password_hash($senhaPura, PASSWORD_DEFAULT);

    // 3. Força a atualização do e-mail master com o hash exato
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET senha = :senha, status = 'ativo' 
        WHERE email = 'master@egissaude.com'
    ");
    
    $stmt->execute(['senha' => $senhaHash]);

    echo "<div style='font-family: sans-serif; padding: 2rem; background: #d1fae5; color: #065f46; border-radius: 8px; max-width: 500px; margin: 40px auto; text-align: center;'>";
    echo "<h3>✓ Credenciais Atualizadas!</h3>";
    echo "<p>O hash da senha foi recalculado e gravado com sucesso.</p>";
    echo "<p>Agora você já pode tentar logar no painel com <strong>admin123</strong>.</p>";
    echo "</div>";

} catch (PDOException $e) {
    die("Erro ao atualizar: " . $e->getMessage());
}