CREATE TABLE IF NOT EXISTS `sotm_category` (
`category_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор категории',
`parent_category_id` INT(11) UNSIGNED   COMMENT 'Идентификатор родительской категории',
`name` VARCHAR(128) NOT NULL DEFAULT '' COMMENT 'Название категории',
`uri` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'URI категории',
`type_prefix` VARCHAR(32)   COMMENT 'TypePrefix',
`is_seo` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Товары в категории участвуют в SEO оптимизации по алгоритму Сатори\"',
`is_published` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Признак публикации',
`category_order` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Номер по порядку',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`sotm_id` INT(11) UNSIGNED   COMMENT 'Внешний идентификатор',
PRIMARY KEY (`category_id`),
UNIQUE INDEX `indx_sotm_id` (`sotm_id` ASC),
INDEX `fk_sotm_category_parent_category_id` (`parent_category_id` ASC),
CONSTRAINT `fk_sotm_category_parent_category_id`
FOREIGN KEY (`parent_category_id` )
REFERENCES `sotm_category` (`category_id` ))
ENGINE = InnoDB;