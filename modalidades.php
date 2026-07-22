<?php
session_start();
// Proteção de Sessão padrão do ecossistema
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Carregamento da conexão oficial
require_once __DIR__ . '/config/conexao.php'; 
$mensagem_swal = "";

// Processa o cadastro de Nova Modalidade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_modalidade'])) {
    try {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($nome)) {
            // Verifica se já existe uma modalidade com o mesmo nome para evitar duplicados
            $check = $pdo->prepare("SELECT id FROM modalidades WHERE nome = :nome");
            $check->execute([':nome' => $nome]);
            
            if ($check->rowCount() == 0) {
                $stmt = $pdo->prepare("INSERT INTO modalidades (nome) VALUES (:nome)");
                $stmt->execute([':nome' => $nome]);
                $mensagem_swal = "Swal.fire('Sucesso', 'Modalidade cadastrada com sucesso!', 'success');";
            } else {
                $mensagem_swal = "Swal.fire('Aviso', 'Esta modalidade já está cadastrada no sistema.', 'warning');";
            }
        }
    } catch (PDOException $e) {
        $mensagem_swal = "Swal.fire('Erro', 'Falha ao salvar no banco: " . addslashes($e->getMessage()) . "', 'error');";
    }
}

// Busca todas as Modalidades ordenadas alfabeticamente
$modalidades = [];
try {
    $modalidades = $pdo->query("SELECT * FROM modalidades ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tratamento silencioso contra falhas
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Modalidades - Premium Clean</title>
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
            --sombra-premium: 0 10px 30px -10px rgba(0,0,0,0.05);
        }
        body, html { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: var(--bg-principal); color: var(--texto-puro); }
        
        .main-content { margin-left: 280px; padding: 3.5rem; min-height: 100vh; }
        @media (max-width: 1200px) { .main-content { margin-left: 80px; padding: 2rem; } }
        
        .card-premium { 
            background: var(--bg-card); 
            border: 1px solid #f1f5f9; 
            border-radius: 20px; 
            padding: 2.5rem; 
            box-shadow: var(--sombra-premium); 
            margin-bottom: 2rem; 
        }
        
        .form-group { display: flex; flex-direction: column; margin-bottom: 1.25rem; }
        .form-group label { font-size: 0.85rem; font-weight: 500; color: var(--texto-mutado); margin-bottom: 0.5rem; }
        
        .form-control { 
            padding: 0.75rem 1rem; 
            border: 1px solid var(--border-color); 
            border-radius: 12px; 
            font-size: 0.95rem; 
            outline: none; 
            color: var(--texto-puro);
            transition: border-color 0.2s;
        }
        .form-control:focus { border-color: var(--primary-premium); }
        
        .btn-premium-orange { 
            background: var(--primary-premium); 
            color: #fff; 
            border: none; 
            padding: 0.85rem 2rem; 
            border-radius: 12px; 
            font-weight: 500; 
            cursor: pointer; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            transition: background-color 0.2s;
        }
        .btn-premium-orange:hover { background-color: #c2410c; }
        
        .table-premium { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left; }
        .table-premium th { color: var(--texto-mutado); font-weight: 600; padding-bottom: 1rem; border-bottom: 2px solid var(--border-color); }
        .table-premium td { padding: 1.25rem 0; border-bottom: 1px solid #f8fafc; }
        
        .badge-premium { 
            padding: 0.35rem 0.85rem; 
            border-radius: 8px; 
            font-size: 0.8rem; 
            font-weight: 500; 
            background: #fff7ed; 
            color: #c2410c; 
            border: 1px solid #ffedd5;
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="card-premium">
            <h2 style="margin:0 0 4px 0; font-size: 1.4rem; font-weight:600;">Cadastro de Modalidades</h2>
            <p style="margin:0 0 2rem 0; color: var(--texto-mutado); font-size: 0.9rem;">Configure os tipos de contratação do escritório (Ex: PME, Adesão, PF).</p>
            
            <form action="" method="POST">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 1.25rem; align-items: flex-end;">
                    <div class="form-group" style="margin:0;">
                        <label>Nome da Modalidade *</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: PME (De 02 a 29 vidas), PF Individual, Cooperados" required>
                    </div>
                    <button type="submit" name="salvar_modalidade" class="btn-premium-orange">
                        <i class="fa-solid fa-plus"></i> Cadastrar Tipo
                    </button>
                </div>
            </form>
        </div>

        <div class="card-premium">
            <h3 style="margin:0 0 1.5rem 0; font-size: 1.1rem; font-weight:600;">Modalidades Disponíveis nos Filtros</h3>
            <div style="overflow-x: auto;">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Descrição / Nome da Regra</th>
                            <th style="text-align: right; padding-right: 1rem;">Identificador Visual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($modalidades) > 0): ?>
                            <?php foreach ($modalidades as $mod): ?>
                                <tr>
                                    <td style="color: var(--texto-mutado);">#<?= $mod['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($mod['nome']) ?></strong></td>
                                    <td style="text-align: right; padding-right: 1rem;">
                                        <span class="badge-premium"><i class="fa-solid fa-tags" style="font-size: 0.75rem; margin-right: 4px;"></i> Contrato Ativo</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--texto-mutado); padding: 2.5rem;">Nenhuma modalidade localizada no banco de dados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (!empty($mensagem_swal)) echo $mensagem_swal; ?>
        });
    </script>
</body>
</html>