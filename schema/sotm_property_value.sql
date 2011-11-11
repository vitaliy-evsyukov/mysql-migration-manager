CREATE  TABLE IF NOT EXISTS `sotm_property_value` (
`property_value_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор значения характеритики',
`name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Значение характеристики',
`uri` VARCHAR(64) NULL DEFAULT '' COMMENT 'Уникальное имя значения характеристики',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак редактирования',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`sotm_id` INT(11)   COMMENT 'Внешний идентификатор',
PRIMARY KEY (`property_value_id`),
UNIQUE INDEX `uk_sotm_id` (`sotm_id` ASC),
INDEX `indx_name` (`name` ASC) )
ENGINE = InnoDB;