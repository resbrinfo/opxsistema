<?php
session_start();
require_once __DIR__ . '/config/conexao.php';

// Sincronização inteligente de variáveis de conexão locais do WampServer
if (!isset($conexao) && isset($pdo)) { $conexao = $pdo; }
if (!isset($conexao) && isset($conn)) { $conexao = $conn; }

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// === AÇÃO DE EXCLUSÃO DE LEAD (NOVA) ===
if ($action === 'excluir_lead' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $lead_id = $_POST['lead_id'] ?? null;

    if ($lead_id) {
        try {
            $query = "DELETE FROM leads WHERE id = :id";
            $stmt = $conexao->prepare($query);
            $stmt->execute(['id' => $lead_id]);
            
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// === AÇÕES ANTIGAS PRESERVADAS (Para não quebrar históricos se voltar a usar) ===
if ($action === 'salvar_nota' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $lead_id = $_POST['lead_id'] ?? null;
    $nota = trim($_POST['nota'] ?? '');

    if ($lead_id && $nota !== '') {
        try {
            $query = "INSERT INTO leads_notas (lead_id, nota, data_registro) VALUES (:lead_id, :nota, NOW())";
            $stmt = $conexao->prepare($query);
            $stmt->execute(['lead_id' => $lead_id, 'nota' => $nota]);
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

if ($action === 'listar_notas' && isset($_GET['lead_id'])) {
    $lead_id = $_GET['lead_id'];
    try {
        $query = "SELECT *, DATE_FORMAT(data_registro, '%d/%m/%Y %H:%i') as data_pt FROM leads_notas WHERE lead_id = :lead_id ORDER BY data_registro DESC";
        $stmt = $conexao->prepare($query);
        $stmt->execute(['lead_id' => $lead_id]);
        $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'notas' => $notas]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Ação ou parâmetros inválidos.']);
exit;