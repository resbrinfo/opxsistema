<?php
// Inicia a sessão se ela ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Caminho absoluto para o arquivo de conexão local
require_once __DIR__ . '/config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$nome_usuario = $_SESSION['usuario_nome'];
$nivel_usuario = $_SESSION['usuario_nivel'];

try {
    // 1. Métrica: Total de Clientes Cadastrados na Base
    $totalClientes = $pdo->query("SELECT COUNT(*) as total FROM clientes")->fetch()['total'] ?? 0;

    // 2. Métricas de Volume por Ramos Individuais para alimentar os indicadores
    $totalConsorcios = $pdo->query("SELECT COUNT(*) as total FROM consorcio")->fetch()['total'] ?? 0;
    $totalAutos       = $pdo->query("SELECT COUNT(*) as total FROM auto")->fetch()['total'] ?? 0;
    $totalVidas       = $pdo->query("SELECT COUNT(*) as total FROM vida")->fetch()['total'] ?? 0;
    $totalSaude       = $pdo->query("SELECT COUNT(*) as total FROM plano_saude")->fetch()['total'] ?? 0;

    // 3. Cálculo de Faturamento Total Dinâmico (Soma de todos os ramos comerciais)
    $faturamentoConsorcio = $pdo->query("SELECT SUM(credito) as total FROM consorcio")->fetch()['total'] ?? 0;
    $faturamentoAuto       = $pdo->query("SELECT SUM(premio_total) as total FROM auto")->fetch()['total'] ?? 0;
    $faturamentoVida       = $pdo->query("SELECT SUM(valor_titular) as total FROM vida")->fetch()['total'] ?? 0;
    $faturamentoSaude      = $pdo->query("SELECT SUM(valor_titular) as total FROM plano_saude")->fetch()['total'] ?? 0;
    
    $totalFaturamento = $faturamentoConsorcio + $faturamentoAuto + $faturamentoVida + $faturamentoSaude;

    // 4. Estimativa Dinâmica de Comissão Baseada nas Grades de Repasse Ativas dos Corretores
    // Simulação gerencial: Se o corretor tem grade, aplica o percentual dele, senão aplica 10% padrão sobre o prêmio/crédito
    $queryComissoes = "
        SELECT SUM(valor_base * COALESCE(percentual, 10.00) / 100) as total_gerado FROM (
            SELECT con.credito as valor_base, g.percentual FROM consorcio con INNER JOIN corretores corr ON con.corretor_id = corr.id LEFT JOIN grades_comissao g ON corr.grade_comissao_id = g.id
            UNION ALL
            SELECT aut.premio_total as valor_base, g.percentual FROM auto aut INNER JOIN corretores corr ON aut.corretor_id = corr.id LEFT JOIN grades_comissao g ON corr.grade_comissao_id = g.id
            UNION ALL
            SELECT v.valor_titular as valor_base, g.percentual FROM vida v INNER JOIN corretores corr ON v.corretor_id = corr.id LEFT JOIN grades_comissao g ON corr.grade_comissao_id = g.id
            UNION ALL
            SELECT s.valor_titular as valor_base, g.percentual FROM plano_saude s INNER JOIN corretores corr ON s.corretor_id = corr.id LEFT JOIN grades_comissao g ON corr.grade_comissao_id = g.id
        ) as sub_comissoes
    ";
    $totalComissoes = $pdo->query($queryComissoes)->fetch()['total_gerado'] ?? 0.00;

    // 5. Consulta Avançada Unificada: Últimos 5 registros de qualquer ramo comercial emitido
    $queryUltimasVendas = "
        (SELECT 'Consórcio' as ramo, con.administradora as produto, con.credito as valor, cli.nome_completo as cliente, corr.nome_completo as corretor, con.criado_em, 'fa-layer-group' as icone 
         FROM consorcio con INNER JOIN clientes cli ON con.cliente_id = cli.id INNER JOIN corretores corr ON con.corretor_id = corr.id)
        UNION ALL
        (SELECT 'Automóvel' as ramo, aut.seguradora as produto, aut.premio_total as valor, cli.nome_completo as cliente, corr.nome_completo as corretor, aut.criado_em, 'fa-car' as icone 
         FROM auto aut INNER JOIN clientes cli ON aut.cliente_id = cli.id INNER JOIN corretores corr ON aut.corretor_id = corr.id)
        UNION ALL
        (SELECT 'Vida' as ramo, v.plano as produto, v.valor_titular as valor, cli.nome_completo as cliente, corr.nome_completo as corretor, v.criado_em, 'fa-heart-pulse' as icone 
         FROM vida v INNER JOIN clientes cli ON v.cliente_id = cli.id INNER JOIN corretores corr ON v.corretor_id = corr.id)
        UNION ALL
        (SELECT 'Plano de Saúde' as ramo, s.plano as produto, s.valor_titular as valor, cli.nome_completo as cliente, corr.nome_completo as corretor, s.criado_em, 'fa-user-doctor' as icone 
         FROM plano_saude s INNER JOIN clientes cli ON s.cliente_id = cli.id INNER JOIN corretores corr ON s.corretor_id = corr.id)
        ORDER BY criado_em DESC LIMIT 5
    ";
    $ultimasVendasLista = $pdo->query($queryUltimasVendas)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro no Dashboard Comercial Dinâmico: " . $e->getMessage());
    $totalClientes = 0; $totalConsorcios = 0; $totalAutos = 0; $totalVidas = 0; $totalSaude = 0;
    $totalFaturamento = 0.00; $totalComissoes = 0.00; $ultimasVendasLista = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Comercial | Egis Saúde</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-principal: #f8fafc;
            --bg-card: #ffffff;
            --texto-puro: #0f172a;
            --texto-mutado: #64748b;
            --primary-premium: #ea580c; 
            --primary-light: rgba(234, 88, 12, 0.05); 
            --sombra-premium: 0 10px 30px -5px rgba(0, 0, 0, 0.03), 0 4px 12px -2px rgba(0, 0, 0, 0.02);
            --transicao: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-principal); color: var(--texto-puro); margin: 0; }
        .main-content { margin-left: 280px; padding: 3.5rem; min-height: 100vh; }
        
        .welcome-banner { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 24px; padding: 3.5rem; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.15); }
        .welcome-banner::before { content: ''; position: absolute; inset: 0; background-image: linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px); background-size: 30px 30px; pointer-events: none; }

        .card-premium { background: var(--bg-card); border: 1px solid #f1f5f9; border-radius: 20px; padding: 2rem; box-shadow: var(--sombra-premium); transition: var(--transicao); height: 100%; }
        .card-premium:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.05); }

        .table-premium th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--texto-mutado); font-weight: 600; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; }
        .table-premium td { padding: 1.25rem 0; border-bottom: 1px solid #f8fafc; }
        .progress-bar-orange { background-color: #ea580c; }

        .mini-card-ramo { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; text-align: center; }

        @media (max-width: 1200px) {
            .main-content { margin-left: 0 !important; padding: 5.5rem 1.25rem 2.5rem 1.25rem !important; }
        }
    </style>
</head>
<body>

<?php include ("includes/sidebar.php"); ?>

<div class="main-content">
    
    <div class="welcome-banner mb-5 d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div>
            <span class="badge px-3 py-2 rounded-pill mb-3 text-uppercase font-monospace" style="font-size: 0.75rem; letter-spacing: 1px; background: rgba(234, 88, 12, 0.2); color: #ffa16c;">Métricas Gerais Ativas</span>
            <h1>Olá, <?= explode(' ', htmlspecialchars($nome_usuario))[0] ?>.</h1>
            <p class="text-white-50 mb-0 fs-5">Abaixo estão os indicadores reais consolidados das suas carteiras de produção de seguros e consórcios.</p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4">
            <div class="card-premium">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-uppercase font-monospace text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Clientes na Carteira</span>
                    <span class="badge rounded-pill px-2 py-1" style="font-size: 0.75rem; background: rgba(234, 88, 12, 0.1); color: var(--primary-premium);">Titulares</span>
                </div>
                <h2 class="fw-bold text-dark display-6 mb-1"><?= (int)$totalClientes ?></h2>
                <p class="text-muted small mb-0"><i class="fa-solid fa-circle text-success me-1" style="font-size: 10px;"></i> Clientes legítimos mapeados</p>
            </div>
        </div>
        
        <div class="col-12 col-md-4">
            <div class="card-premium">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-uppercase font-monospace text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Volume de Produção</span>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1" style="font-size: 0.75rem;">Global</span>
                </div>
                <h2 class="fw-bold text-dark display-6 mb-1">R$ <?= number_format($totalFaturamento, 2, ',', '.') ?></h2>
                <p class="text-muted small mb-0">Total movimentado em todos os ramos</p>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card-premium">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-uppercase font-monospace text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Estimativa de Repasse</span>
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-1" style="font-size: 0.75rem;">Comissões</span>
                </div>
                <h2 class="fw-bold display-6 mb-1" style="color: var(--primary-premium);">R$ <?= number_format($totalComissoes, 2, ',', '.') ?></h2>
                <div class="progress mt-3" style="height: 6px; background-color: #f1f5f9; border-radius: 10px;">
                    <div class="progress-bar progress-bar-orange rounded-pill" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold text-dark mb-3">Distribuição Estrutural por Ramos</h5>
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-3">
            <div class="mini-card-ramo">
                <i class="fa-solid fa-user-doctor fs-3 text-orange-premium mb-2" style="color:#ea580c;"></i>
                <div class="text-muted small fw-medium">Planos de Saúde</div>
                <h4 class="fw-bold text-dark mt-1 mb-0"><?= $totalSaude ?> contratos</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-card-ramo">
                <i class="fa-solid fa-car fs-3 text-orange-premium mb-2" style="color:#ea580c;"></i>
                <div class="text-muted small fw-medium">Seguro Automóvel</div>
                <h4 class="fw-bold text-dark mt-1 mb-0"><?= $totalAutos ?> apólices</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-card-ramo">
                <i class="fa-solid fa-layer-group fs-3 text-orange-premium mb-2" style="color:#ea580c;"></i>
                <div class="text-muted small fw-medium">Consórcios</div>
                <h4 class="fw-bold text-dark mt-1 mb-0"><?= $totalConsorcios ?> cotas</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="mini-card-ramo">
                <i class="fa-solid fa-heart-pulse fs-3 text-orange-premium mb-2" style="color:#ea580c;"></i>
                <div class="text-muted small fw-medium">Seguro de Vida</div>
                <h4 class="fw-bold text-dark mt-1 mb-0"><?= $totalVidas ?> apólices</h4>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-1">Últimas Apólices Emitidas (Tempo Real)</h5>
                <p class="text-muted small mb-0">Auditoria integrada recente de contratos e movimentos em todas as carteiras.</p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium">
                <thead>
                    <tr>
                        <th>Segurado Titular</th>
                        <th>Ramo Comercial</th>
                        <th>Produto / Cia</th>
                        <th>Corretor Operacional</th>
                        <th class="text-end">Volume</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimasVendasLista)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">Nenhum contrato ou apólice emitido nos novos módulos até o momento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($ultimasVendasLista as $venda): ?>
                            <tr>
                                <td data-label="Segurado Titular">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: var(--primary-light); color: var(--primary-premium);">
                                            <i class="fa-solid <?= $venda['icone'] ?>"></i>
                                        </div>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($venda['cliente']) ?></div>
                                    </div>
                                </td>
                                <td data-label="Ramo">
                                    <span class="badge rounded-pill px-3 py-1.5 fw-medium bg-dark text-white font-monospace" style="font-size: 0.7rem; letter-spacing:0.3px;">
                                        <?= htmlspecialchars($venda['ramo']) ?>
                                    </span>
                                </td>
                                <td data-label="Produto / Cia">
                                    <div class="fw-semibold text-dark text-uppercase small font-monospace"><?= htmlspecialchars($venda['produto']) ?></div>
                                </td>
                                <td data-label="Corretor" class="text-secondary small fw-medium">
                                    <i class="fa-solid fa-user-tie me-1 opacity-50"></i> <?= htmlspecialchars($venda['corretor']) ?>
                                </td>
                                <td data-label="Volume" class="text-end fw-bold" style="color: var(--primary-premium);">
                                    R$ <?= number_format($venda['valor'], 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>