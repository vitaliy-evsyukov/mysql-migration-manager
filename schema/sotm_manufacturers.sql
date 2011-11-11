CREATE  TABLE IF NOT EXISTS `sotm_manufacturers` (
`manufacturers_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`manufacturers_name` VARCHAR(32) NOT NULL,
`manufacters_name_seo` VARCHAR(50) NOT NULL COMMENT 'Seo название: Напрмиер Sony-Ericsson может быть SonyEricsson',
`manufacturers_image` VARCHAR(64),
`uri` VARCHAR(255) NOT NULL DEFAULT '',
`type` INT(11) UNSIGNED NULL DEFAULT '3',
`is_popular` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак популярности',
`edited` TINYINT(1) NOT NULL DEFAULT '0',
`is_deleted` TINYINT(1) NULL DEFAULT '0',
`status` INT(11) NOT NULL DEFAULT '1',
`sort_order` INT(11)  ,
`sotm_id` INT(11)  ,
`last_modified` DATETIME  ,
PRIMARY KEY (`manufacturers_id`),
UNIQUE INDEX `uri` (`uri` ASC),
UNIQUE INDEX `uk_sotm_id` (`sotm_id` ASC),
INDEX `IDX_MANUFACTURERS_NAME` (`manufacturers_name` ASC),
INDEX `type` (`type` ASC),
INDEX `indx_last_modified` (`last_modified` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `last_modified` ASC),
CONSTRAINT `fk_sotm_manufacturers__manufacturer_type_id`
FOREIGN KEY (`type` )
REFERENCES `sotm_manufacturers_type` (`type_id` ))
ENGINE = InnoDB;