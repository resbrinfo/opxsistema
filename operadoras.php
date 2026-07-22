<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/config/conexao.php'; 
$mensagem_swal = "";

// Processa o cadastro da Operadora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_operadora'])) {
    try {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($nome)) {
            $stmt = $pdo->prepare("INSERT INTO operadoras (nome) VALUES (:nome)");
            $stmt->execute([':nome' => $nome]);
            $mensagem_swal = "Swal.fire('Sucesso', 'Operadora cadastrada com sucesso!', 'success');";
        }
    } catch (PDOException $e) {
        $mensagem_swal = "Swal.fire('Erro', 'Erro ao salvar: " . addslashes($e->getMessage()) . "', 'error');";
    }
}

// Busca Operadoras
$operadoras = [];
try {
    $operadoras = $pdo->query("SELECT * FROM operadoras ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Operadoras - Premium Clean</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-principal: #f8fafc;
            --bg-card: #ffffff;
            --texto-puro: #0f172a;
            --texto-mutado: #64748b;
            --primary-premium: #ea580c;
            --border-color: #e2e8f0;
        }
        body, html { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: var(--bg-principal); color: var(--texto-puro); }
        .main-content { margin-left: 280px; padding: 3.5rem; min-height: 100vh; }
        @media (max-width: 1200px) { .main-content { margin-left: 80px; padding: 2rem; } }
        .card-premium { background: var(--bg-card); border: 1px solid #f1f5f9; border-radius: 20px; padding: 2.5rem; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); margin-bottom: 2rem; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 1.25rem; }
        .form-group label { font-size: 0.85rem; font-weight: 500; color: var(--texto-mutado); margin-bottom: 0.5rem; }
        .form-control { padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 12px; font-size: 0.95rem; outline: none; }
        .form-control:focus { border-color: var(--primary-premium); }
        .btn-premium-orange { background: var(--primary-premium); color: #fff; border: none; padding: 0.85rem 2rem; border-radius: 12px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; }
        .table-premium { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left; }
        .table-premium th { color: var(--texto-mutado); font-weight: 600; padding-bottom: 1rem; border-bottom: 2px solid var(--border-color); }
        .table-premium td { padding: 1.25rem 0; border-bottom: 1px solid #f8fafc; }
        .badge { padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.8rem; font-weight: 500; background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="card-premium">
            <h2 style="margin:0 0 1.5rem 0; font-size: 1.4rem;">Cadastro de Operadoras / Seguradoras</h2>
            <form action="" method="POST">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 1rem; align-items: flex-end;">
                    <div class="form-group" style="margin:0;">
                        <label>Nome da Operadora *</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: Amil, Bradesco, SulAmérica, Unimed" required>
                    </div>
                    <button type="submit" name="salvar_operadora" class="btn-premium-orange"><i class="fa-solid fa-plus"></i> Cadastrar</button>
                </div>
            </form>
        </div>

        <div class="card-premium">
            <h3 style="margin:0 0 1.5rem 0; font-size: 1.1rem;">Operadoras Ativas</h3>
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome da Operadora</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($operadoras) > 0): ?>
                        <?php foreach($operadoras as $op): ?>
                            <tr>
                                <td>#<?= $op['id'] ?></td>
                                bins<td><strong><?= htmlspecialchars($op['nome']) ?></strong></td>
                                <td><span class="badge"><?= $op['status'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--texto-mutado); padding: 2rem;">Nenhuma operadora cadastrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded', () => { <?php if (!empty($mensagem_swal)) echo $mensagem_swal; ?> });</script>
</body>
</html>