CREATE  TABLE IF NOT EXISTS `sotm_category_x_property_x_value` (
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`property_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор характеристики',
`property_value_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор значения',
`weight` INT(11) NOT NULL COMMENT 'Весовой коэффициент значения',
`value_order` INT(11) NOT NULL DEFAULT '1' COMMENT 'Номер по порядку',
`is_edited` INT(1) NOT NULL DEFAULT '1' COMMENT 'Признак редактирования',
`date_modified` DATETIME NOT NULL COMMENT 'Дата модификации',
`is_deleted` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` DATETIME   COMMENT 'Дата удаления',
`is_custom` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись созданная партнером\"',
PRIMARY KEY (`category_id`, `property_id`, `property_value_id`),
INDEX `fk_sotm_category_x_property_x_value__property_value_id` (`property_value_id` ASC),
CONSTRAINT `fk_sotm_category_x_property_x_value`
FOREIGN KEY (`category_id`, `property_id` )
REFERENCES `sotm_category_x_property` (`category_id`, `property_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_category_x_property_x_value__property_value_id`
FOREIGN KEY (`property_value_id` )
REFERENCES `sotm_property_value` (`property_value_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;