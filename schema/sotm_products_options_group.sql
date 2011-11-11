CREATE  TABLE IF NOT EXISTS `sotm_products_options_group` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`categories_group_id` INT(11) UNSIGNED  ,
`name` VARCHAR(50) NOT NULL,
`order` TINYINT(4) NOT NULL,
`edited` TINYINT(1) NULL DEFAULT '0',
`last_modified` DATETIME  ,
PRIMARY KEY (`id`),
UNIQUE INDEX `uk_option_group__name` (`categories_group_id` ASC, `name` ASC),
INDEX `categories_options_id` (`categories_group_id` ASC),
INDEX `indx_last_modified` (`last_modified` ASC),
CONSTRAINT `sotm_products_options_group_ibfk_1`
FOREIGN KEY (`categories_group_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;