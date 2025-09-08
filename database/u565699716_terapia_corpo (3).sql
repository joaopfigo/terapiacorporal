-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/09/2025 às 06:21
-- Versão do servidor: 10.11.10-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u565699716_terapia_corpo`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendamentos`
--

CREATE TABLE `agendamentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `nome_visitante` varchar(100) DEFAULT NULL,
  `email_visitante` varchar(100) DEFAULT NULL,
  `telefone_visitante` varchar(20) DEFAULT NULL,
  `idade_visitante` int(11) DEFAULT NULL,
  `especialidade_id` int(11) DEFAULT NULL,
  `data_horario` datetime NOT NULL,
  `duracao` int(11) NOT NULL,
  `adicional_reflexo` tinyint(1) DEFAULT 0,
  `status` varchar(20) DEFAULT 'Pendente',
  `criado_em` datetime DEFAULT current_timestamp(),
  `preco_final` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `agendamentos`
--

INSERT INTO `agendamentos` (`id`, `usuario_id`, `nome_visitante`, `email_visitante`, `telefone_visitante`, `idade_visitante`, `especialidade_id`, `data_horario`, `duracao`, `adicional_reflexo`, `status`, `criado_em`, `preco_final`) VALUES
(1, 10, NULL, NULL, NULL, NULL, 3, '2025-09-01 09:00:00', 90, 0, 'Pendente', '2025-09-08 06:19:31', 200.00),
(2, 10, NULL, NULL, NULL, NULL, 3, '2025-09-01 09:00:00', 90, 0, 'Pendente', '2025-09-08 06:19:51', 200.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `anamneses`
--

CREATE TABLE `anamneses` (
  `id` int(11) NOT NULL,
  `agendamento_id` int(11) NOT NULL,
  `anamnese` text DEFAULT NULL,
  `data_escrita` datetime DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `anamneses`
--

INSERT INTO `anamneses` (`id`, `agendamento_id`, `anamnese`, `data_escrita`, `updated_at`) VALUES
(1, 1, '', '2025-09-08 06:19:31', NULL),
(2, 2, '', '2025-09-08 06:19:51', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo` text NOT NULL,
  `data_post` date DEFAULT NULL,
  `imagem` varchar(400) DEFAULT NULL,
  `categoria` varchar(80) DEFAULT NULL,
  `publicado` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `titulo`, `conteudo`, `data_post`, `imagem`, `categoria`, `publicado`, `criado_em`) VALUES
