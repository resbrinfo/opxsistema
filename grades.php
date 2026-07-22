<?php
// Força a exibição de erros na tela para o ambiente local do WampServer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

// Bloqueia acesso de corretores comuns às configurações de comissão (Apenas master, diretoria e financeiro)
$nivel_usuario = $_SESSION['usuario_nivel'] ?? 'corretor';
if (!in_array($nivel_usuario, ['master', 'diretoria', 'financeiro'])) {
    echo "<script>alert('Acesso negado a este módulo.'); window.location.href = 'dashboard.php';</script>";
    exit;
}

$mensagem_swal = null;

// ==============================================================================
// 1. PROCESSAMENTO: CADASTRO DE GRADE DE COMISSÃO (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_grade'])) {
    $nome       = $_POST['nome'] ?? '';
    $percentual = $_POST['percentual'] ?? '';

    // Limpa e formata o percentual trocando vírgula por ponto se necessário
    $percentual = str_replace(',', '.', $percentual);

    if (!empty($nome) && $percentual !== '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO grades_comissao (nome, percentual) VALUES (:nome, :percentual)");
            $stmt->execute([
                'nome'       => $nome,
                'percentual' => (float)$percentual
            ]);
            $mensagem_swal = ['status' => 'success', 'title' => 'Sucesso!', 'text' => 'Grade de comissão cadastrada com sucesso.'];
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar grade: " . $e->getMessage());
            $mensagem_swal = ['status' => 'error', 'title' => 'Erro', 'text' => 'Falha ao salvar a regra no banco de dados.'];
        }
    }
}

// ==============================================================================
// 2. CONSULTA: LISTAGEM E MÉTRICAS DAS GRADES
// ==============================================================================
try {
    // Lista todas as comissões e faz uma contagem de quantos corretores usam cada uma atualmente
    $queryGrades = "
        SELECT g.*, COUNT(c.id) as total_corretores 
        FROM grades_comissao g
        LEFT JOIN corretores c ON c.grade_comissao_id = g.id
        GROUP BY g.id
        ORDER BY g.nome ASC
    ";
    $gradesComissao = $pdo->query($queryGrades)->fetchAll(PDO::FETCH_ASSOC);
    $totalGrades = count($gradesComissao);
} catch (PDOException $e) {
    error_log("Erro ao listar grades: " . $e->getMessage());
    $gradesComissao = [];
    $totalGrades = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades de Comissão | Egis Saúde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; }
        .main-content { padding: 3rem; min-height: 100vh; }
        .card-premium { background: #fff; border: 1px solid #f1f5f9; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.03); }
        .search-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.6rem 1rem; transition: all 0.3s ease; }
        .search-box:focus { border-color: #ea580c; box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1); outline: none; }
        .table-premium th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
        .table-premium td { padding: 1.2rem 0; border-bottom: 1px solid #f8fafc; }
        .form-label { font-weight: 500; color: #475569; font-size: 0.85rem; }
        
        @media (max-width: 1200px) {
            .main-content { padding: 5.5rem 1.25rem 2.5rem 1.25rem !important; }
        }
    </style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">Grades e Regras de Repasse</h2>
            <p class="text-muted mb-0">Configure os percentuais de comissionamento que serão aplicados aos contratos da sua força de vendas.</p>
        </div>
        <button class="btn btn-dark rounded-3 px-4 py-2 fw-500" data-bs-toggle="modal" data-bs-target="#modalNovaGrade" style="background:#ea580c; border:none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);">
            <i class="fa-solid fa-percent me-2"></i> Nova Regra de Repasse
        </button>
    </div>

    <!-- METRICA APP -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-premium py-3">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Grades Ativas</span>
                <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalGrades ?> Parâmetros</h3>
            </div>
        </div>
    </div>

    <!-- LISTAGEM OPERACIONAL -->
    <div class="card-premium">
        <div class="mb-4">
            <input type="text" class="form-control search-box w-25" id="filtroGrades" onkeyup="filtrarGrades()" placeholder="Buscar regra...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium" id="tabelaGrades">
                <thead>
                    <tr>
                        <th>DESCRIÇÃO DA REGRA</th>
                        <th>PERCENTUAL DE REPASSE</th>
                        <th>USUÁRIOS VINCULADOS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($gradesComissao)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-5">Nenhuma grade parametrizada no ecossistema.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gradesComissao as $g): ?>
                            <tr>
                                <td data-label="Descrição" class="fw-bold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background: rgba(234, 88, 12, 0.05); color: #ea580c;">
                                            <i class="fa-solid fa-sliders"></i>
                                        </div>
                                        <?= htmlspecialchars($g['nome']) ?>
                                    </div>
                                </td>
                                <td data-label="Percentual">
                                    <span class="badge rounded-pill px-3 py-2 fw-bold text-orange-premium font-monospace fs-6" style="background: rgba(234, 88, 12, 0.08); color:#ea580c;">
                                        <?= number_format($g['percentual'], 2, ',', '.') ?>%
                                    </span>
                                </td>
                                <td data-label="Corretores Associados" class="text-secondary fw-medium">
                                    <i class="fa-solid fa-user-tie me-1 opacity-50"></i> <?= $g['total_corretores'] ?> corretores utilizando
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL DE CADASTRO -->
<div class="modal fade" id="modalNovaGrade" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background: #ffffff;">
            <div class="modal-header border-bottom border-light p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-percentage text-orange-premium me-2" style="color:#ea580c;"></i> Parametrizar Grade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="grades.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Identificação / Nome da Regra</label>
                        <input type="text" name="nome" class="form-control rounded-3" placeholder="Ex: Grade Premium Ouro, Diretoria, Repasse Externo" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Percentual de Repasse (%)</label>
                        <div class="input-group">
                            <input type="text" name="percentual" class="form-control rounded-3" placeholder="Ex: 75.50" required style="border-top-right-radius: 0 !important; border-bottom-right-radius: 0 !important;">
                            <span class="input-group-text bg-light text-muted fw-bold rounded-3" style="border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important;">%</span>
                        </div>
                        <div class="form-text text-muted small mt-2">Use ponto ou vírgula para separar as frações centesimais da taxa.</div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2 text-muted" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_grade" class="btn btn-primary rounded-3 px-4 py-2" style="background:#ea580c; border:none;">Salvar Parâmetro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function filtrarGrades() {
    const filter = document.getElementById("filtroGrades").value.toUpperCase();
    const rows = document.getElementById("tabelaGrades").getElementsByTagName("tr");
    for (let i = 1; i < rows.length; i++) {
        let td = rows[i].getElementsByTagName("td")[0];
        if (td) {
            let txt = td.textContent || td.innerText;
            rows[i].style.display = txt.toUpperCase().indexOf(filter) > -1 ? "" : "none";
        }       
    }
}

document.addEventListener("DOMContentLoaded", function() {
    <?php if ($mensagem_swal): ?>
    Swal.fire({
        icon: '<?= $mensagem_swal['status'] ?>',
        title: '<?= $mensagem_swal['title'] ?>',
        text: '<?= $mensagem_swal['text'] ?>',
        customClass: { popup: 'rounded-4 p-4', confirmButton: 'btn btn-primary px-4 py-2 rounded-3 fw-500 bg-orange-premium border-0' },
        buttonsStyling: false
    });
    <?php endif; ?>
});
</script>
</body>
</html>