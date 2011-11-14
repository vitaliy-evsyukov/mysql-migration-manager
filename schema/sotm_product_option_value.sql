CREATE  TABLE IF NOT EXISTS `sotm_product_option_value` (
`option_value_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор значения опции',
`title` VARCHAR(255) NOT NULL COMMENT 'Название значения',
`option_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор опции',
`value` VARCHAR(255)   COMMENT 'Значение опции (например код цвета)',
`date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата модификации',
`is_deleted` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'Признак удаления',
`date_deleted` TIMESTAMP   COMMENT 'Дата удаления',
PRIMARY KEY (`option_value_id`),
INDEX `fk_sotm_product_option_value_option_id` (`option_id` ASC),
CONSTRAINT `fk_sotm_product_option_value_option_id`
FOREIGN KEY (`option_id` )
REFERENCES `sotm_product_option` (`option_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;