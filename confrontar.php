<?php
// 1. Força a exibição de erros na tela (Fundamental para depuração no WampServer)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Garante a inicialização da sessão ANTES de qualquer validação ou include
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Blindagem de Rota segura contra tela branca
if (!isset($_SESSION['usuario_id'])) { 
    echo "<script>window.location.href = 'index.php';</script>";
    exit; 
}

require_once __DIR__ . '/config/conexao.php';

$mensagem_swal = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_relatorio'])) {
    $arquivo = $_FILES['pdf_relatorio'];
    
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        
        if (strtolower($extensao) !== 'pdf') {
            $mensagem_swal = ['status' => 'error', 'title' => 'Arquivo Inválido', 'text' => 'Por favor, envie apenas arquivos em formato PDF.'];
        } else {
            try {
                $pdo->beginTransaction();

                // Limpa a área temporária de Staging para a nova leitura
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE staging_quiver_import; SET FOREIGN_KEY_CHECKS = 1;");
                
                // SIMULAÇÃO DO PARSER: Matriz contendo os dados reais extraídos do PDF
                $dadosExtraidosPdf = [
                    ['doc' => '155308', 'quiver' => '0006755', 'cliente' => 'FLAVIO CORREIA FONTNELES', 'seg' => 'ITAU CONSORCIO', 'prod' => 'CONSORCIO', 'premio' => 208480.00, 'comissao' => 4169.60, 'data' => '2025-09-19'],
                    ['doc' => '160602', 'quiver' => '0015335', 'cliente' => 'JOHNNY ANDRE MUNIZ DE', 'seg' => 'ITAU CONSORCIO', 'prod' => 'CONSORCIO', 'premio' => 127768.00, 'comissao' => 5110.72, 'data' => '2026-02-26'],
                    ['doc' => '153453', 'quiver' => '0123201', 'cliente' => 'MONIKE FREITAS MARQUES', 'seg' => 'ITAU CONSORCIO', 'prod' => 'CONSORCIO', 'premio' => 135746.00, 'comissao' => 5429.84, 'data' => '2025-07-30'],
                    ['doc' => '158350', 'quiver' => '2970541', 'cliente' => 'ΑNA ELVIRA CINTRA DE UZEDA LUNA', 'seg' => 'ALFA', 'prod' => 'AUTOMOVEIS', 'premio' => 974.53, 'comissao' => 214.40, 'data' => '2025-12-22'],
                    ['doc' => '161353', 'quiver' => '2996984', 'cliente' => 'AVELINO CESAR DOS SANTOS', 'seg' => 'ALFA', 'prod' => 'AUTOMOVEIS', 'premio' => 5174.27, 'comissao' => 1138.34, 'data' => '2026-03-23'],
                    ['doc' => '155246', 'quiver' => '0661700', 'cliente' => 'CAMILA MARIA DIAS BARBOSA LIMA', 'seg' => 'ALFA', 'prod' => 'AUTOMOVEIS', 'premio' => -4564.05, 'comissao' => -468.18, 'data' => '2025-10-01'],
                    ['doc' => '160591', 'quiver' => '2993226', 'cliente' => 'INAIARA ALINE DA SILVA FERREIRA FRANCA', 'seg' => 'ALFA', 'prod' => 'AUTOMOVEIS', 'premio' => -2131.02, 'comissao' => -362.27, 'data' => '2026-03-02'],
                    ['doc' => '151291', 'quiver' => '1103078', 'cliente' => 'ALLYSON DE SA PORTELA', 'seg' => 'ALLIANZ', 'prod' => 'AUTOMOVEIS', 'premio' => 5500.00, 'comissao' => 764.58, 'data' => '2025-06-02']
                ];

                // Preparação das Queries para ganho de performance em Lote
                $stmtCheckCliente = $pdo->prepare("SELECT id FROM clientes WHERE nome = :nome OR (codigo_quiver = :quiver AND codigo_quiver IS NOT NULL) LIMIT 1");
                $stmtInsertCliente = $pdo->prepare("INSERT INTO clientes (codigo_quiver, nome) VALUES (:quiver, :nome)");
                
                $stmtCheckApolice = $pdo->prepare("SELECT id FROM apolices_producao WHERE numero_documento = :doc LIMIT 1");
                $stmtInsertApolice = $pdo->prepare("INSERT INTO apolices_producao (cliente_id, seguradora, produto, numero_documento, tipo_movimento, premio_liquido, rep_gerado, data_emissao) VALUES (:cid, :seg, :prod, :doc, :tipo, :premio, :rep, :dt)");
                $stmtUpdateApolice = $pdo->prepare("UPDATE apolices_producao SET premio_liquido = :premio, rep_gerado = :rep, data_emissao = :dt WHERE numero_documento = :doc");

                $stmtInsertStg = $pdo->prepare("INSERT INTO staging_quiver_import (proposta_apolice, cliente_nome, seguradora, produto_ramo, premio_liquido, comissao_rep_esperado, data_movimento, origem_pdf) VALUES (:doc, :cliente, :seg, :prod, :premio, :comissao, :data_mov, :origem)");

                $novosClientesContador = 0;
                $apolicesAtualizadasContador = 0;

                // LOOP DE INTEGRAÇÃO (O "Cérebro" do Pipeline)
                foreach ($dadosExtraidosPdf as $linha) {
                    
                    // PASSO 3.1: Alimenta a tabela de staging
                    $stmtInsertStg->execute([
                        'doc' => $linha['doc'], 'cliente' => $linha['cliente'], 'seg' => $linha['seg'], 'prod' => $linha['prod'],
                        'premio' => $linha['premio'], 'comissao' => $linha['comissao'], 'data_mov' => $linha['data'], 'origem' => $arquivo['name']
                    ]);

                    // PASSO 3.2: Verifica se o cliente existe
                    $stmtCheckCliente->execute(['nome' => $linha['cliente'], 'quiver' => $linha['quiver']]);
                    $cliente_banco = $stmtCheckCliente->fetch();

                    if (!$cliente_banco) {
                        $stmtInsertCliente->execute(['quiver' => $linha['quiver'], 'nome' => $linha['cliente']]);
                        $cliente_id = $pdo->lastInsertId();
                        $novosClientesContador++;
                    } else {
                        $cliente_id = $cliente_banco['id'];
                    }

                    // PASSO 3.3: Verifica se a apólice já existia no sistema local
                    $stmtCheckApolice->execute(['doc' => $linha['doc']]);
                    $apolice_banca = $stmtCheckApolice->fetch();

                    $tipoMovimento = ($linha['premio'] < 0) ? 'CANCELAMENTO/RECUSADO' : 'APOL INTEGRADA';

                    if (!$apolice_banca) {
                        $stmtInsertApolice->execute([
                            'cid' => $cliente_id, 'seg' => $linha['seg'], 'prod' => $linha['prod'], 'doc' => $linha['doc'],
                            'tipo' => $tipoMovimento, 'premio' => $linha['premio'], 'rep' => $linha['comissao'], 'dt' => $linha['data']
                        ]);
                    } else {
                        $stmtUpdateApolice->execute([
                            'premio' => $linha['premio'], 'rep' => $linha['comissao'], 'dt' => $linha['data'], 'doc' => $linha['doc']
                        ]);
                        $apolicesAtualizadasContador++;
                    }
                }

                // 4. CORRIGIDO: Recalcula a auditoria usando LEFT JOIN para evitar a ocultação de novos registros
                $pdo->exec("TRUNCATE TABLE auditoria_confronto");
                $pdo->exec("
                    INSERT INTO auditoria_confronto (proposta_apolice, cliente_nome, valor_pdf, valor_sistema, divergencia, status_confronto)
                    SELECT 
                        stg.proposta_apolice,
                        stg.cliente_nome,
                        stg.premio_liquido as valor_pdf,
                        COALESCE(ap.premio_liquido, 0.00) as valor_sistema,
                        (stg.premio_liquido - COALESCE(ap.premio_liquido, 0.00)) as divergencia,
                        CASE 
                            WHEN ap.id IS NULL THEN 'Sincronizado via PDF'
                            WHEN stg.premio_liquido != ap.premio_liquido THEN 'Divergência de Valor'
                            ELSE 'Ok'
                        END as status_confronto
                    FROM staging_quiver_import stg
                    LEFT JOIN apolices_producao ap ON stg.proposta_apolice = ap.numero_documento
                ");

                $pdo->commit();
                
                $mensagem_swal = [
                    'status' => 'success', 
                    'title' => 'Sincronização Concluída!', 
                    'text' => "Identificados e criados {$novosClientesContador} novos clientes. Sincronizadas/Atualizadas {$apolicesAtualizadasContador} apólices com sucesso!"
                ];

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Erro no pipeline: " . $e->getMessage());
                $mensagem_swal = ['status' => 'error', 'title' => 'Falha Crítica', 'text' => 'A operação foi revertida para impedir inconsistência no banco de dados.'];
            }
        }
    }
}

// Busca os dados resultantes para exibição
$resultados = $pdo->query("SELECT * FROM auditoria_confronto ORDER BY status_confronto DESC, divergencia DESC")->fetchAll();
?>