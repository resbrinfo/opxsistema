<?php
// Inicia a sessão se ela ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORRIGIDO: Caminho absoluto exato para a raiz do seu projeto local no WampServer
require_once __DIR__ . '/config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$nome_usuario = $_SESSION['usuario_nome'];
$nivel_usuario = $_SESSION['usuario_nivel'];

try {
    // 1. Métrica: Total de Leads Ativos (Não fechados)
    $stmtLeads = $pdo->prepare("SELECT COUNT(*) as total FROM leads WHERE usuario_id = :uid AND status != 'Fechado'");
    $stmtLeads->execute(['uid' => $usuario_id]);
    $totalLeads = $stmtLeads->fetch()['total'];

    // 2. Métrica: Faturamento Total de Propostas Fechadas
    $stmtVendas = $pdo->prepare("SELECT SUM(valor_proposta) as total FROM leads WHERE usuario_id = :uid AND status = 'Fechado'");
    $stmtVendas->execute(['uid' => $usuario_id]);
    $totalVendas = $stmtVendas->fetch()['total'] ?? 0.00;

    // 3. Consulta: Últimos 5 Leads para a Tabela Operacional
    $stmtLista = $pdo->prepare("SELECT * FROM leads WHERE usuario_id = :uid ORDER BY criado_em DESC LIMIT 5");
    $stmtLista->execute(['uid' => $usuario_id]);
    $leadsLista = $stmtLista->fetchAll();

} catch (PDOException $e) {
    error_log("Erro no Dashboard: " . $e->getMessage());
    $totalLeads = 0; $totalVendas = 0.00; $leadsLista = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Operacional | Egis Saúde</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-principal: #f8fafc;
            --bg-card: #ffffff;
            --texto-puro: #0f172a;
            --texto-mutado: #64748b;
            --primary-premium: #2563eb;
            --primary-light: rgba(37, 99, 235, 0.05);
            --sombra-premium: 0 10px 30px -5px rgba(0, 0, 0, 0.03), 0 4px 12px -2px rgba(0, 0, 0, 0.02);
            --transicao: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-principal); color: var(--texto-puro); margin: 0; }
        
        .sidebar { width: 280px; height: 100vh; position: fixed; top: 0; left: 0; background: var(--bg-card); border-right: 1px solid #f1f5f9; padding: 2.5rem 1.75rem; z-index: 100; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar .nav-link { color: var(--texto-mutado); font-weight: 500; font-size: 0.95rem; padding: 0.8rem 1rem; border-radius: 12px; display: flex; align-items: center; transition: var(--transicao); text-decoration: none; margin-bottom: 0.25rem; }
        .sidebar .nav-link i { font-size: 1.1rem; width: 24px; margin-right: 12px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: var(--primary-premium); background-color: var(--primary-light); }

        .main-content { margin-left: 280px; padding: 3.5rem; min-height: 100vh; }
        
        .welcome-banner { background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%); border-radius: 24px; padding: 3.5rem; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.15); }
        .welcome-banner::before { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px); background-size: 30px 30px; pointer-events: none; }

        .card-premium { background: var(--bg-card); border: 1px solid #f1f5f9; border-radius: 20px; padding: 2rem; box-shadow: var(--sombra-premium); transition: var(--transicao); height: 100%; }
        .card-premium:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.05); }

        .table-premium th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--texto-mutado); font-weight: 600; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
        .table-premium td { padding: 1.25rem 0; border-bottom: 1px solid #f8fafc; }

        @media (max-width: 1200px) {
            .sidebar { width: 80px; padding: 2rem 1rem; align-items: center; }
            .sidebar .nav-link span, .sidebar .brand-text { display: none; }
            .main-content { margin-left: 80px; padding: 2rem; }
        }
    </style>
</head>
<body>