<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- CDN do Chart.js para renderizar os gráficos minimalistas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const DashboardSwal = Swal.mixin({
        customClass: {
            popup: 'rounded-4 shadow-lg border-0 p-4',
            confirmButton: 'btn btn-primary px-4 py-2 rounded-3 fw-500 mx-2',
            cancelButton: 'btn btn-light text-muted px-4 py-2 mx-2 rounded-3'
        },
        buttonsStyling: false
    });

    // Inicialização do Gráfico Premium Clean
    const ctx = document.getElementById('graficoPerformance');
    if(ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Propostas Fechadas (R$)',
                    data: [12000, 19000, 15000, 28000, 35000, 48500],
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.03)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4, // Curva suave na linha estilo Apple
                    pointRadius: 4,
                    pointBackgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { color: '#64748b' } }
                }
            }
        });
    }

    function abrirNovoLead() {
        DashboardSwal.fire({
            title: '<h3 class="fw-bold text-start mb-0 text-dark">Nova Cotação</h3>',
            html: `
                <div class="text-start mt-3">
                    <label class="form-label text-muted small fw-semibold">Nome do Proponente</label>
                    <input type="text" id="nome_cliente" class="form-control rounded-3 py-2 mb-3 border-light" placeholder="Ex: Maria Silva">
                    
                    <label class="form-label text-muted small fw-semibold">Tipo de Plano</label>
                    <select id="tipo_plano" class="form-select rounded-3 py-2 border-light">
                        <option value="Amil Saúde">Amil Saúde</option>
                        <option value="SulAmérica PME">SulAmérica PME</option>
                        <option value="Bradesco Saúde">Bradesco Saúde</option>
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Salvar Lead',
            cancelButtonText: 'Cancelar'
        });
    }

    function dispararWhatsapp(nome) {
        DashboardSwal.fire({
            icon: 'info',
            title: 'Abrir Conversa',
            text: `Direcionando você para o WhatsApp de ${nome}...`,
            timer: 1500,
            showConfirmButton: false
        });
    }
</script>
</body>
</html>