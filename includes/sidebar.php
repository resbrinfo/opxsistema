<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$nome_usuario = $_SESSION['usuario_nome'] ?? 'Corretor';
$nivel_usuario = $_SESSION['usuario_nivel'] ?? 'corretor';

function verificarAtivo($pagina) {
    return (basename($_SERVER['PHP_SELF']) == $pagina) ? 'active' : '';
}
?>

<div class="mobile-top-bar d-xl-none">
    <button class="menu-toggle-btn" type="button" onclick="alternarMenuMobile()">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="mobile-brand-title">Egis Saúde</div>
    <div class="user-avatar-mini">
        <?= strtoupper(substr($nome_usuario, 0, 2)) ?>
    </div>
</div>

<div class="sidebar" id="sidebarApp">
    <div class="sidebar-header">
        <div class="d-flex align-items-center px-2 brand-logo-wrapper">
            <img src="img/logo.png" alt="Logo" style="max-width: 140px; height: auto;">
        </div>
        <button class="close-menu-btn d-xl-none" onclick="alternarMenuMobile()">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    
    <div class="nav-scroll-container">
        <nav class="nav flex-column">
            <a class="nav-link <?= verificarAtivo('dashboard.php') ?>" href="dashboard.php">
                <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
            </a>

            <?php if (in_array($nivel_usuario, ['master', 'diretoria', 'colaborador', 'corretor'])): ?>
                <a class="nav-link <?= verificarAtivo('clientes.php') ?>" href="clientes.php">
                    <i class="fa-solid fa-users"></i> <span>Clientes</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($nivel_usuario, ['master', 'diretoria', 'financeiro'])): ?>
                <a class="nav-link <?= verificarAtivo('leads.php') ?>" href="leads.php">
                    <i class="fa-solid fa-filter"></i> <span>Funil de Leads</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($nivel_usuario, ['master', 'diretoria', 'financeiro'])): ?>
                <a class="nav-link <?= verificarAtivo('corretores.php') ?>" href="corretores.php">
                    <i class="fa-solid fa-user-tie"></i> <span>Corretores</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($nivel_usuario, ['master', 'diretoria', 'financeiro'])): ?>
                <a class="nav-link <?= verificarAtivo('grades_comissoes.php') ?>" href="grades_comissoes.php">
                    <i class="fa-solid fa-percent"></i> <span>Grades de Repasse</span>
                </a>
                
                <a class="nav-link <?= verificarAtivo('operadoras.php') ?>" href="operadoras.php">
                    <i class="fa-solid fa-building-shield"></i> <span>Operadoras</span>
                </a>

                <a class="nav-link <?= verificarAtivo('administradoras.php') ?>" href="administradoras.php">
                    <i class="fa-solid fa-building-columns"></i> <span>Administradoras</span>
                </a>

                <a class="nav-link <?= verificarAtivo('modalidades.php') ?>" href="modalidades.php">
                    <i class="fa-solid fa-tags"></i> <span>Modalidades</span>
                </a>
            <?php endif; ?>

            <a class="nav-link <?= verificarAtivo('plano_saude.php') ?>" href="plano_saude.php">
                <i class="fa-solid fa-user-doctor"></i> <span>Planos de Saúde</span>
            </a>

            <a class="nav-link <?= verificarAtivo('auto.php') ?>" href="auto.php">
                <i class="fa-solid fa-car"></i> <span>Seguro Automóvel</span>
            </a>

            <a class="nav-link <?= verificarAtivo('consorcio.php') ?>" href="consorcio.php">
                <i class="fa-solid fa-layer-group"></i> <span>Consórcios</span>
            </a>

            <a class="nav-link <?= verificarAtivo('vida.php') ?>" href="vida.php">
                <i class="fa-solid fa-heart-pulse"></i> <span>Seguro de Vida</span>
            </a>
        </nav>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center overflow-hidden">
            <div class="user-avatar-main">
                <?= strtoupper(substr($nome_usuario, 0, 2)) ?>
            </div>
            <div class="overflow-hidden brand-text">
                <div class="fw-semibold text-dark text-truncate user-name-text"><?= htmlspecialchars($nome_usuario) ?></div>
                <div class="user-role-text"><?= htmlspecialchars($nivel_usuario) ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-action-btn" title="Sair do App"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
    </div>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="alternarMenuMobile()"></div>

