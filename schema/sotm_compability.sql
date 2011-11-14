CREATE  TABLE IF NOT EXISTS `sotm_compatibility` (
`compatibility_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор подбора',
`title` VARCHAR(255) NOT NULL COMMENT 'Назваение подбора',
`cmp_product_id` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимого товара',
`cmp_category_id` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимой категории',
`cmp_manufacturer_id` INT(11) UNSIGNED   COMMENT 'Идентификатор производителя',
`cmp_model_id` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимой модели',
`cmp_property_id_1` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимой характеристики №1',
`cmp_property_value_id_1` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимого значения характеристики №1',
`cmp_property_id_2` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимой характеристики №2',
`cmp_property_value_id_2` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимого значения характеристики №2',
`cmp_property_id_3` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимой характеристики №3',
`cmp_property_value_id_3` INT(11) UNSIGNED   COMMENT 'Идентификатор совместимого значения характеристики №3',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`sotm_id` INT(11)   COMMENT 'Внешний идентификатор',
PRIMARY KEY (`compatibility_id`),
UNIQUE INDEX `uk_sotm_id` (`sotm_id` ASC),
INDEX `fk_sotm_compatibility_cmp_product_id` (`cmp_product_id` ASC),
INDEX `fk_sotm_compatibility_cmp_category_id` (`cmp_category_id` ASC),
INDEX `fk_sotm_compatibility_cmp_model_id` (`cmp_model_id` ASC),
INDEX `fk_sotm_compatibility_cmp_property_id_1` (`cmp_property_id_1` ASC),
INDEX `fk_sotm_compatibility_cmp_property_id_2` (`cmp_property_id_2` ASC),
INDEX `fk_sotm_compatibility_cmp_property_id_3` (`cmp_property_id_3` ASC),
INDEX `fk_sotm_compatibility_cmp_property_value_id_1` (`cmp_property_value_id_1` ASC),
INDEX `fk_sotm_compatibility_cmp_property_value_id_2` (`cmp_property_value_id_2` ASC),
INDEX `fk_sotm_compatibility_cmp_property_value_id_3` (`cmp_property_value_id_3` ASC),
INDEX `fk_sotm_compatibility__manufacturer_id` (`cmp_manufacturer_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`is_edited` ASC, `date_modified` ASC),
CONSTRAINT `fk_sotm_compatibility_cmp_category_id`
FOREIGN KEY (`cmp_category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_compatibility_cmp_model_id`
FOREIGN KEY (`cmp_model_id` )
REFERENCES `sotm_model` (`model_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_compatibility_cmp_product_id`
FOREIGN KEY (`cmp_product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE
ON UPDATE CASCADE,
CONSTRAINT `fk_sotm_compatibility_cmp_property_id_1`
FOREIGN KEY (`cmp_property_id_1` )
REFERENCES `sotm_property` (`property_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_compatibility_cmp_property_id_2`
FOREIGN KEY (`cmp_property_id_2` )
REFERENCES `sotm_property` (`property_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_compatibility_cmp_property_id_3`
FOREIGN KEY (`cmp_property_id_3` )
REFERENCES `sotm_property` (`property_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_compatibility_cmp_property_value_id_1`
FOREIGN KEY (`cmp_property_value_id_1` )
REFERENCES `sotm_property_value` (`property_value_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_compatibility_cmp_property_value_id_2`
FOREIGN KEY (`cmp_property_value_id_2` )
REFERENCES `sotm_property_value` (`property_value_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_compatibility_cmp_property_value_id_3`
FOREIGN KEY (`cmp_property_value_id_3` )
REFERENCES `sotm_property_value` (`property_value_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_compatibility__manufacturer_id`
FOREIGN KEY (`cmp_manufacturer_id` )
REFERENCES `sotm_manufacturers` (`manufacturers_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;