<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito | OPX Investimentos</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-premium: #0f172a;        /* Preto / Grafite Escuro */
            --primary-orange: #ea580c;    /* Laranja Comercial */
            --primary-hover: #c2410c;     /* Laranja Dark para foco */
            --text-main: #0f172a;         /* Cinza Escuro */
            --text-muted: #64748b;        /* Cinza Médio */
            --bg-light-gray: #f8fafc;     /* Cinza Claro */
            --transicao-suave: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            overflow: hidden;
        }

        .login-container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        .login-form-side {
            width: 40%;
            min-width: 450px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 5rem 4rem;
            z-index: 10;
            box-shadow: 10px 0 50px rgba(0,0,0,0.02);
        }

        .form-wrapper {
            margin: auto 0;
            width: 100%;
        }

        .login-visual-side {
            width: 60%;
            background: var(--bg-premium);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 6rem;
        }

        .parallax-bg-wrapper {
            position: absolute;
            top: -12%;
            left: -12%;
            width: 124%;
            height: 124%;
            pointer-events: none;
            will-change: transform;
        }

        /* Camada visual adaptada para Preto e Laranja */
        .parallax-layer-1 {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 80% 20%, rgba(234, 88, 12, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 20% 80%, rgba(100, 116, 139, 0.12) 0%, transparent 40%);
        }

        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .visual-content {
            position: relative;
            color: #ffffff;
            width: 100%;
            max-width: 640px;
            z-index: 5;
        }

        .form-floating {
            position: relative;
            width: 100%;
        }

        .form-floating > .form-control {
            height: 58px;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            transition: var(--transicao-suave);
            padding: 1rem 0.75rem;
            background-color: var(--bg-light-gray);
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary-orange) !important;
            box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1) !important;
            background-color: #ffffff;
        }

        .form-floating > .form-control:focus ~ label {
            color: var(--primary-orange) !important;
            opacity: 0.85;
        }

        /* Botão Premium Laranja Comercial */
        .btn-login-premium {
            background: var(--primary-orange);
            color: white !important;
            border: none;
            border-radius: 12px;
            padding: 0.9rem;
            font-weight: 500;
            transition: var(--transicao-suave);
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .btn-login-premium:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(234, 88, 12, 0.3);
        }

        .text-orange-premium {
            color: var(--primary-orange) !important;
        }

        .bg-orange-premium {
            background-color: var(--primary-orange) !important;
        }

        @media (max-width: 1024px) {
            .login-form-side { width: 100%; min-width: 100%; padding: 3rem 2rem; }
            .login-visual-side { display: none; }
        }
    </style>
</head>
<body>