<style>
    :root {
        --orange-app: #ea580c;
        --orange-light: rgba(234, 88, 12, 0.05);
        --slate-dark: #0f172a;
        --slate-muted: #64748b;
        --border-color: #f1f5f9;
        --radius-premium: 16px;
    }

    body, html {
        overflow-x: hidden !important;
        background-color: #f8fafc;
    }

    /* --- SIDEBAR DESKTOP ARQUITETURA --- */
    .sidebar {
        width: 280px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background: #ffffff;
        border-right: 1px solid var(--border-color);
        padding: 1.5rem 1.25rem 1.25rem 1.25rem; /* Reduzido levemente os paddings externos */
        z-index: 1050;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem; /* Reduzido de 2.5rem para aproximar o menu do logo */
        flex-shrink: 0; /* Garante que o cabeçalho nunca encolha */
    }
    
    /* CONTEINER DE ROLAGEM EXCLUSIVO DOS LINKS */
    .nav-scroll-container {
        flex: 1;
        overflow-y: auto;
        margin-bottom: 1rem;
        padding-right: 4px;
    }
    
    /* Customização estética da barra de rolagem para mantê-la sutil */
    .nav-scroll-container::-webkit-scrollbar {
        width: 5px;
    }
    .nav-scroll-container::-webkit-scrollbar-track {
        background: transparent;
    }
    .nav-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    .nav-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    .sidebar .nav-link {
        color: var(--slate-muted);
        font-weight: 500;
        font-size: 0.92rem; /* Compactado levemente de 0.95rem */
        padding: 0.65rem 0.85rem; /* Reduzido padding interno de 0.85rem x 1rem */
        border-radius: 10px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        text-decoration: none;
        margin-bottom: 0.25rem; /* Diminuído espaçamento entre links */
    }
    .sidebar .nav-link i {
        font-size: 1.1rem;
        width: 24px;
        margin-right: 10px;
        opacity: 0.8;
    }
    .sidebar .nav-link:hover, .sidebar .nav-link.active {
        color: var(--orange-app) !important;
        background-color: var(--orange-light);
    }
    .sidebar-footer {
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0; /* Garante que o rodapé do usuário nunca sofra distorção */
    }
    .user-avatar-main {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 50%;
        background-color: #f1f5f9;
        color: var(--orange-app);
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-size: 0.85rem;
    }
    .user-name-text { font-size: 0.85rem; }
    .user-role-text { font-size: 0.7rem; color: var(--slate-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-top: 1px; }
    .logout-action-btn { color: var(--slate-muted); transition: color 0.2s; font-size: 1.1rem; }
    .logout-action-btn:hover { color: #ef4444; }

    /* --- ADAPTAÇÕES DE CONTEÚDO --- */
    .main-content {
        margin-left: 280px;
        padding: 3rem;
        transition: all 0.3s ease;
    }

    /* --- MOBILE TOP BAR NAVBAR --- */
    .mobile-top-bar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 64px;
        background: #ffffff;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1.25rem;
        z-index: 1000;
    }
    .menu-toggle-btn {
        background: none;
        border: none;
        font-size: 1.35rem;
        color: var(--slate-dark);
        padding: 0.25rem;
    }
    .mobile-brand-title {
        font-weight: 700;
        font-size: 1.15rem;
        color: var(--slate-dark);
        letter-spacing: -0.3px;
    }
    .user-avatar-mini {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--orange-light);
        color: var(--orange-app);
        font-weight: 700;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .close-menu-btn {
        background: none;
        border: none;
        font-size: 1.4rem;
        color: var(--slate-muted);
    }
    .sidebar-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(4px);
        z-index: 1040;
        display: none;
        opacity: 0;
        transition: opacity 0.2s linear;
    }

    /* --- RESPONSIVIDADE APP MOBILE --- */
    @media (max-width: 1200px) {
        .sidebar {
            transform: translateX(-100%);
            box-shadow: 20px 0 40px rgba(0,0,0,0.05);
            width: 290px;
        }
        .sidebar.open {
            transform: translateX(0);
        }
        .sidebar.open + .sidebar-backdrop {
            display: block;
            opacity: 1;
        }
        .main-content {
            margin-left: 0 !important;
            padding: 5.5rem 1.25rem 2.5rem 1.25rem !important;
        }
        
        .card-premium {
            padding: 1.25rem !important;
            border-radius: 16px !important;
        }
        .table-premium thead {
            display: none;
        }
        .table-premium tbody tr {
            display: block;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: relative;
        }
        .table-premium tbody tr:last-child { border-bottom: none; }
        .table-premium tbody td {
            display: flex;
            justify-content: justify;
            align-items: center;
            padding: 0.4rem 0 !important;
            border: none !important;
            font-size: 0.9rem;
        }
        .table-premium tbody td::before {
            content: attr(data-label);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--slate-muted);
            width: 35%;
            min-width: 100px;
            display: inline-block;
        }
        .table-premium tbody td .btn-action {
            width: 38px;
            height: 38px;
        }
        .table-premium tbody td:last-child {
            justify-content: flex-end;
            margin-top: 0.5rem;
        }
        .table-premium tbody td:last-child::before { display: none; }
    }
</style>

<script>
function alternarMenuMobile() {
    const sidebar = document.getElementById('sidebarApp');
    const backdrop = document.getElementById('sidebarBackdrop');
    
    if (sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        backdrop.style.opacity = '0';
        setTimeout(() => { backdrop.style.display = 'none'; }, 200);
    } else {
        backdrop.style.display = 'block';
        setTimeout(() => { backdrop.style.opacity = '1'; }, 10);
        sidebar.classList.add('open');
    }
}
</script>