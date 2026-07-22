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
// 1. PROCESSAMENTO: EXCLUSÃO DE CLIENTE (GET)
// ==============================================================================
if (isset($_GET['excluir'])) {
    $id_excluir = (int)$_GET['excluir'];
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
        $stmt->execute(['id' => $id_excluir]);
        $mensagem_swal = ['status' => 'success', 'title' => 'Excluído!', 'text' => 'O registro do cliente foi removido com sucesso.'];
    } catch (PDOException $e) {
        error_log("Erro ao excluir cliente: " . $e->getMessage());
        $mensagem_swal = ['status' => 'error', 'title' => 'Erro', 'text' => 'Não é possível excluir um cliente vinculado a contratos ativos.'];
    }
}

// ==============================================================================
// 2. PROCESSAMENTO: CADASTRO OU ATUALIZAÇÃO DE CLIENTE (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_cliente'])) {
    $id             = $_POST['id'] ?? ''; // Se vier ID, é edição
    $nome           = $_POST['nome_completo'] ?? '';
    $cpf            = $_POST['cpf'] ?? null;
    $data_nasc      = $_POST['data_nascimento'] ?? null;
    $email          = $_POST['email'] ?? null;
    $telefone       = $_POST['telefone'] ?? '';
    $telefone_fixo  = $_POST['telefone_fixo'] ?? null;
    $endereco       = $_POST['endereco'] ?? null;
    $numero         = $_POST['numero'] ?? null;
    $bairro         = $_POST['bairro'] ?? null;
    $cep            = $_POST['cep'] ?? null;
    $cidade         = $_POST['cidade'] ?? '';
    $uf             = $_POST['uf'] ?? '';

    if (!empty($nome) && !empty($telefone) && !empty($cidade) && !empty($uf)) {
        try {
            if (empty($id)) {
                // AÇÃO: INSERIR NOVO (CORRIGIDO: de 'city' para 'cidade')
                $stmt = $pdo->prepare("
                    INSERT INTO clientes (nome_completo, cpf, data_nascimento, email, telefone, telefone_fixo, endereco, numero, bairro, cep, cidade, uf) 
                    VALUES (:nome, :cpf, :data_nasc, :email, :cel, :fixo, :end, :num, :bairro, :cep, :cid, :uf)
                ");
                $params = [
                    'nome'      => $nome,
                    'cpf'       => !empty($cpf) ? $cpf : null,
                    'data_nasc' => !empty($data_nasc) ? $data_nasc : null,
                    'email'     => !empty($email) ? $email : null,
                    'cel'       => $telefone,
                    'fixo'      => $telefone_fixo,
                    'end'       => $endereco,
                    'num'       => $numero,
                    'bairro'    => $bairro,
                    'cep'       => $cep,
                    'cid'       => $cidade,
                    'uf'        => $uf
                ];
                $stmt->execute($params);
                $mensagem_swal = ['status' => 'success', 'title' => 'Sucesso!', 'text' => 'Cliente cadastrado com sucesso.'];
            } else {
                // AÇÃO: ATUALIZAR EXISTENTE
                $stmt = $pdo->prepare("
                    UPDATE clientes SET 
                        nome_completo = :nome, cpf = :cpf, data_nascimento = :data_nasc, email = :email, 
                        telefone = :cel, telefone_fixo = :fixo, endereco = :end, numero = :num, 
                        bairro = :bairro, cep = :cep, cidade = :cid, uf = :uf 
                    WHERE id = :id
                ");
                $params = [
                    'nome'      => $nome,
                    'cpf'       => !empty($cpf) ? $cpf : null,
                    'data_nasc' => !empty($data_nasc) ? $data_nasc : null,
                    'email'     => !empty($email) ? $email : null,
                    'cel'       => $telefone,
                    'fixo'      => $telefone_fixo,
                    'end'       => $endereco,
                    'num'       => $numero,
                    'bairro'    => $bairro,
                    'cep'       => $cep,
                    'cid'       => $cidade,
                    'uf'        => $uf,
                    'id'        => (int)$id
                ];
                $stmt->execute($params);
                $mensagem_swal = ['status' => 'success', 'title' => 'Atualizado!', 'text' => 'Os dados do cliente foram salvos.'];
            }
        } catch (PDOException $e) {
            error_log("Erro ao salvar cliente: " . $e->getMessage());
            // Exibe o erro real do banco de dados na tela para fácil diagnóstico
            $mensagem_swal = [
                'status' => 'error', 
                'title' => 'Erro de Banco de Dados', 
                'text' => 'O MySQL rejeitou a operação: ' . $e->getMessage()
            ];
        }
    }
}

// ==============================================================================
// 3. CONSULTAS BASE DE DADOS
// ==============================================================================
try {
    $clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
    $totalClientes = count($clientes);
} catch (PDOException $e) {
    error_log("Erro ao listar clientes: " . $e->getMessage());
    $clientes = [];
    $totalClientes = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes | Egis Saúde</title>
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
        .btn-view-premium { background: #f1f5f9; color: #475569; }
        .btn-view-premium:hover { background: #ea580c; color: #fff; }
        .btn-edit-premium { background: #f1f5f9; color: #475569; }
        .btn-edit-premium:hover { background: #0f172a; color: #fff; }
        .btn-delete-premium { background: #fff1f2; color: #f43f5e; }
        .btn-delete-premium:hover { background: #f43f5e; color: #fff; }
        
        .hover-orange-link { color: #0f172a; transition: color 0.2s; text-decoration: none; }
        .hover-orange-link:hover { color: #ea580c !important; }
        
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
            <h2 class="fw-bold text-dark mb-1">Carteira de Clientes</h2>
            <p class="text-muted mb-0">Gerencie a base de proponentes e segurados ativos do sistema.</p>
        </div>
        <button class="btn btn-dark rounded-3 px-4 py-2 fw-500" onclick="abrirModalCadastro()" style="background:#ea580c; border:none; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);">
            <i class="fa-solid fa-user-plus me-2"></i> Cadastrar Cliente
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card-premium py-3">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Total de Leads / Clientes</span>
                <h3 class="fw-bold text-dark mt-1 mb-0"><?= $totalClientes ?> Registros</h3>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="mb-4">
            <input type="text" class="form-control search-box w-25" id="filtroClientes" onkeyup="filtrarClientes()" placeholder="Buscar por cliente...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium" id="tabelaClientes">
                <thead>
                    <tr>
                        <th>NOME DO CLIENTE / CPF</th>
                        <th>CONTATOS</th>
                        <th>LOCALIDADE</th>
                        <th>CADASTRO</th>
                        <th class="text-end">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">Nenhum cliente cadastrado nesta base.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $cli): ?>
                            <tr>
                                <td data-label="Nome Completo" class="fw-semibold text-dark">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; font-size: 0.85rem; background: rgba(234, 88, 12, 0.05); color: #ea580c;">
                                            <?= strtoupper(substr($cli['nome_completo'] ?? 'CL', 0, 2)) ?>
                                        </div>
                                        <div>
                                            <a href="perfil_cliente.php?id=<?= $cli['id'] ?>" class="hover-orange-link fw-bold">
                                                <?= htmlspecialchars($cli['nome_completo'] ?? '') ?> 
                                                <i class="fa-solid fa-arrow-up-right-from-square small opacity-50 ms-1" style="font-size:0.65rem;"></i>
                                            </a>
                                            <div class="text-muted font-monospace" style="font-size:0.7rem;">CPF: <?= htmlspecialchars($cli['cpf'] ?? 'Não informado') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Contatos">
                                    <div class="text-dark fw-medium"><?= htmlspecialchars($cli['telefone'] ?? 'Não informado') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($cli['email'] ?? 'Sem e-mail') ?></div>
                                </td>
                                <td data-label="Localidade">
                                    <div class="text-dark fw-semibold"><?= htmlspecialchars($cli['cidade'] ?? '') ?> - <?= htmlspecialchars($cli['uf'] ?? '') ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($cli['bairro'] ?? 'Sem Bairro') ?></div>
                                </td>
                                <td data-label="Cadastro" class="text-muted small font-monospace">
                                    <?= date('d/m/Y', strtotime($cli['criado_em'] ?? date('Y-m-d'))) ?>
                                </td>
                                <td data-label="Ações" class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="perfil_cliente.php?id=<?= $cli['id'] ?>" class="btn-action btn-view-premium" title="Ver Perfil 360º">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <button class="btn-action btn-edit-premium" title="Editar Cliente"
                                            data-id="<?= $cli['id'] ?>"
                                            data-nome="<?= htmlspecialchars($cli['nome_completo'] ?? '') ?>"
                                            data-cpf="<?= htmlspecialchars($cli['cpf'] ?? '') ?>"
                                            data-nasc="<?= htmlspecialchars($cli['data_nascimento'] ?? '') ?>"
                                            data-email="<?= htmlspecialchars($cli['email'] ?? '') ?>"
                                            data-tel="<?= htmlspecialchars($cli['telefone'] ?? '') ?>"
                                            data-fixo="<?= htmlspecialchars($cli['telefone_fixo'] ?? '') ?>"
                                            data-cep="<?= htmlspecialchars($cli['cep'] ?? '') ?>"
                                            data-end="<?= htmlspecialchars($cli['endereco'] ?? '') ?>"
                                            data-num="<?= htmlspecialchars($cli['numero'] ?? '') ?>"
                                            data-bairro="<?= htmlspecialchars($cli['bairro'] ?? '') ?>"
                                            data-cid="<?= htmlspecialchars($cli['cidade'] ?? '') ?>"
                                            data-uf="<?= htmlspecialchars($cli['uf'] ?? '') ?>"
                                            onclick="abrirModalEdicao(this)">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="btn-action btn-delete-premium" title="Excluir Cliente" onclick="confirmarExclusao(<?= $cli['id'] ?>, '<?= htmlspecialchars(addslashes($cli['nome_completo'] ?? '')) ?>')">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="background: #ffffff;">
            <div class="modal-header border-bottom border-light p-4">
                <h5 class="modal-title fw-bold text-dark" id="modalTitulo"><i class="fa-solid fa-user-tag text-orange-premium me-2" style="color:#ea580c;"></i> Novo Cliente Proponente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="clientes.php" method="POST">
                <input type="hidden" name="id" id="form_id">

                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    
                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">1. Informações Básicas & Contato</h6>
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
                            <label class="form-label">Data Nascimento</label>
                            <input type="date" name="data_nascimento" id="form_nasc" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" id="form_email" class="form-control rounded-3" placeholder="nome@provedor.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fone Celular</label>
                            <input type="text" name="telefone" id="form_tel" class="form-control rounded-3" placeholder="(81) 99999-9999" maxlength="15" oninput="maskTel(this)" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fone Fixo</label>
                            <input type="text" name="telefone_fixo" id="form_fixo" class="form-control rounded-3" placeholder="(81) 3333-3333" maxlength="14" oninput="maskTel(this)">
                        </div>
                    </div>

                    <h6 class="text-uppercase tracking-wider text-muted font-monospace mb-3" style="font-size:0.75rem; font-weight:700;">2. Endereço Completo</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" name="cep" id="cep" class="form-control rounded-3" placeholder="50000-000" maxlength="9" onblur="apiViaCEP()" oninput="maskCEP(this)">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Logradouro / Endereço</label>
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
                            <input type="text" name="cidade" id="cidade" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">UF</label>
                            <input type="text" name="uf" id="uf" class="form-control rounded-3" maxlength="2" required>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3 px-4 py-2 text-muted" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_cliente" class="btn btn-primary rounded-3 px-4 py-2" style="background:#ea580c; border:none;">Confirmar Operação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const modalBootstrap = new bootstrap.Modal(document.getElementById('modalCliente'));

function abrirModalCadastro() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-user-plus text-orange-premium me-2" style="color:#ea580c;"></i> Novo Cliente Proponente';
    document.getElementById('form_id').value = '';
    document.getElementById('form_nome').value = '';
    document.getElementById('form_cpf').value = '';
    document.getElementById('form_nasc').value = '';
    document.getElementById('form_email').value = '';
    document.getElementById('form_tel').value = '';
    document.getElementById('form_fixo').value = '';
    document.getElementById('cep').value = '';
    document.getElementById('endereco').value = '';
    document.getElementById('form_num').value = '';
    document.getElementById('bairro').value = '';
    document.getElementById('cidade').value = '';
    document.getElementById('uf').value = '';
    modalBootstrap.show();
}

function abrirModalEdicao(btn) {
    document.getElementById('modalTitulo').innerHTML = '<i class="fa-solid fa-user-gear text-orange-premium me-2" style="color:#ea580c;"></i> Editar Cadastro do Cliente';
    document.getElementById('form_id').value = btn.getAttribute('data-id');
    document.getElementById('form_nome').value = btn.getAttribute('data-nome');
    document.getElementById('form_cpf').value = btn.getAttribute('data-cpf');
    document.getElementById('form_nasc').value = btn.getAttribute('data-nasc');
    document.getElementById('form_email').value = btn.getAttribute('data-email');
    document.getElementById('form_tel').value = btn.getAttribute('data-tel');
    document.getElementById('form_fixo').value = btn.getAttribute('data-fixo');
    document.getElementById('cep').value = btn.getAttribute('data-cep');
    document.getElementById('endereco').value = btn.getAttribute('data-end');
    document.getElementById('form_num').value = btn.getAttribute('data-num');
    document.getElementById('bairro').value = btn.getAttribute('data-bairro');
    document.getElementById('cidade').value = btn.getAttribute('data-cid');
    document.getElementById('uf').value = btn.getAttribute('data-uf');
    modalBootstrap.show();
}

function confirmarExclusao(id, nome) {
    Swal.fire({
        title: 'Tem certeza?',
        text: `Você irá remover o cliente ${nome} permanentemente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-4 p-4',
            confirmButton: 'btn btn-danger px-4 py-2 rounded-3 fw-500 border-0 me-2',
            cancelButton: 'btn btn-light text-muted px-4 py-2 rounded-3'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `clientes.php?excluir=${id}`;
        }
    });
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

function filtrarClientes() {
    const filter = document.getElementById("filtroClientes").value.toUpperCase();
    const rows = document.getElementById("tabelaClientes").getElementsByTagName("tr");
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