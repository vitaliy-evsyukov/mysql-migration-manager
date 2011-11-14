CREATE  TABLE IF NOT EXISTS `crm_filled_form_field` (
`filled_form_field_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор заполненного поля',
`filled_form_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор заполненной формы',
`form_field_id` INT(11) UNSIGNED   COMMENT 'Идентификатор поля формы',
`form_field_value_id` INT(11) UNSIGNED   COMMENT 'Идентификатор значения для списковых полей',
`field_name` VARCHAR(255) NOT NULL COMMENT 'Название поля',
`filled_value` VARCHAR(16384) NOT NULL COMMENT 'Заполненное значение для строковых или числовых полей',
PRIMARY KEY (`filled_form_field_id`),
INDEX `fk_crm_filled_form_field_filled_form` (`filled_form_id` ASC),
INDEX `fk_crm_filled_form_field_form_field` (`form_field_id` ASC),
INDEX `fk_crm_filled_form_field_form_field_value` (`form_field_value_id` ASC),
CONSTRAINT `fk_crm_filled_form_field_filled_form`
FOREIGN KEY (`filled_form_id` )
REFERENCES `crm_filled_form` (`filled_form_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_crm_filled_form_field_form_field`
FOREIGN KEY (`form_field_id` )
REFERENCES `crm_form_field` (`form_field_id` )
ON DELETE SET NULL,
CONSTRAINT `fk_crm_filled_form_field_form_field_value`
FOREIGN KEY (`form_field_value_id` )
REFERENCES `crm_form_field_value` (`form_field_value_id` )
ON DELETE SET NULL)
ENGINE = InnoDB;