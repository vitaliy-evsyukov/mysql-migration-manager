CREATE  TABLE IF NOT EXISTS `sotm_product_service_info` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор товара',
`exported_to_1c` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак экспорта в 1C',
`date_received` DATETIME NOT NULL COMMENT 'Дата оприходования',
`date_added` DATETIME NOT NULL COMMENT 'Дата добавления',
`date_commented` DATETIME   COMMENT 'Дата последнего комментирования',
PRIMARY KEY (`product_id`),
CONSTRAINT `fk_sotm_product_service_info_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;