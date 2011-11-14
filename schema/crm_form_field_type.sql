CREATE  TABLE IF NOT EXISTS `crm_form_field_type` (
`field_type` VARCHAR(32) NOT NULL COMMENT 'Тип поля',
`title` VARCHAR(255) NOT NULL COMMENT 'Название типа поля',
`reg_exp` VARCHAR(255) COMMENT 'Регулярное выражение проверки типа',
PRIMARY KEY (`field_type`) )
ENGINE = InnoDB;