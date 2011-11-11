CREATE  TABLE IF NOT EXISTS `crm_internal_num_x_manager` (
`internal_num` INT(11) UNSIGNED NOT NULL COMMENT 'Добавочный номер',
`manager_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор менеджера',
PRIMARY KEY (`internal_num`, `manager_id`),
INDEX `fk_crm_internal_num_x_manager__manager_id` (`manager_id` ASC),
CONSTRAINT `fk_crm_internal_num_x_manager__internal_num`
FOREIGN KEY (`internal_num` )
REFERENCES `crm_internal_num` (`internal_num` )
ON DELETE CASCADE,
CONSTRAINT `fk_crm_internal_num_x_manager__manager_id`
FOREIGN KEY (`manager_id` )
REFERENCES `managers` (`man_id` )
ON DELETE CASCADE)
ENGINE = InnoDB;