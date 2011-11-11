CREATE  TABLE IF NOT EXISTS `sotm_property_group` (
`property_group_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор группы характеристик',
`name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Название группы характеристик',
`property_group_order` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Номер по порядку',
`is_edited` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак редактирования',
`date_modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`sotm_id` INT(11) UNSIGNED   COMMENT 'Внешний идентификатор',
PRIMARY KEY (`property_group_id`),
UNIQUE INDEX `uk_sotm_id` (`sotm_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC) )
ENGINE = InnoDB;