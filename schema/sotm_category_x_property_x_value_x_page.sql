CREATE  TABLE IF NOT EXISTS `sotm_category_x_property_x_value_x_page` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор записи',
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`page_num` INT(11) UNSIGNED NOT NULL COMMENT 'Номер страницы',
`property_id_1` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор характеристики 1',
`property_value_id_1` INT(11) UNSIGNED NOT NULL COMMENT 'Идентфикатор значения характеристики 1',
`property_id_2` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор характеристики 2',
`property_value_id_2` INT(11) UNSIGNED NOT NULL COMMENT 'Идентфикатор значения характеристики 2',
`property_id_3` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор характеристики 3',
`property_value_id_3` INT(11) UNSIGNED NOT NULL COMMENT 'Идентфикатор значения характеристики 3',
`uri` VARCHAR(32) NOT NULL COMMENT 'URI страницы',
`additional_title` VARCHAR(255)   COMMENT 'Дополнительное название',
`full_title` VARCHAR(512)   COMMENT 'Title страницы',
`description_short` VARCHAR(4096)   COMMENT 'Краткое описание',
`meta_description` VARCHAR(512)   COMMENT 'Meta Description',
`meta_keywords` VARCHAR(512)   COMMENT 'Meta Keywords',
`is_edited` INT(1) NOT NULL DEFAULT '1' COMMENT 'Признак редактирования',
`date_modified` DATETIME NOT NULL COMMENT 'Дата модификации',
`is_deleted` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` DATETIME   COMMENT 'Дата удаления',
`is_custom` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"',
PRIMARY KEY (`id`),
UNIQUE INDEX `uk_uri` (`uri` ASC),
INDEX `fk_sotm_category_x_property_x_value_x_page_property_1` (`category_id` ASC, `property_id_1` ASC, `property_value_id_1` ASC),
INDEX `fk_sotm_category_x_property_x_value_x_page_property_2` (`category_id` ASC, `property_id_2` ASC, `property_value_id_2` ASC),
INDEX `fk_sotm_category_x_property_x_value_x_page_property_3` (`category_id` ASC, `property_id_3` ASC, `property_value_id_3` ASC),
INDEX `uk_record_unique` (`category_id` ASC, `page_num` ASC, `property_id_1` ASC, `property_value_id_1` ASC, `property_id_2` ASC, `property_value_id_2` ASC, `property_id_3` ASC, `property_value_id_3` ASC),
CONSTRAINT `fk_sotm_category_x_property_x_value_x_page_property_1`
FOREIGN KEY (`category_id`, `property_id_1`, `property_value_id_1` )
REFERENCES `sotm_category_x_property_x_value` (`category_id`, `property_id`, `property_value_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_category_x_property_x_value_x_page_property_2`
FOREIGN KEY (`category_id`, `property_id_2`, `property_value_id_2` )
REFERENCES `sotm_category_x_property_x_value` (`category_id`, `property_id`, `property_value_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_category_x_property_x_value_x_page_property_3`
FOREIGN KEY (`category_id`, `property_id_3`, `property_value_id_3` )
REFERENCES `sotm_category_x_property_x_value` (`category_id`, `property_id`, `property_value_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;