CREATE  TABLE IF NOT EXISTS `sotm_product_x_compatibility` (
`compatibility_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор подбора',
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор товара',
`amount` INT(11) NOT NULL DEFAULT '1' COMMENT 'Количество товаров в подборе',
`compatibility_order` INT(11) NOT NULL DEFAULT '1' COMMENT 'Номер по порядку',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`is_custom` INT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"',
PRIMARY KEY (`compatibility_id`, `product_id`),
INDEX `fk_sotm_product_compatibility_product_id` (`product_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`is_edited` ASC, `date_modified` ASC),
CONSTRAINT `fk_sotm_product_compatibility_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_product_x_compatibility__compatibility_id`
FOREIGN KEY (`compatibility_id` )
REFERENCES `sotm_compatibility` (`compatibility_id` ))
ENGINE = InnoDB;