<?php
session_start();
require_once __DIR__ . '/config/conexao.php';

if (!isset($conexao) && isset($pdo)) { $conexao = $pdo; }
if (!isset($conexao) && isset($conn)) { $conexao = $conn; }

if (!$conexao) {
    die("Erro: Conexão com o banco de dados falhou.");
}

try {
    $query = "SELECT * FROM leads ORDER BY data_captura DESC";
    $stmt = $conexao->prepare($query);
    $stmt->execute();
    $todos_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na consulta de leads: " . $e->getMessage());
}

$colunas = [
    'Novo Lead' => [],
    'Em Atendimento' => [],
    'Proposta Enviada' => [],
    'Fechado' => []
];

foreach ($todos_leads as $lead) {
    $status = $lead['status'] ?? 'Novo Lead';
    if (array_key_exists($status, $colunas)) {
        $colunas[$status][] = $lead;
    } else {
        $colunas['Novo Lead'][] = $lead;
    }
}

function formatarProduto($slug) {
    $produtos = [
        'consorcio_inteligente' => 'Consórcio Inteligente',
        'seguros_auto_moto'     => 'Auto & Moto',
        'saude_empresarial'     => 'Saúde Empresarial',
        'energia_renovavel'     => 'Energia Renovável'
    ];
    return $produtos[$slug] ?? ucfirst(str_replace('_', ' ', $slug));
}

