CREATE  TABLE IF NOT EXISTS `sotm_product_rank` (
`product_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор продукта',
`new` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Новинка\"',
`sales` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Распродажа\"',
`hit` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Хит\"',
`day_product` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Товар дня\"',
`week_product` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Товар недели\"',
`special_offer` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Спец предложение\"',
`recommended_sot` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Рекомендуемые товары\" для sotmarket.ru',
`recommended` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак \"Рекомендуемые товары\"',
`is_edited` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
PRIMARY KEY (`product_id`),
CONSTRAINT `fk_sotm_product_rank_product_id`
FOREIGN KEY (`product_id` )
REFERENCES `sotm_product` (`product_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;