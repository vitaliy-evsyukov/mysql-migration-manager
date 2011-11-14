CREATE  TABLE IF NOT EXISTS `crm_form_field` (
`form_field_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор поля формы',
`form_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор формы',
`field_type` VARCHAR(32) NOT NULL DEFAULT 'string' COMMENT 'Тип поля',
`title` VARCHAR(255) NOT NULL COMMENT 'Название поля',
`required` INT(1) NOT NULL DEFAULT '0' COMMENT 'Признак \"Обязательно для заполнения\"',
`default_value` VARCHAR(255)   COMMENT 'Значение по умолчанию',
`field_order` INT(11) NOT NULL DEFAULT '0' COMMENT 'Номер по порядку',
`form_table_column` VARCHAR(255)   COMMENT 'Имя поля таблицы для хранения полей формы',
`stat_name` VARCHAR(16)   COMMENT 'Название поле для статистики',
PRIMARY KEY (`form_field_id`),
INDEX `fk_crm_form_field__form_id` (`form_id` ASC),
INDEX `fk_crm_form_field__field_type` (`field_type` ASC),
CONSTRAINT `fk_crm_form_field__field_type`
FOREIGN KEY (`field_type` )
REFERENCES `crm_form_field_type` (`field_type` ),
CONSTRAINT `fk_crm_form_field__form_id`
FOREIGN KEY (`form_id` )
REFERENCES `crm_form` (`form_id` ))
ENGINE = InnoDB;