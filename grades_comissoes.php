<?php
session_start();
// Proteção de Sessão padrão
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Carregamento seguro da conexão
require_once __DIR__ . '/config/conexao.php'; 

$mensagem_swal = "";

// 1. Processamento do Formulário (Inserção de Nova Grade)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_grade'])) {
    try {
        $nome_grade        = filter_input(INPUT_POST, 'nome_grade', FILTER_SANITIZE_SPECIAL_CHARS);
        $operadora_id      = filter_input(INPUT_POST, 'operadora_id', FILTER_SANITIZE_NUMBER_INT);
        $administradora_id = filter_input(INPUT_POST, 'administradora_id', FILTER_SANITIZE_NUMBER_INT);
        $modalidade_id     = filter_input(INPUT_POST, 'modalidade_id', FILTER_SANITIZE_NUMBER_INT); 
        $vigencia          = filter_input(INPUT_POST, 'vigencia', FILTER_SANITIZE_SPECIAL_CHARS);
        $vitalicio         = filter_input(INPUT_POST, 'vitalicio_inicio', FILTER_SANITIZE_SPECIAL_CHARS);

        // Captura e trata as 12 parcelas substituindo vírgula por ponto para o banco decimal
        $p = [];
        for ($i = 1; $i <= 12; $i++) {
            $val = $_POST["p$i"] ?? 0;
            $p[$i] = str_replace(',', '.', $val);
        }

        $sql = "INSERT INTO grades_comissoes 
                (nome_grade, operadora, administradora, modalidade, vigencia, p1, p2, p3, p4, p5, p6, p7, p8, p9, p10, p11, p12, vitalicio_inicio) 
                VALUES 
                (:nome_grade, :operadora, :admin, :modalidade, :vigencia, :p1, :p2, :p3, :p4, :p5, :p6, :p7, :p8, :p9, :p10, :p11, :p12, :vitalicio)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome_grade'     => $nome_grade,
            ':operadora'      => $operadora_id, 
            ':admin'          => !empty($administradora_id) ? $administradora_id : null,
            ':modalidade'     => $modalidade_id, 
            ':vigencia'       => $vigencia,
            ':p1'             => $p[1],  ':p2'  => $p[2],  ':p3'  => $p[3],  ':p4'  => $p[4],
            ':p5'             => $p[5],  ':p6'  => $p[6],  ':p7'  => $p[7],  ':p8'  => $p[8],
            ':p9'             => $p[9],  ':p10' => $p[10], ':p11' => $p[11], ':p12' => $p[12],
            ':vitalicio'      => $vitalicio
        ]);

        $mensagem_swal = "Swal.fire('Sucesso', 'Nova grade de comissão salva!', 'success');";
    } catch (PDOException $e) {
        $mensagem_swal = "Swal.fire('Erro', 'Falha ao salvar no banco: " . addslashes($e->getMessage()) . "', 'error');";
    }
}

// 2. Coleta de Dados para popular os Selects e a Tabela inferior
$operadoras = [];
$administradoras = [];
$modalidades = [];
$grades = [];

