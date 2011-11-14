CREATE  TABLE IF NOT EXISTS `sotm_product_x_property` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор продукта',
`property_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор характеристики',
`property_value_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор значения характеристики',
`is_edited` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак редактирования',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`is_custom` INT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"',
PRIMARY KEY (`product_id`, `property_id`),
INDEX `fk_product_property_property_id` (`property_id` ASC),
INDEX `fk_product_property_property_value_id` (`property_value_id` ASC),
CONSTRAINT `fk_product_property_property_id`
FOREIGN KEY (`property_id` )
REFERENCES `sotm_property` (`property_id` ),
CONSTRAINT `fk_product_property_property_value_id`
FOREIGN KEY (`property_value_id` )
REFERENCES `sotm_property_value` (`property_value_id` ),
CONSTRAINT `fk_sotm_product_x_property_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;