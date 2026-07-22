<?php
session_start();

// Caminho absoluto para a raiz do seu projeto no WampServer
require_once __DIR__ . '/config/conexao.php';

// Sincronização inteligente de variáveis de conexão
if (!isset($conexao) && isset($pdo)) { $conexao = $pdo; }
if (!isset($conexao) && isset($conn)) { $conexao = $conn; }
if (!isset($conexao) && isset($db)) { $conexao = $db; }

header('Content-Type: application/json');

// Se mesmo assim a conexão falhar, avisa o JavaScript com o erro exato
if (!$conexao) {
    echo json_encode(['success' => false, 'error' => 'Variável de conexão com o banco de dados não configurada.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($id && $status) {
        try {
            $query = "UPDATE leads SET status = :status WHERE id = :id";
            $stmt = $conexao->prepare($query);
            $stmt->execute(['status' => $status, 'id' => $id]);
            
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'error' => 'Dados de requisição inválidos ou vazios.']);
exit;