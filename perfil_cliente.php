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

$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cliente_id === 0) {
    echo "<script>alert('Cliente não especificado.'); window.location.href = 'clientes.php';</script>";
    exit;
}

try {
    // 1. BUSCA DADOS CADASTRAIS DO CLIENTE
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
    $stmt->execute(['id' => $cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo "<script>alert('Cliente não encontrado.'); window.location.href = 'clientes.php';</script>";
        exit;
    }

    // 2. BUSCA PRODUTOS ADQUIRIDOS: PLANOS DE SAÚDE
    $stmtSaude = $pdo->prepare("SELECT * FROM plano_saude WHERE cliente_id = :id ORDER BY id DESC");
    $stmtSaude->execute(['id' => $cliente_id]);
    $planosSaude = $stmtSaude->fetchAll(PDO::FETCH_ASSOC);

    // 3. BUSCA PRODUTOS ADQUIRIDOS: SEGUROS AUTOMÓVEL
    $stmtAuto = $pdo->prepare("SELECT * FROM auto WHERE cliente_id = :id ORDER BY id DESC");
    $stmtAuto->execute(['id' => $cliente_id]);
    $segurosAuto = $stmtAuto->fetchAll(PDO::FETCH_ASSOC);

    // 4. BUSCA PRODUTOS ADQUIRIDOS: CONSÓRCIOS
    $stmtConsorcio = $pdo->prepare("SELECT * FROM consorcio WHERE cliente_id = :id ORDER BY id DESC");
    $stmtConsorcio->execute(['id' => $cliente_id]);
    $consorcios = $stmtConsorcio->fetchAll(PDO::FETCH_ASSOC);

    // 5. BUSCA PRODUTOS ADQUIRIDOS: SEGUROS DE VIDA
    $stmtVida = $pdo->prepare("SELECT * FROM vida WHERE cliente_id = :id ORDER BY id DESC");
    $stmtVida->execute(['id' => $cliente_id]);
    $segurosVida = $stmtVida->fetchAll(PDO::FETCH_ASSOC);

    // 6. MÉTRICA FINANCEIRA LOCAL: Total investido pelo cliente no ecossistema
    $totalInvestido = 0;
    foreach ($planosSaude as $s) { $totalInvestido += $s['valor_titular']; }
    foreach ($segurosAuto as $a)  { $totalInvestido += $a['premio_total']; }
    foreach ($consorcios as $c)   { $totalInvestido += $c['credito']; }
    foreach ($segurosVida as $v)  { $totalInvestido += $v['valor_titular']; }

} catch (PDOException $e) {
    error_log("Erro ao carregar perfil do cliente: " . $e->getMessage());
    echo "Erro técnico ao carregar o perfil.";
    exit;
}

// Função auxiliar para calcular dias restantes para renovação
function calcularDiasRenovacao($data_fim) {
    if (empty($data_fim)) return 'N/A';
    $hoje = new DateTime();
    $fim  = new DateTime($data_fim);
    
    if ($hoje > $fim) {
        return '<span class="badge bg-danger rounded-pill px-2.5 py-1.5">Vencido</span>';
    }
    
    $intervalo = $hoje->diff($fim);
    $dias = $intervalo->days;
    
    if ($dias <= 30) {
        return '<span class="badge bg-warning text-dark rounded-pill px-2.5 py-1.5">Renovar em ' . $dias . ' dias</span>';
    }
    return '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1.5">' . $dias . ' dias restantes</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de 360º | <?= htmlspecialchars($cliente['nome_completo']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2 family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; margin: 0; }
        .main-content { padding: 3rem; min-height: 100vh; }
        .card-premium { background: #fff; border: 1px solid #f1f5f9; border-radius: 20px; padding: 2rem; box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.03); }
        .profile-header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 24px; padding: 2.5rem; color: white; }
        .table-premium th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9; }
        .table-premium td { padding: 1rem 0; border-bottom: 1px solid #f8fafc; }
        .nav-tabs-premium .nav-link { color: #64748b; font-weight: 500; border: none; padding: 0.75rem 1.25rem; border-radius: 10px; transition: all 0.2s; }
        .nav-tabs-premium .nav-link.active { background: rgba(234, 88, 12, 0.08); color: #ea580c; fw: 600; }
        @media (max-width: 1200px) { .main-content { padding: 5.5rem 1.25rem 2.5rem 1.25rem !important; } }
    </style>
</head>
<body>

<?php include('includes/sidebar.php'); ?>

<div class="main-content">
    
    <!-- Voltar para a listagem -->
    <div class="mb-4">
        <a href="clientes.php" class="text-decoration-none small text-muted fw-medium"><i class="fa-solid fa-arrow-left me-1"></i> Voltar para a Carteira de Clientes</a>
    </div>

    <!-- CABEÇALHO DO PERFIL 360º -->
    <div class="profile-header mb-5">
        <div class="d-flex align-items-center flex-wrap gap-4">
            <div class="rounded-circle bg-white text-dark fw-bold d-flex align-items-center justify-content-center shadow-lg" style="width: 70px; height: 70px; font-size: 1.5rem; color: #ea580c !important;">
                <?= strtoupper(substr($cliente['nome_completo'], 0, 2)) ?>
            </div>
            <div class="flex-grow-1">
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($cliente['nome_completo']) ?></h3>
                <div class="d-flex flex-wrap gap-3 font-monospace text-white-50 small mt-1">
                    <span><i class="fa-solid fa-id-card me-1"></i> CPF: <?= htmlspecialchars($cliente['cpf'] ?? 'Não informado') ?></span>
                    <span><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($cliente['telefone']) ?></span>
                    <span><i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($cliente['email'] ?? 'Sem e-mail') ?></span>
                    <span><i class="fa-solid fa-location-dot me-1"></i> <?= htmlspecialchars($cliente['cidade']) ?>-<?= htmlspecialchars($cliente['uf']) ?></span>
                </div>
            </div>
            <div class="text-end bg-white bg-opacity-10 p-3 rounded-4 backdrop-blur">
                <span class="text-uppercase tracking-wider font-monospace text-white-50 small d-block">Volume Global em Carteira</span>
                <span class="fs-4 fw-bold text-white">R$ <?= number_format($totalInvestido, 2, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <!-- TABS DE PRODUTOS ADQUIRIDOS -->
    <div class="card-premium">
        <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-box-open text-muted me-2"></i> Portfólio de Produtos Adquiridos</h5>
        
        <ul class="nav nav-tabs border-0 gap-2 nav-tabs-premium mb-4" id="portfolioTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="saude-tab" data-bs-toggle="tab" data-bs-target="#saude" type="button" role="tab"><i class="fa-solid fa-user-doctor me-2"></i>Saúde (<?= count($planosSaude) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="auto-tab" data-bs-toggle="tab" data-bs-target="#auto" type="button" role="tab"><i class="fa-solid fa-car me-2"></i>Auto (<?= count($segurosAuto) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="consorcio-tab" data-bs-toggle="tab" data-bs-target="#consorcio" type="button" role="tab"><i class="fa-solid fa-layer-group me-2"></i>Consórcios (<?= count($consorcios) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="vida-tab" data-bs-toggle="tab" data-bs-target="#vida" type="button" role="tab"><i class="fa-solid fa-heart-pulse me-2"></i>Vida (<?= count($segurosVida) ?>)</button>
            </li>
        </ul>

        <div class="tab-content" id="portfolioTabsContent">
            
            <!-- ABRA: PLANOS DE SAÚDE -->
            <div class="tab-pane fade show active" id="saude" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0 table-premium">
                        <thead>
                            <tr>
                                <th>OPERADORA / PLANO</th>
                                <th>DEPENDENTES</th>
                                <th>STATUS CONTRATO</th>
                                <th class="text-end">VALOR DO PRODUTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($planosSaude)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum plano de saúde adquirido.</td></tr>
                            <?php else: foreach($planosSaude as $s): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($s['plano']) ?></td>
                                    <td><?= $s['tem_dependentes'] === 'Sim' ? 'Familiar / Coletivo' : 'Individual' ?></td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2.5 py-1.5">Ativo (Recorrente)</span></td>
                                    <td class="text-end fw-bold text-dark">R$ <?= number_format($s['valor_titular'], 2, ',', '.') ?>/mês</td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ABA: SEGURO AUTOMÓVEL (Com Renovação Computada) -->
            <div class="tab-pane fade" id="auto" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0 table-premium">
                        <thead>
                            <tr>
                                <th>SEGURADORA / APÓLICE</th>
                                <th>VEÍCULO / PLACA</th>
                                <th>VIGÊNCIA FINAL</th>
                                <th>TEMPO PARA RENOVAÇÃO</th>
                                <th class="text-end">VALOR DO PRODUTO (PRÊMIO)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($segurosAuto)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Nenhum seguro de automóvel adquirido.</td></tr>
                            <?php else: foreach($segurosAuto as $a): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark text-uppercase"><?= htmlspecialchars($a['seguradora']) ?></div>
                                        <div class="text-muted small font-monospace">Nº <?= htmlspecialchars($a['num_apolice']) ?></div>
                                    </td>
                                    <td><span class="badge bg-dark text-white font-monospace"><?= htmlspecialchars($a['placa']) ?></span></td>
                                    <td class="text-secondary font-monospace small"><?= date('d/m/Y', strtotime($a['vigencia_fim'])) ?></td>
                                    <td><?= calcularDiasRenovacao($a['vigencia_fim']) ?></td>
                                    <td class="text-end fw-bold text-dark">R$ <?= number_format($a['premio_total'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ABA: CONSÓRCIOS -->
            <div class="tab-pane fade" id="consorcio" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0 table-premium">
                        <thead>
                            <tr>
                                <th>ADMINISTRADORA / SEGMENTO</th>
                                <th>GRUPO / COTA</th>
                                <th>PRAZO TOTAL</th>
                                <th class="text-end">VALOR DO PRODUTO (CARTA)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($consorcios)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum consórcio adquirido.</td></tr>
                            <?php else: foreach($consorcios as $c): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark text-uppercase"><?= htmlspecialchars($c['administradora']) ?></div>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary small mt-1"><?= htmlspecialchars($c['produto']) ?></span>
                                    </td>
                                    <td class="font-monospace small">G: <?= htmlspecialchars($c['grupo']) ?> / C: <?= htmlspecialchars($c['cota']) ?></td>
                                    <td class="text-secondary fw-medium"><?= $c['prazo'] ?> Meses</td>
                                    <td class="text-end fw-bold text-orange-premium" style="color:#ea580c;">R$ <?= number_format($c['credito'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ABA: SEGURO DE VIDA -->
            <div class="tab-pane fade" id="vida" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0 table-premium">
                        <thead>
                            <tr>
                                <th>Nº APÓLICE</th>
                                <th>PLANO / COBERTURA</th>
                                <th>BENEFICIÁRIOS</th>
                                <th class="text-end">VALOR DO PRODUTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($segurosVida)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Nenhum seguro de vida adquirido.</td></tr>
                            <?php else: foreach($segurosVida as $v): ?>
                                <tr>
                                    <td class="font-monospace fw-bold text-secondary"><?= htmlspecialchars($v['num_apolice']) ?></td>
                                    <td class="fw-medium text-dark"><?= htmlspecialchars($v['plano']) ?></td>
                                    <td><?= $v['tem_dependentes'] === 'Sim' ? 'Possui vinculados' : 'Apenas Titular' ?></td>
                                    <td class="text-end fw-bold text-dark">R$ <?= number_format($v['valor_titular'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>