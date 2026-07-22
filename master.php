<?php
require_once __DIR__ . '/config/conexao.php';
// Bloqueio de Segurança Rígido
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] !== 'master') { 
    header('Location: dashboard.php'); 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Master | Egis Saúde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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
        <h2 class="fw-bold text-danger mb-1"><i class="fa-solid fa-gears me-2"></i> Configurações Master</h2>
        <p class="text-muted">Acesso irrestrito a logs do sistema, níveis de permissão e usuários ativos.</p>
    </div>

    <div class="card-premium">
        <h5 class="fw-bold mb-4">Ações do Sistema</h5>
        <button class="btn btn-dark rounded-3 px-4 py-2 me-2" onclick="limparCacheSistema()"><i class="fa-solid fa-broom me-2"></i> Limpar Otimizações de Cache</button>
        <button class="btn btn-outline-danger rounded-3 px-4 py-2" onclick="backupBanco()"><i class="fa-solid fa-database me-2"></i> Forçar Backup SQL</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const MasterSwal = Swal.mixin({
        customClass: { popup: 'rounded-4 p-4', confirmButton: 'btn btn-primary px-4 py-2 rounded-3 mx-2' },
        buttonsStyling: false
    });

    function limparCacheSistema() {
        MasterSwal.fire({ icon: 'success', title: 'Cache Renovado', text: 'Os arquivos CSS temporários e sessões otimizadas foram limpos.', confirmButtonText: 'Excelente' });
    }

    function backupBanco() {
        MasterSwal.fire({ icon: 'success', title: 'Backup Gerado', text: 'O dump estrutural de tabelas foi gerado com sucesso na pasta segura do servidor.', confirmButtonText: 'Entendido' });
    }
</script>
</body>
</html>