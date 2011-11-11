CREATE  TABLE IF NOT EXISTS `crm_form` (
`form_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор формы',
`title` VARCHAR(255) NOT NULL COMMENT 'Название формы',
`form_table` VARCHAR(255) COMMENT 'Таблица для храниния заполненных форм',
PRIMARY KEY (`form_id`) )
ENGINE = InnoDB;