(1, '10 bons motivos para receber uma massagem', '<p>Receber uma massagem profissional tem múltiplos benefícios para a saúde do corpo e da mente. Veja os principais:</p>\r\n<ul>\r\n<li>Alivia dores musculares e tensões</li>\r\n<li>Melhora a circulação sanguínea</li>\r\n<li>Reduz ansiedade e estresse</li>\r\n<li>Proporciona sensação de bem-estar e relaxamento profundo</li>\r\n<li>Fortalece o sistema imunológico</li>\r\n<li>Ajuda no combate à insônia</li>\r\n<li>Melhora a flexibilidade</li>\r\n<li>Auxilia na eliminação de toxinas</li>\r\n<li>Promove autoconhecimento e conexão corpo-mente</li>\r\n<li>Previne lesões e acelera a recuperação pós-exercício</li>\r\n</ul>', '2025-06-04', 'https://images.pexels.com/photos/161599/massage-therapy-acupuncture-161599.jpeg?auto=compress&w=600&h=400&fit=crop', 'Massoterapia', 0, '2025-06-04 14:43:19'),
(2, '10 Motivos para receber massagem', '<p>Receber massagem regularmente oferece inúmeros benefícios para o corpo e a mente. Veja 10 motivos para agendar sua sessão:</p>\r\n<ul>\r\n  <li>Alívio das dores musculares e tensões</li>\r\n  <li>Melhora significativa da circulação sanguínea</li>\r\n  <li>Redução comprovada do estresse e ansiedade</li>\r\n  <li>Promoção do relaxamento profundo e bem-estar</li>\r\n  <li>Fortalecimento do sistema imunológico</li>\r\n  <li>Auxílio no combate à insônia e na qualidade do sono</li>\r\n  <li>Melhora da flexibilidade e mobilidade articular</li>\r\n  <li>Eliminação de toxinas acumuladas no organismo</li>\r\n  <li>Estímulo ao autoconhecimento corporal e mental</li>\r\n  <li>Prevenção de lesões e recuperação mais rápida após exercícios físicos</li>\r\n</ul>', '2025-06-04', 'https://images.pexels.com/photos/3997985/pexels-photo-3997985.jpeg?auto=compress&w=600&h=400&fit=crop', 'Massoterapia', 0, '2025-06-04 14:43:19'),
(3, 'Benefícios da Massagem Tailandesa', '<p>A massagem tailandesa combina pressão, alongamento e movimentos de yoga passiva, trazendo benefícios como:</p>\r\n<ul>\r\n<li>Melhora da flexibilidade</li>\r\n<li>Redução do estresse</li>\r\n<li>Alívio de dores e tensões musculares</li>\r\n<li>Estímulo da circulação sanguínea</li>\r\n<li>Equilíbrio energético</li>\r\n</ul>', '2025-06-04', 'https://images.pexels.com/photos/3182811/pexels-photo-3182811.jpeg?auto=compress&w=600&h=400&fit=crop', 'Massoterapia', 1, '2025-06-04 14:43:19'),
(4, 'Massagem como tratamento para ansiedade', '<p>A ansiedade afeta corpo e mente. Massagem ajuda:</p>\r\n<ul>\r\n<li>Reduzindo níveis de cortisol</li>\r\n<li>Relaxando músculos tensos</li>\r\n<li>Promovendo sensação de segurança</li>\r\n<li>Facilitando o sono reparador</li>\r\n<li>Equilibrando as emoções</li>\r\n</ul>\r\n<p>Procure sempre profissionais qualificados para um tratamento eficaz e seguro.</p>', '2025-06-04', 'https://images.pexels.com/photos/1135747/pexels-photo-1135747.jpeg?auto=compress&w=600&h=400&fit=crop', 'Técnicas', 1, '2025-06-04 14:43:19'),
(5, '5 Benefícios da Massagem', '<ol>\r\n<li>Relaxamento físico e mental</li>\r\n<li>Melhora da circulação sanguínea</li>\r\n<li>Auxílio na recuperação de lesões</li>\r\n<li>Alívio do estresse e da ansiedade</li>\r\n<li>Promoção do bem-estar geral</li>\r\n</ol>\r\n<p>Inclua a massagem na sua rotina e sinta a diferença no seu dia a dia!</p>', '2025-06-04', 'https://images.pexels.com/photos/3757942/pexels-photo-3757942.jpeg?auto=compress&w=600&h=400&fit=crop', 'Dicas', 1, '2025-06-04 14:43:19'),
(6, 'Ejaculação precoce e a massagem lingam', '<p>A massagem lingam trabalha energia sexual, controle e consciência corporal. Benefícios incluem:</p>\r\n<ul>\r\n<li>Melhora do controle da ejaculação</li>\r\n<li>Aumento do autoconhecimento</li>\r\n<li>Redução da ansiedade sexual</li>\r\n<li>Promoção do prazer consciente</li>\r\n</ul>', '2025-06-04', 'https://images.pexels.com/photos/1181736/pexels-photo-1181736.jpeg?auto=compress&w=600&h=400&fit=crop', 'Massoterapia', 1, '2025-06-04 14:43:19'),
(7, 'Teste', '<p>Testand a publicacao</p>', '2025-06-19', NULL, 'Massoterapia', 1, '2025-06-19 21:18:49'),
(8, 'teste', '<p>boa noite, teste</p>', '2025-06-29', NULL, 'Massoterapia', 1, '2025-06-29 23:54:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `especialidades`
--

CREATE TABLE `especialidades` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT 1,
  `quick` tinyint(1) NOT NULL DEFAULT 0,
  `preco_15` decimal(10,2) NOT NULL DEFAULT 0.00,
  `preco_30` decimal(10,2) NOT NULL DEFAULT 0.00,
  `preco_50` decimal(10,2) NOT NULL DEFAULT 0.00,
  `preco_90` decimal(10,2) NOT NULL DEFAULT 0.00,
  `preco_escalda` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pacote5` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pacote10` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `especialidades`
--

INSERT INTO `especialidades` (`id`, `nome`, `descricao`, `ativa`, `quick`, `preco_15`, `preco_30`, `preco_50`, `preco_90`, `preco_escalda`, `pacote5`, `pacote10`) VALUES
(1, 'Quick Massage', 'Massagem rápida', 1, 1, 50.00, 30.00, 0.00, 0.00, 0.00, 0.00, 0.00),
(2, 'Massoterapia', 'Massagem terapêutica', 1, 0, 0.00, 0.00, 150.00, 200.00, 0.00, 0.00, 0.00),
(3, 'Reflexologia Podal', 'Massagem nos pés', 1, 0, 0.00, 0.00, 150.00, 200.00, 0.00, 0.00, 0.00),
(4, 'Auriculoterapia', 'Terapia na orelha', 1, 0, 0.00, 0.00, 150.00, 200.00, 0.00, 0.00, 0.00),
(5, 'Ventosa', 'Terapia com ventosas', 1, 0, 0.00, 0.00, 150.00, 200.00, 0.00, 0.00, 0.00),
(6, 'Acupuntura', 'Terapia com agulhas', 1, 0, 0.00, 0.00, 150.00, 200.00, 0.00, 0.00, 0.00),
(7, 'Biomagnetismo', 'Terapia com ímãs', 1, 0, 0.00, 0.00, 150.00, 200.00, 0.00, 0.00, 0.00),
(8, 'Reiki', 'Energia vital', 1, 0, 0.00, 0.00, 150.00, 200.00, 0.00, 0.00, 0.00),
(9, 'Escalda Pés', 'Escalda Pés', 1, 0, 0.00, 0.00, 0.00, 0.00, 60.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `formularios_queixa`
--

CREATE TABLE `formularios_queixa` (
  `agendamento_id` int(11) NOT NULL,
  `desconforto_principal` text DEFAULT NULL,
  `queixa_secundaria` text DEFAULT NULL,
  `tempo_desconforto` varchar(100) DEFAULT NULL,
  `classificacao_dor` varchar(50) DEFAULT NULL,
  `tratamento_medico` text DEFAULT NULL,
  `em_cuidados_medicos` tinyint(1) DEFAULT NULL,
  `medicacao` tinyint(1) DEFAULT NULL,
  `gravida` tinyint(1) DEFAULT NULL,
  `lesao` tinyint(1) DEFAULT NULL,
  `torcicolo` tinyint(1) DEFAULT NULL,
  `dor_coluna` tinyint(1) DEFAULT NULL,
  `caimbras` tinyint(1) DEFAULT NULL,
  `distensoes` tinyint(1) DEFAULT NULL,
  `fraturas` tinyint(1) DEFAULT NULL,
  `edemas` tinyint(1) DEFAULT NULL,
  `outras_dores` tinyint(1) DEFAULT NULL,
  `cirurgias` tinyint(1) DEFAULT NULL,
  `prob_pele` tinyint(1) DEFAULT NULL,
  `digestivo` tinyint(1) DEFAULT NULL,
  `intestino` tinyint(1) DEFAULT NULL,
  `prisao_ventre` tinyint(1) DEFAULT NULL,
  `circulacao` tinyint(1) DEFAULT NULL,
  `trombose` tinyint(1) DEFAULT NULL,
  `cardiaco` tinyint(1) DEFAULT NULL,
  `pressao` tinyint(1) DEFAULT NULL,
  `artrite` tinyint(1) DEFAULT NULL,
  `asma` tinyint(1) DEFAULT NULL,
  `alergia` tinyint(1) DEFAULT NULL,
  `rinite` tinyint(1) DEFAULT NULL,
  `diabetes` tinyint(1) DEFAULT NULL,
  `colesterol` tinyint(1) DEFAULT NULL,
  `epilepsia` tinyint(1) DEFAULT NULL,
  `osteoporose` tinyint(1) DEFAULT NULL,
  `cancer` tinyint(1) DEFAULT NULL,
  `contagiosa` tinyint(1) DEFAULT NULL,
  `sono` tinyint(1) DEFAULT NULL,
  `ansiedade` tinyint(1) DEFAULT NULL,
  `tristeza` tinyint(1) DEFAULT NULL,
  `raiva` tinyint(1) DEFAULT NULL,
  `preocupacao` tinyint(1) DEFAULT NULL,
  `medo` tinyint(1) DEFAULT NULL,
  `irritacao` tinyint(1) DEFAULT NULL,
  `angustia` tinyint(1) DEFAULT NULL,
  `termo_aceite` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacotes`
--

CREATE TABLE `pacotes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `total_sessoes` int(11) NOT NULL,
  `sessoes_usadas` int(11) DEFAULT 0,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pacotes`
--

INSERT INTO `pacotes` (`id`, `usuario_id`, `total_sessoes`, `sessoes_usadas`, `criado_em`) VALUES
(1, 12, 5, 0, '2025-06-04 18:41:45'),
(2, 13, 5, 0, '2025-06-09 00:04:00'),
(3, 13, 5, 0, '2025-06-09 00:05:01'),
(4, 13, 5, 0, '2025-06-09 00:05:11'),
(5, 11, 5, 0, '2025-06-19 22:53:17'),
(6, 12, 5, 0, '2025-06-28 23:03:20'),
(7, 12, 5, 0, '2025-06-28 23:03:29'),
(8, 11, 5, 0, '2025-06-29 23:50:48');

-- --------------------------------------------------------

--
-- Estrutura para tabela `uso_pacote`
--

CREATE TABLE `uso_pacote` (
  `id` int(11) NOT NULL,
  `pacote_id` int(11) NOT NULL,
  `agendamento_id` int(11) NOT NULL,
  `data_uso` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `nascimento` date DEFAULT NULL,
  `idade` int(11) DEFAULT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `criado_em` datetime DEFAULT current_timestamp(),
  `sexo` varchar(20) DEFAULT NULL,
  `token_recuperacao` varchar(100) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `telefone`, `nascimento`, `idade`, `senha_hash`, `foto_perfil`, `is_admin`, `criado_em`, `sexo`, `token_recuperacao`, `token_expira`) VALUES
(10, 'João Pedro Figueiredo', 'joaopedrofvalle@gmail.com', '+5531999654279', NULL, 20, '$2y$10$YwHvqjSUPVzVs.izKgAOieti8pkquRcwDfUeGFQ77ozECvKcJQq72', 'uploads/perfil_10_1748982421.jpg', 0, '2025-06-03 20:21:28', 'masculino', NULL, NULL),
(11, 'Virlene', 'virleneterapiacorporal@gmail.com', '31999444203', '1963-08-17', 60, '$2y$10$tfR0IzXwR7lMrQBjrd8qA.ugPRBykw7jpSHJBHjgZzsi.YQx/fJgG', NULL, 1, '2025-06-04 02:40:28', 'feminino', NULL, NULL),
(12, 'Lara Ruas', 'laramrrf@hotmail.com', '31999544554', NULL, 24, '$2y$10$9ZmyLGXStQw3/jR2qF03H.Eff7Ln8UOXmyxxyrCXNFYDY/ZNrY8Vm', NULL, 0, '2025-06-04 18:41:29', 'feminino', NULL, NULL),
(13, 'SAMUEL OLIVEIRA SOARES', 'samueloliveirasoares11@gmail.com', '319953364', '2002-03-11', 23, '$2y$10$/cBHq7vAzX/UiANHvlphy..MHxilyB.1PU28KP5NBJEF/Lh2.CnIC', NULL, 0, '2025-06-08 21:51:17', 'Masculino', '98abcf80389c03d04093963d6b092c64059ddcd338e13364161cb9c43406', '2025-06-09 00:18:57');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_usuario` (`usuario_id`),
  ADD KEY `fk_servico` (`especialidade_id`);

--
-- Índices de tabela `anamneses`
--
ALTER TABLE `anamneses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agendamento_unico` (`agendamento_id`);

--
-- Índices de tabela `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `formularios_queixa`
--
ALTER TABLE `formularios_queixa`
  ADD PRIMARY KEY (`agendamento_id`);

--
-- Índices de tabela `pacotes`
--
ALTER TABLE `pacotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `uso_pacote`
--
ALTER TABLE `uso_pacote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pacote_id` (`pacote_id`),
  ADD KEY `agendamento_id` (`agendamento_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendamentos`
--
ALTER TABLE `agendamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `anamneses`
--
ALTER TABLE `anamneses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `pacotes`
--
ALTER TABLE `pacotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `uso_pacote`
--
ALTER TABLE `uso_pacote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agendamentos`
--
ALTER TABLE `agendamentos`
  ADD CONSTRAINT `fk_servico` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`id`),
  ADD CONSTRAINT `fk_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `anamneses`
--
ALTER TABLE `anamneses`
  ADD CONSTRAINT `fk_anamneses_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `formularios_queixa`
--
ALTER TABLE `formularios_queixa`
  ADD CONSTRAINT `fk_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`);

--
-- Restrições para tabelas `pacotes`
--
ALTER TABLE `pacotes`
  ADD CONSTRAINT `pacotes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `uso_pacote`
--
ALTER TABLE `uso_pacote`
  ADD CONSTRAINT `uso_pacote_ibfk_1` FOREIGN KEY (`pacote_id`) REFERENCES `pacotes` (`id`),
  ADD CONSTRAINT `uso_pacote_ibfk_2` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
