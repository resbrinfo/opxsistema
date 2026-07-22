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

$mensagem_swal = null;

// ==============================================================================
// 1. PROCESSAMENTO: CADASTRO DE CONSÓRCIO (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_consorcio'])) {
    $cliente_id        = $_POST['cliente_id'] ?? '';
    $corretor_id       = $_POST['corretor_id'] ?? '';
    $grade_comissao_id = $_POST['grade_comissao_id'] ?? ''; // Nova chave estrangeira recebida
    $administradora    = $_POST['administradora'] ?? '';
    $produto           = $_POST['produto'] ?? '';
    $grupo             = $_POST['grupo'] ?? '';
    $cota              = $_POST['cota'] ?? '';
    $credito           = $_POST['credito'] ?? '0';
    $prazo             = $_POST['prazo'] ?? '';
    $tipo_tabela       = $_POST['tipo_tabela'] ?? 'Normal';
    $lance             = $_POST['lance'] ?? '0';

    // Formata os valores numéricos limpando a formatação de moeda brasileira se enviada
    $credito = str_replace(['.', ','], ['', '.'], $credito);
    $lance   = str_replace(['.', ','], ['', '.'], $lance);

    if (!empty($cliente_id) && !empty($corretor_id) && !empty($grade_comissao_id) && !empty($administradora) && !empty($produto)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO consorcio (cliente_id, corretor_id, grade_comissao_id, administradora, produto, grupo, cota, credito, prazo, tipo_tabela, lance) 
                VALUES (:cliente_id, :corretor_id, :grade_comissao_id, :administradora, :produto, :grupo, :cota, :credito, :prazo, :tipo_tabela, :lance)
            ");
            $stmt->execute([
                'cliente_id'        => $cliente_id,
                'corretor_id'       => $corretor_id,
                'grade_comissao_id' => $grade_comissao_id, // Vinculando o ID relacional da comissão
                'administradora'    => $administradora,
                'produto'           => $produto,
                'grupo'             => $grupo,
                'cota'              => $cota,
                'credito'           => (float)$credito,
                'prazo'             => (int)$prazo,
                'tipo_tabela'       => $tipo_tabela,
                'lance'             => (float)$lance
            ]);
            $mensagem_swal = ['status' => 'success', 'title' => 'Sucesso!', 'text' => 'Consórcio lançado com sucesso na produção.'];
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar consorcio: " . $e->getMessage());
            $mensagem_swal = ['status' => 'error', 'title' => 'Erro', 'text' => 'Falha técnica ao salvar consórcio no banco de dados.'];
        }
    }
}

