CREATE  TABLE IF NOT EXISTS `sotm_products_options_name` (
`products_options_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`group_id` INT(11) UNSIGNED  ,
`categories_options_id` INT(11) UNSIGNED  ,
`products_options_name` VARCHAR(32) NOT NULL,
`uri` VARCHAR(255)  ,
`is_filter` TINYINT(1) NOT NULL DEFAULT '0',
`is_podbor` TINYINT(1) NOT NULL DEFAULT '0',
`moderate` TINYINT(1) NOT NULL DEFAULT '0',
`edited` TINYINT(1) NOT NULL DEFAULT '0',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0',
`sort_option` INT(11) NOT NULL,
`last_modified` DATETIME  ,
PRIMARY KEY (`products_options_id`),
UNIQUE INDEX `uri` (`uri` ASC, `categories_options_id` ASC),
UNIQUE INDEX `indx_option__name` (`group_id` ASC, `categories_options_id` ASC, `products_options_name` ASC),
INDEX `categories_options_id` (`categories_options_id` ASC, `is_filter` ASC),
INDEX `is_filter` (`is_filter` ASC),
INDEX `indx_last_modified` (`last_modified` ASC),
INDEX `products_options_id` (`products_options_id` ASC, `categories_options_id` ASC),
INDEX `is_deleted` (`is_deleted` ASC, `last_modified` ASC),
CONSTRAINT `fk_sotm_products_options_name__option_group_id`
FOREIGN KEY (`group_id` )
REFERENCES `sotm_products_options_group` (`id` ),
CONSTRAINT `fk_sotm_products_options_name__option_id`
FOREIGN KEY (`categories_options_id` )
REFERENCES `sotm_category` (`category_id` ))
ENGINE = InnoDB;