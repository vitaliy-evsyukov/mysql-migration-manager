CREATE  TABLE IF NOT EXISTS `sotm_product_additional_info` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор товара',
`block_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор блока',
`title` VARCHAR(255)   COMMENT 'Название',
`is_edited` INT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` INT(11) NOT NULL COMMENT 'Дата модификации',
PRIMARY KEY (`product_id`, `block_id`),
UNIQUE INDEX `uk_product_additional_info` (`product_id` ASC, `block_id` ASC),
INDEX `FK_sotm_product_additional_info` (`block_id` ASC),
CONSTRAINT `FK_sotm_product_additional_info`
FOREIGN KEY (`block_id` )
REFERENCES `sotm_blocks` (`id` )
ON DELETE CASCADE,
CONSTRAINT `fk_sotm_product_additional_info__product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;