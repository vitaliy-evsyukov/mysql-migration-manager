CREATE  TABLE IF NOT EXISTS `sotm_articles` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`categoryid` INT(11) UNSIGNED  ,
`uri` VARCHAR(255)  ,
`status` TINYINT(1) NOT NULL DEFAULT '1',
`is_active` TINYINT(1) NOT NULL DEFAULT '0',
`title` VARCHAR(255)  ,
`title_ex` VARCHAR(255)  ,
`full_title` TEXT NOT NULL,
`description_intro` VARCHAR(255) NOT NULL,
`description` VARCHAR(5120)  ,
`text` LONGTEXT  ,
`meta_title` VARCHAR(255)  ,
`meta_description` VARCHAR(512)  ,
`meta_keywords` VARCHAR(255)  ,
`when_added` TIMESTAMP NULL DEFAULT '0000-00-00 00:00:00',
`is_deleted` TINYINT(1) UNSIGNED NULL DEFAULT '0',
`edited` TINYINT(1) UNSIGNED NULL DEFAULT '0',
`last_modified` DATETIME  ,
`sotm_id` INT(11)  ,
PRIMARY KEY (`id`),
UNIQUE INDEX `categoryid` (`categoryid` ASC, `uri` ASC),
CONSTRAINT `fk_sotm_articles__article_category_id`
FOREIGN KEY (`categoryid` )
REFERENCES `sotm_article_categories` (`id` ))
ENGINE = InnoDB;