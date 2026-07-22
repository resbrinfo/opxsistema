-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 21/07/2026 às 21:10
-- Versão do servidor: 8.4.7
-- Versão do PHP: 8.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `egis_saude_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `administradoras`
--

DROP TABLE IF EXISTS `administradoras`;
CREATE TABLE IF NOT EXISTS `administradoras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'Ativo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `auto`
--

DROP TABLE IF EXISTS `auto`;
CREATE TABLE IF NOT EXISTS `auto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `corretor_id` int NOT NULL,
  `grade_comissao_id` int NOT NULL,
  `seguradora` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_apolice` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vigencia_inicio` date NOT NULL,
  `vigencia_fim` date NOT NULL,
  `placa` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chassi` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe_bonus` int DEFAULT '0',
  `parcelas` int DEFAULT '1',
  `forma_pagamento` enum('Cartão','Boleto','Débito') COLLATE utf8mb4_unicode_ci NOT NULL,
  `assistencia_24h` enum('Sim','Não') COLLATE utf8mb4_unicode_ci DEFAULT 'Sim',
  `franquia_tipo` enum('Normal','Reduzida') COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `valor_franquia` decimal(10,2) DEFAULT '0.00',
  `valor_terceiros` decimal(12,2) DEFAULT '0.00',
  `premio_total` decimal(12,2) NOT NULL,
  `premio_liquido` decimal(12,2) NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `corretor_id` (`corretor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `auto`
--

