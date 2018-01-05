SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';
USE `{db_name}`;


-- -----------------------------------------------------
-- Table `{db_name}`.`{prefixo}_VISAO_CLIENTE_USERS`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `{db_name}`.`{prefixo}_VISAO_CLIENTE_USERS` (
  `CLIENTE_USER_ID` INT(11) NOT NULL AUTO_INCREMENT ,
  `LOGIN` VARCHAR(10) NULL DEFAULT NULL ,
  `SENHA` VARCHAR(10) NULL DEFAULT NULL ,
  `NOME_COMPLETO` VARCHAR(80) NULL DEFAULT NULL ,
  `NOME_SIMPLIFICADO` VARCHAR(40) NULL DEFAULT NULL ,
  PRIMARY KEY (`CLIENTE_USER_ID`) );


-- --------------------------------------------
-- Table `{db_name}`.`Estados Já Alimentada
-- --------------------------------------------
-- DROP TABLE IF EXISTS `Estados`;

CREATE TABLE IF NOT EXISTS `estados` (
  `id_estado` int(11) NOT NULL AUTO_INCREMENT,
  `estado` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `uf` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT IGNORE INTO `estados` (`id_estado`, `estado`, `uf`)
VALUES
	(1,'Rio de Janeiro','RJ'),
	(2,'S&atilde;o Paulo','SP'),
	(3,'Minas Gerais','MG'),
	(4,'Espirito Santo','ES'),
	(5,'Paran&aacute;','PR'),
	(6,'Santa Catarina','SC'),
	(7,'Rio Grande do Sul','RS'),
	(9,'Distrito Federal','DF'),
	(10,'Mato Grosso','MT'),
	(11,'Mato Grosso do Sul','MS'),
	(12,'Goi&aacute;s','GO'),
	(13,'Amazonas','AM'),
	(14,'Roraima','RR'),
	(15,'Amap&aacute;','AP'),
	(16,'Acre','AC'),
	(17,'Par&aacute;','PA'),
	(18,'Rondônia','RO'),
	(19,'Tocantins','TO'),
	(20,'Maranh&atilde;o','MA'),
	(21,'Piau&iacute;','PI'),
	(22,'Cear&aacute;','CE'),
	(23,'Rio Grande do Norte','RN'),
	(24,'Para&iacute;ba','PB'),
	(25,'Pernambuco','PE'),
	(26,'Alagoas','AL'),
	(27,'Sergipe','SE'),
	(28,'Bahia','BA');



-- DROP TABLE IF EXISTS `menu_principal`;
CREATE TABLE IF NOT EXISTS `{prefixo}_MAIN_MENU` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `link` varchar(100) DEFAULT NULL,
  `submenu_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_menu_principal_menu_principal` (`submenu_id`),
  CONSTRAINT `fk_menu_principal_menu_principal` FOREIGN KEY (`submenu_id`) REFERENCES `menu_principal` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
);


INSERT IGNORE INTO `{prefixo}_MAIN_MENU` (`id`, `name`, `label`, `link`, `submenu_id`)
VALUES
        (1,'menu_principal',NULL,NULL,NULL),
        (2,'principal','Tela Principal','#index/tela_principal',1),
        (3,'trabalhos','Trabalhos','#',1),
        (4,'clientes','Clientes','#',1),
        (5,'agencias','Agencias','#',1),
        (6,'contas','Contas','#',1),
        (7,'controle_financeiro','Controle Financeiro','#',1),
 
        -- Submenus de Trabalhos--
        (10,'tra_cad','Cadastro','#trabalhos/cadastro',3),
        (11,'tra_rel','Relatorio','#trabalhos/index',3),
       
        -- Submenus de Clientes--
        (20,'cli_cad','Cadastro','#clientes/cadastro',4),
        (21,'cli_rel','Relatorio','#clientes/index',4),

        -- Submenus de Agencias--
        (30,'cli_cad','Cadastro','#agencias/cadastro',5),
        (31,'cli_rel','Relatorio','#agencias/index',5),

        -- Submenus de Contas--
        (40,'con_cad','Cadastro','#contas/cadastro',6),
        (41,'con_rel','Relatorio','#contas/index',6),
         
        -- Submenus de controle_financeiro
        (50,'con_cad','Pagar ou Receber','#controle_financeiro/efetuar_operacao',7),
        (51,'con_rel','Relatorio','#controle_financeiro/index',7)
        ;

-- View sobre financeiro para tabela CAIXA

SET @bal= 0;
CREATE OR REPLACE VIEW {prefixo}_VW_CAIXA  AS
SELECT C.*,
IF(C.OPERACAO = '+', true, false) AS operation,
IF(C.OPERACAO = '+', C.VALOR, NULL) AS credit,
IF(C.OPERACAO = '-', C.VALOR, NULL) AS debit,
 C.`DATA` AS `date`,
C.DESCRICAO AS `description`,
T.ID_CLIENTE AS ID_CLIENTE,
T.ID_AGENCIA AS ID_AGENCIA
FROM H_CAIXA AS C
INNER JOIN {prefixo}_TRABALHOS AS T ON (T.ID = C.ID_TRABALHO);


CREATE OR REPLACE VIEW {prefixo}_VW_TRABALHOS AS
SELECT T.*,
Cl.CLIENTE AS NOME_CLIENTE,
A.AGENCIA AS NOME_AGENCIA
FROM H_TRABALHOS AS T
LEFT JOIN H_CLIENTES AS Cl ON (Cl.ID = T.ID_CLIENTE)
LEFT JOIN H_AGENCIAS AS A ON (A.ID = T.ID_AGENCIA);

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;