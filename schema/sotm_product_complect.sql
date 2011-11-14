CREATE  TABLE IF NOT EXISTS `sotm_product_complect` (
`product_complect_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор комплекта',
`title` VARCHAR(255)   COMMENT 'Название комплекта',
`discount` INT(11) NOT NULL,
`is_active` INT(1) NOT NULL DEFAULT '1' COMMENT 'Признак публикации',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`product_complect_id`),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`is_edited` ASC, `date_modified` ASC) )
ENGINE = InnoDB;