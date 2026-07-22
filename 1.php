<?php include ("includes/header.php");?>
<?php include ("includes/sidebar.php");?>



<div class="main-content">
    
    <div class="welcome-banner mb-5 d-flex justify-content-between align-items-center flex-wrap gap-4">
        <div>
            <span class="badge bg-white bg-opacity-10 text-white px-3 py-2 rounded-pill mb-3 text-uppercase font-monospace" style="font-size: 0.75rem; letter-spacing: 1px;">Painel de Performance</span>
            <h1 class="fw-bold tracking-tight mb-2">Seus resultados estão voando alto.</h1>
            <p class="text-white-50 mb-0 fs-5">Você atingiu 84% da meta de planos de saúde do estado este mês.</p>
        </div>
        <div>
            <button class="btn btn-light fw-semibold px-4 py-3 rounded-3 shadow-sm border-0" onclick="abrirNovoLead()" style="color: var(--primary-premium); transition: var(--transicao);">
                <i class="fa-solid fa-plus me-2"></i> Nova Cotação
            </button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4">
            <div class="card-premium">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-uppercase font-monospace text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Leads Ativos</span>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1" style="font-size: 0.75rem;">+12% <i class="fa-solid fa-arrow-up ms-1"></i></span>
                </div>
                <h2 class="fw-bold text-dark display-6 mb-1">142</h2>
                <p class="text-muted small mb-0 d-flex align-items-center"><i class="fa-brands fa-whatsapp text-success me-1 fs-6"></i> Aguardando contato direto</p>
            </div>
        </div>
        
        <div class="col-12 col-md-4">
            <div class="card-premium">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-uppercase font-monospace text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Vendas do Mês</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1" style="font-size: 0.75rem;">Planos & Seguros</span>
                </div>
                <h2 class="fw-bold text-dark display-6 mb-1">R$ 48.500</h2>
                <p class="text-muted small mb-0">Em comissões estimadas em carteira</p>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card-premium">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="text-uppercase font-monospace text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">Taxa de Conversão</span>
                </div>
                <h2 class="fw-bold text-dark display-6 mb-1">24.8%</h2>
                <div class="progress mt-3" style="height: 6px; background-color: #f1f5f9; border-radius: 10px;">
                    <div class="progress-bar bg-primary rounded-pill" style="width: 24.8%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-premium">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-1">Últimas Solicitações de Planos</h5>
                <p class="text-muted small mb-0">Visão em tempo real das triagens de leads de Pernambuco.</p>
            </div>
            <button class="btn btn-link btn-sm text-decoration-none text-primary fw-semibold p-0">Ver todos os registros <i class="fa-solid fa-arrow-right ms-1"></i></button>
        </div>
        
        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0 table-premium">
                <thead>
                    <tr>
                        <th>Cliente Proponente</th>
                        <th>Produto / Operadora</th>
                        <th>Status Operacional</th>
                        <th class="text-end">Ação Comercial</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle fw-bold d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    MA
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">Matheus Andrade</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">Recife, PE</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-medium text-dark">Amil Saúde Individual</div>
                            <div class="text-muted" style="font-size: 0.8rem;">Coletivo por adesão</div>
                        </td>
                        <td>
                            <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-2 fw-medium" style="font-size: 0.75rem;">Aguardando Retorno</span>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-light btn-sm rounded-3 px-3 py-2 fw-medium border border-light" onclick="dispararWhatsapp('Matheus')">
                                <i class="fa-brands fa-whatsapp text-success me-1"></i> Chamar no Whats
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include ("includes/footer.php");?>