CREATE TABLE IF NOT EXISTS `sotm_category_x_option` (
`category_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор категории',
`product_option_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор опции',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`category_id`, `product_option_id`),
INDEX `fk_sotm_category_x_option_option_id` (`product_option_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`date_modified` ASC),
CONSTRAINT `fk_sotm_category_x_option_category_id`
FOREIGN KEY (`category_id` )
REFERENCES `sotm_category` (`category_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_category_x_option_option_id`
FOREIGN KEY (`product_option_id` )
REFERENCES `sotm_product_option` (`option_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;