<!-- Bootstrap & SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Customização padrão do SweetAlert2 combinando com o Tema Premium
        const LoginSwal = Swal.mixin({
            customClass: {
                popup: 'rounded-4 shadow-lg border-0 p-4',
                confirmButton: 'btn btn-primary px-4 py-2 rounded-3 fw-500',
                cancelButton: 'btn btn-light text-muted px-4 py-2 mx-2 rounded-3'
            },
            buttonsStyling: false
        });

        // 1. EFEITO PARALLAX DINÂMICO (Mouse Interaction)
        const visualCanvas = document.getElementById('visualCanvas');
        const parallaxBg = document.getElementById('parallaxBg');

        if(visualCanvas && parallaxBg) {
            visualCanvas.addEventListener('mousemove', (e) => {
                const width = visualCanvas.offsetWidth;
                const height = visualCanvas.offsetHeight;
                
                // Calcula o deslocamento do mouse com base no centro da tela
                const mouseX = e.clientX - (visualCanvas.getBoundingClientRect().left + width / 2);
                const mouseY = e.clientY - (height / 2);
                
                // Suaviza o movimento reduzindo a força (fator de divisão)
                const moveX = (mouseX / width) * 25; 
                const moveY = (mouseY / height) * 25;

                parallaxBg.style.transform = `translate(${moveX}px, ${moveY}px) scale(1.05)`;
            });
        }

        // 2. CONTADOR PROGRESSIVO DE PERFORMANCE (UX Atrativa)
        function animarContador(id, valorFinal, duracao) {
            let elemento = document.getElementById(id);
            let valorInicial = 0;
            let passo = Math.ceil(valorFinal / (duracao / 16));
            
            let timer = setInterval(() => {
                valorInicial += passo;
                if (valorInicial >= valorFinal) {
                    elemento.innerText = valorFinal.toLocaleString();
                    clearInterval(timer);
                } else {
                    elemento.innerText = valorInicial.toLocaleString();
                }
            }, 16);
        }
        
        // Dispara a contagem assim que a página termina de carregar
        document.addEventListener("DOMContentLoaded", () => {
            animarContador("countCotacoes", 3419, 2000); // Exemplo de 3.419 cotações geradas hoje
        });

        // 3. EVENTO DE LOGIN COM INTERAÇÃO DO SWEETALERT2
        function executarLogin(e) {
            e.preventDefault();
            
            LoginSwal.fire({
                title: 'Autenticando...',
                html: 'Aguarde enquanto validamos suas credenciais criptografadas.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Simulação de requisição AJAX (substitua pelo seu backend PHP real)
            setTimeout(() => {
                Swal.close();
                
                LoginSwal.fire({
                    icon: 'success',
                    title: 'Acesso Autorizado',
                    text: 'Direcionando para o seu Painel de Controle...',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // Redireciona para o index.php (o dashboard que criamos antes)
                    window.location.href = 'index.php';
                });
            }, 1800);
        }

        // 4. MODAL AUXILIAR (Esqueci a Senha via SweetAlert)
        function esqueciSenha() {
            LoginSwal.fire({
                title: 'Recuperar Acesso',
                text: 'Insira seu e-mail corporativo cadastrado para receber as instruções de redefinição.',
                input: 'email',
                inputPlaceholder: 'nome@corretora.com',
                showCancelButton: true,
                confirmButtonText: 'Enviar Link',
                cancelButtonText: 'Voltar'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    LoginSwal.fire({
                        icon: 'success',
                        title: 'E-mail enviado!',
                        text: `As instruções foram enviadas para: ${result.value}`,
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
            });
        }
    </script>
</body>
</html>