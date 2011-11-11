CREATE  TABLE IF NOT EXISTS `sotm_article_x_tag_x_page` (
`article_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор статьи',
`tag_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор тега',
`page_num` INT(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Номер страницы',
`uri` VARCHAR(32) NOT NULL COMMENT 'URI страницы',
`additional_title` VARCHAR(255)   COMMENT 'Дополнительное название',
`full_title` VARCHAR(512)   COMMENT 'Полное название',
`description_short` VARCHAR(4096)   COMMENT 'Краткое описание',
`meta_description` VARCHAR(512)   COMMENT 'Meta description страницы',
`meta_keywords` VARCHAR(512)   COMMENT 'Meta keywords старницы',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`is_custom` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"',
PRIMARY KEY (`article_id`, `tag_id`, `page_num`),
UNIQUE INDEX `uk_uri` (`uri` ASC),
INDEX `fk_sotm_article_x_tag_x_page_tag_id` (`tag_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`is_edited` ASC, `date_modified` ASC),
CONSTRAINT `fk_sotm_article_x_tag_x_page__article_x_tag`
FOREIGN KEY (`article_id`, `tag_id` )
REFERENCES `sotm_article_x_tag` (`article_id`, `tag_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;