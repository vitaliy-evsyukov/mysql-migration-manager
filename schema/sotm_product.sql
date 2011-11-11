CREATE  TABLE IF NOT EXISTS `sotm_product` (
`product_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор продукта',
`manufacturer_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор производителя',
`name` VARCHAR(128) NOT NULL DEFAULT '' COMMENT 'Название продукта',
`uri` VARCHAR(128) NOT NULL COMMENT 'URI продукта',
`price` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Цена продукта',
`sales_price` INT(11) UNSIGNED   COMMENT 'Предыдущая цена',
`model_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор модели',
`amount` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Количество',
`product_status_id` INT(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Идентификатор статуса товара',
`changed_to` INT(11) UNSIGNED   COMMENT 'Идентификатор заменяющего продукта',
`new_version_id` INT(11) UNSIGNED   COMMENT 'Идентификатор новой версии',
`seo_status_id` INT(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Статус SEO',
`rating_manual` TINYINT(4) NOT NULL DEFAULT '0' COMMENT 'Бал \"интересности\" выставленный в ручную',
`rating_internal` TINYINT(4) NOT NULL DEFAULT '0' COMMENT 'Внутренняя оценка для товара',
`is_edited` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак модификации',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`product_id`),
INDEX `fk_sotm_product_manufacturer_id` (`manufacturer_id` ASC),
INDEX `fk_sotm_product_model_id` (`model_id` ASC),
INDEX `fk_sotm_product_product_status_id` (`product_status_id` ASC),
INDEX `indx_name` (`name` ASC, `product_status_id` ASC),
INDEX `fk_sotm_product_changed_to` (`changed_to` ASC),
INDEX `fk_sotm_product_new_version` (`new_version_id` ASC),
INDEX `fk_sotm_product_seo_status` (`seo_status_id` ASC),
CONSTRAINT `fk_sotm_product_changed_to`
FOREIGN KEY (`changed_to` )
REFERENCES `sotm_product` (`product_id` ),
CONSTRAINT `fk_sotm_product_manufacturer_id`
FOREIGN KEY (`manufacturer_id` )
REFERENCES `sotm_manufacturers` (`manufacturers_id` ),
CONSTRAINT `fk_sotm_product_model_id`
FOREIGN KEY (`model_id` )
REFERENCES `sotm_model` (`model_id` ),
CONSTRAINT `fk_sotm_product_new_version`
FOREIGN KEY (`new_version_id` )
REFERENCES `sotm_product` (`product_id` ),
CONSTRAINT `fk_sotm_product__product_status_id`
FOREIGN KEY (`product_status_id` )
REFERENCES `sotm_product_status` (`product_status_id` ),
CONSTRAINT `fk_sotm_product_seo_status`
FOREIGN KEY (`seo_status_id` )
REFERENCES `sotm_seo_status` (`seo_status_id` ))
ENGINE = InnoDB;