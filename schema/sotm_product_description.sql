CREATE  TABLE IF NOT EXISTS `sotm_product_description` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор товара',
`listing_name` VARCHAR(128)   COMMENT 'Имя товара в списке',
`full_title` VARCHAR(384)   COMMENT 'Полное название',
`meta_description` VARCHAR(255)   COMMENT 'Meta описание страницы',
`meta_keywords` VARCHAR(255)   COMMENT 'Meta keywords страницы',
`description_short` VARCHAR(512)   COMMENT 'Краткое описание товара',
`description` VARCHAR(16384)   COMMENT 'Полное описание товара',
`type_prefix` VARCHAR(64)   COMMENT 'TypePrefix',
`product_vendor_code_id` INT(11) UNSIGNED   COMMENT 'Идентификатор артикула товара', -- Оставлять или в отдельную таблицу?
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
PRIMARY KEY (`product_id`),
INDEX `fk_sotm_product_description__product_vendor_code_id` (`product_vendor_code_id` ASC),
CONSTRAINT `fk_sotm_product_description_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_product_description__product_vendor_code_id`
FOREIGN KEY (`product_vendor_code_id` )
REFERENCES `sotm_product_vendor_code` (`product_vendor_code_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;