CREATE  TABLE IF NOT EXISTS `sotm_property_group_x_property` (
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`property_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор характеристики',
`property_group_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор группы характеристик',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак редактирования',
`date_modified` DATETIME NOT NULL COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` DATETIME   COMMENT 'Дата удаления',
`is_custom` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"',
PRIMARY KEY (`category_id`, `property_id`, `property_group_id`),
INDEX `fk_property_group_x_property_property_group_id` (`property_group_id` ASC),
CONSTRAINT `fk_property_group_x_property_property_group_id`
FOREIGN KEY (`property_group_id` )
REFERENCES `sotm_property_group` (`property_group_id` ),
CONSTRAINT `fk_sotm_property_group_x_property__category_property`
FOREIGN KEY (`category_id`, `property_id` )
REFERENCES `sotm_category_x_property` (`category_id`, `property_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;