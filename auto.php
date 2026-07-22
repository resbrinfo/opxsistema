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
// 1. PROCESSAMENTO: CADASTRO DE APÓLICE DE AUTO (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_auto'])) {
    $cliente_id        = $_POST['cliente_id'] ?? '';
    $corretor_id       = $_POST['corretor_id'] ?? '';
    $grade_comissao_id = $_POST['grade_comissao_id'] ?? ''; // Nova chave estrangeira recebida
    $seguradora        = $_POST['seguradora'] ?? '';
    $num_apolice       = $_POST['num_apolice'] ?? '';
    $vigencia_inicio   = $_POST['vigencia_inicio'] ?? '';
    $vigencia_fim      = $_POST['vigencia_fim'] ?? '';
    $placa             = $_POST['placa'] ?? '';
    $chassi            = $_POST['chassi'] ?? '';
    $classe_bonus      = $_POST['classe_bonus'] ?? '0';
    $parcelas          = $_POST['parcelas'] ?? '1';
    $forma_pagamento   = $_POST['forma_pagamento'] ?? 'Boleto';
    $assistencia_24h   = $_POST['assistencia_24h'] ?? 'Sim';
    $franquia_tipo     = $_POST['franquia_tipo'] ?? 'Normal';
    $valor_franquia    = $_POST['valor_franquia'] ?? '0';
    $valor_terceiros   = $_POST['valor_terceiros'] ?? '0';
    $premio_total      = $_POST['premio_total'] ?? '0';
    $premio_liquido    = $_POST['premio_liquido'] ?? '0';

    // Formatação de valores (Limpando o padrão brasileiro R$ para float puro do MySQL)
    $valor_franquia  = str_replace(['.', ','], ['', '.'], $valor_franquia);
    $valor_terceiros = str_replace(['.', ','], ['', '.'], $valor_terceiros);
    $premio_total    = str_replace(['.', ','], ['', '.'], $premio_total);
    $premio_liquido  = str_replace(['.', ','], ['', '.'], $premio_liquido);

    if (!empty($cliente_id) && !empty($corretor_id) && !empty($grade_comissao_id) && !empty($seguradora) && !empty($num_apolice)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO auto (cliente_id, corretor_id, grade_comissao_id, seguradora, num_apolice, vigencia_inicio, vigencia_fim, placa, chassi, classe_bonus, parcelas, forma_pagamento, assistencia_24h, franquia_tipo, valor_franquia, valor_terceiros, premio_total, premio_liquido) 
                VALUES (:cliente_id, :corretor_id, :grade_comissao_id, :seguradora, :num_apolice, :v_ini, :v_fim, :placa, :chassi, :bonus, :parcelas, :forma_pag, :ass_24h, :fran_tipo, :v_fran, :v_terc, :p_total, :p_liq)
            ");
            $stmt->execute([
                'cliente_id'        => $cliente_id,
                'corretor_id'       => $corretor_id,
                'grade_comissao_id' => $grade_comissao_id, // Vinculando o ID relacional
                'seguradora'        => $seguradora,
                'num_apolice'       => $num_apolice,
                'v_ini'             => $vigencia_inicio,
                'v_fim'             => $vigencia_fim,
                'placa'             => strtoupper($placa),
                'chassi'            => strtoupper($chassi),
                'bonus'             => (int)$classe_bonus,
                'parcelas'          => (int)$parcelas,
                'forma_pag'         => $forma_pagamento,
                'ass_24h'           => $assistencia_24h,
                'fran_tipo'         => $franquia_tipo,
                'v_fran'            => (float)$valor_franquia,
                'v_terc'            => (float)$valor_terceiros,
                'p_total'           => (float)$premio_total,
                'p_liq'             => (float)$premio_liquido
            ]);
            $mensagem_swal = ['status' => 'success', 'title' => 'Sucesso!', 'text' => 'Apólice de Automóvel emitida com sucesso.'];
        } catch (PDOException $e) {
            error_log("Erro ao cadastrar seguro auto: " . $e->getMessage());
            $mensagem_swal = ['status' => 'error', 'title' => 'Erro', 'text' => 'Falha técnica ao salvar apólice auto no banco de dados.'];
        }
    }
}

