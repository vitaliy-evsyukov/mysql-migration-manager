CREATE  TABLE IF NOT EXISTS `sotm_category_to_category_links` (
`id` INT(11) NOT NULL AUTO_INCREMENT,
`master_category_id` INT(11) UNSIGNED NOT NULL,
`slave_category_id` INT(11) UNSIGNED NOT NULL,
`master_option` INT(11) UNSIGNED,
`slave_option` INT(11) UNSIGNED,
`use_man_model` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '0- игнорировать модель,1-учитывать модель при построении подбора',
`group` SMALLINT(5) UNSIGNED  ,
`sort_order` SMALLINT(5) UNSIGNED  ,
`master_to_slave_option_equation` ENUM('=','<','>','!=','>=','<=') NOT NULL DEFAULT '=',
`limit` INT(11)   COMMENT 'если null выдавать все совпадения',
`slave_filter_option_value_id` INT(11) UNSIGNED  ,
PRIMARY KEY (`id`),
INDEX `master_cat_id_idx` (`master_category_id` ASC),
INDEX `slave_cat_id_idx` (`slave_category_id` ASC),
INDEX `master_option` (`master_option` ASC, `master_category_id` ASC),
INDEX `slave_option` (`slave_option` ASC, `slave_category_id` ASC),
INDEX `slave_filter_option_value_id` (`slave_filter_option_value_id` ASC),
CONSTRAINT `sotm_category_to_category_links_ibfk_1`
FOREIGN KEY (`master_category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE,
CONSTRAINT `sotm_category_to_category_links_ibfk_2`
FOREIGN KEY (`slave_category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE,
CONSTRAINT `sotm_category_to_category_links_ibfk_3`
FOREIGN KEY (`master_option`, `master_category_id` )
REFERENCES `sotm_products_options_name` (`products_options_id`, `categories_options_id` ),
CONSTRAINT `sotm_category_to_category_links_ibfk_4`
FOREIGN KEY (`slave_option`, `slave_category_id` )
REFERENCES `sotm_products_options_name` (`products_options_id`, `categories_options_id` ),
CONSTRAINT `sotm_category_to_category_links_ibfk_5`
FOREIGN KEY (`slave_filter_option_value_id` )
REFERENCES `sotm_products_options_values` (`products_options_values_id` ),
CONSTRAINT `sotm_category_to_category_links_master_category_id`
FOREIGN KEY (`master_category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE,
CONSTRAINT `sotm_category_to_category_links_slave_category_id`
FOREIGN KEY (`slave_category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;