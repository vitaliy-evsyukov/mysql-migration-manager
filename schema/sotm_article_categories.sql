CREATE  TABLE IF NOT EXISTS `sotm_article_categories` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`pid` INT(11) UNSIGNED  ,
`uri` VARCHAR(255)  ,
`is_active` TINYINT(1) NOT NULL DEFAULT '0',
`title` VARCHAR(255)  ,
`title_ex` VARCHAR(255)  ,
`full_title` VARCHAR(512) NOT NULL COMMENT 'Если надо можно весь Title задать',
`description` VARCHAR(512)  ,
`image` VARCHAR(255)  ,
`meta_title` VARCHAR(255)  ,
`meta_description` VARCHAR(512)  ,
`meta_keywords` VARCHAR(255)  ,
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0',
`edited` TINYINT(1) NOT NULL DEFAULT '1',
`last_modified` DATETIME  ,
`sotm_id` INT(11) UNSIGNED  ,
PRIMARY KEY (`id`),
UNIQUE INDEX `uri` (`uri` ASC),
INDEX `fk_sotm_article_categories__parent_category_id` (`pid` ASC),
CONSTRAINT `fk_sotm_article_categories__parent_category_id`
FOREIGN KEY (`pid` )
REFERENCES `sotm_article_categories` (`id` )
ON DELETE CASCADE)
ENGINE = InnoDB;