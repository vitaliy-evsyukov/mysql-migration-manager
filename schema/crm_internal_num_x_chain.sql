CREATE  TABLE IF NOT EXISTS `crm_internal_num_x_chain` (
`chain_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор цепочки',
`internal_num` INT(11) UNSIGNED NOT NULL COMMENT 'Добавочный номер',
`left` INT(11)  ,
`right` INT(11)  ,
`level` INT(11)  ,
PRIMARY KEY (`chain_id`, `internal_num`),
INDEX `fk_crm_internal_num_x_chain__internal_num` (`internal_num` ASC),
CONSTRAINT `fk_crm_internal_num_x_chain__chan_id`
FOREIGN KEY (`chain_id` )
REFERENCES `crm_internal_num_chain` (`chain_id` )
ON DELETE CASCADE,
CONSTRAINT `fk_crm_internal_num_x_chain__internal_num`
FOREIGN KEY (`internal_num` )
REFERENCES `crm_internal_num` (`internal_num` )
ON DELETE CASCADE)
ENGINE = InnoDB;