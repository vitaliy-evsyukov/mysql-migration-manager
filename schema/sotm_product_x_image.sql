CREATE  TABLE IF NOT EXISTS `sotm_product_x_image` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор товара' ,
`image_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор фотографии' ,
`is_default` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"использовать по умолчанию\"' ,
`product_option_value_id` INT(11) UNSIGNED COMMENT 'Идентфикатор значения опции' ,
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации' ,
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации' ,
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления' ,
`date_deleted` TIMESTAMP COMMENT 'Дата удаления' ,
`is_custom` INT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Запись создана партнером\"' ,
PRIMARY KEY (`product_id`, `image_id`) ,
UNIQUE INDEX `u_product_x_image` (`product_id` ASC, `image_id` ASC) ,
INDEX `fk_sotm_product_x_image_image_id` (`image_id` ASC) ,
INDEX `fk_sotm_product_x_image_option_value_id` (`product_option_value_id` ASC) ,
CONSTRAINT `fk_sotm_product_x_image_image_id`
FOREIGN KEY (`image_id` )
REFERENCES `sotm_images` (`id`)
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_product_x_image_option_value_id`
FOREIGN KEY (`product_option_value_id` )
REFERENCES `sotm_product_option_value` (`option_value_id` )
ON DELETE SET NULL,
CONSTRAINT `fk_sotm_product_x_image_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;