// ==============================================================================
// 2. CONSULTAS RELACIONAIS DA CARTEIRA
// ==============================================================================
try {
    // Listagem Completa de Consórcios cruzando os nomes legítimos de Clientes e Corretores
    $queryConsorcio = "
        SELECT 
            con.*, 
            cli.nome_completo as nome_cliente,
            corr.nome_completo as nome_corretor
        FROM consorcio con
        INNER JOIN clientes cli ON con.cliente_id = cli.id
        INNER JOIN corretores corr ON con.corretor_id = corr.id
        ORDER BY con.id DESC
    ";
    $consorciosListados = $pdo->query($queryConsorcio)->fetchAll(PDO::FETCH_ASSOC);
    $totalConsorcios = count($consorciosListados);

    // Métricas Financeiras Rápidas
    $totalVolumeCredito = $pdo->query("SELECT SUM(credito) as total FROM consorcio")->fetch()['total'] ?? 0.00;

    // População dos selects do modal para amarrar os relacionamentos íntegros
    $clientesSelect   = $pdo->query("SELECT id, nome_completo FROM clientes ORDER BY nome_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    $corretoresSelect = $pdo->query("SELECT id, nome_completo FROM corretores WHERE status = 'Ativo' ORDER BY nome_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Nova consulta para carregar as grades de comissão multi-parcelas
    $gradesSelect     = $pdo->query("SELECT id, nome_grade FROM grades_comissoes ORDER BY nome_grade ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro nas consultas de consorcio: " . $e->getMessage());
    $consorciosListados = []; $clientesSelect = []; $corretoresSelect = []; $gradesSelect = []; $totalConsorcios = 0; $totalVolumeCredito = 0.00;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produção de Consórcios | Egis Saúde</title>
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
            <h2 class="fw-bold text-dark mb-1">Carteira de Consórcios</h2>
            <p class="text-muted mb-0">Controle administradoras, grupos, cotas, lances estruturados e créditos comercializados.</p>
        </div>
        <button class="btn btn-dark rounded-3 px-4 py-2 fw-500" data-bs-toggle="modal" data-bs-target="#modalNovoConsorcio" style="background:#ea580c; border:none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);">
            <i class="fa-solid fa-file-invoice me-2"></i> Lançar Consórcio
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-premium py-3">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Cotas na Fila</span>
                <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalConsorcios ?> Emitidas</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-premium py-3">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Volume de Cartas</span>
                <h3 class="fw-bold text-dark mt-1 mb-0">R$ <?= number_format($totalVolumeCredito, 2, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="mb-4">
            <input type="text" class="form-control search-box w-25" id="filtroConsorcios" onkeyup="filtrarConsorcios()" placeholder="Filtrar por segurado ou administradora...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium" id="tabelaConsorcios">
                <thead>
                    <tr>
                        <th>CLIENTE / SEGURADO</th>
                        <th>ADMINISTRADORA / RAMO</th>
                        <th>GRUPO & COTA</th>
                        <th>CRÉDITO / PRAZO</th>
                        <th>LANCE PROPOSTO</th>
                        <th>CORRETOR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($consorciosListados)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Nenhum contrato de consórcio lançado nesta base de dados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consorciosListados as $con): ?>
                            <tr>
                                <td data-label="Cliente" class="fw-bold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background: rgba(234, 88, 12, 0.05); color: #ea580c; font-size: 0.85rem; font-weight:700;">
                                            <?= strtoupper(substr($con['nome_cliente'], 0, 2)) ?>
                                        </div>
                                        <?= htmlspecialchars($con['nome_cliente']) ?>
                                    </div>
                                </td>
                                <td data-label="Administradora / Ramo">
                                    <div class="text-dark fw-semibold text-uppercase font-monospace small"><?= htmlspecialchars($con['administradora']) ?></div>
                                    <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary mt-1" style="font-size:0.7rem;"><?= htmlspecialchars($con['produto']) ?></span>
                                </td>
                                <td data-label="Grupo & Cota" class="font-monospace text-secondary small">
                                    G: <?= htmlspecialchars($con['grupo']) ?> / C: <?= htmlspecialchars($con['cota']) ?>
                                </td>
                                <td data-label="Crédito / Prazo">
                                    <div class="fw-bold text-dark">R$ <?= number_format($con['credito'], 2, ',', '.') ?></div>
                                    <div class="text-muted small"><?= $con['prazo'] ?> Meses (<?= htmlspecialchars($con['tipo_tabela']) ?>)</div>
                                </td>
                                <td data-label="Lance Proposto" class="fw-medium text-dark">
                                    R$ <?= number_format($con['lance'], 2, ',', '.') ?>
                                </td>
                                <td data-label="Corretor" class="text-muted small fw-medium">
                                    <i class="fa-solid fa-user-tie me-1 opacity-50"></i> <?= htmlspecialchars($con['nome_corretor']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoConsorcio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background: #ffffff;">
            <div class="modal-header border-bottom border-light p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-layer-group text-orange-premium me-2" style="color:#ea580c;"></i> Lançamento de Consórcio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="consorcio.php" method="POST">
                <div class="modal-body p-4">
                    
                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">1. Relacionamentos & Regras de Repasse</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Cliente / Proponente</label>
                            <select name="cliente_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Vincular cliente...</option>
                                <?php foreach ($clientesSelect as $cli): ?>
                                    <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['nome_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Corretor Responsável</label>
                            <select name="corretor_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Vincular força de vendas...</option>
                                <?php foreach ($corretoresSelect as $corr): ?>
                                    <option value="<?= $corr['id'] ?>"><?= htmlspecialchars($corr['nome_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Grade de Comissão do Produto</label>
                            <select name="grade_comissao_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Selecione a Tabela de Repasse Multi-Parcelas...</option>
                                <?php foreach ($gradesSelect as $grd): ?>
                                    <option value="<?= $grd['id'] ?>"><?= htmlspecialchars($grd['nome_grade']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">2. Parâmetros da Cota contratada</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Administradora</label>
                            <input type="text" name="administradora" class="form-control rounded-3" placeholder="Ex: Porto Seguro, Embracon, Caixa" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Segmento / Produto</label>
                            <select name="produto" class="form-select rounded-3" required>
                                <option value="Imóveis">Imóveis</option>
                                <option value="Auto">Auto</option>
                                <option value="Moto">Moto</option>
                                <option value="Serviços">Serviços</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Grupo</label>
                            <input type="text" name="grupo" class="form-control rounded-3" placeholder="0001" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cota</label>
                            <input type="text" name="cota" class="form-control rounded-3" placeholder="024" required>
                        </div>
                    </div>

                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">3. Valores & Metodologia</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Valor do Crédito (R$)</label>
                            <input type="text" name="credito" class="form-control rounded-3 text-end" placeholder="0,00" oninput="formatarMoeda(this)" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Prazo (Meses)</label>
                            <input type="number" name="prazo" class="form-control rounded-3" placeholder="120" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Modelo de Tabela</label>
                            <select name="tipo_tabela" class="form-select rounded-3">
                                <option value="Normal">Tabela Normal</option>
                                <option value="Reduzida">Tabela Reduzida</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Proposta de Lance (R$)</label>
                            <input type="text" name="lance" class="form-control rounded-3 text-end" placeholder="0,00" oninput="formatarMoeda(this)">
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2 text-muted" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_consorcio" class="btn btn-primary rounded-3 px-4 py-2" style="background:#ea580c; border:none;">Lançar Apólice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Máscara dinâmica de moeda em tempo real para campos financeiros de aplicativos
function formatarMoeda(i) {
    let v = i.value.replace(/\D/g, "");
    v = (v / 100).toFixed(2) + "";
    v = v.replace(".", ",");
    v = v.replace(/(\d)(\d{3})(,\d{2})/g, "$1.$2$3");
    v = v.replace(/(\d)(\d{3})(\.\d{3})(,\d{2})/g, "$1.$2$3$4");
    i.value = v;
}

function filtrarConsorcios() {
    const filter = document.getElementById("filtroConsorcios").value.toUpperCase();
    const rows = document.getElementById("tabelaConsorcios").getElementsByTagName("tr");
    for (let i = 1; i < rows.length; i++) {
        let tdCliente = rows[i].getElementsByTagName("td")[0];
        let tdAdmin   = rows[i].getElementsByTagName("td")[1];
        if (tdCliente || tdAdmin) {
            let txtCliente = tdCliente.textContent || tdCliente.innerText;
            let txtAdmin   = tdAdmin.textContent || tdAdmin.innerText;
            rows[i].style.display = (txtCliente.toUpperCase().indexOf(filter) > -1 || txtAdmin.toUpperCase().indexOf(filter) > -1) ? "" : "none";
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