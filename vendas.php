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
// 1. PROCESSAMENTO: REGISTRAR NOVA VENDA (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_venda'])) {
    $cliente_id   = $_POST['cliente_id'] ?? '';
    $produto_id   = $_POST['produto_id'] ?? '';
    $corretor_id  = $_POST['corretor_id'] ?? '';
    $num_doc      = $_POST['numero_documento'] ?? '';
    $tipo_mov     = $_POST['tipo_movimento'] ?? 'APOL NOVA';
    $premio       = $_POST['premio_liquido'] ?? 0.00;
    $comissao     = $_POST['comissao_esperada'] ?? 0.00;
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    $categoria_slug = $_POST['categoria_slug_hidden'] ?? '';

    if (!empty($cliente_id) && !empty($produto_id) && !empty($corretor_id) && !empty($num_doc)) {
        try {
            $pdo->beginTransaction();
            
            // Grava na Tabela Pai (Geral) - Agora incluindo o corretor_id relacional
            $stmtPai = $pdo->prepare("
                INSERT INTO vendas_apolices (cliente_id, produto_id, corretor_id, numero_documento, tipo_movimento, premio_liquido, comissao_esperada, data_emissao) 
                VALUES (:cid, :pid, :corretor_id, :doc, :mov, :premio, :com, :emissao)
            ");
            $stmtPai->execute([
                'cid' => $cliente_id, 
                'pid' => $produto_id, 
                'corretor_id' => $corretor_id,
                'doc' => $num_doc, 
                'mov' => $tipo_mov,
                'premio' => $premio, 
                'com' => $comissao, 
                'emissao' => $data_emissao
            ]);
            
            $venda_id = $pdo->lastInsertId();

            if ($categoria_slug === 'saude' || $categoria_slug === 'odontologico') {
                $stmtFilha = $pdo->prepare("INSERT INTO venda_detalhes_saude (venda_id, idade_titular, quantidade_vidas, odonto_incluso) VALUES (:venda_id, :idade, :vidas, :odonto)");
                $stmtFilha->execute(['venda_id' => $venda_id, 'idade' => $_POST['idade_titular'] ?? 0, 'vidas' => $_POST['quantidade_vidas'] ?? 1, 'odonto' => $_POST['odonto_incluso'] ?? 'Não']);
            } elseif ($categoria_slug === 'automoveis') {
                $stmtFilha = $pdo->prepare("INSERT INTO venda_detalhes_auto (venda_id, placa_veiculo, chassi_veiculo, classe_bonus) VALUES (:venda_id, :placa, :chassi, :bonus)");
                $stmtFilha->execute(['venda_id' => $venda_id, 'placa' => $_POST['placa_veiculo'] ?? '', 'chassi' => $_POST['chassi_veiculo'] ?? null, 'bonus' => $_POST['classe_bonus'] ?? 0]);
            } elseif ($categoria_slug === 'consorcios') {
                $stmtFilha = $pdo->prepare("INSERT INTO venda_detalhes_consorcio (venda_id, numero_grupo, numero_cota, valor_credito) VALUES (:venda_id, :grupo, :cota, :credito)");
                $stmtFilha->execute(['venda_id' => $venda_id, 'grupo' => $_POST['numero_grupo'] ?? '', 'cota' => $_POST['numero_cota'] ?? '', 'credito' => $_POST['valor_credito'] ?? 0.00]);
            }

            $pdo->commit();
            $mensagem_swal = ['status' => 'success', 'title' => 'Venda Processada!', 'text' => 'Apólice vinculada ao corretor e registrada com sucesso.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_swal = ['status' => 'error', 'title' => 'Falha no Lançamento', 'text' => $e->getMessage()];
        }
    }
}

// ==============================================================================
// 2. PROCESSAMENTO: ATUALIZAR/EDITAR VENDA EXISTENTE (POST)
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_venda'])) {
    $venda_id     = $_POST['edit_venda_id'] ?? '';
    $corretor_id  = $_POST['corretor_id'] ?? '';
    $num_doc      = $_POST['numero_documento'] ?? '';
    $tipo_mov     = $_POST['tipo_movimento'] ?? 'APOL NOVA';
    $premio       = $_POST['premio_liquido'] ?? 0.00;
    $comissao     = $_POST['comissao_esperada'] ?? 0.00;
    $data_emissao = $_POST['data_emissao'] ?? date('Y-m-d');
    $categoria_slug = $_POST['edit_categoria_slug'] ?? '';

    if (!empty($venda_id) && !empty($num_doc) && !empty($corretor_id)) {
        try {
            $pdo->beginTransaction();

            // Atualiza os dados gerais (Tabela Pai) incluindo a nova chave do corretor
            $stmtUpdatePai = $pdo->prepare("
                UPDATE vendas_apolices 
                SET numero_documento = :doc, tipo_movimento = :mov, premio_liquido = :premio, 
                    comissao_esperada = :com, data_emissao = :emissao, corretor_id = :corretor_id 
                WHERE id = :id
            ");
            $stmtUpdatePai->execute([
                'doc' => $num_doc, 'mov' => $tipo_mov, 'premio' => $premio, 'com' => $comissao,
                'emissao' => $data_emissao, 'corretor_id' => $corretor_id, 'id' => $venda_id
            ]);

            // Atualiza dados específicos (Tabela Filha) baseado na categoria
            if ($categoria_slug === 'saude' || $categoria_slug === 'odontologico') {
                $stmtUpdateFilha = $pdo->prepare("UPDATE venda_detalhes_saude SET idade_titular = :idade, quantidade_vidas = :vidas, odonto_incluso = :odonto WHERE venda_id = :venda_id");
                $stmtUpdateFilha->execute(['idade' => $_POST['idade_titular'] ?? 0, 'vidas' => $_POST['quantidade_vidas'] ?? 1, 'odonto' => $_POST['odonto_incluso'] ?? 'Não', 'venda_id' => $venda_id]);
            } elseif ($categoria_slug === 'automoveis') {
                $stmtUpdateFilha = $pdo->prepare("UPDATE venda_detalhes_auto SET placa_veiculo = :placa, chassi_veiculo = :chassi, classe_bonus = :bonus WHERE venda_id = :venda_id");
                $stmtUpdateFilha->execute(['placa' => $_POST['placa_veiculo'] ?? '', 'chassi' => $_POST['chassi_veiculo'] ?? null, 'bonus' => $_POST['classe_bonus'] ?? 0, 'venda_id' => $venda_id]);
            } elseif ($categoria_slug === 'consorcios') {
                $stmtUpdateFilha = $pdo->prepare("UPDATE venda_detalhes_consorcio SET numero_grupo = :grupo, numero_cota = :cota, valor_credito = :credito WHERE venda_id = :venda_id");
                $stmtUpdateFilha->execute(['grupo' => $_POST['numero_grupo'] ?? '', 'cota' => $_POST['numero_cota'] ?? '', 'credito' => $_POST['valor_credito'] ?? 0.00, 'venda_id' => $venda_id]);
            }

            $pdo->commit();
            $mensagem_swal = ['status' => 'success', 'title' => 'Atualizado!', 'text' => 'Os dados da apólice foram alterados com sucesso.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_swal = ['status' => 'error', 'title' => 'Erro ao Atualizar', 'text' => $e->getMessage()];
        }
    }
}

// ==============================================================================
// 3. PROCESSAMENTO: EXCLUIR VENDA (GET)
// ==============================================================================
if (isset($_GET['excluir_id'])) {
    $id_excluir = (int)$_GET['excluir_id'];
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM vendas_apolices WHERE id = :id");
        $stmtDelete->execute(['id' => $id_excluir]);
        header("Location: vendas.php?sucesso_excluir=1");
        exit;
    } catch (PDOException $e) {
        $mensagem_swal = ['status' => 'error', 'title' => 'Erro ao Deletar', 'text' => 'Não foi possível remover este faturamento.'];
    }
}

if (isset($_GET['sucesso_excluir'])) {
    $mensagem_swal = ['status' => 'success', 'title' => 'Apagado!', 'text' => 'O registro da apólice foi removido permanentemente da base.'];
}

// ==============================================================================
// 4. CONSULTAS DE ALIMENTAÇÃO DA TELA (INNER JOIN COM CORRETORES)
// ==============================================================================
try {
    $queryVendas = "
        SELECT 
            v.id, v.cliente_id, v.produto_id, v.corretor_id, v.numero_documento, v.tipo_movimento, v.premio_liquido, v.comissao_esperada, v.data_emissao,
            DATE_FORMAT(v.data_emissao, '%d/%m/%Y') as data_emissao_formatada,
            c.nome_completo as cliente_nome, p.nome_comercial as produto_nome, p.operadora_seguradora,
            cat.nome as categoria_nome, cat.slug as categoria_slug, corr.nome_completo as corretor_nome,
            ds.idade_titular, ds.quantidade_vidas, ds.odonto_incluso,
            da.placa_veiculo, da.chassi_veiculo, da.classe_bonus, dc.numero_grupo, dc.numero_cota, dc.valor_credito
        FROM vendas_apolices v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN produtos p ON v.produto_id = p.id
        INNER JOIN categorias_produtos cat ON p.categoria_id = cat.id
        INNER JOIN corretores corr ON v.corretor_id = corr.id
        LEFT JOIN venda_detalhes_saude ds ON v.id = ds.venda_id
        LEFT JOIN venda_detalhes_auto da ON v.id = da.venda_id
        LEFT JOIN venda_detalhes_consorcio dc ON v.id = dc.venda_id
        ORDER BY v.data_emissao DESC, v.id DESC
    ";
    $vendas = $pdo->query($queryVendas)->fetchAll();

    $kpis = $pdo->query("SELECT COUNT(*) as qtd, SUM(premio_liquido) as faturamento, SUM(comissao_esperada) as comissao FROM vendas_apolices")->fetch();
    $listaClientes = $pdo->query("SELECT id, nome_completo FROM clientes ORDER BY nome_completo ASC")->fetchAll();
    
    // Busca os corretores ativos
    $listaCorretores = $pdo->query("SELECT id, nome_completo, comissao_padrao FROM corretores WHERE status = 'Ativo' ORDER BY nome_completo ASC")->fetchAll();
    
    $listaProdutos = $pdo->query("SELECT p.id, p.nome_comercial, p.operadora_seguradora, cat.slug as categoria_slug FROM produtos p INNER JOIN categorias_produtos cat ON p.categoria_id = cat.id WHERE p.status = 'Ativo' ORDER BY p.nome_comercial ASC")->fetchAll();
} catch (PDOException $e) {
    $vendas = []; $kpis = ['qtd' => 0, 'faturamento' => 0, 'comissao' => 0]; $listaCorretores = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Produção | Egis Saúde</title>
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
        .table-premium th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
        .table-premium td { padding: 1.2rem 0; border-bottom: 1px solid #f8fafc; }
        .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #f1f5f9; background: #fff; color: #64748b; transition: all 0.2s; cursor: pointer; }
        .btn-action:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
        .btn-delete:hover { background: #fef2f2; color: #ef4444; border-color: #fca5a5; }
        .badge-saude { background: rgba(37, 99, 235, 0.06); color: #2563eb; }
        .badge-automoveis { background: rgba(79, 70, 229, 0.06); color: #4f46e5; }
        .badge-consorcios { background: rgba(16, 185, 129, 0.06); color: #10b981; }
    </style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">Painel de Produção Comercial</h2>
            <p class="text-muted mb-0">Controle, auditoria e fechamento integrado à carteira relacional de corretores.</p>
        </div>
        <button class="btn btn-primary rounded-3 px-4 py-2 fw-500" data-bs-toggle="modal" data-bs-target="#modalNovaVenda" style="background:#2563eb; border:none; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);" onclick="document.getElementById('produtoSelect').selectedIndex=0; alternarCamposPorCategoria();">
            <i class="fa-solid fa-file-signature me-2"></i> Lançar Produção
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4">
            <div class="card-premium">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Contratos Ativos</span>
                <h2 class="fw-bold text-dark mt-2 mb-0"><?= (int)$kpis['qtd'] ?> Emitidos</h2>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card-premium">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Volume Líquido</span>
                <h2 class="fw-bold text-dark mt-2 mb-0">R$ <?= number_format($kpis['faturamento'] ?? 0, 2, ',', '.') ?></h2>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card-premium">
                <span class="text-uppercase text-muted font-monospace small fw-bold">Comissões Estimadas</span>
                <h2 class="fw-bold text-primary mt-2 mb-0">R$ <?= number_format($kpis['comissao'] ?? 0, 2, ',', '.') ?></h2>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="mb-4">
            <input type="text" class="form-control search-box w-25" id="filtroProducao" onkeyup="filtrarProducao()" placeholder="Filtrar apólice...">
        </div>

        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium" id="tabelaProducao">
                <thead>
                    <tr>
                        <th>DOCUMENTO</th>
                        <th>SEGURADO / DETALHES</th>
                        <th>PRODUTO / OPERADORA</th>
                        <th>CORRETOR / CONSULTOR</th>
                        <th>PRÊMIO LÍQUIDO</th>
                        <th>REPASSE</th>
                        <th class="text-end">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendas)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Nenhum faturamento registrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendas as $venda): ?>
                            <tr>
                                <td class="font-monospace fw-bold text-secondary"><?= htmlspecialchars($venda['numero_documento']) ?></td>
                                <td class="fw-semibold text-dark">
                                    <?= htmlspecialchars($venda['cliente_nome']) ?>
                                    <div class="text-muted font-normal small mt-1" style="font-weight:400; font-size:0.75rem;">
                                        <?php if ($venda['categoria_slug'] === 'saude' || $venda['categoria_slug'] === 'odontologico'): ?>
                                            Idade: <?= $venda['idade_titular'] ?> | Vidas: <?= $venda['quantidade_vidas'] ?> | Odonto: <?= $venda['odonto_incluso'] ?>
                                        <?php elseif ($venda['categoria_slug'] === 'automoveis'): ?>
                                            Placa: <?= htmlspecialchars($venda['placa_veiculo']) ?> | Bônus: C<?= $venda['classe_bonus'] ?>
                                        <?php elseif ($venda['categoria_slug'] === 'consorcios'): ?>
                                            Grupo: <?= htmlspecialchars($venda['numero_grupo']) ?> | Cota: <?= htmlspecialchars($venda['numero_cota']) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $venda['categoria_slug'] ?> rounded-pill px-3 py-1 fw-medium mb-1" style="font-size:0.7rem;"><?= htmlspecialchars($venda['categoria_nome']) ?></span>
                                    <div class="text-dark small fw-medium"><?= htmlspecialchars($venda['produto_nome']) ?></div>
                                </td>
                                <td class="text-secondary small fw-medium"><i class="fa-solid fa-user-tie me-1 opacity-50"></i> <?= htmlspecialchars($venda['corretor_nome']) ?></td>
                                <td class="fw-semibold">R$ <?= number_format($venda['premio_liquido'], 2, ',', '.') ?></td>
                                <td class="fw-bold text-primary">R$ <?= number_format($venda['comissao_esperada'], 2, ',', '.') ?></td>
                                <td class="text-end">
                                    <button class="btn-action" title="Editar" onclick='abrirEdicao(<?= json_encode($venda, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Excluir" onclick="confirmarExclusao(<?= $venda['id'] ?>, '<?= $venda['numero_documento'] ?>')">
                                        <i class="fa-solid fa-trash-can"></i>
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

<div class="modal fade" id="modalNovaVenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> Lançar Nova Venda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="vendas.php" method="POST">
                <input type="hidden" name="categoria_slug_hidden" id="categoria_slug_hidden" value="">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Cliente Segurado</label>
                            <select name="cliente_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Escolha o titular...</option>
                                <?php foreach ($listaClientes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Produto / Ramo</label>
                            <select name="produto_id" id="produtoSelect" class="form-select rounded-3" required onchange="alternarCamposPorCategoria()">
                                <option value="" disabled selected>Escolha o produto...</option>
                                <?php foreach ($listaProdutos as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-categoria="<?= $p['categoria_slug'] ?>"><?= htmlspecialchars($p['nome_comercial']) ?> (<?= htmlspecialchars($p['operadora_seguradora']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="bloco_saude" class="card bg-light border-0 p-3 mb-4 d-none">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Idade Titular</label><input type="number" name="idade_titular" class="form-control bg-white"></div>
                            <div class="col-md-4"><label class="form-label">Qtd Vidas</label><input type="number" name="quantidade_vidas" class="form-control bg-white" value="1"></div>
                            <div class="col-md-4"><label class="form-label">Odonto Incluso?</label><select name="odonto_incluso" class="form-select bg-white"><option value="Não">Não</option><option value="Sim">Sim</option></select></div>
                        </div>
                    </div>
                    <div id="bloco_automoveis" class="card bg-light border-0 p-3 mb-4 d-none">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Placa do Carro</label><input type="text" name="placa_veiculo" class="form-control bg-white" placeholder="ABC1D23"></div>
                            <div class="col-md-5"><label class="form-label">Chassi</label><input type="text" name="chassi_veiculo" class="form-control bg-white"></div>
                            <div class="col-md-3"><label class="form-label">Classe Bônus</label><input type="number" name="classe_bonus" class="form-control bg-white" value="0"></div>
                        </div>
                    </div>
                    <div id="bloco_consorcios" class="card bg-light border-0 p-3 mb-4 d-none">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Grupo</label><input type="text" name="numero_grupo" class="form-control bg-white"></div>
                            <div class="col-md-4"><label class="form-label">Cota</label><input type="text" name="numero_cota" class="form-control bg-white"></div>
                            <div class="col-md-4"><label class="form-label">Valor Crédito</label><input type="number" step="0.01" name="valor_credito" class="form-control bg-white"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label">Nº Documento</label><input type="text" name="numero_documento" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Movimento</label><select name="tipo_movimento" class="form-select"><option value="APOL NOVA">APOL NOVA</option><option value="APOL RENOVACAO">APOL RENOVACAO</option></select></div>
                        <div class="col-md-4"><label class="form-label">Emissão</label><input type="date" name="data_emissao" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Prêmio Líquido</label><input type="number" step="0.01" name="premio_liquido" id="premioInput" class="form-control" required oninput="calcularComissaoAutomatica('', 'premioInput', 'comissaoInput', 'corretorSelect')"></div>
                        <div class="col-md-6"><label class="form-label">Comissão Repasse</label><input type="number" step="0.01" name="comissao_esperada" id="comissaoInput" class="form-control" required></div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Corretor / Produtor Responsável</label>
                            <select name="corretor_id" id="corretorSelect" class="form-select rounded-3" required onchange="calcularComissaoAutomatica('', 'premioInput', 'comissaoInput', 'corretorSelect')">
                                <option value="" disabled selected>Selecione quem fechou a venda...</option>
                                <?php foreach ($listaCorretores as $corr): ?>
                                    <option value="<?= $corr['id'] ?>" data-comissao="<?= $corr['comissao_padrao'] ?>"><?= htmlspecialchars($corr['nome_completo']) ?> (Taxa padrão: <?= number_format($corr['comissao_padrao'], 1) ?>%)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="registrar_venda" class="btn btn-primary rounded-3" style="background:#2563eb; border:none;">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarVenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom border-light p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-pen-to-square text-warning me-2"></i> Modificar Apólice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="vendas.php" method="POST">
                <input type="hidden" name="edit_venda_id" id="edit_venda_id">
                <input type="hidden" name="edit_categoria_slug" id="edit_categoria_slug">
                
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><label class="form-label">Segurado</label><input type="text" id="edit_cliente_nome" class="form-control" readonly disabled></div>
                        <div class="col-md-6"><label class="form-label">Produto Vinc.</label><input type="text" id="edit_produto_nome" class="form-control" readonly disabled></div>
                    </div>

                    <div id="edit_bloco_saude" class="card bg-light border-0 p-3 mb-4 d-none">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Idade Titular</label><input type="number" name="idade_titular" id="edit_idade_titular" class="form-control bg-white"></div>
                            <div class="col-md-4"><label class="form-label">Qtd Vidas</label><input type="number" name="quantidade_vidas" id="edit_quantidade_vidas" class="form-control bg-white"></div>
                            <div class="col-md-4"><label class="form-label">Odonto Incluso?</label><select name="odonto_incluso" id="edit_odonto_incluso" class="form-select bg-white"><option value="Não">Não</option><option value="Sim">Sim</option></select></div>
                        </div>
                    </div>
                    <div id="edit_bloco_automoveis" class="card bg-light border-0 p-3 mb-4 d-none">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Placa do Carro</label><input type="text" name="placa_veiculo" id="edit_placa_veiculo" class="form-control bg-white"></div>
                            <div class="col-md-5"><label class="form-label">Chassi</label><input type="text" name="chassi_veiculo" id="edit_chassi_veiculo" class="form-control bg-white"></div>
                            <div class="col-md-3"><label class="form-label">Classe Bônus</label><input type="number" name="classe_bonus" id="edit_classe_bonus" class="form-control bg-white"></div>
                        </div>
                    </div>
                    <div id="edit_bloco_consorcios" class="card bg-light border-0 p-3 mb-4 d-none">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Grupo</label><input type="text" name="numero_grupo" id="edit_numero_grupo" class="form-control bg-white"></div>
                            <div class="col-md-4"><label class="form-label">Cota</label><input type="text" name="numero_cota" id="edit_numero_cota" class="form-control bg-white"></div>
                            <div class="col-md-4"><label class="form-label">Valor Crédito</label><input type="number" step="0.01" name="valor_credito" id="edit_valor_credito" class="form-control bg-white"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label">Nº Documento</label><input type="text" name="numero_documento" id="edit_numero_documento" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Movimento</label><select name="tipo_movimento" id="edit_tipo_movimento" class="form-select"><option value="APOL NOVA">APOL NOVA</option><option value="APOL RENOVACAO">APOL RENOVACAO</option></select></div>
                        <div class="col-md-4"><label class="form-label">Emissão</label><input type="date" name="data_emissao" id="edit_data_emissao" class="form-control" required></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Prêmio Líquido</label><input type="number" step="0.01" name="premio_liquido" id="edit_premioInput" class="form-control" required oninput="calcularComissaoAutomatica('edit_', 'edit_premioInput', 'edit_comissaoInput', 'edit_corretorSelect')"></div>
                        <div class="col-md-6"><label class="form-label">Comissão Repasse</label><input type="number" step="0.01" name="comissao_esperada" id="edit_comissaoInput" class="form-control" required></div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Alterar Corretor Vinculado</label>
                            <select name="corretor_id" id="edit_corretorSelect" class="form-select rounded-3" required onchange="calcularComissaoAutomatica('edit_', 'edit_premioInput', 'edit_comissaoInput', 'edit_corretorSelect')">
                                <?php foreach ($listaCorretores as $corr): ?>
                                    <option value="<?= $corr['id'] ?>" data-comissao="<?= $corr['comissao_padrao'] ?>"><?= htmlspecialchars($corr['nome_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light p-4">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" name="atualizar_venda" class="btn btn-warning rounded-3 text-dark fw-semibold">Atualizar Apólice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function alternarCamposPorCategoria() {
        const select = document.getElementById('produtoSelect');
        const opcao = select.options[select.selectedIndex];
        const categoria = opcao.getAttribute('data-categoria') || '';
        document.getElementById('categoria_slug_hidden').value = categoria;

        document.getElementById('bloco_saude').classList.add('d-none');
        document.getElementById('bloco_automoveis').classList.add('d-none');
        document.getElementById('bloco_consorcios').classList.add('d-none');

        if (categoria === 'saude' || categoria === 'odontologico') document.getElementById('bloco_saude').classList.remove('d-none');
        if (categoria === 'automoveis') document.getElementById('bloco_automoveis').classList.remove('d-none');
        if (categoria === 'consorcios') document.getElementById('bloco_consorcios').classList.remove('d-none');
    }

    // Algoritmo atualizado: Lê a taxa customizada (data-comissao) do seletor do corretor ativo para calcular o repasse
    function calcularComissaoAutomatica(prefixo = '', idPremio, idComissao, idCorretorSelect) {
        const premio = parseFloat(document.getElementById(idPremio).value) || 0;
        const selectCorr = document.getElementById(idCorretorSelect);
        
        let percentual = 22.00; // Fallback caso nenhum corretor tenha sido selecionado ainda
        if(selectCorr.selectedIndex >= 0) {
            const opcaoCorr = selectCorr.options[selectCorr.selectedIndex];
            percentual = parseFloat(opcaoCorr.getAttribute('data-comissao')) || 22.00;
        }

        const comissaoCalculada = premio * (percentual / 100);
        document.getElementById(idComissao).value = comissaoCalculada.toFixed(2);
    }

    function abrirEdicao(dados) {
        document.getElementById('edit_venda_id').value = dados.id;
        document.getElementById('edit_categoria_slug').value = dados.categoria_slug;
        document.getElementById('edit_cliente_nome').value = dados.cliente_nome;
        document.getElementById('edit_produto_nome').value = dados.produto_nome + " (" + dados.operadora_seguradora + ")";
        document.getElementById('edit_numero_documento').value = dados.numero_documento;
        document.getElementById('edit_tipo_movimento').value = dados.tipo_movimento;
        document.getElementById('edit_data_emissao').value = dados.data_emissao;
        document.getElementById('edit_premioInput').value = dados.premio_liquido;
        document.getElementById('edit_comissaoInput').value = dados.comissao_esperada;
        document.getElementById('edit_corretorSelect').value = dados.corretor_id;

        document.getElementById('edit_bloco_saude').classList.add('d-none');
        document.getElementById('edit_bloco_automoveis').classList.add('d-none');
        document.getElementById('edit_bloco_consorcios').classList.add('d-none');

        if (dados.categoria_slug === 'saude' || dados.categoria_slug === 'odontologico') {
            document.getElementById('edit_bloco_saude').classList.remove('d-none');
            document.getElementById('edit_idade_titular').value = dados.idade_titular;
            document.getElementById('edit_quantidade_vidas').value = dados.quantidade_vidas;
            document.getElementById('edit_odonto_incluso').value = dados.odonto_incluso;
        } else if (dados.categoria_slug === 'automoveis') {
            document.getElementById('edit_bloco_automoveis').classList.remove('d-none');
            document.getElementById('edit_placa_veiculo').value = dados.placa_veiculo;
            document.getElementById('edit_chassi_veiculo').value = dados.chassi_veiculo;
            document.getElementById('edit_classe_bonus').value = dados.classe_bonus;
        } else if (dados.categoria_slug === 'consorcios') {
            document.getElementById('edit_bloco_consorcios').classList.remove('d-none');
            document.getElementById('edit_numero_grupo').value = dados.numero_grupo;
            document.getElementById('edit_numero_cota').value = dados.numero_cota;
            document.getElementById('edit_valor_credito').value = dados.valor_credito;
        }

        new bootstrap.Modal(document.getElementById('modalEditarVenda')).show();
    }

    function confirmarExclusao(id, documento) {
        Swal.fire({
            title: 'Excluir Apólice?',
            text: `Tem certeza que deseja apagar o documento nº ${documento}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            customClass: { popup: 'rounded-4 p-4', confirmButton: 'btn btn-danger px-4 py-2 me-2 rounded-3', cancelButton: 'btn btn-light px-4 py-2 rounded-3' },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `vendas.php?excluir_id=${id}`;
            }
        });
    }

    function filtrarProducao() {
        const filter = document.getElementById("filtroProducao").value.toUpperCase();
        const rows = document.getElementById("tabelaProducao").getElementsByTagName("tr");
        for (let i = 1; i < rows.length; i++) {
            let txt = rows[i].innerText || rows[i].textContent;
            rows[i].style.display = txt.toUpperCase().indexOf(filter) > -1 ? "" : "none";
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