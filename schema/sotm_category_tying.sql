CREATE  TABLE IF NOT EXISTS `sotm_category_tying` (
`category_tying_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор набора сопуствующих категори',
`title` VARCHAR(255) NOT NULL COMMENT 'Название набора',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`sotm_id` INT(3) UNSIGNED   COMMENT 'Внешний идентификатор',
PRIMARY KEY (`category_tying_id`),
UNIQUE INDEX `uk_sotm_id` (`sotm_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`is_edited` ASC, `date_modified` ASC) )
ENGINE = InnoDB
COMMENT = 'Сопутствующие категории';