<div class="login-container">
    
    <div class="login-form-side">
        <div class="d-flex align-items-center">
            <img src="img/logo.png">
        </div>

        <div class="form-wrapper">
            <div class="mb-4">
                <h2 class="fw-bold text-dark mb-2">Bem-vindo de volta</h2>
                <p class="text-muted">Insira suas credenciais para gerenciar suas cotações.</p>
            </div>

            <form id="formLogin" onsubmit="executarLogin(event)">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="nome@corretora.com" required>
                    <label for="email" class="text-muted">E-mail corporativo</label>
                </div>
                
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="Sua senha" required>
                    <label for="senha" class="text-muted">Senha de acesso</label>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="lembrarMe" name="lembrarMe">
                        <label class="form-check-label text-muted small" for="lembrarMe">Manter conectado</label>
                    </div>
                    <a href="#" class="text-primary text-decoration-none small fw-500" onclick="esqueciSenha()">Esqueceu a senha?</a>
                </div>

                <button type="submit" class="btn btn-login-premium">
                    Entrar no Painel <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </form>
        </div>

        <div class="pt-3 border-top" style="border-color: #f1f5f9 !important;">
            <p class="text-muted small mb-0">Suporte Técnico: <span class="text-dark fw-500">0800 700 4000</span></p>
        </div>
    </div>

    <div class="login-visual-side" id="visualCanvas">
        <div class="parallax-bg-wrapper" id="parallaxBg">
            <div class="parallax-layer-1"></div>
            <div class="grid-overlay"></div>
        </div>

        <div class="visual-content">
            <span class="badge bg-primary px-3 py-2 rounded-pill mb-4" style="font-weight: 500; font-size: 0.75rem; letter-spacing: 0.5px;">PRODUTIVIDADE ATIVA</span>
            <h1 class="display-6 fw-bold mb-3 text-white" style="line-height: 1.25; letter-spacing: -0.5px;">A plataforma inteligente para corretores de alta performance.</h1>
            <p class="text-white-50 mb-5 fs-5">Cruze dados de operadoras, gerencie leads e feche contratos de planos de saúde e seguros em minutos.</p>
            
            <div class="row g-4 border-top pt-4" style="border-color: rgba(255,255,255,0.1) !important;">
                <div class="col-6">
                    <div class="text-white-50 small mb-1">Cotações Hoje</div>
                    <div class="fs-2 fw-bold text-white" id="countCotacoes">0</div>
                </div>
                <div class="col-6">
                    <div class="text-white-50 small mb-1">Tempo Médio de Fechamento</div>
                    <div class="fs-2 fw-bold text-success">14<span class="fs-5 text-white-50 fw-normal"> min</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const LoginSwal = Swal.mixin({
        customClass: {
            popup: 'rounded-4 shadow-lg border-0 p-4',
            confirmButton: 'btn btn-primary px-4 py-2 rounded-3 fw-500',
            cancelButton: 'btn btn-light text-muted px-4 py-2 mx-2 rounded-3'
        },
        buttonsStyling: false
    });

    // REFEITO: Parallax de Alta Performance usando requestAnimationFrame
    const visualCanvas = document.getElementById('visualCanvas');
    const parallaxBg = document.getElementById('parallaxBg');
    let ticking = false;

    if(visualCanvas && parallaxBg) {
        visualCanvas.addEventListener('mousemove', (e) => {
            if (!ticking) {
                window.requestAnimationFrame(() => {
                    const width = visualCanvas.offsetWidth;
                    const height = visualCanvas.offsetHeight;
                    const mouseX = e.clientX - (visualCanvas.getBoundingClientRect().left + width / 2);
                    const mouseY = e.clientY - (height / 2);
                    
                    const moveX = (mouseX / width) * 25; 
                    const moveY = (mouseY / height) * 25;

                    parallaxBg.style.transform = `translate3d(${moveX}px, ${moveY}px, 0) scale(1.05)`;
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    function animarContador(id, valorFinal, duracao) {
        let elemento = document.getElementById(id);
        if(!elemento) return;
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
    
    document.addEventListener("DOMContentLoaded", () => {
        animarContador("countCotacoes", 3419, 1800);
    });

    function executarLogin(e) {
        e.preventDefault();
        
        LoginSwal.fire({
            title: 'Autenticando...',
            html: 'Validando suas credenciais com segurança.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const form = document.getElementById('formLogin');
        const dadosForm = new FormData(form);

        fetch('login-action.php', {
            method: 'POST',
            body: dadosForm
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor');
            }
            return response.json();
        })
        .then(data => {
            Swal.close();

            if (data.status === 'sucesso') {
                LoginSwal.fire({
                    icon: 'success',
                    title: 'Acesso Autorizado',
                    text: 'Direcionando para o seu Painel de Controle...',
                    showConfirmButton: false,
                    timer: 1200
                }).then(() => {
                    window.location.href = 'dashboard.php';
                });
            } else {
                LoginSwal.fire({
                    icon: 'error',
                    title: 'Falha no Acesso',
                    text: data.mensagem || 'Credenciais inválidas.',
                    confirmButtonText: 'Tentar Novamente'
                });
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Erro de autenticação:', error);
            
            LoginSwal.fire({
                icon: 'error',
                title: 'Erro de Comunicação',
                text: 'Não foi possível estabelecer contato com o servidor de banco de dados.',
                confirmButtonText: 'Entendido'
            });
        });
    }

    function esqueciSenha() {
        LoginSwal.fire({
            title: 'Recuperar Acesso',
            text: 'Insira seu e-mail corporativo para receber o link de redefinição.',
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
                    text: `Instruções enviadas para: ${result.value}`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    }
</script>
</body>
</html>