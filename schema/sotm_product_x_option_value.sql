CREATE  TABLE IF NOT EXISTS `sotm_product_x_option_value` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор продукта',
`option_value_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор опции продукта',
`price` INT(11) NOT NULL COMMENT 'Цена товара',
`amount` INT(11) NOT NULL DEFAULT '0' COMMENT 'Количество',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`product_id`, `option_value_id`),
INDEX `fk_sotm_product_x_option_value_` (`option_value_id` ASC),
CONSTRAINT `fk_sotm_product_x_option_value_`
FOREIGN KEY (`option_value_id` )
REFERENCES `sotm_product_option_value` (`option_value_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_product_x_option_value_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;