// ==============================================================================
// 2. CONSULTAS RELACIONAIS
// ==============================================================================
try {
    $queryAuto = "
        SELECT 
            a.*, 
            cli.nome_completo as nome_cliente,
            corr.nome_completo as nome_corretor
        FROM auto a
        INNER JOIN clientes cli ON a.cliente_id = cli.id
        INNER JOIN corretores corr ON a.corretor_id = corr.id
        ORDER BY a.id DESC
    ";
    $autosListados = $pdo->query($queryAuto)->fetchAll(PDO::FETCH_ASSOC);
    $totalSeguros = count($autosListados);

    $clientesSelect   = $pdo->query("SELECT id, nome_completo FROM clientes ORDER BY nome_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    $corretoresSelect = $pdo->query("SELECT id, nome_completo FROM corretores WHERE status = 'Ativo' ORDER BY nome_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Nova consulta para carregar as grades de comissão ativas
    $gradesSelect     = $pdo->query("SELECT id, nome_grade FROM grades_comissoes ORDER BY nome_grade ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $autosListados = []; $clientesSelect = []; $corretoresSelect = []; $gradesSelect = []; $totalSeguros = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguros de Automóvel | Egis Saúde</title>
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
            <h2 class="fw-bold text-dark mb-1">Ramo Automóvel (RE)</h2>
            <p class="text-muted mb-0">Controle frotas, apólices individuais, prêmios líquidos, classes de bônus e coberturas RCF-V.</p>
        </div>
        <button class="btn btn-dark rounded-3 px-4 py-2 fw-500" data-bs-toggle="modal" data-bs-target="#modalNovoAuto" style="background:#ea580c; border:none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);">
            <i class="fa-solid fa-car-burst me-2"></i> Emitir Apólice Auto
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-premium py-3">
                <span class="text-uppercase text-muted font-monospace small font-weight-bold">Veículos Cobertos</span>
                <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalSeguros ?> Apólices</h3>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="mb-4">
            <input type="text" class="form-control search-box w-25" id="filtroAuto" onkeyup="filtrarAuto()" placeholder="Buscar por cliente ou apólice...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium" id="tabelaAuto">
                <thead>
                    <tr>
                        <th>SEGURADO</th>
                        <th>COMPANHIA / APÓLICE</th>
                        <th>VEÍCULO / PLACA</th>
                        <th>VIGÊNCIA</th>
                        <th>PRÊMIO TOTAL</th>
                        <th>CORRETOR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($autosListados)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Nenhum seguro de automóvel registrado na base.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($autosListados as $aut): ?>
                            <tr>
                                <td data-label="Segurado" class="fw-bold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background: rgba(234, 88, 12, 0.05); color: #ea580c; font-size: 0.85rem; font-weight:700;">
                                            <i class="fa-solid fa-car"></i>
                                        </div>
                                        <?= htmlspecialchars($aut['nome_cliente']) ?>
                                    </div>
                                </td>
                                <td data-label="Companhia / Apólice">
                                    <div class="text-dark fw-semibold text-uppercase font-monospace small"><?= htmlspecialchars($aut['seguradora']) ?></div>
                                    <div class="text-muted small">Nº <?= htmlspecialchars($aut['num_apolice']) ?></div>
                                </td>
                                <td data-label="Veículo / Placa">
                                    <span class="badge bg-dark text-white font-monospace px-2.5 py-1.5 rounded" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                                        <?= htmlspecialchars($aut['placa']) ?>
                                    </span>
                                    <div class="text-muted small mt-1 font-monospace" style="font-size: 0.7rem;">C. Bônus: <?= $aut['classe_bonus'] ?></div>
                                </td>
                                <td data-label="Vigência" class="small fw-medium text-secondary">
                                    <?= date('d/m/Y', strtotime($aut['vigencia_inicio'])) ?> até<br>
                                    <?= date('d/m/Y', strtotime($aut['vigencia_fim'])) ?>
                                </td>
                                <td data-label="Prêmio Total">
                                    <div class="fw-bold text-dark">R$ <?= number_format($aut['premio_total'], 2, ',', '.') ?></div>
                                    <div class="text-muted small font-monospace" style="font-size:0.7rem;"><?= $aut['parcelas'] ?>x no <?= htmlspecialchars($aut['forma_pagamento']) ?></div>
                                </td>
                                <td data-label="Corretor" class="text-muted small fw-medium">
                                    <i class="fa-solid fa-user-tie me-1 opacity-50"></i> <?= htmlspecialchars($aut['nome_corretor']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoAuto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background: #ffffff;">
            <div class="modal-header border-bottom border-light p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-car-rear text-orange-premium me-2" style="color:#ea580c;"></i> Lançar Apólice Auto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="auto.php" method="POST">
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    
                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">1. Relacionamentos, Vigências & Grade</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Segurado / Titular</label>
                            <select name="cliente_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Vincular cliente...</option>
                                <?php foreach ($clientesSelect as $cli): ?>
                                    <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['nome_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Corretor Operacional</label>
                            <select name="corretor_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Vincular parceiro...</option>
                                <?php foreach ($corretoresSelect as $corr): ?>
                                    <option value="<?= $corr['id'] ?>"><?= htmlspecialchars($corr['nome_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Grade de Comissão do Produto</label>
                            <select name="grade_comissao_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Selecione a Regra de Repasse Multi-Parcelas...</option>
                                <?php foreach ($gradesSelect as $grd): ?>
                                    <option value="<?= $grd['id'] ?>"><?= htmlspecialchars($grd['nome_grade']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Seguradora Parceira</label>
                            <input type="text" name="seguradora" class="form-control rounded-3" placeholder="Ex: PORTO, AZUL, TOKIO" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nº de Apólice</label>
                            <input type="text" name="num_apolice" class="form-control rounded-3" placeholder="Ex: 10023948293" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Início Vigência</label>
                            <input type="date" name="vigencia_inicio" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Fim Vigência</label>
                            <input type="date" name="vigencia_fim" class="form-control rounded-3" required>
                        </div>
                    </div>

                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">2. Dados do Bem Coberto</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Placa do Veículo</label>
                            <input type="text" name="placa" class="form-control rounded-3 text-uppercase" placeholder="ABC1D23" maxlength="7" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Numeração do Chassi</label>
                            <input type="text" name="chassi" class="form-control rounded-3 text-uppercase" placeholder="Insira a numeração completa estruturada" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Classe Bônus</label>
                            <input type="number" name="classe_bonus" class="form-control rounded-3" min="0" max="10" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Assistência 24h</label>
                            <select name="assistencia_24h" class="form-select rounded-3">
                                <option value="Sim">Sim</option>
                                <option value="Não">Não</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">3. Parâmetros de Custos & Cobrança</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Modelo de Franquia</label>
                            <select name="franquia_tipo" class="form-select rounded-3">
                                <option value="Normal">Normal</option>
                                <option value="Reduzida">Reduzida</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Valor Franquia (R$)</label>
                            <input type="text" name="valor_franquia" class="form-control rounded-3 text-end" placeholder="0,00" oninput="formatarMoeda(this)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">RCF-V Terceiros (R$)</label>
                            <input type="text" name="valor_terceiros" class="form-control rounded-3 text-end" placeholder="0,00" oninput="formatarMoeda(this)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="form-select rounded-3">
                                <option value="Boleto">Boleto Bancário</option>
                                <option value="Cartão">Cartão de Crédito</option>
                                <option value="Débito">Débito Automático</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Divisão Parcelas</label>
                            <input type="number" name="parcelas" class="form-control rounded-3" min="1" max="12" value="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prêmio Total Comercial (R$)</label>
                            <input type="text" name="premio_total" class="form-control rounded-3 text-end" placeholder="0,00" oninput="formatarMoeda(this)" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Prêmio Líquido de Cálculo (R$)</label>
                            <input type="text" name="premio_liquido" class="form-control rounded-3 text-end" placeholder="0,00" oninput="formatarMoeda(this)" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2 text-muted" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_auto" class="btn btn-primary rounded-3 px-4 py-2" style="background:#ea580c; border:none;">Salvar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function formatarMoeda(i) {
    let v = i.value.replace(/\D/g, "");
    v = (v / 100).toFixed(2) + "";
    v = v.replace(".", ",");
    v = v.replace(/(\d)(\d{3})(,\d{2})/g, "$1.$2$3");
    v = v.replace(/(\d)(\d{3})(\.\d{3})(,\d{2})/g, "$1.$2$3$4");
    i.value = v;
}

function filtrarAuto() {
    const filter = document.getElementById("filtroAuto").value.toUpperCase();
    const rows = document.getElementById("tabelaAuto").getElementsByTagName("tr");
    for (let i = 1; i < rows.length; i++) {
        let tdCliente = rows[i].getElementsByTagName("td")[0];
        let tdApolice = rows[i].getElementsByTagName("td")[1];
        if (tdCliente || tdApolice) {
            let txtCliente = tdCliente.textContent || tdCliente.innerText;
            let txtApolice = tdApolice.textContent || tdApolice.innerText;
            rows[i].style.display = (txtCliente.toUpperCase().indexOf(filter) > -1 || txtApolice.toUpperCase().indexOf(filter) > -1) ? "" : "none";
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