try {
    $operadoras      = $pdo->query("SELECT id, nome FROM operadoras ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $administradoras = $pdo->query("SELECT id, nome FROM administradoras ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    $modalidades     = $pdo->query("SELECT id, nome FROM modalidades ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC); 
    
    // TRIPLO LEFT JOIN para carregar os nomes na listagem
    $sql_list = "SELECT g.*, o.nome as operadora_nome, a.nome as admin_nome, m.nome as modalidade_nome 
                 FROM grades_comissoes g
                 LEFT JOIN operadoras o ON g.operadora = o.id
                 LEFT JOIN administras a ON g.administradora = a.id
                 LEFT JOIN modalidades m ON g.modalidade = m.id
                 ORDER BY g.id DESC";
    $grades = $pdo->query($sql_list)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tratamento silencioso
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade de Comissões - Premium Clean</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-principal: #f8fafc;
            --bg-card: #ffffff;
            --texto-puro: #0f172a;
            --texto-mutado: #64748b;
            --primary-premium: #ea580c;
            --primary-light: #ffedd5;
            --border-color: #e2e8f0;
            --sombra-premium: 0 10px 30px -10px rgba(0,0,0,0.05);
        }

        body, html {
            margin: 0; padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-principal);
            color: var(--texto-puro);
        }

        .main-content { margin-left: 280px; padding: 3.5rem; min-height: 100vh; }
        @media (max-width: 1200px) { .main-content { margin-left: 80px; padding: 2rem; } }

        .card-premium {
            background: var(--bg-card);
            border: 1px solid #f1f5f9;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--sombra-premium);
            margin-bottom: 2rem;
        }

        .header-inline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .header-inline h2 { margin: 0; font-size: 1.4rem; font-weight: 600; }
        .header-inline p { margin: 0; color: var(--texto-mutado); font-size: 0.9rem; }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--texto-puro);
            margin: 0 0 1.25rem 0;
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
        }

        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85rem; font-weight: 500; color: var(--texto-mutado); margin-bottom: 0.5rem; }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.95rem;
            color: var(--texto-puro);
            outline: none;
            background: #fff;
            transition: border-color 0.2s;
        }
        .form-control:focus, .form-select:focus { border-color: var(--primary-premium); box-shadow: none; }

        .parcelas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .percentage-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .percentage-wrapper input { text-align: center; padding-right: 1.75rem; }
        .percentage-wrapper .symbol { position: absolute; right: 1rem; color: var(--texto-mutado); font-size: 0.9rem; }

        .info-banner {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #1e3a8a;
            font-size: 0.85rem;
            margin: 1.5rem 0;
        }
        .info-banner i { color: #3b82f6; font-size: 1.1rem; }

        .btn-premium-orange {
            background-color: var(--primary-premium);
            color: #fff; border: none; padding: 0.85rem 1.5rem;
            border-radius: 12px; font-size: 0.95rem; font-weight: 500;
            cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;
            transition: background-color 0.2s; text-decoration: none;
        }
        .btn-premium-orange:hover { background-color: #c2410c; color: #fff; }

        .table-responsive { overflow-x: auto; }
        .table-premium { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        .table-premium th { color: var(--texto-mutado); font-weight: 600; padding-bottom: 1rem; border-bottom: 2px solid var(--border-color); }
        .table-premium td { padding: 1.25rem 0; border-bottom: 1px solid #f8fafc; }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="card-premium">
            <div class="header-inline" style="margin-bottom: 0;">
                <div>
                    <h2 style="font-size: 1.5rem; font-weight:600; margin-bottom:4px;">Comissão da Corretora</h2>
                    <p style="margin:0; color: var(--texto-mutado);">Esta é a grade de comissões que o escritório recebe das operadoras.</p>
                </div>
                <button type="button" class="btn-premium-orange" data-bs-toggle="modal" data-bs-target="#modalNovaGrade">
                    <i class="fa-solid fa-plus"></i> Configurar Nova Grade
                </button>
            </div>
        </div>

        <div class="card-premium">
            <div class="section-title">Grades Ativas no Sistema</div>
            <div class="table-responsive">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Nome da Grade</th>
                            <th>Operadora / Seguradora</th>
                            <th>Administradora</th>
                            <th>Modalidade</th>
                            <th>Vigência</th>
                            <th>1ª Parcela</th>
                            <th>Início Vitalício</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($grades) > 0): ?>
                            <?php foreach ($grades as $g): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($g['nome_grade'] ?? 'Sem identificação') ?></strong></td>
                                    <td><strong><i class="fa-solid fa-building-shield" style="color: var(--primary-premium); margin-right: 6px;"></i><?= htmlspecialchars($g['operadora_nome'] ?? 'Desconhecida') ?></strong></td>
                                    <td><?= htmlspecialchars($g['admin_nome'] ?? 'Venda Direta') ?></td>
                                    <td><span style="background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; padding: 4px 10px; border-radius: 8px; font-size: 0.85rem; font-weight:500;"><?= htmlspecialchars($g['modalidade_nome'] ?? 'Não especificada') ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($g['vigencia'])) ?></td>
                                    <td><strong style="color: #16a34a;"><?= number_format($g['p1'], 2, ',', '.') ?>%</strong></td>
                                    <td><span style="color: var(--primary-premium); font-weight:600; font-size: 0.85rem;"><i class="fa-solid fa-infinity"></i> <?= htmlspecialchars($g['vitalicio_inicio']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--texto-mutado); padding: 3rem;">Nenhuma regra de comissão localizada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNovaGrade" tabindex="-1" aria-labelledby="modalNovaGradeLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color); padding: 1.5rem 2rem;">
                    <h5 class="modal-title" id="modalNovaGradeLabel" style="font-weight: 600; font-size: 1.2rem;">Configuração de Detalhes da Grade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body" style="padding: 2rem; max-height: 75vh; overflow-y: auto;">
                        
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label>Nome / Identificador da Grade *</label>
                            <input type="text" name="nome_grade" class="form-control" placeholder="Ex: Amil PME Padrão, Bradesco Adesão 2026" required>
                        </div>

                        <div class="config-grid">
                            <div class="form-group">
                                <label>Operadora / Seguradora *</label>
                                <select name="operadora_id" class="form-select" required>
                                    <option value="">Selecione a Operadora...</option>
                                    <?php foreach ($operadoras as $op): ?>
                                        <option value="<?= $op['id'] ?>"><?= htmlspecialchars($op['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Administradora (Opcional)</label>
                                <select name="administradora_id" class="form-select">
                                    <option value="">Nenhuma (Venda Direta / Balcão)</option>
                                    <?php foreach ($administras as $ad): ?>
                                        <option value="<?= $ad['id'] ?>"><?= htmlspecialchars($ad['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Modalidade *</label>
                                <select name="modalidade_id" class="form-select" required>
                                    <option value="">Selecione a Modalidade...</option>
                                    <?php foreach ($modalidades as $mod): ?>
                                        <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Início da Vigência *</label>
                                <input type="date" name="vigencia" class="form-control" value="2026-07-01" required>
                            </div>
                        </div>

                        <div class="section-title" style="margin-top: 2.5rem;">Defina o percentual de recebimento para cada parcela:</div>
                        
                        <div class="parcelas-grid">
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <div class="form-group">
                                    <label style="text-align: center; font-weight:500;"><?= $i ?>ª Parcela</label>
                                    <div class="percentage-wrapper">
                                        <input type="number" name="p<?= $i ?>" class="form-control" min="0" max="200" step="0.01" value="0" onfocus="this.select();">
                                        <span class="symbol">%</span>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="info-banner">
                            <i class="fa-solid fa-circle-info"></i>
                            <span><strong>Parcelas com 0,00% não geram receita.</strong> Parcelas deixadas em branco ou zeradas serão interpretadas no processamento financeiro como nulas.</span>
                        </div>

                        <div class="form-group" style="margin-top: 1.5rem;">
                            <label><i class="fa-solid fa-infinity" style="color: var(--primary-premium);"></i> Se a comissão for vitalícia, defina em qual parcela ela se inicia:</label>
                            <select name="vitalicio_inicio" class="form-select" style="max-width: 450px;">
                                <option value="Nenhuma parcela">Nenhuma parcela (Finitas até a 12ª)</option>
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="A partir da <?= $i ?>ª parcela">A partir da <?= $i ?>ª parcela</option>
                                <?php endfor; ?>
                                <option value="A partir da 13ª parcela">A partir da 13ª parcela (Renovação Contínua)</option>
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer" style="border-top: 1px solid var(--border-color); padding: 1.25rem 2rem;">
                        <button type="button" class="form-control" style="width: auto; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight:500;" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="salvar_grade" class="btn-premium-orange">
                            <i class="fa-solid fa-floppy-disk"></i> Gravar Regra de Comissão
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (!empty($mensagem_swal)) echo $mensagem_swal; ?>
        });
    </script>
</body>
</html>