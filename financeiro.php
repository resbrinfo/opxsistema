<?php
require_once __DIR__ . '/config/conexao.php';
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_nivel'], ['master', 'diretoria', 'financeiro'])) { 
    header('Location: dashboard.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro | Egis Saúde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .sidebar { width: 280px; height: 100vh; position: fixed; top: 0; left: 0; background: #fff; border-right: 1px solid #f1f5f9; padding: 2.5rem 1.75rem; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar .nav-link { color: #64748b; font-weight: 500; padding: 0.8rem 1rem; border-radius: 12px; display: flex; align-items: center; text-decoration: none; margin-bottom: 0.25rem; }
        .sidebar .nav-link i { width: 24px; margin-right: 12px; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #2563eb; background-color: rgba(37, 99, 235, 0.05); }
        .main-content { margin-left: 280px; padding: 3.5rem; min-height: 100vh; }
        .card-premium { background: #fff; border: 1px solid #f1f5f9; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.03); }
    </style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">
    <div class="mb-4">
        <h2 class="fw-bold text-dark mb-1">Conciliação & Comissões</h2>
        <p class="text-muted">Acompanhamento do fechamento de caixa e repasses de operadoras de planos de saúde.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card-premium">
                <div class="text-muted small fw-bold mb-1">TOTAL LIQUIDADO NO MÊS</div>
                <h2 class="fw-bold text-dark mb-0">R$ 124.390,00</h2>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-premium">
                <div class="text-muted small fw-bold mb-1">PREVISÃO DE REPASSE (30 DIAS)</div>
                <h2 class="fw-bold text-success mb-0">R$ 32.110,00</h2>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>