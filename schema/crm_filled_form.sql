CREATE  TABLE IF NOT EXISTS `crm_filled_form` (
`filled_form_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Идентификатор заполненной формы',
`form_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор формы конструктора форм',
`add_date` DATETIME NOT NULL COMMENT 'Дата заполнения',
PRIMARY KEY (`filled_form_id`),
UNIQUE INDEX `filled_form_id` (`filled_form_id` ASC),
INDEX `fk_crm_filled_form_form_id` (`form_id` ASC),
CONSTRAINT `fk_crm_filled_form_form_id`
FOREIGN KEY (`form_id` )
REFERENCES `crm_form` (`form_id` ))
ENGINE = InnoDB;