CREATE  TABLE IF NOT EXISTS `sotm_product_vendor_code` (
`product_vendor_code_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор артикула товара',
`title` VARCHAR(255) NOT NULL COMMENT 'Имя артикура',
`uri` VARCHAR(32) NOT NULL COMMENT 'URI страницы',
`kit` VARCHAR(255)   COMMENT 'Комплект',
`vendor_code` VARCHAR(64)   COMMENT 'VendorCode',
`additional_title` VARCHAR(255)   COMMENT 'Дополнительное название',
`full_title` VARCHAR(512)   COMMENT 'Полное название',
`description_short` VARCHAR(4096)   COMMENT 'Краткое описание',
`meta_description` VARCHAR(512)   COMMENT 'Meta description страницы',
`meta_keywords` VARCHAR(512)   COMMENT 'Meta keywords старницы',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`product_vendor_code_id`) )
ENGINE = InnoDB;