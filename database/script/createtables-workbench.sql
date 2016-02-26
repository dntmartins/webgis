SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `GEODATA` DEFAULT CHARACTER SET latin1 ;
USE `GEODATA` ;

-- -----------------------------------------------------
-- Table `GEODATA`.`project`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`project` (
  `prj_id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_name` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `logo` VARCHAR(100) NULL,
  `link` VARCHAR(45) NOT NULL,
  `active` tinyint NOT NULL DEFAULT '1',
  `publicacao_oficial` DATE NULL,
  PRIMARY KEY (`prj_id`))
ENGINE = InnoDB
AUTO_INCREMENT = 0
DEFAULT CHARACTER SET = utf8;

-- -----------------------------------------------------
-- Table `GEODATA`.`geoserver`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`geoserver` (
  `geoserver_id` INT NOT NULL AUTO_INCREMENT,
  `prj_id` INT(11) NULL,
  `login` VARCHAR(255) NOT NULL,
  `pass` VARCHAR(255) NOT NULL,
  `host` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`geoserver_id`),
  INDEX `fk_prj_1_idx` (`prj_id` ASC),
  CONSTRAINT `fk_prj_1`
    FOREIGN KEY (`prj_id`)
    REFERENCES `GEODATA`.`project` (`prj_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `GEODATA`.`role`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`role` (
  `rol_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rol_id`))
ENGINE = InnoDB
AUTO_INCREMENT = 11
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `GEODATA`.`user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`user` (
  `use_id` INT(11) NOT NULL AUTO_INCREMENT,
  `rol_id` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `last_access` DATETIME NULL DEFAULT NULL,
  `reset_token` VARCHAR(255) NULL DEFAULT NULL,
  `active` tinyint DEFAULT 1,
  PRIMARY KEY (`use_id`),
  INDEX `role_user_fk` (`rol_id` ASC),
  CONSTRAINT `role_user_fk`
    FOREIGN KEY (`rol_id`)
    REFERENCES `GEODATA`.`role` (`rol_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 0
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `GEODATA`.`access`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`access` (
  `acc_id` INT(11) NOT NULL AUTO_INCREMENT,
  `prj_id` INT(11) NOT NULL,
  `use_id` INT(11) NOT NULL,
  PRIMARY KEY (`acc_id`),
  INDEX `access_project_fk` (`prj_id` ASC),
  INDEX `access_user_fk` (`use_id` ASC),
  CONSTRAINT `access_project_fk`
    FOREIGN KEY (`prj_id`)
    REFERENCES `GEODATA`.`project` (`prj_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `access_user_fk`
    FOREIGN KEY (`use_id`)
    REFERENCES `GEODATA`.`user` (`use_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 0
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `GEODATA`.`shapefile`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`shapefile` (
  `shape_id` INT(11) NOT NULL AUTO_INCREMENT,
  `prj_id` INT(11) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_extension` VARCHAR(4) NOT NULL,
  `disk_location` VARCHAR(255) NOT NULL,
  `upload_date` DATETIME NOT NULL,
  `info` TEXT NULL,
  PRIMARY KEY (`shape_id`),
  INDEX `prj_shapefile_fk` (`prj_id` ASC),
  CONSTRAINT `prj_shapefile_fk`
    FOREIGN KEY (`prj_id`)
    REFERENCES `GEODATA`.`project` (`prj_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 0
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `GEODATA`.`resource`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`resource` (
  `res_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`res_id`))
ENGINE = InnoDB
AUTO_INCREMENT = 0
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `GEODATA`.`privilege`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`privilege` (
  `pri_id` INT(11) NOT NULL AUTO_INCREMENT,
  `res_id` INT(11) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`pri_id`),
  INDEX `resource_privilege_fk` (`res_id` ASC),
  CONSTRAINT `resource_privilege_fk`
    FOREIGN KEY (`res_id`)
    REFERENCES `GEODATA`.`resource` (`res_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 0
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `GEODATA`.`role_privilege`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`role_privilege` (
  `rolPriId` INT(11) NOT NULL AUTO_INCREMENT,
  `rol_id` INT(11) NOT NULL,
  `pri_id` INT(11) NOT NULL,
  PRIMARY KEY (`rolPriId`),
  INDEX `fk_role_id` (`rol_id` ASC),
  INDEX `fk_pri_id` (`pri_id` ASC),
  CONSTRAINT `fk_pri_id`
    FOREIGN KEY (`pri_id`)
    REFERENCES `GEODATA`.`privilege` (`pri_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_role_id`
    FOREIGN KEY (`rol_id`)
    REFERENCES `GEODATA`.`role` (`rol_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 0
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `GEODATA`.`datasource`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`datasource` (
  `data_id` INT NOT NULL  AUTO_INCREMENT,
  `db_name` VARCHAR(45) NOT NULL,
  `host` VARCHAR(45) NOT NULL,
  `port` INT NOT NULL,
  `login` VARCHAR(255) NOT NULL,
  `password` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`data_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `GEODATA`.`layer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`layer` (
  `layer_id` INT NOT NULL AUTO_INCREMENT,
  `sld_id` INT,
  `prj_id` INT(11) NOT NULL,
  `datasource_id` INT(11) NOT NULL,
  `official` TINYINT(1) NOT NULL,
  `publicacao_oficial` DATETIME NULL,
  `projection` INT NOT NULL,
  PRIMARY KEY (`layer_id`),
  INDEX `fk_layer_project1_idx` (`prj_id` ASC),
  INDEX `fk_layer_datasource1_idx` (`datasource_id` ASC),
  CONSTRAINT `fk_layer_project1`
    FOREIGN KEY (`prj_id`)
    REFERENCES `GEODATA`.`project` (`prj_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_layer_datasource1`
    FOREIGN KEY (`datasource_id`)
    REFERENCES `GEODATA`.`datasource` (`data_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
CONSTRAINT `fk_layer_sld`
    FOREIGN KEY (`sld_id`)
    REFERENCES `GEODATA`.`sld` (`sld_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `GEODATA`.`sld`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GEODATA`.`sld` (
  `sld_id` INT NOT NULL AUTO_INCREMENT,
  `sld_name` VARCHAR(255) NOT NULL,
  `disk_location` VARCHAR(255) NOT NULL,
  `sld_date` DATETIME NOT NULL,
  `registered` TINYINT(1) NOT NULL,
  `admin_uploaded` TINYINT(1) NOT NULL,
  PRIMARY KEY (`sld_id`)
)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
