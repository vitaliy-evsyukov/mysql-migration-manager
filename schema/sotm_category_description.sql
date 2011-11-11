CREATE  TABLE IF NOT EXISTS `sotm_category_description` (
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`page_num` INT(11) NOT NULL DEFAULT '1' COMMENT 'Номер страницы по умолчанию',
`additional_title` VARCHAR(128)   COMMENT 'Дополнительное название',
`full_title` VARCHAR(255)   COMMENT 'Полное название',
`image` VARCHAR(64)   COMMENT 'Картинка',
`description_short` VARCHAR(512)   COMMENT 'Краткое описание',
`description` VARCHAR(2048)   COMMENT 'Полное описание',
`meta_title` VARCHAR(255)   COMMENT 'Title страницы',
`meta_description` VARCHAR(255)   COMMENT 'Meta описание страницы',
`meta_keywords` VARCHAR(255)   COMMENT 'Keywords страницы',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`category_id`, `page_num`),
CONSTRAINT `fk_sotm_category_description_category_id`
FOREIGN KEY (`category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;