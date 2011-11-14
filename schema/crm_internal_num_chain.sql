CREATE  TABLE IF NOT EXISTS `crm_internal_num_chain` (
`chain_id` INT(11) UNSIGNED NOT NULL COMMENT 'Идентификатор цеочки номеров',
`title` VARCHAR(255) NOT NULL COMMENT 'Название',
PRIMARY KEY (`chain_id`) )
ENGINE = InnoDB;