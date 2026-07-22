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
// 1. PROCESSAMENTO: CADASTRO DE PLANO DE SAÚDE + DEPENDENTES DINÂMICOS (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_saude'])) {
    $cliente_id        = $_POST['cliente_id'] ?? '';
    $corretor_id       = $_POST['corretor_id'] ?? '';
    $grade_comissao_id = $_POST['grade_comissao_id'] ?? ''; // Nova chave estrangeira recebida
    $plano             = $_POST['plano'] ?? '';
    $valor_titular     = $_POST['valor_titular'] ?? '0';
    $tem_dependentes   = $_POST['tem_dependentes'] ?? 'Não';
    
    // Arrays dos dependentes injetados via JS dinâmico
    $dep_nomes        = $_POST['dep_nome'] ?? [];
    $dep_cpfs         = $_POST['dep_cpf'] ?? [];
    $dep_nascs        = $_POST['dep_nasc'] ?? [];
    $dep_parentescos = $_POST['dep_parentesco'] ?? [];
    $dep_valores      = $_POST['dep_valor'] ?? [];

    // Limpeza da moeda para float nativo
    $valor_titular = str_replace(['.', ','], ['', '.'], $valor_titular);

    if (!empty($cliente_id) && !empty($corretor_id) && !empty($grade_comissao_id) && !empty($plano)) {
        try {
            $pdo->beginTransaction();

            // Inserção do contrato titular na tabela 'plano_saude' com grade de comissão
            $stmtSaude = $pdo->prepare("
                INSERT INTO plano_saude (cliente_id, corretor_id, grade_comissao_id, plano, valor_titular, tem_dependentes) 
                VALUES (:cliente_id, :corretor_id, :grade_comissao_id, :plano, :valor_titular, :tem_dep)
            ");
            $stmtSaude->execute([
                'cliente_id'        => $cliente_id,
                'corretor_id'       => $corretor_id,
                'grade_comissao_id' => $grade_comissao_id, // Vinculando o ID relacional da comissão
                'plano'             => $plano,
                'valor_titular'     => (float)$valor_titular,
                'tem_dep'           => $tem_dependentes
            ]);
            
            $saude_id = $pdo->lastInsertId();

            // Inserção dos dependentes vinculados se houver ativação no formulário
            if ($tem_dependentes === 'Sim' && !empty($dep_nomes)) {
                $stmtDep = $pdo->prepare("
                    INSERT INTO dependentes (tipo_contrato, contrato_id, nome_completo, cpf, data_nascimento, grau_parentesco, valor_dependente) 
                    VALUES ('Saúde', :contrato_id, :nome, :cpf, :nasc, :parentesco, :valor)
                ");

                foreach ($dep_nomes as $index => $nome_dep) {
                    if (!empty($nome_dep)) {
                        $v_dep = str_replace(['.', ','], ['', '.'], $dep_valores[$index] ?? '0');
                        $stmtDep->execute([
                            'contrato_id' => $saude_id,
                            'nome'        => $nome_dep,
                            'cpf'         => !empty($dep_cpfs[$index]) ? $dep_cpfs[$index] : null,
                            'nasc'        => !empty($dep_nascs[$index]) ? $dep_nascs[$index] : null,
                            'parentesco'  => $dep_parentescos[$index] ?? 'Outros',
                            'valor'       => (float)$v_dep
                        ]);
                    }
                }
            }

            $pdo->commit();
            $mensagem_swal = ['status' => 'success', 'title' => 'Sucesso!', 'text' => 'Contrato de Plano de Saúde emitido com sucesso na base.'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log("Erro ao cadastrar plano de saude: " . $e->getMessage());
            $mensagem_swal = ['status' => 'error', 'title' => 'Erro', 'text' => 'Falha relacional técnica: ' . $e->getMessage()];
        }
    }
}

// ==============================================================================
// 2. CONSULTAS RELACIONAIS DA CARTEIRA DE SAÚDE
// ==============================================================================
try {
    $querySaude = "
        SELECT 
            s.*, 
            cli.nome_completo as nome_cliente,
            corr.nome_completo as nome_corretor,
            (SELECT COUNT(*) FROM dependentes d WHERE d.contrato_id = s.id AND d.tipo_contrato = 'Saúde') as qtd_dependentes
        FROM plano_saude s
        INNER JOIN clientes cli ON s.cliente_id = cli.id
        INNER JOIN corretores corr ON s.corretor_id = corr.id
        ORDER BY s.id DESC
    ";
    $saudesListadas = $pdo->query($querySaude)->fetchAll(PDO::FETCH_ASSOC);
    $totalPlanos = count($saudesListadas);

    $clientesSelect   = $pdo->query("SELECT id, nome_completo FROM clientes ORDER BY nome_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    $corretoresSelect = $pdo->query("SELECT id, nome_completo FROM corretores WHERE status = 'Ativo' ORDER BY nome_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Nova consulta para carregar as grades de comissão ativas
    $gradesSelect     = $pdo->query("SELECT id, nome_grade FROM grades_comissoes ORDER BY nome_grade ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $saudesListadas = []; $clientesSelect = []; $corretoresSelect = []; $gradesSelect = []; $totalPlanos = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planos de Saúde | Egis Saúde</title>
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
        .box-dependente { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1rem; }
        
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
            <h2 class="fw-bold text-dark mb-1">Carteira de Planos de Saúde</h2>
            <p class="text-muted mb-0">Controle contratos médicos, operadoras de saúde, vidas inclusas e faturamentos de carteira.</p>
        </div>
        <button class="btn btn-dark rounded-3 px-4 py-2 fw-500" data-bs-toggle="modal" data-bs-target="#modalNovoSaude" style="background:#ea580c; border:none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);">
            <i class="fa-solid fa-notes-medical me-2"></i> Emitir Plano de Saúde
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-premium py-3">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Contratos Médicos</span>
                <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalPlanos ?> Vínculos</h3>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="mb-4">
            <input type="text" class="form-control search-box w-25" id="filtroSaude" onkeyup="filtrarSaude()" placeholder="Buscar por titular ou operadora...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium" id="tabelaSaude">
                <thead>
                    <tr>
                        <th>BENEFICIÁRIO TITULAR</th>
                        <th>PRODUTO / OPERADORA</th>
                        <th>VALOR TITULAR</th>
                        <th>DEPENDENTES</th>
                        <th>CORRETOR PARCEIRO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($saudesListadas)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">Nenhum contrato médico ativo registrado nesta base.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($saudesListadas as $sau): ?>
                            <tr>
                                <td data-label="Beneficiário Titular" class="fw-bold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background: rgba(234, 88, 12, 0.05); color: #ea580c; font-size: 0.85rem; font-weight:700;">
                                            <i class="fa-solid fa-user-doctor"></i>
                                        </div>
                                        <?= htmlspecialchars($sau['nome_cliente']) ?>
                                    </div>
                                </td>
                                <td data-label="Produto / Operadora" class="text-dark fw-semibold text-uppercase font-monospace small">
                                    <?= htmlspecialchars($sau['plano']) ?>
                                </td>
                                <td data-label="Valor Titular" class="fw-bold text-dark">
                                    R$ <?= number_format($sau['valor_titular'], 2, ',', '.') ?>
                                </td>
                                <td data-label="Dependentes">
                                    <?php if ($sau['tem_dependentes'] === 'Sim'): ?>
                                        <span class="badge rounded-pill px-2.5 py-1.5 fw-medium" style="background: rgba(234, 88, 12, 0.08); color:#ea580c; font-size:0.72rem;">
                                            <i class="fa-solid fa-users me-1"></i> Familiar (<?= $sau['qtd_dependentes'] ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted rounded-pill px-2.5 py-1.5 fw-medium" style="font-size:0.72rem;">Individual</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Corretor" class="text-muted small fw-medium">
                                    <i class="fa-solid fa-user-tie me-1 opacity-50"></i> <?= htmlspecialchars($sau['nome_corretor']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNovoSaude" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background: #ffffff;">
            <div class="modal-header border-bottom border-light p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-heart-pulse text-orange-premium me-2" style="color:#ea580c;"></i> Lançar Contrato de Saúde</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="plano_saude.php" method="POST">
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    
                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">1. Informações de Venda & Grade</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Cliente Titular</label>
                            <select name="cliente_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Vincular proponente...</option>
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
                                <option value="" disabled selected>Selecione a Regra de Repasse Multi-Parcelas...</option>
                                <?php foreach ($gradesSelect as $grd): ?>
                                    <option value="<?= $grd['id'] ?>"><?= htmlspecialchars($grd['nome_grade']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Operadora & Nome do Plano</label>
                            <input type="text" name="plano" class="form-control rounded-3" placeholder="Ex: Amil S450, Bradesco Saúde Top Nacional, Unimed Alfa" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mensalidade Titular (R$)</label>
                            <input type="text" name="valor_titular" class="form-control rounded-3 text-end" placeholder="0,00" oninput="formatarMoeda(this)" required>
                        </div>
                    </div>

                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-2" style="font-size:0.75rem; font-weight:700;">2. Dependentes Adicionais (Inclusões)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Este contrato possui dependentes?</label>
                            <select name="tem_dependentes" id="selectDep" class="form-select rounded-3" onchange="togglePainelDependentes()">
                                <option value="Não">Não (Plano Individual)</option>
                                <option value="Sim">Sim (Plano Familiar / PME)</option>
                            </select>
                        </div>
                    </div>

                    <div id="wrapperDependentes" style="display: none;">
                        <div id="listaDependentes"></div>
                        <button type="button" class="btn btn-outline-dark btn-sm rounded-3 fw-semibold mt-1" onclick="adicionarLinhaDependente()">
                            <i class="fa-solid fa-plus me-1"></i> Inserir Próximo Dependente
                        </button>
                    </div>

                </div>
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2 text-muted" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_saude" class="btn btn-primary rounded-3 px-4 py-2" style="background:#ea580c; border:none;">Salvar Plano</button>
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

function togglePainelDependentes() {
    const status = document.getElementById('selectDep').value;
    const wrapper = document.getElementById('wrapperDependentes');
    if (status === 'Sim') {
        wrapper.style.display = 'block';
        if (document.getElementById('listaDependentes').children.length === 0) {
            adicionarLinhaDependente();
        }
    } else {
        wrapper.style.display = 'none';
    }
}

function adicionarLinhaDependente() {
    const container = document.getElementById('listaDependentes');
    const index = container.children.length;
    
    const div = document.createElement('div');
    div.className = 'box-dependente';
    div.id = 'dep_linha_' + index;
    
    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="badge bg-dark text-white font-monospace">Familiar Beneficiário #${index + 1}</span>
            <button type="button" class="btn-close" style="font-size:0.75rem;" onclick="removerLinhaDependente(${index})"></button>
        </div>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label small text-muted">Nome Completo</label>
                <input type="text" name="dep_nome[]" class="form-control form-control-sm rounded-2" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">CPF</label>
                <input type="text" name="dep_cpf[]" class="form-control form-control-sm rounded-2" placeholder="000.000.000-00" maxlength="14" oninput="maskCPF(this)">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted">Data Nascimento</label>
                <input type="date" name="dep_nasc[]" class="form-control form-control-sm rounded-2" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted">Grau de Parentesco</label>
                <input type="text" name="dep_parentesco[]" class="form-control form-control-sm rounded-2" placeholder="Ex: Filho(a), Cônjuge, Dependente Legal" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small text-muted">Custo Mensal do Dependente (R$)</label>
                <input type="text" name="dep_valor[]" class="form-control form-control-sm rounded-2 text-end" placeholder="0,00" oninput="formatarMoeda(this)" required>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function removerLinhaDependente(index) {
    const div = document.getElementById('dep_linha_' + index);
    if (div) div.remove();
}

function maskCPF(i) {
    let v = i.value.replace(/\D/g, "");
    if (v.length > 11) v = v.slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    i.value = v;
}

function filtrarSaude() {
    const filter = document.getElementById("filtroSaude").value.toUpperCase();
    const rows = document.getElementById("tabelaSaude").getElementsByTagName("tr");
    for (let i = 1; i < rows.length; i++) {
        let tdCliente = rows[i].getElementsByTagName("td")[0];
        let tdPlano   = rows[i].getElementsByTagName("td")[1];
        if (tdCliente || tdPlano) {
            let txtCliente = tdCliente.textContent || tdCliente.innerText;
            let txtPlano   = tdPlano.textContent || tdPlano.innerText;
            rows[i].style.display = (txtCliente.toUpperCase().indexOf(filter) > -1 || txtPlano.toUpperCase().indexOf(filter) > -1) ? "" : "none";
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