SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `-!db_name!-` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci ;

-- -----------------------------------------------------
-- Table `-!db_name!-`.`tb_simplemenu_names`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `-!db_name!-`.`tb_simplemenu_names` (
  `name_id` INT NOT NULL AUTO_INCREMENT ,
  `menu_name` VARCHAR(100) NOT NULL ,
  `menu_template` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`name_id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `-!db_name!-`.`tb_simplemenu_itens`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `-!db_name!-`.`tb_simplemenu_itens` (
  `field_id` INT NOT NULL AUTO_INCREMENT ,
  `field_name` VARCHAR(100) NULL ,
  `field_label` VARCHAR(100) NULL ,
  `field_link` VARCHAR(100) NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `-!db_name!-`.`tb_simplemenu_names_itens`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `-!db_name!-`.`tb_simplemenu_names_itens` (
  `rel_name_id` INT NOT NULL ,
  `rel_field_id` INT NOT NULL ,
  PRIMARY KEY (`rel_name_id`, `rel_field_id`) ,
  INDEX `fk_tb_simplemenu_names_has_tb_simplemenu_itens_tb_simplemenu_` (`rel_name_id` ASC) ,
  INDEX `fk_tb_simplemenu_names_has_tb_simplemenu_itens_tb_simplemenu_1` (`rel_field_id` ASC) ,
  CONSTRAINT `fk_tb_simplemenu_names_has_tb_simplemenu_itens_tb_simplemenu_`
    FOREIGN KEY (`rel_name_id` )
    REFERENCES `-!db_name!-`.`tb_simplemenu_names` (`name_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_tb_simplemenu_names_has_tb_simplemenu_itens_tb_simplemenu_1`
    FOREIGN KEY (`rel_field_id` )
    REFERENCES `-!db_name!-`.`tb_simplemenu_itens` (`field_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Placeholder table for view `-!db_name!-`.`vw_simplemenu_data`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `-!db_name!-`.`vw_simplemenu_data` (`field_id` INT, `field_name` INT, `field_label` INT, `field_link` INT, `name_id` INT, `menu_name` INT, `menu_template` INT, `rel_name_id` INT, `rel_field_id` INT);

-- -----------------------------------------------------
-- View `-!db_name!-`.`vw_simplemenu_data`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `-!db_name!-`.`vw_simplemenu_data`;
CREATE  OR REPLACE VIEW `-!db_name!-`.`vw_simplemenu_data` AS
SELECT i. * , n. * , ni. *
FROM tb_simplemenu_names_itens ni
   INNER JOIN tb_simplemenu_names n ON ni.rel_name_id = n.name_id
   INNER JOIN tb_simplemenu_itens i ON ni.rel_field_id = i.field_id;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
