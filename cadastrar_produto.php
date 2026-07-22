<?php
// Força a exibição de erros na tela para o ambiente local do WampServer
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

require_once __DIR__ . '/config/conexao.php';

$mensagem_swal = null;

// ==============================================================================
// 1. PROCESSAMENTO: CADASTRO DE NOVO PRODUTO / CARTEIRA (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_produto'])) {
    $nome_comercial = $_POST['nome_comercial'] ?? '';
    $operadora      = $_POST['operadora_seguradora'] ?? '';
    $categoria_id   = $_POST['categoria_id'] ?? '';
    $status         = $_POST['status'] ?? 'Ativo';

    if (!empty($nome_comercial) && !empty($operadora) && !empty($categoria_id)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO produtos (categoria_id, nome_comercial, operadora_seguradora, status) 
                VALUES (:categoria_id, :nome, :operadora, :status)
            ");
            $stmt->execute([
                'categoria_id' => $categoria_id,
                'nome'         => $nome_comercial,
                'operadora'    => $operadora,
                'status'       => $status
            ]);
            $mensagem_swal = ['status' => 'success', 'title' => 'Sucesso!', 'text' => 'Produto cadastrado com sucesso na base.'];
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar produto: " . $e->getMessage());
            $mensagem_swal = ['status' => 'error', 'title' => 'Erro', 'text' => 'Falha ao salvar o produto no banco.'];
        }
    }
}

