CREATE  TABLE IF NOT EXISTS `sotm_property` (
`property_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор характеристики',
`name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Название характеристики',
`uri` VARCHAR(64) NULL DEFAULT '' COMMENT 'Уникальное имя характеристики',
`type` ENUM('checkbox','radio','range','select') NOT NULL DEFAULT 'checkbox',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак редактирования',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`sotm_id` INT(11) UNSIGNED   COMMENT 'Внешний идентификатор',
PRIMARY KEY (`property_id`),
UNIQUE INDEX `uk_sotm_id` (`sotm_id` ASC),
INDEX `indx_name` (`name` ASC) )
ENGINE = InnoDB;