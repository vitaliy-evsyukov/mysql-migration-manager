CREATE  TABLE IF NOT EXISTS `crm_form_field_value` (
`form_field_value_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор значения поля',
`form_field_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор поля',
`title` VARCHAR(255)   COMMENT 'Название значения',
`value` VARCHAR(32)   COMMENT 'Дополнительное значение',
`is_default` INT(1) NOT NULL DEFAULT '0' COMMENT 'Значение по умолчанию',
`field_value_order` INT(11) NOT NULL DEFAULT '10' COMMENT 'Номер по порядку следования',
PRIMARY KEY (`form_field_value_id`),
INDEX `fk_crm_form_field_value__form_field_id` (`form_field_id` ASC),
CONSTRAINT `fk_crm_form_field_value__form_field_id`
FOREIGN KEY (`form_field_id` )
REFERENCES `crm_form_field` (`form_field_id` ))
ENGINE = InnoDB;