// ==============================================================================
// 2. CONSULTAS: LISTAGEM RELACIONAL DE PRODUTOS E CATEGORIAS
// ==============================================================================
try {
    // Busca os produtos trazendo os dados reais e os nomes legíveis das categorias vinculadas
    $queryProdutos = "
        SELECT p.*, c.nome as nome_categoria, c.slug as categoria_slug 
        FROM produtos p
        INNER JOIN categorias_produtos c ON p.categoria_id = c.id
        ORDER BY c.nome ASC, p.nome_comercial ASC
    ";
    $produtosListados = $pdo->query($queryProdutos)->fetchAll(PDO::FETCH_ASSOC);
    $totalProdutos = count($produtosListados);

    // Busca as categorias de produtos para preencher o <select> do modal
    $categoriasSelect = $pdo->query("SELECT id, nome FROM categorias_produtos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao recuperar dados dos produtos: " . $e->getMessage());
    $produtosListados = [];
    $categoriasSelect = [];
    $totalProdutos = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carteira de Produtos | Egis Saúde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; }
        .sidebar { width: 280px; height: 100vh; position: fixed; top: 0; left: 0; background: #fff; border-right: 1px solid #f1f5f9; padding: 2.5rem 1.75rem; display: flex; flex-direction: column; justify-content: space-between; z-index: 100; }
        .main-content { margin-left: 280px; padding: 3.5rem; min-height: 100vh; }
        .card-premium { background: #fff; border: 1px solid #f1f5f9; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.03); }
        .search-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.6rem 1rem; transition: all 0.3s ease; }
        .search-box:focus { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); outline: none; }
        
        .table-premium th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
        .table-premium td { padding: 1.2rem 0; border-bottom: 1px solid #f8fafc; }
        .form-label { font-weight: 500; color: #475569; font-size: 0.85rem; }

        /* Badges Temáticos Minimalistas */
        .badge-saude { background: rgba(37, 99, 235, 0.06); color: #2563eb; }
        .badge-odontologico { background: rgba(13, 148, 136, 0.06); color: #0d9488; }
        .badge-automoveis { background: rgba(79, 70, 229, 0.06); color: #4f46e5; }
        .badge-consorcios { background: rgba(16, 185, 129, 0.06); color: #10b981; }
        .badge-empresarial { background: rgba(100, 116, 139, 0.06); color: #64748b; }
    </style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">Carteira de Produtos e Ramos</h2>
            <p class="text-muted mb-0">Consulte os produtos comerciais e apólices pré-configuradas no sistema.</p>
        </div>
        <button class="btn btn-primary rounded-3 px-4 py-2 fw-500" data-bs-toggle="modal" data-bs-target="#modalNovoProduto" style="background:#2563eb; border:none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">
            <i class="fa-solid fa-plus me-2"></i> Cadastrar Produto
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-premium py-3">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Produtos Ativos</span>
                <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalProdutos ?> Configurados</h3>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="mb-4">
            <input type="text" class="form-control search-box w-25" id="filtroProdutos" onkeyup="filtrarProdutos()" placeholder="Filtrar por plano ou companhia...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium" id="tabelaProdutos">
                <thead>
                    <tr>
                        <th>NOME COMERCIAL DO PRODUTO</th>
                        <th>OPERADORA / SEGURADORA</th>
                        <th>RAMO / CATEGORIA</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produtosListados)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">Nenhum produto cadastrado no banco de dados. Use o botão acima para inserir.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produtosListados as $prod): ?>
                            <tr>
                                <td class="fw-bold text-dark" style="font-size: 0.95rem;">
                                    <?= htmlspecialchars($prod['nome_comercial']) ?>
                                </td>
                                <td class="font-monospace text-secondary text-uppercase fw-semibold">
                                    <?= htmlspecialchars($prod['operadora_seguradora']) ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?= htmlspecialchars($prod['categoria_slug'] ?? 'saude') ?> rounded-pill px-3 py-1.5 fw-medium" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($prod['nome_categoria']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-1.5 fw-medium <?= $prod['status'] === 'Ativo' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>" style="font-size: 0.75rem;">
                                        <?= $prod['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoProduto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background: #ffffff;">
            <div class="modal-header border-bottom border-light p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-cubes text-primary me-2"></i> Adicionar Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="produtos.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nome Comercial do Produto</label>
                        <input type="text" name="nome_comercial" class="form-control rounded-3" placeholder="Ex: Amil S60, Top Nacional, Bradesco Auto" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Companhia / Seguradora</label>
                        <input type="text" name="operadora_seguradora" class="form-control rounded-3" placeholder="Ex: AMIL, SULAMERICA, BRADESCO, AZUL" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Ramo Comercial</label>
                            <select name="categoria_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Escolha o ramo...</option>
                                <?php foreach ($categoriasSelect as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="Ativo">Ativo</option>
                                <option value="Inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2 text-muted" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_produto" class="btn btn-primary rounded-3 px-4 py-2" style="background:#2563eb; border:none;">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Filtro instantâneo local da listagem de produtos
function filtrarProdutos() {
    const filter = document.getElementById("filtroProdutos").value.toUpperCase();
    const rows = document.getElementById("tabelaProdutos").getElementsByTagName("tr");
    for (let i = 1; i < rows.length; i++) {
        let tdNome = rows[i].getElementsByTagName("td")[0];
        let tdOperadora = rows[i].getElementsByTagName("td")[1];
        if (tdNome || tdOperadora) {
            let txtNome = tdNome.textContent || tdNome.innerText;
            let txtOperadora = tdOperadora.textContent || tdOperadora.innerText;
            rows[i].style.display = (txtNome.toUpperCase().indexOf(filter) > -1 || txtOperadora.toUpperCase().indexOf(filter) > -1) ? "" : "none";
        }       
    }
}

document.addEventListener("DOMContentLoaded", function() {
    <?php if ($mensagem_swal): ?>
    Swal.fire({
        icon: '<?= $mensagem_swal['status'] ?>',
        title: '<?= $mensagem_swal['title'] ?>',
        text: '<?= $mensagem_swal['text'] ?>',
        customClass: { popup: 'rounded-4 p-4', confirmButton: 'btn btn-primary px-4 py-2 rounded-3 fw-500' },
        buttonsStyling: false
    });
    <?php endif; ?>
});
</script>
</body>
</html>