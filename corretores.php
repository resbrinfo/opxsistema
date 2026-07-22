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

// Recupera a mensagem guardada na sessão (se houver) e limpa em seguida
$mensagem_swal = $_SESSION['mensagem_swal'] ?? null;
unset($_SESSION['mensagem_swal']);

// ==============================================================================
// 1. PROCESSAMENTO COMPLETO ADAPTADO À ESTRUTURA REAL DO SQL (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_corretor'])) {
    $id                = $_POST['id'] ?? ''; 
    $nome              = $_POST['nome_completo'] ?? '';
    $cpf               = $_POST['cpf'] ?? null;
    $email             = $_POST['email'] ?? null;
    $fone_celular      = $_POST['fone_celular'] ?? '';
    $fone_fixo         = $_POST['fone_fixo'] ?? null;
    $banco             = $_POST['banco'] ?? null;
    $agencia           = $_POST['agencia'] ?? null;
    $conta             = $_POST['conta'] ?? null;
    $pix               = $_POST['pix'] ?? null;
    $grade_comissao_id = !empty($_POST['grade_comissao_id']) ? (int)$_POST['grade_comissao_id'] : null;
    $status            = $_POST['status'] ?? 'Ativo';

    if (!empty($cpf)) { $cpf = preg_replace('/\D/', '', $cpf); }

    if (!empty($nome) && !empty($fone_celular)) {
        try {
            // Evita erro #1452 se a grade selecionada não existir na tabela grades_comissao
            if ($grade_comissao_id !== null) {
                $checkGrade = $pdo->prepare("SELECT id FROM grades_comissao WHERE id = :gid");
                $checkGrade->execute(['gid' => $grade_comissao_id]);
                if (!$checkGrade->fetch()) {
                    $grade_comissao_id = null;
                }
            }

            if (empty($id)) {
                // AÇÃO: INSERIR NOVO (Mapeado exatamente com as colunas reais do corretores.sql)
                $stmt = $pdo->prepare("
                    INSERT INTO corretores (
                        nome_completo, cpf, fone_celular, fone_fixo, email, 
                        banco, agencia, conta, pix, grade_comissao_id, status
                    ) VALUES (
                        :nome, :cpf, :cel, :fixo, :email, 
                        :banco, :agencia, :conta, :pix, :grade, :status
                    )
                ");
                $params = [
                    'nome'    => $nome,
                    'cpf'     => !empty($cpf) ? $cpf : null,
                    'cel'     => $fone_celular,
                    'fixo'    => !empty($fone_fixo) ? $fone_fixo : null,
                    'email'   => !empty($email) ? $email : null,
                    'banco'   => !empty($banco) ? $banco : null,
                    'agencia' => !empty($agencia) ? $agencia : null,
                    'conta'   => !empty($conta) ? $conta : null,
                    'pix'     => !empty($pix) ? $pix : null,
                    'grade'   => $grade_comissao_id,
                    'status'  => $status
                ];
                $stmt->execute($params);
                $_SESSION['mensagem_swal'] = ['status' => 'success', 'title' => 'Sucesso!', 'text' => 'Corretor cadastrado com sucesso.'];
            } else {
                // AÇÃO: ATUALIZAR EXISTENTE
                $stmt = $pdo->prepare("
                    UPDATE corretores SET 
                        nome_completo = :nome, cpf = :cpf, fone_celular = :cel, fone_fixo = :fixo, email = :email, 
                        banco = :banco, agencia = :agencia, conta = :conta, pix = :pix, 
                        grade_comissao_id = :grade, status = :status 
                    WHERE id = :id
                ");
                $params = [
                    'nome'    => $nome,
                    'cpf'     => !empty($cpf) ? $cpf : null,
                    'cel'     => $fone_celular,
                    'fixo'    => !empty($fone_fixo) ? $fone_fixo : null,
                    'email'   => !empty($email) ? $email : null,
                    'banco'   => !empty($banco) ? $banco : null,
                    'agencia' => !empty($agencia) ? $agencia : null,
                    'conta'   => !empty($conta) ? $conta : null,
                    'pix'     => !empty($pix) ? $pix : null,
                    'grade'   => $grade_comissao_id,
                    'status'  => $status,
                    'id'      => (int)$id
                ];
                $stmt->execute($params);
                $_SESSION['mensagem_swal'] = ['status' => 'success', 'title' => 'Atualizado!', 'text' => 'Os dados do corretor foram salvos.'];
            }
        } catch (PDOException $e) {
            error_log("Erro ao salvar corretor: " . $e->getMessage());
            $_SESSION['mensagem_swal'] = [
                'status' => 'error', 
                'title' => 'Erro de Banco de Dados', 
                'text' => 'O MySQL rejeitou a gravação. Detalhes: ' . $e->getMessage()
            ];
        }
        
        header("Location: corretores.php");
        exit;
    } else {
        $_SESSION['mensagem_swal'] = ['status' => 'error', 'title' => 'Atenção', 'text' => 'Nome completo e Celular são obrigatórios.'];
        header("Location: corretores.php");
        exit;
    }
}

// ==============================================================================
// 2. CONSULTAS BASE DE DADOS
// ==============================================================================
try {
    $queryCorr = "
        SELECT c.*, g.nome as nome_grade, g.percentual 
        FROM corretores c
        LEFT JOIN grades_comissao g ON c.grade_comissao_id = g.id
        ORDER BY c.nome_completo ASC
    ";
    $corretores = $pdo->query($queryCorr)->fetchAll(PDO::FETCH_ASSOC);
    $totalCorretores = count($corretores);

    $gradesSelect = $pdo->query("SELECT id, nome, percentual FROM grades_comissao ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $corretores = []; $gradesSelect = []; $totalCorretores = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipe de Corretores | Egis Saúde</title>
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
        .btn-action { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; transition: all 0.2s; border: none; text-decoration: none; }
        .btn-edit-premium { background: #f1f5f9; color: #475569; }
        .btn-edit-premium:hover { background: #ea580c; color: #fff; }
        @media (max-width: 1200px) { .main-content { padding: 5.5rem 1.25rem 2.5rem 1.25rem !important; } }
    </style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">Gestão de Parceiros & Corretores</h2>
            <p class="text-muted mb-0">Cadastre a força de vendas, gerencie dados bancários e defina as regras de comissionamento.</p>
        </div>
        <button class="btn btn-dark rounded-3 px-4 py-2 fw-500" onclick="abrirModalCadastro()" style="background:#ea580c; border:none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);">
            <i class="fa-solid fa-user-plus me-2"></i> Adicionar Corretor
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-premium py-3">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Corretores Ativos</span>
                <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalCorretores ?> Integrantes</h3>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="mb-4">
            <input type="text" class="form-control search-box w-25" id="filtroCorretores" onkeyup="filtrarCorretores()" placeholder="Buscar parceiro...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium" id="tabelaCorretores">
                <thead>
                    <tr>
                        <th>NOME DO CORRETOR</th>
                        <th>CONTATOS</th>
                        <th>REPASSE / GRADE</th>
                        <th>CHAVE PIX</th>
                        <th>STATUS</th>
                        <th class="text-end">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($corretores)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Nenhum corretor parceiro registrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($corretores as $corr): ?>
                            <tr>
                                <td data-label="Nome Completo" class="fw-semibold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; font-size: 0.85rem; background: rgba(234, 88, 12, 0.05); color: #ea580c;">
                                            <?= strtoupper(substr($corr['nome_completo'] ?? 'CO', 0, 2)) ?>
                                        </div>
                                        <div>
                                            <?= htmlspecialchars($corr['nome_completo']) ?>
                                            <div class="text-muted font-monospace" style="font-size:0.7rem;">CPF: <?= htmlspecialchars($corr['cpf'] ?? 'Não informado') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Contatos">
                                    <div class="text-dark fw-medium"><?= htmlspecialchars($corr['fone_celular']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($corr['email'] ?? 'Sem e-mail') ?></div>
                                </td>
                                <td data-label="Grade / Comissão">
                                    <div class="text-dark fw-bold"><?= htmlspecialchars($corr['nome_grade'] ?? 'Sem Grade') ?></div>
                                    <div class="text-muted small"><?= !empty($corr['percentual']) ? $corr['percentual'].'% de repasse' : '0.00%' ?></div>
                                </td>
                                <td data-label="Chave PIX" class="font-monospace text-secondary small">
                                    <?= htmlspecialchars($corr['pix'] ?? 'Não informada') ?>
                                </td>
                                <td data-label="Status">
                                    <span class="badge rounded-pill px-3 py-1.5 fw-medium <?= $corr['status'] === 'Ativo' ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' ?>" style="font-size: 0.75rem;">
                                        <?= $corr['status'] ?>
                                    </span>
                                </td>
                                <td data-label="Ações" class="text-end">
                                    <button class="btn-action btn-edit-premium" title="Editar Corretor"
                                        data-id="<?= $corr['id'] ?>"
                                        data-nome="<?= htmlspecialchars($corr['nome_completo'] ?? '') ?>"
                                        data-cpf="<?= htmlspecialchars($corr['cpf'] ?? '') ?>"
                                        data-email="<?= htmlspecialchars($corr['email'] ?? '') ?>"
                                        data-cel="<?= htmlspecialchars($corr['fone_celular'] ?? '') ?>"
                                        data-fixo="<?= htmlspecialchars($corr['fone_fixo'] ?? '') ?>"
                                        data-banco="<?= htmlspecialchars($corr['banco'] ?? '') ?>"
                                        data-agencia="<?= htmlspecialchars($corr['agencia'] ?? '') ?>"
                                        data-conta="<?= htmlspecialchars($corr['conta'] ?? '') ?>"
                                        data-pix="<?= htmlspecialchars($corr['pix'] ?? '') ?>"
                                        data-grade="<?= htmlspecialchars($corr['grade_comissao_id'] ?? '') ?>"
                                        data-status="<?= htmlspecialchars($corr['status'] ?? 'Ativo') ?>"
                                        onclick="abrirModalEdicao(this)">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCorretor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background: #ffffff;">
            <form action="corretores.php" method="POST">
                <div class="modal-header border-bottom border-light p-4">
                    <h5 class="modal-title fw-bold text-dark" id="modalTitulo"><i class="fa-solid fa-user-shield text-orange-premium me-2" style="color:#ea580c;"></i> Novo Integrante de Vendas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <input type="hidden" name="id" id="form_id">
                
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    
                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">1. Informações Pessoais & Contato</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome_completo" id="form_nome" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CPF</label>
                            <input type="text" name="cpf" id="form_cpf" class="form-control rounded-3" placeholder="000.000.000-00" maxlength="14" oninput="maskCPF(this)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data Nascimento <span class="text-muted" style="font-size:10px;">(Opcional)</span></label>
                            <input type="date" name="data_nascimento" id="form_nasc" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">E-mail Comercial</label>
                            <input type="email" name="email" id="form_email" class="form-control rounded-3" placeholder="nome@egissaude.com.br">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Celular / WhatsApp</label>
                            <input type="text" name="fone_celular" id="form_cel" class="form-control rounded-3" placeholder="(81) 99999-9999" maxlength="15" oninput="maskTel(this)" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefone Fixo</label>
                            <input type="text" name="fone_fixo" id="form_fixo" class="form-control rounded-3" placeholder="(81) 3333-3333" maxlength="14" oninput="maskTel(this)">
                        </div>
                    </div>

                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">2. Localização Residencial <span class="text-muted" style="font-size:10px;">(Visualização em tela)</span></h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" name="cep" id="cep" class="form-control rounded-3" placeholder="50000-000" maxlength="9" onblur="apiViaCEP()" oninput="maskCEP(this)">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Endereço</label>
                            <input type="text" name="endereco" id="endereco" class="form-control rounded-3">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Número</label>
                            <input type="text" name="numero" id="form_num" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bairro</label>
                            <input type="text" name="bairro" id="bairro" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cidade</label>
                            <input type="text" name="cidade" id="cidade" class="form-control rounded-3">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">UF</label>
                            <input type="text" name="uf" id="uf" class="form-control rounded-3" maxlength="2">
                        </div>
                    </div>

                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">3. Dados Bancários, Pix & Regras de Repasse</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Instituição Bancária</label>
                            <input type="text" name="banco" id="form_banco" class="form-control rounded-3" placeholder="Ex: Itaú, Nubank, Bradesco">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Agência</label>
                            <input type="text" name="agencia" id="form_agencia" class="form-control rounded-3" placeholder="Ex: 0001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Conta Corrente / Poupança</label>
                            <input type="text" name="conta" id="form_conta" class="form-control rounded-3" placeholder="Ex: 12345-6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Chave PIX Oficial</label>
                            <input type="text" name="pix" id="form_pix" class="form-control rounded-3" placeholder="Chave Pix">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Grade de Comissão</label>
                            <select name="grade_comissao_id" id="form_grade" class="form-select rounded-3">
                                <option value="">Sem Grade (Repasse Padrão)</option>
                                <?php foreach ($gradesSelect as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nome']) ?> (<?= $g['percentual'] ?>%)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="form_status" class="form-select rounded-3">
                                <option value="Ativo">Ativo</option>
                                <option value="Inativo">Inativo</option>
                            </select>
                        </div>
                    </div>

                </div>
                
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2 text-muted" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_corretor" class="btn btn-primary rounded-3 px-4 py-2" style="background:#ea580c; border:none;">Salvar Cadastro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const modalBootstrap = new bootstrap.Modal(document.getElementById('modalCorretor'));

function abrirModalCadastro() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-user-shield text-orange-premium me-2" style="color:#ea580c;"></i> Novo Integrante de Vendas';
    document.getElementById('form_id').value = '';
    document.getElementById('form_nome').value = '';
    document.getElementById('form_cpf').value = '';
    document.getElementById('form_nasc').value = '';
    document.getElementById('form_email').value = '';
    document.getElementById('form_cel').value = '';
    document.getElementById('form_fixo').value = '';
    document.getElementById('cep').value = '';
    document.getElementById('endereco').value = '';
    document.getElementById('form_num').value = '';
    document.getElementById('bairro').value = '';
    document.getElementById('cidade').value = '';
    document.getElementById('uf').value = '';
    document.getElementById('form_banco').value = '';
    document.getElementById('form_agencia').value = '';
    document.getElementById('form_conta').value = '';
    document.getElementById('form_pix').value = '';
    document.getElementById('form_grade').value = '';
    document.getElementById('form_status').value = 'Ativo';
    modalBootstrap.show();
}

function abrirModalEdicao(btn) {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-user-gear text-orange-premium me-2" style="color:#ea580c;"></i> Editar Cadastro do Corretor';
    document.getElementById('form_id').value = btn.getAttribute('data-id');
    document.getElementById('form_nome').value = btn.getAttribute('data-nome');
    document.getElementById('form_cpf').value = btn.getAttribute('data-cpf');
    document.getElementById('form_nasc').value = btn.getAttribute('data-nasc') || '';
    document.getElementById('form_email').value = btn.getAttribute('data-email');
    document.getElementById('form_cel').value = btn.getAttribute('data-cel');
    document.getElementById('form_fixo').value = btn.getAttribute('data-fixo');
    document.getElementById('cep').value = btn.getAttribute('data-cep') || '';
    document.getElementById('endereco').value = btn.getAttribute('data-end') || '';
    document.getElementById('form_num').value = btn.getAttribute('data-num') || '';
    document.getElementById('bairro').value = btn.getAttribute('data-bairro') || '';
    document.getElementById('cidade').value = btn.getAttribute('data-cid') || '';
    document.getElementById('uf').value = btn.getAttribute('data-uf') || '';
    document.getElementById('form_banco').value = btn.getAttribute('data-banco');
    document.getElementById('form_agencia').value = btn.getAttribute('data-agencia');
    document.getElementById('form_conta').value = btn.getAttribute('data-conta');
    document.getElementById('form_pix').value = btn.getAttribute('data-pix');
    document.getElementById('form_grade').value = btn.getAttribute('data-grade');
    document.getElementById('form_status').value = btn.getAttribute('data-status');
    modalBootstrap.show();
}

function maskCPF(i) {
    let v = i.value.replace(/\D/g, "");
    if (v.length > 11) v = v.slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    i.value = v;
}

function maskTel(i) {
    let v = i.value.replace(/\D/g, "");
    if (v.length > 11) v = v.slice(0, 11);
    v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
    v = v.length > 13 ? v.replace(/(\d{5})(\d{4})$/, "$1-$2") : v.replace(/(\d{4})(\d{4})$/, "$1-$2");
    i.value = v;
}

function maskCEP(i) {
    let v = i.value.replace(/\D/g, "");
    if (v.length > 8) v = v.slice(0, 8);
    v = v.replace(/^(\d{5})(\d)/, "$1-$2");
    i.value = v;
}

async function apiViaCEP() {
    const cep = document.getElementById('cep').value.replace(/\D/g, '');
    if (cep.length !== 8) return; 
    try {
        const r = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const d = await r.json();
        if (!d.erro) {
            document.getElementById('endereco').value = d.logradouro;
            document.getElementById('bairro').value = d.bairro;
            document.getElementById('cidade').value = d.localidade;
            document.getElementById('uf').value = d.uf;
        }
    } catch (e) { console.error(e); }
}

function filtrarCorretores() {
    const filter = document.getElementById("filtroCorretores").value.toUpperCase();
    const rows = document.getElementById("tabelaCorretores").getElementsByTagName("tr");
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