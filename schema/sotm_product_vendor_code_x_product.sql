CREATE  TABLE IF NOT EXISTS `sotm_product_vendor_code_x_product` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор продукта',
`product_vendor_code_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор артикула товара',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`product_id`, `product_vendor_code_id`),
CONSTRAINT `fk_product_vendor_code__product_vendor_code_id_2`
FOREIGN KEY (`product_vendor_code_id` )
REFERENCES `sotm_product_vendor_code` (`product_vendor_code_id` ),
CONSTRAINT `fk_sotm_product_x_property_product_id_2`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;