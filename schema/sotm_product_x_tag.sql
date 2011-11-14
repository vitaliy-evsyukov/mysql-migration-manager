CREATE  TABLE IF NOT EXISTS `sotm_product_x_tag` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор продукта',
`tag_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор тега',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`is_custom` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"',
PRIMARY KEY (`product_id`, `tag_id`),
INDEX `fk_sotm_product_x_tag_tag_id` (`tag_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`is_edited` ASC, `date_modified` ASC),
CONSTRAINT `fk_sotm_product_x_tag_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_product_x_tag_tag_id`
FOREIGN KEY (`tag_id` )
REFERENCES `sotm_tag` (`tag_id` ))
ENGINE = InnoDB;