SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `-!db_name!-` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci ;
USE `-!db_name!-`;

-- -----------------------------------------------------
-- Table `-!db_name!-`.`-!table_menu_name!-`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `-!db_name!-`.`-!table_menu_name!-` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL ,
  `label` VARCHAR(100) NULL ,
  `link` VARCHAR(100) NULL ,
  `submenu_id` INT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_-!table_menu_name!-_-!table_menu_name!-` (`submenu_id` ASC) ,
  CONSTRAINT `fk_-!table_menu_name!-_-!table_menu_name!-`
    FOREIGN KEY (`submenu_id` )
    REFERENCES `-!db_name!-`.`-!table_menu_name!-` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
