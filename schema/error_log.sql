CREATE  TABLE IF NOT EXISTS `error_log` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
`server` ENUM('admin-01','front-01','front-02','front-11','front-21','dev-01','crmdev','crmbeta','crm-content','crm-schedule') NULL DEFAULT NULL ,
`time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
`level` VARCHAR(32) NOT NULL ,
`text` TEXT NULL DEFAULT NULL ,
`stack_trace` TEXT,
`md5` VARCHAR(32),
`coll` INT(11) UNSIGNED NULL DEFAULT NULL ,
`affiliate_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' ,
`site_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'id сайта, на котором произошла ошибка' ,
`from` ENUM('crm','cms','parser') NULL DEFAULT NULL ,
PRIMARY KEY (`id`) ,
INDEX `time` (`time` ASC) ,
INDEX `indx_server` (`server` ASC) ,
INDEX `md5` (`md5` ASC) )
ENGINE = InnoDB;