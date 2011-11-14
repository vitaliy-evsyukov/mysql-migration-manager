CREATE  TABLE IF NOT EXISTS `sotm_category_x_property` (
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`property_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор характеристики',
`is_filter` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Используется в фильтре\"',
`is_selection` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Используется в подборе\"',
`display_in_short` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Отображать в краткой форме\"',
`property_order` INT(10) UNSIGNED NOT NULL COMMENT 'Номер по порядку',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак редактирования',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления записи',
`is_custom` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"',
PRIMARY KEY (`category_id`, `property_id`),
INDEX `fk_category_property_peoperty_id` (`property_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_property_order` (`property_order` ASC),
CONSTRAINT `fk_category_property_property_id`
FOREIGN KEY (`property_id` )
REFERENCES `sotm_property` (`property_id` ),
CONSTRAINT `fk_sotm_category_x_property_category_id`
FOREIGN KEY (`category_id` )
REFERENCES `sotm_category` (`category_id` ))
ENGINE = InnoDB;