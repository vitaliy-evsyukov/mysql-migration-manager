CREATE  TABLE IF NOT EXISTS `sotm_product_status_x_category` (
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`product_status_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор статуса товара',
`display_in_product_list` INT(1) NOT NULL DEFAULT '1' COMMENT 'Признак отображения на странице списка товаров',
`display_in_product_page` INT(1) NOT NULL DEFAULT '1' COMMENT 'Признак отображения на странице товара',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`category_id`, `product_status_id`),
INDEX `fk_sotm_product_status_x_category_product_status_id` (`product_status_id` ASC),
CONSTRAINT `fk_sotm_product_status_x_category_category_id`
FOREIGN KEY (`category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_product_status_x_category_product_status_id`
FOREIGN KEY (`product_status_id` )
REFERENCES `sotm_product_status` (`product_status_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;