<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito | Seguros & Saúde Premium</title>
    
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 & SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-premium: #0f172a;
            --primary-blue: #2563eb;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transicao-suave: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            overflow: hidden;
        }

        /* Estrutura Split-Screen */
        .login-container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* Lado Esquerdo: Formulário Minimalista */
        .login-form-side {
            width: 40%;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 5rem;
            z-index: 10;
            box-shadow: 10px 0 50px rgba(0,0,0,0.02);
        }

        /* Lado Direito: Visual Imersivo & Parallax */
        .login-visual-side {
            width: 60%;
            background: var(--bg-premium);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Efeito Parallax de Fundo Dinâmico */
        .parallax-bg-wrapper {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 120%;
            height: 120%;
            pointer-events: none;
            will-change: transform;
        }

        .parallax-layer-1 {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 20% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 40%);
        }

        /* Linhas de malha elegantes (Grid Glass) */
        .grid-overlay {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Conteúdo Flutuante sobre o Parallax */
        .visual-content {
            position: relative;
            color: #ffffff;
            max-width: 600px;
            padding: 2rem;
            text-align: left;
            z-index: 5;
        }

        /* Inputs Premium Clean */
        .form-floating > .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: var(--transicao-suave);
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        /* Botão de Entrada */
        .btn-login-premium {
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.85rem;
            font-weight: 500;
            transition: var(--transicao-suave);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-login-premium:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
            color: white;
        }

        /* Responsividade para telas menores */
        @media (max-width: 1024px) {
            .login-form-side { width: 100%; padding: 3rem; }
            .login-visual-side { display: none; }
        }
    </style>
</head>
<body>