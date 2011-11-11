CREATE  TABLE IF NOT EXISTS `sotm_category_x_product` (
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор товара',
`sort_by_name` INT(11) NOT NULL DEFAULT '0' COMMENT 'Порядковый номер при сортировке по названию',
`sort_by_price` INT(11) NOT NULL DEFAULT '0' COMMENT 'Порядковый номер при сортировке по цене',
`sort_by_popularity` INT(11) NOT NULL DEFAULT '0' COMMENT 'Порядковый номер при сортировке по популярности',
`is_edited` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак редактирования',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
`is_custom` INT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"',
PRIMARY KEY (`category_id`, `product_id`),
INDEX `fk_sotm_category_x_product_category_id` (`category_id` ASC),
INDEX `indx_edited` (`is_edited` ASC, `date_modified` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `fk_sotm_category_x_product_product_id` (`product_id` ASC),
INDEX `indx_sort_by_price` (`sort_by_price` ASC),
INDEX `indx_sort_by_popularity` (`sort_by_popularity` ASC),
INDEX `indx_sort_by_name` (`sort_by_name` ASC),
CONSTRAINT `fk_sotm_category_x_product_category_id`
FOREIGN KEY (`category_id` )
REFERENCES `sotm_category` (`category_id` ),
CONSTRAINT `fk_sotm_category_x_product_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;