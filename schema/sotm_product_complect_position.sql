CREATE  TABLE IF NOT EXISTS `sotm_product_complect_position` (
`product_complect_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор комплекта',
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор товара',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`product_complect_id`, `product_id`),
INDEX `fk_sotm_product_complect_position__product_id` (`product_id` ASC),
INDEX `indx_deleted` (`is_deleted` ASC, `date_deleted` ASC),
INDEX `indx_edited` (`date_modified` ASC),
CONSTRAINT `fk_sotm_product_complect_position__product_complect_id`
FOREIGN KEY (`product_complect_id` )
REFERENCES `sotm_product_complect` (`product_complect_id` ),
CONSTRAINT `fk_sotm_product_complect_position__product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;