function corBadgeProduto($slug) {
    $cores = [
        'consorcio_inteligente' => 'background-color: #fef3c7; color: #d97706;',
        'seguros_auto_moto'     => 'background-color: #e0f2fe; color: #0369a1;',
        'saude_empresarial'     => 'background-color: #dcfce7; color: #15803d;',
        'energia_renovavel'     => 'background-color: #f3e8ff; color: #6b21a8;'
    ];
    return $cores[$slug] ?? 'background-color: #f1f5f9; color: #475569;';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pipeline de Leads - Egis Saúde</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-principal: #f8fafc;
            --bg-card: #ffffff;
            --texto-puro: #0f172a;
            --texto-mutado: #64748b;
            --primary-orange: #ea580c;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-principal);
            color: var(--texto-puro);
            margin: 0;
        }
        .main-content {
            margin-left: 280px;
            padding: 3.5rem;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        .kanban-board {
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
            overflow-x: auto;
            padding-bottom: 1rem;
            width: 100%;
        }
        .kanban-column {
            flex: 1;
            min-width: 290px;
            background-color: #f1f5f9;
            border-radius: 16px;
            padding: 1.25rem;
        }
        .kanban-column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .kanban-column-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0;
        }
        .lead-count {
            background-color: #cbd5e1;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
        }
        .kanban-cards-zone {
            min-height: 500px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .kanban-cards-zone.drag-over {
            background-color: #e2e8f0;
            outline: 2px dashed #cbd5e1;
            border-radius: 12px;
        }
        .lead-card {
            background: var(--bg-card);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            cursor: grab;
            position: relative;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .lead-card:active { cursor: grabbing; }
        .lead-name {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            padding-right: 65px;
        }
        .lead-info {
            font-size: 0.8rem;
            color: var(--texto-mutado);
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .product-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.6rem;
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        .card-actions-wrapper {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            display: flex;
            gap: 0.4rem;
            z-index: 10;
        }
        .btn-card-interaction {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .btn-whatsapp-direct {
            background-color: #e8fbf1;
            color: #10b981;
        }
        .btn-whatsapp-direct:hover {
            background-color: #10b981;
            color: #ffffff;
            transform: scale(1.05);
        }
        .btn-delete-lead {
            background-color: #fef2f2;
            color: #ef4444;
            cursor: pointer;
        }
        .btn-delete-lead:hover {
            background-color: #ef4444;
            color: #ffffff;
            transform: scale(1.05);
        }
        @media (max-width: 1200px) { 
            .main-content { margin-left: 0 !important; padding: 5.5rem 1.25rem 2.5rem 1.25rem !important; } 
        }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="mb-4">
            <h4 class="fw-bold m-0" style="color: var(--primary-orange);">Pipeline de Leads Corporativo</h4>
            <p class="text-muted small m-0">Gerencie contatos, inicie conversas ou remova cadastros inválidos do funil.</p>
        </div>

        <div class="kanban-board">
            <?php foreach ($colunas as $nome_coluna => $leads_coluna): ?>
                <div class="kanban-column">
                    <div class="kanban-column-header">
                        <h5 class="kanban-column-title"><?= $nome_coluna ?></h5>
                        <span class="lead-count"><?= count($leads_coluna) ?></span>
                    </div>

                    <div class="kanban-cards-zone" data-status="<?= $nome_coluna ?>">
                        <?php foreach ($leads_coluna as $lead): 
                            $fone_limpo = preg_replace('/\D/', '', $lead['telefone']);
                            
                            $primeiro_nome = explode(' ', trim($lead['nome']))[0];
                            $nome_formatado = mb_convert_case($primeiro_nome, MB_CASE_TITLE, "UTF-8");
                            $nome_produto = formatarProduto($lead['produto_interesse']);
                            
                            $texto_whatsapp = "Olá, " . $nome_formatado . "! Tudo bem? Me chamo Corretor e sou da OPX Investimentos e Consórcio Inteligente. Vi aqui no nosso sistema que você demonstrou interesse no produto *" . $nome_produto . "*. Como posso te ajudar hoje?";
                            $link_texto = urlencode($texto_whatsapp);
                        ?>
                            <div class="lead-card" draggable="true" data-id="<?= $lead['id'] ?>">
                                
                                <div class="card-actions-wrapper">
                                    <a href="https://wa.me/55<?= $fone_limpo ?>?text=<?= $link_texto ?>" target="_blank" class="btn-card-interaction btn-whatsapp-direct" title="Chamar no WhatsApp" onclick="event.stopPropagation();">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                    <button type="button" class="btn-card-interaction btn-delete-lead" title="Excluir Lead" onclick="event.stopPropagation(); confirmarExclusao(<?= $lead['id'] ?>, '<?= addslashes($lead['nome']) ?>', this);">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </div>

                                <div class="lead-name text-truncate" title="<?= $lead['nome'] ?>"><?= $lead['nome'] ?></div>
                                <div class="lead-info text-truncate"><i class="fa-regular fa-envelope"></i> <?= $lead['email'] ?></div>
                                <div class="lead-info"><i class="fa-solid fa-phone"></i> <?= $lead['telefone'] ?></div>
                                
                                <span class="product-badge" style="<?= corBadgeProduto($lead['produto_interesse']) ?>">
                                    <?= $nome_produto ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.lead-card');
            const zones = document.querySelectorAll('.kanban-cards-zone');
            let colunaOrigem = null;

            const atualizarContadoresLocais = () => {
                document.querySelectorAll('.kanban-column').forEach(col => {
                    const countSpan = col.querySelector('.lead-count');
                    if (countSpan) countSpan.textContent = col.querySelectorAll('.lead-card').length;
                });
            };

            cards.forEach(card => {
                card.addEventListener('dragstart', () => {
                    card.classList.add('dragging');
                    colunaOrigem = card.closest('.kanban-cards-zone');
                });
                card.addEventListener('dragend', () => {
                    card.classList.remove('dragging');
                    atualizarContadoresLocais();
                });
            });

            zones.forEach(zone => {
                zone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    zone.classList.add('drag-over');
                    const draggingCard = document.querySelector('.dragging');
                    if (draggingCard) zone.appendChild(draggingCard);
                });
                zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
                zone.addEventListener('drop', async (e) => {
                    e.preventDefault();
                    zone.classList.remove('drag-over');
                    const draggingCard = document.querySelector('.dragging');
                    if (!draggingCard) return;

                    zone.appendChild(draggingCard);
                    atualizarContadoresLocais();

                    const formData = new FormData();
                    formData.append('id', draggingCard.getAttribute('data-id'));
                    formData.append('status', zone.getAttribute('data-status'));

                    try {
                        let response = await fetch('atualizar_status_lead.php', { method: 'POST', body: formData });
                        let resultado = await response.json();
                        if (!resultado.success) throw new Error();
                    } catch (err) {
                        Swal.fire('Erro', 'Falha ao salvar status no banco de dados.', 'error');
                        if (colunaOrigem) { colunaOrigem.appendChild(draggingCard); atualizarContadoresLocais(); }
                    }
                });
            });
        });

        // 🌟 FUNÇÃO DE EXCLUSÃO VIA AJAX REAL-TIME
        function confirmarExclusao(id, nome, botao) {
            Swal.fire({
                title: 'Remover Lead?',
                text: `Deseja realmente excluir o cadastro de "${nome}"? Esta ação não pode ser desfeita.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('lead_id', id);

                    try {
                        let response = await fetch('ajustes_kanban_acoes.php?action=excluir_lead', {
                            method: 'POST',
                            body: formData
                        });
                        let res = await response.json();

                        if (res.success) {
                            // Encontra o card HTML correspondente e remove de forma suave
                            const card = botao.closest('.lead-card');
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.8)';
                            
                            setTimeout(() => {
                                card.remove();
                                // Recalcula os números das colunas na hora
                                document.querySelectorAll('.kanban-column').forEach(col => {
                                    if (col.querySelector('.lead-count')) {
                                        col.querySelector('.lead-count').textContent = col.querySelectorAll('.lead-card').length;
                                    }
                                });
                            }, 300);

                            Swal.fire({
                                title: 'Excluído!',
                                text: 'O lead foi removido com sucesso.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Erro', 'Não foi possível excluir o registro do banco.', 'error');
                        }
                    } catch (error) {
                        Swal.fire('Erro Técnico', 'Falha ao se comunicar com o servidor local.', 'error');
                    }
                }
            });
        }
    </script>
</body>
</html>