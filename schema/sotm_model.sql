CREATE  TABLE IF NOT EXISTS `sotm_model` (
`model_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор модели',
`name` VARCHAR(64) NOT NULL COMMENT 'Название модели',
`uri` VARCHAR(64) NOT NULL COMMENT 'Uri модели',
`is_popular` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак популярности',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак редактирования записи',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`model_id`),
UNIQUE INDEX `uk_uri` (`uri` ASC) )
ENGINE = InnoDB;