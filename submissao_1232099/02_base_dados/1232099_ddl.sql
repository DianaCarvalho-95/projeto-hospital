
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `display_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `passwrd` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `profile` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `identificador` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notas` varchar(160) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fotografia` varchar(180) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conteudos_publicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conteudos_publicos` (
  `chave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conteudo` text COLLATE utf8mb4_unicode_ci,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conteudos_publicos_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conteudos_publicos_itens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `seccao` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordem` int NOT NULL DEFAULT '0',
  `titulo` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtitulo` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `icone` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagem` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preco` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seccao_ordem` (`seccao`,`ordem`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documentacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_documento` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nome_documento` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `data_documento` date DEFAULT NULL,
  `data_validade` date DEFAULT NULL,
  `caminho_ficheiro` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `equipamento_id` int NOT NULL,
  `fornecedor_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipamento_id` (`equipamento_id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  CONSTRAINT `documentacao_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `documentacao_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=313 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `edificios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edificios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emprestimos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emprestimos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipamento_id` int NOT NULL,
  `servico_origem` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `servico_destino` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `data_emprestimo` date NOT NULL,
  `data_prevista_devolucao` date DEFAULT NULL,
  `data_devolucao` date DEFAULT NULL,
  `responsavel` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `equipamento_id` (`equipamento_id`),
  CONSTRAINT `emprestimos_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_inventario` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `designacao` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `marca` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero_serie` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fabricante` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_aquisicao` date DEFAULT NULL,
  `ano_fabrico` year DEFAULT NULL,
  `custo_aquisicao` decimal(10,2) DEFAULT NULL,
  `tipo_entrada` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `criticidade` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  `imagem_upload` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `localizacao_id` int DEFAULT NULL,
  `fornecedor_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_inventario` (`codigo_inventario`),
  KEY `localizacao_id` (`localizacao_id`),
  CONSTRAINT `equipamentos_ibfk_1` FOREIGN KEY (`localizacao_id`) REFERENCES `localizacoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eventos_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eventos_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_evento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `utilizador` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `perfil` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modulo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acao` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidade` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidade_id` int DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eventos_data` (`data_evento`),
  KEY `idx_eventos_modulo` (`modulo`),
  KEY `idx_eventos_utilizador` (`utilizador`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fornecedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fornecedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome_empresa` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `nif` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `morada` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pessoa_contacto` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telefone_contacto` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipo_fornecedor` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nif` (`nif`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `garantias_contratos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `garantias_contratos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipamento_id` int NOT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `existe_contrato` tinyint(1) DEFAULT '0',
  `tipo_contrato` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `entidade_responsavel` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `periodicidade` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  `caminho_ficheiro` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipamento_id` (`equipamento_id`),
  CONSTRAINT `garantias_contratos_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `imagens_equipamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `imagens_equipamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `imagem` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `localizacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `localizacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `edificio` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `piso` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `servico` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sala` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manuais_equipamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manuais_equipamentos` (
  `id_manual` int NOT NULL AUTO_INCREMENT,
  `id_equipamento` int NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_manual` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `idioma` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Português',
  `ficheiro` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `data_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  `observacoes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id_manual`),
  KEY `fk_manuais_equipamentos` (`id_equipamento`),
  CONSTRAINT `fk_manuais_equipamentos` FOREIGN KEY (`id_equipamento`) REFERENCES `equipamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manutencoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manutencoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipamento_id` int NOT NULL,
  `fornecedor_id` int DEFAULT NULL,
  `tipo_manutencao` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `data_manutencao` date NOT NULL,
  `proxima_manutencao` date DEFAULT NULL,
  `responsavel` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `descricao` text COLLATE utf8mb4_general_ci,
  `custo` decimal(10,2) DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `equipamento_id` (`equipamento_id`),
  KEY `fornecedor_id` (`fornecedor_id`),
  CONSTRAINT `manutencoes_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos` (`id`),
  CONSTRAINT `manutencoes_ibfk_2` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimentacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimentacoes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipamento_id` int NOT NULL,
  `local_origem` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `local_destino` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `data_movimentacao` date NOT NULL,
  `responsavel` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `motivo` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`),
  KEY `equipamento_id` (`equipamento_id`),
  CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `equipamentos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

