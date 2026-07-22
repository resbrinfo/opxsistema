<?php
/**
 * Egis Saúde - Conexão Premium via PDO
 * Design Patterns: Singleton-like para reaproveitamento de instância
 */

// Garante que a sessão está ativa para consultar níveis de acesso posteriormente
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações do ambiente de banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'egis_saude_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Deixe vazio ou insira a senha do seu ambiente local
define('DB_CHARSET', 'utf8mb4');

try {
    // Monta a DSN (Data Source Name) especificando o charset correto
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // Configurações avançadas de comportamento do PDO
    $opcoes = [
        // 1. Lança exceções em caso de erros de SQL (essencial para blocos try/catch)
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        
        // 2. Define o retorno padrão como Array Associativo (ex: $dados['nome'])
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        
        // 3. Desativa a emulação de prepares para usar o Prepared Statements real do MySQL (Segurança máxima)
        PDO::ATTR_EMULATE_PREPARES => false,
        
        // 4. Força a conexão a usar conexões persistentes se necessário (otimiza performance)
        PDO::ATTR_PERSISTENT => true
    ];

    // Cria a instância global da conexão
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);

} catch (PDOException $e) {
    /**
     * UX & Segurança Premium: Nunca exiba o erro bruto ($e->getMessage()) em produção.
     * Isso poderia expor nomes de tabelas, colunas ou caminhos de arquivos para invasores.
     */
    
    // Registra o erro detalhado internamente no servidor para o desenvolvedor analisar
    error_log("Erro de Conexão Egis Saúde: " . $e->getMessage());
    
    // Exibe uma resposta elegante ou encerra com uma mensagem limpa
    die("
        <div style='font-family: \"Inter\", sans-serif; display: flex; height: 100vh; align-items: center; justify-content: center; background: #f8fafc; color: #0f172a;'>
            <div style='text-align: center; max-width: 450px; padding: 2rem; background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);'>
                <h3 style='font-weight: 700; margin-bottom: 10px; color: #ef4444;'>Instabilidade no Sistema</h3>
                <p style='color: #64748b; line-height: 1.6; font-size: 14px;'>Não conseguimos estabelecer comunicação com o servidor de dados. Nossa equipe técnica já foi notificada.</p>
                <button onclick='window.location.reload()' style='margin-top: 15px; background: #2563eb; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;'>Tentar Novamente</button>
            </div>
        </div>
    ");
}