INSERT INTO `auto` (`id`, `cliente_id`, `corretor_id`, `grade_comissao_id`, `seguradora`, `num_apolice`, `vigencia_inicio`, `vigencia_fim`, `placa`, `chassi`, `classe_bonus`, `parcelas`, `forma_pagamento`, `assistencia_24h`, `franquia_tipo`, `valor_franquia`, `valor_terceiros`, `premio_total`, `premio_liquido`, `criado_em`) VALUES
(1, 2, 1, 0, 'PORTO', '1000232111', '2025-07-01', '2026-07-01', 'KKK-1Q8', 'ZZXCSASDASFZ', 10, 5, 'Boleto', 'Sim', 'Reduzida', 2350.00, 1200.00, 2350.00, 2350.00, '2026-06-24 00:12:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone_fixo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uf` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'Ativo',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `idx_cliente_nome` (`nome_completo`),
  KEY `idx_cliente_cidade` (`cidade`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome_completo`, `cpf`, `data_nascimento`, `email`, `telefone`, `telefone_fixo`, `endereco`, `complemento`, `numero`, `bairro`, `cidade`, `uf`, `cep`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, 'JORGE RICARDO GOMES DE LIMA', '034.251.034-74', '1979-05-15', 'resbrinfo@gmail.com', '(81) 98160-7907', '(81) 8160-7907', 'Rua Paraíba', NULL, '48', 'Jardim Brasil', 'Olinda', 'PE', '53290-160', 'Ativo', '2026-06-23 22:36:56', '2026-06-23 22:36:56'),
(2, 'Maria da Silva', '123.457.807-70', '1970-01-01', 'maria@email.com', '(81) 99999-9999', '(81) 3333-3333', 'Rua da Regeneração', NULL, '120', 'Água Fria', 'Recife', 'PE', '52120-300', 'Ativo', '2026-06-23 22:38:25', '2026-06-23 22:38:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `consorcio`
--

DROP TABLE IF EXISTS `consorcio`;
CREATE TABLE IF NOT EXISTS `consorcio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `corretor_id` int NOT NULL,
  `grade_comissao_id` int NOT NULL,
  `administradora` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produto` enum('Imóveis','Auto','Moto','Serviços') COLLATE utf8mb4_unicode_ci NOT NULL,
  `grupo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cota` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `credito` decimal(12,2) NOT NULL,
  `prazo` int NOT NULL,
  `tipo_tabela` enum('Normal','Reduzida') COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `lance` decimal(12,2) DEFAULT '0.00',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `corretor_id` (`corretor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `consorcio`
--

INSERT INTO `consorcio` (`id`, `cliente_id`, `corretor_id`, `grade_comissao_id`, `administradora`, `produto`, `grupo`, `cota`, `credito`, `prazo`, `tipo_tabela`, `lance`, `criado_em`) VALUES
(1, 2, 1, 0, 'embracon', 'Imóveis', '0001', '024', 150000.00, 120, 'Normal', 50000.00, '2026-06-24 00:13:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `corretores`
--

DROP TABLE IF EXISTS `corretores`;
CREATE TABLE IF NOT EXISTS `corretores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome_completo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fone_celular` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fone_fixo` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_cadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `banco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agencia` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conta` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pix` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grade_comissao_id` int DEFAULT NULL,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'Ativo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `fk_corretores_grade` (`grade_comissao_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `corretores`
--

INSERT INTO `corretores` (`id`, `nome_completo`, `cpf`, `fone_celular`, `fone_fixo`, `endereco`, `complemento`, `numero`, `bairro`, `cep`, `cidade`, `uf`, `email`, `data_cadastro`, `banco`, `agencia`, `conta`, `pix`, `grade_comissao_id`, `status`) VALUES
(1, 'Edgar Queiroz', '11234568134', '(81) 99999-9999', '(81) 3333-3333', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'edgar@email.com', '2026-06-24 00:09:47', 'itau', '0001', '123456-5', 'a', 1, 'Ativo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `dependentes`
--

DROP TABLE IF EXISTS `dependentes`;
CREATE TABLE IF NOT EXISTS `dependentes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_contrato` enum('Vida','Saúde') COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrato_id` int NOT NULL,
  `nome_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date NOT NULL,
  `grau_parentesco` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_dependente` decimal(10,2) NOT NULL DEFAULT '0.00',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `grades_comissao`
--

DROP TABLE IF EXISTS `grades_comissao`;
CREATE TABLE IF NOT EXISTS `grades_comissao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentual` decimal(5,2) NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `grades_comissao`
--

INSERT INTO `grades_comissao` (`id`, `nome`, `percentual`, `criado_em`) VALUES
(1, 'Ouro', 10.00, '2026-06-19 10:02:05'),
(2, 'Prata', 5.00, '2026-06-19 10:02:21'),
(3, 'Diamante', 20.00, '2026-06-19 10:02:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `grades_comissoes`
--

DROP TABLE IF EXISTS `grades_comissoes`;
CREATE TABLE IF NOT EXISTS `grades_comissoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome_grade` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operadora` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `administradora` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modalidade` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vigencia` date NOT NULL,
  `p1` decimal(5,2) DEFAULT '0.00',
  `p2` decimal(5,2) DEFAULT '0.00',
  `p3` decimal(5,2) DEFAULT '0.00',
  `p4` decimal(5,2) DEFAULT '0.00',
  `p5` decimal(5,2) DEFAULT '0.00',
  `p6` decimal(5,2) DEFAULT '0.00',
  `p7` decimal(5,2) DEFAULT '0.00',
  `p8` decimal(5,2) DEFAULT '0.00',
  `p9` decimal(5,2) DEFAULT '0.00',
  `p10` decimal(5,2) DEFAULT '0.00',
  `p11` decimal(5,2) DEFAULT '0.00',
  `p12` decimal(5,2) DEFAULT '0.00',
  `vitalicio_inicio` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Nenhuma parcela',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `leads`
--

DROP TABLE IF EXISTS `leads`;
CREATE TABLE IF NOT EXISTS `leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `produto_interesse` varchar(100) NOT NULL,
  `telefone` varchar(30) NOT NULL,
  `data_captura` datetime NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Novo Lead',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `leads`
--

INSERT INTO `leads` (`id`, `nome`, `email`, `produto_interesse`, `telefone`, `data_captura`, `status`) VALUES
(1, 'JORGE RICARDO GOMES DE LIMA', 'resbrinfo@gmail.com', 'consorcio_inteligente', '81981607907', '2026-06-27 11:52:03', 'Novo Lead'),
(2, 'JORGE RICARDO GOMES DE LIMA', 'resbrinfo@gmail.com', 'seguros_auto_moto', '81981607907', '2026-06-27 11:52:19', 'Proposta Enviada'),
(3, 'JORGE RICARDO GOMES DE LIMA', 'resbrinfo@gmail.com', 'saude_empresarial', '81981607907', '2026-06-27 12:01:20', 'Novo Lead'),
(4, 'JORGE RICARDO GOMES DE LIMA', 'resbrinfo@gmail.com', 'energia_renovavel', '81981607907', '2026-06-27 12:06:36', 'Novo Lead'),
(5, 'Maria Eduarda', 'mariaed@email.com', 'saude_empresarial', '81999999999999', '2026-06-28 10:38:00', 'Proposta Enviada');

-- --------------------------------------------------------

--
-- Estrutura para tabela `leads_notas`
--

DROP TABLE IF EXISTS `leads_notas`;
CREATE TABLE IF NOT EXISTS `leads_notas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lead_id` int NOT NULL,
  `nota` text NOT NULL,
  `data_registro` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `modalidades`
--

DROP TABLE IF EXISTS `modalidades`;
CREATE TABLE IF NOT EXISTS `modalidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `modalidades`
--

INSERT INTO `modalidades` (`id`, `nome`, `created_at`) VALUES
(1, 'PME (Empresarial)', '2026-07-07 12:07:10'),
(2, 'Adesão (Coletivo)', '2026-07-07 12:07:10'),
(3, 'PF (Individual / Familiar)', '2026-07-07 12:07:10');

-- --------------------------------------------------------

--
-- Estrutura para tabela `niveis_acesso`
--

DROP TABLE IF EXISTS `niveis_acesso`;
CREATE TABLE IF NOT EXISTS `niveis_acesso` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `niveis_acesso`
--

INSERT INTO `niveis_acesso` (`id`, `nome`, `descricao`, `criado_em`) VALUES
(1, 'master', 'Acesso total irrestrito a todas as configurações, logs e banco de dados.', '2026-06-15 13:26:14'),
(2, 'diretoria', 'Acesso amplo aos relatórios gerenciais, dashboards estratégicos e auditoria de vendas.', '2026-06-15 13:26:14'),
(3, 'financeiro', 'Acesso focado em controle de comissões, faturamento, conciliação e fluxos de caixa.', '2026-06-15 13:26:14'),
(4, 'colaborador', 'Equipe interna de suporte, administrativo e triagem de leads primários.', '2026-06-15 13:26:14'),
(5, 'corretor', 'Visão comercial focada estritamente nos próprios leads, clientes e simulação de propostas.', '2026-06-15 13:26:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `operadoras`
--

DROP TABLE IF EXISTS `operadoras`;
CREATE TABLE IF NOT EXISTS `operadoras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Ativo','Inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'Ativo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `plano_saude`
--

DROP TABLE IF EXISTS `plano_saude`;
CREATE TABLE IF NOT EXISTS `plano_saude` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `corretor_id` int NOT NULL,
  `grade_comissao_id` int NOT NULL,
  `plano` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_titular` decimal(10,2) NOT NULL,
  `tem_dependentes` enum('Sim','Não') COLLATE utf8mb4_unicode_ci DEFAULT 'Não',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `corretor_id` (`corretor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `plano_saude`
--

INSERT INTO `plano_saude` (`id`, `cliente_id`, `corretor_id`, `grade_comissao_id`, `plano`, `valor_titular`, `tem_dependentes`, `criado_em`) VALUES
(1, 2, 1, 0, 'Hapvida', 554.00, 'Não', '2026-06-24 00:10:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `staging_quiver_import`
--

DROP TABLE IF EXISTS `staging_quiver_import`;
CREATE TABLE IF NOT EXISTS `staging_quiver_import` (
  `id` int NOT NULL AUTO_INCREMENT,
  `proposta_apolice` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seguradora` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produto_ramo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `premio_liquido` decimal(10,2) DEFAULT '0.00',
  `comissao_rep_esperado` decimal(10,2) DEFAULT '0.00',
  `data_movimento` date NOT NULL,
  `origem_pdf` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_staging_doc` (`proposta_apolice`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `staging_quiver_import`
--

INSERT INTO `staging_quiver_import` (`id`, `proposta_apolice`, `cliente_nome`, `seguradora`, `produto_ramo`, `premio_liquido`, `comissao_rep_esperado`, `data_movimento`, `origem_pdf`, `criado_em`) VALUES
(1, '155308', 'FLAVIO CORREIA FONTNELES', 'ITAU CONSORCIO', 'CONSORCIO', 208480.00, 4169.60, '2025-09-19', 'PRODUCAO DETALHADO - EDGAR.pdf', '2026-06-15 15:23:14'),
(2, '160602', 'JOHNNY ANDRE MUNIZ DE', 'ITAU CONSORCIO', 'CONSORCIO', 127768.00, 5110.72, '2026-02-26', 'PRODUCAO DETALHADO - EDGAR.pdf', '2026-06-15 15:23:14'),
(3, '153453', 'MONIKE FREITAS MARQUES', 'ITAU CONSORCIO', 'CONSORCIO', 135746.00, 5429.84, '2025-07-30', 'PRODUCAO DETALHADO - EDGAR.pdf', '2026-06-15 15:23:14'),
(4, '158350', 'ΑNA ELVIRA CINTRA DE UZEDA LUNA', 'ALFA', 'AUTOMOVEIS', 974.53, 214.40, '2025-12-22', 'PRODUCAO DETALHADO - EDGAR.pdf', '2026-06-15 15:23:14'),
(5, '155246', 'CAMILA MARIA DIAS', 'ALFA', 'AUTOMOVEIS', -4564.05, -468.18, '2025-10-01', 'PRODUCAO DETALHADO - EDGAR.pdf', '2026-06-15 15:23:14'),
(6, '151291', 'ALLYSON DE SA PORTELA', 'ALLIANZ', 'AUTOMOVEIS', 5500.00, 764.58, '2025-06-02', 'PRODUCAO DETALHADO - EDGAR.pdf', '2026-06-15 15:23:14');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nivel_acesso_id` int NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo',
  `ultimo_login` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `nivel_acesso_id` (`nivel_acesso_id`),
  KEY `idx_usuario_email` (`email`),
  KEY `idx_usuario_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nivel_acesso_id`, `nome`, `email`, `senha`, `telefone`, `status`, `ultimo_login`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'Administrador Master', 'master@egissaude.com', '$2y$12$8IErnRaA1TAvNvNdw5ZuTOW0CpXvDqQmVuXqDw50AYkhq/L5l0b6y', NULL, 'ativo', '2026-07-13 15:10:51', '2026-06-15 13:26:15', '2026-07-13 18:10:51');

-- --------------------------------------------------------

--
-- Estrutura para tabela `vida`
--

DROP TABLE IF EXISTS `vida`;
CREATE TABLE IF NOT EXISTS `vida` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `corretor_id` int NOT NULL,
  `grade_comissao_id` int NOT NULL,
  `num_apolice` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plano` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_titular` decimal(10,2) NOT NULL,
  `tem_dependentes` enum('Sim','Não') COLLATE utf8mb4_unicode_ci DEFAULT 'Não',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `corretor_id` (`corretor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `auto`
--
ALTER TABLE `auto`
  ADD CONSTRAINT `auto_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auto_ibfk_2` FOREIGN KEY (`corretor_id`) REFERENCES `corretores` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `consorcio`
--
ALTER TABLE `consorcio`
  ADD CONSTRAINT `consorcio_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consorcio_ibfk_2` FOREIGN KEY (`corretor_id`) REFERENCES `corretores` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `corretores`
--
ALTER TABLE `corretores`
  ADD CONSTRAINT `fk_corretores_grade` FOREIGN KEY (`grade_comissao_id`) REFERENCES `grades_comissao` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `leads_notas`
--
ALTER TABLE `leads_notas`
  ADD CONSTRAINT `leads_notas_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `plano_saude`
--
ALTER TABLE `plano_saude`
  ADD CONSTRAINT `plano_saude_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `plano_saude_ibfk_2` FOREIGN KEY (`corretor_id`) REFERENCES `corretores` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`nivel_acesso_id`) REFERENCES `niveis_acesso` (`id`) ON DELETE RESTRICT;

--
-- Restrições para tabelas `vida`
--
ALTER TABLE `vida`
  ADD CONSTRAINT `vida_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vida_ibfk_2` FOREIGN KEY (`corretor_id`) REFERENCES `corretores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
