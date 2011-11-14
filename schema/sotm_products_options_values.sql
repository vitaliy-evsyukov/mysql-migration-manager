CREATE  TABLE IF NOT EXISTS `sotm_products_options_values` (
`products_options_values_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`categories_options_values_id` INT(11) UNSIGNED NULL DEFAULT '0',
`products_options_values_name` VARCHAR(64) NOT NULL COMMENT 'Значение опции',
`seo_value` VARCHAR(30) NOT NULL COMMENT 'Например если задано 4GB как значение, то seo значение можно указывать 4 GB',
`uri` VARCHAR(255)  ,
`moderate` TINYINT(1) NOT NULL DEFAULT '0',
`edited` TINYINT(1) NOT NULL DEFAULT '0',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0',
`sort_order` INT(11)  ,
`sotm_id` INT(11) UNSIGNED  ,
`last_modified` DATETIME  ,
PRIMARY KEY (`products_options_values_id`),
UNIQUE INDEX `categories_options_values_id` (`categories_options_values_id` ASC, `products_options_values_name` ASC),
INDEX `uri` (`uri` ASC),
INDEX `indx_last_modified` (`last_modified` ASC),
INDEX `is_deleted` (`is_deleted` ASC, `last_modified` ASC),
CONSTRAINT `fk_sotm_products_options_values__category_id`
FOREIGN KEY (`categories_options_values_id` )
REFERENCES `sotm_category` (`category_id` ))
ENGINE = InnoDB;