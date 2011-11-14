CREATE  TABLE IF NOT EXISTS `sotm_manufacturers_type` (
`type_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`type_name` VARCHAR(32) NOT NULL,
`type_image` VARCHAR(64)  ,
`sort_order` INT(11) NOT NULL DEFAULT '0',
`edited` TINYINT(4) NOT NULL DEFAULT '0',
`last_modified` DATETIME,
PRIMARY KEY (`type_id`),
INDEX `indx_last_modified` (`last_modified` ASC) )
ENGINE = InnoDB;